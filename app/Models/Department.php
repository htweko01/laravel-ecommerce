<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Department extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    protected $fillable = ['name', 'slug'];
}
