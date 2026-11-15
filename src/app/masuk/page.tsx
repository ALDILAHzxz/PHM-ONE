'use client';

import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { DEMO_CREDENTIALS } from '../../data/mockData';
import {
  ShieldCheck,
  User,
  Lock,
  Eye,
  EyeOff,
  AlertCircle,
  CheckCircle2,
  Copy,
  Info,
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
  const [copiedNik, setCopiedNik] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!nik.trim()) {
      setError('Silakan masukkan Nomor Induk Karyawan (NIK).');
      return;
    }
    if (!password.trim()) {
      setError('Silakan masukkan kata sandi Anda.');
      return;
    }

    setIsLoading(true);
    try {
      const res = await login(nik, password);
      if (!res.success) {
        setError(res.message || 'Gagal masuk. Periksa NIK dan kata sandi Anda.');
      }
    } catch (err) {
      setError('Terjadi kesalahan pada sistem. Silakan coba lagi.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleApplyCredential = (credNik: string, credPass: string) => {
    setNik(credNik);
    setPassword(credPass);
    setError(null);
    setCopiedNik(credNik);
    setTimeout(() => setCopiedNik(null), 2000);
  };

  return (
    <div className="min-h-screen bg-gradient-to-b from-[#0F1E36] to-[#1B365D] flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8 selection:bg-[#0077C8] selection:text-white">
      {/* Brand Header */}
      <div className="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-[#0077C8] text-white shadow-xl shadow-blue-500/20 mb-4 ring-4 ring-white/10">
          <ShieldCheck className="w-9 h-9" />
        </div>
        <h1 className="text-3xl font-extrabold text-white tracking-tight">PHM ONE</h1>
        <p className="mt-1 text-sm text-blue-200">
          Sistem Operasi Manufaktur PT Putra Hatim Mandiri
        </p>
      </div>

      {/* Main Login Card */}
      <div className="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div className="bg-white dark:bg-[#1E293B] py-8 px-6 shadow-2xl rounded-2xl sm:px-10 border border-slate-200/80 dark:border-slate-700">
          <div className="mb-6">
            <h2 className="text-xl font-bold text-slate-900 dark:text-white">Masuk ke Akun</h2>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Silakan masukkan Nomor Induk Karyawan dan kata sandi Anda.
            </p>
          </div>

          {error && (
            <div className="mb-5 p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 flex items-start gap-3 text-rose-800 dark:text-rose-200 text-xs sm:text-sm animate-in fade-in">
              <AlertCircle className="w-5 h-5 shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" />
              <span>{error}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="nik" className="phm-label">
                Nomor Induk Karyawan (NIK)
              </label>
              <div className="relative rounded-md shadow-xs">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <User className="w-4 h-4" />
                </div>
                <input
                  id="nik"
                  type="text"
                  value={nik}
                  onChange={(e) => setNik(e.target.value)}
                  placeholder="Contoh: PHM-OP-001"
                  className="phm-input pl-10 uppercase tracking-wider font-medium"
                  autoComplete="username"
                  required
                />
              </div>
              <p className="phm-helper mt-1.5 text-[0.8rem]">
                Format: <span className="font-mono text-slate-700 dark:text-slate-300">PHM-[ROLE]-001</span>
              </p>
            </div>

            <div>
              <label htmlFor="password" className="phm-label">
                Kata Sandi
              </label>
              <div className="relative rounded-md shadow-xs">
                <div className="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                  <Lock className="w-4 h-4" />
                </div>
                <input
                  id="password"
                  type={showPassword ? 'text' : 'password'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Masukkan kata sandi"
                  className="phm-input pl-10 pr-10"
                  autoComplete="current-password"
                  required
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors"
                  aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                >
                  {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                </button>
              </div>
            </div>

            <div className="flex items-center justify-between text-xs sm:text-sm pt-1">
              <label className="flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-300">
                <input
                  type="checkbox"
                  checked={remember}
                  onChange={(e) => setRemember(e.target.checked)}
                  className="w-4 h-4 rounded border-slate-300 text-[#0077C8] focus:ring-[#0077C8]"
                />
                <span>Ingat perangkat ini</span>
              </label>
              <span className="text-slate-400 text-xs">Versi 1.0 (Next.js)</span>
            </div>

            <div className="pt-2">
              <button
                type="submit"
                disabled={isLoading}
                className="w-full h-11 bg-[#0077C8] hover:bg-[#0062A3] active:bg-[#00528A] text-white font-semibold rounded-xl shadow-sm transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed"
              >
                {isLoading ? (
                  <>
                    <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    <span>Memverifikasi Akun…</span>
                  </>
                ) : (
                  <span>Masuk ke Sistem</span>
                )}
              </button>
            </div>
          </form>
        </div>

        {/* Demo Credentials Guide Card */}
        <div className="mt-6 bg-slate-900/60 backdrop-blur-md border border-white/10 rounded-2xl p-4 text-white shadow-lg">
          <div
            onClick={() => setShowDemoGuide(!showDemoGuide)}
            className="flex items-center justify-between cursor-pointer select-none"
          >
            <div className="flex items-center gap-2.5">
              <Info className="w-4 h-4 text-amber-400 shrink-0" />
              <span className="text-xs sm:text-sm font-semibold text-slate-200">
                Daftar NIK & Kata Sandi Demo (Klik untuk Isi Cepat)
              </span>
            </div>
            {showDemoGuide ? (
              <ChevronUp className="w-4 h-4 text-slate-400" />
            ) : (
              <ChevronDown className="w-4 h-4 text-slate-400" />
            )}
          </div>

          {showDemoGuide && (
            <div className="mt-3.5 pt-3 border-t border-white/10 space-y-2">
              <p className="text-[0.75rem] text-slate-300 leading-relaxed">
                Pilih peran di bawah ini untuk mengisi NIK & kata sandi secara otomatis ke dalam form login:
              </p>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-2">
                {DEMO_CREDENTIALS.map((item) => (
                  <button
                    key={item.nik}
                    type="button"
                    onClick={() => handleApplyCredential(item.nik, item.password)}
                    className="p-2.5 rounded-xl bg-white/5 hover:bg-white/15 border border-white/10 text-left transition-all flex items-center justify-between group cursor-pointer"
                  >
                    <div className="min-w-0 pr-2">
                      <div className="text-xs font-bold text-white flex items-center gap-1.5">
                        <span className="truncate">{item.role}</span>
                        {copiedNik === item.nik && (
                          <CheckCircle2 className="w-3.5 h-3.5 text-emerald-400 shrink-0" />
                        )}
                      </div>
                      <div className="text-[0.7rem] text-slate-300 font-mono mt-0.5 truncate">
                        NIK: <span className="text-amber-300 font-semibold">{item.nik}</span>
                      </div>
                      <div className="text-[0.7rem] text-slate-400 font-mono truncate">
                        Pass: <span className="text-slate-200">{item.password}</span>
                      </div>
                    </div>
                    <div className="shrink-0 p-1.5 rounded-lg bg-white/10 group-hover:bg-[#0077C8] text-slate-300 group-hover:text-white transition-colors">
                      <Copy className="w-3.5 h-3.5" />
                    </div>
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
