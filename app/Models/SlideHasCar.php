<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// slide_has_cars has no deleted_at column, so no SoftDeletes here.
class SlideHasCar extends Model
{
    protected $table = "slide_has_cars";

    protected $fillable = ['slide_id', 'car_id', 'order_number'];

    public function car()
    {
        return $this->belongsTo(Car::class);
    }

    public function slide()
    {
        return $this->belongsTo(Slide::class);
    }
}
