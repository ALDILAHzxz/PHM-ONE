'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth, ROLE_DEFAULT_ROUTES } from '../context/AuthContext';

export default function HomePage() {
  const { user, activeRole } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (user && activeRole) {
      router.replace(ROLE_DEFAULT_ROUTES[activeRole] || '/dashboard');
    } else {
      router.replace('/masuk');
    }
  }, [user, activeRole, router]);

  return (
    <div className="min-h-screen bg-[#F4F6F9] dark:bg-[#0B1120] flex items-center justify-center">
      <div className="flex items-center gap-3 text-slate-500 text-sm font-medium">
        <div className="w-5 h-5 border-2 border-[#0077C8] border-t-transparent rounded-full animate-spin" />
        <span>Mengarahkan ke PHM One…</span>
      </div>
    </div>
  );
}
