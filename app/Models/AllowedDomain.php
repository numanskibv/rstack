<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowedDomain extends Model
{
    protected $fillable = ['domain', 'note'];

    /**
     * Check whether the given email address belongs to an allowed domain.
     */
    public static function allows(string $email): bool
    {
        $domain = strtolower(substr($email, strpos($email, '@') + 1));

        return static::whereRaw('LOWER(domain) = ?', [$domain])->exists();
    }
}
