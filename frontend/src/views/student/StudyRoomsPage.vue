<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { studentNav } from '../../layouts/nav'
import ErrorBanner from '../../components/ErrorBanner.vue'
import SkeletonList from '../../components/SkeletonList.vue'
import { useAuthStore } from '../../stores/auth'
import { useRoomsStore } from '../../stores/rooms'

const auth = useAuthStore()
const router = useRouter()
const rooms = useRoomsStore()
const query = ref('')

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'student') {
    router.replace('/student/login')
    return
  }
  await rooms.load()
})

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return rooms.activeRooms
  return rooms.activeRooms.filter((r) => (r.name + ' ' + r.location).toLowerCase().includes(q))
})
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Study Rooms</h1>
          <p class="muted">Browse and book available study rooms</p>
        </div>
      </div>

      <div class="toolbar2">
        <input v-model="query" class="input" type="search" placeholder="Search rooms..." />
      </div>

      <ErrorBanner v-if="rooms.error" :message="rooms.error" />
      <SkeletonList v-else-if="rooms.loading" />

      <div v-else class="room-grid">
        <article v-for="r in filtered" :key="r.id" class="room-card">
          <div class="room-img">
            <img v-if="r.imageUrl" class="room-photo" :src="r.imageUrl" :alt="r.name" />
            <span class="room-badge avail">Available</span>
          </div>

          <div class="room-body">
            <div class="room-name">{{ r.name }}</div>
            <div class="room-meta muted">Capacity: {{ r.capacity }} people</div>
            <div class="chiprow">
              <span v-for="f in r.features.slice(0, 4)" :key="f" class="chip">{{ f }}</span>
            </div>
            <RouterLink class="btn btn-primary wide" :to="`/student/reserve/${r.id}`">Book Room</RouterLink>
          </div>
        </article>
      </div>
    </section>
  </PortalShell>
</template>
