'use client';

import React, { useState } from 'react';
import { useAuth } from '../../context/AuthContext';
import { useOperations } from '../../context/OperationsContext';
import { RoleType } from '../../types';
import {
  ShieldCheck,
  Bot,
  LogOut,
  ChevronDown,
  UserCheck,
  Moon,
  Sun,
  Menu,
  Bell,
} from 'lucide-react';

interface NavbarProps {
  onToggleSidebar?: () => void;
  onOpenAssistant: () => void;
}

const ROLE_LABELS: Record<RoleType, string> = {
  operator: 'Operator Produksi',
  supervisor: 'Supervisor Produksi',
  admin_order: 'Admin Pesanan',
  ppic: 'PPIC (Perencanaan)',
  warehouse: 'Petugas Gudang',
  driver: 'Driver Pengiriman',
  management: 'Pimpinan / Manajemen',
  sysadmin: 'Administrator Sistem',
};

export const Navbar: React.FC<NavbarProps> = ({ onToggleSidebar, onOpenAssistant }) => {
  const { user, activeRole, logout, switchRole } = useAuth();
  const { pendingReviewsCount } = useOperations();
  const [isRoleDropdownOpen, setIsRoleDropdownOpen] = useState(false);
  const [isDarkMode, setIsDarkMode] = useState(false);

  const toggleDarkMode = () => {
    setIsDarkMode(!isDarkMode);
    if (!isDarkMode) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  };

  return (
    <header className="sticky top-0 z-40 h-16 bg-white dark:bg-[#1E293B] border-b border-slate-200 dark:border-slate-800 shadow-xs px-4 sm:px-6 flex items-center justify-between">
      {/* Left side: Hamburger (mobile) + Brand logo */}
      <div className="flex items-center gap-3">
        {onToggleSidebar && (
          <button
            onClick={onToggleSidebar}
            className="md:hidden p-2 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
            aria-label="Menu"
          >
            <Menu className="w-5 h-5" />
          </button>
        )}
        <div className="flex items-center gap-2.5">
          <div className="w-9 h-9 rounded-xl bg-[#0077C8] flex items-center justify-center text-white shadow-xs">
            <ShieldCheck className="w-5 h-5" />
          </div>
          <div>
            <span className="font-extrabold text-base tracking-tight text-slate-900 dark:text-white">
              PHM <span className="text-[#0077C8]">ONE</span>
            </span>
            <span className="hidden sm:inline-block ml-2 text-[0.6875rem] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-900/40 text-[#0077C8] dark:text-blue-300 border border-blue-200 dark:border-blue-800">
              Manufaktur
            </span>
          </div>
        </div>
      </div>

      {/* Right side: Assistant button, Role switcher, Theme toggle, User badge */}
      <div className="flex items-center gap-2 sm:gap-3">
        {/* Beesha AI Assistant Button */}
        <button
          onClick={onOpenAssistant}
          className="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold text-xs shadow-xs transition-all cursor-pointer"
          title="Buka Asisten AI Beesha"
        >
          <Bot className="w-4 h-4" />
          <span className="hidden md:inline">Tanya Beesha AI</span>
          <span className="flex h-2 w-2 relative">
            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-sky-300 opacity-75"></span>
            <span className="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
          </span>
        </button>

        {/* Dark Mode Toggle */}
        <button
          onClick={toggleDarkMode}
          className="p-2 rounded-xl text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          title={isDarkMode ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'}
        >
          {isDarkMode ? <Sun className="w-4 h-4" /> : <Moon className="w-4 h-4" />}
        </button>

        {/* User & Role Badge */}
        {user && (
          <div className="relative">
            <button
              onClick={() => setIsRoleDropdownOpen(!isRoleDropdownOpen)}
              className="flex items-center gap-2 p-1.5 pl-2.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-slate-50 dark:bg-slate-800 transition-colors cursor-pointer"
            >
              <div className="text-left hidden sm:block">
                <div className="text-xs font-bold text-slate-800 dark:text-slate-100 truncate max-w-[120px]">
                  {user.name}
                </div>
                <div className="text-[0.6875rem] text-[#0077C8] dark:text-blue-400 font-semibold truncate max-w-[120px]">
                  {activeRole ? ROLE_LABELS[activeRole] : ''}
                </div>
              </div>
              <div className="w-7 h-7 rounded-lg bg-[#1B365D] text-white text-xs font-bold flex items-center justify-center">
                {user.avatar_initials}
              </div>
              <ChevronDown className="w-3.5 h-3.5 text-slate-400" />
            </button>

            {isRoleDropdownOpen && (
              <div className="absolute right-0 mt-2 w-64 rounded-2xl bg-white dark:bg-[#1E293B] shadow-xl border border-slate-200 dark:border-slate-700 py-2 z-50 animate-in fade-in">
                <div className="px-4 py-2 border-b border-slate-100 dark:border-slate-800">
                  <div className="text-xs font-bold text-slate-900 dark:text-white">{user.name}</div>
                  <div className="text-[0.7rem] text-slate-500 font-mono mt-0.5">{user.employee_number}</div>
                  <div className="text-[0.7rem] text-slate-500">{user.position}</div>
                </div>

                {/* Multiple roles switcher if applicable */}
                <div className="py-1">
                  <div className="px-4 py-1 text-[0.6875rem] font-bold text-slate-400 uppercase tracking-wider">
                    Ganti Peran Aktif
                  </div>
                  {user.roles.map((r) => (
                    <button
                      key={r}
                      onClick={() => {
                        switchRole(r);
                        setIsRoleDropdownOpen(false);
                      }}
                      className={`w-full px-4 py-2 text-left text-xs flex items-center justify-between transition-colors ${
                        activeRole === r
                          ? 'bg-blue-50 dark:bg-blue-900/30 text-[#0077C8] dark:text-blue-300 font-bold'
                          : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'
                      }`}
                    >
                      <span>{ROLE_LABELS[r]}</span>
                      {activeRole === r && <UserCheck className="w-3.5 h-3.5 text-[#0077C8]" />}
                    </button>
                  ))}
                </div>

                <div className="border-t border-slate-100 dark:border-slate-800 pt-1 mt-1">
                  <button
                    onClick={() => {
                      setIsRoleDropdownOpen(false);
                      logout();
                    }}
                    className="w-full px-4 py-2 text-left text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30 flex items-center gap-2 font-semibold"
                  >
                    <LogOut className="w-3.5 h-3.5" />
                    <span>Keluar dari Sistem</span>
                  </button>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    </header>
  );
};
