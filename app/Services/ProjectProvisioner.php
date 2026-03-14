<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * ProjectProvisioner is responsible for all local filesystem operations
 * needed to prepare a project for deployment.
 *
 * Responsibilities:
 *   - Create the project staging directory
 *   - Copy the selected stack template into that directory
 *   - Write a populated .env file from the stack's .env.template
 *
 * This service does NOT interact with Docker or remote servers.
 * Docker orchestration is handled by DeploymentService.
 */
class ProjectProvisioner
{
    public function __construct(
        protected StackService $stackService,
    ) {}

    /**
     * Run all filesystem provisioning steps for a project.
     *
     * Throws on any failure. Status management (pending → ready / failed)
     * is the caller's responsibility (ProjectService).
     */
    public function provision(Project $project): void
    {
        $project->loadMissing('stack');

        if (! $this->stackService->templateExists($project->stack)) {
            throw new RuntimeException(
                "Stack template not found for stack [{$project->stack->slug}] at path: {$project->stack->template_path}"
            );
        }

        $this->createDirectory($project);
        $this->copyTemplate($project);
        $this->writeEnv($project);
    }

    /**
     * Returns the absolute path to the project's staging directory.
     */
    public function directoryFor(Project $project): string
    {
        return storage_path('app/projects/' . $project->slug);
    }

    /**
     * Creates the project staging directory if it does not exist.
     */
    public function createDirectory(Project $project): void
    {
        File::ensureDirectoryExists($this->directoryFor($project));
    }

    /**
     * Copies the full stack template directory into the project directory.
     * Overwrites any existing files.
     *
     * Guards against path traversal: template_path must resolve inside
     * storage/stacks/ to prevent a malicious stack record from reading
     * arbitrary files from the server filesystem.
     */
    public function copyTemplate(Project $project): void
    {
        $source  = realpath(base_path($project->stack->template_path)) ?: '';
        $allowed = realpath(base_path('storage/stacks')) ?: '';

        if ($allowed === '' || ! str_starts_with($source, $allowed . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException(
                "Template path [{$project->stack->template_path}] resolves outside storage/stacks."
            );
        }

        File::copyDirectory($source, $this->directoryFor($project));
    }

    /**
     * Removes the project's staging directory and all its contents.
     * Called when a project is deleted.
     */
    public function deprovision(Project $project): void
    {
        $directory = $this->directoryFor($project);

        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }
    }

    /**
     * Writes a .env file into the project directory.
     *
     * Merges (in order of priority):
     *   1. Keys from .env.template (empty defaults)
     *   2. RStack-managed values (APP_NAME, APP_PORT)
     *   3. User-supplied env_vars from the project record
     */
    public function writeEnv(Project $project): void
    {
        $vars = array_merge(
            $this->stackService->loadEnvTemplate($project->stack),
            $this->platformVars($project),
            $project->env_vars ?? [],
        );

        $lines = array_map(
            fn(string $key, string $value) => $key . '=' . $value,
            array_keys($vars),
            array_values($vars),
        );

        File::put(
            $this->directoryFor($project) . '/.env',
            implode("\n", $lines) . "\n"
        );
    }

    /**
     * Variables injected by RStack into every project .env.
     */
    protected function platformVars(Project $project): array
    {
        return [
            'APP_NAME' => $project->name,
            'APP_PORT' => (string) $project->port,
        ];
    }
}
