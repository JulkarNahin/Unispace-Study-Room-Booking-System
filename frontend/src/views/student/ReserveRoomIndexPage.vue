<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../../stores/auth'
import { useRoomsStore } from '../../stores/rooms'

const auth = useAuthStore()
const rooms = useRoomsStore()
const router = useRouter()

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'student') {
    router.replace('/student/login')
    return
  }
  if (rooms.items.length === 0) await rooms.load()
  const first = rooms.activeRooms[0]
  if (first) router.replace(`/student/reserve/${first.id}`)
  else router.replace('/student/rooms')
})
</script>

<template>
  <div class="page-center">
    <div class="muted">Loading…</div>
  </div>
</template>

