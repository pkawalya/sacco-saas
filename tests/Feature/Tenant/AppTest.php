<?php

use App\Models\Central\Tenant;

test('tenant application returns successful response', function () {
    // Use the existing 'testing' tenant which has a database created
    $tenant = Tenant::find('testing');

    // Create a domain for the testing tenant if not exists
    $domain = 'testing.localhost';
    if (! $tenant->domains()->where('domain', $domain)->exists()) {
        $tenant->domains()->create(['domain' => $domain]);
    }

    // The landing page is served on all domains (central routes have no domain constraint)
    $response = $this->get("http://{$domain}");
    $response->assertStatus(200);
});
