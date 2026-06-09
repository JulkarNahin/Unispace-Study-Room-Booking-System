<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { adminNav } from '../../layouts/nav'
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

const csv = computed(() => {
  const header = ['Student', 'Room', 'Date', 'Start', 'End', 'Status', 'Purpose'].join(',')
  const lines = bookings.items.map((b) => {
    const room = rooms.byId(b.roomId)?.name || b.roomId
    const cols = [b.userName, room, b.date, b.startTime, b.endTime, b.status, b.purpose || '']
    return cols.map((x) => `"${String(x).replaceAll('"', '""')}"`).join(',')
  })
  return [header, ...lines].join('\n')
})

function download() {
  const blob = new Blob([csv.value], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'unispace-admin-report.csv'
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <PortalShell portal="admin" :nav="adminNav" accent="red">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Reports</h1>
          <p class="muted">Download reservations CSV for reporting</p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-title">Reservations Report</div>
        <p class="muted">Exports all reservations with student, room, date/time, status, and purpose.</p>
        <button class="btn btn-danger" type="button" @click="download">Download Report</button>
      </div>
    </section>
  </PortalShell>
</template>

