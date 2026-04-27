<template>
  <div>
    <h4 class="text-primary mb-3">Evaluaciones</h4>

    <!-- Búsqueda de alumnos -->
    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        class="form-control"
        placeholder="Buscar alumno por nombre para ver evaluación..."
        @input="onSearchInput"
      />
      <ul v-if="searchSuggestions.length" class="list-group mt-2" style="max-height: 200px; overflow-y: auto; z-index: 1000; position: absolute; width: 95%;">
        <li
          v-for="suggestion in searchSuggestions"
          :key="suggestion.id"
          class="list-group-item list-group-item-action"
          @click="selectSuggestion(suggestion)"
        >
          {{ suggestion.nombre }} {{ suggestion.apellidoP }} {{ suggestion.apellidoM || '' }} - {{ suggestion.club }}
        </li>
      </ul>
    </div>

    <!-- Detalle de alumno seleccionado via búsqueda -->
    <div v-if="selectedStudent" class="card mb-4 border-primary shadow-sm">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Evaluación de: {{ selectedStudent.nombre }} {{ selectedStudent.apellidoP }} {{ selectedStudent.apellidoM }}</h6>
        <button class="btn btn-sm btn-light" @click="selectedStudent = null">Cerrar</button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>N. Control:</strong> {{ selectedStudent.control || selectedStudent.numeroControl || 'N/A' }}</p>
            <p><strong>Club:</strong> {{ selectedStudent.club }}</p>
            <p><strong>Carrera:</strong> {{ selectedStudent.carrera }}</p>
          </div>
          <div class="col-md-6">
            <p><strong>Estatus:</strong> 
              <span :class="isAcreditado(selectedStudent) ? 'text-success' : 'text-danger'" class="fw-bold">
                {{ isAcreditado(selectedStudent) ? "Acreditado" : "No acreditado" }}
              </span>
            </p>
            <p><strong>Evaluación:</strong> 
               <span v-if="isEvaluated(selectedStudent)" class="badge bg-info text-dark">
                 {{ getDesempenoText(findEvaluation(selectedStudent).nivel_desempeno) }}
               </span>
               <span v-else class="badge bg-secondary">Pendiente</span>
            </p>
            <button class="btn btn-sm btn-outline-info" @click="descargarConstancia(selectedStudent)">
              Ver Documento de Registro
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista por Clubs -->
    <div
      v-for="club in sortedClubs"
      :key="club.id"
      class="card mb-4 shadow-sm"
    >
      <div
        :class="['card-header', 'text-white', 'd-flex', 'justify-content-between', 'align-items-center', club.tipo === 'DEPORTIVO' ? 'bg-primary' : 'bg-secondary']"
      >
        <div class="fw-bold">{{ club.nombre }}</div>
        <div class="d-flex gap-2">
          <button class="btn btn-sm btn-light" @click="toggleClubCollapse(club.id)">
            {{ collapsedClubs[club.id] ? 'Expandir' : 'Colapsar' }}
          </button>
          <button class="btn btn-sm btn-light" @click="abrirVistaPreviaClub(club.nombre)">
            Registro de Participantes (PDF)
          </button>
        </div>
      </div>
      
      <div v-show="!collapsedClubs[club.id]" class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>N. Control</th>
                <th>Semestre</th>
                <th>Carrera</th>
                <th class="text-center">Faltas</th>
                <th class="text-center">Acreditado</th>
                <th class="text-center">Evaluación</th>
                <th class="text-center">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="alumno in filteredAlumnos(club.nombre)" :key="alumno.id">
                <td>{{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}</td>
                <td>{{ alumno.control || alumno.numeroControl }}</td>
                <td>{{ alumno.semestre || alumno.semestre_id }}</td>
                <td>{{ getCarreraAbreviada(alumno.carrera) }}</td>
                <td class="text-center">{{ alumno.faltas }}</td>
                <td class="text-center">
                  <span class="badge" :class="isAcreditado(alumno) ? 'bg-success' : 'bg-danger'">
                    {{ isAcreditado(alumno) ? "Sí" : "No" }}
                  </span>
                </td>
                <td class="text-center">
                   <span v-if="isEvaluated(alumno)" class="badge bg-info text-dark">
                     {{ getDesempenoText(findEvaluation(alumno).nivel_desempeno) }}
                   </span>
                   <span v-else class="badge bg-secondary">Pendiente</span>
                </td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-info" @click="descargarConstancia(alumno)">
                    PDF
                  </button>
                </td>
              </tr>
              <tr v-if="filteredAlumnos(club.nombre).length === 0">
                <td colspan="8" class="text-center text-muted p-3">No hay alumnos en este club</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal / Vista Previa -->
    <div v-if="previewData" class="print-preview">
      <div class="preview-documento">
        <div class="page" id="constancia">
          <table class="header-table">
            <tbody>
              <tr>
                <td class="logo-cell" rowspan="3">
                  <img src="../Img/Logo.jpg" alt="Logo" style="max-width: 80px; height: auto" />
                </td>
                <td class="title-cell">
                  Formato para el Registro de Participantes de<br />
                  Actividades Culturales y/o Deportivas
                </td>
                <td class="meta-cell">
                  <span><strong>Código:</strong> TecNM-VI-PO-003-01</span>
                </td>
              </tr>
              <tr>
                <td class="title-cell" style="font-size:9pt; font-weight:normal;">
                  Referencia a la Norma ISO 9001:2015: 8.1, 8.2.1, 8.2.2
                </td>
                <td class="meta-cell">
                  <span><strong>Revisión:</strong> 0</span>
                </td>
              </tr>
              <tr>
                <td></td>
                <td class="meta-cell">
                  <span><strong>Página</strong> 1 <strong>de</strong> 1</span>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="datos">
            INSTITUTO TECNOLÓGICO DE TLAXIACO<br />
            Subdirección de Planeación y Vinculación<br />
            Departamento de Actividades Extraescolares<br />
            Oficina de Promoción {{ toUpper(previewData.tipoActividad) }}
          </div>

          <div class="actividad-line">
            <strong>Actividad:</strong> {{ toUpper(previewData.club) }}
          </div>

          <table class="participantes">
            <thead>
              <tr>
                <th>NO.</th>
                <th>NOMBRE</th>
                <th>CONTROL</th>
                <th>ESP.</th>
                <th>SEM</th>
                <th>OBSERVACIONES</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(alumno, i) in previewData.alumnos" :key="i">
                <td>{{ i + 1 }}</td>
                <td style="text-align: left;">{{ toUpper(alumno.nombreFull) }}</td>
                <td>{{ alumno.control }}</td>
                <td>{{ getCarreraAbreviada(alumno.carrera) }}</td>
                <td>{{ alumno.semestre }}</td>
                <td style="font-size: 8pt;">{{ alumno.observaciones || '' }}</td>
              </tr>
              <tr v-for="n in Math.max(0, 16 - (previewData.alumnos ? previewData.alumnos.length : 0))" :key="'empty-' + n">
                <td></td><td></td><td></td><td></td><td></td><td></td>
              </tr>
            </tbody>
          </table>

          <p style="margin-bottom:8px; text-align: right;">
            Tlaxiaco, Oaxaca a {{ fechaHoy.dia }} de {{ fechaHoy.mes }} de {{ fechaHoy.anio }}
          </p>

          <div class="firmas">
            <div class="firma-bloque">
              <div class="firma-linea"></div>
              <strong>{{ getNombreJefe('jefe_promocion') }}</strong><br />
              Promotor Cultural<br />o Deportivo.
            </div>
            <div class="firma-bloque">
              <div class="firma-linea"></div>
              <strong>{{ getNombreJefe('jefe_promocion') }}</strong><br />
              Jefe de Oficina de Promoción<br />Cultural o Deportivo.
            </div>
            <div class="firma-bloque">
              <div class="firma-linea"></div>
              <strong>{{ getNombreJefe('jefe_actividades') }}</strong><br />
              Jefe de Departamento<br />Actividades Extraescolares
            </div>
          </div>

          <div class="footer-doc">
            <span>TecNM-VI-PO-003-01</span>
            <span>Rev. 0</span>
          </div>
        </div>
      </div>

      <div class="acciones-preview p-3">
        <h5 class="mb-3">Opciones de Documento</h5>
        <div class="mb-3">
          <label class="form-label small fw-bold">Jefe de Actividades</label>
          <input v-model="jefesSeleccionados.jefe_actividades_nombre" class="form-control form-control-sm" @change="guardarPreferenciaManual('jefe_actividades')"/>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-bold">Jefe de Promoción</label>
          <input v-model="jefesSeleccionados.jefe_promocion_nombre" class="form-control form-control-sm" @change="guardarPreferenciaManual('jefe_promocion')"/>
        </div>
        
        <div class="botones-acciones mt-4">
          <button class="btn btn-secondary w-100 mb-2" @click="previewData = null">Cerrar</button>
          <button class="btn btn-primary w-100" @click="generarPDF">Descargar PDF</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getAsistenciasPorClub, getFirmas, saveConfig, getEvaluatedStudents } from "../services/api";

export default {
  name: "Evaluaciones",
  props: ["clubs", "alumnos", "usuarios", "carreras"],
  data() {
    return {
      searchQuery: "",
      searchSuggestions: [],
      selectedStudent: null,
      previewData: null,
      alumnosPorClub: {},
      evaluadosPorClub: {},
      collapsedClubs: {},
      jefesSeleccionados: {
        jefe_promocion_nombre: "ARIADNA MONSERRAT LOPEZ",
        jefe_actividades_nombre: "URIEL ARCADIO AVILA",
      },
      refreshInterval: null
    };
  },
  computed: {
    sortedClubs() {
      if (!this.clubs) return [];
      const tipoOrden = { 'DEPORTIVO': 1, 'CULTURAL': 2 };
      return [...this.clubs].sort((a, b) => {
        const diff = (tipoOrden[a.tipo] || 3) - (tipoOrden[b.tipo] || 3);
        return diff !== 0 ? diff : a.nombre.localeCompare(b.nombre);
      });
    },
    fechaHoy() {
      const ahora = new Date();
      const meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];
      return { dia: ahora.getDate(), mes: meses[ahora.getMonth()], anio: ahora.getFullYear() };
    }
  },
  methods: {
    async loadAllData() {
      // Inicializar todos los clubs como colapsados primero
      for (const club of this.clubs) {
        if (club.id) {
          this.collapsedClubs[club.id] = true;
        }
      }
      
      for (const club of this.clubs) {
        if (!club.id) continue;
        try {
          const data = await getAsistenciasPorClub(club.id);
          const evaluados = await getEvaluatedStudents(club.nombre);
          
          this.alumnosPorClub[club.id] = (data.alumnos || []).map(al => {
            const map = data.asistencias?.[al.id] || {};
            const faltas = Object.values(map).filter(v => v == 0 || v === false).length;
            let carreraNombre = al.carrera;
            if (!carreraNombre && al.carrera_id) {
               const c = this.carreras.find(cat => cat.id == al.carrera_id);
               if (c) carreraNombre = c.nombre;
            }
            return { ...al, carrera: carreraNombre, asistencias: map, faltas, club: club.nombre };
          });
          
          this.evaluadosPorClub[club.nombre] = evaluados;
        } catch (e) {
          console.error(`Error club ${club.nombre}:`, e);
        }
      }
    },
    filteredAlumnos(clubName) {
      const club = this.clubs.find(c => c.nombre === clubName);
      if (!club) return [];
      let list = this.alumnosPorClub[club.id] || [];
      
      // Si hay búsqueda global, el filtro por club se mantiene pero solo muestra si el alumno está en la búsqueda
      // O simplemente mostramos el club completo si no hay búsqueda
      return list.sort((a, b) => (a.apellidoP || "").localeCompare(b.apellidoP || ""));
    },
    onSearchInput() {
      if (this.searchQuery.length < 2) {
        this.searchSuggestions = [];
        return;
      }
      const query = this.searchQuery.toLowerCase();
      const all = [];
      Object.values(this.alumnosPorClub).forEach(list => all.push(...list));
      
      this.searchSuggestions = all.filter(a => 
        `${a.nombre} ${a.apellidoP} ${a.apellidoM || ''}`.toLowerCase().includes(query)
      ).slice(0, 10);
    },
    selectSuggestion(student) {
      this.selectedStudent = student;
      this.searchQuery = "";
      this.searchSuggestions = [];
      
      // Expandir el club del alumno
      const club = this.clubs.find(c => c.nombre === student.club);
      if (club) this.collapsedClubs[club.id] = false;
    },
    toggleClubCollapse(id) {
      this.collapsedClubs[id] = !this.collapsedClubs[id];
    },
    isAcreditado(alumno) {
      return (alumno.faltas || 0) <= 2;
    },
    isEvaluated(alumno) {
      return !!this.findEvaluation(alumno);
    },
    findEvaluation(alumno) {
      const list = this.evaluadosPorClub[alumno.club] || [];
      const nombreFull = `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim().toUpperCase();
      return list.find(e => (e.nombre_estudiante || "").trim().toUpperCase() === nombreFull);
    },
    getDesempenoText(nivel) {
      const mapa = ["INSUFICIENTE", "SUFICIENTE", "BUENO", "NOTABLE", "EXCELENTE"];
      return mapa[nivel] || "";
    },
    getCarreraAbreviada(carrera) {
      if (!carrera) return "";
      const mapa = {
        "INGENIERÍA EN SISTEMAS COMPUTACIONALES": "I.S.C.",
        "INGENIERÍA INDUSTRIAL": "I.IND.",
        "INGENIERÍA CIVIL": "I.CIV.",
        "INGENIERÍA EN GESTIÓN EMPRESARIAL": "I.G.E.",
        "LICENCIATURA EN ADMINISTRACIÓN": "L.A.",
        "LICENCIATURA EN ARQUITECTURA": "ARQ.",
        "INGENIERÍA EN MECATRÓNICA": "I.MEC."
      };
      return mapa[carrera.toUpperCase()] || (carrera.length > 10 ? carrera.substring(0, 10) + "..." : carrera);
    },
    toUpper(v) { return (v || "").toString().toUpperCase(); },
    descargarConstancia(alumno) { this.abrirVistaPrevia([alumno], alumno.club); },
    abrirVistaPreviaClub(clubName) {
      const list = this.filteredAlumnos(clubName);
      this.abrirVistaPrevia(list, clubName);
    },
    abrirVistaPrevia(listaAlumnos, clubName) {
      const club = this.clubs.find(c => c.nombre === clubName);
      this.previewData = {
        club: clubName,
        tipoActividad: (club?.tipo || 'CULTURAL').toLowerCase(),
        alumnos: listaAlumnos.map(a => {
          const evalData = this.findEvaluation(a);
          let obs = "";
          if (!this.isAcreditado(a)) {
            obs = "NO ACREDITADO";
            if (evalData?.observaciones) obs += ": " + evalData.observaciones;
          } else {
            obs = evalData?.observaciones || (evalData ? this.getDesempenoText(evalData.nivel_desempeno) : "ACREDITADO");
          }
          return {
            nombreFull: `${a.nombre} ${a.apellidoP} ${a.apellidoM || ''}`.trim(),
            control: a.control || a.numeroControl || '',
            carrera: a.carrera,
            semestre: a.semestre || a.semestre_id || '',
            observaciones: obs
          };
        })
      };
    },
    generarPDF() {
      const nodo = document.getElementById("constancia");
      if (!nodo) return;
      const opt = {
        margin: [10, 10, 10, 10],
        filename: `Registro_${this.previewData.club}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: "portrait", unit: "mm", format: "letter" }
      };
      if (typeof html2pdf !== "undefined") {
        html2pdf().set(opt).from(nodo).save();
      } else {
        const script = document.createElement("script");
        script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
        document.head.appendChild(script);
        script.onload = () => html2pdf().set(opt).from(nodo).save();
      }
    },
    getNombreJefe(cargo) { return (this.jefesSeleccionados[cargo + "_nombre"] || "").toUpperCase(); },
    async guardarPreferenciaManual(cargo) {
      await saveConfig("firma_" + cargo, this.jefesSeleccionados[cargo + "_nombre"]);
    },
    async loadCargos() {
      const firmas = await getFirmas();
      if (firmas.jefe_actividades?.nombre) this.jefesSeleccionados.jefe_actividades_nombre = firmas.jefe_actividades.nombre;
      if (firmas.jefe_promocion?.nombre) this.jefesSeleccionados.jefe_promocion_nombre = firmas.jefe_promocion.nombre;
    }
  },
  async mounted() {
    await this.loadCargos();
    await this.loadAllData();
    // Auto-refresco cada 30 segundos
    this.refreshInterval = setInterval(() => this.loadAllData(), 30000);
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  }
};
</script>

<style scoped>
.page {
  background: #fff;
  width: 215.9mm;
  min-height: 279.4mm;
  margin: 0 auto;
  padding: 15mm;
  font-family: Arial, sans-serif;
  font-size: 10pt;
  color: #000;
  position: relative;
}
.header-table { width: 100%; border-collapse: collapse; border: 1.5px solid #000; margin-bottom: 10px; }
.header-table td { border: 1px solid #000; padding: 4px 8px; vertical-align: middle; }
.logo-cell { width: 90px; text-align: center; }
.title-cell { text-align: center; font-weight: bold; font-size: 11pt; }
.meta-cell { font-size: 8pt; white-space: nowrap; }
.datos { text-align: center; font-weight: bold; margin: 10px 0; }
.actividad-line { font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #000; width: 100%; }
.participantes { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
.participantes th, .participantes td { border: 1px solid #000; padding: 3px 5px; text-align: center; font-size: 9pt; }
.participantes th { background: #eee; }
.firmas { margin-top: 30px; display: flex; justify-content: space-around; text-align: center; font-size: 9pt; }
.firma-bloque { width: 30%; }
.firma-linea { border-top: 1px solid #000; margin-top: 40px; }
.footer-doc { position: absolute; bottom: 15mm; left: 15mm; right: 15mm; display: flex; justify-content: space-between; font-size: 8pt; border-top: 1px solid #000; }
.print-preview { position: fixed; inset: 0; background: rgba(0,0,0,0.7); display: flex; z-index: 2000; padding: 20px; overflow-y: auto; }
.preview-documento { flex: 1; display: flex; justify-content: center; }
.acciones-preview { width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; height: fit-content; margin-left: 20px; }

.text-primary { color: #2d3561 !important; }
.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-2 { margin-top: 0.5rem; }

.card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
  transition: box-shadow 0.2s ease;
}
.card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.card-header {
  padding: 12px 16px;
  font-weight: 600;
}
.bg-primary { background: #2d3561 !important; }
.bg-secondary { background: #5865a8 !important; }

.table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}
.table thead {
  background: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
}
.table th {
  padding: 12px 8px;
  text-align: left;
  font-weight: 600;
  color: #495057;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}
.table td {
  padding: 12px 8px;
  border-bottom: 1px solid #f0f0f0;
  vertical-align: middle;
}
.table tbody tr {
  transition: background 0.15s ease;
}
.table tbody tr:hover {
  background: #f8f9ff;
}

.table-hover tbody tr:hover {
  background: #f8f9ff;
}

.badge {
  padding: 6px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}
.bg-success { background: #28a745 !important; }
.bg-danger { background: #dc3545 !important; }
.bg-info { background: #17a2b8 !important; color: #fff; }
.bg-secondary { background: #6c757d !important; }

.btn {
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 500;
  font-size: 0.875rem;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}
.btn-sm {
  padding: 6px 12px;
  font-size: 0.8rem;
}
.btn-light {
  background: #fff;
  color: #495057;
  border: 1px solid #dee2e6;
}
.btn-light:hover {
  background: #f8f9fa;
  color: #2d3561;
}
.btn-outline-info {
  background: transparent;
  color: #17a2b8;
  border: 1px solid #17a2b8;
}
.btn-outline-info:hover {
  background: #17a2b8;
  color: #fff;
}

.form-control {
  padding: 10px 14px;
  border: 2px solid #e0e0e0;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.form-control:focus {
  outline: none;
  border-color: #2d3561;
  box-shadow: 0 0 0 3px rgba(45, 53, 97, 0.1);
}

.list-group {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
}
.list-group-item {
  padding: 10px 14px;
  border: none;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  transition: background 0.15s ease;
}
.list-group-item:hover {
  background: #f8f9ff;
}
.list-group-item:last-child {
  border-bottom: none;
}

.text-muted {
  color: #6c757d;
}
.fw-bold {
  font-weight: 600;
}
</style>
