<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{

    use SoftDeletes;
    
    protected $fillable = ['owl', 'color', 'title1', 'title2', 'maker', 'order_slide', 'dots', 'store_type', 'active'];

    public function car()
    {
        return $this->belongsToMany(Car::class, 'slide_has_cars')->withPivot('order_number');
    }
}
