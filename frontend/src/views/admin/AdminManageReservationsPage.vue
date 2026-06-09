<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { adminNav } from '../../layouts/nav'
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
  if (!auth.isAuthed || auth.role !== 'admin') {
    router.replace('/admin/login')
    return
  }
  await Promise.all([rooms.load(), bookings.load()])
})

const rows = computed(() => bookings.items)

async function approve(id: string) {
  try {
    await bookings.approve(id)
  } catch {}
}
async function reject(id: string) {
  try {
    await bookings.reject(id)
  } catch {}
}
async function cancel(id: string) {
  const ok = confirm('Cancel this booking?')
  if (!ok) return
  try {
    await bookings.cancel(id)
  } catch {}
}
</script>

<template>
  <PortalShell portal="admin" :nav="adminNav" accent="red">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Manage Reservations</h1>
          <p class="muted">Review and manage all student reservations</p>
        </div>
      </div>

      <ErrorBanner v-if="bookings.error" :message="bookings.error" />
      <SkeletonList v-else-if="bookings.loading" />

      <div v-else class="table cardlike">
        <div class="trow thead muted">
          <div>ID</div>
          <div>Student</div>
          <div>Room</div>
          <div>Date</div>
          <div>Time</div>
          <div>Status</div>
          <div>Actions</div>
        </div>
        <div v-for="b in rows" :key="b.id" class="trow">
          <div class="muted">{{ b.id.slice(0, 6) }}</div>
          <div>{{ b.userName }}</div>
          <div>{{ rooms.byId(b.roomId)?.name || b.roomId }}</div>
          <div>{{ b.date }}</div>
          <div>{{ b.startTime }} ({{ Math.max(1, (Number(b.endTime.slice(0, 2)) - Number(b.startTime.slice(0, 2))) || 1) }}h)</div>
          <div>
            <span
              class="pill"
              :class="
                b.status === 'confirmed'
                  ? 'green'
                  : b.status === 'approved'
                    ? 'blue'
                    : b.status === 'pending'
                      ? 'amber'
                      : 'red'
              "
              >{{ b.status }}</span
            >
          </div>
          <div class="actions">
            <button v-if="b.status === 'pending'" class="btn btn-mini btn-success" type="button" @click="approve(b.id)">Approve</button>
            <button v-if="b.status === 'pending'" class="btn btn-mini btn-danger" type="button" @click="reject(b.id)">Reject</button>
            <button
              v-if="b.status === 'confirmed' || b.status === 'approved'"
              class="btn btn-mini btn-outline-danger"
              type="button"
              @click="cancel(b.id)"
            >
              Cancel
            </button>
            <span v-if="b.status !== 'pending' && b.status !== 'confirmed' && b.status !== 'approved'" class="muted"
              >No actions</span
            >
          </div>
        </div>
      </div>
    </section>
  </PortalShell>
</template>
