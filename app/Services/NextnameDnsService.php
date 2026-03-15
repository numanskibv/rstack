<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Manages DNS A-records for RStack projects via the Nextname.nl JSON REST API.
 *
 * Endpoint reference (Nextname API v2):
 *   GET    /v2/domains/{domain}/records          – list all records
 *   POST   /v2/domains/{domain}/records          – create a record
 *   DELETE /v2/domains/{domain}/records/{id}     – delete a record
 *
 * Auth: Authorization: Bearer {NEXTNAME_API_KEY}
 */
class NextnameDnsService
{
    private string $baseUrl;
    private string $apiKey;
    private string $domain;
    private int    $ttl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('rstack.nextname.url', 'https://api.nextname.nl/v2'), '/');
        $this->apiKey  = config('rstack.nextname.key', '');
        $this->domain  = config('rstack.nextname.domain', 'rstack.nl');
        $this->ttl     = config('rstack.nextname.ttl', 300);
    }

    /**
     * Whether DNS integration is enabled and configured.
     */
    public function enabled(): bool
    {
        return (bool) config('rstack.nextname.enabled') && $this->apiKey !== '';
    }

    /**
     * Register an A-record for the project's subdomain pointing to the server IP.
     * Updates the project's dns_status to 'pending' on success.
     */
    public function register(Project $project): void
    {
        if (! $this->enabled() || blank($project->subdomain)) {
            return;
        }

        $project->loadMissing('server');

        $serverIp = $project->server->ip_address ?? '';
        if (blank($serverIp)) {
            throw new RuntimeException("Server IP address is missing for project [{$project->slug}].");
        }

        // Check if a record already exists (idempotent)
        $existing = $this->findRecord($project->subdomain);
        if ($existing) {
            // Update if the IP changed
            if (($existing['content'] ?? '') !== $serverIp) {
                $this->deleteRecordById($existing['id']);
                $this->createRecord($project->subdomain, $serverIp);
            }
        } else {
            $this->createRecord($project->subdomain, $serverIp);
        }

        $project->update(['dns_status' => 'pending']);
    }

    /**
     * Remove the A-record for the project's subdomain.
     */
    public function remove(Project $project): void
    {
        if (! $this->enabled() || blank($project->subdomain)) {
            return;
        }

        $existing = $this->findRecord($project->subdomain);
        if ($existing) {
            $this->deleteRecordById($existing['id']);
        }

        $project->update(['dns_status' => null]);
    }

    /**
     * Check whether the subdomain has propagated to the configured server IP.
     * Updates dns_status to 'active' or 'failed' accordingly and returns the result.
     */
    public function checkPropagation(Project $project): bool
    {
        if (blank($project->subdomain)) {
            return false;
        }

        $project->loadMissing('server');
        $fqdn     = $project->subdomain . '.' . $this->domain;
        $serverIp = $project->server->ip_address ?? '';

        $records = @dns_get_record($fqdn, DNS_A) ?: [];

        $resolved = collect($records)->contains(fn(array $r) => ($r['ip'] ?? '') === $serverIp);

        $project->update(['dns_status' => $resolved ? 'active' : 'pending']);

        return $resolved;
    }

    /**
     * Return the fully-qualified domain name for a project's subdomain.
     */
    public function fqdn(Project $project): string
    {
        return $project->subdomain . '.' . $this->domain;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function createRecord(string $subdomain, string $ip): void
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->post("{$this->baseUrl}/domains/{$this->domain}/records", [
                'type'    => 'A',
                'name'    => $subdomain,
                'content' => $ip,
                'ttl'     => $this->ttl,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                "Nextname DNS record aanmaken mislukt voor [{$subdomain}.{$this->domain}]: " . $response->body()
            );
        }
    }

    private function deleteRecordById(int|string $id): void
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->delete("{$this->baseUrl}/domains/{$this->domain}/records/{$id}");

        if (! $response->successful()) {
            throw new RuntimeException("Nextname DNS record verwijderen mislukt (id={$id}): " . $response->body());
        }
    }

    private function findRecord(string $subdomain): ?array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/domains/{$this->domain}/records");

        if (! $response->successful()) {
            return null;
        }

        // The API may return the records directly as an array, or nested under a key.
        $records = $response->json('records') ?? $response->json('data') ?? $response->json() ?? [];

        foreach ($records as $record) {
            if (
                strtoupper($record['type'] ?? '') === 'A' &&
                $record['name'] === $subdomain
            ) {
                return $record;
            }
        }

        return null;
    }
}
