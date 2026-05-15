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
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $name ?? 'Resume' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 20mm 18mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Georgia, "Times New Roman", Times, serif;
            font-size: 10.5pt;
            line-height: 1.32;
        }

        .header {
            text-align: center;
            border-bottom: 1px solid #111;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        h1 {
            margin: 0 0 4px;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 20pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .contact {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.5pt;
        }

        .section {
            margin-top: 10px;
            page-break-inside: avoid;
        }

        .section-title {
            margin: 0 0 5px;
            border-bottom: 1px solid #111;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        p {
            margin: 0;
        }

        .item {
            margin-bottom: 7px;
            page-break-inside: avoid;
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

        .item-date {
            width: 32%;
            text-align: right;
            white-space: nowrap;
        }

        .item-subtitle {
            font-style: italic;
        }

        ul {
            margin: 3px 0 0 17px;
            padding: 0;
        }

        li {
            margin: 0 0 2px;
            padding-left: 2px;
        }

        .inline-list {
            margin: 0;
        }
    </style>
</head>
<body>
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
                    $bullets = $stringList(data_get($job, 'bullets', []));
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
                                        <span>{{ $company }}</span>
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

    @if ($skills !== [])
        <div class="section">
            <h2 class="section-title">Skills</h2>
            <p class="inline-list">{{ implode(', ', $skills) }}</p>
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
                    $details = $stringList(data_get($school, 'details', []));
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
