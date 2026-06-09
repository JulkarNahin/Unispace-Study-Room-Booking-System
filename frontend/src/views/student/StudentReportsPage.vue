<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { studentNav } from '../../layouts/nav'
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

const csv = computed(() => {
  const header = ['Room', 'Date', 'Start', 'End', 'Status', 'Purpose'].join(',')
  const lines = bookings.items.map((b) => {
    const room = rooms.byId(b.roomId)?.name || b.roomId
    const cols = [room, b.date, b.startTime, b.endTime, b.status, b.purpose || '']
    return cols.map((x) => `"${String(x).replaceAll('"', '""')}"`).join(',')
  })
  return [header, ...lines].join('\n')
})

function download() {
  const blob = new Blob([csv.value], { type: 'text/csv;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = 'unispace-student-report.csv'
  a.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <PortalShell portal="student" :nav="studentNav" accent="blue">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Reports</h1>
          <p class="muted">Generate a simple CSV report of your booking history</p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-title">Booking Report</div>
        <p class="muted">Includes room, date/time, status, and purpose.</p>
        <button class="btn btn-primary" type="button" @click="download">Generate Report</button>
      </div>
    </section>
  </PortalShell>
</template>

