<?php

use App\Models\AllowedDomain;
use App\Services\AllowedDomainService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Title('Toegestane domeinen')] class extends Component {
    #[Validate('required|string|max:253|regex:/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/')]
    public string $domain = '';

    #[Validate('nullable|string|max:255')]
    public string $note = '';

    public function save(AllowedDomainService $service): void
    {
        $this->validate();

        $service->add($this->domain, $this->note ?: null);

        $this->reset('domain', 'note');
    }

    public function delete(AllowedDomainService $service, int $id): void
    {
        $service->delete($id);
    }

    public function with(AllowedDomainService $service): array
    {
        return [
            'domains' => $service->all(),
        ];
    }
}; ?>

<div>
    <flux:heading size="xl" level="1">Toegestane domeinen</flux:heading>
    <flux:subheading class="mb-6">
        Alleen e-mailadressen van onderstaande domeinen mogen zich registreren.
    </flux:subheading>

    {{-- Domein toevoegen --}}
    <flux:card class="mb-8 max-w-lg space-y-4">
        <flux:heading size="lg">Domein toevoegen</flux:heading>

        <form wire:submit="save" class="space-y-4">
            <flux:input wire:model="domain" label="Domeinnaam" placeholder="mbouutrecht.nl" required />
            <flux:input wire:model="note" label="Omschrijving (optioneel)" placeholder="Medewerkers MBou Utrecht" />
            <flux:button type="submit" variant="primary">Toevoegen</flux:button>
        </form>
    </flux:card>

    {{-- Overzicht --}}
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">Domein</th>
                    <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">Omschrijving</th>
                    <th class="px-4 py-3 text-start font-medium text-zinc-600 dark:text-zinc-400">Toegevoegd</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($domains as $domain)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-4 py-3 font-mono font-medium">{{ $domain->domain }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $domain->note ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                            {{ $domain->created_at->format('d-m-Y') }}</td>
                        <td class="px-4 py-3 text-end">
                            <flux:button wire:click="delete({{ $domain->id }})"
                                wire:confirm="Domein '{{ $domain->domain }}' verwijderen?" variant="danger"
                                size="sm">
                                Verwijderen
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-zinc-400">
                            Nog geen domeinen toegevoegd.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
