<template>
  <div>
    <h4 class="text-primary mb-4">Auditoría del Sistema</h4>
    <p class="text-muted mb-4">Registro completo de todas las acciones realizadas en el sistema.</p>

    <!-- Filtros -->
    <div class="row mb-4">
      <div class="col-md-3">
        <select v-model="filtroTipo" class="form-control">
          <option value="">Todos los tipos</option>
          <option value="usuario">Usuarios</option>
          <option value="club">Clubs</option>
          <option value="alumno">Alumnos</option>
          <option value="periodo">Períodos</option>
          <option value="asistencia">Asistencias</option>
          <option value="evaluacion">Evaluaciones</option>
          <option value="inscripcion">Inscripciones</option>
          <option value="sistema">Sistema</option>
        </select>
      </div>
      <div class="col-md-3">
        <select v-model="filtroAccion" class="form-control">
          <option value="">Todas las acciones</option>
          <option value="CREAR">Crear</option>
          <option value="EDITAR">Editar</option>
          <option value="ELIMINAR">Eliminar</option>
          <option value="INSCRIBIR">Inscribir</option>
          <option value="CERRAR">Cerrar período</option>
          <option value="INICIAR">Iniciar sesión</option>
        </select>
      </div>
      <div class="col-md-3">
        <input v-model="busqueda" type="text" class="form-control" placeholder="Buscar..." />
      </div>
      <div class="col-md-3 text-end">
        <button class="btn btn-outline-secondary" @click="recargar">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" class="me-1">
            <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
          </svg>
          Actualizar
        </button>
      </div>
    </div>

    <!-- Tabla de Auditoría -->
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th class="py-3 px-4">Fecha y Hora</th>
              <th class="py-3">Usuario</th>
              <th class="py-3">Acción</th>
              <th class="py-3">Tipo</th>
              <th class="py-3">Descripción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(registro, index) in auditoriaFiltrada" :key="index">
              <td class="py-3 px-4">
                <div class="text-nowrap">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="#6c757d" class="me-1">
                    <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                  </svg>
                  {{ formatearFecha(registro.fecha) }}
                </div>
              </td>
              <td class="py-3">
                <span class="badge bg-primary bg-opacity-10 text-primary">
                  {{ registro.usuario || 'Sistema' }}
                </span>
              </td>
              <td class="py-3">
                <span class="badge rounded-pill" :class="claseAccion(registro.accion)">
                  {{ registro.accion }}
                </span>
              </td>
              <td class="py-3">
                <span class="badge bg-secondary rounded-pill">
                  {{ registro.tipo }}
                </span>
              </td>
              <td class="py-3">{{ registro.descripcion }}</td>
            </tr>
            <tr v-if="auditoriaFiltrada.length === 0">
              <td colspan="5" class="text-center py-5 text-muted">
                No hay registros que mostrar.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Paginación simple -->
    <div v-if="totalPaginas > 1" class="d-flex justify-content-between align-items-center mt-3">
      <div class="text-muted small">
        Mostrando {{ auditoriaFiltrada.length }} registros
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" :disabled="paginaActual === 1" @click="paginaActual--">
          Anterior
        </button>
        <span class="btn btn-sm btn-light">Página {{ paginaActual }} de {{ totalPaginas }}</span>
        <button class="btn btn-sm btn-outline-secondary" :disabled="paginaActual === totalPaginas" @click="paginaActual++">
          Siguiente
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { BACKEND } from '../services/backend';

export default {
  name: 'Auditoria',
  props: ['auditoria'],
  data() {
    return {
      filtroTipo: '',
      filtroAccion: '',
      busqueda: '',
      paginaActual: 1,
      registrosPorPagina: 20
    };
  },
  computed: {
    auditoriaLocal() {
      return this.auditoria || [];
    },
    auditoriaFiltrada() {
      let resultado = this.auditoriaLocal;
      
      if (this.filtroTipo) {
        resultado = resultado.filter(r => 
          (r.tipo || '').toLowerCase() === this.filtroTipo.toLowerCase()
        );
      }
      
      if (this.filtroAccion) {
        resultado = resultado.filter(r => 
          (r.accion || '').toUpperCase() === this.filtroAccion.toUpperCase()
        );
      }
      
      if (this.busqueda) {
        const query = this.busqueda.toLowerCase();
        resultado = resultado.filter(r => 
          (r.descripcion || '').toLowerCase().includes(query) ||
          (r.usuario || '').toLowerCase().includes(query)
        );
      }
      
      return resultado;
    },
    totalPaginas() {
      return Math.ceil(this.auditoriaFiltrada.length / this.registrosPorPagina);
    }
  },
  methods: {
    formatearFecha(fecha) {
      if (!fecha) return '-';
      const d = new Date(fecha);
      if (isNaN(d.getTime())) return fecha;
      
      const opts = { 
        day: '2-digit', 
        month: 'short', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      };
      return d.toLocaleDateString('es-MX', opts);
    },
    claseAccion(accion) {
      if (!accion) return 'bg-secondary';
      const acc = accion.toUpperCase();
      if (acc.includes('CREAR') || acc.includes('INSCRIBIR') || acc.includes('REGISTRAR')) {
        return 'bg-success';
      }
      if (acc.includes('EDITAR') || acc.includes('ACTUALIZAR') || acc.includes('MODIFICAR')) {
        return 'bg-warning text-dark';
      }
      if (acc.includes('ELIMINAR') || acc.includes('BORRAR') || acc.includes('CERRAR')) {
        return 'bg-danger';
      }
      if (acc.includes('INICIAR') || acc.includes('LOGIN') || acc.includes('SESION')) {
        return 'bg-info';
      }
      return 'bg-secondary';
    },
    recargar() {
      this.$emit('refresh');
    }
  }
};
</script>

<style scoped>
.text-primary { color: #2d3561 !important; }

.card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
}

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.table thead {
  background: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}

.table thead th {
  padding: 12px 16px;
  text-align: left;
  font-weight: 600;
  color: #495057;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.5px;
}

.table td {
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}

.table tbody tr:hover {
  background: #f8f9ff;
}

.badge {
  padding: 6px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
}

.bg-success { background-color: #28a745 !important; color: #fff !important; }
.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
.bg-danger { background-color: #dc3545 !important; color: #fff !important; }
.bg-info { background-color: #17a2b8 !important; color: #fff !important; }
.bg-secondary { background-color: #6c757d !important; color: #fff !important; }
.bg-primary { background-color: #2d3561 !important; color: #fff !important; }
.bg-light { background-color: #f8f9fa !important; color: #333 !important; }
</style>