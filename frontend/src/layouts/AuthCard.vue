<script setup lang="ts">
defineProps<{
  title: string
  subtitle: string
  accent: 'blue' | 'red'
  icon: 'student' | 'admin'
  footerLinkText: string
  footerLinkTo: string
}>()
</script>

<template>
  <div class="auth-bg" :data-accent="accent">
    <div class="auth-card">
      <div class="auth-icon" aria-hidden="true">
        <svg v-if="icon === 'student'" width="26" height="26" viewBox="0 0 24 24" fill="none">
          <path
            d="M12 3L2 8l10 5 10-5-10-5Z"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linejoin="round"
          />
          <path d="M6 10.5V16c0 1.5 3 3 6 3s6-1.5 6-3v-5.5" stroke="currentColor" stroke-width="1.7" />
        </svg>
        <svg v-else width="26" height="26" viewBox="0 0 24 24" fill="none">
          <path
            d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4Z"
            stroke="currentColor"
            stroke-width="1.7"
            stroke-linejoin="round"
          />
        </svg>
      </div>

      <h1 class="auth-title">{{ title }}</h1>
      <p class="auth-sub muted">{{ subtitle }}</p>

      <div class="auth-slot">
        <slot />
      </div>

      <RouterLink class="auth-footer" :to="footerLinkTo">{{ footerLinkText }}</RouterLink>
      <div class="auth-demo muted">Demo: Use any email/password to login</div>
    </div>
  </div>
</template>

