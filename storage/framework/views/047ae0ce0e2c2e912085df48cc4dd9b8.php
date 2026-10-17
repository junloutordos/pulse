<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>PULSE evidence brief: <?php echo e($municipality->name); ?></title>
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
<?php if($synthetic): ?>
    <div class="watermark">SYNTHETIC DATA</div>
<?php endif; ?>
<div class="page">
    <table class="head"><tr>
        <td>
            <div class="muted">PULSE evidence brief · NCE <?php echo e($provenance['cycle']); ?> cycle</div>
            <h1><?php echo e($municipality->name); ?>, <?php echo e($municipality->province); ?></h1>
            <div class="muted">PSGC <?php echo e($municipality->psgc); ?></div>
        </td>
        <td style="text-align:right">
            <?php if($rank !== null): ?>
                <div class="status">Priority #<?php echo e($rank); ?> of <?php echo e($rankedCount); ?></div>
                <div class="muted">Score <?php echo e(number_format($score, 0)); ?> of 100</div>
            <?php else: ?>
                <div class="status">Not ranked</div>
            <?php endif; ?>
        </td>
    </tr></table>

    <h2>Summary</h2>
    <p><?php echo e($summary); ?></p>
    <?php if($summaryIsAi): ?>
        <p class="muted">AI-assisted summary. Generated from aggregate figures only; staff must validate it.</p>
    <?php endif; ?>

    <table><tr>
        <td class="col">
            <h2>What we observed</h2>
            <table>
                <?php $__currentLoopData = $observations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr><th><?php echo e($label); ?></th><td class="num"><?php echo e($value ?? 'not available'); ?></td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </table>
        </td>
        <td style="width:4%"></td>
        <td class="col">
            <h2>Why it is on the list</h2>
            <?php if($rank !== null): ?>
                <table>
                    <tr><th>Reach component</th><td class="num"><?php echo e($components['reach'] ?? 'n/a'); ?></td></tr>
                    <tr><th>Attendance component</th><td class="num"><?php echo e($components['attendance'] ?? 'n/a'); ?></td></tr>
                    <tr><th>Decline component</th><td class="num"><?php echo e($components['decline'] ?? 'n/a'); ?></td></tr>
                </table>
                <p class="muted">Weights used: Reach <?php echo e($weights['reach']); ?>%, Attendance <?php echo e($weights['attendance']); ?>%, Decline <?php echo e($weights['decline']); ?>%. Weights are planning preferences, not predictions.</p>
            <?php else: ?>
                <p><b>Not ranked:</b> <?php echo e($notRankedReason); ?>. This municipality has no priority score until the data is verified.</p>
            <?php endif; ?>
        </td>
    </tr></table>

    <h2>Proposed action</h2>
    <p><b><?php echo e($action); ?></b></p>
    <table>
        <tr><th>Responsible office</th><td><?php echo e($office); ?></td></tr>
        <tr><th>Next review</th><td><?php echo e($nextReview); ?></td></tr>
        <?php if($decision): ?>
            <tr><th>Planner decision</th><td><?php echo e(ucfirst($decision['status'])); ?>: <?php echo e($decision['reason']); ?></td></tr>
        <?php endif; ?>
    </table>

    <h2>Limitations</h2>
    <ul>
        <?php $__currentLoopData = $limitations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li><?php echo e($item); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>

    <div class="prov">
        Source <?php echo e($provenance['source']); ?> · Cycle <?php echo e($provenance['cycle']); ?> · Dataset <?php echo e($provenance['version']); ?> · Formula <?php echo e($provenance['formula']); ?> · Refreshed <?php echo e($provenance['refreshedAt']); ?>.
        Decision support only; staff validation required.
    </div>
</div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/brief.blade.php ENDPATH**/ ?>