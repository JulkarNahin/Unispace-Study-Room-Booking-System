import type { NavItem } from './PortalShell.vue'

export const studentNav: NavItem[] = [
  { label: 'Dashboard', to: '/student/dashboard', icon: 'dashboard' },
  { label: 'Study Rooms', to: '/student/rooms', icon: 'rooms' },
  { label: 'Reserve Room', to: '/student/reserve', icon: 'reserve' },
  { label: 'My Reservations', to: '/student/reservations', icon: 'mine' },
  { label: 'Reservation History', to: '/student/history', icon: 'history' },
  { label: 'Reports', to: '/student/reports', icon: 'reports' },
]

export const adminNav: NavItem[] = [
  { label: 'Dashboard', to: '/admin/dashboard', icon: 'dashboard' },
  { label: 'Manage Rooms', to: '/admin/rooms', icon: 'rooms' },
  { label: 'Manage Reservations', to: '/admin/reservations', icon: 'reservations' },
  { label: 'Manage Students', to: '/admin/students', icon: 'students' },
  { label: 'Reports', to: '/admin/reports', icon: 'reports' },
]
