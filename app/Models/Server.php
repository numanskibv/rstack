<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Server extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'ip_address',
        'ssh_user',
        'ssh_port',
        'status',
    ];

    protected $attributes = [
        'ssh_user' => 'root',
        'ssh_port' => 22,
        'status'   => 'active',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
