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

const mine = computed(() =>
  bookings.items.filter((b) => b.status === 'confirmed' || b.status === 'approved' || b.status === 'pending')
)

function statusTone(status: string) {
  if (status === 'confirmed' || status === 'approved') return 'badge ok'
  if (status === 'pending') return 'badge warn'
  return 'badge muted'
}

async function cancel(id: string) {
  const ok = confirm('Cancel reservation?')
  if (!ok) return
  try {
    await bookings.cancel(id)
  } catch {
    // store shows error
  }
}
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">My Reservations</h1>
          <p class="muted">Manage your active study room bookings</p>
        </div>
      </div>

      <ErrorBanner v-if="bookings.error" :message="bookings.error" />
      <SkeletonList v-else-if="bookings.loading" />

      <div v-else class="list">
        <div v-for="b in mine" :key="b.id" class="resrow">
          <div class="rowleft">
            <div class="rowtitle">
              {{ rooms.byId(b.roomId)?.name || b.roomId }}
              <span :class="statusTone(b.status)">{{
                b.status === 'pending' ? 'Pending' : 'Confirmed'
              }}</span>
            </div>
            <div class="rowmeta muted">
              {{ b.date }} · {{ b.startTime }}–{{ b.endTime }} · Reservation ID: {{ b.id }}
            </div>
          </div>
          <button class="btn btn-outline-danger" type="button" @click="cancel(b.id)">Cancel Reservation</button>
        </div>
      </div>
    </section>
  </PortalShell>
</template>
