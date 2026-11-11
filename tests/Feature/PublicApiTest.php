<?php

namespace Tests\Feature;

use App\Mail\ContactMailFlat7Joycal;
use App\Mail\ContactMailKeigayasui;
use App\Mail\GyautoQuestionMail;
use App\Mail\RaitenMail;
use App\Models\Car;
use App\Models\Photo;
use App\Models\Reservation;
use App\Models\Slide;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** The public websites depend on these exact URLs and JSON shapes (ported from the old ichiman API). */
class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    private function store(array $attributes = []): Store
    {
        return Store::create([
            'name' => 'フラット7狭山店', 'name_id' => 'sayama', 'slug' => 'flat7_sayama', 'brand' => 'flat7',
            'email' => 'sayama@example.com', 'email_bcc' => ['boss@example.com', 'staff@example.com'],
            'postal_code' => '350-1300', 'address' => '埼玉県', 'phone_number' => '04-0000-0000', 'operational' => '9:00-18:00',
            ...$attributes,
        ]);
    }

    private function car(array $attributes = []): Car
    {
        return Car::create([
            'brand' => 'N-BOX', 'maker' => 'honda', 'picture' => 'x.jpg', 'grade' => 'G', 'store_type' => 'keigayasui', 'active' => 1,
            ...$attributes,
        ]);
    }

    public function test_slides_are_listed_with_cars_in_order(): void
    {
        $slide = Slide::create(['owl' => 'owl-honda', 'title1' => 'HONDA', 'maker' => 'ホンダ', 'store_type' => 'keigayasui', 'order_slide' => 1, 'active' => 1]);
        Slide::create(['owl' => 'owl-off', 'title1' => 'OFF', 'store_type' => 'keigayasui', 'active' => 0]);
        $first = $this->car(['brand' => 'First']);
        $second = $this->car(['brand' => 'Second']);
        $slide->car()->sync([$second->id => ['order_number' => 1], $first->id => ['order_number' => 0]]);

        $this->getJson('/api/slide/indexKeigayasui')
            ->assertOk()
            ->assertJsonPath('is_error', false)
            ->assertJsonPath('status_code', 200)
            ->assertJsonPath('message', 'Success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.owl', 'owl-honda')
            ->assertJsonPath('data.0.car.0.brand', 'First')
            ->assertJsonPath('data.0.car.1.brand', 'Second');
    }

    public function test_cars_and_photos_lists(): void
    {
        $this->car(['brand' => 'Active']);
        $this->car(['brand' => 'Hidden', 'active' => 0]);
        $this->car(['brand' => 'Other site', 'store_type' => 'flat7_joycal']);

        $this->getJson('/api/car/allKeigayasui')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.brand', 'Active');

        $flat = $this->store();
        $kei = $this->store(['slug' => 'keigayasui_x', 'brand' => 'keigayasui', 'name_id' => 'x']);
        Photo::create(['initial' => 'A', 'date' => '2024-01-01', 'image' => 'a.jpg', 'store_id' => $flat->id]);
        Photo::create(['initial' => 'B', 'date' => '2024-01-02', 'image' => 'b.jpg', 'store_id' => $kei->id]);

        $this->getJson('/api/photo/list')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.name_id', 'sayama');
        $this->getJson('/api/photo/list/gyauto')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.initial', 'A');
    }

    public function test_reservation_validation_error_keeps_old_format(): void
    {
        $this->postJson('/api/reservations/add', ['name' => 'Taro'])
            ->assertStatus(400)
            ->assertJsonPath('is_error', true)
            ->assertJsonPath('message', 'Bad Request')
            ->assertJsonStructure(['data' => ['email', 'store_id']]);
    }

    public function test_flat7_reservation_is_saved_and_mailed_to_customer_store_and_bcc(): void
    {
        Mail::fake();
        $store = $this->store();

        $this->postJson('/api/reservations/add', [
            'name' => '山田太郎', 'type_reservation' => 'contact', 'store_id' => 'flat7_sayama',
            'email' => 'taro@example.com', 'phone_number' => '090', 'address' => '東京',
            'can_contacted' => ['電話', 'メール'], 'type_question' => 'ローン',
        ])->assertOk()->assertJsonPath('message', 'Created');

        $reservation = Reservation::sole();
        $this->assertSame($store->id, $reservation->store_id);
        $this->assertSame(['電話', 'メール'], json_decode($reservation->can_contacted, true));
        $this->assertSame(['ローン'], json_decode($reservation->type_question, true));

        Mail::assertSent(ContactMailFlat7Joycal::class, fn ($mail) => $mail->hasTo('taro@example.com')
            && $mail->hasCc('sayama@example.com')
            && $mail->hasBcc('boss@example.com') && $mail->hasBcc('staff@example.com'));
    }

    public function test_keigayasui_reservation_gets_store_cc_but_no_bcc_like_before(): void
    {
        Mail::fake();
        $this->store(['slug' => 'keigayasui_kumagaya', 'brand' => 'keigayasui', 'email' => 'kuma@example.com']);

        $this->postJson('/api/reservations/add', [
            'name' => 'Taro', 'type_reservation' => 'contact', 'store_id' => 'keigayasui_kumagaya',
            'email' => 'taro@example.com', 'phone_number' => '090', 'address' => '埼玉',
        ])->assertOk();

        Mail::assertSent(ContactMailKeigayasui::class, fn ($mail) => $mail->hasCc('kuma@example.com') && ! $mail->hasBcc('boss@example.com'));
    }

    public function test_raiten_reservation_uses_raiten_mail(): void
    {
        Mail::fake();
        $this->store();

        $this->postJson('/api/reservations/add', [
            'name' => 'Taro', 'type_reservation' => 'raiten', 'store_id' => 'flat7_sayama',
            'email' => 'taro@example.com', 'phone_number' => '090', 'address' => '東京', 'car' => ['N-BOX'],
        ])->assertOk();

        Mail::assertSent(RaitenMail::class);
    }

    public function test_gyauto_validation_and_success_use_contact_form_7_format(): void
    {
        Mail::fake();
        $this->store(['slug' => 'joycal_hanazono', 'brand' => 'joycal']);

        $this->postJson('/api/gyauto/question', ['name' => 'Taro'])
            ->assertOk()
            ->assertJsonPath('status', 'validation_field')
            ->assertJsonPath('invalid_fields.0.into', 'span.wpcf7-form-control-wrap.your-phone_number');

        $this->postJson('/api/gyauto/question', [
            'name' => 'Taro', 'phone_number' => '090', 'address' => '東京',
            'email' => 'taro@example.com', 'email_confirm' => 'taro@example.com',
        ])->assertOk()->assertJsonPath('status', 'mail_sent');

        $this->assertDatabaseHas('gyauto_questions', ['email' => 'taro@example.com', 'type_question' => 'question']);
        Mail::assertSent(GyautoQuestionMail::class, fn ($mail) => $mail->hasTo('taro@example.com'));
    }
}
