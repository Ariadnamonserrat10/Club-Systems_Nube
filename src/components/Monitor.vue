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
      <pre>{{ clubs }}</pre>

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
          </tr>
        </thead>
        <tbody>
          <tr v-if="alumnosClub.length === 0">
            <td :colspan="(fechasData.length + 2)" class="text-center text-muted">
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
                class="badge"
                :class="alumno.faltas < 3 ? 'bg-success' : 'bg-danger'"
              >
                {{ alumno.faltas < 3 ? 'Acreditado' : 'No acreditado' }}
              </span>
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
  </div>
</template>

<script>
import axios from "axios";
import { getAsistenciasPorClub, crearFechaAsistencias, actualizarAsistencia, getAlumnos } from "../services/api";
import { BACKEND } from "../services/backend";

export default {
  name: "Monitor",
  // No props; el componente se auto gestiona desde backend
  data() {
    return {
      usuarioActual: {
        nombre: "",
        apellidoP: "",
        tipo: "",
        foto: "https://cdn-icons-png.flaticon.com/512/847/847969.png", // fallback
        club_asignado: null,
        club_nombre: null,
      },
      monitor: { nombre: "Carlos Pérez", club: "Club de Robótica" }, // se reemplazará si BD trae club
      clubsList: [],          // lista de clubs desde BD
      selectedClubId: null,   // id seleccionado en el dropdown
      assigning: false,       // flag al asignar
      nuevaFecha: "",
      mensaje: { texto: "", tipo: "" },
      // listas manejadas desde backend
      alumnosData: [],
      fechasData: [],
    };
  },
  computed: {
    clubs() {
      return this.clubsList;
    },
    alumnosClub() {
      // Ya vienen filtrados por club desde backend; asegurar asistencias y faltas
      return (this.alumnosData || []).map((a) => {
        if (!a.asistencias) {
          a.asistencias = {};
        }
        // Inicializar para todas las fechas
        this.fechasData.forEach((f) => {
          if (!(f in a.asistencias)) {
            a.asistencias[f] = false;
          }
        });
        // Recalcular faltas
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
        if (!usuarioId) {
          sessionStorage.clear();
          this.$router.push("/");
          return;
        }

        let response;
        try {
          response = await axios.get(`${BACKEND}/obtenerUsuario.php?id=${usuarioId}`);
        } catch (err) {
          if (err?.response?.status === 404) {
            response = await axios.get(`${BACKEND}/Usuarios.php?id=${usuarioId}`);
          } else {
            throw err;
          }
        }

        if (response.data?.status === "success" && response.data.data) {
          const datos = response.data.data;
          this.usuarioActual.nombre = datos.nombre || "";
          this.usuarioActual.apellidoP = datos.apellidoP || "";
          this.usuarioActual.tipo = datos.tipo || sessionStorage.getItem("usuarioTipo") || "";
          this.usuarioActual.foto = this.resolveFotoUrl(datos.foto) || this.usuarioActual.foto;
          this.usuarioActual.club_asignado = datos.club_asignado ?? null;
          this.usuarioActual.club_nombre = datos.club_nombre ?? null;
          this.selectedClubId = this.usuarioActual.club_asignado;

          // Si la BD devuelve el club asignado al monitor, opcionalmente actualizarlo
          if (datos.club_nombre) this.monitor.club = datos.club_nombre;
        } else {
          console.warn("Usuario no encontrado o respuesta inválida, cerrando sesión.");
          sessionStorage.clear();
          this.$router.push("/");
          return;
        }
      } catch (error) {
        if (error?.response?.status === 404) {
          console.warn("usuarioId no existe en backend. Se limpia sesión.");
          sessionStorage.clear();
          this.$router.push("/");
          return;
        }
        console.error("Error cargando usuario Monitor:", error);
      }
    },

    async loadAsistencias() {
      const clubId = this.usuarioActual.club_asignado;
      if (!clubId) {
        console.log('No hay club asignado');
        return;
      }
      try {
        console.log('Cargando asistencias para club:', clubId);
        
        // 1) Cargar alumnos por club (garantiza lista aunque no haya asistencias)
        let alumnosClub = [];
        try {
          const res = await fetch(`${BACKEND}/Alumnos.php?club_id=${encodeURIComponent(clubId)}`);
          const json = await res.json();
          console.log('Respuesta de Alumnos.php:', json);
          if (res.ok && json && Array.isArray(json.data)) {
            alumnosClub = json.data;
            console.log('Alumnos cargados:', alumnosClub.length);
          }
        } catch (e) {
          console.error('Error cargando alumnos por club:', e);
        }

        // 2) Cargar asistencias por club
        const data = await getAsistenciasPorClub(clubId);
        console.log('Datos de asistencias:', data);
        console.log('DEBUG desde backend:', data._debug);
        
        // fechas en ISO yyyy-mm-dd
        const nuevasFechas = Array.isArray(data.fechas) ? data.fechas : [];
        console.log('Nuevas fechas desde BD:', nuevasFechas);
        
        // Mantener fechas existentes + agregar nuevas de la BD
        this.fechasData = [...new Set([...this.fechasData, ...nuevasFechas])].sort();
        console.log('Fechas actuales después de merge:', this.fechasData);

        // Preferir alumnos del endpoint de asistencias si vienen, si no usar los de alumnos por club
        const alumnos = (Array.isArray(data.alumnos) && data.alumnos.length) ? data.alumnos : alumnosClub;
        console.log('Total de alumnos a mostrar:', alumnos.length);
        
        const asist = data.asistencias || {};

        // Mapear estructura de asistencias por alumno y fecha
        const alumnosMappeados = (alumnos || []).map((al) => {
          const map = { ...(asist[al.id] || {}) };
          // Asegurar que todas las fechas estén presentes
          this.fechasData.forEach(fecha => {
            if (!(fecha in map)) {
              map[fecha] = false;
            }
          });
          return { ...al, asistencias: map, faltas: Object.values(map).filter(v => v === false).length };
        });
        
        // Asignar directamente (Vue 3 es reactivo por default)
        this.alumnosData = alumnosMappeados;
        console.log('alumnosData actualizado:', this.alumnosData.length);
        
        // Forzar actualización
        this.$forceUpdate();
        
      } catch (e) {
        console.error('Error cargando asistencias:', e);
        this.mostrarMensaje('No se pudieron cargar asistencias', 'alert-danger');
      }
    },

    async cargarClubs() {
      try {
        const res = await axios.get(`${BACKEND}/getClubs.php`);
        if (res.data?.status === "success" && Array.isArray(res.data.data)) {
          console.log("RESPUESTA:", res.data);
          console.log("CLUBS ARRAY:", res.data.data);
          this.clubsList = res.data.data;
        } else {
          console.warn("No se obtuvieron clubs:", res.data);
        }
      } catch (err) {
        console.error("Error cargando lista de clubs:", err);
      }
    },

    mostrarMensaje(texto, tipo) {
      this.mensaje.texto = texto;
      this.mensaje.tipo = tipo;
      setTimeout(() => (this.mensaje.texto = ""), 2500);
    },
    cerrarSesion() {
      this.mostrarMensaje("Sesión cerrada correctamente.", "alert-info");
      // pequeño retardo para mostrar el mensaje antes de redirigir
      setTimeout(() => {
        sessionStorage.clear();
        this.$router.push("/"); // regresar al login
      }, 1500);
    },
    async agregarFecha() {
      if (!this.nuevaFecha) {
        this.mostrarMensaje("Seleccione una fecha antes de agregar.", "alert-warning");
        return;
      }
      if (!this.usuarioActual.club_asignado) {
        this.mostrarMensaje("No hay club asignado.", "alert-danger");
        return;
      }
      const fechaISO = this.nuevaFecha; // YYYY-MM-DD desde input type="date"
      console.log('Intentando agregar fecha:', fechaISO);
      console.log('Fecha ya existe?', this.fechasData.includes(fechaISO));
      
      if (this.fechasData.includes(fechaISO)) {
        this.mostrarMensaje("La fecha ya está registrada.", "alert-danger");
        return;
      }
      try {
        // crear registros default (presente=false) para cada alumno del club
        const registros = this.alumnosClub.map(a => ({ alumno_id: a.id, presente: false }));
        console.log('Enviando registros:', registros);
        
        const respuesta = await crearFechaAsistencias({ club_id: this.usuarioActual.club_asignado, fecha: fechaISO, registros });
        console.log('Respuesta del servidor:', respuesta);
        console.log('Fecha creada en backend');
        
        // Agregar la fecha localmente SIN recargar todo
        this.fechasData.push(fechaISO);
        this.fechasData.sort();
        console.log('Fecha agregada localmente. fechasData ahora:', this.fechasData);
        
        // Inicializar asistencia para la nueva fecha en todos los alumnos (sin borrar las anteriores)
        this.alumnosData.forEach(alumno => {
          if (!alumno.asistencias) {
            alumno.asistencias = {};
          }
          // Solo agregar si no existe
          if (!(fechaISO in alumno.asistencias)) {
            alumno.asistencias[fechaISO] = false;
          }
        });
        
        this.$forceUpdate();
        this.nuevaFecha = "";
        this.mostrarMensaje("Fecha agregada correctamente.", "alert-success");
      } catch (e) {
        console.error('Error creando fecha:', e);
        console.error('Detalles del error:', JSON.stringify(e));
        this.mostrarMensaje("No se pudo crear la fecha: " + (e.message || e), "alert-danger");
      }
    },
    async actualizarFaltas(alumno) {
      // Este método se dispara al cambiar un checkbox
      alumno.faltas = Object.values(alumno.asistencias).filter((v) => v === false).length;
    },
    async guardarCambios() {
      try {
        console.log('Guardando cambios...');
        // Guardar todas las asistencias de todos los alumnos para todas las fechas
        const promesas = [];
        
        for (const alumno of this.alumnosData) {
          for (const fecha of this.fechasData) {
            const presente = !!alumno.asistencias[fecha];
            promesas.push(
              actualizarAsistencia({ 
                alumno_id: alumno.id, 
                fecha, 
                presente 
              }).catch(e => console.error(`Error guardando ${alumno.id} en ${fecha}:`, e))
            );
          }
        }
        
        await Promise.all(promesas);
        console.log('Todos los cambios guardados en BD');
        this.mostrarMensaje("Cambios guardados correctamente.", "alert-success");
      } catch (e) {
        console.error('Error guardando cambios:', e);
        this.mostrarMensaje("Error al guardar cambios.", "alert-danger");
      }
    },
    async onToggleAsistencia(alumno, fecha) {
      const presente = !!alumno.asistencias[fecha];
      try {
        await actualizarAsistencia({ alumno_id: alumno.id, fecha, presente });
        this.actualizarFaltas(alumno);
      } catch (e) {
        console.error('Error actualizando asistencia:', e);
        // revertir cambio
        alumno.asistencias[fecha] = !presente;
        this.mostrarMensaje('No se pudo actualizar asistencia', 'alert-danger');
      }
    },
    async asignarClub() {
      if (!this.selectedClubId) return;
      this.assigning = true;
      try {
        const usuarioId = sessionStorage.getItem("usuarioId");
        const payload = { usuarioId: Number(usuarioId), club_id: Number(this.selectedClubId) };
        const res = await axios.post(`${BACKEND}/asignarClub.php`, payload, {
          headers: { "Content-Type": "application/json" }
        });

        if (res.data?.status === "success") {
          // actualizar vista local
          const club = this.clubsList.find(c => c.id === Number(this.selectedClubId));
          this.usuarioActual.club_asignado = club ? club.id : this.selectedClubId;
          this.usuarioActual.club_nombre = club ? club.nombre : `Club ID ${this.selectedClubId}`;
          this.monitor.club = this.usuarioActual.club_nombre;
          this.mensaje = { texto: "Club asignado correctamente.", tipo: "success" };
        } else {
          this.mensaje = { texto: res.data.message || "Error al asignar club.", tipo: "error" };
        }
      } catch (err) {
        console.error("Error asignando club:", err);
        this.mensaje = { texto: "Error de red al asignar club.", tipo: "error" };
      } finally {
        this.assigning = false;
        setTimeout(() => (this.mensaje.texto = ""), 2500);
      }
    },
  },
  async mounted() {
    await this.cargarUsuarioActual();
    await this.loadAsistencias();
  },
};
</script>

<style scoped>
.card {
  border-radius: 12px;
}
.table {
  font-size: 0.95rem;
}
img {
  object-fit: cover;
}
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter,
.fade-leave-to {
  opacity: 0;
}
</style>
