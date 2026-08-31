<template>
  <div class="p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="m-0 text-dark">Proceso de Reinscripción</h3>
        <p class="text-muted small mb-0">Solo alumnos acreditados del periodo anterior aparecen en esta lista.</p>
      </div>
    </div>
    
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-secondary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>
    
    <div v-else>
      <!-- Buscador -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0">
              <i class="bi bi-search text-muted"></i>
            </span>
            <input 
              type="text" 
              class="form-control border-start-0" 
              placeholder="Buscar candidato por nombre o número de control..."
              v-model="busqueda"
              style="box-shadow: none;"
            >
          </div>
        </div>
      </div>

      <!-- Tabla de Candidatos -->
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <h5 class="m-0 fw-bold text-secondary text-uppercase small" style="letter-spacing: 1px;">Alumnos Acreditados Pendientes</h5>
          <span class="badge bg-light text-dark border">{{ candidatosFiltrados.length }} candidatos</span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.95rem;">
              <thead style="background-color: #f1f3f5;">
                <tr>
                  <th class="ps-4 py-3 text-secondary text-uppercase small">Control</th>
                  <th class="py-3 text-secondary text-uppercase small">Nombre Completo</th>
                  <th class="py-3 text-secondary text-uppercase small">Carrera</th>
                  <th class="py-3 text-secondary text-uppercase small text-center">Sem. Anterior</th>
                  <th class="py-3 text-secondary text-uppercase small">Club Anterior</th>
                  <th class="pe-4 py-3 text-secondary text-uppercase small text-end">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="candidato in candidatosFiltrados" :key="candidato.id">
                  <td class="ps-4 fw-bold text-primary">{{ candidato.numeroControl }}</td>
                  <td>{{ candidato.apellidoP }} {{ candidato.apellidoM }} {{ candidato.nombre }}</td>
                  <td><span class="text-truncate d-inline-block" style="max-width: 150px;">{{ candidato.carrera_nombre || 'N/A' }}</span></td>
                  <td class="text-center"><span class="fw-bold">{{ candidato.semestre_id }}°</span></td>
                  <td><span class="badge bg-light text-secondary border fw-normal">{{ candidato.club_anterior || 'N/A' }}</span></td>
                  <td class="pe-4 text-end">
                    <button 
                      class="btn btn-sm btn-dark px-3 fw-bold" 
                      @click="abrirModalReinscribir(candidato)"
                    >
                      REINSCRIBIR
                    </button>
                  </td>
                </tr>
                <tr v-if="candidatosFiltrados.length === 0">
                  <td colspan="6" class="text-center py-5">
                    <div class="py-3">
                      <i class="bi bi-person-x fs-1 text-muted d-block mb-2"></i>
                      <span class="text-muted">No hay candidatos que coincidan con la búsqueda.</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Reinscripción Estático -->
    <div v-if="mostrarModal" class="modal show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-dark text-white border-0">
            <h5 class="modal-title fw-bold">Nueva Inscripción de Ciclo</h5>
            <button type="button" class="btn-close btn-close-white" @click="mostrarModal = false"></button>
          </div>
          <div class="modal-body p-4">
            <div class="d-flex align-items-center mb-4">
              <div class="bg-light p-3 rounded-circle me-3">
                <i class="bi bi-person-badge fs-3 text-dark"></i>
              </div>
              <div>
                <h6 class="mb-1 fw-bold">{{ alumnoSeleccionado?.nombre }} {{ alumnoSeleccionado?.apellidoP }}</h6>
                <span class="text-muted small">Control: {{ alumnoSeleccionado?.numeroControl }}</span>
              </div>
            </div>

            <div class="row g-3">
              <div class="col-6">
                <label class="form-label text-muted small fw-bold">SEMESTRE ANTERIOR</label>
                <div class="p-2 border rounded bg-light text-center fw-bold">{{ alumnoSeleccionado?.semestre_id }}°</div>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small fw-bold">NUEVO SEMESTRE</label>
                <div class="p-2 border rounded bg-success-subtle text-success text-center fw-bold">
                  {{ Math.min(8, (alumnoSeleccionado?.semestre_id || 0) + 1) }}°
                </div>
              </div>
              <div class="col-12 mt-4">
                <label class="form-label fw-bold">SELECCIONAR CLUB DESTINO</label>
                <select class="form-select form-select-lg" v-model="clubSeleccionado" style="font-size: 1rem;">
                  <option value="">-- Seleccione un Club --</option>
                  <option v-for="club in clubs" :key="club.id" :value="club.id" :disabled="club.ocupados >= club.cupo">
                    {{ club.nombre }} (Cupo: {{ club.ocupados }}/{{ club.cupo }})
                  </option>
                </select>
                <div v-if="clubSeleccionadoObj && clubSeleccionadoObj.ocupados >= clubSeleccionadoObj.cupo" class="text-danger small mt-1">
                  Este club ya no tiene cupo disponible.
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light">
            <button type="button" class="btn btn-outline-secondary px-4" @click="mostrarModal = false">Cancelar</button>
            <button type="button" class="btn btn-dark px-4 fw-bold" @click="procesarReinscripcion" :disabled="!clubSeleccionado || procesando">
              {{ procesando ? 'Inscribiendo...' : 'CONFIRMAR REINSCRIPCIÓN' }}
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
  name: 'Reinscripciones',
  props: {
    clubs: {
      type: Array,
      default: () => []
    }
  },
  data() {
    return {
      candidatos: [],
      busqueda: '',
      loading: true,
      mostrarModal: false,
      alumnoSeleccionado: null,
      clubSeleccionado: '',
      procesando: false
    };
  },
  computed: {
    candidatosFiltrados() {
      const q = this.busqueda.toLowerCase().trim();
      if (!q) return this.candidatos;
      return this.candidatos.filter(c => {
        return (
          c.numeroControl.toLowerCase().includes(q) ||
          c.nombre.toLowerCase().includes(q) ||
          c.apellidoP.toLowerCase().includes(q) ||
          c.apellidoM.toLowerCase().includes(q)
        );
      });
    },
    clubSeleccionadoObj() {
      if (!this.clubSeleccionado) return null;
      return this.clubs.find(c => c.id === this.clubSeleccionado);
    }
  },
  mounted() {
    this.cargarCandidatos();
  },
  methods: {
    async cargarCandidatos() {
      this.loading = true;
      try {
        const res = await axios.get(`${BACKEND}/reinscripciones?action=candidatos`, { headers: { Authorization: `Bearer ${sessionStorage.getItem('auth_token') || ''}` } });
        if (res.data.status === 'success') {
          this.candidatos = res.data.data;
        }
      } catch (error) {
        this.$emit('show-error', "No se pudo obtener la lista de candidatos.");
      } finally {
        this.loading = false;
      }
    },
    abrirModalReinscribir(alumno) {
      this.alumnoSeleccionado = alumno;
      this.clubSeleccionado = '';
      this.mostrarModal = true;
    },
    async procesarReinscripcion() {
      if (!this.clubSeleccionado || !this.alumnoSeleccionado) return;
      
      this.procesando = true;
      try {
        const payload = {
          numeroControl: this.alumnoSeleccionado.numeroControl,
          id_club: this.clubSeleccionado
        };
        const res = await axios.post(`${BACKEND}/reinscripciones?action=inscribir`, payload, { headers: { Authorization: `Bearer ${sessionStorage.getItem('auth_token') || ''}` } });
        
        if (res.data.status === 'success') {
          this.$emit('log', {
            accion: 'Insertar',
            tipo: 'alumno',
            descripcion: `Reinscripción exitosa del alumno ${this.alumnoSeleccionado.numeroControl}`
          });
          
          this.$emit('show-toast', "El alumno ha sido reinscrito satisfactoriamente al nuevo ciclo.");
          this.mostrarModal = false;
          
          this.$emit('refresh');
          this.$emit('request-reload-alumnos');
          await this.cargarCandidatos();
        } else {
          throw new Error(res.data.message);
        }
      } catch (error) {
        this.$emit('show-error', "Error al reinscribir: " + (error.response?.data?.message || error.message));
      } finally {
        this.procesando = false;
      }
    }
  }
}
</script>

<style scoped>
.table, .btn, .modal-content, .badge {
  transition: none !important;
}
.table-hover tbody tr:hover {
  background-color: rgba(0, 0, 0, 0.02) !important;
}
</style>
