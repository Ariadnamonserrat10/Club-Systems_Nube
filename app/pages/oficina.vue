<template><LegacyOficina /></template>

<script setup lang="ts">
import LegacyOficina from '../../src/Pages/Oficina.vue'
definePageMeta({ requiresAuth: true, roles: ['SUPERADMIN', 'ADMIN', 'OFICINA'] })
</script>
