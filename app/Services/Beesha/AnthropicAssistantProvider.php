<?php

namespace App\Services\Beesha;

use App\Models\User;
use App\Services\Beesha\Contracts\AssistantProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Server-side model provider (opt-in).
 *
 * IMPORTANT — organizational approval required. Enabling this sends the user's
 * question plus the RESULT of authorized tool calls to an external model
 * provider. It stays disabled by default (BEESHA_PROVIDER=demo) and must not be
 * switched on for PHM data without explicit written approval.
 * See docs/BEESHA_AI_ARCHITECTURE.md.
 *
 * Safety properties preserved here:
 *   - the model never receives database credentials or raw table access
 *   - it can only request tools this user is permitted to call
 *   - every tool result is produced by BeeshaToolRegistry, with its own checks
 *   - the model cannot approve production or modify shipments: no write tools
 *     are exposed at all
 */
class AnthropicAssistantProvider implements AssistantProvider
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    private const MAX_TOOL_ROUNDS = 4;

    public function __construct(
        private readonly BeeshaToolRegistry $tools,
        private readonly DemoAssistantProvider $fallback,
    ) {}

    public function name(): string
    {
        return 'anthropic';
    }

    public function isAvailable(): bool
    {
        return filled(config('phm.beesha.api_key'));
    }

    public function answer(User $user, string $question, array $history = []): AssistantReply
    {
        if (! $this->isAvailable()) {
            // Never pretend: fall back to the deterministic assistant and say so.
            $reply = $this->fallback->answer($user, $question, $history);

            return new AssistantReply(
                $reply->text,
                $reply->tool,
                'demo',
                $reply->dataContext,
            );
        }

        try {
            return $this->converse($user, $question, $history);
        } catch (Throwable $e) {
            Log::warning('Beesha: panggilan provider gagal, kembali ke asisten demo.', [
                'error' => $e->getMessage(),
            ]);

            $reply = $this->fallback->answer($user, $question, $history);

            return new AssistantReply(
                $reply->text."\n\n_(Layanan AI sedang tidak dapat dihubungi, jawaban ini disusun langsung dari database.)_",
                $reply->tool,
                'demo',
                $reply->dataContext,
            );
        }
    }

    private function converse(User $user, string $question, array $history): AssistantReply
    {
        $messages = [];

        foreach ($history as $turn) {
            $messages[] = [
                'role' => $turn['author'] === 'assistant' ? 'assistant' : 'user',
                'content' => $turn['content'],
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        $toolsUsed = [];
        $collectedData = [];

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $response = Http::withHeaders([
                'x-api-key' => config('phm.beesha.api_key'),
                'anthropic-version' => self::API_VERSION,
                'content-type' => 'application/json',
            ])
                ->timeout(30)
                ->post(self::API_URL, [
                    'model' => config('phm.beesha.model'),
                    'max_tokens' => 1200,
                    'system' => $this->systemPrompt($user),
                    'tools' => $this->toolSchemas($user),
                    'messages' => $messages,
                ])
                ->throw()
                ->json();

            $content = $response['content'] ?? [];

            if (($response['stop_reason'] ?? null) !== 'tool_use') {
                return new AssistantReply(
                    $this->extractText($content) ?: 'Maaf, saya belum bisa menjawab pertanyaan itu.',
                    $toolsUsed === [] ? null : implode(',', $toolsUsed),
                    $this->name(),
                    $collectedData === [] ? null : $collectedData,
                );
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $results = [];

            foreach ($content as $block) {
                if (($block['type'] ?? null) !== 'tool_use') {
                    continue;
                }

                $toolName = $block['name'];

                try {
                    // Authorization is re-checked inside the registry: a model
                    // asking for a tool this user lacks gets a refusal, not data.
                    $data = $this->tools->call($user, $toolName, $block['input'] ?? []);
                    $toolsUsed[] = $toolName;
                    $collectedData[$toolName] = $data;

                    $results[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ];
                } catch (BeeshaAuthorizationException $e) {
                    $results[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'is_error' => true,
                        'content' => 'Pengguna tidak memiliki izin untuk data ini. Jangan menebak nilainya.',
                    ];
                }
            }

            $messages[] = ['role' => 'user', 'content' => $results];
        }

        // Tool loop exhausted — do not invent a conclusion.
        return new AssistantReply(
            'Maaf, saya memerlukan terlalu banyak langkah untuk menjawab itu. Coba pertanyaan yang lebih spesifik.',
            $toolsUsed === [] ? null : implode(',', $toolsUsed),
            $this->name(),
            $collectedData === [] ? null : $collectedData,
        );
    }

    private function extractText(array $content): string
    {
        return trim(implode("\n", array_map(
            fn (array $block) => $block['text'] ?? '',
            array_filter($content, fn (array $b) => ($b['type'] ?? null) === 'text')
        )));
    }

    private function systemPrompt(User $user): string
    {
        $role = $user->activeRole()?->name ?? 'Karyawan';

        return <<<PROMPT
        Anda adalah Beesha, asisten operasional PT Putra Hatim Mandiri di aplikasi PHM One.

        Pengguna saat ini: {$user->full_name} ({$user->employee_number}), peran: {$role}.

        Aturan yang wajib Anda patuhi:
        1. Jawab dalam Bahasa Indonesia yang sederhana dan singkat.
        2. SELALU gunakan tool untuk mengambil angka. Jangan pernah mengarang angka,
           nama, nomor pesanan, atau tanggal.
        3. Bila tool menolak karena izin, katakan terus terang bahwa data itu di luar
           akses pengguna. Jangan menebak.
        4. Bedakan dengan jelas antara "dilaporkan" (termasuk yang belum diperiksa)
           dan "terverifikasi" (sudah disetujui supervisor). Jangan menyebut data
           yang belum diperiksa sebagai hasil final.
        5. Selalu sebutkan periode data (misalnya "hari ini, 15 November 2026") dan
           satuan (pcs).
        6. Anda TIDAK dapat menyetujui laporan produksi, mengubah pengiriman, atau
           menulis data apa pun. Bila diminta, arahkan pengguna ke menu yang tepat.
        7. Bila informasi tidak tersedia, katakan tidak tersedia.
        PROMPT;
    }

    /**
     * Tool schemas, filtered to this user's permissions. The model is never
     * even told about tools it cannot use.
     *
     * @return array<int, array<string, mixed>>
     */
    private function toolSchemas(User $user): array
    {
        $catalogue = $this->tools->catalogue();

        return array_values(array_map(
            fn (string $name) => [
                'name' => $name,
                'description' => $catalogue[$name]['description'],
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass,
                ],
            ],
            $this->tools->availableTo($user)
        ));
    }
}
