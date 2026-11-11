<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CarTableTest extends TestCase
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
            ['N-BOX', 'honda', 'G', 'keigayasui', 1, 1, '2024-01-03'],
            ['タント', 'daihatsu', 'L', 'keigayasui', 1, 0, '2024-01-01'],
            ['スペーシア', 'suzuki', 'HYBRID X', 'flat7_joycal', 0, 1, '2024-01-02'],
        ] as [$brand, $maker, $grade, $type, $new, $active, $created]) {
            $car = Car::create(['brand' => $brand, 'maker' => $maker, 'grade' => $grade, 'picture' => 'x.jpg', 'store_type' => $type, 'new' => $new, 'active' => $active]);
            $car->forceFill(['created_at' => $created])->save();
        }
        Car::create(['brand' => 'Deleted', 'maker' => 'honda', 'grade' => 'x', 'picture' => 'x.jpg', 'store_type' => 'keigayasui'])->delete();
    }

    private function brands(string $query): array
    {
        $brands = [];
        $this->actingAs($this->admin)->get('/cars'.$query)->assertOk()
            ->assertInertia(function (Assert $page) use (&$brands) {
                $brands = collect($page->toArray()['props']['cars']['data'])->pluck('brand')->all();
            });

        return $brands;
    }

    public function test_default_sort_and_soft_deleted_cars_hidden(): void
    {
        // store_type, maker, brand
        $this->assertSame(['スペーシア', 'タント', 'N-BOX'], $this->brands(''));
    }

    public function test_sortable_columns_match_the_old_dashboard(): void
    {
        $this->assertSame(['N-BOX', 'スペーシア', 'タント'], $this->brands('?sort=-created_at'));
        $this->assertSame(['タント', 'スペーシア', 'N-BOX'], $this->brands('?sort=created_at'));
        $this->assertSame(['タント', 'N-BOX', 'スペーシア'], $this->brands('?sort=maker'));
        $this->assertSame(['N-BOX', 'スペーシア', 'タント'], $this->brands('?sort=grade')); // G < HYBRID X < L
        $this->assertSame('スペーシア', $this->brands('?sort=new')[0]);
        $this->assertSame('タント', $this->brands('?sort=active')[0]);
        // formatted price strings are not sortable: falls back to the default order
        $this->assertSame(['スペーシア', 'タント', 'N-BOX'], $this->brands('?sort=-price'));
    }

    public function test_filters_including_inactive_zero_value(): void
    {
        $this->assertSame(['タント'], $this->brands('?filter[active]=0'));
        $this->assertSame(['スペーシア', 'N-BOX'], $this->brands('?filter[active]=1'));
        $this->assertSame(['N-BOX'], $this->brands('?filter[maker]=honda'));
        $this->assertSame(['タント', 'N-BOX'], $this->brands('?filter[store_type]=keigayasui'));
        $this->assertSame(['スペーシア'], $this->brands('?filter[search]=HYBRID'));
    }

    public function test_row_shape(): void
    {
        $this->actingAs($this->admin)->get('/cars?filter[maker]=honda')->assertInertia(fn (Assert $page) => $page
            ->where('cars.data.0.new', true)
            ->where('cars.data.0.active', true)
            ->where('cars.data.0.created_at', '2024-01-03')
            ->where('cars.data.0.picture_url', fn ($url) => str_ends_with($url, '/storage/car/x.jpg'))
            ->where('query.filter', ['maker' => 'honda'])
            ->where('makers', ['daihatsu', 'honda', 'suzuki'])
            ->reloadOnly(['cars', 'query'], fn (Assert $reload) => $reload->missing('makers')));
    }
}
