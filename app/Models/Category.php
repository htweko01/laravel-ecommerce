<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use InteractsWithMedia;
    
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    protected $fillable = ['name', 'slug',  'department_id'];
}
