<template>
  <div>
    <h3 class="text-primary mb-4">Constancias</h3>

    <!-- Búsqueda de alumnos -->
    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        class="form-control"
        placeholder="Buscar alumno por nombre..."
        @input="onSearchInput"
      />
      <ul v-if="searchSuggestions.length" class="list-group mt-2" style="max-height: 200px; overflow-y: auto;">
        <li
          v-for="suggestion in searchSuggestions"
          :key="suggestion.id"
          class="list-group-item list-group-item-action"
          @click="selectSuggestion(suggestion)"
        >
          {{ suggestion.nombre }} {{ suggestion.apellidoP }} {{ suggestion.apellidoM || '' }} - {{ suggestion.club }}
        </li>
      </ul>
      <div v-if="searchQuery && !searchSuggestions.length && searchQuery.length > 2" class="text-muted mt-2">
        No se encontraron alumnos con ese nombre.
      </div>
    </div>

    <!-- Detalle de alumno seleccionado via búsqueda -->
    <div v-if="selectedStudent" class="card mb-4 border-info shadow-sm">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Detalles del Alumno: {{ selectedStudent.nombre }} {{ selectedStudent.apellidoP }} {{ selectedStudent.apellidoM }}</h6>
        <button class="btn btn-sm btn-light" @click="selectedStudent = null">Cerrar Detalle</button>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-6 mb-2">
            <p class="mb-1"><strong>Club:</strong> {{ selectedStudent.club }}</p>
            <p class="mb-1"><strong>Número de Control:</strong> {{ selectedStudent.control || selectedStudent.numeroControl || 'N/A' }}</p>
          </div>
          <div class="col-md-6 mb-2">
            <p class="mb-1"><strong>Monitor:</strong> {{ getMonitorNameForStudent(selectedStudent) }}</p>
            <p class="mb-1"><strong>Estatus:</strong> 
              <span :class="isAcreditado(selectedStudent) ? 'text-success' : 'text-danger'">
                {{ isAcreditado(selectedStudent) ? "Acreditado" : "No acreditado" }}
              </span>
            </p>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light text-center">
              <tr>
                <th v-for="fecha in (fechasData.length ? fechasData : fechas)" :key="fecha" style="font-size: 0.8rem">
                  {{ fecha }}
                </th>
                <th style="font-size: 0.8rem">Faltas</th>
                <th style="font-size: 0.8rem">Acción</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td v-for="fecha in (fechasData.length ? fechasData : fechas)" :key="fecha" class="text-center">
                  <span v-if="selectedStudent.asistencias?.[fecha]" class="text-success fw-bold">✔</span>
                  <span v-else class="text-danger fw-bold">✖</span>
                </td>
                <td class="text-center">{{ selectedStudent.faltas }}</td>
                <td class="text-center">
                  <button
                    class="btn btn-sm btn-info"
                    :disabled="!isAcreditado(selectedStudent)"
                    @click="descargarConstancia(selectedStudent, selectedStudent.club, periodoActual)"
                  >
                    Descargar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div
      v-for="(club, index) in sortedClubs"
      :key="club.nombre + index"
      class="card mb-4 shadow-sm"
    >
      <div
        :class="['card-header', 'text-white', 'd-flex', 'justify-content-between', 'align-items-center', club.tipo === 'DEPORTIVO' ? 'bg-primary' : 'bg-secondary']"
      >
        <div>
          {{ club.nombre }} — Monitores:
          <span v-if="club.monitores && club.monitores.length">
            {{ club.monitores.map((m) => formatMonitorNombre(m)).join(", ") }}
          </span>
          <span v-else> Sin asignar </span>
        </div>
        <div class="d-flex gap-2">
          <button
            class="btn btn-sm btn-light"
            @click="toggleClubCollapse(club.id)"
          >
            {{ collapsedClubs[club.id] ? 'Expandir' : 'Colapsar' }}
          </button>
          <button
            class="btn btn-sm btn-light"
            @click="openConstanciaPreview(club)"
          >
            Vista previa
          </button>
          <button
            class="btn btn-sm btn-success"
            @click="descargarTodasConstanciasClub(club.nombre)"
          >
            Descargar PDFs
          </button>
        </div>
      </div>
      <div v-show="!collapsedClubs[club.id]" class="card-body">
        <p>{{ club.descripcion }}</p>

        <div
          v-if="filteredAlumnos(club.nombre).length === 0"
          class="text-muted"
        >
          No hay alumnos en este club.
        </div>

        <table v-else class="table table-bordered align-middle">
          <thead class="table-secondary">
            <tr>
              <th>Alumno</th>
              <th
                v-for="fecha in fechasData.length ? fechasData : fechas"
                :key="fecha"
              >
                {{ fecha }}
              </th>
              <th>Constancia</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(alumno, i) in filteredAlumnos(club.nombre)"
              :key="alumno.id || alumno.control || 'c-' + i"
            >
              <td>
                {{ alumno.nombre }} {{ alumno.apellidoP }}
                {{ alumno.apellidoM }}
              </td>
              <td
                v-for="fecha in fechasData.length ? fechasData : fechas"
                :key="fecha"
                class="text-center"
              >
                <span
                  v-if="alumno.asistencias?.[fecha]"
                  class="text-success fw-bold"
                  style="font-size: 1.5rem"
                  >✔</span
                >
                <span
                  v-else
                  class="text-danger fw-bold"
                  style="font-size: 1.5rem"
                  >✖</span
                >
              </td>
              <td>
                <span
                  :class="alumno.faltas <= 2 ? 'text-success' : 'text-danger'"
                >
                  {{ alumno.faltas <= 2 ? "Sí" : "No" }}
                </span>
              </td>
              <td>
                <button
                  class="btn btn-sm btn-outline-info"
                  :disabled="!isAcreditado(alumno)"
                  :title="
                    !isAcreditado(alumno)
                      ? 'No acreditado: supera el límite de faltas'
                      : 'Descargar constancia'
                  "
                  @click="
                    descargarConstancia(alumno, club.nombre, periodoActual)
                  "
                >
                  Descargar
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <button class="btn btn-outline-primary" @click="downloadAll">
      Descargar todas las constancias
    </button>

    <!-- Modal/Vista previa imprimible -->
    <div v-if="previewData" class="print-preview">
      <div class="preview-documento">
        <div class="constancia A4" id="constancia">

          <!-- ENCABEZADO CORREGIDO según imagen oficial -->
          <table
            cellpadding="0"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 9pt; border: 1px solid #000;"
          >
            <tbody>
              <!-- Fila 1: Logo | Título principal | Código -->
              <tr>
                <td
                  rowspan="3"
                  style="border: 1px solid #000; width: 90px; text-align: center; vertical-align: middle; padding: 6px;"
                >
                  <img src="../Img/Logo.jpg" alt="Logo" style="max-width: 75px; height: auto;" />
                </td>
                <td
                  rowspan="2"
                  style="border: 1px solid #000; vertical-align: middle; padding: 6px 10px; font-weight: bold; font-size: 10pt; line-height: 1.4;"
                >
                  Constancia de cumplimiento de actividad Cultural y/o Deportiva
                </td>
                <td
                  style="border: 1px solid #000; padding: 5px 8px; font-weight: bold; font-size: 9pt; white-space: nowrap; width: 220px;"
                >
                  Código:TecNM-VI-PO-003-05
                </td>
              </tr>
              <!-- Fila 2: Revisión -->
              <tr>
                <td style="border: 1px solid #000; padding: 5px 8px; font-weight: bold; font-size: 9pt;">
                  Revisión: 0
                </td>
              </tr>
              <!-- Fila 3: Referencia ISO | Página -->
              <tr>
                <td
                  style="border: 1px solid #000; vertical-align: middle; padding: 5px 10px; font-weight: bold; font-size: 9pt;"
                >
                  Referencia a la Norma ISO 9001:2015&nbsp;&nbsp;&nbsp;8.1
                </td>
                <td style="border: 1px solid #000; padding: 5px 8px; font-weight: bold; font-size: 9pt;">
                  Página 1 de 1
                </td>
              </tr>
            </tbody>
          </table>

          <h2
            class="titulo-constancia"
            style="font-size: 11pt; margin: 30px 0 15px 0; text-align: center;">
            CONSTANCIA DE CUMPLIMIENTO DE ACTIVIDAD CULTURAL Y/O DEPORTIVA
          </h2>
          <div class="espacios-mediano"></div>
          <div class="cuerpo">
            <p
              class="destinatario"
              style="font-size: 10pt; margin: 0 0 20px 0; line-height: 1.6"
            >
            <br>
              {{ getNombreJefe('jefa_servicios') || '__________________________' }}<br />
              {{ tituloServiciosEscolares() }} DEL DEPARTAMENTO DE SERVICIOS ESCOLARES<br />
              PRESENTE
            </p>
            <div class="espacios"></div>
            <p class="texto justificado">
              La que suscribe {{ getNombreJefe('jefe_actividades') || '__________________________' }}, {{ tituloFirma('jefe_actividades') }} del Departamento de Actividades Extraescolares, por este medio se permite hacer de su
              conocimiento que la estudiante
              <strong>{{ toUpper(previewData.estudianteNombre) }}</strong> con número de control <strong>{{ previewData.numeroControl }}</strong> de la
              carrera de <strong>{{ toUpper(previewData.carrera) }}</strong
              >, ha cumplido su actividad extraescolar en el club de
              <strong>{{ toUpper(previewData.club) }}</strong> con el nivel de
              desempeño <strong>{{ toUpper(previewData.desempeno) }}</strong> y
              un valor numérico de
              <strong>{{ previewData.valorNumerico || desempenoValor(previewData.desempeno) }}</strong>
              durante el periodo escolar
              <strong>{{ toUpper(previewData.mesInicio) }} - {{ toUpper(previewData.mesFin) }} {{ previewData.anioPeriodo }}</strong>, con un valor curricular de 1 crédito.
            </p>

            <div class="espacios"></div>

            <p class="lugar-fecha">
              Se extiende la presente en la Heroica ciudad de Tlaxiaco a los
              {{ fechaHoy.dia }} días del mes de {{ fechaHoy.mes }} de
              {{ fechaHoy.anio }}.
            </p>

            <div class="espacios-firma"></div>

            <table
              class="tabla-firmas"
              style="width: 100%; border-collapse: collapse; margin-top: 20px;">
              <tbody>
                <tr style="border: none !important;">
                  <td
                    style="
                      width: 50%;
                      text-align: center;
                      vertical-align: top;
                      padding: 0;
                      border: none !important;">
                    ATENTAMENTE
                  </td>
                  <td
                    style="
                      width: 50%;
                      text-align: center;
                      vertical-align: top;
                      padding: 0;
                      border: none !important;">
                    Vo. Bo.
                  </td>
                </tr>
                <tr style="border: none !important;">
                  <td colspan="2" class="espacios-firma-grandes" style="border: none !important;"></td>
                </tr>
                <tr style="border: none !important;">
                  <td
                    style="
                      width: 50%;
                      text-align: center;
                      vertical-align: top;
                      padding: 0;
                      border: none !important;">
                    <div class="linea-firma"></div>
                    <div class="nombre-firma">{{ getNombreJefe('jefe_promocion') || '__________________________' }}</div>
                    <div class="cargo-firma">
                      {{ tituloFirma('jefe_promocion') }} DE LA OFICINA DE PROMOCIÓN {{ toUpper(previewData.tipoActividad || 'CULTURAL') }}
                    </div>
                  </td>
                  <td
                    style="
                      width: 50%;
                      text-align: center;
                      vertical-align: top;
                      padding: 0;
                      border: none !important;">
                    <div class="linea-firma"></div>
                    <div class="nombre-firma">{{ getNombreJefe('jefe_actividades') || '__________________________' }}</div>
                    <div class="cargo-firma">
                      {{ tituloFirma('jefe_actividades') }} DEL DEPARTAMENTO DE ACTIVIDADES EXTRAESCOLARES
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="pie-pagina">
              c.c.p. Jefe (a) de Departamento Correspondiente
            </div>

            <table class="tabla-pie">
              <tbody>
                <tr style="border: none !important;">
                  <td class="pie-izq" style="border: none !important;">TecNM-VI-PO-003-05</td>
                  <td class="pie-der" style="border: none !important;">Rev. 0</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="acciones-preview p-3" style="width: 300px; background: #f8f9fa; border-left: 1px solid #dee2e6;">
        <h5 class="mb-3">Configuración de Firmas</h5>
        
        <div class="mb-3">
          <label class="form-label small fw-bold">Jefa de Servicios Escolares</label>
          <input 
            v-model="jefesSeleccionados.jefa_servicios_nombre" 
            class="form-control form-control-sm" 
            placeholder="Nombre de la jefa"
            maxlength="60"
            @input="sanitizeNombreInput('jefa_servicios_nombre')"
            @change="guardarPreferenciaManual('jefa_servicios')"
          />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Jefe/Jefa de Actividades</label>
          <input
            v-model="jefesSeleccionados.jefe_actividades_nombre"
            class="form-control form-control-sm"
            placeholder="Nombre completo"
            maxlength="60"
            @input="sanitizeNombreInput('jefe_actividades_nombre')"
            @change="guardarPreferenciaManual('jefe_actividades')"
          />
        </div>

        <div class="mb-3">
          <label class="form-label small fw-bold">Jefe/Jefa de Promoción</label>
          <input
            v-model="jefesSeleccionados.jefe_promocion_nombre"
            class="form-control form-control-sm"
            placeholder="Nombre completo"
            maxlength="60"
            @input="sanitizeNombreInput('jefe_promocion_nombre')"
            @change="guardarPreferenciaManual('jefe_promocion')"
          />
        </div>

        <hr>

        <h5 class="mb-3">Editar Constancia</h5>
        <div class="campos-editar">
          <div class="campo">
            <label>Período:</label>
            <div class="d-flex gap-2">
              <select v-model="previewData.mesInicio" class="form-select form-select-sm">
                <option value="Enero">Enero</option>
                <option value="Febrero">Febrero</option>
                <option value="Marzo">Marzo</option>
                <option value="Abril">Abril</option>
                <option value="Mayo">Mayo</option>
                <option value="Junio">Junio</option>
                <option value="Julio">Julio</option>
                <option value="Agosto">Agosto</option>
                <option value="Septiembre">Septiembre</option>
                <option value="Octubre">Octubre</option>
                <option value="Noviembre">Noviembre</option>
                <option value="Diciembre">Diciembre</option>
              </select>
              <span class="align-self-center">-</span>
              <select v-model="previewData.mesFin" class="form-select form-select-sm">
                <option value="Enero">Enero</option>
                <option value="Febrero">Febrero</option>
                <option value="Marzo">Marzo</option>
                <option value="Abril">Abril</option>
                <option value="Mayo">Mayo</option>
                <option value="Junio">Junio</option>
                <option value="Julio">Julio</option>
                <option value="Agosto">Agosto</option>
                <option value="Septiembre">Septiembre</option>
                <option value="Octubre">Octubre</option>
                <option value="Noviembre">Noviembre</option>
                <option value="Diciembre">Diciembre</option>
              </select>
              <input
                v-model="previewData.anioPeriodo"
                type="number"
                min="2020"
                max="2030"
                class="form-control form-control-sm"
                style="width: 100px"
              />
            </div>
          </div>
        </div>
        <div class="botones-acciones mt-3">
          <button
            class="btn btn-secondary w-100 mb-2"
            @click="previewData = null"
          >
            Cerrar
          </button>
          <button class="btn btn-primary w-100" @click="generarPDF">
            Descargar PDF
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getAsistenciasPorClub, getEvaluacion, getFirmas, saveConfig } from "../services/api";
import JSZip from 'jszip';

export default {
  name: "Constancias",
  props: ["clubs", "alumnos", "fechas", "usuarios", "carreras"],
  data() {
    return {
      previewData: null,
      periodoActual: this.getPeriodoActual(),
      alumnosPorClub: {}, // Objeto que almacena alumnos por club_id
      fechasData: [], // Fechas desde BD
      collapsedClubs: {}, // Estado de colapso por club
      searchQuery: '', // Consulta de búsqueda
      searchSuggestions: [], // Sugerencias de búsqueda
      jefesSeleccionados: {
        jefe_promocion: "",
        jefe_promocion_nombre: "ARIADNA MONSERRAT LOPEZ",
        jefe_actividades: "",
        jefe_actividades_nombre: "URIEL ARCADIO AVILA",
        jefa_servicios: "",
        jefa_servicios_nombre: ""
      },
      selectedStudent: null, // Alumno seleccionado por búsqueda
      refreshInterval: null
    };
  },
  computed: {
    sortedClubs() {
      if (!Array.isArray(this.clubs)) return [];
      return [...this.clubs].sort((a, b) => {
        const tipoA = (a.tipo || '').toUpperCase();
        const tipoB = (b.tipo || '').toUpperCase();
        // Deportivos primero (DEPORTIVO < CULTURAL alfabéticamente)
        if (tipoA !== tipoB) {
          return tipoA.localeCompare(tipoB);
        }
        // Si mismo tipo, ordenar por nombre
        return (a.nombre || '').localeCompare(b.nombre || '');
      });
    },
    usuariosOficina() {
      if (!this.usuarios) return [];
      return this.usuarios.filter(u => 
        u.tipo && u.tipo.toString().toUpperCase() === 'OFICINA'
      );
    },
    fechaHoy() {
      const ahora = new Date();
      const meses = [
        "enero",
        "febrero",
        "marzo",
        "abril",
        "mayo",
        "junio",
        "julio",
        "agosto",
        "septiembre",
        "octubre",
        "noviembre",
        "diciembre",
      ];
      return {
        dia: ahora.getDate(),
        mes: meses[ahora.getMonth()],
        anio: ahora.getFullYear(),
      };
    },
  },
  methods: {
    formatMonitorNombre(monitor) {
      // Acepta objeto o string y devuelve "PrimerNombre PrimerApellido"
      if (!monitor) return "";
      if (typeof monitor === "string") {
        const parts = monitor.trim().split(/\s+/).filter(Boolean);
        if (parts.length >= 2) return `${parts[0]} ${parts[1]}`;
        return parts[0] || "";
      }
      const nombre =
        (monitor.nombre || monitor.nombres || monitor.firstName || "")
          .toString()
          .trim()
          .split(/\s+/)[0] || "";
      const apStr = (
        monitor.apellidoP ||
        monitor.apellido ||
        monitor.apellidos ||
        monitor.lastName ||
        ""
      )
        .toString()
        .trim();
      const apellidoP = apStr ? apStr.split(/\s+/)[0] : "";
      return [nombre, apellidoP].filter(Boolean).join(" ");
    },
    getClubMonitores(club) {
      if (!club) return [];
      // Arrays de monitores en distintas keys
      if (Array.isArray(club.monitores)) return club.monitores.filter(Boolean);
      if (Array.isArray(club.monitors)) return club.monitors.filter(Boolean);
      if (Array.isArray(club.monitor)) return club.monitor.filter(Boolean);
      // Strings separados por coma o punto y coma
      const str =
        (typeof club.monitores === "string" && club.monitores) ||
        (typeof club.monitors === "string" && club.monitors) ||
        "";
      if (str.trim()) {
        return str
          .split(/[;,]/)
          .map((s) => s.trim())
          .filter(Boolean);
      }
      // Monitores individuales en distintas keys comunes
      const candidatos = [
        club.monitor,
        club.monitorNombre,
        club.monitor_name,
        club.monitorFullName,
        club.monitor1,
        club.monitor2,
      ].filter(Boolean);
      if (candidatos.length) return candidatos;

      // Derivar desde this.usuarios (monitores asignados)
      const usuarios = Array.isArray(this.usuarios) ? this.usuarios : [];
      const clubId = Number(club.id);
      const nombreClub = (club.nombre || "").toString().trim();
      // helper para extraer id numérico de cadenas como '80_NULL_' o similares
      const parseId = (val) => {
        if (val == null) return NaN;
        const num = Number(val);
        if (!Number.isNaN(num) && Number.isFinite(num)) return num;
        const m = String(val).match(/\d+/);
        return m ? Number(m[0]) : NaN;
      };
      const asignados = usuarios.filter((u) => {
        if (!u) return false;
        const tipo = (u.tipo || "").toString().toUpperCase();
        if (tipo && tipo !== "MONITOR") return false;
        const asignId = parseId(
          u.club_asignado || u.id_club || u.club_id || u.clubId,
        );
        const asignName = (u.club_nombre || u.clubName || u.club || "")
          .toString()
          .trim();
        if (
          clubId &&
          !Number.isNaN(clubId) &&
          asignId &&
          !Number.isNaN(asignId) &&
          asignId === clubId
        )
          return true;
        if (nombreClub && asignName && asignName === nombreClub) return true;
        // también considerar responsable del club
        const userId = parseId(u.id || u.user_id || u.usuario_id);
        const respId = parseId(club.id_responsable);
        if (respId && userId && respId === userId) return true;
        return false;
      });
      return asignados;
    },
    async loadAsistenciasPorClubs() {
      try {
        // Cargar asistencias para cada club
        for (const club of this.clubs || []) {
          if (club.id) {
            try {
              const data = await getAsistenciasPorClub(club.id);
              this.alumnosPorClub[club.id] = Array.isArray(data.alumnos)
                ? data.alumnos
                : [];

              // Agregar fechas únicas
              if (Array.isArray(data.fechas)) {
                this.fechasData = [
                  ...new Set([...this.fechasData, ...data.fechas]),
                ].sort();
              }

              // Mapear asistencias
              const asist = data.asistencias || {};
              this.alumnosPorClub[club.id] = (
                this.alumnosPorClub[club.id] || []
              ).map((al) => {
                const map = { ...(asist[al.id] || {}) };
                const faltas = Object.values(map).filter(
                  (v) => v === false,
                ).length;
                return { ...al, asistencias: map, faltas, club: club.nombre };
              });
            } catch (e) {
              console.error(
                `Error cargando asistencias para club ${club.id}:`,
                e,
              );
              this.alumnosPorClub[club.id] = [];
            }
          }
        }
      } catch (e) {
        console.error("Error cargando asistencias por clubs:", e);
      }
    },
    filteredAlumnos(clubName) {
      // Buscar el club por nombre
      const club = (this.sortedClubs || []).find((c) => c.nombre === clubName);

      // Si tenemos datos de BD para este club, usarlos
      let alumnos = [];
      const alumnosBD = club && club.id ? this.alumnosPorClub[club.id] : null;
      if (Array.isArray(alumnosBD) && alumnosBD.length > 0) {
        alumnos = alumnosBD;
      } else {
        // Fallback a props por id de club o por nombre de club
        const clubId = club?.id ? Number(club.id) : null;
        alumnos = (this.alumnos || []).filter((a) => {
          const aClubId = Number(a.clubId ?? a.club_id ?? a.id_club ?? 0);
          if (clubId && aClubId && aClubId === clubId) return true;
          return a.club === clubName;
        });
      }

      // Filtrar por búsqueda si hay query
      if (this.searchQuery && this.searchQuery.length >= 3) {
        const query = this.searchQuery.toLowerCase();
        alumnos = alumnos.filter(alumno =>
          `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.toLowerCase().includes(query)
        );
      }

      return alumnos;
    },
    downloadAll() {
      const rows = [];
      for (const club of this.clubs) {
        const alumnos = this.filteredAlumnos(club.nombre);
        for (const a of alumnos) {
          if (!this.isAcreditado(a)) continue; // solo acreditados
          rows.push([
            club.nombre,
            a.nombre + " " + a.apellidoP + " " + (a.apellidoM || ""),
            a.control,
            a.faltas,
          ]);
        }
      }
      const csv = rows
        .map((r) =>
          r.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(","),
        )
        .join("\n");
      const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.setAttribute("href", url);
      link.setAttribute("download", "constancias.csv");
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      this.$emit("log", {
        usuario: "Usuario Oficina",
        accion: "Exportar",
        tipo: "constancias",
        descripcion: "Descarga de constancias (solo acreditados)",
      });
    },
    desempenoValor(desempeno) {
      const mapa = { EXCELENTE: 4, NOTABLE: 3, BUENO: 2, REGULAR: 1, SUFICIENTE: 1, DEFICIENTE: 0, INSUFICIENTE: 0 };
      return mapa[(desempeno || "").toUpperCase()] !== undefined ? mapa[(desempeno || "").toUpperCase()] : 0;
    },
    getPeriodoActual() {
      const f = new Date();
      const mes = f.getMonth();
      const anio = f.getFullYear();
      if (mes >= 0 && mes <= 5) {
        return { mesInicio: 'Enero', mesFin: 'Junio', anioPeriodo: anio };
      } else {
        return { mesInicio: 'Agosto', mesFin: 'Diciembre', anioPeriodo: anio };
      }
    },
    isCulturalName(nombre) {
      const n = (nombre || "").toString().trim().toLowerCase();
      const culturales = [
        "danza",
        "rondalla",
        "banda de musica",
        "banda de música",
      ];
      return culturales.includes(n);
    },
    tipoActividad(club) {
      if (club && club.tipo) return club.tipo.toUpperCase();
      const nombre = typeof club === 'string' ? club : (club?.nombre || "");
      return this.isCulturalName(nombre) ? "CULTURAL" : "DEPORTIVA";
    },
    // Regla de acreditación: faltas <= 2 (ajustable)
    isAcreditado(alumno) {
      const faltas = Number(alumno?.faltas ?? 0);
      return Number.isFinite(faltas) ? faltas <= 2 : false;
    },
    toggleClubCollapse(clubId) {
      this.collapsedClubs[clubId] = !this.collapsedClubs[clubId];
    },
    onSearchInput() {
      if (this.searchQuery.length < 3) {
        this.searchSuggestions = [];
        return;
      }
      const query = this.searchQuery.toLowerCase();
      const allAlumnos = [];
      Object.values(this.alumnosPorClub).forEach(alumnos => {
        allAlumnos.push(...alumnos);
      });
      this.searchSuggestions = allAlumnos.filter(alumno =>
        `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.toLowerCase().includes(query)
      ).slice(0, 10); // Limitar a 10 sugerencias
    },
    selectSuggestion(suggestion) {
      this.searchQuery = `${suggestion.nombre} ${suggestion.apellidoP} ${suggestion.apellidoM || ''}`;
      this.searchSuggestions = [];
      this.selectedStudent = suggestion; // Guardar el alumno seleccionado para mostrar detalles
      
      // Cerrar todos los clubs
      this.sortedClubs.forEach(c => {
        this.collapsedClubs[c.id] = true;
      });
      // Expandir el club del alumno
      const club = this.sortedClubs.find(c => c.nombre === suggestion.club);
      if (club) {
        this.collapsedClubs[club.id] = false;
      }
    },
    getMonitorNameForStudent(alumno) {
      if (!alumno || !alumno.club) return "Sin asignar";
      const club = (this.clubs || []).find(c => c.nombre === alumno.club);
      if (!club) return "Sin asignar";
      const monitores = this.getClubMonitores(club);
      if (monitores && monitores.length) {
        return monitores.map(m => this.formatMonitorNombre(m)).join(", ");
      }
      return "Sin asignar";
    },
    openConstanciaPreview(club) {
      const periodo = this.getPeriodoActual();
      this.previewData = {
        estudianteNombre: "NOMBRE DEL ESTUDIANTE",
        numeroControl: "00000000",
        carrera: (this.carreras && this.carreras[0] ? this.carreras[0].nombre : "CARRERA").toUpperCase(),
        club: (club?.nombre || "club").toLowerCase(),
        desempeno: "EXCELENTE",
        mesInicio: periodo.mesInicio,
        mesFin: periodo.mesFin,
        anioPeriodo: periodo.anioPeriodo,
        tipoActividad: this.tipoActividad(club),
      };
    },
    printConstancia(data) {
      this.previewData = {
        tipoActividad: this.tipoActividad(data.club),
        ...data,
      };
    },
    async descargarConstancia(alumno, clubNombre, periodoActual) {
      // Construir datos de la constancia
      const periodo = this.getPeriodoActual();
      let desempeno = '';
      let valorNumerico = null;

      try {
        const nombreFull = `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim();
        const evalData = await getEvaluacion({ 
          nombre_estudiante: nombreFull, 
          nombre_club: (alumno.club || clubNombre || '') 
        });

        if (evalData) {
          const nivel = parseInt(evalData.nivel_desempeno);
          if (nivel === 5) desempeno = 'EXCELENTE';
          else if (nivel === 4) desempeno = 'NOTABLE';
          else if (nivel === 3) desempeno = 'BUENO';
          else if (nivel === 2) desempeno = 'SUFICIENTE';
          else desempeno = 'INSUFICIENTE';
          
          valorNumerico = parseInt(evalData.valor_numerico);
        }
      } catch (e) {
        console.error('Error obteniendo evaluación:', e);
      }

      if (!desempeno) {
        // Fallback: calcular por faltas
        desempeno = (
          alumno.desempeno ||
          (alumno.faltas <= 1
            ? 'EXCELENTE'
            : alumno.faltas === 2
              ? 'BUENO'
              : 'REGULAR')
        ).toString();
      }

      const data = {
        estudianteNombre: `${alumno.nombre} ${alumno.apellidoP} ${alumno.apellidoM || ''}`.trim(),
        numeroControl: alumno.numeroControl || alumno.control || 'SIN CONTROL',
        carrera: alumno.carrera || (this.carreras && this.carreras[0] ? this.carreras[0].nombre : 'SIN CARRERA'),
        club: (alumno.club || clubNombre || '').toLowerCase(),
        desempeno: desempeno,
        valorNumerico: valorNumerico, // Si es null, el template usará el calculado
        mesInicio: periodo.mesInicio,
        mesFin: periodo.mesFin,
        anioPeriodo: periodo.anioPeriodo,
      };
      
      const clubActual = this.clubs.find(c => c.nombre === data.club || c.id === alumno.club_id);

      this.previewData = {
        tipoActividad: this.tipoActividad(clubActual || data.club),
        ...data,
      };
    },
    generarPDF() {
      this.$nextTick(() => {
        setTimeout(() => {
          const nodo = document.getElementById("constancia");
          if (!nodo || !this.previewData) return;

          const nombre = `${this.previewData.estudianteNombre.replace(/\s+/g, "_")}_${this.previewData.numeroControl}`;
          const opt = {
            margin: [15, 15, 15, 15],
            filename: `Constancia_${nombre}.pdf`,
            image: { type: "jpeg", quality: 0.98 },
            html2canvas: { 
              scale: 2,
              allowTaint: true,
              useCORS: true,
              logging: false,
              windowHeight: nodo.scrollHeight
            },
            jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
          };

          // Usar html2pdf
          if (typeof html2pdf === "undefined") {
            setTimeout(() => {
              // Si aún no cargó, esperar
              if (typeof html2pdf !== "undefined") {
                html2pdf().set(opt).from(nodo).save();
              }
            }, 1000);
          } else {
            setTimeout(() => {
              html2pdf().set(opt).from(nodo).save();
            }, 200);
          }
        }, 300);
      });
    },
    async generarPDFBlob() {
      return new Promise((resolve, reject) => {
        this.$nextTick(() => {
          setTimeout(() => {
            const nodo = document.getElementById("constancia");
            if (!nodo || !this.previewData) {
              reject(new Error('No hay datos para generar PDF'));
              return;
            }

            const opt = {
              margin: [15, 15, 15, 15],
              image: { type: "jpeg", quality: 0.98 },
              html2canvas: { 
                scale: 2,
                allowTaint: true,
                useCORS: true,
                logging: false,
                windowHeight: nodo.scrollHeight
              },
              jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
            };

            if (typeof html2pdf === "undefined") {
              setTimeout(() => {
                if (typeof html2pdf !== "undefined") {
                  html2pdf().set(opt).from(nodo).outputPdf('blob').then(resolve).catch(reject);
                } else {
                  reject(new Error('html2pdf no está disponible'));
                }
              }, 1000);
            } else {
              setTimeout(() => {
                html2pdf().set(opt).from(nodo).outputPdf('blob').then(resolve).catch(reject);
              }, 200);
            }
          }, 300);
        });
      });
    },
    toUpper(v) {
      return (v == null ? "" : String(v)).toUpperCase();
    },
    async descargarTodasConstanciasClub(clubNombre) {
      const alumnosAcreditados = this.filteredAlumnos(clubNombre).filter(alumno => this.isAcreditado(alumno));
      console.log('Alumnos acreditados:', alumnosAcreditados);
      if (!alumnosAcreditados.length) {
        alert(`No hay alumnos acreditados en el club ${clubNombre}`);
        return;
      }

      const zip = new JSZip();
      const folder = zip.folder(`Constancias_${clubNombre.replace(/\s+/g, '_')}`);

      for (const alumno of alumnosAcreditados) {
        // Preparar datos para el alumno
        await this.descargarConstancia(alumno, clubNombre, this.periodoActual);
        // Esperar a que previewData se actualice
        await new Promise(resolve => setTimeout(resolve, 500));

        // Generar PDF como blob
        const pdfBlob = await this.generarPDFBlob();
        
        // Agregar al ZIP
        const nombreArchivo = `Constancia_${alumno.nombre}_${alumno.apellidoP}_${alumno.numeroControl || 'SIN_CONTROL'}.pdf`;
        folder.file(nombreArchivo, pdfBlob);
      }

      // Generar y descargar el ZIP
      const zipBlob = await zip.generateAsync({ type: 'blob' });
      const url = URL.createObjectURL(zipBlob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `Constancias_${clubNombre.replace(/\s+/g, '_')}.zip`;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    },
    printStyles() {
      return `
        @page { size: A4; margin: 20mm; }
        body { font-family: Arial, Helvetica, sans-serif; color: #000; }
        .A4 { width: 190mm; margin: 0 auto; }
        .header { text-align: center; }
        .logos { display: grid; grid-template-columns: 80px 1fr 80px; align-items: center; gap: 10px; }
        .logo.izq, .logo.der { width: 70px; height: 70px; background: #eee; border-radius: 6px; }
        .titulos .top-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: bold; }
        .titulos .meta { display: flex; justify-content: space-between; font-size: 10px; margin-top: 4px; }
        .separador { height: 2px; background: #000; margin: 10px 0; }
        h2 { font-size: 16px; margin: 10px 0 20px; text-align: center; }
        .cuerpo { font-size: 12px; line-height: 1.6; }
        .destinatario { font-weight: bold; text-align: left; }
        .texto.justificado { text-align: justify; }
        .lugar-fecha { margin-top: 16px; }
        .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 36px; text-align: center; }
        .firmas .linea { border-top: 1px solid #000; margin: 36px 40px 6px; }
        .firmas .nombre { font-weight: bold; }
        .firmas .cargo { font-size: 11px; }
      `;
    },
    formatNombre(u) {
      if (!u) return '';
      const np = u.nombre || '';
      const ap = u.apellidoP || '';
      const am = u.apellidoM || '';
      return `${np} ${ap} ${am}`.trim().toUpperCase();
    },
    sanitizeNombreInput(campo) {
      const raw = (this.jefesSeleccionados[campo] || '').toString();
      const base = raw
        .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g, '')
        .replace(/\s+/g, ' ')
        .trimStart()
        .toUpperCase();

      let letras = 0;
      let out = '';
      for (const ch of base) {
        if (/[A-ZÁÉÍÓÚÜÑ]/u.test(ch)) {
          if (letras >= 50) continue;
          letras += 1;
          out += ch;
        } else if (ch === ' ') {
          if (out && out[out.length - 1] !== ' ') out += ch;
        }
      }

      this.jefesSeleccionados[campo] = out;
    },
    inferGenero(nombre) {
      const first = (nombre || '')
        .toString()
        .trim()
        .split(/\s+/)[0]
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toUpperCase();

      if (!first) return 'M';

      const femeninos = new Set([
        'BEATRIZ',
        'LOURDES',
        'GUADALUPE',
        'CARMEN',
        'ARIADNA',
        'XIMENA',
        'SOFIA',
        'LUCIA',
      ]);
      const masculinos = new Set(['JESUS', 'JOSE', 'LUIS', 'CARLOS', 'JUAN', 'MIGUEL', 'URIEL']);

      if (femeninos.has(first)) return 'F';
      if (masculinos.has(first)) return 'M';

      if (/A$|RIZ$|TRIZ$|DES$/.test(first)) return 'F';
      if (/O$|OS$|EL$|ER$|AR$/.test(first)) return 'M';

      return 'M';
    },
    tituloFirma(cargo) {
      const nombre = this.getNombreJefe(cargo).replace(/^C\.\s*/i, '');
      if (!nombre) {
        return cargo === 'jefa_servicios' ? 'JEFA' : 'JEFE';
      }
      return this.inferGenero(nombre) === 'F' ? 'JEFA' : 'JEFE';
    },
    tituloServiciosEscolares() {
      const raw = (this.jefesSeleccionados.jefa_servicios_nombre || '').toUpperCase().trim();
      if (/^ENCARGAD[OA]\b/.test(raw)) return 'ENCARGADO';
      if (/^JEFA\b/.test(raw)) return 'JEFA';
      if (/^JEFE\b/.test(raw)) return 'JEFE';
      const nombre = this.getNombreJefe('jefa_servicios').replace(/^C\.\s*/i, '');
      return this.inferGenero(nombre) === 'F' ? 'JEFA' : 'JEFE';
    },
    async guardarPreferenciaManual(cargo) {
      this.sanitizeNombreInput(cargo + '_nombre');
      const valor = String(this.jefesSeleccionados[cargo + '_nombre'] || '')
        .replace(/\s+/g, ' ')
        .trim();
      this.jefesSeleccionados[cargo + '_nombre'] = valor;
      try {
        await saveConfig('firma_' + cargo, valor);
      } catch (e) {
        console.error('Error al guardar firma manual:', e);
      }
    },
    async loadCargos() {
      try {
        const firmas = await getFirmas();
        if (firmas.jefe_actividades) {
          this.jefesSeleccionados.jefe_actividades = firmas.jefe_actividades.id || '';
          if (firmas.jefe_actividades.nombre) {
            this.jefesSeleccionados.jefe_actividades_nombre = String(firmas.jefe_actividades.nombre).toUpperCase();
            this.sanitizeNombreInput('jefe_actividades_nombre');
          }
        }
        if (firmas.jefe_promocion) {
          this.jefesSeleccionados.jefe_promocion = firmas.jefe_promocion.id || '';
          if (firmas.jefe_promocion.nombre) {
            this.jefesSeleccionados.jefe_promocion_nombre = String(firmas.jefe_promocion.nombre).toUpperCase();
            this.sanitizeNombreInput('jefe_promocion_nombre');
          }
        }
        if (firmas.jefa_servicios) {
          this.jefesSeleccionados.jefa_servicios_nombre = firmas.jefa_servicios.nombre;
          this.sanitizeNombreInput('jefa_servicios_nombre');
        }
      } catch (e) {
        console.error('Error al cargar cargos:', e);
      }
    },
    getNombreJefe(cargo) {
      if (cargo === 'jefa_servicios') {
        const raw = String(this.jefesSeleccionados.jefa_servicios_nombre || '').toUpperCase();
        const limpio = raw.replace(/^(JEFA|JEFE|ENCARGAD[OA])\s+/i, '').trim();
        return limpio ? 'C. ' + limpio : '';
      }

      const manualKey = cargo + '_nombre';
      const manual = (this.jefesSeleccionados[manualKey] || '').toString().trim();
      if (manual) return manual.toUpperCase();

      const id = this.jefesSeleccionados[cargo];
      if (!id) {
        if (cargo === 'jefe_promocion') return 'ARIADNA MONSERRAT LOPEZ';
        if (cargo === 'jefe_actividades') return 'URIEL ARCADIO AVILA';
        return '';
      }
      const u = this.usuariosOficina.find(user => user.id == id);
      const fromUser = u ? this.formatNombre(u) : '';
      if (fromUser && fromUser !== 'ADMIN') return fromUser;
      if (cargo === 'jefe_promocion') return 'ARIADNA MONSERRAT LOPEZ';
      if (cargo === 'jefe_actividades') return 'URIEL ARCADIO AVILA';
      return fromUser;
    },
  },
  watch: {
    clubs: {
      handler(newClubs) {
        this.loadAsistenciasPorClubs();
        // Inicializar collapsed para nuevos clubs
        if (Array.isArray(newClubs)) {
          newClubs.forEach(club => {
            if (!(club.id in this.collapsedClubs)) {
              this.collapsedClubs[club.id] = true;
            }
          });
        }
      },
      deep: true,
    },
  },
  async mounted() {
    await this.loadCargos();
    this.loadAsistenciasPorClubs();
    // Inicializar collapsedClubs con todos colapsados
    if (Array.isArray(this.clubs)) {
      this.clubs.forEach(club => {
        this.collapsedClubs[club.id] = true;
      });
    }
    // Cargar html2pdf si no está disponible
    if (typeof html2pdf === "undefined") {
      const script = document.createElement("script");
      script.src = "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
      document.head.appendChild(script);
    }
    // Auto-refresco cada 30 segundos
    this.refreshInterval = setInterval(() => this.loadAsistenciasPorClubs(), 30000);
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  }
};
</script>

<style scoped>
.print-preview {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  gap: 20px;
  align-items: flex-start;
  justify-content: flex-start;
  padding: 20px;
  z-index: 1000;
  overflow-y: auto;
}

.preview-documento {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.print-preview .A4 {
  background: #fff;
  width: 215.9mm;
  height: 279.4mm;
  padding: 13mm 13mm 13mm 13mm;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 10pt;
  line-height: 1.2;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* Tablas */
.tabla-encabezado {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #000;
  margin-bottom: 30px;
  background: #fff;
}

.tabla-encabezado tr {
  border: 1px solid #000;
}

.tabla-encabezado td {
  border: 1px solid #000;
  padding: 8px;
}

/* Evita texto invisible por herencia de estilos globales */
.tabla-encabezado,
.tabla-encabezado td,
.tabla-encabezado strong,
.tabla-pie,
.tabla-pie td,
.cuerpo,
.cuerpo p,
.cuerpo strong {
  color: #000 !important;
}

.celda-logo {
  width: 80px;
  text-align: center;
  vertical-align: middle;
  background: #fff;
}

.logo-img {
  width: 70px;
  height: 70px;
  object-fit: contain;
}

.celda-titulo-principal {
  text-align: left;
  vertical-align: middle;
  font-size: 9pt;
  background: #fff;
  padding: 8px;
  padding-left: 10px;
}

.celda-codigo {
  width: 120px;
  text-align: left;
  vertical-align: top;
  background: #fff;
  padding: 8px;
  border-left: 1px solid #000;
  padding-left: 10px;
}

.celda-norma {
  text-align: left;
  font-size: 8pt;
  background: #fff;
  vertical-align: middle;
  padding-left: 10px;
}

.codigo-box {
  font-weight: bold;
  font-size: 9pt;
  margin-bottom: 8px;
  padding-bottom: 8px;
  border-bottom: 1px solid #000;
}

.revision-box {
  font-size: 9pt;
  margin-bottom: 8px;
  padding: 8px 0;
  border-bottom: 1px solid #000;
}

.pagina-box {
  font-size: 9pt;
  padding-top: 8px;
}

.separador-grande {
  height: 2px;
  background: #000;
  margin: 15px 0;
}

.titulo-constancia {
  text-align: center;
  font-size: 12pt;
  font-weight: bold;
  margin: 30px 0 15px 0;
}

.espacios {
  height: 10px;
}

.espacios-mediano {
  height: 30px;
}

.espacios-firma {
  height: 60px;
}

.espacios-firma-grandes {
  height: 100px;
}

.cuerpo {
  font-size: 11pt;
  line-height: 1.5;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  flex: 1;
}

.destinatario {
  font-weight: bold;
  margin-bottom: 15px;
  line-height: 1.4;
}

.texto {
  text-align: justify;
  margin-bottom: 15px;
}

.texto.justificado {
  text-align: justify;
}

.lugar-fecha {
  text-align: left;
  margin-bottom: 15px;
}

.tabla-firmas {
  width: 100%;
  border-collapse: collapse;
  margin-top: 30px;
  border: none;
  border-color: transparent;
}

.tabla-firmas tr {
  border: none;
  border-color: transparent;
}

.tabla-firmas td {
  border: none;
  border-color: transparent;
}

.celda-firma-iz,
.celda-firma-der {
  width: 50%;
  text-align: center;
  font-weight: bold;
  padding: 5px;
  border-color: transparent;
}

.celda-firma-nombre {
  width: 50%;
  text-align: center;
  padding: 5px;
}

.linea-firma {
  border-top: 1px solid #000;
  margin: 50px 20px 5px;
}

.nombre-firma {
  font-weight: bold;
  margin-top: 5px;
  font-size: 10pt;
}

.cargo-firma {
  font-size: 9pt;
  margin-top: 2px;
}

.pie-pagina {
  text-align: left;
  font-size: 10pt;
  margin-top: 20px;
  margin-bottom: 10px;
}

.tabla-pie {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
  padding-top: 5px;
}

.tabla-pie tr {
  border: none;
}

.tabla-pie td {
  border: none;
}

.pie-izq {
  text-align: left;
  font-size: 9pt;
  padding-top: 5px;
  margin-top: -50px;
  border: none;
}

.pie-der {
  text-align: right;
  font-size: 9pt;
  padding-top: 5px;
  border: none;
}

.acciones-preview {
  width: 350px;
  background: #fff;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  height: fit-content;
  position: sticky;
  top: 20px;
}

.acciones-preview h5 {
  border-bottom: 2px solid #0d6efd;
  padding-bottom: 10px;
  color: #0d6efd;
}

.campos-editar {
  display: flex;
  flex-direction: column;
  gap: 15px;
  margin-bottom: 20px;
}

.campo {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.campo label {
  font-weight: 600;
  font-size: 0.9rem;
  color: #333;
}

.campo input,
.campo select {
  padding: 8px 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 0.9rem;
}

.campo input:focus,
.campo select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
  outline: none;
}

.botones-acciones {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.botones-acciones button {
  padding: 10px 20px;
  font-weight: 600;
}

/* Estilos para expandir contenido en la hoja */
.print-preview .A4 table {
  border-collapse: collapse;
}

.print-preview .A4 table thead {
  background-color: #f5f5f5;
  display: table-header-group;
}

.print-preview .A4 table tr {
  page-break-inside: avoid;
}

.print-preview .A4 > * {
  margin: 0;
  padding: 0;
}

.cuerpo {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.cuerpo table {
  margin: 5px 0;
  font-size: 9pt;
}

.cuerpo table th,
.cuerpo table td {
  padding: 3px 4px;
  border: 0.5pt solid #000;
}

@media (max-width: 1200px) {
  .print-preview {
    flex-direction: column;
    align-items: center;
  }

  .acciones-preview {
    width: 100%;
    position: static;
  }
}

/* Estilos mejorados */
.text-primary { color: #2d3561 !important; }

.card {
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  overflow: hidden;
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
.bg-success { background: #28a745 !important; }
.bg-danger { background: #dc3545 !important; }
.bg-info { background: #17a2b8 !important; }

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
.table-bordered th, .table-bordered td {
  border: 1px solid #dee2e6;
}

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
.btn-success {
  background: #28a745;
  color: #fff;
}
.btn-info {
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

.text-muted { color: #6c757d; }
.fw-bold { font-weight: 600; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-2 { margin-top: 0.5rem; }
</style>
