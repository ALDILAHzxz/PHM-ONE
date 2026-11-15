# Desain Autentikasi

## 1. Ringkasan

| Aspek | Keadaan sekarang |
| --- | --- |
| Mekanisme | Sesi Laravel (cookie, `SESSION_DRIVER=database`) |
| Identifier | Nomor Induk Karyawan (`users.employee_number`) |
| Faktor | Kata sandi tunggal, hash bcrypt |
| Alur | Dua langkah: identifikasi → autentikasi |
| SSO perusahaan | **Belum ada.** Batas integrasi disiapkan, tidak tersambung. |
| MFA / passkey | **Belum ada.** Tidak disimulasikan. |
| Pemulihan kata sandi | **Belum ada.** Memerlukan layanan email resmi. |

**Ini bukan SSO perusahaan dan tidak pernah disebut demikian di antarmuka.**

## 2. Alur login dua langkah

Spesifikasi §6.2 meminta satu halaman login dengan satu field terlihat, lalu
"langkah autentikasi perusahaan yang aman". Implementasinya:

```
┌─ Langkah 1: identifikasi ──────────────────────────────┐
│ "Selamat Datang di PHM One"                            │
│ "Masuk untuk melihat dan mencatat pekerjaan Anda."     │
│                                                        │
│ [ Nomor Induk Karyawan        ]   ← satu field         │
│ [        Lanjutkan            ]   ← satu tombol        │
│ Tidak bisa masuk?                                      │
└────────────────────────────────────────────────────────┘
                     │ ditemukan & aktif
                     ▼
┌─ Langkah 2: autentikasi ───────────────────────────────┐
│ ← Ganti nomor karyawan                                 │
│  (BS)  Budi Santoso                                    │
│        Operator Produksi · PHM-EMP-001                 │
│                                                        │
│ [ Kata Sandi            👁 ]                            │
│ [ ] Ingat perangkat ini                                │
│ [          Masuk           ]                           │
│ Lupa kata sandi?                                       │
└────────────────────────────────────────────────────────┘
```

Menampilkan nama pada langkah 2 memberi pekerja kepastian bahwa ia masuk ke akun
yang benar — berguna di lantai produksi dengan perangkat bersama.

### Pengalihan setelah masuk

`App\Services\RoleRouting::homeUrlFor()` memakai `roles.default_route` milik peran
aktif. Tidak ada layar pemilihan dashboard. Pengguna tanpa peran yang dapat
dipakai diarahkan ke halaman Profil, bukan dibiarkan dalam loop pengalihan.

## 3. Pembatasan percobaan

`Illuminate\Support\Facades\RateLimiter`, dikunci pada
`<aksi>|<nomor karyawan>|<IP>`:

| Aksi | Batas | Jendela |
| --- | --- | --- |
| `identify` | 12 percobaan | 60 detik |
| `login` | 5 percobaan | 60 detik |

Keduanya dibersihkan setelah autentikasi berhasil. Pesan kesalahan menyebutkan
sisa waktu tunggu dalam detik.

## 4. Keterbatasan keamanan yang diketahui

Disebutkan terbuka, bukan disembunyikan:

### 4.1 Kata sandi saja bukan autentikasi perusahaan yang kuat

Spesifikasi §6.3 menyatakannya dan implementasi ini mematuhinya: **tidak ada
sistem autentikasi aman palsu yang dibangun hanya dari nomor karyawan.** Yang ada
adalah autentikasi kata sandi lokal yang jujur, ditandai sebagai mode demo, dengan
titik pasang eksplisit untuk penyedia identitas sungguhan.

### 4.2 Langkah 1 mengungkap keberadaan nomor karyawan

Menampilkan nama karyawan pada langkah 2 berarti langkah 1 memberi tahu apakah
sebuah nomor karyawan ada dan aktif (*user enumeration*).

Ini **pertukaran yang disengaja** demi UX yang diminta spesifikasi. Mitigasinya:

- pembatasan percobaan per nomor dan per IP pada langkah 1;
- nomor karyawan internal tidak dipublikasikan;
- pada deployment nyata, langkah ini digantikan penyedia OIDC sehingga
  pertukaran ini hilang.

Bila PHM memutuskan enumerasi tidak dapat diterima sebelum SSO tersedia,
perubahan yang diperlukan bersifat lokal: `LoginForm::identify()` selalu lanjut ke
langkah 2 dengan label generik, dan kesalahan digabung pada langkah 2.

### 4.3 Belum ada MFA

Tidak ada faktor kedua. Pada deployment nyata, MFA menjadi tanggung jawab
penyedia identitas.

### 4.4 Pemulihan kata sandi tidak tersedia

Halaman bantuan dan halaman Profil menyatakan dengan jelas bahwa fitur ini belum
tersedia di mode demo karena memerlukan layanan email atau identitas resmi.
**Tidak ada alur pemulihan palsu** yang dibuat agar tidak memberi kesan aman
padahal belum.

### 4.5 Passkey / biometrik tidak tersedia

Spesifikasi §10 melarang menangkap sidik jari secara langsung dan melarang
autentikasi biometrik palsu. Aplikasi ini mematuhinya: halaman Profil
menampilkan fitur tersebut sebagai belum tersedia, dengan penjelasan bahwa
implementasi yang benar memerlukan WebAuthn dengan verifikasi di sisi server.
Tidak ada kode yang berpura-pura membaca biometrik.

## 5. Login demo

Berlaku hanya saat `PHM_DEMO_MODE=true`.

Jaminan isolasi:

1. `loginAsDemo()` diawali `abort_unless(config('phm.demo_mode'), 403)`.
2. Kueri memfilter `where('is_demo', true)` — identitas nyata tidak dapat
   disentuh, bahkan bila nomornya diketahui.
3. Akun demo diberi nama fiktif dan email `@phm.example`.
4. Kata sandi demo bersama (`demo1234`) disebut terbuka di README sebagai
   kredensial demo, bukan rahasia perusahaan.
5. Panel hanya menampilkan akun demo dengan **satu** peran, sehingga daftar
   peran tetap jelas.

Diuji oleh:
- `test_demo_login_only_works_for_demo_accounts`
- `test_demo_login_is_refused_when_demo_mode_is_disabled`

## 6. Manajemen sesi

| Hal | Implementasi |
| --- | --- |
| Penyimpanan | Tabel `sessions` (`SESSION_DRIVER=database`) |
| Rotasi ID | `session()->regenerate()` setelah login berhasil (mitigasi session fixation) |
| Token CSRF | Middleware web Laravel; setiap form menyertakan `@csrf` |
| "Ingat perangkat" | `remember_token` Laravel, opsional lewat kotak centang |
| Logout | `Auth::logout()` + `session()->invalidate()` + `regenerateToken()` |
| Akun dinonaktifkan | Middleware `active` memaksa logout pada permintaan berikutnya |

Logout **tidak** menghapus record produksi. Dinyatakan di halaman Profil dan
diuji oleh `test_logout_ends_the_session_without_deleting_records`.

## 7. Batas integrasi OIDC

Disiapkan di `config/phm.php`:

```php
'oidc' => [
    'enabled' => env('OIDC_ENABLED', false),
    'issuer_url' => env('OIDC_ISSUER_URL'),
    'client_id' => env('OIDC_CLIENT_ID'),
    'client_secret' => env('OIDC_CLIENT_SECRET'),
    'redirect_uri' => env('OIDC_REDIRECT_URI'),
],
```

### Rancangan alur yang dituju

```
1. Pengguna menekan "Masuk dengan Akun PHM"
2. Redirect ke penyedia OIDC — Authorization Code Flow + PKCE
      • code_verifier dibuat, code_challenge (S256) dikirim
      • parameter state dan nonce disimpan di sesi
3. Penyedia mengautentikasi (termasuk MFA bila dikonfigurasi)
4. Callback dengan authorization code
      • verifikasi state
      • tukar code + code_verifier menjadi token
      • verifikasi tanda tangan ID token, iss, aud, exp, nonce
5. Petakan klaim subject ke users.employee_number
6. Ambil peran dari data backend yang dipercaya — BUKAN dari klaim yang dapat
   dipengaruhi klien
7. session()->regenerate(), Auth::login()
8. Pemeriksaan izin sisi server berjalan seperti sekarang (tidak berubah)
```

### Yang perlu diubah saat SSO disetujui

| Berkas | Perubahan |
| --- | --- |
| `app/Livewire/Auth/LoginForm.php` | langkah 2 digantikan redirect ke penyedia |
| rute baru | `/auth/oidc/redirect`, `/auth/oidc/callback` |
| `DemoUserSeeder` | digantikan sinkronisasi karyawan dari direktori |
| `config/phm.php` | `oidc.enabled = true`, `demo_mode = false` |

Yang **tidak** perlu diubah: tabel peran/izin, middleware, Gate, seluruh
service domain, dan semua tampilan. Batasnya memang diletakkan di satu tempat
untuk alasan ini.

## 8. Daftar periksa sebelum deployment nyata

- [ ] `PHM_DEMO_MODE=false`
- [ ] Hapus atau nonaktifkan seluruh akun `is_demo = true`
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `APP_KEY` baru dan tidak pernah di-commit
- [ ] HTTPS dipaksa; `SESSION_SECURE_COOKIE=true`
- [ ] `SESSION_SAME_SITE=lax` (atau `strict` bila tidak ada alur lintas situs)
- [ ] Penyedia OIDC dikonfigurasi dan callback URL terdaftar
- [ ] Kebijakan kata sandi dan MFA ditangani penyedia identitas
- [ ] Pemulihan kata sandi diarahkan ke layanan resmi, bukan ke aplikasi ini
- [ ] Rotasi dan retensi log audit disetujui
