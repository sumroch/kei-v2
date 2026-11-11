<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'name',
        'how_to_read',
        'phone_number',
        'email',
        'address',
        'time1',
        'time2',
        'time3',
        'date1',
        'date2',
        'date3',
        'question',
        'other_question',
        'message',
        'type_reservation',
        'type_customer',
        'type_question',
        'usage',
        'buyer',
        'store_id',
        'can_contacted',
        'can_contacted_day',
        'car',
        'other_car',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
