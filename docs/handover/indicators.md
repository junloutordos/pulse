# Indicator definitions (formula version 1.0)

All indicators are per municipality for one admission cycle. Missing values are never treated as zero.

| Indicator | Definition | Empty when |
|---|---|---|
| Applications per 1,000 | applicants / Grade 6 learners × 1,000 | applicants or Grade 6 count missing, or Grade 6 count is 0 |
| Exam attendance | examinees / applicants | either missing, or applicants is 0 |
| Change vs prior cycle | (applicants − prior applicants) / prior applicants | no prior cycle, prior missing or suppressed, or prior is 0 |
| Qualification | qualifiers / applicants, shown as context only | qualifiers suppressed or missing |

The Grade 6 count is an approximate eligible population, not an exact NCE-eligible count.

## Priority score

Three components, each a 0 to 1 percentile among the municipalities that can be ranked, where a low indicator means higher priority:

- **Reach** = 1 − percentile of applications per 1,000
- **Attendance** = 1 − percentile of exam attendance
- **Decline** = 1 − percentile of change vs prior cycle

`score = 100 × Σ(weight × component) / Σ(weight)` over the components that exist in the dataset.

- A component is dropped when no rankable municipality has it (for example, no Grade 6 counts, or only one cycle). The remaining weights are renormalised and the screens say so.
- Weights are planning preferences, not predictions. Three presets: Balanced (1/3 each), Reach first (0.6/0.2/0.2), Attendance first (0.25/0.55/0.2).
- **Rank band:** the best and worst rank across the three presets. **Stable** means the band spans at most four ranks and the worst rank is within the top 10.
- Rank 1 is the highest score; ties share the lower rank number.

The Python pipeline is the official implementation (`pipeline/src/pulse_pipeline/scoring.py`). The browser recomputes the score live for the weight sliders (`resources/js/lib/scoring.ts`). Both are tested against the same cases in `shared/fixtures/scoring_cases.json`. The brief recomputes only the weighted sum from stored components.

## Suggested focus

Rule-based, from the component that contributes most to the weighted score: reach → information and application campaign; attendance → exam reminder and access review; decline → follow-up with feeder schools. A municipality that is not ranked gets "Data verification with the PSHSS custodian". Staff always validate.

## Spatial indicators

- **Neighbours:** the four nearest municipalities by straight-line distance between town centers.
- **Groupable municipalities:** those within 28 km. Context only: proximity does not mean shared barriers or a feasible route.
- **Clusters:** local Moran's I on applications per 1,000 with the four-nearest-neighbour weights, 999 permutations, two-sided, seed 2026. A cluster is labelled only when p < 0.05; otherwise "no clear pattern".
