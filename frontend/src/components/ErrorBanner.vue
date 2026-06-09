<script setup lang="ts">
defineProps<{ message: string }>()
</script>

<template>
  <div class="banner banner-error" role="alert">
    <strong>Something went wrong.</strong>
    <span class="banner-msg">{{ message }}</span>
  </div>
</template>
