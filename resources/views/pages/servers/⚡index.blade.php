<?php

use App\Services\ServerService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Servers')] class extends Component {
    #[Computed]
    public function servers()
    {
        return app(ServerService::class)->all();
    }

    public function delete(int $id): void
    {
        app(ServerService::class)->delete($id);
        unset($this->servers);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Servers') }}</flux:heading>
            <flux:subheading>{{ __('Docker hosts registered in RStack') }}</flux:subheading>
        </div>
        <flux:button icon="plus" href="{{ route('servers.create') }}" wire:navigate variant="primary">
            {{ __('Add Server') }}
        </flux:button>
    </div>

    <flux:separator />

    @if ($this->servers->isEmpty())
        <div
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-300 p-12 dark:border-zinc-700">
            <flux:icon.server class="size-10 text-zinc-400" />
            <div class="text-center">
                <flux:heading>{{ __('No servers yet') }}</flux:heading>
                <flux:subheading>{{ __('Add your first Docker host to get started.') }}</flux:subheading>
            </div>
            <flux:button href="{{ route('servers.create') }}" wire:navigate>
                {{ __('Add Server') }}
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
                            {{ __('IP Address') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('SSH') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Projects') }}</th>
                        <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                            {{ __('Status') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->servers as $server)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium">{{ $server->name }}</td>
                            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">{{ $server->ip_address }}
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $server->ssh_user }}:{{ $server->ssh_port }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $server->projects_count }}</td>
                            <td class="px-4 py-3">
                                <flux:badge :color="$server->status === 'active' ? 'green' : 'zinc'">
                                    {{ ucfirst($server->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <flux:button wire:click="delete({{ $server->id }})"
                                    wire:confirm="Remove this server?" size="sm" variant="danger">
                                    {{ __('Remove') }}
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
