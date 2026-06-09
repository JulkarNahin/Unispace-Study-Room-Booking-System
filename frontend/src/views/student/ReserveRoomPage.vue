<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { studentNav } from '../../layouts/nav'
import { api } from '../../api/client'
import { useAuthStore } from '../../stores/auth'
import { useBookingsStore } from '../../stores/bookings'
import { useRoomsStore } from '../../stores/rooms'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const rooms = useRoomsStore()
const bookings = useBookingsStore()

const roomId = computed(() => String(route.params.id || ''))
const room = computed(() => rooms.byId(roomId.value))

const date = ref(new Date().toISOString().slice(0, 10))
const startTime = ref('09:00')
const durationHours = ref(1)

const checking = ref(false)
const available = ref<boolean | null>(null)
const computedEnd = ref<string>('')

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'student') {
    router.replace('/student/login')
    return
  }
  if (rooms.items.length === 0) await rooms.load()
  if (!room.value) router.replace('/student/rooms')
})

async function checkAvailability() {
  checking.value = true
  available.value = null
  bookings.error = null as any
  try {
    const res = await api.post<{ available: boolean; endTime: string }>('/api/availability', {
      roomId: roomId.value,
      date: date.value,
      startTime: startTime.value,
      durationHours: durationHours.value,
    })
    available.value = res.data.available
    computedEnd.value = res.data.endTime
  } catch (err: any) {
    available.value = false
    bookings.error = err?.response?.data?.error?.message || err?.message || 'Failed to check availability.'
  } finally {
    checking.value = false
  }
}

async function confirmReservation() {
  if (!available.value) return
  try {
    await bookings.create({
      roomId: roomId.value,
      userName: auth.name || '',
      userRole: 'student',
      date: date.value,
      startTime: startTime.value,
      endTime: computedEnd.value || startTime.value,
      purpose: 'Study session',
    })
    router.push('/student/reservations')
  } catch {
    // store shows error
  }
}
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="page">
      <div class="crumb">
        <RouterLink class="small-link" to="/student/rooms">← Back to Study Rooms</RouterLink>
      </div>

      <div class="reserve-card">
        <div class="reserve-img">
          <img v-if="room?.imageUrl" class="reserve-photo" :src="room.imageUrl" :alt="room.name" />
        </div>
        <div class="reserve-meta">
          <div class="reserve-name">{{ room?.name || 'Study Room' }}</div>
          <div class="muted">Capacity: {{ room?.capacity || 0 }} people</div>
          <div class="chiprow" style="margin-top: 8px">
            <span v-for="f in (room?.features || []).slice(0, 6)" :key="f" class="chip">{{ f }}</span>
          </div>
        </div>
      </div>

      <div class="panel" style="margin-top: 14px">
        <div class="panel-title">Check Availability</div>
        <form class="avail-form" @submit.prevent="checkAvailability">
          <label class="field">
            <span class="label">Date</span>
            <input v-model="date" class="input" type="date" required />
          </label>
          <label class="field">
            <span class="label">Start Time</span>
            <input v-model="startTime" class="input" type="time" required />
          </label>
          <label class="field">
            <span class="label">Duration (hours)</span>
            <select v-model.number="durationHours" class="input">
              <option v-for="h in 6" :key="h" :value="h">{{ h }} hour</option>
            </select>
          </label>
          <button class="btn btn-primary wide" type="submit" :disabled="checking">
            {{ checking ? 'Checking…' : 'Check Availability' }}
          </button>
        </form>
      </div>

      <div v-if="available === true" class="result okbox">
        <div class="result-ico ok">✓</div>
        <div class="result-title">Room Available!</div>
        <div class="muted">
          {{ room?.name }} is available on {{ date }} from {{ startTime }} for {{ durationHours }} hour.
        </div>
        <button class="btn btn-success" type="button" @click="confirmReservation">Confirm Reservation</button>
      </div>

      <div v-else-if="available === false" class="result badbox">
        <div class="result-ico bad">✕</div>
        <div class="result-title">Room Unavailable</div>
        <div class="muted">This time slot is not available. Please try a different time.</div>
      </div>
    </section>
  </PortalShell>
</template>

