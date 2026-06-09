<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { adminNav } from '../../layouts/nav'
import ErrorBanner from '../../components/ErrorBanner.vue'
import SkeletonList from '../../components/SkeletonList.vue'
import { useAuthStore } from '../../stores/auth'
import { useBookingsStore } from '../../stores/bookings'

const auth = useAuthStore()
const router = useRouter()
const bookings = useBookingsStore()

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'admin') {
    router.replace('/admin/login')
    return
  }
  await bookings.load()
})

const students = computed(() => {
  const map = new Map<string, { name: string; total: number; pending: number; confirmed: number }>()
  for (const b of bookings.items) {
    if (b.userRole !== 'student') continue
    const key = b.userName
    const cur = map.get(key) || { name: key, total: 0, pending: 0, confirmed: 0 }
    cur.total++
    if (b.status === 'pending') cur.pending++
    if (b.status === 'confirmed') cur.confirmed++
    map.set(key, cur)
  }
  return [...map.values()].sort((a, b) => b.total - a.total)
})
</script>

<template>
  <PortalShell portal="admin" :nav="adminNav" accent="red">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Manage Students</h1>
          <p class="muted">Student list is derived from booking records (demo)</p>
        </div>
      </div>

      <ErrorBanner v-if="bookings.error" :message="bookings.error" />
      <SkeletonList v-else-if="bookings.loading" />

      <div v-else class="table cardlike">
        <div class="trow thead muted">
          <div>Student</div>
          <div>Total</div>
          <div>Pending</div>
          <div>Confirmed</div>
        </div>
        <div v-for="s in students" :key="s.name" class="trow">
          <div>{{ s.name }}</div>
          <div>{{ s.total }}</div>
          <div>{{ s.pending }}</div>
          <div>{{ s.confirmed }}</div>
        </div>
      </div>
    </section>
  </PortalShell>
</template>

