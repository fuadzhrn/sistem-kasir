<?php

namespace Tests\Feature\Report;

class ReportPrintAuthorizationTest extends ReportTestCase
{
    public function test_print_requires_login_and_rejects_cashier(): void
    {
        $branch = $this->createBranch('RPR');
        $cashier = $this->createUser('cashier', $branch);

        $this->get(route('reports.sales.print'))->assertRedirect(route('login'));
        $this->getPrintReport($cashier, 'sales')->assertForbidden();
    }
}
