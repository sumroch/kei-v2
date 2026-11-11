<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReservationTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $sayama;

    private Store $chichibu;

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
        // ids in the opposite order of names: "sort by store" must use the name
        $this->sayama = $store('Sayama', 'flat7_sayama');
        $this->chichibu = $store('Chichibu', 'flat7_chichibu');

        foreach ([
            ['Aoki', 'aoki@example.com', 'contact', $this->sayama, '2025-03-01 10:00:00'],
            ['Kato', 'kato@example.com', 'raiten', $this->chichibu, '2025-07-15 09:00:00'],
            ['Mori', 'mori@example.com', 'shinsa', $this->sayama, '2026-07-02 12:00:00'],
        ] as [$name, $email, $type, $s, $at]) {
            $r = Reservation::create([
                'name' => $name, 'how_to_read' => "{$name}-kana", 'email' => $email, 'phone_number' => '090', 'type_reservation' => $type,
                'store_id' => $s->id, 'address' => '埼玉', 'message' => "hello from {$name}",
                'date1' => '2025-08-01', 'time1' => '10:00', 'date2' => '2025-08-02', 'time2' => '-', 'date3' => '-', 'time3' => '-',
                'car' => '["N-BOX","タント"]', 'can_contacted' => '["電話"]',
            ]);
            $r->forceFill(['created_at' => $at])->save();
        }
    }

    private function names(string $query): array
    {
        $names = [];
        $this->actingAs($this->admin)->get('/reservations'.$query)->assertOk()
            ->assertInertia(function (Assert $page) use (&$names) {
                $names = collect($page->toArray()['props']['reservations']['data'])->pluck('name')->all();
            });

        return $names;
    }

    public function test_default_is_newest_first(): void
    {
        $this->assertSame(['Mori', 'Kato', 'Aoki'], $this->names(''));
    }

    public function test_sortable_columns_match_the_old_dashboard(): void
    {
        $this->assertSame(['Aoki', 'Kato', 'Mori'], $this->names('?sort=created_at'));
        $this->assertSame(['Mori', 'Kato', 'Aoki'], $this->names('?sort=-name'));
        $this->assertSame(['Aoki', 'Kato', 'Mori'], $this->names('?sort=email'));
        $this->assertSame(['Aoki', 'Kato', 'Mori'], $this->names('?sort=type_reservation')); // contact < raiten < shinsa
        $this->assertSame('Kato', $this->names('?sort=store')[0]); // Chichibu < Sayama
    }

    public function test_filters(): void
    {
        $this->assertSame(['Kato'], $this->names('?filter[type]=raiten'));
        $this->assertSame(['Mori', 'Aoki'], $this->names("?filter[store]={$this->sayama->id}"));
        $this->assertSame(['Kato', 'Aoki'], $this->names('?filter[year]=2025'));
        $this->assertSame(['Mori', 'Kato'], $this->names('?filter[month]=7'));
        $this->assertSame(['Kato'], $this->names('?filter[year]=2025&filter[month]=7'));
        $this->assertSame(['Aoki'], $this->names('?filter[search]=aoki-kana'));
        $this->assertSame(['Kato'], $this->names('?filter[search]=kato@'));
    }

    public function test_list_rows_do_not_carry_the_form_fields(): void
    {
        $this->actingAs($this->admin)->get('/reservations')->assertInertia(fn (Assert $page) => $page
            ->where('reservations.data.0', [
                'id' => Reservation::where('name', 'Mori')->value('id'),
                'name' => 'Mori', 'how_to_read' => 'Mori-kana', 'email' => 'mori@example.com', 'phone_number' => '090',
                'type_reservation' => 'shinsa', 'store' => 'Sayama', 'created_at' => '2026-07-02 12:00',
            ])
            ->missing('detail')                     // optional prop: not sent on normal visits
            ->where('years', [2026, 2025])
            ->has('stores', 2));
    }

    public function test_detail_is_loaded_only_when_requested(): void
    {
        $kato = Reservation::where('name', 'Kato')->first();

        $this->actingAs($this->admin)->get('/reservations')->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('detail', fn (Assert $reload) => $reload->where('detail', null))); // no id requested

        $this->actingAs($this->admin)->get("/reservations?detail={$kato->id}")->assertInertia(fn (Assert $page) => $page
            ->reloadOnly('detail', fn (Assert $reload) => $reload
                ->missing('reservations')->missing('stores')
                ->where('detail.name', 'Kato')
                ->where('detail.message', 'hello from Kato')
                ->where('detail.car', ['N-BOX', 'タント'])
                ->where('detail.can_contacted', ['電話'])
                ->where('detail.preferred', ['2025-08-01 10:00', '2025-08-02']) // "-" placeholders dropped
                ->where('detail.store', 'Chichibu')));
    }

    public function test_paging_a_sort_with_many_equal_values_is_stable(): void
    {
        foreach (range(1, 22) as $i) {
            Reservation::create(['name' => "c{$i}", 'type_reservation' => 'contact', 'store_id' => $this->sayama->id]);
        }

        $ids = [];
        foreach ([1, 2, 3] as $page) {
            $this->actingAs($this->admin)->get("/reservations?sort=type_reservation&per_page=10&page={$page}")
                ->assertInertia(function (Assert $p) use (&$ids) {
                    $ids = [...$ids, ...collect($p->toArray()['props']['reservations']['data'])->pluck('id')];
                });
        }

        // every row exactly once across pages
        $this->assertCount(25, $ids);
        $this->assertCount(25, array_unique($ids));

        // equal type -> tiebreaker id desc (newest first); 23 contacts come first
        $contactIds = array_slice($ids, 0, 23);
        $sorted = $contactIds;
        rsort($sorted);
        $this->assertSame($sorted, $contactIds);
    }

    public function test_partial_table_reload_skips_dropdown_data(): void
    {
        $this->actingAs($this->admin)->get('/reservations?sort=name')->assertInertia(fn (Assert $page) => $page
            ->reloadOnly(['reservations', 'query'], fn (Assert $reload) => $reload
                ->has('reservations.data', 3)->missing('stores')->missing('years')->missing('detail')));
    }
}
