<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { studentNav } from '../../layouts/nav'
import ErrorBanner from '../../components/ErrorBanner.vue'
import SkeletonList from '../../components/SkeletonList.vue'
import { useAuthStore } from '../../stores/auth'
import { useBookingsStore } from '../../stores/bookings'
import { useRoomsStore } from '../../stores/rooms'

const auth = useAuthStore()
const router = useRouter()
const rooms = useRoomsStore()
const bookings = useBookingsStore()

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'student') {
    router.replace('/student/login')
    return
  }
  await Promise.all([rooms.load(), bookings.load({ userName: auth.name || '', userRole: 'student' })])
})

const rows = computed(() => bookings.items)

function tone(status: string) {
  if (status === 'confirmed' || status === 'approved') return 'pill green'
  if (status === 'pending') return 'pill amber'
  if (status === 'cancelled') return 'pill red'
  if (status === 'rejected') return 'pill red'
  return 'pill'
}
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Reservation History</h1>
          <p class="muted">View all your past and current reservations</p>
        </div>
      </div>

      <ErrorBanner v-if="bookings.error" :message="bookings.error" />
      <SkeletonList v-else-if="bookings.loading" />

      <div v-else class="table cardlike">
        <div class="trow thead muted">
          <div>Room</div>
          <div>Date</div>
          <div>Time</div>
          <div>Duration</div>
          <div>Status</div>
        </div>
        <div v-for="b in rows" :key="b.id" class="trow">
          <div>{{ rooms.byId(b.roomId)?.name || b.roomId }}</div>
          <div>{{ b.date }}</div>
          <div>{{ b.startTime }}</div>
          <div>{{ Math.max(1, (Number(b.endTime.slice(0, 2)) - Number(b.startTime.slice(0, 2))) || 1) }}h</div>
          <div>
            <span :class="tone(b.status)">{{ b.status === 'approved' ? 'confirmed' : b.status }}</span>
          </div>
        </div>
      </div>
    </section>
  </PortalShell>
</template>
