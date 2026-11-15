<?php

namespace App\Services\Beesha;

use App\Models\User;
use App\Services\Beesha\Contracts\AssistantProvider;
use App\Support\Roles;

/**
 * Deterministic assistant used while no approved model provider is configured.
 *
 * It does NOT generate text from a language model. It matches the question
 * against a fixed intent table, calls one authorized tool, and formats the
 * real values that come back. Consequences, stated plainly:
 *
 *   + no company data leaves the server
 *   + a number in an answer always exists in the database
 *   - it understands phrasings it has keywords for, and says so when it does not
 *
 * It is labelled "Demo" in the interface and never presented as a connected AI.
 */
class DemoAssistantProvider implements AssistantProvider
{
    public function __construct(private readonly BeeshaToolRegistry $tools) {}

    public function name(): string
    {
        return 'demo';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function answer(User $user, string $question, array $history = []): AssistantReply
    {
        $normalized = $this->normalize($question);

        // 1. Procedural questions ("bagaimana cara ...") need no data access.
        if ($procedural = $this->matchProcedural($normalized, $user)) {
            return new AssistantReply($procedural, null, $this->name());
        }

        // 2. Data questions resolve to exactly one authorized tool.
        $tool = $this->matchTool($normalized, $user);

        if ($tool === null) {
            return new AssistantReply($this->fallback($user), null, $this->name());
        }

        try {
            $data = $this->tools->call($user, $tool);
        } catch (BeeshaAuthorizationException $e) {
            return new AssistantReply(
                'Maaf, data itu berada di luar izin akses peran Anda. '
                .'Silakan hubungi atasan atau Administrator Sistem bila Anda memerlukannya.',
                null,
                $this->name(),
            );
        }

        return new AssistantReply(
            $this->format($tool, $data),
            $tool,
            $this->name(),
            $data,
        );
    }

    // -------------------------------------------------------------- matching

    private function normalize(string $text): string
    {
        return ' '.preg_replace('/\s+/', ' ', mb_strtolower(trim($text))).' ';
    }

    private function hasAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Intent table. Order matters: the most specific intents come first.
     */
    private function matchTool(string $q, User $user): ?string
    {
        $candidates = [];

        // --- operator -------------------------------------------------------
        if ($this->hasAny($q, ['target produksi saya', 'target saya', 'tugas saya', 'pekerjaan saya', 'belum saya kerjakan', 'sisa pekerjaan', 'sisa target', 'kerjakan hari ini'])) {
            $candidates[] = 'getMyAssignments';
        }

        if ($this->hasAny($q, ['laporan terakhir saya', 'laporan saya', 'status laporan', 'sudah diterima', 'sudah diperiksa belum', 'sudah diverifikasi belum'])) {
            $candidates[] = 'getMyReportStatus';
        }

        if ($this->hasAny($q, ['hasil saya', 'produksi saya', 'sudah saya catat', 'berapa yang saya', 'capaian saya'])) {
            $candidates[] = 'getMyProductionSummary';
        }

        // --- supervisor -----------------------------------------------------
        if ($this->hasAny($q, ['belum diperiksa', 'menunggu pemeriksaan', 'perlu diperiksa', 'perlu diverifikasi', 'yang harus saya periksa', 'perlu diperbaiki', 'perlu perbaikan'])) {
            $candidates[] = 'getPendingReviews';
        }

        if ($this->hasAny($q, ['produksi tim', 'hasil tim', 'tugas aktif', 'siapa yang masih', 'tim hari ini'])) {
            $candidates[] = 'getTeamProductionSummary';
        }

        // --- orders ---------------------------------------------------------
        if ($this->hasAny($q, ['pesanan', 'order', 'mitra', 'po ', 'tenggat', 'deadline', 'jatuh tempo'])) {
            $candidates[] = 'getOrderSummary';
        }

        // --- shipping -------------------------------------------------------
        if ($this->hasAny($q, ['pengiriman saya', 'kirim saya', 'tujuan pengiriman', 'tujuan berikutnya', 'surat jalan saya', 'antar hari ini'])) {
            $candidates[] = 'getMyShipments';
        }

        if ($this->hasAny($q, ['pengiriman', 'dikirim', 'kirim', 'barang keluar', 'ekspedisi'])) {
            $candidates[] = 'getShippingSummary';
        }

        // --- management -----------------------------------------------------
        if ($this->hasAny($q, ['kendala operasional', 'peringatan', 'masalah terbaru', 'perlu perhatian', 'alert'])) {
            $candidates[] = 'getOperationalAlerts';
        }

        if ($this->hasAny($q, ['dilaporkan', 'diverifikasi', 'terverifikasi', 'ringkasan perusahaan', 'kpi', 'produksi hari ini', 'ringkasan operasional', 'pesanan aktif'])) {
            $candidates[] = 'getAuthorizedOperationalKPIs';
        }

        // Fall back through the candidate list until one is permitted, so an
        // operator asking "produksi hari ini" gets their own figure rather
        // than a permission error.
        foreach ($candidates as $tool) {
            if ($this->tools->userCanCall($user, $tool)) {
                return $tool;
            }
        }

        // Nothing matched: offer the most useful tool for this role.
        if ($candidates === []) {
            return null;
        }

        return $this->defaultToolFor($user);
    }

    private function defaultToolFor(User $user): ?string
    {
        $preferred = match ($user->activeRole()?->code) {
            Roles::OPERATOR => ['getMyAssignments', 'getMyProductionSummary'],
            Roles::SUPERVISOR => ['getPendingReviews', 'getTeamProductionSummary'],
            Roles::ADMIN_ORDER => ['getOrderSummary'],
            Roles::PPIC => ['getTeamProductionSummary', 'getOrderSummary'],
            Roles::WAREHOUSE => ['getShippingSummary'],
            Roles::DRIVER => ['getMyShipments'],
            Roles::MANAGEMENT => ['getAuthorizedOperationalKPIs'],
            default => [],
        };

        foreach ($preferred as $tool) {
            if ($this->tools->userCanCall($user, $tool)) {
                return $tool;
            }
        }

        return null;
    }

    private function matchProcedural(string $q, User $user): ?string
    {
        $asksHow = $this->hasAny($q, ['bagaimana cara', 'gimana cara', 'cara ', 'langkah', 'apa yang harus saya lakukan']);

        if ($this->hasAny($q, ['salah input', 'salah catat', 'salah memasukkan', 'keliru catat', 'salah jumlah'])) {
            return "Kalau jumlah yang Anda catat ternyata salah:\n\n"
                ."1. Laporan yang sudah tersimpan tidak bisa Anda hapus sendiri — ini supaya riwayat pekerjaan tetap utuh.\n"
                ."2. Beri tahu atasan Anda agar laporan itu ditandai **Perlu Perbaikan**.\n"
                ."3. Setelah ditandai, laporan tersebut tidak lagi dihitung ke total, lalu Anda bisa mencatat ulang jumlah yang benar.\n\n"
                .'Anda bisa melihat status setiap laporan di halaman Riwayat Saya.';
        }

        if (! $asksHow) {
            return null;
        }

        if ($this->hasAny($q, ['mencatat hasil', 'catat hasil', 'input hasil', 'lapor hasil', 'melaporkan hasil', 'mencatat produksi'])) {
            return "Cara mencatat hasil produksi:\n\n"
                ."1. Buka halaman **Tugas Saya**.\n"
                ."2. Tekan tombol besar **Catat Hasil Kerja** pada pekerjaan yang Anda kerjakan.\n"
                ."3. Isi jumlah unit yang **baru** selesai pada pencatatan ini, bukan jumlah keseluruhan.\n"
                ."4. Pilih \"Ada kendala\" hanya bila memang ada masalah, lalu jelaskan singkat.\n"
                ."5. Tekan **Periksa & Simpan Hasil**, lihat ringkasannya, lalu tekan **Ya, Simpan Hasil**.\n\n"
                .'Setelah tersimpan, status laporan Anda menjadi Menunggu Pemeriksaan sampai atasan memeriksanya.';
        }

        if ($this->hasAny($q, ['menambahkan pesanan', 'menambah pesanan', 'tambah pesanan', 'buat pesanan', 'input pesanan'])) {
            return "Cara menambahkan pesanan mitra:\n\n"
                ."1. Buka halaman **Pesanan Mitra**.\n"
                ."2. Tekan tombol **Tambah Pesanan**.\n"
                ."3. Pilih mitra dan nama barang dari daftar — Anda tidak perlu mengetik ulang data yang sudah ada.\n"
                ."4. Isi Nomor PO, Jumlah Pesanan, dan Target Pengiriman.\n"
                ."5. Periksa ringkasan, lalu tekan **Simpan Pesanan**.\n\n"
                .'Pesanan yang tersimpan langsung terlihat oleh PPIC untuk direncanakan produksinya.';
        }

        if ($this->hasAny($q, ['melaporkan kendala', 'lapor kendala', 'laporkan kendala', 'kendala pengiriman'])) {
            if ($user->hasRole(Roles::DRIVER)) {
                return "Cara melaporkan kendala pengiriman:\n\n"
                    ."1. Buka halaman **Pengiriman Saya**.\n"
                    ."2. Pilih pengiriman yang bermasalah.\n"
                    ."3. Tekan **Laporkan Kendala**.\n"
                    ."4. Tuliskan apa yang terjadi, misalnya kendaraan rusak atau alamat tidak ditemukan.\n\n"
                    .'Status pengiriman akan berubah menjadi Ada Kendala dan langsung terlihat oleh petugas gudang serta pimpinan.';
            }

            return "Cara melaporkan kendala produksi:\n\n"
                ."1. Saat mencatat hasil, pilih **Ada kendala** pada kartu Kendala Pekerjaan.\n"
                ."2. Pilih jenis kendala: Mesin/Peralatan, Bahan/Material, Kualitas Produk, atau Lainnya.\n"
                ."3. Jelaskan singkat apa yang terjadi.\n\n"
                .'Kendala yang Anda tulis akan muncul di daftar kendala atasan dan di peringatan pimpinan.';
        }

        if ($this->hasAny($q, ['memverifikasi', 'verifikasi laporan', 'memeriksa laporan'])) {
            return "Cara memeriksa laporan produksi:\n\n"
                ."1. Buka halaman **Pemeriksaan**.\n"
                ."2. Baca jumlah, operator, dan kendala pada kartu laporan.\n"
                ."3. Tekan **Verifikasi** bila sudah sesuai, atau **Perlu Perbaikan** bila belum.\n"
                ."4. Bila meminta perbaikan, tuliskan alasannya agar operator tahu apa yang harus diperbaiki.\n\n"
                .'Angka Terverifikasi di dashboard hanya bertambah setelah Anda menekan Verifikasi.';
        }

        if ($this->hasAny($q, ['ganti tema', 'mode gelap', 'mode terang', 'ubah tema'])) {
            return 'Tekan ikon matahari atau bulan di sudut kanan atas layar untuk berganti antara mode terang dan mode gelap. Pilihan Anda akan diingat di perangkat ini.';
        }

        return null;
    }

    // ------------------------------------------------------------- formatting

    private function format(string $tool, array $data): string
    {
        return match ($tool) {
            'getMyAssignments' => $this->formatMyAssignments($data),
            'getMyProductionSummary' => $this->formatMyProduction($data),
            'getMyReportStatus' => $this->formatMyReports($data),
            'getPendingReviews' => $this->formatPendingReviews($data),
            'getTeamProductionSummary' => $this->formatTeamProduction($data),
            'getOrderSummary' => $this->formatOrders($data),
            'getShippingSummary' => $this->formatShipping($data),
            'getMyShipments' => $this->formatMyShipments($data),
            'getAuthorizedOperationalKPIs' => $this->formatKpis($data),
            'getOperationalAlerts' => $this->formatAlerts($data),
            default => 'Maaf, saya belum bisa menyajikan data itu.',
        };
    }

    private function n(int|float|null $value): string
    {
        return number_format((int) $value, 0, ',', '.');
    }

    private function formatMyAssignments(array $d): string
    {
        if ($d['count'] === 0) {
            return 'Saat ini Anda tidak memiliki penugasan produksi yang aktif untuk '.$d['period'].'. '
                .'Bila Anda merasa seharusnya ada pekerjaan, hubungi atasan atau PPIC.';
        }

        $lines = ['Pekerjaan Anda untuk '.$d['period'].':'];

        foreach ($d['items'] as $item) {
            $lines[] = sprintf(
                "\n**%s** (%s)\n· Target: %s %s\n· Sudah dilaporkan: %s %s\n· Sudah terverifikasi: %s %s\n· Sisa: %s %s",
                $item['produk'],
                $item['work_order'],
                $this->n($item['target']), $item['satuan'],
                $this->n($item['dilaporkan']), $item['satuan'],
                $this->n($item['terverifikasi']), $item['satuan'],
                $this->n($item['sisa']), $item['satuan'],
            );
        }

        return implode("\n", $lines);
    }

    private function formatMyProduction(array $d): string
    {
        if ($d['jumlah_laporan'] === 0) {
            return 'Anda belum mencatat hasil produksi untuk '.$d['period'].'. '
                .'Sisa target aktif Anda '.$this->n($d['sisa_aktif']).' '.$d['satuan'].'.';
        }

        return sprintf(
            "Hasil Anda untuk %s:\n\n"
            ."· Dilaporkan: **%s %s** dari %d pencatatan\n"
            ."· Sudah terverifikasi: **%s %s**\n"
            ."· Menunggu pemeriksaan: **%s %s**\n"
            ."· Sisa target aktif: %s %s\n\n"
            .'Angka terverifikasi bertambah setelah atasan memeriksa laporan Anda.',
            $d['period'],
            $this->n($d['dilaporkan_hari_ini']), $d['satuan'], $d['jumlah_laporan'],
            $this->n($d['terverifikasi_hari_ini']), $d['satuan'],
            $this->n($d['menunggu_pemeriksaan']), $d['satuan'],
            $this->n($d['sisa_aktif']), $d['satuan'],
        );
    }

    private function formatMyReports(array $d): string
    {
        if ($d['count'] === 0) {
            return 'Anda belum pernah mencatat hasil pekerjaan, jadi belum ada laporan yang bisa saya tampilkan.';
        }

        $lines = ['Laporan terakhir Anda:'];

        foreach ($d['items'] as $item) {
            $line = sprintf(
                "\n· **%s %s** — %s\n  %s · %s",
                $this->n($item['jumlah']), $item['satuan'],
                $item['status'],
                $item['produk'] ?? 'Produk',
                $item['waktu'],
            );

            if (! empty($item['catatan_pemeriksaan'])) {
                $line .= "\n  Catatan atasan: ".$item['catatan_pemeriksaan'];
            }

            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function formatPendingReviews(array $d): string
    {
        if ($d['menunggu_pemeriksaan'] === 0) {
            $extra = $d['perlu_perbaikan'] > 0
                ? sprintf(' Namun ada %d laporan berstatus Perlu Perbaikan yang menunggu operator mencatat ulang.', $d['perlu_perbaikan'])
                : '';

            return 'Tidak ada laporan yang menunggu pemeriksaan saat ini.'.$extra;
        }

        $lines = [sprintf(
            'Ada **%d laporan** menunggu pemeriksaan, total **%s pcs**:',
            $d['menunggu_pemeriksaan'],
            $this->n($d['total_unit_menunggu']),
        )];

        foreach ($d['items'] as $item) {
            $lines[] = sprintf(
                "\n· %s — **%s %s** · %s\n  %s · %s · kendala: %s",
                $item['operator'] ?? 'Operator',
                $this->n($item['jumlah']), $item['satuan'],
                $item['nomor'],
                $item['produk'] ?? 'Produk',
                $item['waktu'],
                $item['kendala'],
            );
        }

        if ($d['perlu_perbaikan'] > 0) {
            $lines[] = sprintf("\nSelain itu, %d laporan berstatus Perlu Perbaikan.", $d['perlu_perbaikan']);
        }

        return implode("\n", $lines);
    }

    private function formatTeamProduction(array $d): string
    {
        $lines = [sprintf(
            "Hasil produksi tim untuk %s:\n\n· Dilaporkan: **%s %s** dari %d laporan\n· Terverifikasi: **%s %s**\n· Menunggu pemeriksaan: **%s %s**",
            $d['period'],
            $this->n($d['dilaporkan']), $d['satuan'], $d['jumlah_laporan'],
            $this->n($d['terverifikasi']), $d['satuan'],
            $this->n($d['menunggu_pemeriksaan']), $d['satuan'],
        )];

        if ($d['penugasan_aktif'] !== []) {
            $lines[] = "\nPenugasan yang masih aktif:";

            foreach ($d['penugasan_aktif'] as $item) {
                $lines[] = sprintf(
                    '· %s — %s (%s): %s/%s %s, sisa %s',
                    $item['operator'] ?? 'Operator',
                    $item['produk'],
                    $item['work_order'],
                    $this->n($item['dilaporkan']),
                    $this->n($item['target']),
                    $item['satuan'],
                    $this->n($item['sisa']),
                );
            }
        }

        $lines[] = "\n".$d['catatan'];

        return implode("\n", $lines);
    }

    private function formatOrders(array $d): string
    {
        $lines = [sprintf(
            "Ringkasan pesanan mitra (%s):\n\n· Pesanan aktif: **%d**\n· Pesanan baru hari ini: **%d**\n· Total pesanan tercatat: %d",
            $d['period'],
            $d['pesanan_aktif'],
            $d['pesanan_baru_hari_ini'],
            $d['total_pesanan'],
        )];

        if ($d['mendekati_tenggat'] !== []) {
            $lines[] = "\nMendekati tenggat (7 hari ke depan):";

            foreach ($d['mendekati_tenggat'] as $o) {
                $lines[] = sprintf(
                    '· %s — %s · target %s%s · sisa kirim %s pcs · %s',
                    $o['nomor'],
                    $o['mitra'],
                    $o['target_kirim'],
                    $o['terlambat'] ? ' (**terlambat**)' : '',
                    $this->n($o['sisa_kirim']),
                    $o['status'],
                );
            }
        } else {
            $lines[] = "\nTidak ada pesanan yang mendekati tenggat dalam 7 hari ke depan.";
        }

        return implode("\n", $lines);
    }

    private function formatShipping(array $d): string
    {
        return sprintf(
            "Ringkasan pengiriman (%s):\n\n"
            ."· Dikirim hari ini: **%s pcs** dalam %d pengiriman\n"
            ."· Dalam perjalanan: %d\n"
            ."· Sudah tiba: %d\n"
            ."· Sudah diterima: %d\n"
            ."· Siap dikirim: %d\n"
            .'· Bermasalah: %d',
            $d['period'],
            $this->n($d['dikirim_hari_ini_unit']), $d['jumlah_pengiriman_hari_ini'],
            $d['dalam_perjalanan'],
            $d['sudah_tiba'],
            $d['sudah_diterima'],
            $d['siap_dikirim'],
            $d['bermasalah'],
        );
    }

    private function formatMyShipments(array $d): string
    {
        if ($d['count'] === 0) {
            return 'Tidak ada pengiriman aktif yang ditugaskan kepada Anda saat ini.';
        }

        $lines = [sprintf('Anda memiliki **%d pengiriman** aktif:', $d['count'])];

        foreach ($d['items'] as $s) {
            $lines[] = sprintf(
                "\n· **%s** — %s\n  Tujuan: %s\n  Jumlah: %s pcs · Status: %s%s",
                $s['nomor'],
                $s['mitra'] ?? 'Mitra',
                $s['tujuan'] ?? 'Alamat belum dicatat',
                $this->n($s['jumlah']),
                $s['status'],
                $s['tindakan_berikutnya'] !== []
                    ? "\n  Tindakan berikutnya: ".implode(', ', $s['tindakan_berikutnya'])
                    : '',
            );
        }

        return implode("\n", $lines);
    }

    private function formatKpis(array $d): string
    {
        return sprintf(
            "Ringkasan operasional %s (diperbarui %s):\n\n"
            ."· Pesanan aktif: **%d**\n"
            ."· Produksi dilaporkan: **%s pcs**\n"
            ."· Produksi terverifikasi: **%s pcs**\n"
            ."· Menunggu pemeriksaan: **%s pcs** dari %d laporan\n"
            ."· Dikirim hari ini: **%s pcs**\n"
            ."· Perlu perhatian: %d hal\n\n%s",
            $d['period'],
            $d['diperbarui'],
            $d['pesanan_aktif'],
            $this->n($d['produksi_dilaporkan']),
            $this->n($d['produksi_terverifikasi']),
            $this->n($d['menunggu_pemeriksaan_unit']), $d['menunggu_pemeriksaan_laporan'],
            $this->n($d['pengiriman_hari_ini_unit']),
            $d['perlu_perhatian'],
            $d['catatan'],
        );
    }

    private function formatAlerts(array $d): string
    {
        if ($d['count'] === 0) {
            return 'Tidak ada peringatan operasional saat ini ('.$d['period'].').';
        }

        $lines = [sprintf('Ada **%d hal** yang perlu perhatian (%s):', $d['count'], $d['period'])];

        foreach ($d['items'] as $a) {
            $mark = match ($a['tingkat']) {
                'critical' => 'Penting',
                'warning' => 'Perhatian',
                default => 'Info',
            };

            $lines[] = sprintf("\n· [%s] **%s**\n  %s", $mark, $a['judul'], $a['detail']);
        }

        return implode("\n", $lines);
    }

    private function fallback(User $user): string
    {
        $suggestions = $this->suggestionsFor($user);

        $text = "Maaf, saya belum menangkap maksud pertanyaan Anda.\n\n"
            ."Saya bisa membantu hal-hal berikut:\n";

        foreach ($suggestions as $s) {
            $text .= "\n· ".$s;
        }

        $text .= "\n\nCoba tulis pertanyaan dengan kata-kata di atas, atau tekan salah satu pertanyaan cepat di bawah.";

        return $text;
    }

    /**
     * Quick-question chips, filtered to what this user is actually allowed to
     * ask — so the suggestions can never produce a permission error.
     *
     * @return array<int, string>
     */
    public function suggestionsFor(User $user): array
    {
        $all = [
            'getMyAssignments' => 'Berapa target produksi saya hari ini?',
            'getMyProductionSummary' => 'Berapa hasil produksi saya hari ini?',
            'getMyReportStatus' => 'Apakah laporan terakhir saya sudah diterima?',
            'getPendingReviews' => 'Laporan mana yang belum diperiksa?',
            'getTeamProductionSummary' => 'Berapa hasil produksi tim hari ini?',
            'getOrderSummary' => 'Pesanan mana yang mendekati tenggat?',
            'getShippingSummary' => 'Berapa barang yang sudah dikirim?',
            'getMyShipments' => 'Pengiriman apa yang harus saya selesaikan hari ini?',
            'getAuthorizedOperationalKPIs' => 'Berapa produksi yang dilaporkan dan diverifikasi hari ini?',
            'getOperationalAlerts' => 'Apa kendala operasional terbaru?',
        ];

        $available = collect($all)
            ->filter(fn (string $q, string $tool) => $this->tools->userCanCall($user, $tool))
            ->values()
            ->all();

        // Procedural help is available to everyone.
        $available[] = match ($user->activeRole()?->code) {
            Roles::OPERATOR => 'Bagaimana cara mencatat hasil produksi?',
            Roles::SUPERVISOR => 'Bagaimana cara memverifikasi laporan?',
            Roles::ADMIN_ORDER => 'Bagaimana cara menambahkan pesanan?',
            Roles::DRIVER => 'Bagaimana cara melaporkan kendala?',
            default => 'Bagaimana cara mencatat hasil produksi?',
        };

        return array_slice($available, 0, 5);
    }
}
