<?php

namespace Tests\Feature\Stage23;

use Tests\TestCase;

class Stage23DatabaseSafetyTest extends TestCase
{
    public function test_suite_uses_a_named_isolated_testing_database(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $resolvedDatabase = realpath($database);

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', $connection);
        $this->assertStringContainsString('testing', mb_strtolower($database));
        $this->assertNotSame(':memory:', $database);
        $this->assertNotFalse($resolvedDatabase);
        $this->assertStringStartsWith(
            base_path('storage'.DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'cache'),
            $resolvedDatabase,
        );
        $this->assertFileExists(base_path('.env.testing'));
    }
}
