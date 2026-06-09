import { defineStore } from 'pinia'

import { api } from '../api/client'
import type { CreateRoomInput, Room } from '../types'

type RoomsState = {
  items: Room[]
  loading: boolean
  saving: boolean
  error: string | null
  lastLoadedAt: string | null
}

export const useRoomsStore = defineStore('rooms', {
  state: (): RoomsState => ({
    items: [],
    loading: false,
    saving: false,
    error: null,
    lastLoadedAt: null,
  }),
  getters: {
    byId: (state) => (id: string) => state.items.find((r) => r.id === id) || null,
    activeRooms: (state) => state.items.filter((r) => r.active),
  },
  actions: {
    async load() {
      this.loading = true
      this.error = null
      try {
        const res = await api.get<{ rooms: Room[] }>('/api/rooms')
        this.items = res.data.rooms
        this.lastLoadedAt = new Date().toISOString()
      } catch (err: any) {
        this.error = err?.message || 'Failed to load rooms.'
      } finally {
        this.loading = false
      }
    },
    async create(input: CreateRoomInput) {
      this.saving = true
      this.error = null
      try {
        const res = await api.post<{ room: Room }>('/api/rooms', input)
        this.items = [...this.items, res.data.room]
      } catch (err: any) {
        this.error = err?.message || 'Failed to create room.'
        throw err
      } finally {
        this.saving = false
      }
    },
    async update(id: string, patch: Partial<CreateRoomInput>) {
      this.saving = true
      this.error = null
      try {
        const res = await api.put<{ room: Room }>(`/api/rooms/${id}`, patch)
        const idx = this.items.findIndex((r) => r.id === id)
        if (idx >= 0) this.items.splice(idx, 1, res.data.room)
      } catch (err: any) {
        this.error = err?.message || 'Failed to update room.'
        throw err
      } finally {
        this.saving = false
      }
    },
    async remove(id: string) {
      this.saving = true
      this.error = null
      try {
        await api.delete(`/api/rooms/${id}`)
        this.items = this.items.filter((r) => r.id !== id)
      } catch (err: any) {
        this.error = err?.message || 'Failed to delete room.'
        throw err
      } finally {
        this.saving = false
      }
    },
  },
})

