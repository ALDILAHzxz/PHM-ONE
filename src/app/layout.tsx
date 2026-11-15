import type { Metadata } from 'next';
import './globals.css';
import { AuthProvider } from '../context/AuthContext';
import { OperationsProvider } from '../context/OperationsContext';

export const metadata: Metadata = {
  title: 'PHM One — Sistem Operasi Manufaktur PT Putra Hatim Mandiri',
  description: 'Aplikasi terpadu operasional manufaktur, pelaporan lini produksi, dan pelacakan logistik PT PHM.',
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="id">
      <body className="min-h-screen antialiased bg-[#F4F6F9] dark:bg-[#0B1120] text-[#1E293B] dark:text-[#F1F5F9]">
        <AuthProvider>
          <OperationsProvider>
            {children}
          </OperationsProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
