<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $databasePath = dirname(__DIR__)
            .DIRECTORY_SEPARATOR.'storage'
            .DIRECTORY_SEPARATOR.'framework'
            .DIRECTORY_SEPARATOR.'cache'
            .DIRECTORY_SEPARATOR.'sistem_kasir_testing.sqlite';

        if (! is_file($databasePath) && ! touch($databasePath)) {
            throw new \RuntimeException('Database SQLite khusus testing tidak dapat dibuat.');
        }

        parent::setUp();

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        if (
            ! app()->environment('testing')
            || ! str_contains(mb_strtolower($database), 'test')
        ) {
            throw new \RuntimeException(
                'Pengujian dihentikan karena database tidak teridentifikasi sebagai database testing.',
            );
        }
    }
}
