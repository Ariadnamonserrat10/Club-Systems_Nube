<template>
  <div class="monitor-wrapper">
    <div class="d-flex flex-column monitor-background p-4 min-vh-100">
    <!-- PERFIL DEL MONITOR -->
    <div class="card shadow-sm p-3 mb-3 bg-white d-flex flex-row align-items-center">
      <template v-if="resolveFotoUrl(usuarioActual.foto) && !usuarioActualImageError">
        <img
          :src="resolveFotoUrl(usuarioActual.foto)"
          alt="Foto del monitor"
          class="rounded-circle me-3"
          width="80"
          height="80"
          style="object-fit: cover;"
          @error="handleUserImageError"
        />
      </template>
      <div v-else class="rounded-circle me-3 d-flex align-items-center justify-content-center text-white fw-bold" style="width: 80px; height: 80px; background: linear-gradient(135deg, #080A4C 0%, #0066CC 100%); border: 3px solid rgba(255,255,255,0.5); font-size: 1.75rem;">
        {{ getInitials(usuarioActual.nombre, usuarioActual.apellidoP) }}
      </div>
      <div>
        <h4 class="text-primary mb-2">Registro de Asistencias</h4>
        <p><strong>Usuario:</strong> {{ usuarioActual.nombre }} {{ usuarioActual.apellidoP }}</p>
        <p>
          <strong>Club asignado:</strong>
          <span v-if="usuarioActual.club_nombre">{{ usuarioActual.club_nombre }}</span>
          <span v-else>- Ninguno -</span>
        </p>
      </div>
      <div class="ms-auto">
        <button class="btn btn-outline-danger btn-sm" @click="cerrarSesion">
          Cerrar sesión
        </button>
      </div>
    </div>

    <!-- ALERTAS -->
    <transition name="fade">
      <div
        v-if="mensaje.texto"
        :class="['alert', mensaje.tipo, 'position-fixed top-0 end-0 mt-3 me-3 shadow']"
        style="z-index:2000; min-width:280px"
        role="alert"
      >
        {{ mensaje.texto }}
      </div>
    </transition>

    <!-- CONTENEDOR DE ASISTENCIAS -->
    <div class="card shadow-sm p-3 flex-grow-1 overflow-auto" style="max-height: 70vh;">
      <h5 class="text-secondary mb-3">Asistencias del Club</h5>

      <!-- AGREGAR NUEVA FECHA -->
      <div class="mb-3">
        <label for="nuevaFecha" class="form-label">Agregar nueva fecha:</label>
        <div class="input-group">
          <input
            type="date"
            id="nuevaFecha"
            v-model="nuevaFecha"
            class="form-control"
          />
          <button class="btn btn-success" @click="agregarFecha">Agregar</button>
        </div>
      </div>


      <!-- TABLA DE ASISTENCIAS SIEMPRE VISIBLE PARA MOSTRAR FECHAS -->
      <table class="table table-hover align-middle">
         <thead class="text-center modern-table-header">
           <tr>
             <th class="header-cell">Nombre</th>
             <th v-for="fecha in fechasData" :key="fecha" class="header-cell">{{ fecha }}</th>
             <th class="header-cell">Estatus</th>
             <th class="header-cell">Evaluación</th>
           </tr>
         </thead>
        <tbody>
          <tr v-if="alumnosClub.length === 0">
            <td :colspan="(fechasData.length + 3)" class="text-center text-muted">
              No hay alumnos registrados en este club.
            </td>
          </tr>
          <tr v-else v-for="(alumno, index) in alumnosClub" :key="index">
            <td>{{ alumno.nombre }} {{ alumno.apellidoP }}</td>
            <td v-for="fecha in fechasData" :key="fecha" class="text-center">
              <input
                type="checkbox"
                v-model="alumno.asistencias[fecha]"
                @change="onToggleAsistencia(alumno, fecha)"
              />
            </td>
             <td class="text-center">
               <span 
                 class="status-pill" 
                 :class="alumno.faltas < 3 ? 'status-accredited' : 'status-not-accredited'"
               >
                 <span class="status-dot"></span>
                 {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
               </span>
             </td>
            <td class="text-center">
              <button
                v-if="!isEvaluated(alumno)"
                class="btn btn-sm btn-outline-primary"
                @click="openEvalModal(alumno)"
              >
                Evaluar
              </button>
              <span v-else class="badge bg-secondary">Evaluado</span>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- BOTÓN GUARDAR CAMBIOS (OPCIONAL) -->
      <div class="d-flex justify-content-end mt-3" v-if="alumnosClub.length">
        <button class="btn btn-outline-primary" @click="guardarCambios">
          Guardar cambios
        </button>
      </div>
    </div>

    <!-- MODAL EVALUACION -->
    <div v-if="showEvalModal" class="modal-backdrop fade show"></div>
    <div v-if="showEvalModal" class="modal d-block" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header text-white" style="background-color: #080A4C;">
            <h5 class="modal-title">Evaluación de Desempeño</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeEvalModal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3 p-2 bg-light rounded border">
              <div class="row">
                <div class="col-md-6"><strong>Estudiante:</strong> {{ evalForm.nombre_estudiante }}</div>
                <div class="col-md-6"><strong>Club:</strong> {{ evalForm.nombre_club }}</div>
                <div v-if="currentStudent && currentStudent.faltas >= 3" class="col-12 mt-2">
                  <div class="alert alert-warning py-1 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Este estudiante tiene {{ currentStudent.faltas }} faltas y no acreditará créditos independientemente de la evaluación.
                  </div>
                </div>
                <div class="col-12 mt-2">
                  <label class="form-label"><strong>Periodo de realización:</strong></label>
                  <input type="date" v-model="evalForm.periodo_realizacion" class="form-control" />
                </div>
              </div>
            </div>

            <div class="mb-3">
              <div class="card bg-light mb-2">
                <div class="card-body py-2">
                  <h6 class="mb-1">Guía de Valores</h6>
                  <ul class="list-unstyled small mb-0 d-flex justify-content-between flex-wrap">
                    <li class="me-2"><strong>1:</strong> Insuficiente</li>
                    <li class="me-2"><strong>2:</strong> Suficiente</li>
                    <li class="me-2"><strong>3:</strong> Bueno</li>
                    <li class="me-2"><strong>4:</strong> Notable</li>
                    <li><strong>5:</strong> Excelente</li>
                  </ul>
                </div>
              </div>

              <h6>Criterios a evaluar</h6>
              <div class="table-responsive">
                <table class="table table-sm table-bordered">
                  <thead class="table-light text-center">
                    <tr>
                      <th style="width: 5%">No.</th>
                      <th style="width: 55%">Criterio</th>
                      <th style="width: 8%">1</th>
                      <th style="width: 8%">2</th>
                      <th style="width: 8%">3</th>
                      <th style="width: 8%">4</th>
                      <th style="width: 8%">5</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(criterio, i) in criteriosList" :key="i">
                      <td class="text-center">{{ i + 1 }}</td>
                      <td>{{ criterio }}</td>
                      <td v-for="val in 5" :key="val" class="text-center">
                        <input 
                          type="radio" 
                          :name="'criterio_' + (i+1)" 
                          :value="val" 
                          v-model="evalForm['criterio_' + (i+1)]"
                        />
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label"><strong>Observaciones:</strong></label>
              <textarea v-model="evalForm.observaciones" class="form-control" rows="4" placeholder="Escriba sus observaciones aquí..."></textarea>
            </div>

            <div class="row">
              <div class="col-md-6">
                <label class="form-label"><strong>Valor numérico de la actividad Cultural y/o Deportiva:</strong></label>
                <select v-model.number="evalForm.valor_numerico" class="form-select">
                  <option disabled value="">Seleccione</option>
                  <option value="1">1 (Insuficiente)</option>
                  <option value="2">2 (Suficiente)</option>
                  <option value="3">3 (Bueno)</option>
                  <option value="4">4 (Notable)</option>
                  <option value="5">5 (Excelente)</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label"><strong>Nivel de desempeño alcanzado de la actividad Cultural y/o Deportiva:</strong></label>
                 <select v-model.number="evalForm.nivel_desempeno" class="form-select">
                  <option disabled value="">Seleccione</option>
                   <option value="1">1 (Insuficiente)</option>
                  <option value="2">2 (Suficiente)</option>
                  <option value="3">3 (Bueno)</option>
                  <option value="4">4 (Notable)</option>
                  <option value="5">5 (Excelente)</option>
                </select>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="closeEvalModal">Cancelar</button>
            <button type="button" class="btn btn-primary" @click="submitEvaluacion">Guardar Evaluación</button>
          </div>
        </div>
      </div>
    </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import { 
  getAsistenciasPorClub, 
  crearFechaAsistencias, 
  actualizarAsistencia, 
  getAlumnos, 
  saveEvaluacion,
  getEvaluatedStudents,
  getClubs
} from "../services/api";
import { BACKEND } from "../services/backend";
import { authService } from "../services/auth";
import { resolveFotoUrl } from "../services/imageUtils";

export default {
  name: "Monitor",
  data() {
    return {
      usuarioActual: {
        nombre: "",
        apellidoP: "",
        tipo: "",
        foto: "https://cdn-icons-png.flaticon.com/512/847/847969.png",
        club_asignado: null,
        club_nombre: null,
      },
      usuarioActualImageError: false,
      monitor: { nombre: "", club: "" },
      clubsList: [],
      selectedClubId: null,
      assigning: false,
      nuevaFecha: "",
      mensaje: { texto: "", tipo: "" },
      alumnosData: [],
      fechasData: [],
      showEvalModal: false,
      currentStudent: null,
      evaluados: [],
      criteriosList: [
        "Cumple en tiempo y forma con las actividades encomendadas alcanzando los objetivos.",
        "Trabaja en equipo y se adapta a nuevas situaciones.",
        "Muestra liderazgo en las actividades encomendadas.",
        "Organiza su tiempo y trabaja de manera proactiva.",
        "Interpreta la realidad y se sensibiliza aportando soluciones a la problemática con la actividad Cultural y/o Deportiva.",
        "Realiza sugerencias innovadoras para beneficio o mejora del programa en el que participa.",
        "Tiene iniciativa para ayudar en las actividades encomendadas and muestra espíritu de servicio."
      ],
      evalForm: {
        nombre_estudiante: '',
        nombre_club: '',
        periodo_realizacion: '',
        criterio_1: null, criterio_2: null, criterio_3: null, criterio_4: null, criterio_5: null, criterio_6: null, criterio_7: null,
        observaciones: '',
        valor_numerico: null,
        nivel_desempeno: null
      }
    };
  },
  computed: {
    alumnosClub() {
      return (this.alumnosData || []).map((a) => {
        if (!a.asistencias) a.asistencias = {};
        this.fechasData.forEach((f) => {
          if (!(f in a.asistencias)) a.asistencias[f] = false;
        });
        a.faltas = Object.values(a.asistencias).filter((v) => v === false).length;
        return a;
      });
    },
  },
  methods: {
    resolveFotoUrl,
    getInitials(nombre, apellido) {
      let initials = "";
      if (nombre && typeof nombre === "string" && nombre.trim()) {
        initials += nombre.trim()[0].toUpperCase();
      }
      if (apellido && typeof apellido === "string" && apellido.trim()) {
        initials += apellido.trim()[0].toUpperCase();
      }
      return initials || "U";
    },
    handleUserImageError() {
      this.usuarioActualImageError = true;
    },
    async cargarUsuarioActual() {
      try {
        const usuarioId = sessionStorage.getItem("usuarioId");
        if (!usuarioId) return;

        const response = await axios.get(`${BACKEND}/obtenerUsuario.php?id=${usuarioId}`);

        if (response.data?.status === "success") {
          const datos = response.data.data;
          this.usuarioActual.nombre = datos.nombre || "";
          this.usuarioActual.apellidoP = datos.apellidoP || "";
          this.usuarioActual.tipo = datos.tipo || sessionStorage.getItem("usuarioTipo") || "";
          this.usuarioActual.foto = datos.foto || this.usuarioActual.foto;
          this.usuarioActual.club_asignado = datos.club_asignado ?? null;
          this.usuarioActual.club_nombre = datos.club_nombre ?? null;
          this.usuarioActualImageError = false;
          this.selectedClubId = this.usuarioActual.club_asignado;
          if (datos.club_nombre) this.monitor.club = datos.club_nombre;
        }
      } catch (error) {
        // Silencioso en producción
      }
    },

    async loadAsistencias() {
      const clubId = this.usuarioActual.club_asignado;
      if (!clubId) return;
      try {
        let alumnosClub = [];
        try {
          const res = await fetch(`${BACKEND}/Alumnos.php?club_id=${encodeURIComponent(clubId)}`);
          const json = await res.json();
          if (res.ok && json && Array.isArray(json.data)) {
            alumnosClub = json.data;
          }
        } catch (e) {
          // Silencioso
        }

        const data = await getAsistenciasPorClub(clubId);
        const nuevasFechas = Array.isArray(data.fechas) ? data.fechas : [];
        this.fechasData = [...new Set([...this.fechasData, ...nuevasFechas])].sort();

        const alumnos = (Array.isArray(data.alumnos) && data.alumnos.length) ? data.alumnos : alumnosClub;
        const asist = data.asistencias || {};

        this.alumnosData = (alumnos || []).map((al) => {
          const rawMap = asist[al.id] || {};
          const map = {};
          Object.keys(rawMap).forEach(f => { map[f] = rawMap[f] == 1; });
          this.fechasData.forEach(fecha => {
            if (!(fecha in map)) map[fecha] = false;
          });
          return { ...al, asistencias: map, faltas: Object.values(map).filter(v => v === false).length };
        });
        
        if (this.alumnosData.length > 0) {
          const clubName = this.usuarioActual.club_nombre || this.alumnosData[0].club || '';
          if (clubName) this.loadEvaluatedStudents(clubName);
        }
        this.$forceUpdate();
      } catch (e) {
        // Silencioso - mostrar mensaje solo en interacción explícita
        this.mostrarMensaje('No se pudieron cargar asistencias', 'alert-danger');
      }
    },

    async loadEvaluatedStudents(clubName) {
      try {
        this.evaluados = await getEvaluatedStudents(clubName);
      } catch (e) {
        // Silencioso
      }
    },
    isEvaluated(alumno) {
      const nombreFull = `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim();
      function normalize(s) {
        if (!s) return '';
        const from = s.normalize('NFD').replace(/\p{Diacritic}/gu, '');
        return from.toLowerCase().replace(/\s+/g, ' ').trim();
      }
      const target = normalize(nombreFull);
      const targetTokens = target.split(' ').filter(Boolean);
      return this.evaluados.some(e => {
        const norm = normalize(e.nombre_estudiante || '');
        if (norm === target) return true;
        return targetTokens.every(t => norm.includes(t));
      });
    },
    mostrarMensaje(texto, tipo) {
      this.mensaje.texto = texto;
      this.mensaje.tipo = tipo;
      setTimeout(() => (this.mensaje.texto = ""), 2500);
    },
    cerrarSesion() {
      this.mostrarMensaje("Sesión cerrada correctamente.", "alert-info");
      setTimeout(() => {
        authService.logout('current').then(() => {
          this.$router.push("/");
        }).catch(() => {
          authService.clearAuth();
          this.$router.push("/");
        });
      }, 500);
    },
    async agregarFecha() {
      if (!this.nuevaFecha || !this.usuarioActual.club_asignado) return;
      if (this.fechasData.includes(this.nuevaFecha)) {
        this.mostrarMensaje("La fecha ya está registrada.", "alert-danger");
        return;
      }
      try {
        const registros = this.alumnosClub.map(a => ({ alumno_id: a.id, presente: false }));
        await crearFechaAsistencias({ club_id: this.usuarioActual.club_asignado, fecha: this.nuevaFecha, registros });
        this.fechasData.push(this.nuevaFecha);
        this.fechasData.sort();
        this.alumnosData.forEach(al => { if (!al.asistencias[this.nuevaFecha]) al.asistencias[this.nuevaFecha] = false; });
        this.nuevaFecha = "";
        this.mostrarMensaje("Fecha agregada correctamente.", "alert-success");
      } catch (e) {
        this.mostrarMensaje("Error al crear fecha", "alert-danger");
      }
    },
    async guardarCambios() {
      try {
        const promesas = [];
        for (const alumno of this.alumnosData) {
          for (const fecha of this.fechasData) {
            promesas.push(actualizarAsistencia({ alumno_id: alumno.id, fecha, presente: !!alumno.asistencias[fecha] }));
          }
        }
        await Promise.all(promesas);
         this.mostrarMensaje("Cambios guardados correctamente.", "alert-success");
         // NOTA: Auditoría manejada por el backend
       } catch (e) {
        this.mostrarMensaje("Error al guardar cambios.", "alert-danger");
      }
    },
    async onToggleAsistencia(alumno, fecha) {
      const presente = !!alumno.asistencias[fecha];
      try {
        await actualizarAsistencia({ alumno_id: alumno.id, fecha, presente });
        alumno.faltas = Object.values(alumno.asistencias).filter((v) => v === false).length;
      } catch (e) {
        alumno.asistencias[fecha] = !presente;
        this.mostrarMensaje('Error al actualizar asistencia', 'alert-danger');
      }
    },
    openEvalModal(alumno) {
      this.currentStudent = alumno;
      const clubName = alumno.club || (this.alumnosData[0]?.club) || this.usuarioActual.club_nombre || 'Sin Club';
      this.evalForm = {
        nombre_estudiante: `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim(),
        nombre_club: clubName,
        periodo_realizacion: new Date().toISOString().split('T')[0],
        criterio_1: null, criterio_2: null, criterio_3: null, criterio_4: null, criterio_5: null, criterio_6: null, criterio_7: null,
        observaciones: '',
        valor_numerico: null,
        nivel_desempeno: null
      };
      this.showEvalModal = true;
    },
    closeEvalModal() { this.showEvalModal = false; },
    async submitEvaluacion() {
      const f = this.evalForm;
      if (!f.criterio_1 || !f.criterio_2 || !f.criterio_3 || !f.criterio_4 || !f.criterio_5 || !f.criterio_6 || !f.criterio_7) {
        return alert('Por favor califique todos los criterios.');
      }
      if (!f.valor_numerico || !f.nivel_desempeno) {
        return alert('Por favor asigne valor numérico y nivel de desempeño.');
      }
      try {
        const payload = { ...this.evalForm };
        for (let i = 1; i <= 7; i++) payload['criterio_' + i] = parseInt(payload['criterio_' + i], 10);
        
        // Ajuste 1-5 -> 0-4 para valor_numerico y nivel_desempeno
        const sendPayload = {
          ...payload,
          valor_numerico: parseInt(payload.valor_numerico, 10) - 1,
          nivel_desempeno: parseInt(payload.nivel_desempeno, 10) - 1,
          observaciones: payload.observaciones || ''
        };

        const res = await saveEvaluacion(sendPayload);
         if (res.status === 'success') {
           this.mostrarMensaje('Evaluación guardada exitosamente', 'alert-success');
           this.showEvalModal = false;
           // NOTA: Auditoría manejada por el backend
           if (!this.evaluados.some(e => e.nombre_estudiante === f.nombre_estudiante)) {
            this.evaluados.push({ 
              nombre_estudiante: f.nombre_estudiante,
              nivel_desempeno: sendPayload.nivel_desempeno,
              valor_numerico: sendPayload.valor_numerico,
              observaciones: sendPayload.observaciones
            });
          }
        }
      } catch (e) {
        this.mostrarMensaje(e.message || 'Error al guardar evaluación', 'alert-danger');
      }
    }
  },
  async mounted() {
    await this.cargarUsuarioActual();
    await this.loadAsistencias();
    // Auto-refresco cada 10 segundos
    this.refreshInterval = setInterval(async () => {
      try {
        await this.loadAsistencias();
      } catch (e) {
        // Silencioso
      }
    }, 10000);
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.monitor-wrapper {
  background-color: #080A4C;
  min-height: 100vh;
  margin: 0;
  padding: 0;
  width: 100%;
}

.monitor-background {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 30%, #0d1b4d 70%, #050630 100%);
  color: #eef1ff;
  min-height: 100vh;
  width: 100%;
  position: relative;
  overflow-x: hidden;
}

.monitor-background::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(ellipse at 10% 20%, rgba(99, 102, 241, 0.15) 0%, transparent 70%),
    radial-gradient(ellipse at 90% 80%, rgba(13, 71, 161, 0.1) 0%, transparent 70%);
  pointer-events: none;
  animation: ambientGlow 15s ease-in-out infinite alternate;
}

@keyframes ambientGlow {
  0% { opacity: 0.6; }
  100% { opacity: 1; }
}

.monitor-background .card {
  background: rgba(255, 255, 255, 0.98);
  border: none;
  border-radius: 20px;
  backdrop-filter: blur(20px);
  box-shadow: 
    0 8px 32px rgba(0, 0, 0, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.08),
    inset 0 1px 0 rgba(255, 255, 255, 0.9);
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.monitor-background .card:hover {
  box-shadow: 
    0 12px 48px rgba(0, 0, 0, 0.18),
    0 4px 16px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}

.modern-table-header {
  background: linear-gradient(135deg, #1e3a5f 0%, #080A4C 50%, #0d1b4d 100%) !important;
  border: none;
  position: relative;
  overflow: hidden;
}

.modern-table-header::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 200%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
  animation: shimmer 3s infinite;
}

@keyframes shimmer {
  0% { transform: translateX(-50%); }
  100% { transform: translateX(50%); }
}

.header-cell {
  color: #ffffff !important;
  font-weight: 600;
  font-size: 0.8rem;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  padding: 16px 12px !important;
  border: none;
  background: transparent !important;
}

.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.85rem;
  letter-spacing: 0.02em;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  cursor: default;
  user-select: none;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  position: relative;
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { 
    transform: scale(1);
    opacity: 1;
  }
  50% { 
    transform: scale(1.3);
    opacity: 0.8;
  }
}

.status-accredited {
  background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%);
  color: #ffffff;
  box-shadow: 
    0 4px 15px rgba(16, 185, 129, 0.4),
    0 0 20px rgba(16, 185, 129, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.status-accredited .status-dot {
  background: #ffffff;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
}

.status-accredited:hover {
  transform: scale(1.05);
  box-shadow: 
    0 6px 25px rgba(16, 185, 129, 0.5),
    0 0 30px rgba(16, 185, 129, 0.3);
}

.status-not-accredited {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 50%, #b91c1c 100%);
  color: #ffffff;
  box-shadow: 
    0 4px 15px rgba(239, 68, 68, 0.4),
    0 0 20px rgba(239, 68, 68, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.status-not-accredited .status-dot {
  background: #ffffff;
  box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
  animation: urgentPulse 1s ease-in-out infinite;
}

@keyframes urgentPulse {
  0%, 100% { 
    transform: scale(1);
    opacity: 1;
  }
  50% { 
    transform: scale(1.4);
    opacity: 0.6;
  }
}

.status-not-accredited:hover {
  transform: scale(1.05);
  box-shadow: 
    0 6px 25px rgba(239, 68, 68, 0.5),
    0 0 30px rgba(239, 68, 68, 0.3);
}

.monitor-background .text-secondary,
.monitor-background .text-muted,
.monitor-background .form-label,
.monitor-background .table td {
  color: #1e293b !important;
  font-weight: 400;
}

.text-primary {
  color: #1e3a5f !important;
  font-weight: 600;
}

.btn-outline-primary {
  color: #1e3a5f;
  border-color: #1e3a5f;
  border-width: 2px;
  font-weight: 500;
  border-radius: 12px;
  padding: 10px 20px;
  transition: all 0.3s ease;
}

.btn-outline-primary:hover {
  background: linear-gradient(135deg, #1e3a5f 0%, #080A4C 100%);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(30, 58, 95, 0.3);
}

.btn-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border: none;
  border-radius: 12px;
  font-weight: 600;
  padding: 10px 24px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.btn-outline-danger {
  color: #dc2626;
  border-color: #dc2626;
  border-width: 2px;
  font-weight: 500;
  border-radius: 12px;
  transition: all 0.3s ease;
}

.btn-outline-danger:hover {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
}

.table {
  border-collapse: separate;
  border-spacing: 0;
  border-radius: 16px;
  overflow: hidden;
}

.table tbody tr {
  transition: all 0.2s ease;
}

.table tbody tr:hover {
  background: linear-gradient(90deg, rgba(99, 102, 241, 0.04), rgba(59, 130, 246, 0.02));
}

.table tbody td {
  border-top: 1px solid #f1f5f9;
  padding: 14px 12px;
  vertical-align: middle;
}

.table thead th {
  border: none;
}

.fade-enter-active, .fade-leave-active { 
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-enter, .fade-leave-to { 
  opacity: 0;
  transform: translateY(10px);
}

@keyframes slideIn {
  from { 
    transform: translateX(100px); 
    opacity: 0; 
  }
  to { 
    transform: translateX(0); 
    opacity: 1; 
  }
}

.table input[type="checkbox"] {
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #10b981;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.table input[type="checkbox"]:hover {
  transform: scale(1.1);
}

.modal-content {
  border-radius: 20px;
  border: none;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
}

.modal-header {
  background: linear-gradient(135deg, #1e3a5f 0%, #080A4C 100%) !important;
  border-radius: 20px 20px 0 0 !important;
  border: none;
  padding: 20px 24px;
}

.modal-title {
  font-weight: 600;
  font-size: 1.15rem;
}

.btn-secondary {
  border-radius: 12px;
  font-weight: 500;
  padding: 10px 20px;
  transition: all 0.3s ease;
}

.btn-secondary:hover {
  transform: translateY(-1px);
}

.btn-primary {
  background: linear-gradient(135deg, #1e3a5f 0%, #080A4C 100%);
  border: none;
  border-radius: 12px;
  font-weight: 600;
  padding: 10px 24px;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(30, 58, 95, 0.3);
}

.form-control,
.form-select {
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  padding: 12px 16px;
  font-size: 0.95rem;
  transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
  border-color: #1e3a5f;
  box-shadow: 0 0 0 4px rgba(30, 58, 95, 0.1);
}
</style>
