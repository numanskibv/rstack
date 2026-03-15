<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\NextnameDnsService;
use App\Services\NginxProxyService;
use Illuminate\Console\Command;

class CheckDnsPropagation extends Command
{
    protected $signature = 'rstack:check-dns
                            {--project= : Slug van een specifiek project}
                            {--all      : Controleer alle projecten met een subdomain, ook active}
                            {--ssl      : Vraag SSL-certificaat via NPM aan zodra DNS actief is}';

    protected $description = 'Controleer DNS-propagatie van RStack subdomeinen en update dns_status';

    public function handle(NextnameDnsService $dns, NginxProxyService $npm): int
    {
        if (! $dns->enabled()) {
            $this->warn('Nextname DNS integratie is uitgeschakeld (NEXTNAME_ENABLED=false of geen API key).');
            return self::SUCCESS;
        }

        $query = Project::with(['server', 'stack'])
            ->whereNotNull('subdomain');

        if ($slug = $this->option('project')) {
            $query->where('slug', $slug);
        } elseif (! $this->option('all')) {
            // By default only check projects that are not yet active
            $query->where(fn($q) => $q->where('dns_status', 'pending')->orWhereNull('dns_status'));
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('Geen projecten gevonden om te controleren.');
            return self::SUCCESS;
        }

        $this->info("DNS-propagatie controleren voor {$projects->count()} project(en)…");
        $this->newLine();

        $rows   = [];
        $active = 0;
        $pending = 0;

        foreach ($projects as $project) {
            $fqdn    = $dns->fqdn($project);
            $before  = $project->dns_status;
            $resolved = $dns->checkPropagation($project);
            $project->refresh();
            $after   = $project->dns_status;

            $statusLabel = $resolved ? '<fg=green>✓ active</>' : '<fg=yellow>✗ pending</>';

            $rows[] = [
                $project->name,
                $project->slug,
                $fqdn,
                $project->server->ip_address ?? '?',
                $statusLabel,
            ];

            if ($resolved) {
                $active++;

                if ($this->option('ssl') && $after === 'active' && $before !== 'active') {
                    $this->line("  → SSL aanvragen via NPM voor <comment>{$fqdn}</comment>…");
                    try {
                        // Set domain to FQDN so NPM picks it up, then provision
                        $project->update(['domain' => $fqdn]);
                        $npm->provision($project);
                        $this->line("    <info>✓ NPM proxy host aangemaakt.</info>");
                    } catch (\Throwable $e) {
                        $this->line("    <fg=red>✗ NPM fout: {$e->getMessage()}</>");
                    }
                }
            } else {
                $pending++;
            }
        }

        $this->table(
            ['Project', 'Slug', 'Subdomain (FQDN)', 'Server IP', 'DNS Status'],
            $rows
        );

        $this->newLine();
        $this->line("<info>{$active} actief</info>, <comment>{$pending} pending</comment>");

        return self::SUCCESS;
    }
}
