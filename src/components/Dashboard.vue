<template>
  <div class="dashboard">
    <div class="row g-4">
      <!-- Bienvenida -->
      <div class="col-12">
        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #080A4C 0%, #050630 100%);">
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
        <h5 class="text-muted mb-3">Clubes del Periodo</h5>
      </div>

      <div
        v-for="club in clubsOrdenados"
        :key="club.id"
        class="col-md-6 col-lg-4"
      >
        <div class="card border-0 shadow-sm h-100">
          <div
            :class="['card-header', 'text-white', club.tipo === 'DEPORTIVO' ? 'bg-primary' : 'bg-secondary']"
          >
            <div class="d-flex justify-content-between align-items-center">
              <span class="fw-bold">{{ club.nombre }}</span>
              <span class="badge bg-light text-dark">{{ club.tipo }}</span>
            </div>
          </div>
          <div class="card-body">
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
          </div>
        </div>
      </div>

      <!-- Acciones rápidas -->
      <div class="col-12 mt-2">
        <h5 class="text-muted mb-3">Acciones Rápidas</h5>
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-primary w-100 py-3" @click="$emit('navigate', 'Gestion')">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" class="me-2">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
          </svg>
          Gestionar Usuarios
        </button>
      </div>

      <div class="col-md-3">
        <button class="btn btn-outline-success w-100 py-3" @click="$emit('navigate', 'ClubsR')">
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
export default {
  name: "Dashboard",
  props: ["clubs", "usuarios", "alumnos", "periodoActivo"],
  emits: ["navigate"],
  methods: {
    formatearFecha(fecha) {
      if (!fecha) return '-';
      let fechaObj = fecha;
      if (typeof fecha === 'object' && fecha !== null) {
        if (fecha.date) fechaObj = fecha.date;
        else if (fecha.val) fechaObj = fecha.val;
        else return '-';
      }
      const opts = { day: 'numeric', month: 'long', year: 'numeric' };
      return new Date(fechaObj).toLocaleDateString('es-MX', opts);
    },
  },
  computed: {
    usuarioNombre() {
      const nombre = this.$parent?.usuarioActual?.nombre || "Usuario";
      const apellidoP = this.$parent?.usuarioActual?.apellidoP || "";
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
    }
  },
  methods: {
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
</style>