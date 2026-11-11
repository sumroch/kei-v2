<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhotoTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $honjo;

    private Store $ashikaga;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $store = fn ($name, $slug) => Store::create([
            'name' => $name, 'name_id' => $slug, 'slug' => $slug, 'brand' => 'flat7', 'email' => "{$slug}@example.com",
            'email_bcc' => [], 'postal_code' => '1', 'address' => 'x', 'phone_number' => '1', 'operational' => '9-18',
        ]);
        // Store ids are in the opposite order of their names, so "sort by store" can't pass by sorting on store_id.
        $this->honjo = $store('Honjo', 'joycal_honjo');
        $this->ashikaga = $store('Ashikaga', 'flat7_ashikaga');

        foreach ([
            ['K', '2023-05-10', 'ホンダ N-BOX', $this->honjo],
            ['A', '2024-01-20', 'スズキ ハスラー', $this->ashikaga],
            ['M', '2024-05-02', 'ダイハツ タント', $this->honjo],
        ] as [$initial, $date, $maker, $s]) {
            Photo::create(['initial' => $initial, 'date' => $date, 'maker' => $maker, 'image' => "{$initial}.jpg", 'store_id' => $s->id]);
        }
    }

    private function initials(string $query): array
    {
        $initials = [];
        $this->actingAs($this->admin)->get('/photos'.$query)->assertOk()
            ->assertInertia(function (Assert $page) use (&$initials) {
                $initials = collect($page->toArray()['props']['photos']['data'])->pluck('initial')->all();
            });

        return $initials;
    }

    public function test_default_is_newest_delivery_first(): void
    {
        $this->assertSame(['M', 'A', 'K'], $this->initials(''));
    }

    public function test_sorts_including_store_name(): void
    {
        $this->assertSame(['K', 'A', 'M'], $this->initials('?sort=date'));
        $this->assertSame(['A', 'K', 'M'], $this->initials('?sort=initial'));
        $this->assertSame('A', $this->initials('?sort=store')[0]);       // Ashikaga first, although its id is higher
        $this->assertSame('A', $this->initials('?sort=-store')[2]);
    }

    public function test_store_year_and_month_filters(): void
    {
        $this->assertSame(['M', 'K'], $this->initials("?filter[store]={$this->honjo->id}"));
        $this->assertSame(['M', 'A'], $this->initials('?filter[year]=2024'));
        $this->assertSame(['M', 'K'], $this->initials('?filter[month]=5'));
        $this->assertSame(['M'], $this->initials('?filter[year]=2024&filter[month]=5'));
        // nonsense values are ignored
        $this->assertCount(3, $this->initials('?filter[month]=13&filter[year]=abc'));
    }

    public function test_dropdown_data_only_on_full_visits(): void
    {
        $this->actingAs($this->admin)->get('/photos')->assertInertia(fn (Assert $page) => $page
            ->where('years', [2024, 2023])
            ->has('stores', 2)
            ->where('photos.data.0.store', 'Honjo')
            ->where('photos.data.0.image_url', fn ($url) => str_ends_with($url, '/storage/customer/M.jpg'))
            ->reloadOnly(['photos', 'query'], fn (Assert $reload) => $reload->missing('stores')->missing('years')));
    }
}
