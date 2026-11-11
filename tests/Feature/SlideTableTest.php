<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Slide;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SlideTableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $car = fn ($brand, $type) => Car::create(['brand' => $brand, 'maker' => 'honda', 'grade' => 'G', 'picture' => "{$brand}.jpg", 'store_type' => $type]);
        [$a, $b] = [$car('A', 'keigayasui'), $car('B', 'keigayasui')];
        $car('Flat', 'flat7_joycal');
        $car('Gone', 'keigayasui')->delete();

        $honda = Slide::create(['owl' => 'owl-honda', 'title1' => 'HONDA', 'maker' => 'ホンダ', 'store_type' => 'keigayasui', 'order_slide' => 2, 'active' => 1]);
        $honda->car()->sync([$b->id => ['order_number' => 0], $a->id => ['order_number' => 1]]);
        Slide::create(['owl' => 'owl-suzuki', 'title1' => 'SUZUKI', 'store_type' => 'keigayasui', 'order_slide' => 1, 'active' => 0]);
        Slide::create(['owl' => 'owl-X', 'title1' => '11,000', 'title2' => 'bonus', 'color' => '#3A88FE', 'store_type' => 'flat7_joycal', 'order_slide' => 1, 'active' => 1]);
    }

    private function titles(string $query): array
    {
        $titles = [];
        $this->actingAs($this->admin)->get('/slides'.$query)->assertOk()
            ->assertInertia(function (Assert $page) use (&$titles) {
                $titles = collect($page->toArray()['props']['slides']['data'])->pluck('title1')->all();
            });

        return $titles;
    }

    public function test_default_is_site_then_display_order(): void
    {
        $this->assertSame(['11,000', 'SUZUKI', 'HONDA'], $this->titles(''));
    }

    public function test_sorts_and_filters_like_the_old_dashboard(): void
    {
        // equal site -> tiebreaker keeps the carousel order (order_slide)
        $this->assertSame(['SUZUKI', 'HONDA', '11,000'], $this->titles('?sort=-store_type'));
        $this->assertSame('SUZUKI', $this->titles('?sort=active')[0]);
        $this->assertSame(['HONDA', 'SUZUKI'], $this->titles('?filter[store_type]=keigayasui&sort=-order_slide'));
        $this->assertSame(['SUZUKI'], $this->titles('?filter[active]=0'));
    }

    public function test_rows_carry_cars_in_display_order(): void
    {
        $this->actingAs($this->admin)->get('/slides?filter[store_type]=keigayasui&sort=-order_slide')->assertInertia(fn (Assert $page) => $page
            ->where('slides.data.0.title1', 'HONDA')
            ->where('slides.data.0.cars.0.brand', 'B')
            ->where('slides.data.0.cars.1.brand', 'A')
            ->where('slides.data.0.active', true));
    }

    public function test_picker_cars_are_sent_on_full_visits_only(): void
    {
        $this->actingAs($this->admin)->get('/slides')->assertInertia(fn (Assert $page) => $page
            ->has('cars', 3)                         // soft-deleted "Gone" excluded
            ->where('cars.0.picture_url', fn ($url) => str_contains($url, '/storage/car/'))
            ->reloadOnly(['slides', 'query'], fn (Assert $reload) => $reload->has('slides.data', 3)->missing('cars')));
    }
}
