<template><LegacyMonitor /></template>

<script setup lang="ts">
import LegacyMonitor from '../../src/Pages/Monitor.vue'
definePageMeta({ requiresAuth: true, roles: ['MONITOR'] })
</script>
