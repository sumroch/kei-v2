<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RaitenMail extends Mailable
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
        return $this->type == 'raiten' 
        ? $this->subject('ご来店予約を受け付けました【 '.$this->data->store->name.' 】')->view('mail.raiten') 
        : $this->subject('店頭来店予約を受け付けました【 '.$this->data->store->name.' 】')->view('mail.raiten');
    }
}
