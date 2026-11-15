import type { Metadata, Viewport } from 'next';
import './globals.css';
import { AuthProvider } from '../context/AuthContext';
import { OperationsProvider } from '../context/OperationsContext';

export const metadata: Metadata = {
  title: 'PHM One — Sistem Operasi Manufaktur PT Putra Hatim Mandiri',
  description:
    'Aplikasi terpadu operasional manufaktur, pelaporan lini produksi, dan pelacakan logistik PT Putra Hatim Mandiri.',
};

export const viewport: Viewport = {
  width: 'device-width',
  initialScale: 1,
  viewportFit: 'cover',
  themeColor: [
    { media: '(prefers-color-scheme: light)', color: '#072a38' },
    { media: '(prefers-color-scheme: dark)', color: '#071620' },
  ],
};

/**
 * Tema diterapkan sebelum render pertama lewat skrip inline di <head>.
 * Tanpa ini, pengguna yang memilih mode gelap akan melihat kedipan putih
 * setiap kali membuka halaman.
 */
const THEME_SCRIPT = `
(function () {
  try {
    if (localStorage.getItem('phm-tema') === 'gelap') {
      document.documentElement.classList.add('dark');
    }
  } catch (e) {}
})();
`;

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="id" suppressHydrationWarning>
      <head>
        <script dangerouslySetInnerHTML={{ __html: THEME_SCRIPT }} />
      </head>
      <body className="min-h-screen bg-latar text-teks antialiased">
        <AuthProvider>
          <OperationsProvider>{children}</OperationsProvider>
        </AuthProvider>
      </body>
    </html>
  );
}
