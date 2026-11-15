import type { Config } from 'tailwindcss';

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
        phm: {
          navy: '#1B365D',
          'navy-dark': '#11223B',
          blue: '#0077C8',
          'blue-hover': '#0062A3',
          'blue-light': '#E5F2FF',
          surface: '#FFFFFF',
          'surface-elevated': '#F8FAFC',
          'surface-sunken': '#F1F5F9',
          border: '#E2E8F0',
          text: '#1E293B',
          'text-secondary': '#64748B',
          success: '#059669',
          warning: '#D97706',
          danger: '#DC2626',
          info: '#0284C7',
        },
      },
      borderRadius: {
        card: '14px',
        control: '12px',
        badge: '8px',
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'sans-serif'],
      },
      boxShadow: {
        card: '0 1px 3px rgba(0, 0, 0, 0.08)',
        elevated: '0 4px 12px rgba(0, 0, 0, 0.05)',
      },
    },
  },
  plugins: [],
};

export default config;
