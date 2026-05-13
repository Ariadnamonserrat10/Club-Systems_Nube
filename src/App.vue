<template>
  <router-view />
</template>

<script>
export default { name: 'App' }
</script>

<style>
:root {
  --brand: #080A4C;
  --brand-dark: #050630;
  --brand-soft: #eef1ff;
  --brand-alt: #1a237e;
  --text-dark: #091c36;
  --text-muted: #5f6d8a;
  --surface: #f0f4fb;
}

body {
  background: #e7effc;
  color: var(--text-dark);
  font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.btn-primary {
  background-color: var(--brand) !important;
  border-color: var(--brand) !important;
  color: #fff !important;
}

.btn-primary:hover,
.btn-primary:focus,
.btn-primary:active {
  background-color: var(--brand-dark) !important;
  border-color: var(--brand-dark) !important;
}

.btn-outline-primary {
  color: var(--brand) !important;
  border-color: var(--brand) !important;
}

.btn-outline-primary:hover {
  background-color: rgba(15, 23, 110, 0.1) !important;
  color: var(--brand) !important;
}

.bg-primary {
  background-color: var(--brand) !important;
}

.text-primary {
  color: var(--brand) !important;
}

.table-primary thead,
.table-primary {
  background-color: rgba(15, 23, 110, 0.12) !important;
}

.badge.bg-primary {
  background-color: var(--brand) !important;
  color: #fff !important;
}

.alert-primary {
  background-color: rgba(15, 23, 110, 0.08);
  color: var(--brand);
  border-color: rgba(15, 23, 110, 0.2);
}

.nav-link,
.sidebar a {
  color: #fff !important;
}

.nav-link:hover,
.sidebar a:hover {
  background-color: rgba(255, 255, 255, 0.12) !important;
  color: #fff !important;
}
</style>
