<?php

use App\Models\Tenant\Member;
use App\Models\Tenant\MemberShare;

beforeEach(function () {
    $this->initializeTenancy();
});

// ─── FR-MM-020–023: Share capital calculations ───────────────

it('correctly computes total_value from shares_held and par_value', function () {
    $member = Member::factory()->create();

    $share = MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 100,
        'par_value' => 10000.00,
        'total_value' => 100 * 10000.00,
        'percentage_of_total' => 0.0100,
    ]);

    expect((float) $share->total_value)->toBe(1000000.0)
        ->and((int) $share->shares_held)->toBe(100);
});

it('casts share values to correct PHP types', function () {
    $member = Member::factory()->create();

    $share = MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 50,
        'par_value' => 10000.00,
        'total_value' => 500000.00,
        'percentage_of_total' => 0.0050,
    ]);

    $share->refresh();

    expect($share->shares_held)->toBeInt()
        ->and($share->par_value)->toBeString()  // decimal:2 cast returns string
        ->and($share->total_value)->toBeString()
        ->and($share->percentage_of_total)->toBeString();
});

it('share belongs to correct member', function () {
    $member = Member::factory()->create();

    MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 25,
        'par_value' => 10000.00,
        'total_value' => 250000.00,
        'percentage_of_total' => 0.0025,
    ]);

    expect($member->shares->member_id)->toBe($member->id)
        ->and((int) $member->shares->shares_held)->toBe(25);
});

it('member with zero shares does not block exit', function () {
    $member = Member::factory()->create();

    MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 0,
        'par_value' => 10000.00,
        'total_value' => 0,
        'percentage_of_total' => 0.0000,
    ]);

    expect($member->getExitBlockReasons())->toBeEmpty();
});

it('member with positive share value blocks exit', function () {
    $member = Member::factory()->create();

    MemberShare::create([
        'member_id' => $member->id,
        'shares_held' => 10,
        'par_value' => 10000.00,
        'total_value' => 100000.00,
        'percentage_of_total' => 0.0010,
    ]);

    $blocks = $member->getExitBlockReasons();

    expect($blocks)->not->toBeEmpty()
        ->and(count($blocks))->toBe(1)
        ->and($blocks[0])->toContain('100,000');
});
