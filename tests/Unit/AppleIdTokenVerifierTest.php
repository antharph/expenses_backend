<?php

namespace Tests\Unit;

use App\Services\AppleIdTokenVerifier;
use Tests\TestCase;

class AppleIdTokenVerifierTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.apple.bundle_id' => 'com.maiexpenses.app',
            'services.apple.services_id' => 'com.maiexpenses.service',
        ]);
    }

    public function test_allowed_audiences_includes_bundle_id_and_services_id(): void
    {
        $verifier = new AppleIdTokenVerifier;

        $this->assertSame(
            ['com.maiexpenses.app', 'com.maiexpenses.service'],
            $verifier->allowedAudiences(),
        );
    }

    public function test_allowed_audiences_omits_empty_values(): void
    {
        config([
            'services.apple.bundle_id' => 'com.maiexpenses.app',
            'services.apple.services_id' => '',
        ]);

        $verifier = new AppleIdTokenVerifier;

        $this->assertSame(['com.maiexpenses.app'], $verifier->allowedAudiences());
    }
}
