<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useAuthStore } from '../stores/auth'

export type NavItem = { label: string; to: string; icon: string }

const props = defineProps<{
  portal: 'student' | 'admin'
  nav: NavItem[]
  accent: 'blue' | 'red'
}>()

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const activePath = computed(() => String(route.path || ''))
const displayName = computed(() => auth.name || (props.portal === 'admin' ? 'Admin' : 'Student'))
const avatarLetter = computed(() => displayName.value.trim().slice(0, 1).toUpperCase() || 'U')

function logout() {
  auth.logout()
  router.push(props.portal === 'admin' ? '/admin/login' : '/student/login')
}
</script>

<template>
  <div class="portal" :data-accent="accent">
    <aside class="sidebar">
      <div class="side-brand">
        <div class="side-logo" aria-hidden="true">{{ portal === 'admin' ? 'A' : 'U' }}</div>
        <div class="side-text">
          <div class="side-title">UniSpace</div>
          <div class="side-sub muted">{{ portal === 'admin' ? 'Admin Portal' : 'Student Portal' }}</div>
        </div>
      </div>

      <nav class="side-nav" aria-label="Sidebar">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          class="side-link"
          :class="{ on: activePath.startsWith(item.to) }"
          :to="item.to"
        >
          <span class="side-ico" aria-hidden="true">
            <span v-if="item.icon === 'dashboard'">▦</span>
            <span v-else-if="item.icon === 'rooms'">▤</span>
            <span v-else-if="item.icon === 'reserve'">＋</span>
            <span v-else-if="item.icon === 'mine'">✓</span>
            <span v-else-if="item.icon === 'history'">≡</span>
            <span v-else-if="item.icon === 'reports'">▣</span>
            <span v-else-if="item.icon === 'reservations'">☰</span>
            <span v-else-if="item.icon === 'students'">👤</span>
            <span v-else>•</span>
          </span>
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="side-user">
        <div class="side-userdot" aria-hidden="true">{{ avatarLetter }}</div>
        <div class="side-usertext">
          <div class="side-username">{{ displayName }}</div>
          <div class="side-usermeta muted">{{ auth.role || portal }}</div>
        </div>
      </div>
    </aside>

    <div class="main">
      <header class="topbar">
        <div class="top-left">
          <div class="top-welcome muted">Welcome back,</div>
          <div class="top-name">{{ displayName }}</div>
        </div>
        <div class="top-actions">
          <button class="iconbtn" type="button" aria-label="Notifications">🔔</button>
          <button class="avatar" type="button" :aria-label="`Account: ${displayName}`">{{ avatarLetter }}</button>
          <button class="linkbtn" type="button" @click="logout">Logout</button>
        </div>
      </header>

      <main class="content">
        <slot />
      </main>
    </div>
  </div>
</template>

