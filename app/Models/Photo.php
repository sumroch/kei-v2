<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use SoftDeletes;

    public const IMAGE_DIR = 'customer/';

    protected $fillable = [
        'date', 
        'initial', 
        'image', 
        'maker', 
        'store_id', 
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url(self::IMAGE_DIR.$this->image) : null;
    }
}
