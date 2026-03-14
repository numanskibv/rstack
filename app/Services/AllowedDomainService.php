<?php

namespace App\Services;

use App\Models\AllowedDomain;
use Illuminate\Database\Eloquent\Collection;

class AllowedDomainService
{
    public function all(): Collection
    {
        return AllowedDomain::orderBy('domain')->get();
    }

    public function add(string $domain, ?string $note = null): AllowedDomain
    {
        $domain = strtolower(trim($domain));

        return AllowedDomain::firstOrCreate(
            ['domain' => $domain],
            ['note'   => $note],
        );
    }

    public function delete(int $id): void
    {
        AllowedDomain::findOrFail($id)->delete();
    }

    public function allows(string $email): bool
    {
        return AllowedDomain::allows($email);
    }
}
