<?php

namespace Tests\Feature;

use Tests\TestCase;

class TimezoneConfigTest extends TestCase
{
    public function test_application_timezone_is_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezone->getName());
    }
}
