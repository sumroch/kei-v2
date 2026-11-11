<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class GyautoQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'gyauto_questions';

    protected $fillable = [
        'name',
        'how_to_read',
        'phone_number',
        'gender',
        'date_birth',
        'email',
        'address',
        'married_status',
        'living_status',
        'living_year',
        'licence_number',
        'car_name',
        'company',
        'company_telephone',
        'company_address',
        'salary',
        'work_year',
        'company_type',
        'job',
        'date_1',
        'date_2',
        'date_3',
        'message',
        'type_question',
        'store_id'
    ];

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }
}
