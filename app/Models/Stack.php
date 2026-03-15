<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stack extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'runtime',
        'description',
        'template_path',
    ];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
