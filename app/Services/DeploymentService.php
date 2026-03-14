<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;
use Spatie\Ssh\Ssh;
use Throwable;

class DeploymentService
{
    public function forProject(Project $project): Collection
    {
        return $project->deployments()->latest()->get();
    }

    public function latest(Project $project): ?Deployment
    {
        return $project->deployments()->latest()->first();
    }

    public function create(Project $project, array $data): Deployment
    {
        return $project->deployments()->create(array_merge([
            'branch' => 'main',
            'status' => 'pending',
        ], $data));
    }

    /**
     * Deploy a project to its assigned server.
     *
     * Flow:
     *   1. Verify the project is in "ready" status.
     *   2. Create a Deployment record with status "running".
     *   3. Connect to the server via SSH (spatie/ssh).
     *   4. Navigate to the remote project directory and run docker compose up -d.
     *   5. Persist stdout+stderr as the deployment log.
     *   6. On success → status "deployed" + deployed_at timestamp + project "running".
     *      On failure → status "failed" + log + project "failed".
     *
     * @throws RuntimeException  When the project is not ready to deploy.
     * @throws Throwable          Re-throws any SSH / process exception after persisting failure.
     */
    public function deploy(Project $project): Deployment
    {
        if ($project->status !== 'ready') {
            throw new RuntimeException(
                "Project [{$project->slug}] cannot be deployed. Expected status \"ready\", got \"{$project->status}\"."
            );
        }

        $project->loadMissing('server');

        $deployment = $project->deployments()->create([
            'status' => 'running',
            'branch' => 'main',
        ]);

        try {
            $remoteDir = rtrim(config('rstack.remote_project_root', '/srv/rstack/projects'), '/') . '/' . $project->slug;

            $process = Ssh::create(
                $project->server->ssh_user,
                $project->server->ip_address,
            )
                ->usePort($project->server->ssh_port)
                ->usePrivateKey($this->sshKeyPath())
                ->disableStrictHostKeyChecking()
                ->setTimeout(config('rstack.ssh_timeout', 120))
                ->execute([
                    'cd ' . escapeshellarg($remoteDir),
                    'docker compose up -d 2>&1',
                ]);

            $log = trim($process->getOutput() . $process->getErrorOutput());

            if ($process->isSuccessful()) {
                $this->markDeployed($deployment, $log);
            } else {
                $this->markFailed($deployment, $log);
            }
        } catch (Throwable $e) {
            $this->markFailed($deployment, $e->getMessage());
            throw $e;
        }

        return $deployment->fresh();
    }

    /**
     * Mark a deployment as successfully deployed.
     */
    public function markDeployed(Deployment $deployment, string $log = ''): void
    {
        $deployment->update([
            'status'      => 'deployed',
            'log'         => $log,
            'deployed_at' => now(),
        ]);

        $deployment->project->update(['status' => 'running']);
    }

    /**
     * Mark a deployment as failed and persist the log output.
     */
    public function markFailed(Deployment $deployment, string $log = ''): void
    {
        $deployment->update([
            'status' => 'failed',
            'log'    => $log,
        ]);

        $deployment->project->update(['status' => 'failed']);
    }

    /**
     * Absolute path to the SSH private key used to connect to managed servers.
     */
    protected function sshKeyPath(): string
    {
        return config('rstack.ssh_key_path', storage_path('app/ssh/id_rsa'));
    }
}
