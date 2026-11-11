<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** URL contract of the admin data tables (App\Support\DataTable + useDataTable.js), on the stores pilot. */
class StoreTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        foreach ([
            ['Chichibu', 'flat7_chichibu', 'flat7', '0494'],
            ['Asakura', 'flat7_ashikaga', 'flat7', '0284'],
            ['Hanazono', 'joycal_hanazono', 'joycal', '048'],
            ['Kumagaya', 'keigayasui_kumagaya', 'keigayasui', '0485'],
        ] as [$name, $slug, $brand, $phone]) {
            Store::create([
                'name' => $name, 'name_id' => $slug, 'slug' => $slug, 'brand' => $brand, 'email' => "{$slug}@example.com",
                'email_bcc' => [], 'postal_code' => '1', 'address' => 'x', 'phone_number' => $phone, 'operational' => '9-18',
            ]);
        }

        $hanazono = Store::where('slug', 'joycal_hanazono')->first();
        foreach (range(1, 3) as $i) {
            Reservation::create(['name' => "r{$i}", 'type_reservation' => 'contact', 'store_id' => $hanazono->id]);
        }
    }

    private function names(string $query): array
    {
        $names = [];
        $this->actingAs($this->admin)->get('/stores'.$query)->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = collect($page->toArray()['props']['stores']['data'])->pluck('name')->all();
            });

        return $names;
    }

    public function test_default_sort_is_brand_then_name(): void
    {
        $this->assertSame(['Asakura', 'Chichibu', 'Hanazono', 'Kumagaya'], $this->names(''));
    }

    public function test_sorts_by_whitelisted_columns_in_both_directions(): void
    {
        $this->assertSame(['Asakura', 'Chichibu', 'Hanazono', 'Kumagaya'], $this->names('?sort=name'));
        $this->assertSame(['Kumagaya', 'Hanazono', 'Chichibu', 'Asakura'], $this->names('?sort=-name'));
        $this->assertSame('Hanazono', $this->names('?sort=-reservations_count')[0]);
    }

    public function test_unknown_sort_or_filter_is_ignored_instead_of_failing(): void
    {
        $this->assertSame(['Asakura', 'Chichibu', 'Hanazono', 'Kumagaya'], $this->names('?sort=password'));
        $this->assertCount(4, $this->names('?filter[id]=1'));

        // The UI must not show a sort arrow for a sort that wasn't applied.
        $this->actingAs($this->admin)->get('/stores?sort=-password')
            ->assertInertia(fn (Assert $page) => $page->where('query.sort', null));
    }

    public function test_filters_by_brand_and_search(): void
    {
        $this->assertSame(['Asakura', 'Chichibu'], $this->names('?filter[brand]=flat7'));
        $this->assertSame(['Kumagaya'], $this->names('?filter[search]=kumagaya'));
        $this->assertSame([], $this->names('?filter[brand]=joycal&filter[search]=chichibu'));
    }

    public function test_per_page_is_limited_to_the_allowed_options(): void
    {
        $this->actingAs($this->admin)->get('/stores?per_page=20')
            ->assertInertia(fn (Assert $page) => $page->where('stores.per_page', 20));

        $this->actingAs($this->admin)->get('/stores?per_page=100000')
            ->assertInertia(fn (Assert $page) => $page->where('stores.per_page', 10));
    }

    public function test_query_state_is_echoed_back_for_the_ui(): void
    {
        $this->actingAs($this->admin)->get('/stores?sort=-name&filter[brand]=flat7&filter[search]=')
            ->assertInertia(fn (Assert $page) => $page
                ->where('query.sort', '-name')
                ->where('query.filter', ['brand' => 'flat7']));
    }

    public function test_partial_reload_only_sends_the_table(): void
    {
        $this->actingAs($this->admin)->get('/stores?sort=name')->assertInertia(function (Assert $page) {
            $page->reloadOnly(['stores', 'query'], fn (Assert $reload) => $reload->has('stores.data', 4)->has('query')->missing('brands'));
        });
    }
}
