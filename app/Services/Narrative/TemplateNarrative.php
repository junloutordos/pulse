<?php

namespace App\Services\Narrative;

/** The fixed-template summary. Always available, never leaves the server, and states only what the data shows. */
class TemplateNarrative
{
    /** @param array<string, mixed> $d */
    public function summarize(array $d): string
    {
        $name = $d['municipality']->name;
        $action = $d['action'];

        if ($d['rank'] === null) {
            return "{$name} is not ranked: {$d['notRankedReason']}. The suggested next step is {$action}. Staff should validate the data before planning outreach.";
        }

        $obs = $d['observations'];
        $show = fn (string $key) => $obs[$key] ?? null ?: 'not available';
        $score = number_format($d['score'], 0);
        $basis = ($d['weightsBasis'] ?? 'balanced') === 'decision' ? 'the weights recorded with the planner decision' : 'Balanced weights';

        return "{$name} ranks #{$d['rank']} of {$d['rankedCount']} at {$basis} (score {$score}). "
            .'It shows '.$show('Applications per 1,000').' applications per 1,000 Grade 6 learners, '
            .$show('Exam attendance').' exam attendance and '.$show('Change vs prior cycle').' in applications compared with the prior cycle. '
            ."The suggested focus is {$action}. Staff should validate local circumstances before acting.";
    }
}
