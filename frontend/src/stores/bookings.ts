import { defineStore } from 'pinia'

import { api } from '../api/client'
import type { Booking, CreateBookingInput } from '../types'

type BookingsState = {
  items: Booking[]
  loading: boolean
  saving: boolean
  error: string | null
  lastLoadedAt: string | null
}

export const useBookingsStore = defineStore('bookings', {
  state: (): BookingsState => ({
    items: [],
    loading: false,
    saving: false,
    error: null,
    lastLoadedAt: null,
  }),
  getters: {
    active: (state) => state.items.filter((b) => ['confirmed', 'approved', 'pending'].includes(b.status)),
    pending: (state) => state.items.filter((b) => b.status === 'pending'),
  },
  actions: {
    async load(params?: { userName?: string; userRole?: string }) {
      this.loading = true
      this.error = null
      try {
        const res = await api.get<{ bookings: Booking[] }>('/api/bookings', { params })
        this.items = res.data.bookings
        this.lastLoadedAt = new Date().toISOString()
      } catch (err: any) {
        this.error = err?.message || 'Failed to load bookings.'
      } finally {
        this.loading = false
      }
    },
    async create(input: CreateBookingInput) {
      this.saving = true
      this.error = null
      try {
        const res = await api.post<{ booking: Booking }>('/api/bookings', input)
        this.items = [res.data.booking, ...this.items]
      } catch (err: any) {
        // Backend returns JSON error; axios surfaces a generic message, keep simple for demo.
        this.error = err?.response?.data?.error?.message || err?.message || 'Failed to create booking.'
        throw err
      } finally {
        this.saving = false
      }
    },
    async cancel(id: string) {
      this.saving = true
      this.error = null
      try {
        const res = await api.put<{ booking: Booking }>(`/api/bookings/${id}`, { status: 'cancelled' })
        const idx = this.items.findIndex((b) => b.id === id)
        if (idx >= 0) this.items.splice(idx, 1, res.data.booking)
      } catch (err: any) {
        this.error = err?.message || 'Failed to cancel booking.'
        throw err
      } finally {
        this.saving = false
      }
    },
    async approve(id: string) {
      this.saving = true
      this.error = null
      try {
        const res = await api.put<{ booking: Booking }>(`/api/bookings/${id}`, { status: 'approved' })
        const idx = this.items.findIndex((b) => b.id === id)
        if (idx >= 0) this.items.splice(idx, 1, res.data.booking)
      } catch (err: any) {
        this.error = err?.message || 'Failed to approve booking.'
        throw err
      } finally {
        this.saving = false
      }
    },
    async reject(id: string) {
      this.saving = true
      this.error = null
      try {
        const res = await api.put<{ booking: Booking }>(`/api/bookings/${id}`, { status: 'rejected' })
        const idx = this.items.findIndex((b) => b.id === id)
        if (idx >= 0) this.items.splice(idx, 1, res.data.booking)
      } catch (err: any) {
        this.error = err?.message || 'Failed to reject booking.'
        throw err
      } finally {
        this.saving = false
      }
    },
  },
})
