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

const upcoming = computed(() => bookings.items.filter((b) => b.status === 'confirmed' || b.status === 'approved').slice(0, 2))
const featuredRooms = computed(() => rooms.activeRooms.slice(0, 3))
const totalBookings = computed(() => bookings.items.length)
const cancelledCount = computed(() => bookings.items.filter((b) => b.status === 'cancelled').length)
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="dash">
      <div class="cards">
        <div class="stat">
          <div class="stat-ico blue">✓</div>
          <div class="stat-num">{{ bookings.active.length }}</div>
          <div class="stat-label muted">Active Reservations</div>
        </div>
        <div class="stat">
          <div class="stat-ico green">▤</div>
          <div class="stat-num">{{ rooms.activeRooms.length }}</div>
          <div class="stat-label muted">Available Rooms</div>
        </div>
        <div class="stat">
          <div class="stat-ico orange">≡</div>
          <div class="stat-num">{{ totalBookings }}</div>
          <div class="stat-label muted">Total Bookings</div>
        </div>
        <div class="stat">
          <div class="stat-ico red">✕</div>
          <div class="stat-num">{{ cancelledCount }}</div>
          <div class="stat-label muted">Cancelled</div>
        </div>
      </div>

      <div class="dash-grid">
        <div class="panel">
          <div class="panel-head">
            <div>
              <div class="panel-title">Upcoming Reservations</div>
            </div>
            <RouterLink class="small-link" to="/student/reservations">View All →</RouterLink>
          </div>

          <ErrorBanner v-if="bookings.error" :message="bookings.error" />
          <SkeletonList v-else-if="bookings.loading" />

          <div v-else class="list">
            <div v-for="b in upcoming" :key="b.id" class="rowitem">
              <div class="rowleft">
                <div class="rowtitle">{{ rooms.byId(b.roomId)?.name || b.roomId }}</div>
                <div class="rowmeta muted">{{ b.date }} · {{ b.startTime }}–{{ b.endTime }}</div>
              </div>
              <span class="badge ok">Confirmed</span>
            </div>
            <div v-if="upcoming.length === 0" class="emptyline muted">No confirmed reservations yet.</div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-head">
            <div class="panel-title">Featured Study Rooms</div>
            <RouterLink class="small-link" to="/student/rooms">View All →</RouterLink>
          </div>

          <ErrorBanner v-if="rooms.error" :message="rooms.error" />
          <SkeletonList v-else-if="rooms.loading" />

          <div v-else class="feat">
            <div v-for="r in featuredRooms" :key="r.id" class="featrow">
              <div class="thumb">
                <img v-if="r.imageUrl" class="thumb-img" :src="r.imageUrl" :alt="r.name" />
              </div>
              <div class="featmeta">
                <div class="rowtitle">{{ r.name }}</div>
                <div class="rowmeta muted">Capacity: {{ r.capacity }} · {{ r.location }}</div>
              </div>
              <RouterLink class="btn btn-mini btn-primary" :to="`/student/reserve/${r.id}`">Book</RouterLink>
            </div>
          </div>
        </div>

        <div class="panel wide">
          <div class="panel-title">Notifications</div>
          <div class="note note-blue">
            <div class="note-title">Reservation Confirmed</div>
            <div class="muted">Your booking for a study room may appear here after admin approval.</div>
          </div>
          <div class="note note-amber">
            <div class="note-title">Pending Approval</div>
            <div class="muted">Some reservations require admin approval before they become confirmed.</div>
          </div>
        </div>
      </div>
    </section>
  </PortalShell>
</template>
