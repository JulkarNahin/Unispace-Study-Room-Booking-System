<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import AuthCard from '../../layouts/AuthCard.vue'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('admin@university.edu')
const password = ref('')

function submit() {
  auth.login(email.value, 'admin')
  router.push('/admin/dashboard')
}
</script>

<template>
  <AuthCard
    title="UniSpace – Admin Login"
    subtitle="Authorized personnel only"
    accent="red"
    icon="admin"
    footer-link-text="← Back to Student Login"
    footer-link-to="/student/login"
  >
    <form class="form auth-form" @submit.prevent="submit">
      <label class="field">
        <span class="label">Admin Email</span>
        <div class="input-ico">
          <span aria-hidden="true">✉</span>
          <input v-model="email" class="input" type="email" required />
        </div>
      </label>

      <label class="field">
        <span class="label">Password</span>
        <div class="input-ico">
          <span aria-hidden="true">🔒</span>
          <input v-model="password" class="input" type="password" placeholder="Enter admin password" required />
        </div>
      </label>

      <button class="btn btn-danger wide" type="submit">Admin Login →</button>
    </form>
  </AuthCard>
</template>

