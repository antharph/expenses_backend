<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacyPolicyPageTest extends TestCase
{
    public function test_privacy_policy_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('privacy'));

        $response->assertOk();
        $response->assertSee('Privacy Policy', false);
        $response->assertSee('MaiExpenses', false);
        $response->assertSee('antharph@gmail.com', false);
    }
}
