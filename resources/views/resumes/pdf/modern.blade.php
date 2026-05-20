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
        + count($links)
        + (int) floor($totalSummaryChars / 250);

    $hasDenseShape = $totalExperience >= 4
        && $totalBullets >= 28
        && count($education) >= 3
        && count($skills) >= 18
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
            'education_details' => 4,
            'skills' => 28,
            'links' => 3,
            'summary_chars' => 900,
            'education_detail_chars' => 260,
            'bullet_chars' => 360,
            'current_extra_bullets' => 6,
            'old_extra_bullets' => 3,
            'late_old_job_bullets' => 4,
        ],
        'normal' => [
            'current_job_bullets' => 9,
            'old_job_bullets' => 5,
            'education_details' => 3,
            'skills' => 22,
            'links' => 3,
            'summary_chars' => 700,
            'education_detail_chars' => 220,
            'bullet_chars' => 300,
            'current_extra_bullets' => 5,
            'old_extra_bullets' => 2,
            'late_old_job_bullets' => 2,
        ],
        'compact' => [
            'current_job_bullets' => 7,
            'old_job_bullets' => 3,
            'education_details' => 2,
            'skills' => 18,
            'links' => 2,
            'summary_chars' => 500,
            'education_detail_chars' => 180,
            'bullet_chars' => 260,
            'current_extra_bullets' => 4,
            'old_extra_bullets' => 2,
            'late_old_job_bullets' => 1,
        ],
        'dense' => [
            'current_job_bullets' => 5,
            'old_job_bullets' => 2,
            'education_details' => 1,
            'skills' => 14,
            'links' => 2,
            'summary_chars' => 350,
            'education_detail_chars' => 140,
            'bullet_chars' => 220,
            'current_extra_bullets' => 3,
            'old_extra_bullets' => 1,
            'late_old_job_bullets' => 1,
        ],
    ];

    $budget = $budgets[$mode];

    if (count($experience) <= 2) {
        $budget['current_job_bullets'] += 3;
        $budget['old_job_bullets'] += 2;
    }

    if (count($education) <= 2) {
        $budget['education_details'] += 2;
    }

    $links = array_slice($links, 0, $budget['links']);
    $skills = array_slice($skills, 0, $budget['skills']);
    $summary = $truncate($summary, $budget['summary_chars']);

    $jobBulletLimits = [];
    $unusedBulletSlots = 0;
    $lateExperienceStartsAt = $mode === 'relaxed' || $totalExperience <= 4 ? PHP_INT_MAX : 3;
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

    $firstExperience = $experience[0] ?? [];
    $headline = $text(data_get($personalInformation, 'headline'))
        ?? $text(data_get($personalInformation, 'title'))
        ?? (is_array($firstExperience) ? $text(data_get($firstExperience, 'title')) : null);

    $initials = 'CV';

    if ($name) {
        $parts = preg_split('/\s+/', $name);
        $first = $parts[0] ?? '';
        $last = count($parts) > 1 ? $parts[count($parts) - 1] : '';

        $initials = strtoupper(substr($first, 0, 1) . substr($last ?: $first, 0, 1));
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
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            color: #343233;
            font-family: Helvetica, Arial, sans-serif;
            font-size: 8.2pt;
            line-height: 1.32;
            background: #ffffff;
        }

        body.resume-relaxed {
            font-size: 8.2pt;
            line-height: 1.32;
        }

        body.resume-normal {
            font-size: 7.6pt;
            line-height: 1.26;
        }

        body.resume-compact {
            font-size: 7pt;
            line-height: 1.20;
        }

        body.resume-dense {
            font-size: 6.5pt;
            line-height: 1.15;
        }

        .sidebar-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 36%;
            height: 297mm;
            background: #302e2f;
            z-index: 0;
        }

        .page {
            position: relative;
            z-index: 1;
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .sidebar {
            width: 36%;
            background: transparent;
            color: #ffffff;
            padding: 17mm 9mm 12mm;
            vertical-align: top;
        }

        .resume-normal .sidebar {
            padding: 14mm 8mm 10mm;
        }

        .resume-compact .sidebar {
            padding: 11mm 7mm 9mm;
        }

        .resume-dense .sidebar {
            padding: 9mm 6mm 8mm;
        }

        .main {
            width: 64%;
            background: #ffffff;
            padding: 21mm 12mm 12mm;
            vertical-align: top;
        }

        .resume-normal .main {
            padding: 18mm 10mm 10mm;
        }

        .resume-compact .main {
            padding: 14mm 9mm 9mm;
        }

        .resume-dense .main {
            padding: 11mm 8mm 8mm;
        }

        .logo {
            width: 31mm;
            height: 31mm;
            border: 1.2pt solid #c9b476;
            border-radius: 50%;
            color: #d7c17d;
            text-align: center;
            line-height: 31mm;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 20pt;
            font-weight: 700;
            margin: 0 auto 17mm;
            letter-spacing: 1px;
        }

        .resume-normal .logo {
            width: 28mm;
            height: 28mm;
            line-height: 28mm;
            margin-bottom: 12mm;
            font-size: 18pt;
        }

        .resume-compact .logo {
            width: 24mm;
            height: 24mm;
            line-height: 24mm;
            margin-bottom: 9mm;
            font-size: 16pt;
        }

        .resume-dense .logo {
            width: 21mm;
            height: 21mm;
            line-height: 21mm;
            margin-bottom: 7mm;
            font-size: 14pt;
        }

        h1 {
            margin: 0;
            color: #343233;
            font-size: 23pt;
            line-height: 1.05;
            font-weight: 700;
            letter-spacing: -0.4px;
        }

        .headline {
            margin-top: 2mm;
            color: #686363;
            font-size: 8.5pt;
            letter-spacing: 0.2px;
        }

        .main-header {
            margin-bottom: 18mm;
        }

        .resume-normal .main-header {
            margin-bottom: 13mm;
        }

        .resume-compact .main-header {
            margin-bottom: 9mm;
        }

        .resume-dense .main-header {
            margin-bottom: 6mm;
        }

        .section {
            margin-top: 8mm;
        }

        .resume-normal .section {
            margin-top: 6mm;
        }

        .resume-compact .section {
            margin-top: 4.5mm;
        }

        .resume-dense .section {
            margin-top: 3.5mm;
        }

        .main .section {
            margin-top: 7mm;
        }

        .section-title {
            margin: 0 0 4.5mm;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 8.8pt;
            font-weight: 700;
            letter-spacing: 2.2px;
            text-transform: uppercase;
        }

        .resume-normal .section-title {
            margin-bottom: 3.5mm;
        }

        .resume-compact .section-title {
            margin-bottom: 2.5mm;
            letter-spacing: 1.6px;
        }

        .resume-dense .section-title {
            margin-bottom: 2mm;
            letter-spacing: 1.2px;
        }

        .sidebar .section-title {
            color: #eee4d0;
        }

        .main .section-title {
            color: #5f5554;
        }

        p {
            margin: 0;
        }

        .contact-item {
            margin-bottom: 1.7mm;
            color: #ffffff;
            font-size: 7.2pt;
            font-weight: 600;
            word-break: break-word;
        }

        .contact-label {
            display: inline-block;
            width: 10mm;
            color: #d7c17d;
            font-weight: 700;
            font-size: 6.7pt;
            text-transform: uppercase;
        }

        .sidebar-item {
            margin-bottom: 4.5mm;
            page-break-inside: avoid;
        }

        .resume-normal .sidebar-item {
            margin-bottom: 3.5mm;
        }

        .resume-compact .sidebar-item {
            margin-bottom: 2.5mm;
        }

        .resume-dense .sidebar-item {
            margin-bottom: 2mm;
        }

        .sidebar-title {
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 700;
            margin-bottom: 0.8mm;
        }

        .sidebar-subtitle,
        .sidebar-date {
            color: #ffffff;
            font-size: 6.9pt;
            line-height: 1.35;
        }

        .sidebar-date {
            margin-top: 0.5mm;
        }

        .summary {
            color: #5a5555;
            font-size: 7.4pt;
            line-height: 1.35;
        }

        ul {
            margin: 0;
            padding-left: 4mm;
        }

        li {
            margin: 0 0 1.5mm;
            padding-left: 1mm;
        }

        .resume-normal li {
            margin-bottom: 1.1mm;
        }

        .resume-compact li {
            margin-bottom: 0.8mm;
        }

        .resume-dense li {
            margin-bottom: 0.55mm;
        }

        .sidebar ul {
            color: #ffffff;
            font-size: 7.1pt;
        }

        .timeline-item {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
            margin-bottom: 5mm;
        }

        .resume-normal .timeline-item {
            margin-bottom: 4mm;
        }

        .resume-compact .timeline-item {
            margin-bottom: 3mm;
        }

        .resume-dense .timeline-item {
            margin-bottom: 2.3mm;
        }

        .timeline-marker {
            width: 7mm;
            vertical-align: top;
        }

        .dot {
            width: 2.2mm;
            height: 2.2mm;
            background: #333333;
            border-radius: 50%;
            margin-top: 1.3mm;
        }

        .line {
            width: 0.35mm;
            height: 18mm;
            background: #d7c17d;
            margin-left: 0.92mm;
            margin-top: 1.3mm;
        }

        .resume-compact .line {
            height: 13mm;
        }

        .resume-dense .line {
            height: 10mm;
        }

        .timeline-content {
            vertical-align: top;
            padding-left: 2.5mm;
        }

        .job-date {
            color: #343233;
            font-size: 7.5pt;
            font-weight: 700;
            margin-bottom: 0.5mm;
        }

        .job-title {
            color: #343233;
            font-size: 7.7pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .job-company {
            color: #5b5656;
            font-size: 7.7pt;
            margin-bottom: 1mm;
        }

        .job-location {
            color: #6b6666;
            font-size: 7.2pt;
            font-style: italic;
            margin-bottom: 1mm;
        }

        .main ul {
            color: #555050;
            font-size: 7.1pt;
            margin-top: 1.3mm;
        }

        table,
        tr,
        td {
            page-break-inside: auto;
        }

        .sidebar,
        .main {
            page-break-inside: auto;
        }
    </style>
</head>

<body class="resume-{{ $mode }}">
<div class="sidebar-bg"></div>

<table class="page">
    <tr>
        <td class="sidebar">
            <div class="logo">{{ $initials }}</div>

            @if ($email || $phone || $location || $links !== [])
                <div class="section">
                    <h2 class="section-title">Contact</h2>

                    @if ($phone)
                        <div class="contact-item">
                            <span class="contact-label">Tel</span>{{ $phone }}
                        </div>
                    @endif

                    @if ($email)
                        <div class="contact-item">
                            <span class="contact-label">Mail</span>{{ $email }}
                        </div>
                    @endif

                    @if ($location)
                        <div class="contact-item">
                            <span class="contact-label">Adr</span>{{ $location }}
                        </div>
                    @endif

                    @foreach ($links as $link)
                        <div class="contact-item">
                            <span class="contact-label">Web</span>{{ $link }}
                        </div>
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
                            $schoolLocation = $text(data_get($school, 'location'));
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

                        @continue(! $institution && ! $credential && ! $schoolLocation && ! $dates && $details === [])

                        <div class="sidebar-item">
                            @if ($institution)
                                <div class="sidebar-title">{{ $institution }}</div>
                            @endif

                            @if ($credential)
                                <div class="sidebar-subtitle">{{ $credential }}</div>
                            @endif

                            @if ($schoolLocation)
                                <div class="sidebar-subtitle">{{ $schoolLocation }}</div>
                            @endif

                            @if ($dates)
                                <div class="sidebar-date">{{ $dates }}</div>
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

            @if ($skills !== [])
                <div class="section">
                    <h2 class="section-title">Skills</h2>

                    <ul>
                        @foreach ($skills as $skill)
                            <li>{{ $skill }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($languages !== [])
                <div class="section">
                    <h2 class="section-title">Language</h2>

                    <ul>
                        @foreach ($languages as $language)
                            <li>{{ $language }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </td>

        <td class="main">
            @if ($name || $headline)
                <div class="main-header">
                    @if ($name)
                        <h1>{{ $name }}</h1>
                    @endif

                    @if ($headline)
                        <div class="headline">{{ $headline }}</div>
                    @endif
                </div>
            @endif

            @if ($summary)
                <div class="section">
                    <h2 class="section-title">Summary</h2>
                    <p class="summary">{{ $summary }}</p>
                </div>
            @endif

            @if ($experience !== [])
                <div class="section">
                    <h2 class="section-title">Work Experience</h2>

                    @foreach ($experience as $job)
                        @continue(! is_array($job))

                        @php
                            $title = $text(data_get($job, 'title'));
                            $company = $text(data_get($job, 'company'));
                            $jobLocation = $text(data_get($job, 'location'));
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

                        @continue(! $title && ! $company && ! $jobLocation && ! $dates && $bullets === [])

                        <table class="timeline-item">
                            <tr>
                                <td class="timeline-marker">
                                    <div class="dot"></div>
                                    <div class="line"></div>
                                </td>

                                <td class="timeline-content">
                                    @if ($dates)
                                        <div class="job-date">({{ $dates }})</div>
                                    @endif

                                    @if ($title)
                                        <div class="job-title">{{ $title }}</div>
                                    @endif

                                    @if ($company)
                                        <div class="job-company">{{ $company }}</div>
                                    @endif

                                    @if ($jobLocation)
                                        <div class="job-location">{{ $jobLocation }}</div>
                                    @endif

                                    @if ($bullets !== [])
                                        <ul>
                                            @foreach ($bullets as $bullet)
                                                <li>{{ $bullet }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endforeach
                </div>
            @endif
        </td>
    </tr>
</table>
</body>
</html>
