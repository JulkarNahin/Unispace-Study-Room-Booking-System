<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'

import AuthCard from '../../layouts/AuthCard.vue'
import { useAuthStore } from '../../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('ahmad.razak@university.edu')
const password = ref('')

function submit() {
  auth.login(email.value, 'student')
  router.push('/student/dashboard')
}
</script>

<template>
  <AuthCard
    title="UniSpace – Student Login"
    subtitle="Access your study room reservation portal"
    accent="blue"
    icon="student"
    footer-link-text="Admin Portal →"
    footer-link-to="/admin/login"
  >
    <form class="form auth-form" @submit.prevent="submit">
      <label class="field">
        <span class="label">Student Email</span>
        <div class="input-ico">
          <span aria-hidden="true">✉</span>
          <input v-model="email" class="input" type="email" required />
        </div>
      </label>

      <label class="field">
        <span class="label">Password</span>
        <div class="input-ico">
          <span aria-hidden="true">🔒</span>
          <input v-model="password" class="input" type="password" placeholder="Enter your password" required />
        </div>
      </label>

      <button class="btn btn-primary wide" type="submit">Login →</button>
    </form>
  </AuthCard>
</template>

