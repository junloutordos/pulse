<?php

use App\Services\WeightedScore;

const BALANCED = ['reach' => 1 / 3, 'attendance' => 1 / 3, 'decline' => 1 / 3];

function pythonScores(): array
{
    $lines = file(__DIR__.'/../fixtures/version_mini/scores.jsonl', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    return array_map(fn ($l) => json_decode($l, true), $lines);
}

test('weighted sum of stored components matches the pipeline score', function () {
    foreach (pythonScores() as $row) {
        $got = WeightedScore::score(
            ['reach' => $row['c_reach'], 'attendance' => $row['c_attendance'], 'decline' => $row['c_decline']],
            BALANCED,
        );
        if ($row['score'] === null) {
            expect($got)->toBeNull();
        } else {
            expect($got)->toEqualWithDelta($row['score'], 1e-9);
        }
    }
});

test('components that are null drop out and weights renormalise', function () {
    $score = WeightedScore::score(['reach' => null, 'attendance' => 0.5, 'decline' => 1.0], ['reach' => 1, 'attendance' => 2, 'decline' => 1]);

    expect($score)->toEqualWithDelta(100 * (2 * 0.5 + 1.0) / 3, 1e-9);
});

test('zero weight on every available component gives no score', function () {
    expect(WeightedScore::score(['reach' => null, 'attendance' => 0.5, 'decline' => null], ['reach' => 1, 'attendance' => 0, 'decline' => 0]))->toBeNull();
});

test('ranks use the minimum rank for ties and skip nulls', function () {
    expect(WeightedScore::ranks(['a' => 90.0, 'b' => 50.0, 'c' => 50.0, 'd' => null, 'e' => 10.0]))
        ->toBe(['a' => 1, 'b' => 2, 'c' => 2, 'e' => 4]);
});
