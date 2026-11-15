<?php

namespace App\Livewire;

use App\Models\BeeshaMessage;
use App\Services\Beesha\BeeshaService;
use App\Support\Permissions;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The global floating Beesha button and its chat panel.
 *
 * Rendered once, inside the shared authenticated layout, so it is present on
 * every authenticated route and survives navigation without re-mounting its
 * own conversation.
 */
class BeeshaAssistant extends Component
{
    public bool $open = false;

    public string $question = '';

    public string $conversationId = '';

    /** Suggestions collapse once the conversation starts, to free up space. */
    public bool $showSuggestions = true;

    public function mount(): void
    {
        abort_unless(auth()->user()->hasPermission(Permissions::BEESHA_USE), 403);

        // One conversation per browser session, so history survives page moves.
        $this->conversationId = session()->get('phm.beesha_conversation')
            ?? tap((string) Str::uuid(), fn (string $id) => session(['phm.beesha_conversation' => $id]));

        $this->showSuggestions = $this->messages->isEmpty();
    }

    /** @return Collection<int, BeeshaMessage> */
    #[Computed]
    public function messages(): Collection
    {
        return app(BeeshaService::class)->history(auth()->user(), $this->conversationId);
    }

    /** @return array<int, string> */
    #[Computed]
    public function suggestions(): array
    {
        return app(BeeshaService::class)->suggestionsFor(auth()->user());
    }

    #[Computed]
    public function isDemoAssistant(): bool
    {
        return app(BeeshaService::class)->isDemoProvider();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function askSuggestion(string $question): void
    {
        $this->question = $question;
        $this->send();
    }

    public function send(): void
    {
        $this->validate(
            ['question' => ['required', 'string', 'min:2', 'max:500']],
            [
                'question.required' => 'Tuliskan pertanyaan Anda.',
                'question.min' => 'Pertanyaan terlalu pendek.',
                'question.max' => 'Pertanyaan terlalu panjang (maksimal 500 karakter).',
            ],
        );

        app(BeeshaService::class)->ask(auth()->user(), $this->conversationId, $this->question);

        $this->question = '';
        $this->showSuggestions = false;
        unset($this->messages);

        $this->dispatch('beesha-scroll-bottom');
    }

    public function clearConversation(): void
    {
        app(BeeshaService::class)->clearHistory(auth()->user(), $this->conversationId);

        unset($this->messages);
        $this->showSuggestions = true;
    }

    public function render()
    {
        return view('livewire.beesha-assistant');
    }
}
