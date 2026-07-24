<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DesignSystemTest extends TestCase
{
    public function test_design_system_is_accessible_in_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->get(route('design-system.index'));

        $response
            ->assertOk()
            ->assertSeeText('Design System')
            ->assertSeeText('Tahap 2')
            ->assertSee('assets/css/pages/design-system.css', false)
            ->assertSee('assets/js/pages/design-system.js', false);
    }

    public function test_design_system_returns_not_found_in_production_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->get(route('design-system.index'))->assertNotFound();
    }

    public function test_design_system_renders_without_a_database_connection(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        DB::shouldReceive('connection')->never();

        $this->get(route('design-system.index'))
            ->assertOk()
            ->assertSeeText('Fondasi antarmuka');
    }

    public function test_print_layout_does_not_render_application_navigation(): void
    {
        $html = view('layouts.print')->render();

        $this->assertStringContainsString('print-document', $html);
        $this->assertStringContainsString('assets/css/print/receipt.css', $html);
        $this->assertStringNotContainsString('app-sidebar', $html);
        $this->assertStringNotContainsString('app-navbar', $html);
    }
}
