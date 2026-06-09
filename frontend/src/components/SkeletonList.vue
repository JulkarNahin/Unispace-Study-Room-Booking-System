<template>
  <div class="room-grid">
    <div v-for="i in 6" :key="i" class="panel skeleton">
      <div class="sk-line w-60" />
      <div class="sk-line w-90" />
      <div class="sk-line w-75" />
      <div class="sk-row">
        <div class="sk-pill w-30" />
        <div class="sk-pill w-22" />
      </div>
    </div>
  </div>
</template>
