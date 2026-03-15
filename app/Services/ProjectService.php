<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ProjectService
{
    public function __construct(
        protected PortService $portService,
        protected ProjectProvisioner $provisioner,
        protected NextnameDnsService $dns,
    ) {}

    public function all(): Collection
    {
        $query = Project::with(['server', 'stack'])->latest();

        if (! Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        return $query->get();
    }

    /**
     * Paginated project list for use in the UI at scale.
     */
    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        $query = Project::with(['server', 'stack'])->latest();

        if (! Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a project record atomically, then provision its filesystem.
     *
     * The DB write (slug + port + insert) is wrapped in a transaction so that
     * concurrent creates cannot produce duplicate slugs or ports silently.
     * Provisioning runs outside the transaction to avoid holding a lock
     * during slow filesystem I/O.
     */
    public function create(array $data): Project
    {
        $project = DB::transaction(function () use ($data) {
            // Re-check capacity inside the transaction to prevent race conditions
            $server = \App\Models\Server::lockForUpdate()->findOrFail($data['server_id']);
            if (! $server->hasCapacity()) {
                throw new \Illuminate\Validation\ValidationException(
                    validator([], []),
                    response()->json(['message' => 'Server has reached its maximum project limit.'], 422)
                );
            }

            $data['slug']    = $this->uniqueSlug($data['name']);
            $data['port']    = $this->portService->allocateNext();
            $data['status']  = 'pending';
            $data['user_id'] = Auth::id();

            return Project::create($data);
        });

        try {
            $this->provisioner->provision($project);
            $project->update(['status' => 'ready']);
        } catch (Throwable $e) {
            $project->update(['status' => 'failed']);
            throw $e;
        }

        // Register DNS A-record after successful provisioning (non-fatal)
        try {
            $this->dns->register($project);
        } catch (Throwable) {
            // DNS failure does not block project creation
        }

        return $project;
    }

    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project;
    }

    /**
     * Delete a project and remove its provisioned directory from disk.
     */
    public function delete(int $id): void
    {
        $project = Auth::user()->is_admin
            ? Project::findOrFail($id)
            : Project::where('user_id', Auth::id())->findOrFail($id);

        $this->dns->remove($project);
        $this->provisioner->deprovision($project);
        $project->delete();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (Project::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
