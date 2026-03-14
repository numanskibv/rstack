<?php

use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Gebruikers')] class extends Component {
    public function toggleAdmin(int $id): void
    {
        $user = User::findOrFail($id);

        // Prevent removing your own admin rights
        if ($user->id === auth()->id()) {
            return;
        }

        $user->update(['is_admin' => !$user->is_admin]);
    }

    public function with(): array
    {
        $users = User::query()
            ->withCount(['servers', 'projects', 'projects as running_projects_count' => fn($q) => $q->where('status', 'running'), 'projects as failed_projects_count' => fn($q) => $q->where('status', 'failed')])
            ->latest()
            ->get();

        return compact('users');
    }
}; ?>

<div>
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Gebruikers</flux:heading>
            <flux:subheading>Beheer gebruikers en bekijk hun platform-gebruik.</flux:subheading>
        </div>
        <flux:badge color="zinc">{{ $users->count() }} {{ Str::plural('gebruiker', $users->count()) }}</flux:badge>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">Gebruiker</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">2FA</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Rol</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Servers</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Projects</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Running</th>
                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-400">Failed</th>
                    <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">Lid sinds</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($users as $user)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">

                        {{-- Naam + e-mail --}}
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs font-bold uppercase text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <div class="font-medium">{{ $user->name }}</div>
                                    <div class="text-xs text-zinc-400">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- 2FA --}}
                        <td class="px-4 py-3 text-center">
                            @if ($user->hasEnabledTwoFactorAuthentication())
                                <flux:badge color="green" size="sm">Aan</flux:badge>
                            @else
                                <flux:badge color="red" size="sm">Uit</flux:badge>
                            @endif
                        </td>

                        {{-- Rol --}}
                        <td class="px-4 py-3 text-center">
                            @if ($user->is_admin)
                                <flux:badge color="yellow" size="sm">Admin</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Gebruiker</flux:badge>
                            @endif
                        </td>

                        {{-- Servers --}}
                        <td class="px-4 py-3 text-center font-mono">
                            {{ $user->servers_count }}
                        </td>

                        {{-- Projects (totaal) --}}
                        <td class="px-4 py-3 text-center font-mono">
                            {{ $user->projects_count }}
                        </td>

                        {{-- Running --}}
                        <td class="px-4 py-3 text-center">
                            @if ($user->running_projects_count > 0)
                                <span class="font-mono font-medium text-green-600 dark:text-green-400">
                                    {{ $user->running_projects_count }}
                                </span>
                            @else
                                <span class="font-mono text-zinc-400">0</span>
                            @endif
                        </td>

                        {{-- Failed --}}
                        <td class="px-4 py-3 text-center">
                            @if ($user->failed_projects_count > 0)
                                <span class="font-mono font-medium text-red-600 dark:text-red-400">
                                    {{ $user->failed_projects_count }}
                                </span>
                            @else
                                <span class="font-mono text-zinc-400">0</span>
                            @endif
                        </td>

                        {{-- Lid sinds --}}
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                            <span title="{{ $user->created_at->format('d-m-Y H:i') }}">
                                {{ $user->created_at->diffForHumans() }}
                            </span>
                        </td>

                        {{-- Acties --}}
                        <td class="px-4 py-3 text-end">
                            @if ($user->id !== auth()->id())
                                <flux:button wire:click="toggleAdmin({{ $user->id }})"
                                    wire:confirm="{{ $user->is_admin ? 'Admin-rechten intrekken?' : 'Admin-rechten toekennen?' }}"
                                    size="sm" variant="{{ $user->is_admin ? 'danger' : 'filled' }}">
                                    {{ $user->is_admin ? 'Admin intrekken' : 'Admin maken' }}
                                </flux:button>
                            @else
                                <span class="text-xs text-zinc-400 italic">Jij</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
