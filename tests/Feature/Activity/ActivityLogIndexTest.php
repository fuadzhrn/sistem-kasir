<?php

namespace Tests\Feature\Activity;

class ActivityLogIndexTest extends ActivityLogTestCase
{
    public function test_owner_can_view_paginated_activity_index_with_assets(): void
    {
        $owner = $this->user('owner');
        $this->log($owner, null);

        $this->actingAs($owner)
            ->get(route('activities.index'))
            ->assertOk()
            ->assertSee('Audit Aktivitas')
            ->assertSee('Produk aman diperbarui.')
            ->assertSee('assets/css/pages/activities.css', false)
            ->assertSee('assets/js/pages/activities.js', false);
    }
}
