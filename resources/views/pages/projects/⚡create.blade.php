<?php

use App\Services\ProjectService;
use App\Services\ServerService;
use App\Services\StackService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('New Project')] class extends Component {
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('nullable|string|max:255')]
    public string $domain = '';

    #[Validate('nullable|regex:/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/|max:63|unique:projects,subdomain')]
    public string $subdomain = '';

    #[Validate('nullable|url|max:500')]
    public string $repository = '';

    #[Validate('nullable|string|max:100')]
    public string $branch = 'main';

    #[Validate('required|exists:servers,id')]
    public string $server_id = '';

    #[Validate('required|exists:stacks,id')]
    public string $stack_id = '';

    #[Computed]
    public function servers()
    {
        return app(ServerService::class)->active()->loadCount('projects');
    }

    #[Computed]
    public function stacks()
    {
        return app(StackService::class)->all();
    }

    public function save(): void
    {
        $this->validate();

        app(ProjectService::class)->create([
            'name' => $this->name,
            'domain' => $this->domain ?: null,
            'subdomain' => $this->subdomain ?: null,
            'repository' => $this->repository ?: null,
            'branch' => $this->branch ?: 'main',
            'server_id' => $this->server_id,
            'stack_id' => $this->stack_id,
        ]);

        $this->redirect(route('projects.index'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">{{ __('New Project') }}</flux:heading>
        <flux:subheading>{{ __('Deploy an application stack to a server') }}</flux:subheading>
    </div>

    <flux:separator />

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Project Name') }}</flux:label>
            <flux:input wire:model="name" placeholder="e.g. My Laravel App" autofocus />
            <flux:description>{{ __('A slug will be generated automatically.') }}</flux:description>
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Domain') }} <flux:badge size="sm" variant="ghost">{{ __('optional') }}</flux:badge>
            </flux:label>
            <flux:input wire:model="domain" placeholder="e.g. myapp.example.com" />
            <flux:error name="domain" />
        </flux:field>

        @if (config('rstack.nextname.enabled'))
            <flux:field>
                <flux:label>
                    {{ __('Subdomain') }}
                    <flux:badge size="sm" variant="ghost">{{ __('optional') }}</flux:badge>
                </flux:label>
                <div class="flex items-center gap-0">
                    <flux:input wire:model.live="subdomain" placeholder="myapp" class="rounded-r-none"
                        pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]" />
                    <span
                        class="flex h-10 items-center rounded-r-md border border-l-0 border-zinc-300 bg-zinc-100 px-3 text-sm text-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                        .{{ config('rstack.nextname.domain', 'rstack.nl') }}
                    </span>
                </div>
                @if ($subdomain)
                    <flux:description class="text-blue-600 dark:text-blue-400">
                        {{ __('Will register:') }}
                        <strong>{{ $subdomain }}.{{ config('rstack.nextname.domain', 'rstack.nl') }}</strong>
                    </flux:description>
                @else
                    <flux:description>
                        {{ __('Lowercase letters, numbers and hyphens only. A DNS A-record will be registered automatically.') }}
                    </flux:description>
                @endif
                <flux:error name="subdomain" />
            </flux:field>
        @endif

        <flux:field>
            <flux:label>{{ __('Git Repository') }} <flux:badge size="sm" variant="ghost">{{ __('optional') }}
                </flux:badge>
            </flux:label>
            <flux:input wire:model="repository" placeholder="git@github.com:gebruiker/project.git" />
            <flux:description>
                {{ __('SSH clone URL van je GitHub/GitLab repo. Wordt automatisch gekloond bij eerste deploy.') }}
            </flux:description>
            <flux:error name="repository" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Branch') }}</flux:label>
            <flux:input wire:model="branch" placeholder="main" />
            <flux:error name="branch" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Server') }}</flux:label>
            <flux:select wire:model="server_id" placeholder="{{ __('Select a server…') }}">
                @foreach ($this->servers as $server)
                    @php
                        $isFull = $server->max_projects !== null && $server->projects_count >= $server->max_projects;
                        $capacity =
                            $server->max_projects !== null
                                ? $server->projects_count . '/' . $server->max_projects
                                : $server->projects_count;
                    @endphp
                    <flux:select.option value="{{ $server->id }}" @disabled($isFull)>
                        {{ $server->name }} ({{ $server->ip_address }}) —
                        {{ $capacity }}{{ $isFull ? ' · vol' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="server_id" />
            @if ($this->servers->isEmpty())
                <flux:description class="text-amber-500">
                    {{ __('No active servers found.') }}
                    <a href="{{ route('servers.create') }}" wire:navigate
                        class="underline">{{ __('Add a server first.') }}</a>
                </flux:description>
            @elseif (
                $server_id &&
                    ($selected = $this->servers->firstWhere('id', $server_id)) &&
                    $selected->max_projects !== null &&
                    $selected->projects_count >= $selected->max_projects)
                <flux:description class="text-red-500">
                    {{ __('This server has reached its maximum number of projects (:max).', ['max' => $selected->max_projects]) }}
                </flux:description>
            @endif
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Stack') }}</flux:label>
            <flux:select wire:model="stack_id" placeholder="{{ __('Select a stack…') }}">
                @foreach ($this->stacks as $stack)
                    <flux:select.option value="{{ $stack->id }}">{{ $stack->name }} ({{ $stack->runtime }})
                    </flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="stack_id" />
        </flux:field>

        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
            <flux:heading size="sm">{{ __('Port Allocation') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ __('A port will be automatically assigned starting from 8001.') }}
            </flux:text>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">
                {{ __('Create Project') }}
            </flux:button>
            <flux:button href="{{ route('projects.index') }}" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

</div>
