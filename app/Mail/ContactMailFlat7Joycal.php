<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactMailFlat7Joycal extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject    = 'お問い合わせを受け付けました 【 ' . $this->data->store->name . ' 】';
        return $this->subject($subject)->view('mail.contact-flat7-joycal')->text('mail_plain.contact-flat7-joycal');
    }
}
