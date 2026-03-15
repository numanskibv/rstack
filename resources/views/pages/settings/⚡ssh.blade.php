<?php

use App\Services\SshKeyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('SSH Key')] class extends Component {
    public ?string $publicKey = null;
    public ?string $fingerprint = null;
    public bool $confirmRegenerate = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->publicKey = $user->ssh_public_key;
        $this->fingerprint = $user->ssh_key_fingerprint;
    }

    public function generate(): void
    {
        $user = Auth::user();

        if ($user->ssh_public_key && !$this->confirmRegenerate) {
            $this->confirmRegenerate = true;
            return;
        }

        $this->confirmRegenerate = false;

        $publicKey = app(SshKeyService::class)->generate($user);

        $user->refresh();
        $this->publicKey = $user->ssh_public_key;
        $this->fingerprint = $user->ssh_key_fingerprint;

        session()->flash('status', 'ssh-key-generated');
    }

    public function cancelRegenerate(): void
    {
        $this->confirmRegenerate = false;
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('SSH Key')" :subheading="__('Your personal deploy key for pulling private repositories on the server.')">
        @if (session('status') === 'ssh-key-generated')
            <flux:callout variant="success" icon="check-circle" class="mb-4">
                <flux:callout.heading>{{ __('SSH key generated') }}</flux:callout.heading>
                <flux:callout.text>
                    {{ __('Your new SSH key is ready. Add the public key as a deploy key to your repositories.') }}
                </flux:callout.text>
            </flux:callout>
        @endif

        @if ($publicKey)
            <div class="space-y-4">
                <div>
                    <flux:label>{{ __('Public key') }}</flux:label>
                    @if ($fingerprint)
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-mono">{{ $fingerprint }}</p>
                    @endif
                    <div class="relative mt-2">
                        <textarea id="ssh-public-key" rows="4" readonly
                            class="w-full rounded-lg border border-zinc-200 bg-zinc-50 p-3 font-mono text-xs text-zinc-800 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 resize-none focus:outline-none">{{ $publicKey }}</textarea>
                    </div>
                    <div class="mt-2">
                        <flux:button size="sm" icon="clipboard" x-data
                            x-on:click="
                                navigator.clipboard.writeText($el.closest('.space-y-4').querySelector('#ssh-public-key').value);
                                $el.querySelector('span').textContent = '{{ __('Copied!') }}';
                                setTimeout(() => $el.querySelector('span').textContent = '{{ __('Copy') }}', 2000);
                            ">
                            {{ __('Copy') }}
                        </flux:button>
                    </div>
                </div>

                <flux:separator />

                <div>
                    <flux:heading size="sm">{{ __('How to use') }}</flux:heading>
                    <ul class="mt-2 space-y-1 text-sm text-zinc-600 dark:text-zinc-400 list-disc list-inside">
                        <li>{{ __('GitHub: Settings → Deploy keys → Add deploy key') }}</li>
                        <li>{{ __('Gitea: Repository → Settings → Deploy Keys → Add Key') }}</li>
                        <li>{{ __('GitLab: Repository → Settings → Repository → Deploy keys') }}</li>
                    </ul>
                </div>

                <flux:separator />

                @if ($confirmRegenerate)
                    <flux:callout variant="warning" icon="exclamation-triangle">
                        <flux:callout.heading>{{ __('Regenerate SSH key?') }}</flux:callout.heading>
                        <flux:callout.text>
                            {{ __('This will invalidate your current key. You will need to update all repositories where this key is used as a deploy key.') }}
                        </flux:callout.text>
                    </flux:callout>
                    <div class="flex gap-2">
                        <flux:button variant="danger" wire:click="generate">{{ __('Yes, regenerate') }}</flux:button>
                        <flux:button wire:click="cancelRegenerate">{{ __('Cancel') }}</flux:button>
                    </div>
                @else
                    <flux:button variant="ghost" wire:click="generate" icon="arrow-path">
                        {{ __('Regenerate key') }}
                    </flux:button>
                @endif
            </div>
        @else
            <div class="space-y-4">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('You do not have an SSH key yet. Generate one to use for deploying private repositories.') }}
                </p>
                <flux:button variant="primary" wire:click="generate" icon="key">
                    {{ __('Generate SSH key') }}
                </flux:button>
            </div>
        @endif
    </x-pages::settings.layout>
</section>
