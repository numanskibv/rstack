<x-layouts::app :title="__('Dashboard')">
    @php
        $serverCount = \App\Models\Server::count();
        $projectCount = \App\Models\Project::count();
        $stackCount = \App\Models\Stack::count();
        $runningCount = \App\Models\Project::where('status', 'running')->count();
    @endphp

    <div class="flex h-full w-full flex-1 flex-col gap-6">

        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Overview of your RStack deployment platform') }}</flux:subheading>
        </div>

        <flux:separator />

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <a href="{{ route('servers.index') }}" wire:navigate
                class="group rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                <div class="mb-2 flex items-center gap-2 text-zinc-500">
                    <flux:icon.server class="size-5" />
                    <span class="text-sm font-medium">{{ __('Servers') }}</span>
                </div>
                <div class="text-3xl font-bold">{{ $serverCount }}</div>
            </a>

            <a href="{{ route('projects.index') }}" wire:navigate
                class="group rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                <div class="mb-2 flex items-center gap-2 text-zinc-500">
                    <flux:icon.squares-2x2 class="size-5" />
                    <span class="text-sm font-medium">{{ __('Projects') }}</span>
                </div>
                <div class="text-3xl font-bold">{{ $projectCount }}</div>
            </a>

            <a href="{{ route('stacks.index') }}" wire:navigate
                class="group rounded-xl border border-zinc-200 p-5 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-500">
                <div class="mb-2 flex items-center gap-2 text-zinc-500">
                    <flux:icon.squares-plus class="size-5" />
                    <span class="text-sm font-medium">{{ __('Stacks') }}</span>
                </div>
                <div class="text-3xl font-bold">{{ $stackCount }}</div>
            </a>

            <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                <div class="mb-2 flex items-center gap-2 text-zinc-500">
                    <flux:icon.play-circle class="size-5" />
                    <span class="text-sm font-medium">{{ __('Running') }}</span>
                </div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $runningCount }}</div>
            </div>
        </div>

        @if ($projectCount > 0)
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                    <flux:heading size="sm">{{ __('Recent Projects') }}</flux:heading>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                                {{ __('Project') }}</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                                {{ __('Server') }}</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                                {{ __('Stack') }}</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                                {{ __('Port') }}</th>
                            <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">
                                {{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach (\App\Models\Project::with(['server', 'stack'])->latest()->take(5)->get() as $project)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-3 font-medium">{{ $project->name }}</td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->server->name }}
                                </td>
                                <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $project->stack->name }}</td>
                                <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">{{ $project->port }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $color = match ($project->status) {
                                            'running' => 'green',
                                            'pending' => 'yellow',
                                            'stopped' => 'zinc',
                                            'failed' => 'red',
                                            default => 'zinc',
                                        };
                                    @endphp
                                    <flux:badge :color="$color">{{ ucfirst($project->status) }}</flux:badge>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div
                class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-300 p-12 dark:border-zinc-700">
                <flux:icon.rocket-launch class="size-10 text-zinc-400" />
                <div class="text-center">
                    <flux:heading>{{ __('Welcome to RStack') }}</flux:heading>
                    <flux:subheading>{{ __('Start by adding a server, then deploy your first project.') }}
                    </flux:subheading>
                </div>
                <div class="flex gap-3">
                    <flux:button href="{{ route('servers.create') }}" wire:navigate variant="primary">
                        {{ __('Add a Server') }}
                    </flux:button>
                </div>
            </div>
        @endif

    </div>
</x-layouts::app>
