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

const totalStudents = computed(() => {
  const set = new Set(bookings.items.filter((b) => b.userRole === 'student').map((b) => b.userName))
  return set.size
})

const analytics = computed(() => {
  const base = { confirmed: 0, approved: 0, pending: 0, cancelled: 0, rejected: 0, completed: 0 }
  for (const b of bookings.items) (base as any)[b.status] = ((base as any)[b.status] || 0) + 1
  return base
})

const roomUsage = computed(() => {
  const map = new Map<string, number>()
  for (const b of bookings.items) {
    if (b.status === 'cancelled' || b.status === 'rejected') continue
    map.set(b.roomId, (map.get(b.roomId) || 0) + 1)
  }
  return rooms.items
    .map((r) => ({ room: r, count: map.get(r.id) || 0 }))
    .sort((a, b) => b.count - a.count)
    .slice(0, 6)
})

const recent = computed(() => bookings.items.slice(0, 5))
</script>

<template>
  <PortalShell portal="admin" :nav="adminNav" accent="red">
    <section class="dash">
      <div class="cards">
        <div class="stat">
          <div class="stat-ico red">▤</div>
          <div class="stat-num">{{ rooms.items.length }}</div>
          <div class="stat-label muted">Total Rooms</div>
        </div>
        <div class="stat">
          <div class="stat-ico green">✓</div>
          <div class="stat-num">{{ bookings.active.length }}</div>
          <div class="stat-label muted">Active Reservations</div>
        </div>
        <div class="stat">
          <div class="stat-ico blue">👤</div>
          <div class="stat-num">{{ totalStudents }}</div>
          <div class="stat-label muted">Total Students</div>
        </div>
        <div class="stat">
          <div class="stat-ico orange">≡</div>
          <div class="stat-num">{{ bookings.items.length }}</div>
          <div class="stat-label muted">Total Bookings</div>
        </div>
      </div>

      <ErrorBanner v-if="rooms.error" :message="rooms.error" />
      <ErrorBanner v-if="bookings.error" :message="bookings.error" />
      <SkeletonList v-else-if="rooms.loading || bookings.loading" />

      <div v-else class="dash-grid admin-grid">
        <div class="panel">
          <div class="panel-title">Room Usage Overview</div>
          <div class="bars">
            <div v-for="x in roomUsage" :key="x.room.id" class="barrow">
              <div class="barname">{{ x.room.name }}</div>
              <div class="bar">
                <div class="barfill" :style="{ width: `${Math.min(100, x.count * 18)}%` }" />
              </div>
              <div class="barcount muted">{{ x.count }} bookings</div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-title">Reservation Analytics</div>
          <div class="mini-grid">
            <div class="mini ok">
              <div class="mini-num">{{ analytics.confirmed }}</div>
              <div class="mini-lab muted">Confirmed</div>
            </div>
            <div class="mini blue">
              <div class="mini-num">{{ analytics.pending }}</div>
              <div class="mini-lab muted">Pending</div>
            </div>
            <div class="mini red">
              <div class="mini-num">{{ analytics.cancelled }}</div>
              <div class="mini-lab muted">Cancelled</div>
            </div>
            <div class="mini red">
              <div class="mini-num">{{ analytics.rejected }}</div>
              <div class="mini-lab muted">Rejected</div>
            </div>
            <div class="mini ok">
              <div class="mini-num">{{ analytics.completed }}</div>
              <div class="mini-lab muted">Completed</div>
            </div>
            <div class="mini blue">
              <div class="mini-num">{{ analytics.approved }}</div>
              <div class="mini-lab muted">Approved</div>
            </div>
          </div>
        </div>

        <div class="panel wide">
          <div class="panel-title">Recent Reservations</div>
          <div class="table cardlike">
            <div class="trow thead muted">
              <div>Student</div>
              <div>Room</div>
              <div>Date</div>
              <div>Status</div>
            </div>
            <div v-for="b in recent" :key="b.id" class="trow">
              <div>{{ b.userName }}</div>
              <div>{{ rooms.byId(b.roomId)?.name || b.roomId }}</div>
              <div>{{ b.date }}</div>
              <div><span class="pill" :class="b.status === 'confirmed' ? 'green' : b.status === 'pending' ? 'amber' : 'red'">{{ b.status }}</span></div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </PortalShell>
</template>
