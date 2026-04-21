<template>
  <div>
    <h4 class="text-primary mb-3">Listas de Clubs</h4>

    <div class="mb-3">
      <label class="form-label">Selecciona un club:</label>
      <select v-model="clubSeleccionado" class="form-select">
        <option disabled value="">-- Seleccionar --</option>
        <option v-for="(club, i) in clubs" :key="i" :value="club.nombre">
          {{ club.nombre }}
        </option>
      </select>
    </div>

    <div v-if="clubSeleccionado">
      <h5 class="mt-3 text-secondary">Alumnos del {{ clubSeleccionado }}</h5>
      <table class="table table-bordered table-hover mt-3">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th v-for="fecha in fechasCols" :key="fecha">{{ fecha }}</th>
            <th>Acreditado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(alumno, index) in alumnosClub" :key="alumno.id || alumno.control || (alumno.nombre + '-' + alumno.apellidoP + '-' + alumno.apellidoM + '-' + index)">
            <td>
              {{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}
            </td>
            <td v-for="fecha in fechasCols" :key="fecha" class="text-center">
              <span v-if="alumno.asistencias && alumno.asistencias[fecha]" class="text-success fw-bold" style="font-size: 1.5rem">✔</span>
              <span v-else class="text-danger fw-bold" style="font-size: 1.5rem">✖</span>
            </td>
            <td>
              <span
                class="badge"
                :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
              >
                {{ alumno.faltas < 3 ? "Acreditado" : "No acreditado" }}
              </span>
            </td>
            <td>
              <button
                class="btn btn-sm btn-outline-info"
                :disabled="alumno.faltas >= 3"
                @click="descargarConstancia(alumno)"
              >
                Descargar
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="text-end mt-3">
        <button class="btn btn-success" @click="descargarTodas">
          Descargar todas (PDF)
        </button>
      </div>
    </div>

    <div v-else class="text-muted mt-4 text-center">
      Selecciona un club para mostrar su lista.
    </div>

    <!-- Vista previa / Plantilla imprimible -->
    <div v-if="previewData" class="print-preview">
      <div class="preview-documento">
        <div class="constancia A4" id="constancia">
          <!-- ENCABEZADO EN TEXTO (evita depender de imagen con celdas vacías) -->
          <table
            class="tabla-encabezado"
            cellpadding="8"
            cellspacing="0"
            style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif; font-size: 9pt"
          >
            <tr>
              <td rowspan="2" style="border: 1px solid #000; width: 95px; text-align: center; vertical-align: middle;">
                <img src="../Img/Logo.jpg" alt="Logo" style="max-width: 75px; height: auto" />
              </td>
              <td style="border: 1px solid #000; vertical-align: middle; font-weight: bold; font-size: 11pt; line-height: 1.1;">
                Formato&nbsp;&nbsp;&nbsp; para&nbsp;&nbsp;&nbsp; el&nbsp;&nbsp;&nbsp; Registro&nbsp;&nbsp;&nbsp; de&nbsp;&nbsp;&nbsp; Participantes&nbsp;&nbsp;&nbsp; de<br>
                Actividades Culturales y/o Deportivas
              </td>
              <td rowspan="2" style="border: 1px solid #000; width: 32%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                  <tr>
                    <td style="border-bottom: 1px solid #000; padding: 6px;"><strong>Código:TecNM-VI-PO-003-01</strong></td>
                  </tr>
                  <tr>
                    <td style="border-bottom: 1px solid #000; padding: 6px;"><strong>Revisión: 0</strong></td>
                  </tr>
                  <tr>
                    <td style="padding: 6px;"><strong>Página 1 de 1</strong></td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr>
              <td style="border: 1px solid #000; vertical-align: middle; font-weight: bold; font-size: 10pt;">
                Referencia a la Norma ISO 9001:2015: 8.1,&nbsp; 8.2.1,&nbsp; 8.2.2
              </td>
            </tr>
          </table>

          <h2
            class="titulo-constancia"
            style="font-size: 11pt; margin: 90px 0 15px 0"
          >
            CONSTANCIA DE CUMPLIMIENTO DE ACTIVIDAD CULTURAL Y/O DEPORTIVA
          </h2>

          <div class="espacios-mediano"></div>

          <div class="cuerpo">
            <p
              class="destinatario"
              style="font-size: 10pt; margin: 0 0 20px 0; line-height: 1.6"
            >
              {{ getNombreJefe('jefa_servicios') || 'C. __________________________' }}<br />
              {{ tituloServiciosEscolares() }} DEL DEPARTAMENTO DE SERVICIOS ESCOLARES<br />
              PRESENTE
            </p>

            <div class="espacios"></div>

            <p class="texto justificado">
              La que suscribe {{ getNombreJefe('jefe_actividades') || '__________________________' }}, {{ tituloFirma('jefe_actividades') }} del Departamento de
              Actividades Extraescolares, por este medio se permite hacer de su
              conocimiento que la estudiante
              <strong>{{ toUpper(previewData.estudianteNombre) }}</strong> con
              número de control
              <strong>{{ toUpper(previewData.numeroControl) }}</strong> de la
              carrera de <strong>{{ toUpper(previewData.carrera) }}</strong
              >, ha cumplido su actividad extraescolar en el club de
              <strong>{{ toUpper(previewData.club) }}</strong> con el nivel de
              desempeño <strong>{{ toUpper(previewData.desempeno) }}</strong> y
              un valor numérico de
              <strong>{{ desempenoValor(previewData.desempeno) }}</strong>
              durante el periodo escolar
              <strong>{{ toUpper(previewData.mesInicio) }}-{{ toUpper(previewData.mesFin) }} {{ previewData.anioPeriodo }}</strong
              >, con un valor curricular de 1 crédito.
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
              style="width: 100%; border-collapse: collapse"
            >
              <tr>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  ATENTAMENTE
                </td>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
                  Vo. Bo.
                </td>
              </tr>
              <tr>
                <td colspan="2" class="espacios-firma-grandes"></td>
              </tr>
              <tr>
                <td
                  style="
                    width: 50%;
                    text-align: center;
                    vertical-align: top;
                    padding: 0;
                    border: none;
                  "
                >
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
                    border: none;
                  "
                >
                  <div class="linea-firma"></div>
                  <div class="nombre-firma">{{ getNombreJefe('jefe_actividades') || '__________________________' }}</div>
                  <div class="cargo-firma">
                    {{ tituloFirma('jefe_actividades') }} DEL DEPARTAMENTO DE ACTIVIDADES EXTRAESCOLARES
                  </div>
                </td>
              </tr>
            </table>

            <div class="pie-pagina">
              c.c.p. Jefe (a) de Departamento Correspondiente
            </div>

            <table class="tabla-pie">
              <tr>
                <td class="pie-izq">TecNM-VI-PO-003-05</td>
                <td class="pie-der">Rev. 0</td>
              </tr>
            </table>
          </div>
        </div>
      </div>

      <div class="acciones-preview">
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
        <div class="botones-acciones">
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
import { getAsistenciasPorClub, getFirmas, saveConfig } from "../services/api";
export default {
  name: "Listas",
  props: ["clubs", "alumnos", "fechas", "usuarios"],
  data() {
    return {
      clubSeleccionado: "",
      previewData: null,
      periodoActual: this.getPeriodoActual(),
      alumnosData: [],
      fechasData: [],
      jefesSeleccionados: {
        jefe_promocion: "",
        jefe_promocion_nombre: "ARIADNA MONSERRAT LOPEZ",
        jefe_actividades: "",
        jefe_actividades_nombre: "URIEL ARCADIO AVILA",
        jefa_servicios_nombre: "",
      },
    };
  },
  computed: {
    fechasCols() {
      return this.fechasData && this.fechasData.length
        ? this.fechasData
        : this.fechas || [];
    },
    alumnosClub() {
      // Preferir alumnos desde backend; si no hay, usar fallback desde props
      let base =
        this.alumnosData && this.alumnosData.length
          ? this.alumnosData.slice()
          : (this.alumnos || [])
              .filter((a) => a.club === this.clubSeleccionado)
              .map((a) => {
                const asist = a.asistencias || {};
                const faltas = Object.values(asist).filter(
                  (v) => v === false,
                ).length;
                return { ...a, asistencias: asist, faltas };
              });
      base.sort(
        (a, b) =>
          (a.apellidoP || "").localeCompare(b.apellidoP || "") ||
          (a.apellidoM || "").localeCompare(b.apellidoM || "") ||
          (a.nombre || "").localeCompare(b.nombre || ""),
      );
      return base;
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
    usuariosOficina() {
      if (!Array.isArray(this.usuarios)) return [];
      return this.usuarios.filter(u => (u.tipo || "").toString().toUpperCase() === "OFICINA");
    },
  },
  methods: {
    async loadAsistencias() {
      // Limpiar datos anteriores para evitar mezcla visual mientras se actualiza
      this.alumnosData = [];
      const club = (this.clubs || []).find(c => c.nombre === this.clubSeleccionado);

      // Si no hay club o ID, usar los datos de props como respaldo
      if (!club || !club.id) {
        this.fechasData = [];
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          this.alumnosData = [];
        }
        return;
      }

      try {
        const data = await getAsistenciasPorClub(club.id);
        this.fechasData = Array.isArray(data.fechas) ? data.fechas : [];
        const alumnos = Array.isArray(data.alumnos) ? data.alumnos : [];

        // Si el backend no devuelve alumnos, usar props
        if (!alumnos.length && this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          const asist = data.asistencias || {};
          this.alumnosData = alumnos.map(al => {
            const map = { ...(asist[al.id] || {}) };
            const faltas = Object.values(map).filter(v => v === false).length;
            return { ...al, asistencias: map, faltas };
          });
        }
      } catch (e) {
        console.error('Error cargando asistencias:', e);
        this.fechasData = [];
        // Si hay error, cargar desde props
        if (this.alumnos && this.alumnos.length) {
          const alumnosDelClub = this.alumnos.filter(a => a.club === this.clubSeleccionado);
          this.alumnosData = alumnosDelClub.map(a => ({
            ...a,
            asistencias: a.asistencias || {},
            faltas: Object.values(a.asistencias || {}).filter(v => v === false).length
          }));
        } else {
          this.alumnosData = [];
        }
      }
    },
    actualizarFaltas(alumno) {
      const asist = alumno.asistencias || {};
      const totalFaltas = Object.values(asist).filter(
        (v) => v === false,
      ).length;
      this.$set
        ? this.$set(alumno, "faltas", totalFaltas)
        : (alumno.faltas = totalFaltas);
    },
    toUpper(v) {
      return (v == null ? "" : String(v)).toUpperCase();
    },
    desempenoValor(desempeno) {
      const mapa = { EXCELENTE: 4, BUENO: 3, REGULAR: 2, DEFICIENTE: 1 };
      return mapa[(desempeno || "").toUpperCase()] || 0;
    },
    getPeriodoActual() {
      const f = new Date();
      const mes = f.getMonth();
      const anio = f.getFullYear();
      return mes >= 0 && mes <= 5
        ? { mesInicio: "Enero", mesFin: "Junio", anioPeriodo: anio }
        : { mesInicio: "Agosto", mesFin: "Diciembre", anioPeriodo: anio };
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
    tipoActividad(nombreClub) {
      return this.isCulturalName(nombreClub) ? "CULTURAL" : "DEPORTIVA";
    },
    imprimirConstancia(alumno) {
      const club = alumno.club || this.clubSeleccionado || "";
      const periodo = this.getPeriodoActual();
      const data = {
        estudianteNombre:
          `${alumno.nombre || ""} ${alumno.apellidoP || ""} ${alumno.apellidoM || ""}`.trim(),
        numeroControl: alumno.control || "",
        carrera: alumno.carrera || "INGENIERÍA EN SISTEMAS COMPUTACIONALES",
        club: club.toLowerCase(),
        desempeno: (
          alumno.desempeno ||
          (alumno.faltas <= 1
            ? "EXCELENTE"
            : alumno.faltas === 2
              ? "BUENO"
              : "REGULAR")
        ).toString(),
        mesInicio: periodo.mesInicio,
        mesFin: periodo.mesFin,
        anioPeriodo: periodo.anioPeriodo,
        tipoActividad: this.tipoActividad(club),
      };
      this.previewData = { ...data };
    },
    descargarConstancia(alumno) {
      this.imprimirConstancia(alumno);
    },
    generarPDF() {
      const nodo = document.getElementById("constancia");
      if (!nodo || !this.previewData) return;

      const nombre = `${this.previewData.estudianteNombre.replace(/\s+/g, "_")}_${this.previewData.numeroControl}`;
      const opt = {
        margin: [15, 15, 15, 15],
        filename: `Constancia_${nombre}.pdf`,
        image: { type: "jpeg", quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { orientation: "portrait", unit: "mm", format: "letter" },
      };

      // Cargar html2pdf desde CDN
      if (typeof html2pdf === "undefined") {
        const script = document.createElement("script");
        script.src =
          "https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js";
        document.head.appendChild(script);
        script.onload = () => {
          html2pdf().set(opt).from(nodo).save();
        };
      } else {
        html2pdf().set(opt).from(nodo).save();
      }
    },
    descargarTodas() {
      console.warn('Descargar todas (PDF) no implementado');
    },
    formatNombre(u) {
      if (!u) return "";
      return `${u.nombre || ""} ${u.apellidoP || ""} ${u.apellidoM || ""}`.trim().toUpperCase();
    },
    sanitizeNombreInput(campo) {
      const raw = (this.jefesSeleccionados[campo] || "").toString();
      const base = raw
        .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g, "")
        .replace(/\s+/g, " ")
        .trimStart()
        .toUpperCase();

      let letras = 0;
      let out = "";
      for (const ch of base) {
        if (/[A-ZÁÉÍÓÚÜÑ]/u.test(ch)) {
          if (letras >= 50) continue;
          letras += 1;
          out += ch;
        } else if (ch === " ") {
          if (out && out[out.length - 1] !== " ") out += ch;
        }
      }

      this.jefesSeleccionados[campo] = out;
    },
    inferGenero(nombre) {
      const first = (nombre || "").trim().split(/\s+/)[0] || "";
      if (!first) return "M";
      return first.endsWith("A") ? "F" : "M";
    },
    tituloFirma(cargo) {
      const nombre = this.getNombreJefe(cargo).replace(/^C\.\s*/i, "");
      if (!nombre) {
        return cargo === "jefa_servicios" ? "JEFA" : "JEFE";
      }
      return this.inferGenero(nombre) === "F" ? "JEFA" : "JEFE";
    },
    tituloServiciosEscolares() {
      const raw = (this.jefesSeleccionados.jefa_servicios_nombre || "").toUpperCase().trim();
      if (/^ENCARGAD[OA]\b/.test(raw)) return "ENCARGADO";
      if (/^JEFA\b/.test(raw)) return "JEFA";
      if (/^JEFE\b/.test(raw)) return "JEFE";
      const nombre = this.getNombreJefe("jefa_servicios").replace(/^C\.\s*/i, "");
      return this.inferGenero(nombre) === "F" ? "JEFA" : "JEFE";
    },
    async guardarPreferenciaManual(cargo) {
      this.sanitizeNombreInput(cargo + "_nombre");
      const valor = String(this.jefesSeleccionados[cargo + "_nombre"] || "")
        .replace(/\s+/g, " ")
        .trim();
      this.jefesSeleccionados[cargo + "_nombre"] = valor;
      try {
        await saveConfig("firma_" + cargo, valor);
      } catch (e) {
        console.error("Error guardando firma manual:", e);
      }
    },
    async loadCargos() {
      try {
        const firmas = await getFirmas();
        if (firmas.jefe_actividades) {
          this.jefesSeleccionados.jefe_actividades = firmas.jefe_actividades.id || "";
          if (firmas.jefe_actividades.nombre) {
            this.jefesSeleccionados.jefe_actividades_nombre = String(firmas.jefe_actividades.nombre).toUpperCase();
            this.sanitizeNombreInput("jefe_actividades_nombre");
          }
        }
        if (firmas.jefe_promocion) {
          this.jefesSeleccionados.jefe_promocion = firmas.jefe_promocion.id || "";
          if (firmas.jefe_promocion.nombre) {
            this.jefesSeleccionados.jefe_promocion_nombre = String(firmas.jefe_promocion.nombre).toUpperCase();
            this.sanitizeNombreInput("jefe_promocion_nombre");
          }
        }
        if (firmas.jefa_servicios) {
          this.jefesSeleccionados.jefa_servicios_nombre = firmas.jefa_servicios.nombre;
          this.sanitizeNombreInput("jefa_servicios_nombre");
        }
      } catch (e) {
        console.error("Error cargando firmas:", e);
      }
    },
    getNombreJefe(cargo) {
      if (cargo === "jefa_servicios") {
        const raw = String(this.jefesSeleccionados.jefa_servicios_nombre || "").toUpperCase();
        const limpio = raw.replace(/^(JEFA|JEFE|ENCARGAD[OA])\s+/i, "").trim();
        return limpio ? "C. " + limpio : "";
      }

      const manualKey = cargo + "_nombre";
      const manual = (this.jefesSeleccionados[manualKey] || "").toString().trim();
      if (manual) return manual.toUpperCase();

      const id = this.jefesSeleccionados[cargo];
      if (!id) {
        if (cargo === "jefe_promocion") return "ARIADNA MONSERRAT LOPEZ";
        if (cargo === "jefe_actividades") return "URIEL ARCADIO AVILA";
        return "";
      }
      const u = this.usuariosOficina.find(user => user.id == id);
      const fromUser = u ? this.formatNombre(u) : "";
      if (fromUser && fromUser !== "ADMIN") return fromUser;
      if (cargo === "jefe_promocion") return "ARIADNA MONSERRAT LOPEZ";
      if (cargo === "jefe_actividades") return "URIEL ARCADIO AVILA";
      return fromUser;
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
  },
  watch: {
    clubSeleccionado() {
      this.loadAsistencias();
    },
  },
  mounted() {
    this.loadCargos();
    // si ya hay un club seleccionado inicial, cargar
    if (this.clubSeleccionado) this.loadAsistencias();
  },
};
</script>

<style scoped>
.table {
  font-size: 0.95rem;
}

.print-preview {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  gap: 20px;
  align-items: flex-start;
  justify-content: flex-start;
  z-index: 2000;
  padding: 20px;
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
  max-width: 215.9mm;
  padding: 15mm;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
  width: 100%;
  font-family: Arial, sans-serif;
  color: #000;
  font-size: 11pt;
  line-height: 1.2;
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
  margin: 50px 0 20px 0;
}

.espacios {
  height: 20px;
}

.espacios-mediano {
  height: 30px;
}

.espacios-firma {
  height: 30px;
}

.espacios-firma-grandes {
  height: 80px;
}

.cuerpo {
  font-size: 11pt;
  line-height: 1.5;
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
}

.celda-firma-iz,
.celda-firma-der {
  width: 50%;
  text-align: center;
  font-weight: bold;
  padding: 5px;
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
  border-top: 1px solid #000;
  padding-top: 5px;
}

.pie-izq {
  text-align: left;
  font-size: 9pt;
  padding-top: 5px;
}

.pie-der {
  text-align: right;
  font-size: 9pt;
  padding-top: 5px;
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
</style>
