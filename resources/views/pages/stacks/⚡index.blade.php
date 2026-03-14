<?php

use App\Services\StackService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Stacks')] class extends Component {
    #[Computed]
    public function stacks()
    {
        return app(StackService::class)->all();
    }
}; ?>

<div class="flex h-full w-full flex-1 flex-col gap-6">

    <div>
        <flux:heading size="xl">{{ __('Stacks') }}</flux:heading>
        <flux:subheading>{{ __('Available deployment templates') }}</flux:subheading>
    </div>

    <flux:separator />

    @if ($this->stacks->isEmpty())
        <div
            class="flex flex-col items-center justify-center gap-4 rounded-xl border border-dashed border-zinc-300 p-12 dark:border-zinc-700">
            <flux:icon.squares-plus class="size-10 text-zinc-400" />
            <div class="text-center">
                <flux:heading>{{ __('No stacks available') }}</flux:heading>
                <flux:subheading>{{ __('Run database seeds to load the default stacks.') }}</flux:subheading>
            </div>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->stacks as $stack)
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                    <div class="mb-3 flex items-start justify-between">
                        <flux:heading size="lg">{{ $stack->name }}</flux:heading>
                        <flux:badge>{{ $stack->runtime }}</flux:badge>
                    </div>
                    @if ($stack->description)
                        <flux:text class="mb-4 text-sm text-zinc-500">{{ $stack->description }}</flux:text>
                    @endif
                    <div class="flex items-center justify-between">
                        <flux:text class="text-xs text-zinc-400 font-mono">{{ $stack->template_path }}</flux:text>
                        <flux:badge color="blue" size="sm">
                            {{ $stack->projects_count }} {{ $stack->projects_count === 1 ? 'project' : 'projects' }}
                        </flux:badge>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
