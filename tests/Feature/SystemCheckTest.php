<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemCheckTest extends TestCase
{
    public function test_system_check_is_accessible_in_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->get(route('system-check.index'));

        $response
            ->assertOk()
            ->assertSeeText('Pemeriksaan Sistem')
            ->assertSeeText('Koneksi database')
            ->assertSeeText('Tersambung')
            ->assertSee('assets/css/pages/system-check.css', false)
            ->assertSee('assets/js/pages/system-check.js', false);
    }

    public function test_system_check_returns_not_found_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->get(route('system-check.index'))->assertNotFound();
    }

    public function test_system_check_does_not_expose_credentials_or_secrets(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        config([
            'app.key' => 'base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=',
            'database.connections.sqlite.username' => 'pengguna-sangat-rahasia',
            'database.connections.sqlite.password' => 'password-sangat-rahasia',
        ]);

        $response = $this->get(route('system-check.index'));

        $response
            ->assertOk()
            ->assertDontSee('base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=')
            ->assertDontSee('pengguna-sangat-rahasia')
            ->assertDontSee('password-sangat-rahasia')
            ->assertDontSeeText(base_path())
            ->assertSeeText('APP_KEY')
            ->assertSeeText('Tersedia');
    }
}
