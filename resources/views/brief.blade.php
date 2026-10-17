<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PULSE evidence brief: {{ $municipality->name }}</title>
<style>
    @page { margin: 22mm 18mm; }
    body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 10.5pt; color: #14232b; line-height: 1.45; margin: 0; }
    .page { position: relative; z-index: 1; max-width: 740px; margin: 0 auto; padding: 24px; }
    .watermark { position: fixed; top: 38%; left: 0; right: 0; text-align: center; font-size: 46pt; font-weight: bold; color: #f1dcae; z-index: 0; transform: rotate(-24deg); }
    h1 { font-size: 20pt; margin: 2px 0 0; }
    h2 { font-size: 11pt; margin: 18px 0 6px; text-transform: uppercase; letter-spacing: .06em; color: #566a70; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 4px 6px; border-bottom: 1px solid #d5dfdc; text-align: left; vertical-align: top; }
    th { color: #566a70; font-weight: normal; width: 55%; }
    td.num { text-align: right; }
    .head td { border: 0; padding: 0; }
    .head { border-bottom: 2px solid #14232b; padding-bottom: 8px; margin-bottom: 8px; }
    .muted { color: #566a70; font-size: 9pt; }
    .status { font-weight: bold; }
    .prov { margin-top: 20px; padding-top: 8px; border-top: 1px solid #d5dfdc; font-size: 8.5pt; color: #566a70; }
    ul { margin: 4px 0 0 16px; padding: 0; }
    .col { width: 48%; vertical-align: top; }
</style>
</head>
<body>
@if ($synthetic)
    <div class="watermark">SYNTHETIC DATA</div>
@endif
<div class="page">
    <table class="head"><tr>
        <td>
            <div class="muted">PULSE evidence brief · NCE {{ $provenance['cycle'] }} cycle</div>
            <h1>{{ $municipality->name }}, {{ $municipality->province }}</h1>
            <div class="muted">PSGC {{ $municipality->psgc }}</div>
        </td>
        <td style="text-align:right">
            @if ($rank !== null)
                <div class="status">Priority #{{ $rank }} of {{ $rankedCount }}</div>
                <div class="muted">Score {{ number_format($score, 0) }} of 100</div>
            @else
                <div class="status">Not ranked</div>
            @endif
        </td>
    </tr></table>

    <h2>Summary</h2>
    <p>{{ $summary }}</p>
    @if ($summaryIsAi)
        <p class="muted">AI-assisted summary. Generated from aggregate figures only; staff must validate it.</p>
    @endif

    <table><tr>
        <td class="col">
            <h2>What we observed</h2>
            <table>
                @foreach ($observations as $label => $value)
                    <tr><th>{{ $label }}</th><td class="num">{{ $value ?? 'not available' }}</td></tr>
                @endforeach
            </table>
        </td>
        <td style="width:4%"></td>
        <td class="col">
            <h2>Why it is on the list</h2>
            @if ($rank !== null)
                <table>
                    <tr><th>Reach component</th><td class="num">{{ $components['reach'] ?? 'n/a' }}</td></tr>
                    <tr><th>Attendance component</th><td class="num">{{ $components['attendance'] ?? 'n/a' }}</td></tr>
                    <tr><th>Decline component</th><td class="num">{{ $components['decline'] ?? 'n/a' }}</td></tr>
                </table>
                <p class="muted">Weights used: Reach {{ $weights['reach'] }}%, Attendance {{ $weights['attendance'] }}%, Decline {{ $weights['decline'] }}%. Weights are planning preferences, not predictions.</p>
            @else
                <p><b>Not ranked:</b> {{ $notRankedReason }}. This municipality has no priority score until the data is verified.</p>
            @endif
        </td>
    </tr></table>

    <h2>Proposed action</h2>
    <p><b>{{ $action }}</b></p>
    <table>
        <tr><th>Responsible office</th><td>{{ $office }}</td></tr>
        <tr><th>Next review</th><td>{{ $nextReview }}</td></tr>
        @if ($decision)
            <tr><th>Planner decision</th><td>{{ ucfirst($decision['status']) }}: {{ $decision['reason'] }}</td></tr>
        @endif
    </table>

    <h2>Limitations</h2>
    <ul>
        @foreach ($limitations as $item)
            <li>{{ $item }}</li>
        @endforeach
    </ul>

    <div class="prov">
        Source {{ $provenance['source'] }} · Cycle {{ $provenance['cycle'] }} · Dataset {{ $provenance['version'] }} · Formula {{ $provenance['formula'] }} · Refreshed {{ $provenance['refreshedAt'] }}.
        Decision support only; staff validation required.
    </div>
</div>
</body>
</html>
