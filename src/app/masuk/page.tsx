'use client';

import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { DEMO_CREDENTIALS } from '../../data/mockData';
import {
  User,
  Lock,
  Eye,
  EyeOff,
  AlertCircle,
  Check,
  ChevronDown,
  ChevronUp,
} from 'lucide-react';

export default function LoginPage() {
  const { login } = useAuth();
  const [nik, setNik] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [remember, setRemember] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [showDemoGuide, setShowDemoGuide] = useState(true);
  const [filledNik, setFilledNik] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!nik.trim()) {
      setError('Masukkan Nomor Induk Karyawan Anda.');
      return;
    }
    if (!password.trim()) {
      setError('Masukkan kata sandi Anda.');
      return;
    }

    setIsLoading(true);
    try {
      const res = await login(nik, password);
      if (!res.success) {
        setError(res.message || 'NIK atau kata sandi tidak cocok. Silakan periksa kembali.');
      }
    } catch {
      setError('Terjadi kesalahan pada sistem. Silakan coba lagi.');
    } finally {
      setIsLoading(false);
    }
  };

  const applyCredential = (credNik: string, credPass: string) => {
    setNik(credNik);
    setPassword(credPass);
    setError(null);
    setFilledNik(credNik);
    setTimeout(() => setFilledNik(null), 2000);
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-navy px-4 py-10">
      {/* ------------------------------- MEREK ------------------------------ */}
      <header className="mb-7 text-center">
        <span className="mx-auto mb-4 grid h-16 w-16 place-items-center rounded-kartu bg-brand text-xl font-bold text-brand-teks">
          P1
        </span>
        <h1 className="text-2xl font-bold tracking-tight text-white">PHM One</h1>
        <p className="mt-1 text-isi text-white/70">
          Sistem Operasi Manufaktur PT Putra Hatim Mandiri
        </p>
      </header>

      <div className="w-full max-w-[26rem]">
        {/* ------------------------------ FORM ----------------------------- */}
        <div className="phm-card">
          <h2 className="phm-section">Masuk ke Akun</h2>
          <p className="phm-helper mt-1">
            Gunakan Nomor Induk Karyawan dari perusahaan.
          </p>

          {error && (
            <div
              role="alert"
              className="mt-5 flex items-start gap-2.5 rounded-kontrol bg-kritis-latar p-3 text-isi text-kritis-teks"
            >
              <AlertCircle className="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="mt-5 space-y-4">
            <div>
              <label htmlFor="nik" className="phm-label">
                Nomor Induk Karyawan
              </label>
              <div className="relative">
                <User
                  className="pointer-events-none absolute left-3 top-1/2 h-[1.125rem] w-[1.125rem] -translate-y-1/2 text-redup"
                  aria-hidden="true"
                />
                <input
                  id="nik"
                  type="text"
                  value={nik}
                  onChange={(e) => setNik(e.target.value)}
                  placeholder="PHM-OP-001"
                  className="phm-input pl-10 font-medium uppercase tracking-wide"
                  autoComplete="username"
                  autoCapitalize="characters"
                  spellCheck={false}
                  required
                />
              </div>
            </div>

            <div>
              <label htmlFor="password" className="phm-label">
                Kata Sandi
              </label>
              <div className="relative">
                <Lock
                  className="pointer-events-none absolute left-3 top-1/2 h-[1.125rem] w-[1.125rem] -translate-y-1/2 text-redup"
                  aria-hidden="true"
                />
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Masukkan kata sandi"
                  className="phm-input pl-10 pr-12"
                  autoComplete="current-password"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword((v) => !v)}
                  className="absolute right-1 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-kontrol text-redup transition-colors hover:text-brand-aksen"
                  aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                >
                  {showPassword ? (
                    <EyeOff className="h-[1.125rem] w-[1.125rem]" aria-hidden="true" />
                  ) : (
                    <Eye className="h-[1.125rem] w-[1.125rem]" aria-hidden="true" />
                  )}
                </button>
              </div>
            </div>

            <label className="flex min-h-[44px] cursor-pointer items-center gap-2.5 text-isi text-teks">
              <input
                type="checkbox"
                checked={remember}
                onChange={(e) => setRemember(e.target.checked)}
                className="h-[1.125rem] w-[1.125rem] rounded border-garis-kuat accent-[color:var(--brand)]"
              />
              <span>Ingat perangkat ini</span>
            </label>

            <button type="submit" disabled={isLoading} className="phm-btn-primary w-full">
              {isLoading ? (
                <>
                  <span
                    className="h-4 w-4 animate-spin rounded-pil border-2 border-current border-t-transparent"
                    aria-hidden="true"
                  />
                  <span>Memverifikasi…</span>
                </>
              ) : (
                <span>Masuk</span>
              )}
            </button>
          </form>
        </div>

        {/* ------------------------- KREDENSIAL DEMO ------------------------ */}
        <div className="mt-4 overflow-hidden rounded-kartu border border-white/15 bg-white/5">
          <button
            type="button"
            onClick={() => setShowDemoGuide((v) => !v)}
            aria-expanded={showDemoGuide}
            className="flex w-full items-center justify-between gap-3 px-4 py-3.5 text-left"
          >
            <span className="min-w-0">
              <span className="block text-isi font-semibold text-white">
                Akun demo per peran
              </span>
              <span className="block text-label text-white/60">
                Pilih satu untuk mengisi form otomatis
              </span>
            </span>
            {showDemoGuide ? (
              <ChevronUp className="h-5 w-5 shrink-0 text-white/60" aria-hidden="true" />
            ) : (
              <ChevronDown className="h-5 w-5 shrink-0 text-white/60" aria-hidden="true" />
            )}
          </button>

          {showDemoGuide && (
            <div className="grid gap-1.5 border-t border-white/10 p-3 sm:grid-cols-2">
              {DEMO_CREDENTIALS.map((item) => {
                const isFilled = filledNik === item.nik;
                return (
                  <button
                    key={item.nik}
                    type="button"
                    onClick={() => applyCredential(item.nik, item.password)}
                    className="flex items-center justify-between gap-2 rounded-kontrol border border-white/10 bg-white/5 px-3 py-2.5 text-left transition-colors hover:border-white/30 hover:bg-white/10"
                  >
                    <span className="min-w-0">
                      <span className="block truncate text-label font-semibold text-white">
                        {item.role}
                      </span>
                      <span className="mt-0.5 block truncate text-mini text-white/70">
                        {item.nik} · {item.password}
                      </span>
                    </span>
                    {isFilled && (
                      <Check className="h-4 w-4 shrink-0 text-white" aria-hidden="true" />
                    )}
                  </button>
                );
              })}
            </div>
          )}
        </div>

        <p className="mt-4 text-center text-label text-white/50">
          Mode demo — seluruh data bersifat fiktif.
        </p>
      </div>
    </div>
  );
}
