<template>
  <div class="dashboard">
    <div class="row g-4">
      <!-- Bienvenida -->
      <div class="col-12">
        <div class="card border-0 shadow-sm" :style="{ background: `linear-gradient(135deg, ${themeColor} 0%, ${themeColorEnd} 100%)` }">
          <div class="card-body text-white py-4">
            <h2 class="mb-1">Bienvenido, {{ usuarioNombre }}</h2>
            <p class="mb-0 opacity-75">{{ fechaActual }}</p>
          </div>
        </div>
      </div>

      <!-- Estadísticas Rápidas -->
      <div class="col-12">
        <h5 class="text-muted mb-3">Resumen del Sistema</h5>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                  <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
              </div>
              <div>
                <div class="text-muted small">Total Alumnos</div>
                <div class="h3 mb-0">{{ totalAlumnos }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success bg-opacity-10 rounded-circle p-3 me-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                  <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
              </div>
              <div>
                <div class="text-muted small">Clubs Activos</div>
                <div class="h3 mb-0">{{ clubsActivos }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning bg-opacity-10 rounded-circle p-3 me-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                  <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                </svg>
              </div>
              <div>
                <div class="text-muted small">Monitores</div>
                <div class="h3 mb-0">{{ totalMonitores }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger bg-opacity-10 rounded-circle p-3 me-3">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
                  <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                </svg>
              </div>
              <div>
                <div class="text-muted small">Período Actual</div>
                <div class="h6 mb-0 fw-bold text-primary">{{ periodoNombre }}</div>
                <div class="small text-muted">{{ periodoFechas }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Clubes con más detalle -->
      <div class="col-12 mt-2">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="text-muted mb-0">Clubes del Periodo</h5>
          <button v-if="isSuperAdmin" class="btn btn-success" @click="abrirNuevoClub">
            + Agregar club
          </button>
        </div>
      </div>

      <div
        v-for="club in clubsVisibles"
        :key="club.id"
        :class="clubSeleccionado?.id === club.id ? 'col-12' : 'col-md-6 col-lg-4'"
        class="club-column"
      >
        <div
          class="card shadow-sm club-card"
          :class="{ 'club-card-selected': clubSeleccionado?.id === club.id, 'border-0': clubSeleccionado?.id !== club.id }"
          role="button"
          tabindex="0"
          @click="seleccionarClub(club)"
          @keydown.enter="seleccionarClub(club)"
        >
          <div
            class="card-header text-white"
            :style="{ backgroundColor: club.tipo === 'DEPORTIVO' ? themeColor : '#64748b' }"
          >
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-bold">{{ club.nombre }}</span>
              <div class="d-flex align-items-center gap-2">
                <span v-if="clubSeleccionado?.id === club.id" class="badge selected-badge">Seleccionado</span>
                <span class="badge bg-light text-dark">{{ club.tipo }}</span>
              </div>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted small club-description">{{ club.descripcion || 'Sin descripción registrada.' }}</p>
            <div class="row text-center">
              <div class="col-6">
                <div class="h4 mb-0">{{ club.ocupados }}</div>
                <div class="small text-muted">Inscritos</div>
              </div>
              <div class="col-6">
                <div class="h4 mb-0">{{ club.cupo }}</div>
                <div class="small text-muted">Cupo</div>
              </div>
            </div>
            <div class="progress mt-3" style="height: 8px;">
              <div
                class="progress-bar"
                :class="club.ocupados >= club.cupo ? 'bg-danger' : 'bg-success'"
                :style="{ width: (club.cupo > 0 ? (club.ocupados / club.cupo * 100) : 0) + '%' }"
              ></div>
            </div>
            <div class="text-center mt-2">
              <small class="text-muted">
                {{ club.ocupados >= club.cupo ? 'Cupo lleno' : `${club.cupo - club.ocupados} lugares disponibles` }}
              </small>
            </div>
            <Transition name="action-buttons">
            <div v-if="isSuperAdmin && clubSeleccionado?.id === club.id" class="d-flex gap-2 mt-3" @click.stop>
              <button class="btn btn-warning btn-sm flex-grow-1" @click.stop="abrirEditarClub(club)">Editar club</button>
              <button class="btn btn-danger btn-sm flex-grow-1" @click.stop="eliminarClubDirecto(club)">Eliminar club</button>
            </div>
            </Transition>
          </div>
        </div>

        <Transition name="club-expand">
          <div v-if="clubSeleccionado?.id === club.id" class="card border-0 shadow-sm club-detail mt-3" @click.stop>
            <div class="card-header text-white d-flex justify-content-between align-items-center" :style="{ backgroundColor: themeColor }">
              <div><strong>{{ club.nombre }}</strong><span class="ms-2 opacity-75">Lista y asistencias</span></div>
              <button class="btn btn-sm btn-light" @click.stop="cerrarClub">Cerrar</button>
            </div>
            <div class="card-body">
              <div v-if="cargandoDetalle" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando asistencias...</div>
              <div v-else-if="errorDetalle" class="alert alert-danger mb-0">{{ errorDetalle }}</div>
              <div v-else class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead><tr><th>Alumno</th><th>Número de control</th><th v-for="fecha in detalleClub.fechas" :key="fecha" class="text-center">{{ fechaCorta(fecha) }}</th><th class="text-center">Faltas</th><th>Estado</th></tr></thead>
                  <tbody>
                    <tr v-for="alumno in detalleClub.alumnos" :key="alumno.id"><td>{{ nombreAlumno(alumno) }}</td><td>{{ alumno.numeroControl }}</td><td v-for="fecha in detalleClub.fechas" :key="fecha" class="text-center"><span :class="asistio(alumno.id, fecha) ? 'text-success' : 'text-danger'">{{ asistio(alumno.id, fecha) ? '✓' : '✕' }}</span></td><td class="text-center fw-bold">{{ faltasAlumno(alumno.id) }}</td><td><span class="badge" :class="faltasAlumno(alumno.id) < 3 ? 'bg-success' : 'bg-danger'">{{ faltasAlumno(alumno.id) < 3 ? 'Acreditado' : 'No acreditado' }}</span></td></tr>
                    <tr v-if="!detalleClub.alumnos.length"><td :colspan="detalleClub.fechas.length + 4" class="text-center text-muted py-4">No hay alumnos inscritos en este club.</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </Transition>
      </div>

      <!-- El gestor solo aporta los modales. La lista principal son las tarjetas de arriba. -->
      <ClubsR ref="gestorClubs" :clubs="clubs" :alumnos="alumnos" :can-manage="isSuperAdmin" :modal-only="true" @add-club="reenviarAgregar" @edit-club="reenviarEditar" @delete-club="reenviarEliminar" @refresh="$emit('refresh')" @log="$emit('log', $event)" @show-error="$emit('show-error', $event)" />

      <!-- Acciones rápidas -->
      <div class="col-12 mt-2">
        <h5 class="text-muted mb-3">Acciones Rápidas</h5>
      </div>

      <div v-if="isSuperAdmin" class="col-md-3">
        <button class="btn btn-outline-primary w-100 py-3" @click="$emit('navigate', 'Gestion')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
          Gestionar Usuarios
        </button>
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-success w-100 py-3" @click="volverAClubs">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
          Clubs
        </button>
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-info w-100 py-3" @click="$emit('navigate', 'Evaluaciones')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
          </svg>
          Evaluaciones
        </button>
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-warning w-100 py-3" @click="$emit('navigate', 'AlumnosSR')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
            <path d="M15 4c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4v2h2v-2h9v2h2V6c0-1.1-.9-2-2-2zm-4 10c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm5-10c0-1.66 1.34-3 3-3s3 1.34 3 3v1h-6V4z"/>
          </svg>
          Alumnos sin Club
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import ClubsR from './ClubsR.vue';
import { getAsistenciasPorClub } from '../services/api';

export default {
  name: "Dashboard",
  components: { ClubsR },
  props: ["clubs", "usuarios", "alumnos", "periodoActivo", "usuarioActual"],
  emits: ["navigate", "add-club", "edit-club", "delete-club", "refresh", "log", "show-error"],
  data() {
    return { clubSeleccionado: null, detalleClub: { fechas: [], alumnos: [], asistencias: {} }, cargandoDetalle: false, errorDetalle: '' };
  },
  computed: {
    rolActual() { return this.usuarioActual?.rol || this.usuarioActual?.tipo || 'ADMIN'; },
    isSuperAdmin() { return this.rolActual === 'SUPERADMIN'; },
    themeColor() { return this.rolActual === 'ADMIN' ? '#4c38ff' : '#080A4C'; },
    themeColorEnd() { return this.rolActual === 'ADMIN' ? '#3927d8' : '#050630'; },
    usuarioNombre() {
      const nombre = this.usuarioActual?.nombre || "Usuario";
      const apellidoP = this.usuarioActual?.apellidoP || "";
      return `${nombre} ${apellidoP}`.trim() || "Usuario";
    },
    fechaActual() {
      const now = new Date();
      const dias = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
      const meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      return `${dias[now.getDay()]}, ${now.getDate()} de ${meses[now.getMonth()]} de ${now.getFullYear()}`;
    },
    totalAlumnos() {
      if (!this.alumnos || !Array.isArray(this.alumnos)) return 0;
      return this.alumnos.length;
    },
    clubsActivos() {
      if (!this.clubs || !Array.isArray(this.clubs)) return 0;
      return this.clubs.length;
    },
    totalMonitores() {
      if (!this.usuarios || !Array.isArray(this.usuarios)) return 0;
      return this.usuarios.filter(u => u.tipo === "MONITOR").length;
    },
    periodoData() {
      return this.periodoActivo || null;
    },
    periodoNombre() {
      return this.periodoData?.nombre || "Sin período";
    },
    periodoFechas() {
      if (!this.periodoData?.fecha_inicio || !this.periodoData?.fecha_fin) {
        return "Sin fechas";
      }
      const inicio = this.formatearFecha(this.periodoData.fecha_inicio);
      const fin = this.formatearFecha(this.periodoData.fecha_fin);
      return `${inicio} — ${fin}`;
    },
    clubsOrdenados() {
      if (!this.clubs) return [];
      const tipoOrden = { "DEPORTIVO": 1, "CULTURAL": 2 };
      return [...this.clubs].sort((a, b) => {
        const diff = (tipoOrden[a.tipo] || 3) - (tipoOrden[b.tipo] || 3);
        return diff !== 0 ? diff : (a.nombre || "").localeCompare(b.nombre || "");
      });
    },
    clubsVisibles() {
      if (!this.clubSeleccionado) return this.clubsOrdenados;
      const seleccionado = this.clubsOrdenados.find(club => club.id === this.clubSeleccionado.id);
      return seleccionado ? [seleccionado] : [];
    }
  },
  methods: {
    abrirNuevoClub() {
      if (!this.isSuperAdmin) return;
      this.$nextTick(() => this.$refs.gestorClubs?.openModal());
    },
    abrirEditarClub(club) {
      if (!this.isSuperAdmin) return;
      this.$nextTick(() => this.$refs.gestorClubs?.startEdit(club));
    },
    volverAClubs() {
      if (typeof window !== 'undefined') window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    eliminarClubDirecto(club) {
      if (!this.isSuperAdmin) return;
      if (window.confirm(`¿Seguro que deseas eliminar el club "${club.nombre}"?`)) {
        this.$emit('delete-club', club.id, 'Super Administrador');
      }
    },
    reenviarAgregar(...args) { this.$emit('add-club', ...args); },
    reenviarEditar(...args) { this.$emit('edit-club', ...args); },
    reenviarEliminar(...args) { this.$emit('delete-club', ...args); },
    async seleccionarClub(club) {
      if (this.clubSeleccionado?.id === club.id) {
        this.cerrarClub();
        return;
      }
      this.clubSeleccionado = club; this.cargandoDetalle = true; this.errorDetalle = '';
      try { this.detalleClub = await getAsistenciasPorClub(club.id); } catch (error) { this.errorDetalle = error.message || 'No se pudieron cargar las asistencias'; } finally { this.cargandoDetalle = false; }
    },
    cerrarClub() {
      this.clubSeleccionado = null;
      this.errorDetalle = '';
    },
    nombreAlumno(alumno) { return `${alumno.nombre || ''} ${alumno.apellidoP || ''} ${alumno.apellidoM || ''}`.replace(/\s+/g, ' ').trim(); },
    asistio(id, fecha) { return Boolean(this.detalleClub.asistencias?.[id]?.[fecha]); },
    faltasAlumno(id) { return this.detalleClub.fechas.filter(fecha => !this.asistio(id, fecha)).length; },
    fechaCorta(fecha) { const parts = String(fecha).split('-'); return parts.length === 3 ? `${parts[2]}/${parts[1]}` : fecha; },
    formatearFecha(fecha) {
      if (!fecha) return '-';
      const opts = { day: 'numeric', month: 'long', year: 'numeric' };
      try {
        const dateObj = typeof fecha === 'string' ? new Date(fecha) : fecha;
        return dateObj.toLocaleDateString('es-MX', opts);
      } catch (e) {
        return '-';
      }
    }
  }
};
</script>

<style scoped>
.dashboard {
  padding: 0;
}
.stat-icon {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.stat-icon svg {
  fill: white !important;
}
.card {
  border-radius: 12px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.club-card { cursor: pointer; }
.club-card:focus { outline: 3px solid rgba(76, 56, 255, 0.3); }
.club-card-selected {
  border: 4px solid rgba(76, 56, 255, 0.42) !important;
  box-shadow: 0 0 0 5px rgba(76, 56, 255, 0.08), 0 12px 28px rgba(76, 56, 255, 0.18) !important;
}
.selected-badge {
  color: #4c38ff;
  background: #ffffff;
  border: 1px solid rgba(76, 56, 255, 0.25);
}
.action-buttons-enter-active,
.action-buttons-leave-active { transition: opacity 0.2s ease, transform 0.25s ease; }
.action-buttons-enter-from,
.action-buttons-leave-to { opacity: 0; transform: translateY(-8px); }
.club-detail { scroll-margin-top: 1rem; }
.club-column { transition: width 0.3s ease, flex-basis 0.3s ease; }
.club-expand-enter-active,
.club-expand-leave-active {
  overflow: hidden;
  transition: max-height 0.35s ease, opacity 0.25s ease, transform 0.35s ease;
}
.club-expand-enter-from,
.club-expand-leave-to {
  max-height: 0;
  opacity: 0;
  transform: scaleY(0.96);
  transform-origin: top;
}
.club-expand-enter-to,
.club-expand-leave-from {
  max-height: 1200px;
  opacity: 1;
  transform: scaleY(1);
  transform-origin: top;
}
.club-description { min-height: 2.5rem; }
.btn {
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.2s ease;
}
.btn:hover {
  transform: translateY(-2px);
}
.btn-outline-primary {
  color: #080A4C;
  border-color: #080A4C;
}
.btn-outline-primary:hover {
  background-color: #080A4C;
  color: white;
}
.text-primary {
  color: #080A4C !important;
}
.bg-primary {
  background-color: #080A4C !important;
}
.progress {
  border-radius: 4px;
  background: #e9ecef;
}
.progress-bar {
  transition: width 0.3s ease;
}

@media (max-width: 1366px) {
  .dashboard .row { --bs-gutter-x: 1.15rem; --bs-gutter-y: 1.15rem; }
  .stat-icon { width: 48px; height: 48px; padding: 0.7rem !important; }
}

@media (max-width: 900px) {
  .dashboard { width: 100%; }
  .club-column { width: 100%; }
  .club-detail .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .club-detail table { min-width: 720px; }
}

@media (max-width: 576px) {
  .dashboard > .row { --bs-gutter-x: 0.75rem; --bs-gutter-y: 0.85rem; }
  .dashboard h2 { font-size: 1.35rem; }
  .dashboard .card-body.text-white { padding: 1.15rem !important; }
  .dashboard .col-md-3 .card-body { padding: 0.9rem; }
  .stat-icon { width: 44px; height: 44px; margin-right: 0.75rem !important; }
  .club-card .card-header, .club-detail .card-header { padding: 0.75rem; }
  .club-description { min-height: 0; }
  .club-card-selected { border-width: 3px !important; }
  .action-buttons-enter-active + * { clear: both; }
  .club-card .d-flex.gap-2.mt-3 { flex-direction: column; }
  .club-card .d-flex.gap-2.mt-3 .btn { width: 100%; }
  .selected-badge { display: none; }
}
</style>
