<?php

declare(strict_types=1);

namespace App\Domains\Analysis\Services;

use InvalidArgumentException;

final class StructuredResumeNormalizer
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $rewrittenBullets
     * @return array<string, mixed>
     */
    public function normalize(array $payload, array $rewrittenBullets): array
    {
        return [
            'personal_information' => $this->personalInformation($payload),
            'summary' => $this->nullableString($payload, 'summary'),
            'experience' => $this->experience($payload, $rewrittenBullets),
            'skills' => $this->stringList($payload, 'skills'),
            'education' => $this->education($payload),
            'languages' => $this->stringList($payload, 'languages'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function personalInformation(array $payload): array
    {
        $personalInformation = $this->object($payload, 'personal_information');

        return [
            'name' => $this->nullableString($personalInformation, 'name'),
            'email' => $this->nullableString($personalInformation, 'email'),
            'phone' => $this->nullableString($personalInformation, 'phone'),
            'location' => $this->nullableString($personalInformation, 'location'),
            'links' => $this->stringList($personalInformation, 'links'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $rewrittenBullets
     * @return array<int, array<string, mixed>>
     */
    private function experience(array $payload, array $rewrittenBullets): array
    {
        $experience = $this->array($payload, 'experience');
        $usedIndexes = [];

        $jobs = array_map(function (mixed $job) use ($rewrittenBullets, &$usedIndexes): array {
            if (! is_array($job)) {
                throw new InvalidArgumentException('Structured resume field [experience] must contain objects.');
            }

            $bulletIndexes = $this->integerList($job, 'bullet_indexes');
            $preservedBullets = [];

            foreach ($bulletIndexes as $index) {
                if (! array_key_exists($index, $rewrittenBullets)) {
                    throw new InvalidArgumentException('Structured resume experience contains an out-of-range rewritten bullet index.');
                }

                if (in_array($index, $usedIndexes, true)) {
                    throw new InvalidArgumentException('Structured resume experience contains duplicate rewritten bullet indexes.');
                }

                $usedIndexes[] = $index;
                $preservedBullets[] = $rewrittenBullets[$index];
            }

            return [
                'company' => $this->nullableString($job, 'company'),
                'title' => $this->nullableString($job, 'title'),
                'location' => $this->nullableString($job, 'location'),
                'start_date' => $this->nullableString($job, 'start_date'),
                'end_date' => $this->nullableString($job, 'end_date'),
                'bullets' => $preservedBullets,
            ];
        }, $experience);

        sort($usedIndexes);

        if ($usedIndexes !== range(0, count($rewrittenBullets) - 1)) {
            throw new InvalidArgumentException('Structured resume experience is missing rewritten bullet indexes.');
        }

        return $jobs;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function education(array $payload): array
    {
        return array_map(function (mixed $education): array {
            if (! is_array($education)) {
                throw new InvalidArgumentException('Structured resume field [education] must contain objects.');
            }

            return [
                'institution' => $this->nullableString($education, 'institution'),
                'degree' => $this->nullableString($education, 'degree'),
                'field' => $this->nullableString($education, 'field'),
                'location' => $this->nullableString($education, 'location'),
                'start_date' => $this->nullableString($education, 'start_date'),
                'end_date' => $this->nullableString($education, 'end_date'),
                'details' => $this->stringList($education, 'details'),
            ];
        }, $this->array($payload, 'education'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function object(array $payload, string $field): array
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("Structured resume field [{$field}] is required.");
        }

        if (! is_array($payload[$field]) || array_is_list($payload[$field])) {
            throw new InvalidArgumentException("Structured resume field [{$field}] must be an object.");
        }

        return $payload[$field];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, mixed>
     */
    private function array(array $payload, string $field): array
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("Structured resume field [{$field}] is required.");
        }

        if (! is_array($payload[$field])) {
            throw new InvalidArgumentException("Structured resume field [{$field}] must be an array.");
        }

        return array_values($payload[$field]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    private function stringList(array $payload, string $field): array
    {
        $items = $this->array($payload, $field);
        $strings = [];

        foreach ($items as $item) {
            if (! is_string($item)) {
                throw new InvalidArgumentException("Structured resume field [{$field}] must contain only strings.");
            }

            $trimmed = trim($item);

            if ($trimmed !== '') {
                $strings[] = $trimmed;
            }
        }

        return array_values($strings);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, int>
     */
    private function integerList(array $payload, string $field): array
    {
        $items = $this->array($payload, $field);

        foreach ($items as $item) {
            if (! is_int($item)) {
                throw new InvalidArgumentException("Structured resume field [{$field}] must contain only integers.");
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function nullableString(array $payload, string $field): ?string
    {
        if (! array_key_exists($field, $payload)) {
            throw new InvalidArgumentException("Structured resume field [{$field}] is required.");
        }

        if ($payload[$field] === null) {
            return null;
        }

        if (! is_string($payload[$field])) {
            throw new InvalidArgumentException("Structured resume field [{$field}] must be a string or null.");
        }

        $value = trim($payload[$field]);

        return $value === '' ? null : $value;
    }
}
