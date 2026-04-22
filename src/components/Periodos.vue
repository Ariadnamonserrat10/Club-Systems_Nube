<template>
  <div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="m-0 text-dark">Gestión de Periodos Escolares</h3>
    </div>
    
    <!-- Card de Periodo Activo -->
    <div class="row mb-4">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card border-0 shadow-sm" style="background-color: #f8f9fa; border-left: 5px solid #12343b !important;">
          <div class="card-body p-4">
            <h5 class="text-uppercase fw-bold mb-3" style="color: #12343b; letter-spacing: 1px;">Periodo en Curso</h5>
            <div v-if="periodoActivo">
              <div class="mb-2">
                <span class="text-muted small d-block">NOMBRE DEL PERIODO</span>
                <span class="fs-5 fw-semibold">{{ periodoActivo.nombre }}</span>
              </div>
              <div class="mb-3">
                <span class="text-muted small d-block">VIGENCIA</span>
                <span class="fs-6">{{ formatearFecha(periodoActivo.fecha_inicio) }} — {{ formatearFecha(periodoActivo.fecha_fin) }}</span>
              </div>
              <button 
                class="btn btn-danger px-4 py-2 fw-bold" 
                @click="mostrarModalConfirmacion = true"
                :disabled="procesando"
              >
                {{ procesando ? 'Procesando...' : 'FINALIZAR PERIODO ACTUAL' }}
              </button>
            </div>
            <div v-else class="py-3">
              <p class="text-muted mb-0">No se ha detectado ningún periodo con estado ACTIVO.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabla de Historial -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3 border-bottom">
        <h5 class="m-0 fw-bold text-secondary text-uppercase small" style="letter-spacing: 1px;">Historial de Ciclos</h5>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
            <thead style="background-color: #f1f3f5;">
              <tr>
                <th class="ps-4 py-3 text-secondary text-uppercase small">ID</th>
                <th class="py-3 text-secondary text-uppercase small">Nombre del Ciclo</th>
                <th class="py-3 text-secondary text-uppercase small">Fecha Inicio</th>
                <th class="py-3 text-secondary text-uppercase small">Fecha Fin</th>
                <th class="pe-4 py-3 text-secondary text-uppercase small text-center">Estado</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in periodos" :key="p.id">
                <td class="ps-4 fw-bold text-muted">#{{ p.id }}</td>
                <td>{{ p.nombre }}</td>
                <td>{{ p.fecha_inicio }}</td>
                <td>{{ p.fecha_fin }}</td>
                <td class="pe-4 text-center">
                  <span class="badge rounded-pill px-3 py-2" :class="p.estado === 'ACTIVO' ? 'bg-success' : 'bg-light text-secondary border'">
                    {{ p.estado }}
                  </span>
                </td>
              </tr>
              <tr v-if="periodos.length === 0">
                <td colspan="5" class="text-center py-5 text-muted">No existen registros históricos de periodos.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmación Estático (Bootstrap) -->
    <div v-if="mostrarModalConfirmacion" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-danger text-white border-0">
            <h5 class="modal-title fw-bold">Confirmar Cierre de Periodo</h5>
            <button type="button" class="btn-close btn-close-white" @click="mostrarModalConfirmacion = false"></button>
          </div>
          <div class="modal-body p-4">
            <p class="mb-3">Estás a punto de cerrar el periodo <strong>{{ periodoActivo?.nombre }}</strong>.</p>
            <div class="alert alert-warning border-0">
              <h6 class="alert-heading fw-bold">¿Qué sucederá al cerrar?</h6>
              <ul class="mb-0 small">
                <li>Se evaluarán las asistencias de todos los alumnos.</li>
                <li>Los alumnos con 3 o más faltas quedarán como "REPROBADOS".</li>
                <li>Se guardará la configuración actual para registros históricos.</li>
                <li>Se generará automáticamente el siguiente ciclo escolar.</li>
              </ul>
            </div>
            <p class="text-danger fw-bold mb-0 small mt-3">¡Esta acción es definitiva y no se puede revertir!</p>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-outline-secondary px-4" @click="mostrarModalConfirmacion = false">Cancelar</button>
            <button type="button" class="btn btn-danger px-4 fw-bold" @click="cerrarPeriodo" :disabled="procesando">
              SI, CERRAR PERIODO
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { BACKEND } from '../services/backend';

export default {
  name: 'Periodos',
  data() {
    return {
      periodos: [],
      periodoActivo: null,
      procesando: false,
      mostrarModalConfirmacion: false
    };
  },
  mounted() {
    this.cargarPeriodos();
  },
  methods: {
    formatearFecha(fecha) {
      if (!fecha) return '-';
      const ops = { day: '2-digit', month: 'long', year: 'numeric' };
      return new Date(fecha).toLocaleDateString('es-MX', ops);
    },
    async cargarPeriodos() {
      try {
        const [resActivo, resTodos] = await Promise.all([
          axios.get(`${BACKEND}/Periodos.php?action=activo`),
          axios.get(`${BACKEND}/Periodos.php`)
        ]);

        if (resActivo.data.status === 'success' && resActivo.data.data) {
          this.periodoActivo = resActivo.data.data;
        } else {
          this.periodoActivo = null;
        }

        if (resTodos.data.status === 'success') {
          this.periodos = resTodos.data.data;
        }
      } catch (error) {
        this.$emit("show-error", "Error al cargar los periodos");
      }
    },
    async cerrarPeriodo() {
      this.procesando = true;
      this.mostrarModalConfirmacion = false;
      try {
        const res = await axios.post(`${BACKEND}/Periodos.php?action=cerrar`);
        if (res.data.status === 'success') {
          this.$emit("log", {
            accion: "Cerrar",
            tipo: "periodo",
            descripcion: `Se finalizó el ciclo ${this.periodoActivo.nombre}`
          });
          this.$emit("show-toast", "El periodo se ha cerrado correctamente y se generó el nuevo ciclo.");
          await this.cargarPeriodos();
          this.$emit("request-reload-alumnos");
          this.$emit("refresh");
        } else {
          throw new Error(res.data.message || "No se pudo cerrar el periodo.");
        }
      } catch (error) {
        this.$emit("show-error", "Error crítico: " + (error.response?.data?.message || error.message));
      } finally {
        this.procesando = false;
      }
    }
  }
}
</script>

<style scoped>
/* Eliminar transiciones de botones y tablas para que sea estático */
.btn, .table, .card, .badge {
  transition: none !important;
}
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.03) !important;
}
</style>
