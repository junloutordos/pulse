<?php

use Illuminate\Support\Facades\DB;

test('database is mysql 8.4 or newer', function () {
    expect(DB::connection()->getDriverName())->toBe('mysql');

    $version = DB::selectOne('select version() as v')->v;

    expect(version_compare($version, '8.4', '>='))->toBeTrue("MySQL reported {$version}");
});

test('up route ok', function () {
    $this->get('/up')->assertOk();
});
