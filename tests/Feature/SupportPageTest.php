<?php

namespace Tests\Feature;

use Tests\TestCase;

class SupportPageTest extends TestCase
{
    public function test_support_page_is_publicly_accessible(): void
    {
        $response = $this->get(route('support'));

        $response->assertOk();
        $response->assertSee('Support', false);
        $response->assertSee('antharph@gmail.com', false);
        $response->assertSee('+639989566051', false);
    }
}
