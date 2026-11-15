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
    <div className="flex min-h-screen items-center justify-center bg-latar">
      <div className="flex items-center gap-3 text-redup text-isi font-medium">
        <div className="h-5 w-5 animate-spin rounded-pil border-2 border-brand border-t-transparent" />
        <span>Mengarahkan ke PHM One…</span>
      </div>
    </div>
  );
}
