<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->setupPostgres();
});

it('дополняет общие единицы без потери ID, сокращений и данных организаций', function () {
    $table = DB::table('goods.unit_goods');
    $minute = $table->insertGetId(['name' => ' минута ', 'shortname' => '  ', 'status' => 2]);
    $hour = $table->insertGetId(['name' => 'Час', 'shortname' => 'ч.']);
    $tenant = $table->insertGetId(['name' => 'Метр', 'tenant_id' => 'tenant_a']);
    $deleted = $table->insertGetId(['name' => 'Грамм', 'deleted_at' => now()]);
    $kilogram = $table->insertGetId(['name' => 'кг']);
    $migration = require database_path('migrations/2026_09_10_000023_complete_good_units.php');

    $migration->up();

    expect(DB::table('goods.unit_goods')->where('id', $kilogram)->value('shortname'))->toBe('кг.');
    expect(DB::table('goods.unit_goods')->where('id', $kilogram)->value('name'))->toBe('кг');
    expect($table->where('id', $minute)->value('shortname'))->toBe('мин.');
    expect(DB::table('goods.unit_goods')->where('id', $minute)->value('status'))->toBe(2);
    expect(DB::table('goods.unit_goods')->where('id', $hour)->value('shortname'))->toBe('ч.');
    expect(DB::table('goods.unit_goods')->where('id', $tenant)->value('shortname'))->toBeNull();
    expect(DB::table('goods.unit_goods')->where('id', $deleted)->value('shortname'))->toBeNull();
    expect(DB::table('goods.unit_goods')->whereNull('tenant_id')->whereNull('deleted_at')->count())->toBe(38);
    expect(DB::table('goods.unit_goods')->where('name', 'Доллар')->value('shortname'))->toBe('$');
    expect(DB::table('goods.unit_goods')->where('name', 'Палета')->value('shortname'))->toBe('паллета');

    $before = DB::table('goods.unit_goods')->orderBy('id')->get()->toJson();
    $migration->up();
    expect(DB::table('goods.unit_goods')->orderBy('id')->get()->toJson())->toBe($before);
});
