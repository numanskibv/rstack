<?php

use App\Services\ServerService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Add Server')] class extends Component {
    #[Validate('required|string|max:100')]
    public string $name = '';

    #[Validate('required|ip')]
    public string $ip_address = '';

    #[Validate('required|string|max:50')]
    public string $ssh_user = 'root';

    #[Validate('required|integer|min:1|max:65535')]
    public int $ssh_port = 22;

    public function save(): void
    {
        $this->validate();

        app(ServerService::class)->create([
            'name' => $this->name,
            'ip_address' => $this->ip_address,
            'ssh_user' => $this->ssh_user,
            'ssh_port' => $this->ssh_port,
        ]);

        $this->redirect(route('servers.index'), navigate: true);
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">{{ __('Add Server') }}</flux:heading>
        <flux:subheading>{{ __('Register a Docker host in RStack') }}</flux:subheading>
    </div>

    <flux:separator />

    <form wire:submit="save" class="max-w-lg space-y-6">
        <flux:field>
            <flux:label>{{ __('Server Name') }}</flux:label>
            <flux:input wire:model="name" placeholder="e.g. Home NUC" autofocus />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('IP Address') }}</flux:label>
            <flux:input wire:model="ip_address" placeholder="e.g. 192.168.1.10" />
            <flux:error name="ip_address" />
        </flux:field>

        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>{{ __('SSH User') }}</flux:label>
                <flux:input wire:model="ssh_user" placeholder="root" />
                <flux:error name="ssh_user" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('SSH Port') }}</flux:label>
                <flux:input wire:model="ssh_port" type="number" placeholder="22" />
                <flux:error name="ssh_port" />
            </flux:field>
        </div>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">
                {{ __('Add Server') }}
            </flux:button>
            <flux:button href="{{ route('servers.index') }}" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

</div>
