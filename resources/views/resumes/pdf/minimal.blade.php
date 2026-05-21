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

    $personalInformation = data_get($resume, 'personal_information', []);
    $personalInformation = is_array($personalInformation) ? $personalInformation : [];

    $name = $text(data_get($personalInformation, 'name'));
    $email = $text(data_get($personalInformation, 'email'));
    $phone = $text(data_get($personalInformation, 'phone'));
    $location = $text(data_get($personalInformation, 'location'));
    $links = $stringList(data_get($personalInformation, 'links', []));

    $summary = $text(data_get($resume, 'summary'));
    $experience = data_get($resume, 'experience', []);
    $experience = is_array($experience) ? array_values($experience) : [];
    $skills = $stringList(data_get($resume, 'skills', []));
    $education = data_get($resume, 'education', []);
    $education = is_array($education) ? array_values($education) : [];
    $languages = $stringList(data_get($resume, 'languages', []));

    $firstExperience = $experience[0] ?? [];
    $headline = $text(data_get($personalInformation, 'headline'))
        ?? $text(data_get($personalInformation, 'title'))
        ?? (is_array($firstExperience) ? $text(data_get($firstExperience, 'title')) : null);

    $contactItems = array_values(array_filter([
        $phone,
        $location,
        ...$links,
        $email,
    ], static fn (?string $value): bool => $value !== null));

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

    if ($pressure <= 35) {
        $mode = 'relaxed';
    } elseif ($pressure <= 60) {
        $mode = 'normal';
    } elseif ($pressure <= 90) {
        $mode = 'compact';
    } else {
        $mode = 'dense';
    }

    $budgets = [
        'relaxed' => [
            'current_job_bullets' => 9,
            'old_job_bullets' => 5,
            'late_old_job_bullets' => 3,
            'education_details' => 3,
            'skills' => 24,
            'contacts' => 5,
            'summary_chars' => 850,
            'education_detail_chars' => 220,
            'bullet_chars' => 300,
        ],
        'normal' => [
            'current_job_bullets' => 7,
            'old_job_bullets' => 3,
            'late_old_job_bullets' => 2,
            'education_details' => 2,
            'skills' => 20,
            'contacts' => 5,
            'summary_chars' => 650,
            'education_detail_chars' => 180,
            'bullet_chars' => 260,
        ],
        'compact' => [
            'current_job_bullets' => 6,
            'old_job_bullets' => 2,
            'late_old_job_bullets' => 1,
            'education_details' => 1,
            'skills' => 16,
            'contacts' => 4,
            'summary_chars' => 440,
            'education_detail_chars' => 120,
            'bullet_chars' => 220,
        ],
        'dense' => [
            'current_job_bullets' => 4,
            'old_job_bullets' => 1,
            'late_old_job_bullets' => 1,
            'education_details' => 1,
            'skills' => 14,
            'contacts' => 4,
            'summary_chars' => 340,
            'education_detail_chars' => 110,
            'bullet_chars' => 190,
        ],
    ];

    $budget = $budgets[$mode];

    if (count($experience) <= 2 && $totalBullets <= 8 && $totalBulletChars <= 1200) {
        $budget['current_job_bullets'] += 2;
        $budget['old_job_bullets'] += 1;
    }

    if (count($experience) <= 2 && ($totalBullets > 10 || $totalBulletChars > 1600)) {
        $budget['current_job_bullets'] = min($budget['current_job_bullets'], 8);
        $budget['old_job_bullets'] = min($budget['old_job_bullets'], 3);
    }

    if (count($education) <= 2) {
        $budget['education_details'] += 1;
    }

    $contactItems = array_slice($contactItems, 0, $budget['contacts']);
    $skills = array_slice($skills, 0, $budget['skills']);
    $summary = $truncate($summary, $budget['summary_chars']);

    $lateExperienceStartsAt = $mode === 'relaxed' || $totalExperience <= 4 ? PHP_INT_MAX : 3;
    $jobBulletLimit = static function (int $index) use ($budget, $lateExperienceStartsAt): int {
        if ($index === 0) {
            return $budget['current_job_bullets'];
        }

        if ($index >= $lateExperienceStartsAt) {
            return $budget['late_old_job_bullets'];
        }

        return $budget['old_job_bullets'];
    };

    $skillColumns = [[], [], []];

    foreach (array_values($skills) as $index => $skill) {
        $skillColumns[$index % 3][] = $skill;
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
            margin: 15mm 17mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #1f2023;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9.2pt;
            line-height: 1.34;
        }

        body.resume-normal {
            font-size: 8.8pt;
            line-height: 1.24;
        }

        body.resume-compact {
            font-size: 8.35pt;
            line-height: 1.20;
        }

        body.resume-dense {
            font-size: 7.8pt;
            line-height: 1.14;
        }

        .header {
            margin-bottom: 8mm;
        }

        .resume-compact .header,
        .resume-dense .header {
            margin-bottom: 5mm;
        }

        h1 {
            margin: 0;
            font-size: 24pt;
            line-height: 1;
            letter-spacing: 4px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .headline {
            margin-top: 2mm;
            font-size: 11pt;
            letter-spacing: 1.2px;
        }

        .resume-compact .headline,
        .resume-dense .headline {
            margin-top: 1mm;
            font-size: 9pt;
        }

        .contact-bar {
            width: 100%;
            border-collapse: collapse;
            margin: 4mm 0 0;
            background: #e8edf3;
            font-size: 7.2pt;
        }

        .resume-compact .contact-bar,
        .resume-dense .contact-bar {
            margin-top: 2.5mm;
            font-size: 6.4pt;
        }

        .contact-bar td {
            padding: 2mm 2.5mm;
            text-align: center;
            border-right: 2px solid #ffffff;
        }

        .resume-compact .contact-bar td,
        .resume-dense .contact-bar td {
            padding: 1.1mm 2mm;
        }

        .contact-bar td:last-child {
            border-right: 0;
        }

        .section {
            margin-top: 5.2mm;
        }

        .resume-normal .section {
            margin-top: 4.6mm;
        }

        .resume-compact .section,
        .resume-dense .section {
            margin-top: 3.8mm;
        }

        .section-heading {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2.4mm;
        }

        .resume-normal .section-heading {
            margin-bottom: 2mm;
        }

        .resume-compact .section-heading,
        .resume-dense .section-heading {
            margin-bottom: 1.7mm;
        }

        .section-label {
            width: 1%;
            white-space: nowrap;
            padding: 1.7mm 3.5mm;
            background: #e3e9f0;
            color: #000000;
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .resume-normal .section-label {
            padding: 1.35mm 3mm;
            font-size: 8pt;
        }

        .resume-compact .section-label,
        .resume-dense .section-label {
            padding: 1.15mm 2.6mm;
            font-size: 7.7pt;
        }

        .section-rule {
            border-bottom: 1px solid #5b79a0;
        }

        p {
            margin: 0;
        }

        .item {
            margin-bottom: 3.8mm;
            page-break-inside: avoid;
        }

        .resume-normal .item {
            margin-bottom: 3mm;
        }

        .resume-compact .item,
        .resume-dense .item {
            margin-bottom: 2.3mm;
        }

        .item-heading {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1mm;
        }

        .resume-normal .item-heading {
            margin-bottom: 0.5mm;
        }

        .resume-compact .item-heading,
        .resume-dense .item-heading {
            margin-bottom: 0.4mm;
        }

        .item-heading td {
            padding: 0;
            vertical-align: top;
        }

        .item-title {
            font-weight: 700;
        }

        .item-date {
            width: 28%;
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .item-subtitle {
            margin-bottom: 0.6mm;
        }

        ul {
            margin: 1mm 0 0 4.5mm;
            padding: 0;
        }

        .resume-normal ul {
            margin-top: 0.5mm;
            margin-left: 4mm;
        }

        .resume-compact ul,
        .resume-dense ul {
            margin-top: 0.4mm;
            margin-left: 4mm;
        }

        li {
            margin-bottom: 0.65mm;
            padding-left: 0.7mm;
        }

        .resume-normal li {
            margin-bottom: 0.45mm;
            padding-left: 0.4mm;
        }

        .resume-compact li,
        .resume-dense li {
            margin-bottom: 0.35mm;
            padding-left: 0.4mm;
        }

        .skills-table {
            width: 100%;
            border-collapse: collapse;
        }

        .skills-table td {
            width: 33.33%;
            padding: 0 5mm 0 0;
            vertical-align: top;
        }

        .skills-table ul {
            margin-top: 0;
        }
    </style>
</head>

<body class="resume-{{ $mode }}">
    @if ($name || $headline || $contactItems !== [])
        <div class="header">
            @if ($name)
                <h1>{{ $name }}</h1>
            @endif

            @if ($headline)
                <div class="headline">{{ $headline }}</div>
            @endif

            @if ($contactItems !== [])
                <table class="contact-bar">
                    <tr>
                        @foreach ($contactItems as $contactItem)
                            <td>{{ $contactItem }}</td>
                        @endforeach
                    </tr>
                </table>
            @endif
        </div>
    @endif

    @if ($summary)
        <div class="section">
            <table class="section-heading">
                <tr>
                    <td class="section-label">About Me</td>
                    <td class="section-rule"></td>
                </tr>
            </table>
            <p>{{ $summary }}</p>
        </div>
    @endif

    @if ($education !== [])
        <div class="section">
            <table class="section-heading">
                <tr>
                    <td class="section-label">Education</td>
                    <td class="section-rule"></td>
                </tr>
            </table>

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
                    <table class="item-heading">
                        <tr>
                            <td>
                                @if ($institution)
                                    <div class="item-title">{{ $institution }}</div>
                                @endif
                                @if ($credential || $location)
                                    <div>{{ implode(' | ', array_values(array_filter([$credential, $location], static fn (?string $value): bool => $value !== null && $value !== ''))) }}</div>
                                @endif
                            </td>
                            @if ($dates)
                                <td class="item-date">{{ $dates }}</td>
                            @endif
                        </tr>
                    </table>

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

    @if ($skills !== [])
        <div class="section">
            <table class="section-heading">
                <tr>
                    <td class="section-label">Skill</td>
                    <td class="section-rule"></td>
                </tr>
            </table>

            <table class="skills-table">
                <tr>
                    @foreach ($skillColumns as $column)
                        <td>
                            @if ($column !== [])
                                <ul>
                                    @foreach ($column as $skill)
                                        <li>{{ $skill }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        </div>
    @endif

    @if ($experience !== [])
        <div class="section">
            <table class="section-heading">
                <tr>
                    <td class="section-label">Work Experience</td>
                    <td class="section-rule"></td>
                </tr>
            </table>

            @foreach ($experience as $job)
                @continue(! is_array($job))

                @php
                    $title = $text(data_get($job, 'title'));
                    $company = $text(data_get($job, 'company'));
                    $location = $text(data_get($job, 'location'));
                    $startDate = $text(data_get($job, 'start_date'));
                    $endDate = $text(data_get($job, 'end_date'));
                    $dates = implode(' - ', array_values(array_filter([$startDate, $endDate], static fn (?string $value): bool => $value !== null)));
                    $bullets = array_map(
                        static fn (string $bullet): ?string => $truncate($bullet, $budget['bullet_chars']),
                        array_slice($stringList(data_get($job, 'bullets', [])), 0, $jobBulletLimit($loop->index))
                    );
                    $bullets = array_values(array_filter($bullets, static fn (?string $bullet): bool => $bullet !== null));
                    $heading = implode(' - ', array_values(array_filter([$company, $title], static fn (?string $value): bool => $value !== null)));
                @endphp

                @continue(! $heading && ! $location && ! $dates && $bullets === [])

                <div class="item">
                    <table class="item-heading">
                        <tr>
                            <td>
                                @if ($heading)
                                    <div class="item-title">{{ $heading }}</div>
                                @endif
                                @if ($location)
                                    <div class="item-subtitle">{{ $location }}</div>
                                @endif
                            </td>
                            @if ($dates)
                                <td class="item-date">{{ $dates }}</td>
                            @endif
                        </tr>
                    </table>

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

    @if ($languages !== [])
        <div class="section">
            <table class="section-heading">
                <tr>
                    <td class="section-label">Languages</td>
                    <td class="section-rule"></td>
                </tr>
            </table>
            <p>{{ implode(', ', $languages) }}</p>
        </div>
    @endif
</body>
</html>
