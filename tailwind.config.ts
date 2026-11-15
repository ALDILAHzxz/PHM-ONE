import type { Config } from 'tailwindcss';

/**
 * Token dipetakan ke CSS custom property, bukan nilai hex langsung.
 * Akibatnya satu kelas seperti `bg-kartu` bekerja di tema terang dan gelap
 * tanpa perlu menulis varian `dark:` di setiap elemen.
 */
const config: Config = {
  darkMode: 'class',
  content: [
    './src/pages/**/*.{js,ts,jsx,tsx,mdx}',
    './src/components/**/*.{js,ts,jsx,tsx,mdx}',
    './src/app/**/*.{js,ts,jsx,tsx,mdx}',
  ],
  theme: {
    extend: {
      colors: {
        latar: 'var(--latar)',
        kartu: 'var(--kartu)',
        'kartu-dalam': 'var(--kartu-dalam)',
        navy: 'var(--navy)',
        teks: 'var(--teks)',
        redup: 'var(--redup)',
        garis: 'var(--garis)',
        'garis-kuat': 'var(--garis-kuat)',
        brand: {
          DEFAULT: 'var(--brand)',
          pekat: 'var(--brand-pekat)',
          muda: 'var(--brand-muda)',
          redup: 'var(--brand-redup)',
          teks: 'var(--brand-teks)',
          aksen: 'var(--brand-aksen)',
        },
        aman: {
          DEFAULT: 'var(--aman)',
          latar: 'var(--aman-latar)',
          teks: 'var(--aman-teks)',
        },
        waspada: {
          DEFAULT: 'var(--waspada)',
          latar: 'var(--waspada-latar)',
          teks: 'var(--waspada-teks)',
        },
        kritis: {
          DEFAULT: 'var(--kritis)',
          latar: 'var(--kritis-latar)',
          teks: 'var(--kritis-teks)',
        },
        netral: {
          DEFAULT: 'var(--netral)',
          latar: 'var(--netral-latar)',
          teks: 'var(--netral-teks)',
        },
      },
      borderRadius: {
        kontrol: 'var(--r-kontrol)',
        kartu: 'var(--r-kartu)',
        pil: 'var(--r-pil)',
      },
      boxShadow: {
        kartu: 'var(--sh-kartu)',
        pop: 'var(--sh-pop)',
        modal: 'var(--sh-modal)',
      },
      spacing: {
        shell: 'var(--h-shell)',
        pita: 'var(--h-pita)',
        navbar: 'var(--h-navbar)',
        sidebar: 'var(--w-sidebar)',
      },
      fontSize: {
        mini: ['0.75rem', { lineHeight: '1.35' }],
        label: ['0.8125rem', { lineHeight: '1.4' }],
        // 'isi' sengaja TIDAK bernama 'kartu': lihat catatan di atas.
        isi: ['0.9375rem', { lineHeight: '1.45' }],
        judul: ['1.375rem', { lineHeight: '1.25' }],
        kpi: ['1.75rem', { lineHeight: '1.1' }],
      },
      transitionTimingFunction: {
        panel: 'cubic-bezier(.22,.61,.36,1)',
      },
      zIndex: {
        shell: '60',
        drawer: '70',
        fab: '40',
      },
    },
  },
  plugins: [],
};

export default config;
