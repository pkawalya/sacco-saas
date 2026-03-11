<?php

use App\Models\Central\Plan;

it('returns all registered modules from config', function () {
    $registry = config('modules');
    expect($registry)->toBeArray()
        ->toHaveKey('member_management')
        ->toHaveKey('savings_deposits')
        ->toHaveKey('loan_management')
        ->toHaveKey('general_ledger')
        ->toHaveKey('digital_channels')
        ->toHaveKey('notifications_engine')
        ->toHaveKey('revenue_expense')
        ->toHaveKey('cost_centres')
        ->toHaveKey('regulatory_compliance')
        ->toHaveKey('collections_engine');
});

it('checks that each module has required keys', function () {
    foreach (config('modules') as $key => $module) {
        expect($module)
            ->toHaveKeys(['label', 'description', 'stage', 'icon']);
    }
});

it('plan can store and retrieve modules', function () {
    $plan = Plan::factory()->create([
        'modules' => ['member_management', 'savings_deposits', 'loan_management'],
        'stage' => 1,
    ]);

    expect($plan->modules)->toBeArray()->toHaveCount(3)
        ->and($plan->hasModule('member_management'))->toBeTrue()
        ->and($plan->hasModule('collections_engine'))->toBeFalse()
        ->and($plan->stage)->toBe(1);
});

it('plan returns active module definitions', function () {
    $plan = Plan::factory()->create([
        'modules' => ['member_management', 'loan_management'],
    ]);

    $definitions = $plan->getActiveModuleDefinitions();

    expect($definitions)->toHaveCount(2)
        ->toHaveKey('member_management')
        ->toHaveKey('loan_management');
});

it('plan with null modules returns empty', function () {
    $plan = Plan::factory()->create([
        'modules' => null,
    ]);

    expect($plan->hasModule('member_management'))->toBeFalse()
        ->and($plan->modules)->toBeNull();
});
