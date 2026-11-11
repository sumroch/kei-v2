<?php

namespace App\Services;

use App\Mail\ContactMailFlat7Joycal;
use App\Mail\ContactMailFlat7JoycalText;
use App\Mail\ContactMailKeigayasui;
use App\Mail\ContactMailKeigayasuiText;
use App\Mail\RaitenMail;
use App\Mail\RaitenMailText;
use App\Mail\ShinsaMail;
use App\Models\Reservation;
use App\Models\Store;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the confirmation mail for a reservation coming from the public websites.
 * Routing (cc / bcc per brand) is ported 1:1 from the old ichiman ReservationController.
 */
class ReservationMailer
{
    /** Submissions from this address are test runs: they don't go to the store staff. */
    private const TEST_EMAIL = 'ghobedku@gmail.com';

    private const TEST_CC = 'akiyama12345@zohomail.jp';

    private const ASHIKAGA_SLUG = 'flat7_ashikaga';

    private const ASHIKAGA_EXTRA_CC = 'asikaga-asakura@589kobac.co.jp';

    public function send(Reservation $reservation, Store $store, string $raitenType = 'raiten'): void
    {
        [$bcc, $zoho] = $this->splitZoho($store->email_bcc ?? []);
        $isTest = $reservation->email === self::TEST_EMAIL;

        $mail = Mail::to($reservation->email);

        if ($store->brand === 'keigayasui') {
            // The old code read a non-existent `bcc` column here, so keigayasui stores
            // never received bcc copies. Kept as-is so production behaviour doesn't change.
            if (! $isTest) {
                $mail->cc($store->email);
            }

            $mail->send($this->htmlMail($reservation, 'keigayasui', $raitenType));
        } elseif (in_array($store->brand, ['flat7', 'joycal'], true)) {
            if ($isTest) {
                $mail->cc(self::TEST_CC);
            } else {
                $mail->cc($store->slug === self::ASHIKAGA_SLUG ? [$store->email, self::ASHIKAGA_EXTRA_CC] : $store->email)
                    ->bcc($bcc);
            }

            $mail->send($this->htmlMail($reservation, 'flat7_joycal', $raitenType));
        } else {
            return;
        }

        // Zoho inboxes get the plain-text version.
        if ($zoho) {
            Mail::to($zoho)->send($this->textMail($reservation, $store->brand === 'keigayasui' ? 'keigayasui' : 'flat7_joycal', $raitenType));
        }
    }

    private function htmlMail(Reservation $reservation, string $group, string $raitenType): Mailable
    {
        return match ($reservation->type_reservation) {
            'contact' => $group === 'keigayasui' ? new ContactMailKeigayasui($reservation) : new ContactMailFlat7Joycal($reservation),
            'shinsa' => new ShinsaMail($reservation),
            default => new RaitenMail($reservation, $raitenType),
        };
    }

    private function textMail(Reservation $reservation, string $group, string $raitenType): Mailable
    {
        return match ($reservation->type_reservation) {
            'contact' => $group === 'keigayasui' ? new ContactMailKeigayasuiText($reservation) : new ContactMailFlat7JoycalText($reservation),
            'shinsa' => new ShinsaMail($reservation),
            default => new RaitenMailText($reservation, $raitenType),
        };
    }

    /**
     * @param  list<string>  $addresses
     * @return array{0: list<string>, 1: ?string} [bcc without zoho, last zoho address]
     */
    private function splitZoho(array $addresses): array
    {
        $bcc = [];
        $zoho = null;

        foreach ($addresses as $address) {
            if (str_contains(strtolower($address), 'zoho')) {
                $zoho = $address;
            } else {
                $bcc[] = $address;
            }
        }

        return [$bcc, $zoho];
    }
}
