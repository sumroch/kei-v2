<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_stats_and_latest_are_deferred(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $store = Store::create([
            'name' => 'Sayama', 'name_id' => 'sayama', 'slug' => 'flat7_sayama', 'brand' => 'flat7', 'email' => 's@example.com',
            'email_bcc' => [], 'postal_code' => '1', 'address' => 'x', 'phone_number' => '1', 'operational' => '9-18',
        ]);
        Reservation::create(['name' => 'Old', 'type_reservation' => 'raiten', 'store_id' => $store->id])
            ->forceFill(['created_at' => now()->subYear()])->save();
        Reservation::create(['name' => 'New', 'type_reservation' => 'contact', 'store_id' => $store->id]);

        $this->actingAs($admin)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->missing('stats')      // first response: no counting queries, page shows skeletons
            ->missing('latest')
            ->loadDeferredProps(fn (Assert $deferred) => $deferred
                ->where('stats.reservations_month', 1)
                ->where('stats.reservations_month_by_type', ['contact' => 1])
                ->where('stats.reservations_total', 2)
                ->where('latest.0.name', 'New')
                ->where('latest.0.store', 'Sayama')));
    }
}
