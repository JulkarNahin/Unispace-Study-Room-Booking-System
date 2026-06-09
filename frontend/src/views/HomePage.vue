<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

onMounted(() => {
  if (!auth.isAuthed) {
    router.replace('/student/login')
    return
  }
  if (auth.role === 'admin') router.replace('/admin/dashboard')
  else router.replace('/student/dashboard')
})
</script>

<template>
  <div class="page-center">
    <div class="muted">Redirecting…</div>
  </div>
</template>
