<?php

namespace Tests\Feature\Report;

class ReportIndexTest extends ReportTestCase
{
    public function test_owner_and_admin_can_open_landing_with_all_report_cards(): void
    {
        $branch = $this->createBranch('RID');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);

        foreach ([$owner, $admin] as $user) {
            $response = $this->actingAs($user)->get(route('reports.index'))->assertOk();

            foreach ($this->reportSlugs() as $slug) {
                $response->assertSee(route("reports.{$slug}.index"), false);
            }
        }
    }

    public function test_all_report_screens_execute_for_owner_and_admin(): void
    {
        $branch = $this->createBranch('RIS');
        $owner = $this->createUser('owner');
        $admin = $this->createUser('admin', $branch);

        foreach ($this->reportSlugs() as $slug) {
            try {
                $this->getReport($owner, $slug)->assertOk();
                $this->getReport($admin, $slug)->assertOk();
            } catch (\Throwable $exception) {
                throw new \RuntimeException(
                    "Laporan {$slug} gagal: {$exception->getMessage()} di {$exception->getFile()}:{$exception->getLine()}.",
                    previous: $exception,
                );
            }
        }
    }
}
