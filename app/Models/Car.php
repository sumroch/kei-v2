<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Car extends Model
{
    use SoftDeletes;

    public const IMAGE_DIR = 'car/';

    public const STORE_TYPES = ['flat7_joycal', 'keigayasui'];

    protected $fillable = [
        'maker', 
        'picture', 
        'brand', 
        'grade', 
        'fuel', 
        'new', 
        'price', 
        'total', 
        'monthly', 
        'store_type',
        'active'
    ];

    public function pictureUrl(): ?string
    {
        return $this->picture ? Storage::disk('public')->url(self::IMAGE_DIR.$this->picture) : null;
    }
}
