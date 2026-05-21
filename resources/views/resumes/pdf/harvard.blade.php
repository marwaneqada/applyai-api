@php
    $resume = is_array($resume ?? null) ? $resume : [];

    $text = static function (mixed $value): ?string {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    };

    $stringList = static function (mixed $values) use ($text): array {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map($text, $values), static fn (?string $value): bool => $value !== null));
    };

    $personalInformation = data_get($resume, 'personal_information', []);
    $personalInformation = is_array($personalInformation) ? $personalInformation : [];

    $name = $text(data_get($personalInformation, 'name'));
    $contactItems = array_values(array_filter([
        $text(data_get($personalInformation, 'email')),
        $text(data_get($personalInformation, 'phone')),
        $text(data_get($personalInformation, 'location')),
        ...$stringList(data_get($personalInformation, 'links', [])),
    ], static fn (?string $value): bool => $value !== null));

    $summary = $text(data_get($resume, 'summary'));
    $experience = data_get($resume, 'experience', []);
    $experience = is_array($experience) ? array_values($experience) : [];
    $skills = $stringList(data_get($resume, 'skills', []));
    $education = data_get($resume, 'education', []);
    $education = is_array($education) ? array_values($education) : [];
    $languages = $stringList(data_get($resume, 'languages', []));

    $truncate = static function (?string $value, int $max): ?string {
        if ($value === null || strlen($value) <= $max) {
            return $value;
        }

        $cut = substr($value, 0, $max);
        $lastSpace = strrpos($cut, ' ');

        if ($lastSpace !== false) {
            $cut = substr($cut, 0, $lastSpace);
        }

        return rtrim($cut) . '...';
    };

    $totalExperience = count($experience);
    $totalBullets = 0;
    $totalBulletChars = 0;
    $totalEducationDetails = 0;
    $totalSummaryChars = strlen($summary ?? '');

    foreach ($experience as $job) {
        if (! is_array($job)) {
            continue;
        }

        $bullets = $stringList(data_get($job, 'bullets', []));
        $totalBullets += count($bullets);

        foreach ($bullets as $bullet) {
            $totalBulletChars += strlen($bullet);
        }
    }

    foreach ($education as $school) {
        if (! is_array($school)) {
            continue;
        }

        $totalEducationDetails += count($stringList(data_get($school, 'details', [])));
    }

    $pressure = ($totalExperience * 4)
        + $totalBullets
        + (int) floor($totalBulletChars / 250)
        + (count($education) * 3)
        + $totalEducationDetails
        + (int) floor(count($skills) / 2)
        + count($contactItems)
        + (int) floor($totalSummaryChars / 250);

    $hasDenseShape = $totalExperience >= 5
        && $totalBullets >= 32
        && count($education) >= 3
        && count($skills) >= 20
        && ($totalSummaryChars >= 500 || $totalBulletChars >= 3500);

    if ($pressure <= 35) {
        $mode = 'relaxed';
    } elseif ($pressure <= 60) {
        $mode = 'normal';
    } elseif ($pressure <= 90 || ! $hasDenseShape) {
        $mode = 'compact';
    } else {
        $mode = 'dense';
    }

    $budgets = [
        'relaxed' => [
            'current_job_bullets' => 12,
            'old_job_bullets' => 7,
            'late_old_job_bullets' => 4,
            'education_details' => 4,
            'skills' => 30,
            'links' => 3,
            'summary_chars' => 900,
            'education_detail_chars' => 260,
            'bullet_chars' => 340,
            'current_extra_bullets' => 5,
            'old_extra_bullets' => 2,
        ],
        'normal' => [
            'current_job_bullets' => 11,
            'old_job_bullets' => 6,
            'late_old_job_bullets' => 4,
            'education_details' => 4,
            'skills' => 28,
            'links' => 3,
            'summary_chars' => 850,
            'education_detail_chars' => 260,
            'bullet_chars' => 320,
            'current_extra_bullets' => 4,
            'old_extra_bullets' => 2,
        ],
        'compact' => [
            'current_job_bullets' => 13,
            'old_job_bullets' => 5,
            'late_old_job_bullets' => 3,
            'education_details' => 3,
            'skills' => 28,
            'links' => 2,
            'summary_chars' => 750,
            'education_detail_chars' => 220,
            'bullet_chars' => 300,
            'current_extra_bullets' => 4,
            'old_extra_bullets' => 1,
        ],
        'dense' => [
            'current_job_bullets' => 9,
            'old_job_bullets' => 3,
            'late_old_job_bullets' => 2,
            'education_details' => 2,
            'skills' => 22,
            'links' => 2,
            'summary_chars' => 550,
            'education_detail_chars' => 170,
            'bullet_chars' => 260,
            'current_extra_bullets' => 3,
            'old_extra_bullets' => 1,
        ],
    ];

    $budget = $budgets[$mode];

    if (count($experience) <= 2) {
        $budget['current_job_bullets'] += 3;
        $budget['old_job_bullets'] += 2;
        $budget['late_old_job_bullets'] += 2;
    }

    if (count($education) <= 2) {
        $budget['education_details'] += 2;
    }

    $firstJobBullets = is_array($experience[0] ?? null)
        ? $stringList(data_get($experience[0], 'bullets', []))
        : [];
    $firstJobBulletChars = array_sum(array_map('strlen', $firstJobBullets));

    if ($totalExperience >= 4 && $firstJobBulletChars >= 1800) {
        $budget['current_job_bullets'] = max(8, $budget['current_job_bullets'] - 2);
        $budget['current_extra_bullets'] = max(1, $budget['current_extra_bullets'] - 1);
    }

    $contactItems = array_slice($contactItems, 0, 3 + $budget['links']);
    $skills = array_slice($skills, 0, $budget['skills']);
    $summary = $truncate($summary, $budget['summary_chars']);

    $skillMap = [
        'Data' => ['mysql', 'postgresql', 'mongodb', 'mariadb', 'redis', 'power bi', 'big data', 'hadoop', 'machine learning', 'deep learning'],
        'Programming' => ['php', 'java', 'javascript', 'typescript', 'python', 'c++', 'c#', 'html', 'css'],
        'Frameworks' => ['laravel', 'spring boot', 'symfony', 'asp.net', 'jee', 'react', 'vue', 'angular', 'django', 'codeigniter', 'zend'],
        'Tools' => ['docker', 'jenkins', 'git', 'github', 'gitlab', 'grafana', 'loki', 'prometheus', 'supervisor', 'wordpress', 'prestashop'],
        'Practices' => ['rest api', 'restful api', 'graphql', 'microservices', 'domain-driven design', 'ddd', 'event sourcing', 'cqrs', 'ci/cd', 'scrum', 'agile', 'mvc', 'solid'],
    ];
    $skillGroups = array_fill_keys([...array_keys($skillMap), 'Other'], []);

    foreach ($skills as $skill) {
        $normalizedSkill = strtolower($skill);
        $group = 'Other';

        foreach ($skillMap as $candidateGroup => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($normalizedSkill, $needle)) {
                    $group = $candidateGroup;

                    break 2;
                }
            }
        }

        $skillGroups[$group][] = $skill;
    }

    $skillGroups = array_filter($skillGroups, static fn (array $items): bool => $items !== []);

    $jobBulletLimits = [];
    $unusedBulletSlots = 0;
    $lateExperienceStartsAt = match (true) {
        $mode === 'relaxed' || $totalExperience <= 4 => PHP_INT_MAX,
        $mode === 'normal' => 5,
        $mode === 'compact' => 4,
        default => 3,
    };
    $jobBaseBulletLimit = static function (int $index) use ($budget, $lateExperienceStartsAt): int {
        if ($index === 0) {
            return $budget['current_job_bullets'];
        }

        if ($index >= $lateExperienceStartsAt) {
            return $budget['late_old_job_bullets'];
        }

        return $budget['old_job_bullets'];
    };

    foreach ($experience as $index => $job) {
        if (! is_array($job)) {
            continue;
        }

        $bulletCount = count($stringList(data_get($job, 'bullets', [])));
        $baseLimit = $jobBaseBulletLimit($index);

        $jobBulletLimits[$index] = $baseLimit;
        $unusedBulletSlots += max(0, $baseLimit - min($bulletCount, $baseLimit));
    }

    foreach ($experience as $index => $job) {
        if ($unusedBulletSlots <= 0 || ! is_array($job)) {
            continue;
        }

        $bulletCount = count($stringList(data_get($job, 'bullets', [])));
        $extraLimit = match (true) {
            $index === 0 => $budget['current_extra_bullets'],
            $index >= $lateExperienceStartsAt => 0,
            default => $budget['old_extra_bullets'],
        };

        if ($extraLimit <= 0) {
            continue;
        }

        $availableBullets = max(0, $bulletCount - ($jobBulletLimits[$index] ?? 0));
        $extraBullets = min($unusedBulletSlots, $availableBullets, $extraLimit);

        $jobBulletLimits[$index] = ($jobBulletLimits[$index] ?? 0) + $extraBullets;
        $unusedBulletSlots -= $extraBullets;
    }
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $name ?? 'Resume' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 13mm 16mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Georgia, "Times New Roman", Times, serif;
            font-size: 10pt;
            line-height: 1.32;
        }

        body.resume-relaxed {
            font-size: 10pt;
            line-height: 1.32;
        }

        body.resume-normal {
            font-size: 9.6pt;
            line-height: 1.28;
        }

        body.resume-compact {
            font-size: 9pt;
            line-height: 1.22;
        }

        body.resume-dense {
            font-size: 8.3pt;
            line-height: 1.16;
        }

        .header {
            text-align: center;
            border-bottom: 0.45pt solid #444;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        .resume-normal .header {
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .resume-compact .header,
        .resume-dense .header {
            padding-bottom: 4px;
            margin-bottom: 7px;
        }

        h1 {
            margin: 0 0 3px;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 19pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .resume-normal h1 {
            font-size: 17pt;
        }

        .resume-compact h1 {
            font-size: 15.5pt;
            margin-bottom: 2px;
        }

        .resume-dense h1 {
            font-size: 14.5pt;
            margin-bottom: 1px;
        }

        .contact {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 7.8pt;
            line-height: 1.25;
        }

        .resume-compact .contact,
        .resume-dense .contact {
            font-size: 7.3pt;
        }

        .section {
            margin-top: 8px;
        }

        .resume-normal .section {
            margin-top: 7px;
        }

        .resume-compact .section {
            margin-top: 5px;
        }

        .resume-dense .section {
            margin-top: 4px;
        }

        .section-title {
            margin: 0 0 3.5px;
            border-bottom: 0.45pt solid #555;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.2pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .resume-compact .section-title,
        .resume-dense .section-title {
            margin-bottom: 2.5px;
            font-size: 8.6pt;
        }

        p {
            margin: 0;
        }

        .item {
            margin-bottom: 6.5px;
            page-break-inside: avoid;
        }

        .resume-normal .item {
            margin-bottom: 5px;
        }

        .resume-compact .item,
        .resume-dense .item {
            margin-bottom: 4px;
        }

        .item-heading {
            width: 100%;
            border-collapse: collapse;
        }

        .item-heading td {
            padding: 0;
            vertical-align: top;
        }

        .item-title {
            font-weight: 700;
        }

        .item-company {
            font-style: italic;
        }

        .item-date {
            width: 28%;
            text-align: right;
            white-space: nowrap;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.2pt;
        }

        .item-subtitle {
            font-style: italic;
            margin-top: 1px;
        }

        ul {
            margin: 2px 0 0 15px;
            padding: 0;
        }

        .resume-compact ul,
        .resume-dense ul {
            margin-top: 1px;
        }

        li {
            margin: 0 0 1.5px;
            padding-left: 2px;
        }

        .resume-normal li {
            margin-bottom: 1px;
        }

        .resume-compact li,
        .resume-dense li {
            margin-bottom: 0.4px;
        }

        .inline-list {
            margin: 0;
        }

        .skill-line {
            margin: 0 0 2px;
        }

        .skill-category {
            font-weight: 700;
        }
    </style>
</head>
<body class="resume-{{ $mode }}">
    @if ($name || $contactItems !== [])
        <div class="header">
            @if ($name)
                <h1>{{ $name }}</h1>
            @endif

            @if ($contactItems !== [])
                <div class="contact">{{ implode(' | ', $contactItems) }}</div>
            @endif
        </div>
    @endif

    @if ($summary)
        <div class="section">
            <h2 class="section-title">Summary</h2>
            <p>{{ $summary }}</p>
        </div>
    @endif

    @if ($experience !== [])
        <div class="section">
            <h2 class="section-title">Experience</h2>

            @foreach ($experience as $job)
                @continue(! is_array($job))

                @php
                    $title = $text(data_get($job, 'title'));
                    $company = $text(data_get($job, 'company'));
                    $location = $text(data_get($job, 'location'));
                    $startDate = $text(data_get($job, 'start_date'));
                    $endDate = $text(data_get($job, 'end_date'));
                    $dates = implode(' - ', array_values(array_filter([$startDate, $endDate], static fn (?string $value): bool => $value !== null)));
                    $jobBulletLimit = $jobBulletLimits[$loop->index]
                        ?? $jobBaseBulletLimit($loop->index);
                    $bullets = array_map(
                        static fn (string $bullet): ?string => $truncate($bullet, $budget['bullet_chars']),
                        array_slice($stringList(data_get($job, 'bullets', [])), 0, $jobBulletLimit)
                    );
                    $bullets = array_values(array_filter($bullets, static fn (?string $bullet): bool => $bullet !== null));
                @endphp

                @continue(! $title && ! $company && ! $location && ! $dates && $bullets === [])

                <div class="item">
                    @if ($title || $company || $dates)
                        <table class="item-heading">
                            <tr>
                                <td>
                                    @if ($title)
                                        <span class="item-title">{{ $title }}</span>
                                    @endif
                                    @if ($title && $company)
                                        <span>, </span>
                                    @endif
                                    @if ($company)
                                        <span class="item-company">{{ $company }}</span>
                                    @endif
                                </td>
                                @if ($dates)
                                    <td class="item-date">{{ $dates }}</td>
                                @endif
                            </tr>
                        </table>
                    @endif

                    @if ($location)
                        <div class="item-subtitle">{{ $location }}</div>
                    @endif

                    @if ($bullets !== [])
                        <ul>
                            @foreach ($bullets as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($skillGroups !== [])
        <div class="section">
            <h2 class="section-title">Skills</h2>

            @foreach ($skillGroups as $group => $items)
                <p class="skill-line">
                    <span class="skill-category">{{ $group }}:</span>
                    {{ implode(', ', $items) }}
                </p>
            @endforeach
        </div>
    @endif

    @if ($education !== [])
        <div class="section">
            <h2 class="section-title">Education</h2>

            @foreach ($education as $school)
                @continue(! is_array($school))

                @php
                    $institution = $text(data_get($school, 'institution'));
                    $degree = $text(data_get($school, 'degree'));
                    $field = $text(data_get($school, 'field'));
                    $location = $text(data_get($school, 'location'));
                    $startDate = $text(data_get($school, 'start_date'));
                    $endDate = $text(data_get($school, 'end_date'));
                    $dates = implode(' - ', array_values(array_filter([$startDate, $endDate], static fn (?string $value): bool => $value !== null)));
                    $details = array_map(
                        static fn (string $detail): ?string => $truncate($detail, $budget['education_detail_chars']),
                        array_slice($stringList(data_get($school, 'details', [])), 0, $budget['education_details'])
                    );
                    $details = array_values(array_filter($details, static fn (?string $detail): bool => $detail !== null));
                    $credential = implode(', ', array_values(array_filter([$degree, $field], static fn (?string $value): bool => $value !== null)));
                @endphp

                @continue(! $institution && ! $credential && ! $location && ! $dates && $details === [])

                <div class="item">
                    @if ($institution || $dates)
                        <table class="item-heading">
                            <tr>
                                <td>
                                    @if ($institution)
                                        <span class="item-title">{{ $institution }}</span>
                                    @endif
                                </td>
                                @if ($dates)
                                    <td class="item-date">{{ $dates }}</td>
                                @endif
                            </tr>
                        </table>
                    @endif

                    @if ($credential || $location)
                        <div class="item-subtitle">
                            {{ implode(' | ', array_values(array_filter([$credential, $location], static fn (?string $value): bool => $value !== null && $value !== ''))) }}
                        </div>
                    @endif

                    @if ($details !== [])
                        <ul>
                            @foreach ($details as $detail)
                                <li>{{ $detail }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if ($languages !== [])
        <div class="section">
            <h2 class="section-title">Languages</h2>
            <p class="inline-list">{{ implode(', ', $languages) }}</p>
        </div>
    @endif
</body>
</html>
