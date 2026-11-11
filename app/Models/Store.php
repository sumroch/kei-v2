<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use SoftDeletes;

    public const BRANDS = ['flat7', 'joycal', 'keigayasui'];

    protected $fillable = ['name', 'name_id', 'email', 'email_bcc', 'postal_code', 'address', 'phone_number', 'phone_number_2', 'operational', 'slug', 'brand'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /** email_bcc is stored as a JSON array of addresses. */
    protected function casts(): array
    {
        return ['email_bcc' => 'array'];
    }
}
