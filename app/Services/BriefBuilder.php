<?php

namespace App\Services;

use App\Models\ActionRegisterEntry;
use App\Models\DatasetVersion;
use App\Models\Municipality;
use App\Models\MunicipalityIndicator;
use App\Models\PlannerDecision;

class BriefBuilder
{
    private const ACTION_FOR = [
        'reach' => 'Information and application campaign',
        'attendance' => 'Exam reminder and access review',
        'decline' => 'Follow-up with feeder schools on the decline',
    ];

    private const VERIFY = 'Data verification with the PSHSS custodian';

    private const LIMITATIONS = [
        'Municipal aggregates only; no individual records are used.',
        'The Grade 6 population is an approximate eligible population, not an exact NCE-eligible count.',
        'Straight-line distance and adjacency are context only and do not imply travel routes or shared barriers.',
        'Qualification reflects a selective examination and is not evidence of access barriers.',
        'Decision support only. Staff must validate local circumstances before approving an action.',
    ];

    /** @return array<string, mixed> */
    public function build(DatasetVersion $version, string $psgc): array
    {
        $municipality = Municipality::findOrFail($psgc);
        $rows = MunicipalityIndicator::where('dataset_version_id', $version->id)->get()->keyBy('psgc');
        $ind = $rows[$psgc] ?? abort(404);
        $decision = PlannerDecision::where(['dataset_version_id' => $version->id, 'psgc' => $psgc])->first();
        $entry = ActionRegisterEntry::where(['dataset_version_id' => $version->id, 'psgc' => $psgc])->first();

        $weights = $decision?->weights ?? WeightedScore::BALANCED;
        $weights = array_map('floatval', array_intersect_key($weights, array_flip(WeightedScore::KEYS)));
        $scores = $rows->map(fn ($r) => WeightedScore::score($this->components($r), $weights))->all();
        $ranks = WeightedScore::ranks($scores);
        $score = $scores[$psgc];
        $components = $this->components($ind);
        $weightTotal = array_sum($weights) ?: 1;

        return [
            'municipality' => $municipality,
            'status' => $ind->status,
            'observations' => [
                'Applicants '.$version->cycle => $ind->applicants,
                'Applicants '.($version->prior_cycle ?? 'prior cycle') => $ind->prior_applicants,
                'Examinees '.$version->cycle => $ind->examinees,
                'Grade 6 learners' => $ind->grade6_pop,
                'Applications per 1,000' => $ind->rate_per_1000 === null ? null : round($ind->rate_per_1000, 1),
                'Exam attendance' => $ind->attendance === null ? null : round($ind->attendance * 100).'%',
                'Change vs prior cycle' => $ind->change === null ? null : sprintf('%+d%%', round($ind->change * 100)),
            ],
            'rank' => $ranks[$psgc] ?? null,
            'rankedCount' => count($ranks),
            'score' => $score,
            'components' => array_map(fn ($c) => $c === null ? null : round($c, 3), $components),
            'weights' => array_map(fn ($w) => (int) round(100 * $w / $weightTotal), $weights),
            'notRankedReason' => $ind->not_ranked_reason,
            'action' => $entry?->action ?? ($score === null ? self::VERIFY : $this->focus($components, $weights)),
            'office' => $entry?->office ?? 'PSHS-CRC Outreach',
            'nextReview' => 'Next NCE cycle ('.($version->cycle + 1).')',
            'decision' => $decision ? ['status' => $decision->status, 'reason' => $decision->reason] : null,
            'distance' => null,
            'limitations' => self::LIMITATIONS,
            'provenance' => [
                'source' => $version->source,
                'cycle' => $version->cycle,
                'version' => $version->version,
                'formula' => $version->formula_version,
                'refreshedAt' => $version->refreshed_at->format('Y-m-d'),
            ],
            'synthetic' => $version->source === 'synthetic',
        ];
    }

    /** @return array{reach: ?float, attendance: ?float, decline: ?float} */
    private function components(MunicipalityIndicator $r): array
    {
        return ['reach' => $r->c_reach, 'attendance' => $r->c_attendance, 'decline' => $r->c_decline];
    }

    /** The component that contributes most to the weighted score decides the suggested focus. */
    private function focus(array $components, array $weights): string
    {
        $best = null;
        foreach (WeightedScore::KEYS as $key) {
            if ($components[$key] === null) {
                continue;
            }
            $contribution = $weights[$key] * $components[$key];
            if ($best === null || $contribution > $best[1]) {
                $best = [$key, $contribution];
            }
        }

        return $best ? self::ACTION_FOR[$best[0]] : self::VERIFY;
    }
}
