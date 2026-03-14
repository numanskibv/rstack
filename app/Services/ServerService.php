<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ServerService
{
    public function all(): Collection
    {
        return Server::withCount('projects')->latest()->get();
    }

    public function active(): Collection
    {
        return Server::where('status', 'active')->orderBy('name')->get();
    }

    public function create(array $data): Server
    {
        $data['user_id'] = Auth::id();
        return Server::create($data);
    }

    public function update(Server $server, array $data): Server
    {
        $server->update($data);

        return $server;
    }

    public function delete(int $id): void
    {
        Server::findOrFail($id)->delete();
    }
}
