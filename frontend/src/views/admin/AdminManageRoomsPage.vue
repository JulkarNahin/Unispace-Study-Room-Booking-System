<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import PortalShell from '../../layouts/PortalShell.vue'
import { adminNav } from '../../layouts/nav'
import ErrorBanner from '../../components/ErrorBanner.vue'
import { useAuthStore } from '../../stores/auth'
import { useRoomsStore } from '../../stores/rooms'

const auth = useAuthStore()
const router = useRouter()
const rooms = useRoomsStore()

const createOpen = ref(false)
const form = reactive({
  name: '',
  location: '',
  capacity: 4,
  featuresText: 'Whiteboard, Power',
  active: true,
})

onMounted(async () => {
  if (!auth.isAuthed || auth.role !== 'admin') {
    router.replace('/admin/login')
    return
  }
  await rooms.load()
})

const parsedFeatures = computed(() =>
  form.featuresText
    .split(',')
    .map((x) => x.trim())
    .filter(Boolean)
    .slice(0, 6)
)

async function submit() {
  try {
    await rooms.create({
      name: form.name,
      location: form.location,
      capacity: Number(form.capacity),
      features: parsedFeatures.value,
      active: form.active,
    })
    form.name = ''
    form.location = ''
    form.capacity = 4
    form.featuresText = 'Whiteboard, Power'
    form.active = true
    createOpen.value = false
  } catch {}
}

async function toggle(id: string, active: boolean) {
  try {
    await rooms.update(id, { active: !active })
  } catch {}
}

async function remove(id: string) {
  const ok = confirm('Delete this room?')
  if (!ok) return
  try {
    await rooms.remove(id)
  } catch {}
}
</script>

<template>
  <PortalShell portal="admin" :nav="adminNav" accent="red">
    <section class="page">
      <div class="page-head2">
        <div>
          <h1 class="page-title">Manage Rooms</h1>
          <p class="muted">Create, enable/disable, and delete rooms</p>
        </div>
        <button class="btn btn-danger" type="button" @click="createOpen = !createOpen">
          {{ createOpen ? 'Close' : 'New Room' }}
        </button>
      </div>

      <ErrorBanner v-if="rooms.error" :message="rooms.error" />

      <div v-if="createOpen" class="panel">
        <div class="panel-title">Create Room</div>
        <form class="form" @submit.prevent="submit">
          <label class="field">
            <span class="label">Name</span>
            <input v-model="form.name" class="input" type="text" minlength="3" maxlength="80" required />
          </label>
          <label class="field">
            <span class="label">Location</span>
            <input v-model="form.location" class="input" type="text" minlength="2" maxlength="80" required />
          </label>
          <label class="field">
            <span class="label">Capacity</span>
            <input v-model.number="form.capacity" class="input" type="number" min="1" max="50" required />
          </label>
          <label class="field">
            <span class="label">Features</span>
            <input v-model="form.featuresText" class="input" type="text" maxlength="200" />
          </label>
          <label class="field">
            <span class="label">Active</span>
            <select v-model="form.active" class="input">
              <option :value="true">true</option>
              <option :value="false">false</option>
            </select>
          </label>
          <button class="btn btn-danger wide" type="submit" :disabled="rooms.saving">{{ rooms.saving ? 'Saving…' : 'Save' }}</button>
        </form>
      </div>

      <div class="room-grid">
        <article v-for="r in rooms.items" :key="r.id" class="room-card">
          <div class="room-img">
             <img v-if="r.imageUrl" class="room-photo" :src="r.imageUrl" :alt="r.name" />
             <span class="room-badge" :class="r.active ? 'avail' : 'unavail'">
               {{ r.active ? 'Active' : 'Inactive' }}
             </span>
          </div>
          <div class="room-body">
            <div class="room-name">{{ r.name }}</div>
            <div class="room-meta muted">{{ r.location }} · Capacity: {{ r.capacity }}</div>
            <div class="chiprow">
              <span v-for="f in r.features.slice(0, 4)" :key="f" class="chip">{{ f }}</span>
            </div>
            <div class="btnrow">
              <button class="btn btn-mini btn-outline" type="button" @click="toggle(r.id, r.active)">{{ r.active ? 'Disable' : 'Enable' }}</button>
              <button class="btn btn-mini btn-outline-danger" type="button" @click="remove(r.id)">Delete</button>
            </div>
          </div>
        </article>
      </div>
    </section>
  </PortalShell>
</template>

