# Kebutuhan Produk

Status setiap kebutuhan: ✅ selesai · 🟡 sebagian · ❌ belum (dengan alasan).
Kolom "Verifikasi" menyebut tes yang membuktikannya.

## 1. Autentikasi dan peran

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Satu halaman login untuk semua karyawan dan manajemen | ✅ | `AuthenticationTest::test_login_page_is_reachable_for_guests` |
| Memakai Nomor Induk Karyawan, dibedakan dari NIK Dukcapil | ✅ | teks bantu di form; `DATABASE_SCHEMA.md` §2 |
| Antarmuka awal sederhana — satu field, satu tombol | ✅ | `InterfaceContractTest` + tinjauan form |
| Langkah autentikasi perusahaan setelah identifikasi | ✅ (kata sandi lokal) | `test_correct_password_authenticates_and_redirects_to_the_role_home` |
| Akses bantuan saat gagal masuk | ✅ | halaman `/bantuan-masuk` |
| Tidak membangun autentikasi aman palsu dari nomor karyawan saja | ✅ | `AUTHENTICATION_DESIGN.md` §4 menyatakan keterbatasan terbuka |
| Login demo terpisah dan berlabel jelas | ✅ | `test_demo_login_only_works_for_demo_accounts`, `test_demo_login_is_refused_when_demo_mode_is_disabled` |
| Pengalihan per peran tanpa layar pemilihan dashboard | ✅ | `test_each_role_is_redirected_to_its_own_workspace` (8 peran) |
| Dukungan multi-peran dengan perpindahan dari menu Profil | ✅ | `test_multi_role_user_starts_in_the_primary_workspace`, `test_profile_role_switch_works_for_a_role_the_user_does_hold` |
| Tidak boleh memilih peran yang tidak dimiliki | ✅ | `test_profile_role_switch_rejects_a_role_the_user_does_not_hold` |
| Izin ditegakkan di server | ✅ | `AuthorizationMatrixTest` (23 rute × 8 peran) |
| SSO OIDC sungguhan (Authorization Code + PKCE) | ❌ | Tidak ada penyedia identitas yang diotorisasi. Batas integrasi disiapkan di `config/phm.php`; rancangan alur di `AUTHENTICATION_DESIGN.md` §7 |

## 2. Navbar global (§07, §44)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Navbar atas sticky | ✅ | `test_navbar_is_sticky_at_the_top` |
| Kiri: ikon hamburger + identitas/judul halaman | ✅ | `test_theme_and_profile_controls_are_the_last_two_navbar_controls` |
| Kanan: tombol ikon tema + tombol ikon profil | ✅ | idem |
| **Semua kontrol aksi navbar ikon saja, tanpa teks terlihat** | ✅ | `test_navbar_action_controls_show_no_visible_text_labels` — mengekstrak `<header>`, membuang semua tag/atribut, dan menegaskan hanya nama merek + judul halaman yang tersisa |
| Nama aksesibel, tooltip, dan fokus terlihat pada tombol ikon | ✅ | `test_navbar_icon_buttons_have_accessible_names` |
| Tinggi 64 px mobile / 72 px desktop | ✅ | `test_navbar_height_follows_the_specification` |
| Target klik 44–48 px, ikon 22–24 px | ✅ | `test_navbar_icon_buttons_meet_the_minimum_touch_target` |
| Tetap terlihat saat menggulir, tanpa tumpang tindih atau lompatan layout | ✅ | `sticky top-0 z-50` + `<main>` terpisah |

## 3. Navigasi hamburger (§08)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Drawer hanya menampilkan rute peran aktif | ✅ | `App\Support\Navigation::for()`; `test_drawer_navigation_only_offers_reachable_routes` |
| Lebar ~300–320 px desktop, maks 88 % viewport mobile | ✅ | `w-[88vw] max-w-[320px]` |
| Ikon **dan** label teks terbaca di setiap entri | ✅ | `test_drawer_entries_keep_readable_text_labels` |
| Tinggi baris minimum 52–56 px | ✅ | `test_drawer_rows_meet_the_minimum_height` |
| Indikator menu aktif | ✅ | `phm-drawer-link-active` + `aria-current="page"` |
| Tutup lewat tombol, Escape, klik backdrop, dan navigasi | ✅ | `test_drawer_can_be_closed_by_escape_and_backdrop` |
| Pengelolaan fokus keyboard | ✅ | `x-trap.noscroll` (plugin focus Alpine terbundel Livewire) |

## 4. Tema (§09)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Pengalih terang/gelap ikon saja (matahari/bulan) | ✅ | `components/theme-toggle.blade.php` |
| Label aksesibel menjelaskan aksi, tidak tampil permanen | ✅ | `test_navbar_icon_buttons_have_accessible_names` |
| Tema diingat antar navigasi dan muat ulang | ✅ | `test_theme_choice_is_persisted` |
| Tidak ada kedip tema saat render awal | ✅ | `test_theme_is_applied_before_first_paint_to_avoid_a_flash` |
| Palet korporat diterapkan | ✅ | `test_the_corporate_palette_values_are_used` |
| Semua layar mengikuti tema terpilih | ✅ | token semantik; `test_both_themes_define_every_semantic_colour` |
| Teks dan ikon mode gelap tidak hilang di latar gelap | ✅ | rasio kontras di `DESIGN_SYSTEM.md` §1 |

## 5. Profil (§10)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Identitas: nama, NIK, departemen, jabatan, shift, status akun | ✅ | `test_profile_is_reachable_and_shows_the_employee_number` |
| Informasi akun: email, kontak, lokasi kerja, terakhir digunakan | ✅ | halaman Profil |
| Preferensi: tema, bahasa, aksesibilitas | ✅ | halaman Profil |
| Ubah kata sandi dengan verifikasi kata sandi lama | ✅ | `ProfileController::updatePassword` |
| Lupa kata sandi memakai layanan aman sungguhan | ❌ | Dinyatakan **belum tersedia** di UI. Tidak ada alur palsu yang dibuat |
| Passkey/biometrik lewat WebAuthn | ❌ | Dinyatakan **belum tersedia** di UI. Tidak ada sidik jari yang direkam, tidak ada simulasi biometrik |
| Status keamanan akun | ✅ | halaman Profil, menyebut mode demo secara eksplisit |
| Logout dengan konfirmasi, tanpa menghapus record produksi | ✅ | `test_logout_ends_the_session_without_deleting_records` |

## 6. Beesha AI mengapung (§11, §12, §32)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Dapat diakses di **setiap** halaman terautentikasi | ✅ | `test_the_floating_button_is_present_on_every_authenticated_workspace` (19 halaman × 7 peran) |
| Pemicu utama **bukan** di navbar | ✅ | satu FAB mengapung; navbar hanya punya 3 kontrol |
| Posisi tetap kanan bawah, dengan safe-area | ✅ | `test_the_floating_button_is_fixed_to_the_bottom_right` |
| Z-index di atas konten, di bawah overlay drawer/modal | ✅ | `test_the_floating_button_sits_below_the_drawer_overlay` |
| Tidak menghalangi tombol simpan, pesan validasi, atau aksi bawah | ✅ | `test_page_content_leaves_room_so_the_fab_covers_nothing` |
| Tersembunyi saat panel chat terbuka | ✅ | `x-show="!$wire.open"` |
| Drawer kanan 440–480 px desktop, nyaris penuh layar di mobile | ✅ | `md:w-[28rem] lg:w-[30rem]` |
| Saran pertanyaan menutup setelah pertanyaan dikirim | ✅ | `test_suggestions_collapse_after_the_first_question` |
| Riwayat percakapan dipertahankan | ✅ | `test_asking_a_question_stores_both_messages`; percakapan per sesi |
| Pengguna belum masuk tidak dapat mengakses | ✅ | `test_the_floating_button_is_not_rendered_for_unauthenticated_visitors` |
| Pengguna tanpa izin tidak mendapat tombol | ✅ | `test_a_user_without_the_beesha_permission_does_not_get_the_button` |
| Tool terotorisasi, bukan akses database langsung | ✅ | `test_tool_catalogue_is_filtered_per_role`, `test_an_operator_cannot_reach_management_kpis_through_the_assistant` |
| Tidak ada SQL sembarang | ✅ | metode PHP dengan kueri tetap; `BEESHA_AI_ARCHITECTURE.md` §3 |
| AI tidak dapat menyetujui produksi atau mengubah pengiriman | ✅ | `test_no_tool_in_the_catalogue_performs_a_write` |
| Membedakan data dilaporkan vs terverifikasi | ✅ | `test_answers_distinguish_reported_from_verified` |
| Menyebut periode pelaporan | ✅ | `test_answers_state_the_reporting_period` |
| Tidak mengarang angka | ✅ | `test_an_unrecognised_question_says_so_instead_of_inventing_an_answer` |
| Asisten demo berlabel jelas bila tak ada penyedia disetujui | ✅ | `test_the_demo_provider_is_used_and_labelled_when_no_api_key_exists` |
| Antarmuka penyedia AI yang dapat dikonfigurasi | ✅ | kontrak `AssistantProvider`; `BeeshaServiceProvider` |
| Audit aktivitas tool | ✅ | `test_tool_access_is_audited` |

## 7. Operator (§13, §14, §15, §16)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| "Tugas Saya" sebagai layar paling sederhana | ✅ | tanpa grafik; `test_only_clickable_cards_get_the_hover_lift` memastikan tidak ada kartu interaktif |
| Kartu kerja: produk, work order, target, dilaporkan, sisa, status | ✅ | `test_operator_sees_their_assignment_on_tugas_saya` |
| Tombol besar "Catat Hasil Kerja" tanpa kontrol pesaing | ✅ | tinjauan markup + `test_the_production_form_has_one_dominant_submit_action` |
| Form satu kolom terpusat, maks 680–720 px | ✅ | `test_the_production_form_is_a_single_centred_column` |
| Kartu bertumpuk, tanpa sidebar/tabel/grafik | ✅ | tinjauan markup |
| Kartu 1 informasi pekerjaan baca-saja, tanpa pengetikan ulang | ✅ | `test_the_production_form_shows_known_values_as_read_only_context` (tepat 1 input angka) |
| Kartu 2 satu input numerik besar dengan satuan | ✅ | idem |
| Penjelasan "tambahan unit, bukan keseluruhan" | ✅ | teks bantu di bawah input |
| Kartu 3 kendala kondisional | ✅ | `test_the_production_form_hides_issue_fields_until_an_issue_is_reported` |
| Kartu 4 keterangan tambahan opsional | ✅ | tidak wajib |
| Validasi lengkap (positif, bukan nol/negatif/desimal, batas) | ✅ | 7 tes validasi di `ProductionReportingTest` |
| Pesan kesalahan ramah pengguna | ✅ | `messages()` di komponen; tidak ada istilah pengembang |
| Form tidak valid mempertahankan nilai yang sudah diisi | ✅ | `test_invalid_submission_preserves_entered_values` |
| Langkah konfirmasi sebelum menyimpan | ✅ | `test_confirmation_step_does_not_save_anything` |
| Backend: autentikasi → izin → validasi → idempotensi → transaksi → audit → status → commit | ✅ | `ProductionReportService::submit()`; `test_submission_writes_an_audit_event` |
| Pencegahan kiriman ganda | ✅ | `test_reusing_an_idempotency_key_does_not_create_a_second_report`, `test_calling_save_twice_from_the_form_records_one_report` |
| Sukses hanya setelah data tersimpan | ✅ | `step = 'success'` ditetapkan setelah service kembali |
| Aksi lanjutan: kembali / catat lagi / lihat riwayat | ✅ | halaman sukses |
| Melebihi sisa target: peringatan + aturan terdokumentasi, tanpa pemotongan diam-diam | ✅ | `test_quantity_above_the_remaining_target_is_rejected_not_truncated`, `test_a_user_with_the_exception_permission_may_exceed_the_remaining_target` |
| Penugasan tertutup tidak menerima laporan | ✅ | `test_a_closed_assignment_cannot_accept_new_reports` |

## 8. Supervisor (§18)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| "Pemeriksaan Hari Ini" dengan menunggu / perlu perbaikan / baru diverifikasi | ✅ | `test_supervisor_sees_the_pending_queue` |
| Kartu laporan: karyawan, work order, produk, jumlah, waktu, status kendala | ✅ | tinjauan markup |
| Aksi Verifikasi dan Perlu Perbaikan | ✅ | `test_verifying_a_report_moves_the_verified_total` |
| Harus memiliki peran yang tepat | ✅ | `test_an_operator_cannot_verify_through_the_service` |
| Setiap pemeriksaan membuat kejadian audit | ✅ | `test_verification_creates_a_review_record_and_audit_event` |
| Laporan perlu perbaikan tidak hilang diam-diam dan tidak menjadi terverifikasi | ✅ | `test_a_corrected_report_is_excluded_from_totals_but_still_exists` |
| Riwayat kiriman asli dipertahankan | ✅ | pemeriksaan adalah record terpisah; laporan tidak diubah |
| Total terverifikasi naik hanya setelah persetujuan di-commit | ✅ | `MetricsConsistencyTest::test_verifying_never_double_counts_a_quantity` |
| Tidak boleh memeriksa laporan sendiri | ✅ | `test_nobody_can_verify_their_own_report` |

## 9. Pesanan (§19)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Daftar "Pesanan Mitra": mitra, produk, jumlah, target kirim, status | ✅ | `test_order_list_is_visible_to_the_order_admin` |
| Tombol utama "Tambah Pesanan" | ✅ | idem |
| Lima field wajib | ✅ | `test_all_required_order_fields_are_validated` |
| Nilai yang sudah diketahui terisi otomatis | ✅ | detail mitra dan satuan produk muncul sendiri saat dipilih |
| Validasi, konfirmasi, simpan, tampilkan pesanan baru | ✅ | `test_an_order_is_created_and_becomes_available_to_planning` |
| Tersedia bagi alur perencanaan dan produksi | ✅ | idem — tampil di halaman Rencana Produksi |
| Pencarian dan filter | ✅ | `test_orders_can_be_searched_by_partner_name`, `…by_po_number`, `…filtered_by_status` |
| Pengubahan dan pembatalan pesanan | ❌ | Belum diimplementasikan; izin `order.update` ada tetapi belum punya antarmuka |

## 10. PPIC (§20)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Ruang kerja perencanaan yang menghubungkan pesanan dan tugas produksi | ✅ | `test_ppic_sees_the_planning_overview` |
| Work order dari pesanan, dengan produk terisi otomatis | ✅ | `test_a_work_order_can_be_created_from_an_order`, `test_selecting_an_order_auto_fills_the_product_and_remaining_quantity` |
| Penugasan: work order, produk, target, karyawan, stasiun, tahap, tanggal, status | ✅ | `test_an_assignment_can_be_created_through_the_livewire_form` |
| Tahap produksi dapat dikonfigurasi, bukan hardcode | ✅ | `test_production_stages_are_configurable_data_not_hardcoded` |
| Hanya peran perencanaan/supervisor yang berwenang | ✅ | `test_an_operator_cannot_create_assignments`, `test_the_planning_service_refuses_an_unauthorized_caller` |
| Operator hanya mengirim ke penugasan miliknya | ✅ | `test_the_service_refuses_an_assignment_belonging_to_another_operator` |
| Work order tidak melebihi jumlah pesanan | ✅ | `test_work_orders_cannot_exceed_the_ordered_quantity` |
| Penugasan tidak melebihi sisa work order | ✅ | `test_an_assignment_cannot_exceed_the_unassigned_work_order_quantity` |

## 11. Pengiriman dan driver (§21, §22)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| "Catat Pengiriman" dengan konteks pesanan | ✅ | `test_warehouse_sees_the_shipping_workspace` |
| Jumlah siap, sudah dikirim, sisa | ✅ | halaman Pengiriman + form |
| Field wajib dan opsional sesuai spesifikasi | ✅ | `ShipmentForm` |
| Validasi integer positif, referensi pesanan benar, status valid | ✅ | `test_shipment_quantity_is_validated` |
| Hindari pengiriman melebihi jumlah tersedia | ✅ | `test_shipping_more_than_the_remaining_order_quantity_is_refused`, `test_shipping_more_than_the_verified_stock_is_refused` |
| Cegah record ganda | ✅ | `test_a_duplicate_shipment_submission_creates_one_record` |
| Perbarui riwayat, pemenuhan pesanan, dashboard, peringatan | ✅ | `test_recording_a_shipment_updates_the_order_fulfilment_status` |
| Ruang kerja driver mobile-first | ✅ | `max-w-[42rem]`, kartu bertumpuk, tombol selebar layar |
| Aksi status: mulai, tiba, diterima, laporkan kendala | ✅ | `test_a_full_delivery_journey_is_recorded` |
| Hanya transisi yang valid untuk status saat ini | ✅ | `test_an_invalid_status_transition_is_refused`, `test_a_received_shipment_cannot_move_again` |
| Tidak ada status kontradiktif secara diam-diam | ✅ | idem — `ShipmentStatus::TRANSITIONS` ditegakkan di service |
| Konfirmasi penerima yang wajar | ✅ | `test_marking_goods_received_requires_a_recipient_name` |
| Tidak mengumpulkan data pribadi berlebihan atau pelacakan lokasi kontinu | ✅ | hanya nama penerima; tidak ada geolokasi |
| Driver hanya melihat pengirimannya sendiri | ✅ | `test_driver_sees_only_their_own_deliveries`, `test_driver_history_is_scoped_to_the_signed_in_driver` |
| Driver tidak dapat mengubah pengiriman orang lain | ✅ | `test_a_driver_cannot_move_a_shipment_assigned_to_someone_else` |

## 12. Dashboard manajemen (§23, §24)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Tepat empat KPI utama di bagian pembuka | ✅ | `dashboard/index`: Pesanan Aktif, Produksi Dilaporkan, Produksi Terverifikasi, Perlu Perhatian |
| Bagian operasional: progres produksi, kinerja pengiriman, tenggat, menunggu verifikasi, kendala, aktivitas terbaru | ✅ | `CompanyOverview` |
| Menampilkan periode pelaporan dan waktu pembaruan | ✅ | `test_the_dashboard_states_its_freshness` |
| Membedakan jumlah dilaporkan dan terverifikasi | ✅ | `test_the_dashboard_shows_reported_and_verified_as_separate_figures` |
| Tidak menyiratkan data terverifikasi hanya karena muncul di dashboard | ✅ | catatan transparansi data menyebut jumlah yang belum diperiksa |
| Drilldown pada kartu yang relevan saja | ✅ | hanya `<x-stat-card>` dengan `href` yang interaktif |
| Database bersama, bukan state browser | ✅ | seluruh angka dari `OperationsMetrics` |
| Pembaruan tanpa memasukkan ulang informasi | ✅ | `test_two_sessions_see_the_same_server_state` |
| Tidak ada kejadian ganda yang mempengaruhi total | ✅ | `test_verifying_never_double_counts_a_quantity` |
| Otorisasi sisi server pada tiap pembaruan | ✅ | setiap polling merender ulang komponen di belakang middleware |
| Timestamp kesegaran data | ✅ | `<x-page-header :updated-at>` |
| Pemisahan nilai menunggu dan terverifikasi | ✅ | `MetricsConsistencyTest` (16 tes) |
| Push real-time berbasis WebSocket | ❌ | Memakai polling. Dinyatakan terbuka di UI dan di `QA_REPORT.md` §3 |

## 13. Perhitungan progres (§17)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Nilai `targetQty`, `reportedQty`, `verifiedQty`, `newQty`, `remainingQty` | ✅ | `ProductionAssignment` |
| `remainingQty = max(0, target − reported)` | ✅ | `test_remaining_never_goes_negative` |
| Persentase hanya bila target > 0 | ✅ | `test_percentages_are_only_computed_when_the_target_is_positive` |
| Definisi terdokumentasi untuk laporan aktif/diperbaiki/dibatalkan | ✅ | `App\Support\ReportStatus`; `DATABASE_SCHEMA.md` §3 |
| Jumlah tidak dihitung dua kali | ✅ | `test_verifying_never_double_counts_a_quantity`, `test_corrected_and_voided_reports_are_excluded_from_reported` |
| Laporan menunggu tidak pernah ditampilkan sebagai terverifikasi | ✅ | batang progres dua lapis + angka terpisah di semua layar |

## 14. Administrasi sistem (§04)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Kelola Akun | 🟡 baca-saja | Pembuatan/penghapusan memerlukan integrasi identitas; dinyatakan di halaman |
| Kelola Peran | 🟡 baca-saja | Matriks ditampilkan; perubahan lewat seeder agar tercatat di kontrol versi |
| Audit Aktivitas | ✅ | `system.audit` dengan filter per aksi |
| Pengaturan / Status Sistem | ✅ | `system.status`, termasuk daftar batasan yang diketahui |

## 15. Kinerja dan pengakuan karyawan (§31)

| Kebutuhan | Status | Keterangan |
| --- | --- | --- |
| Modul progres pribadi | ✅ | "Hasil Saya Hari Ini" dan "Riwayat Saya" |
| Peringkat publik **bukan** antarmuka utama operator | ✅ | tidak ada peringkat sama sekali di layar operator |
| Leaderboard karyawan | ❌ | Memerlukan definisi penilaian yang disetujui lebih dulu (§31). Tidak dibangun agar tidak menilai peran dan tugas yang tidak sebanding |
| Tidak memaparkan informasi HR atau penggajian | ✅ | tidak ada field semacam itu di skema |

## 16. Aksesibilitas (§33) dan bahasa (§34)

| Kebutuhan | Status | Verifikasi |
| --- | --- | --- |
| Kontras teks dan non-teks | ✅ | `DESIGN_SYSTEM.md` §1; token diuji ada |
| Fokus terlihat | ✅ | `test_focus_is_always_visible` |
| Navigasi keyboard, label jelas, struktur heading logis | ✅ | elemen semantik asli; `test_a_skip_link_is_the_first_focusable_element` |
| Kesalahan yang dapat dipahami dan dipulihkan | ✅ | `role="alert"` + `aria-invalid` + pesan yang menyebut tindakan |
| Target sentuh memadai | ✅ | `test_inputs_and_buttons_meet_the_specified_sizes` |
| Dukungan reduced-motion | ✅ | `test_reduced_motion_is_respected` |
| Aksesibilitas dialog | ✅ | `role="dialog"` + `aria-modal` + `x-trap` pada drawer dan panel chat |
| Bahasa Indonesia sehari-hari, bukan istilah ERP | ✅ | tabel istilah di `DESIGN_SYSTEM.md` §10 |
| Tidak menstereotipkan pengguna berdasarkan usia | ✅ | tidak ada "mode sederhana" terpisah; satu antarmuka yang jelas untuk semua |

## 17. Definition of Done (§42)

| Kriteria | Status |
| --- | --- |
| Aplikasi berjalan | ✅ |
| Database terkonfigurasi | ✅ |
| Data demo sintetis tersedia | ✅ |
| Alur login berfungsi | ✅ |
| Routing per peran berfungsi | ✅ |
| Izin peran ditegakkan | ✅ |
| Navbar memakai kontrol ikon saja | ✅ |
| Ikon tema di kanan atas | ✅ |
| Ikon profil di samping ikon tema | ✅ |
| Pilihan tema bertahan | ✅ |
| Profil berfungsi | ✅ |
| FAB Beesha terlihat di semua rute terautentikasi | ✅ |
| Beesha buka/tutup dengan benar | ✅ |
| Chat menjawab pertanyaan yang diizinkan dari sumber data yang tepat | ✅ |
| Operator melihat pekerjaan yang ditugaskan | ✅ |
| Form produksi mengikuti tata letak Google Forms satu kolom | ✅ |
| Validasi produksi berfungsi | ✅ |
| Pengiriman produksi berfungsi | ✅ |
| Kiriman ganda dicegah | ✅ |
| Progres dilaporkan diperbarui dengan benar | ✅ |
| Supervisor dapat memverifikasi | ✅ |
| Progres terverifikasi diperbarui dengan benar | ✅ |
| Manajemen dapat membedakan dilaporkan dan terverifikasi | ✅ |
| Admin dapat mencatat pesanan mitra | ✅ |
| Pengguna berwenang dapat mengelola penugasan | ✅ |
| Pencatatan pengiriman berfungsi | ✅ |
| Alur status driver berfungsi | ✅ |
| Pembaruan lintas sesi berfungsi **atau batasannya dilaporkan** | ✅ dilaporkan terbuka di `QA_REPORT.md` §3 |
| Layout mobile dapat digunakan | ✅ |
| Tema terang dan gelap terbaca | ✅ |
| Tes telah dijalankan | ✅ 199 lulus |
| Build berhasil | ✅ |
| Dokumentasi mencerminkan fungsionalitas nyata | ✅ |
| Tidak ada bug pemblokir | ✅ |
| Semua batasan keamanan dan integrasi diungkapkan | ✅ |
