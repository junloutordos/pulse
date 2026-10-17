<?php

return [
    // Folder of a pipeline version used by the demo seeder. Null means: newest folder in data/versions, else the test fixture.
    'demo_version_dir' => env('PULSE_DEMO_VERSION_DIR'),

    // Optional AI narrative for evidence briefs. Off by default; the fixed template always works.
    'ai_narrative' => (bool) env('PULSE_AI_NARRATIVE', false),
];
