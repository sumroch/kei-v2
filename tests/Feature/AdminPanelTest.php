<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Photo;
use App\Models\Reservation;
use App\Models\Slide;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');

        $this->store = Store::create([
            'name' => '狭山店', 'name_id' => 'sayama', 'slug' => 'flat7_sayama', 'brand' => 'flat7',
            'email' => 'sayama@example.com', 'email_bcc' => [],
            'postal_code' => '350', 'address' => '埼玉', 'phone_number' => '04', 'operational' => '9-18',
        ]);
    }

    public function test_guests_are_redirected_and_plain_users_are_forbidden(): void
    {
        $this->get('/reservations')->assertRedirect('/login');

        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user)->get('/reservations')->assertForbidden();
    }

    public function test_every_admin_page_renders(): void
    {
        $pages = [
            '/dashboard' => 'Dashboard',
            '/reservations' => 'Reservations/Index',
            '/photos' => 'Photos/Index',
            '/cars' => 'Cars/Index',
            '/slides' => 'Slides/Index',
            '/stores' => 'Stores/Index',
        ];

        foreach ($pages as $url => $component) {
            $this->actingAs($this->admin)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_reservations_can_be_filtered_viewed_and_deleted(): void
    {
        $match = Reservation::create(['name' => '山田', 'email' => 'yamada@example.com', 'type_reservation' => 'contact', 'store_id' => $this->store->id, 'car' => '["N-BOX"]']);
        Reservation::create(['name' => '佐藤', 'email' => 'sato@example.com', 'type_reservation' => 'raiten', 'store_id' => $this->store->id]);

        // Table contract details: ReservationTableTest.
        $this->actingAs($this->admin)->get('/reservations?filter[type]=contact&filter[search]=山田')
            ->assertInertia(fn (Assert $page) => $page
                ->has('reservations.data', 1)
                ->where('reservations.data.0.name', '山田')
                ->where('reservations.data.0.store', '狭山店'));

        $this->actingAs($this->admin)->delete("/reservations/{$match->id}")->assertRedirect();
        $this->assertSoftDeleted($match);
    }

    public function test_reservations_export_csv(): void
    {
        Reservation::create(['name' => '山田', 'email' => 'y@example.com', 'type_reservation' => 'contact', 'store_id' => $this->store->id]);

        $response = $this->actingAs($this->admin)->get('/reservations/export?date_start='.now()->subDay()->toDateString().'&date_end='.now()->toDateString());

        $response->assertOk();
        $csv = mb_convert_encoding($response->streamedContent(), 'UTF-8', 'EUC-JP');
        $this->assertStringContainsString('No,Name,Email', $csv);
        $this->assertStringContainsString('山田', $csv);
        $this->assertStringContainsString('狭山店', $csv);
    }

    public function test_store_crud_with_bcc_list(): void
    {
        $data = [
            'name' => '本庄店', 'name_id' => 'honjo', 'slug' => 'joycal_honjo', 'brand' => 'joycal', 'email' => 'honjo@example.com',
            'email_bcc' => ['a@example.com', '', 'b@example.com'], 'postal_code' => '367', 'address' => '埼玉',
            'phone_number' => '0495', 'operational' => '9-18',
        ];

        $this->actingAs($this->admin)->post('/stores', $data)->assertRedirect()->assertSessionHasNoErrors();
        $store = Store::where('slug', 'joycal_honjo')->sole();
        $this->assertSame(['a@example.com', 'b@example.com'], $store->email_bcc);

        // slug must stay unique
        $this->actingAs($this->admin)->post('/stores', $data)->assertSessionHasErrors('slug');

        $this->actingAs($this->admin)->put("/stores/{$store->id}", [...$data, 'phone_number_2' => '0495-2', 'email_bcc' => ['not-an-email']])
            ->assertSessionHasErrors('email_bcc.0');
        $this->actingAs($this->admin)->put("/stores/{$store->id}", [...$data, 'phone_number_2' => '0495-2'])->assertSessionHasNoErrors();
        $this->assertSame('0495-2', $store->fresh()->phone_number_2);

        $this->actingAs($this->admin)->delete("/stores/{$store->id}");
        $this->assertSoftDeleted($store);
    }

    public function test_car_create_and_update_store_resized_images_on_the_public_disk(): void
    {
        Storage::fake('public');
        $data = ['store_type' => 'keigayasui', 'brand' => 'タント', 'maker' => 'daihatsu', 'grade' => 'L', 'new' => 1, 'active' => 1];

        $this->actingAs($this->admin)->post('/cars', $data)->assertSessionHasErrors('image');

        $this->actingAs($this->admin)->post('/cars', [...$data, 'image' => UploadedFile::fake()->image('car.jpg', 1200, 800)])
            ->assertSessionHasNoErrors();

        $car = Car::sole();
        Storage::disk('public')->assertExists('car/'.$car->picture);
        $this->assertSame('0', $car->price);
        $this->assertSame(500, getimagesize(Storage::disk('public')->path('car/'.$car->picture))[0]);

        // Update with a new image replaces (and deletes) the old file.
        $old = $car->picture;
        $this->actingAs($this->admin)->post("/cars/{$car->id}", [...$data, '_method' => 'put', 'active' => 0, 'image' => UploadedFile::fake()->image('new.png', 600, 400)])
            ->assertSessionHasNoErrors();

        $car->refresh();
        $this->assertSame(0, (int) $car->active);
        $this->assertNotSame($old, $car->picture);
        Storage::disk('public')->assertMissing('car/'.$old);
        Storage::disk('public')->assertExists('car/'.$car->picture);
    }

    public function test_photo_update_without_new_image_keeps_the_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin)->post('/photos', [
            'date' => '2024-05-01', 'initial' => 'Y', 'maker' => 'ホンダ N-BOX', 'store_id' => $this->store->id,
            'image' => UploadedFile::fake()->image('p.jpg'),
        ])->assertSessionHasNoErrors();

        $photo = Photo::sole();
        $this->actingAs($this->admin)->post("/photos/{$photo->id}", [
            '_method' => 'put', 'date' => '2024-05-02', 'initial' => 'K', 'maker' => 'ホンダ N-BOX', 'store_id' => $this->store->id,
        ])->assertSessionHasNoErrors();

        $photo->refresh();
        $this->assertSame('K', $photo->initial);
        Storage::disk('public')->assertExists('customer/'.$photo->image);
    }

    public function test_slide_keeps_car_order_and_rejects_cars_from_another_site(): void
    {
        $make = fn ($brand, $type = 'keigayasui') => Car::create(['brand' => $brand, 'maker' => 'honda', 'picture' => 'x.jpg', 'grade' => 'G', 'store_type' => $type]);
        [$a, $b, $c] = [$make('A'), $make('B'), $make('C')];
        $other = $make('Other', 'flat7_joycal');

        $base = ['store_type' => 'keigayasui', 'title1' => 'honda', 'maker' => 'ホンダ', 'order_slide' => 3, 'active' => true];

        $this->actingAs($this->admin)->post('/slides', [...$base, 'cars' => [$other->id]])->assertSessionHasErrors('cars.0');

        $this->actingAs($this->admin)->post('/slides', [...$base, 'cars' => [$c->id, $a->id, $b->id]])->assertSessionHasNoErrors();

        $slide = Slide::sole();
        $this->assertSame('HONDA', $slide->title1);
        $this->assertSame('owl-honda', $slide->owl);
        $this->assertSame(['C', 'A', 'B'], $slide->car()->orderBy('slide_has_cars.order_number')->pluck('brand')->all());

        // A second keigayasui slide with the same maker gets a unique carousel id.
        $this->actingAs($this->admin)->post('/slides', [...$base, 'cars' => []])->assertSessionHasNoErrors();
        $this->assertNotSame('owl-honda', Slide::latest('id')->first()->owl);

        // Reorder; keigayasui title1 is locked after creation.
        $this->actingAs($this->admin)->put("/slides/{$slide->id}", [...$base, 'title1' => 'changed', 'cars' => [$b->id, $c->id]])->assertSessionHasNoErrors();
        $this->assertSame(['B', 'C'], $slide->car()->orderBy('slide_has_cars.order_number')->pluck('brand')->all());
        $this->assertSame('HONDA', $slide->fresh()->title1);

        // The public API sees the same order.
        $this->getJson('/api/slide/indexKeigayasui')->assertJsonPath('data.0.car.0.brand', 'B');
    }

    public function test_language_switch_saves_on_the_user_and_sets_locale(): void
    {
        $this->actingAs($this->admin)->from('/dashboard')->post('/language', ['lang' => 'en'])->assertRedirect('/dashboard');
        $this->assertSame('en', $this->admin->fresh()->lang);

        $this->actingAs($this->admin->fresh())->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));

        $this->actingAs($this->admin)->post('/language', ['lang' => 'xx'])->assertSessionHasErrors('lang');
    }
}
