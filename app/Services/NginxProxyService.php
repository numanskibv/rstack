<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class NginxProxyService
{
    private string $baseUrl;
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('rstack.npm.url', ''), '/');
    }

    /**
     * Create or update a proxy host in NPM for the given project.
     */
    public function provision(Project $project): void
    {
        if (! config('rstack.npm.enabled') || ! $this->baseUrl) {
            return;
        }

        $project->loadMissing('server');

        $this->authenticate();

        $existing = $this->findProxyHost($project->domain);

        if ($existing) {
            $this->updateProxyHost($existing['id'], $project);
        } else {
            $this->createProxyHost($project);
        }
    }

    /**
     * Delete the proxy host for a project from NPM.
     */
    public function deprovision(Project $project): void
    {
        if (! config('rstack.npm.enabled') || ! $this->baseUrl) {
            return;
        }

        $this->authenticate();

        $existing = $this->findProxyHost($project->domain);

        if ($existing) {
            $this->client()->delete("/api/nginx/proxy-hosts/{$existing['id']}");
        }
    }

    private function authenticate(): void
    {
        $response = Http::post("{$this->baseUrl}/api/tokens", [
            'identity' => config('rstack.npm.email'),
            'secret'   => config('rstack.npm.password'),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('NPM authenticatie mislukt: ' . $response->body());
        }

        $this->token = $response->json('token');
    }

    private function findProxyHost(string $domain): ?array
    {
        $response = $this->client()->get('/api/nginx/proxy-hosts');

        if (! $response->successful()) {
            return null;
        }

        foreach ($response->json() as $host) {
            if (in_array($domain, $host['domain_names'] ?? [], true)) {
                return $host;
            }
        }

        return null;
    }

    private function createProxyHost(Project $project): void
    {
        $response = $this->client()->post('/api/nginx/proxy-hosts', $this->proxyPayload($project));

        if (! $response->successful()) {
            throw new RuntimeException('NPM proxy host aanmaken mislukt: ' . $response->body());
        }
    }

    private function updateProxyHost(int $id, Project $project): void
    {
        $response = $this->client()->put("/api/nginx/proxy-hosts/{$id}", $this->proxyPayload($project));

        if (! $response->successful()) {
            throw new RuntimeException('NPM proxy host bijwerken mislukt: ' . $response->body());
        }
    }

    private function proxyPayload(Project $project): array
    {
        return [
            'domain_names'          => [$project->domain],
            'forward_scheme'        => 'http',
            'forward_host'          => $project->server->ip_address,
            'forward_port'          => $project->port,
            'block_exploits'        => true,
            'allow_websocket_upgrade' => true,
            'http2_support'         => false,
            'ssl_forced'            => false,
            'hsts_enabled'          => false,
            'hsts_subdomains'       => false,
            'caching_enabled'       => false,
            'enabled'               => true,
            'meta'                  => ['letsencrypt_agree' => false],
            'advanced_config'       => '',
            'locations'             => [],
            'certificate_id'        => 0,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token)
            ->baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson();
    }
}
