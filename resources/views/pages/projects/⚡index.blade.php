<?php

use App\Services\NextnameDnsService;
use App\Services\ProjectService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Projects')] class extends Component {
    #[Computed]
    public function projects()
    {
        return app(ProjectService::class)->all();
    }

    public function delete(int $id): void
    {
        app(ProjectService::class)->delete($id);
        unset($this->projects);
    }

    public function checkDns(int $id): void
    {
        $project = $this->projects->firstWhere('id', $id);
        if ($project) {
            app(NextnameDnsService::class)->checkPropagation($project);
            unset($this->projects);
        }
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
            <flux:subheading>{{ __('Deployed application stacks') }}</flux:subheading>
        </div>
        <flux:button icon="plus" href="{{ route('projects.create') }}" wire:navigate variant="primary">
            {{ __('New Project') }}
        </flux:button>
    </div>

    <flux:separator />

    @if ($this->projects->isEmpty())
        <div
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-300 p-12 dark:border-zinc-700">
            <flux:icon.squares-2x2 class="size-10 text-zinc-400" />
            <div class="text-center">
                <flux:heading>{{ __('No projects yet') }}</flux:heading>
                <flux:subheading>{{ __('Create your first project to start deploying.') }}</flux:subheading>
            </div>
            <flux:button href="{{ route('projects.create') }}" wire:navigate>
                {{ __('Create Project') }}
            </flux:button>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Name') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Server') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Stack') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Domain') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Port') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->projects as $project)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium">
                                {{ $project->name }}
                                <div class="text-xs text-zinc-400 font-mono">{{ $project->slug }}</div>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->server->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->stack->name }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                @if ($project->subdomain)
                                    <span
                                        class="font-mono text-xs">{{ $project->subdomain }}.{{ config('rstack.nextname.domain', 'rstack.nl') }}</span>
                                    @php
                                        $dnsColor = match ($project->dns_status) {
                                            'active' => 'green',
                                            'pending' => 'yellow',
                                            'failed' => 'red',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge size="sm" :color="$dnsColor" class="ml-1">
                                        {{ $project->dns_status ?? 'unregistered' }}</flux:badge>
                                @elseif ($project->domain)
                                    {{ $project->domain }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">{{ $project->port }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$project->statusColor()">{{ ucfirst($project->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($project->subdomain && $project->dns_status !== 'active' && config('rstack.nextname.enabled'))
                                        <flux:button wire:click="checkDns({{ $project->id }})" size="sm"
                                            variant="ghost" icon="signal">
                                            {{ __('Check DNS') }}
                                        </flux:button>
                                    @endif
                                    <flux:button wire:click="delete({{ $project->id }})"
                                        wire:confirm="Delete this project?" size="sm" variant="danger">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
