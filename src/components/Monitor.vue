<template>
  <div class="d-flex flex-column bg-light vh-100 p-4">
    <!-- PERFIL DEL MONITOR -->
    <div class="card shadow-sm p-3 mb-3 bg-white d-flex flex-row align-items-center">
      <img
        :src="resolveFotoUrl(usuarioActual.foto) || 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'"
        alt="Foto del monitor"
        class="rounded-circle me-3"
        width="80"
        height="80"
        style="object-fit: cover;"
      />
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
        <thead class="table-primary text-center">
          <tr>
            <th>Nombre</th>
            <th v-for="fecha in fechasData" :key="fecha">{{ fecha }}</th>
            <th>Acreditado</th>
            <th>Evaluación</th>
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
              <span class="badge" :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'">
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
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">Evaluación de Desempeño</h5>
            <button type="button" class="btn-close btn-close-white" @click="closeEvalModal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3 p-2 bg-light rounded border">
              <div class="row">
                <div class="col-md-6"><strong>Estudiante:</strong> {{ evalForm.nombre_estudiante }}</div>
                <div class="col-md-6"><strong>Club:</strong> {{ evalForm.nombre_club }}</div>
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
</template>

<script>
import axios from "axios";
import { 
  getAsistenciasPorClub, 
  crearFechaAsistencias, 
  actualizarAsistencia, 
  getAlumnos, 
  registrarAuditoria, 
  saveEvaluacion,
  getEvaluatedStudents,
  getClubs
} from "../services/api";
import { BACKEND } from "../services/backend";

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
    resolveFotoUrl(foto) {
      if (!foto || typeof foto !== "string") return "";
      if (foto.startsWith("blob:")) return "";
      if (/^https?:\/\//i.test(foto)) return foto;
      const path = foto.startsWith("/") ? foto.slice(1) : foto;
      return `${BACKEND}/${path}`;
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
          this.usuarioActual.foto = this.resolveFotoUrl(datos.foto) || this.usuarioActual.foto;
          this.usuarioActual.club_asignado = datos.club_asignado ?? null;
          this.usuarioActual.club_nombre = datos.club_nombre ?? null;
          this.selectedClubId = this.usuarioActual.club_asignado;
          if (datos.club_nombre) this.monitor.club = datos.club_nombre;
        }
      } catch (error) {
        console.error("Error cargando usuario Monitor:", error);
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
          console.error('Error cargando alumnos por club:', e);
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
        console.error('Error cargando asistencias:', e);
        this.mostrarMensaje('No se pudieron cargar asistencias', 'alert-danger');
      }
    },

    async loadEvaluatedStudents(clubName) {
      try {
        this.evaluados = await getEvaluatedStudents(clubName);
      } catch (e) {
        console.error('Error loading evaluated students:', e);
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
        const norm = normalize(e || '');
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
        sessionStorage.clear();
        this.$router.push("/");
      }, 1500);
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
        const usuarioId = sessionStorage.getItem('usuarioId');
        if (usuarioId) {
          await registrarAuditoria({
            id_usuario: parseInt(usuarioId),
            accion: 'Asistencia',
            descripcion: `Registró asistencias para el club ${this.usuarioActual.club_nombre || 'N/A'}`
          });
        }
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
          const usuarioId = sessionStorage.getItem('usuarioId');
          if (usuarioId) {
            await registrarAuditoria({
              id_usuario: parseInt(usuarioId),
              accion: 'Insertar',
              descripcion: `Se evaluó al estudiante ${f.nombre_estudiante}`
            });
          }
          if (!this.evaluados.includes(f.nombre_estudiante)) this.evaluados.push(f.nombre_estudiante);
        }
      } catch (e) {
        this.mostrarMensaje(e.message || 'Error al guardar evaluación', 'alert-danger');
      }
    }
  },
  async mounted() {
    await this.cargarUsuarioActual();
    await this.loadAsistencias();
  },
};
</script>

<style scoped>
.card { border-radius: 12px; }
.table { font-size: 0.95rem; }
img { object-fit: cover; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s; }
.fade-enter, .fade-leave-to { opacity: 0; }
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1040; }
.modal { position: fixed; inset: 0; display:flex; align-items:center; justify-content:center; z-index: 1050; }
</style>
