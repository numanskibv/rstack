<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'domain',
        'repository',
        'branch',
        'port',
        'server_id',
        'stack_id',
        'status',
        'env_vars',
    ];

    protected function casts(): array
    {
        return [
            'env_vars' => 'array',
        ];
    }

    public function statusColor(): string
    {
        return match ($this->status) {
            'running' => 'green',
            'ready'   => 'sky',
            'pending' => 'yellow',
            'stopped' => 'zinc',
            'failed'  => 'red',
            default   => 'zinc',
        };
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function stack(): BelongsTo
    {
        return $this->belongsTo(Stack::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }
}
