<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginMobileExperienceTest extends TestCase
{
    public function test_login_page_exposes_mobile_identity_role_summaries_and_accessible_controls(): void
    {
        $response = $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Kelola kegiatan toko dengan lebih mudah.')
            ->assertSeeText('Pemilik toko dan seluruh cabang.')
            ->assertSeeText('Pengelola kegiatan satu cabang.')
            ->assertSeeText('Pelayanan transaksi dan cetak struk.')
            ->assertSee('data-login-title', false)
            ->assertSee('data-password-show-label="Tampilkan password"', false)
            ->assertSee('data-password-hide-label="Sembunyikan password"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertSee('autocomplete="current-password"', false)
            ->assertSee('aria-live="polite"', false);

        $content = $response->getContent();

        $this->assertSame(3, substr_count($content, 'name="login_role"'));
        $this->assertSame(3, substr_count($content, 'type="radio"'));
        $this->assertStringNotContainsString('onclick=', $content);
        $this->assertStringNotContainsString('Password123', $content);
        $this->assertStringNotContainsString('Akun demo', $content);
    }

    public function test_login_css_provides_keyboard_safe_mobile_breakpoints_and_touch_targets(): void
    {
        $css = file_get_contents(public_path('assets/css/pages/auth/login.css'));

        $this->assertStringContainsString('min-height: 100dvh', $css);
        $this->assertStringContainsString('@media (max-width: 1024px)', $css);
        $this->assertStringContainsString('@media (max-width: 768px)', $css);
        $this->assertStringContainsString('@media (max-width: 480px)', $css);
        $this->assertStringContainsString('font-size: 16px', $css);
        $this->assertStringContainsString('min-height: 44px', $css);
        $this->assertDoesNotMatchRegularExpression('/(?<!min-)height:\s*100vh/', $css);
        $this->assertStringNotContainsString('position: fixed', $css);
        $this->assertStringNotContainsString('width: 100vw', $css);
    }

    public function test_login_javascript_updates_context_and_prevents_repeated_submission(): void
    {
        $loginScript = file_get_contents(public_path('assets/js/pages/auth/login.js'));
        $passwordScript = file_get_contents(public_path('assets/js/components/password-toggle.js'));

        $this->assertStringContainsString('data-login-title', $loginScript);
        $this->assertStringContainsString('Pilih Jenis Akun Terlebih Dahulu', $loginScript);
        $this->assertStringContainsString('Memeriksa akun...', $loginScript);
        $this->assertStringContainsString('if (isSubmitting)', $loginScript);
        $this->assertStringContainsString('event.preventDefault();', $loginScript);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $loginScript);
        $this->assertStringContainsString('passwordShowLabel', $passwordScript);
        $this->assertStringContainsString('passwordHideLabel', $passwordScript);
        $this->assertStringContainsString('input.focus()', $passwordScript);
        $this->assertStringNotContainsString('jQuery', $loginScript.$passwordScript);
    }
}
