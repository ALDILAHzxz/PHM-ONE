'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { User, RoleType } from '../types';
import { INITIAL_USERS } from '../data/mockData';
import { useRouter } from 'next/navigation';

interface AuthContextType {
  user: User | null;
  activeRole: RoleType | null;
  isAuthenticated: boolean;
  login: (nik: string, password: string) => Promise<{ success: boolean; message?: string }>;
  logout: () => void;
  switchRole: (role: RoleType) => void;
  hasPermission: (requiredRole: RoleType | RoleType[]) => boolean;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const ROLE_DEFAULT_ROUTES: Record<RoleType, string> = {
  operator: '/operator/tugas',
  supervisor: '/supervisor/pemeriksaan',
  admin_order: '/pesanan',
  ppic: '/perencanaan',
  warehouse: '/pengiriman',
  driver: '/driver/pengiriman',
  management: '/dashboard',
  sysadmin: '/sistem/pengguna',
};

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [user, setUser] = useState<User | null>(null);
  const [activeRole, setActiveRole] = useState<RoleType | null>(null);
  const [isLoaded, setIsLoaded] = useState(false);
  const router = useRouter();

  // Load from localStorage on client mount
  useEffect(() => {
    try {
      const storedUser = localStorage.getItem('phm_user');
      const storedRole = localStorage.getItem('phm_active_role');
      if (storedUser) {
        const parsed = JSON.parse(storedUser);
        setUser(parsed);
        setActiveRole((storedRole as RoleType) || parsed.roles[0]);
      }
    } catch (e) {
      console.error('Failed to load auth state', e);
    } finally {
      setIsLoaded(true);
    }
  }, []);

  const login = async (nik: string, password: string): Promise<{ success: boolean; message?: string }> => {
    // Normalise inputs
    const cleanNik = nik.trim().toUpperCase();
    const cleanPass = password.trim();

    // Map old PHM-EMP-XXX format to new clean PHM-XX-001 format if entered
    const empMapping: Record<string, string> = {
      'PHM-EMP-001': 'PHM-OP-001',
      'PHM-EMP-002': 'PHM-SPV-001',
      'PHM-EMP-003': 'PHM-ADM-001',
      'PHM-EMP-004': 'PHM-PPIC-001',
      'PHM-EMP-005': 'PHM-GDG-001',
      'PHM-EMP-006': 'PHM-DRV-001',
      'PHM-EMP-007': 'PHM-MGT-001',
      'PHM-EMP-008': 'PHM-SYS-001',
    };

    const targetNik = empMapping[cleanNik] || cleanNik;

    const foundUser = INITIAL_USERS.find((u) => u.employee_number.toUpperCase() === targetNik);

    if (!foundUser) {
      return { success: false, message: 'Nomor Induk Karyawan (NIK) tidak terdaftar dalam sistem PHM.' };
    }

    if (foundUser.status !== 'active') {
      return { success: false, message: 'Akun karyawan Anda berstatus tidak aktif. Hubungi bagian IT/HR.' };
    }

    // Accept both user's custom password and the universal demo password 'demo1234'
    const isPasswordValid =
      cleanPass === foundUser.passwordHash ||
      cleanPass === 'demo1234' ||
      cleanPass === 'password';

    if (!isPasswordValid) {
      return { success: false, message: 'Kata sandi tidak sesuai. Silakan periksa kembali.' };
    }

    const primaryRole = foundUser.roles[0];
    const sessionUser: User = {
      id: foundUser.id,
      employee_number: foundUser.employee_number,
      name: foundUser.name,
      email: foundUser.email,
      position: foundUser.position,
      department: foundUser.department,
      roles: foundUser.roles,
      status: foundUser.status,
      is_demo: foundUser.is_demo,
      avatar_initials: foundUser.avatar_initials,
    };

    setUser(sessionUser);
    setActiveRole(primaryRole);

    try {
      localStorage.setItem('phm_user', JSON.stringify(sessionUser));
      localStorage.setItem('phm_active_role', primaryRole);
    } catch (e) {
      console.warn('LocalStorage save failed', e);
    }

    const targetRoute = ROLE_DEFAULT_ROUTES[primaryRole] || '/dashboard';
    router.push(targetRoute);

    return { success: true };
  };

  const logout = () => {
    setUser(null);
    setActiveRole(null);
    try {
      localStorage.removeItem('phm_user');
      localStorage.removeItem('phm_active_role');
    } catch (e) {
      // ignore
    }
    router.push('/masuk');
  };

  const switchRole = (newRole: RoleType) => {
    if (user && user.roles.includes(newRole)) {
      setActiveRole(newRole);
      localStorage.setItem('phm_active_role', newRole);
      const targetRoute = ROLE_DEFAULT_ROUTES[newRole] || '/dashboard';
      router.push(targetRoute);
    }
  };

  const hasPermission = (requiredRole: RoleType | RoleType[]): boolean => {
    if (!user || !activeRole) return false;
    if (user.roles.includes('sysadmin') || activeRole === 'sysadmin') return true;

    if (Array.isArray(requiredRole)) {
      return requiredRole.includes(activeRole) || requiredRole.some((r) => user.roles.includes(r));
    }
    return activeRole === requiredRole || user.roles.includes(requiredRole);
  };

  return (
    <AuthContext.Provider
      value={{
        user,
        activeRole,
        isAuthenticated: !!user,
        login,
        logout,
        switchRole,
        hasPermission,
      }}
    >
      {isLoaded ? (
        children
      ) : (
        <div className="flex min-h-screen items-center justify-center bg-latar text-redup">
          Memuat sistem PHM One…
        </div>
      )}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
