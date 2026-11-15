'use client';

import React, { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '../../context/AuthContext';
import { Navbar } from './Navbar';
import { Sidebar } from './Sidebar';
import { BeeshaAssistant } from '../assistant/BeeshaAssistant';
import { AlertCircle } from 'lucide-react';

interface AppLayoutProps {
  children: React.ReactNode;
}

export const AppLayout: React.FC<AppLayoutProps> = ({ children }) => {
  const { user, isAuthenticated } = useAuth();
  const router = useRouter();

  const [isSidebarOpenMobile, setIsSidebarOpenMobile] = useState(false);
  const [isAssistantOpen, setIsAssistantOpen] = useState(false);

  useEffect(() => {
    if (!isAuthenticated) {
      router.replace('/masuk');
    }
  }, [isAuthenticated, router]);

  if (!user) {
    return (
      <div className="min-h-screen bg-[#F4F6F9] dark:bg-[#0B1120] flex items-center justify-center text-slate-500 text-sm">
        Memeriksa otentikasi…
      </div>
    );
  }

  return (
    <div className="min-h-screen flex flex-col bg-[#F4F6F9] dark:bg-[#0B1120] text-[#1E293B] dark:text-[#F1F5F9]">
      {/* Demo Mode Banner (Mandatory as per system specs) */}
      <div className="bg-amber-500 text-slate-950 px-4 py-1.5 text-xs font-semibold flex items-center justify-center gap-2 select-none shadow-2xs">
        <AlertCircle className="w-3.5 h-3.5" />
        <span>
          <strong>MODE DEMO AKTIF:</strong> Data operasional bersifat fiktif untuk simulasi PT Putra Hatim Mandiri.
        </span>
      </div>

      {/* Main Navbar */}
      <Navbar
        onToggleSidebar={() => setIsSidebarOpenMobile(!isSidebarOpenMobile)}
        onOpenAssistant={() => setIsAssistantOpen(true)}
      />

      {/* Body with Sidebar and Main Content */}
      <div className="flex-1 flex">
        <Sidebar
          isOpenMobile={isSidebarOpenMobile}
          onCloseMobile={() => setIsSidebarOpenMobile(false)}
        />

        <main className="flex-1 md:ml-64 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto animate-in fade-in duration-150">
          {children}
        </main>
      </div>

      {/* Beesha AI Assistant Drawer */}
      <BeeshaAssistant
        isOpen={isAssistantOpen}
        onClose={() => setIsAssistantOpen(false)}
      />
    </div>
  );
};
