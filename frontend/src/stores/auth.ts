import { defineStore } from 'pinia'

import type { UserRole, UserSession } from '../types'

const LS_KEY = 'unispace.session.v1'

function safeParse(raw: string | null): UserSession | null {
  if (!raw) return null
  try {
    const v = JSON.parse(raw)
    if (!v || typeof v !== 'object') return null
    if (typeof v.email !== 'string' || typeof v.name !== 'string') return null
    if (v.role !== 'student' && v.role !== 'admin') return null
    return { email: v.email, name: v.name, role: v.role as UserRole }
  } catch {
    return null
  }
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    session: safeParse(localStorage.getItem(LS_KEY)) as UserSession | null,
  }),
  getters: {
    isAuthed: (s) => Boolean(s.session),
    role: (s) => s.session?.role || null,
    name: (s) => s.session?.name || null,
    email: (s) => s.session?.email || null,
  },
  actions: {
    login(email: string, role: UserRole) {
      const e = email.trim().slice(0, 80)
      const fromEmail = () => {
        const local = e.split('@')[0] || 'User'
        const words = local.split(/[._-]+/).filter(Boolean)
        const cap = (w: string) => w.slice(0, 1).toUpperCase() + w.slice(1)
        return words.map(cap).join(' ').slice(0, 60) || 'User'
      }

      const name =
        role === 'admin' && e.toLowerCase().startsWith('admin@') ? 'Dr. Hassan Ibrahim' : fromEmail()

      const session: UserSession = { email: e, name, role }
      this.session = session
      localStorage.setItem(LS_KEY, JSON.stringify(session))
    },
    logout() {
      this.session = null
      localStorage.removeItem(LS_KEY)
    },
  },
})
