<?php

namespace Tests\Feature\Demo;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class DemoDataSecurityTest extends DemoDataTestCase
{
    public function test_demo_data_contains_no_plaintext_password_or_web_endpoint(): void
    {
        $this->seedDemo();

        $owner = User::query()->where('username', 'demo_owner')->firstOrFail();
        $this->assertNotSame('DemoTesting-Only-2026!', $owner->getRawOriginal('password'));
        $this->assertTrue(Hash::check('DemoTesting-Only-2026!', $owner->password));
        $serializedLogs = ActivityLog::query()->get()->map(
            fn (ActivityLog $log): string => mb_strtolower($log->description.' '.json_encode($log->metadata)),
        )->implode(' ');
        $this->assertStringNotContainsString('demotesting-only-2026', $serializedLogs);
        $this->assertFalse(collect(Route::getRoutes())->contains(fn ($route): bool => str_contains($route->uri(), 'demo')));
    }
}
