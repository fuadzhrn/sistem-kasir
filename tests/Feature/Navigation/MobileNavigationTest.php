<?php

namespace Tests\Feature\Navigation;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Authorization\AuthorizationTestCase;

class MobileNavigationTest extends AuthorizationTestCase
{
    public function test_application_layout_contains_accessible_mobile_drawer_controls(): void
    {
        $layout = (string) file_get_contents(resource_path('views/layouts/app.blade.php'));
        $navbar = (string) file_get_contents(resource_path('views/partials/navbar.blade.php'));
        $sidebar = (string) file_get_contents(resource_path('views/partials/sidebar.blade.php'));

        $this->assertStringContainsString('data-drawer-overlay', $layout);
        $this->assertStringContainsString('assets/js/components/sidebar.js', $layout);
        $this->assertStringContainsString('data-drawer-toggle', $navbar);
        $this->assertStringContainsString('aria-controls="app-navigation"', $navbar);
        $this->assertStringContainsString('aria-expanded="false"', $navbar);
        $this->assertStringContainsString('aria-label="Buka menu navigasi"', $navbar);
        $this->assertStringContainsString('id="app-navigation"', $sidebar);
        $this->assertStringContainsString('aria-label="Navigasi utama"', $sidebar);
        $this->assertStringContainsString('data-drawer-close', $sidebar);
        $this->assertStringContainsString('aria-label="Tutup menu navigasi"', $sidebar);
        $this->assertStringNotContainsString('onclick=', $layout.$navbar.$sidebar);
    }

    #[DataProvider('roleCases')]
    public function test_drawer_keeps_user_role_branch_active_menu_and_secure_logout(
        string $role,
        string $branchName,
    ): void {
        $branch = $role === 'owner'
            ? null
            : $this->createBranch('NAV-'.$role, $branchName);
        $user = $this->createUser($role, $branch, [
            'name' => 'Pengguna Navigasi '.ucfirst($role),
        ]);

        $response = $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSeeText($user->name)
            ->assertSeeText($user->role->name)
            ->assertSeeText($role === 'owner' ? 'Semua Cabang' : $branchName)
            ->assertSee('class="sidebar-drawer-logout"', false)
            ->assertSee('action="'.route('logout').'"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="_token"', false);

        $content = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/class="sidebar-nav__item is-active"[^>]+aria-current="page"[^>]*>.*?Akun Saya/s',
            $content,
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleCases(): array
    {
        return [
            'owner' => ['owner', 'Semua Cabang'],
            'admin cabang' => ['admin', 'Cabang Navigasi Admin'],
            'kasir' => ['cashier', 'Cabang Navigasi Kasir'],
        ];
    }

    public function test_mobile_navigation_css_uses_project_breakpoints_and_safe_layers(): void
    {
        $navbarCss = (string) file_get_contents(public_path('assets/css/layouts/navbar.css'));
        $sidebarCss = (string) file_get_contents(public_path('assets/css/layouts/sidebar.css'));

        $this->assertStringContainsString('@media (max-width: 1024px)', $navbarCss);
        $this->assertStringContainsString('@media (max-width: 1024px)', $sidebarCss);
        $this->assertStringContainsString('@media (max-width: 768px)', $sidebarCss);
        $this->assertStringContainsString('@media (max-width: 480px)', $sidebarCss);
        $this->assertStringContainsString('transform: translateX(-100%);', $sidebarCss);
        $this->assertStringContainsString('transform: translateX(0);', $sidebarCss);
        $this->assertStringContainsString('width: min(72vw, 340px);', $sidebarCss);
        $this->assertStringContainsString('width: min(86vw, 320px);', $sidebarCss);
        $this->assertStringContainsString('body.drawer-open', $sidebarCss);
        $this->assertStringContainsString('overflow-y: auto;', $sidebarCss);
        $this->assertStringContainsString('.app-drawer-overlay.is-visible', $sidebarCss);
    }

    public function test_mobile_navigation_javascript_handles_all_close_and_focus_scenarios(): void
    {
        $javascript = (string) file_get_contents(
            public_path('assets/js/components/sidebar.js'),
        );

        foreach ([
            'window.matchMedia(mobileNavigationQuery)',
            "drawerTrigger.addEventListener('click'",
            "drawerClose.addEventListener('click'",
            "drawerOverlay.addEventListener('click'",
            "event.key === 'Escape'",
            "event.key !== 'Tab'",
            "mobileNavigation.addEventListener('change'",
            "document.body.classList.add('drawer-open')",
            "document.body.classList.remove('drawer-open')",
            'window.scrollTo({',
            "appMain.setAttribute('inert', '')",
            "sidebar.classList.add('is-open')",
            "sidebar.classList.remove('is-open')",
            'closeDrawer(false)',
        ] as $contract) {
            $this->assertStringContainsString($contract, $javascript);
        }

        $this->assertStringNotContainsString('jQuery', $javascript);
        $this->assertStringNotContainsString('onclick', $javascript);
    }
}
