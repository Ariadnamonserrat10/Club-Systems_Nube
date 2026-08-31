<template><LegacyCrearCuenta /></template>

<script setup lang="ts">
import LegacyCrearCuenta from '../../src/Pages/CrearC.vue'
definePageMeta({ requiresAuth: true, roles: ['SUPERADMIN'] })
</script>
