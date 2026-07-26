<?php

namespace Tests\Feature\Activity;

use App\Models\Product;

class ActivityLogDetailTest extends ActivityLogTestCase
{
    public function test_detail_displays_safe_reference_actor_and_metadata(): void
    {
        $owner = $this->user('owner');
        $log = $this->log($owner, null, [
            'reference_type' => Product::class,
            'reference_id' => 99,
            'metadata' => ['before' => ['name' => 'Lama'], 'after' => ['name' => 'Baru']],
        ]);

        $this->actingAs($owner)
            ->get(route('activities.show', $log))
            ->assertOk()
            ->assertSee('Detail Aktivitas')
            ->assertSee('#99')
            ->assertSee('Lama')
            ->assertSee('Baru');
    }
}
