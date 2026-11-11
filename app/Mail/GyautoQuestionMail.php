<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GyautoQuestionMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data, $type;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $type = 'raiten')
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('【アキヤマグループ】お問い合せを受け付けました。')->view('mail.gyauto-loan');
    }
}
