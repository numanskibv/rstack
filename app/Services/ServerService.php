<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class ServerService
{
    public function all(): Collection
    {
        $query = Server::withCount('projects')->latest();

        if (! Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    public function active(): Collection
    {
        $query = Server::where('status', 'active')->orderBy('name');

        if (! Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
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
        $server = Auth::user()->is_admin
            ? Server::findOrFail($id)
            : Server::where('user_id', Auth::id())->findOrFail($id);

        $server->delete();
    }
}
