<template>
  <div class="d-flex">
    <!-- Sidebar -->
    <div
      class="sidebar text-white p-3 d-flex flex-column justify-content-between"
    >
      <div>
        <div class="text-center mb-4">
          <img
            :src="resolveFotoUrl(usuarioActual.foto)"
            alt="Usuario"
            class="rounded-circle mb-2"
            width="80"
            height="80"
          />
          <h5 class="mb-0">{{ usuarioActual.nombre }}</h5>
          <small class="text-light">Perfil: {{ usuarioActual.tipo }}</small>
        </div>

        <!-- Menú -->
        <ul class="nav flex-column mt-4">
          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Gestion')"
              >Gestionar usuarios</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('ClubsR')"
              >Clubs registrados</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('AlumnosSR')"
              >Alumnos sin registrar</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('AlumnosR')"
              >Alumnos registrados</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Evaluaciones')"
              >Evaluaciones</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Periodos')"
              >Periodos</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Reinscripciones')"
              >Reinscripciones</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Constancias')"
              >Constancias</a
            >
          </li>

          <li class="nav-item">
            <a
              href="#"
              class="nav-link text-white"
              @click.prevent="setView('Auditoria')"
              >Auditoría</a
            >
          </li>
        </ul>
      </div>

      <div class="text-center mt-auto">
        <button class="btn btn-outline-light w-100" @click="cerrarSesion">
          Cerrar sesión
        </button>
      </div>
    </div>

    <!-- Contenido dinámico -->
    <div class="content flex-grow-1 p-4 bg-light overflow-auto">
      <component
        :is="currentView"
        :clubs="clubs"
        :alumnos="alumnos"
        :usuarios="usuarios"
        :fechas="fechas"
        :auditoria="auditoria"
        :carreras="carreras"
        @add-club="handleAddClub"
        @edit-club="handleEditClub"
        @delete-club="handleDeleteClub"
        @add-alumno="handleAddAlumno"
        @delete-alumno="handleDeleteAlumno"
        @assign-alumno="handleAssignAlumno"
        @refresh="refreshClubs"
        @log="handleLog"
        @import-unregistered="handleImportUnregistered"
        @filter-users="handleFilterUsers"
        @set-alumnos="handleSetAlumnos"
        @update-alumno="handleUpdateAlumno"
        @request-reload-alumnos="loadAlumnos"
        @show-error="showError"
        @show-toast="showToast"
      />
    </div>

    <!-- Toast container for messages (global) -->
    <div
      class="toast-container position-fixed bottom-0 end-0 p-3"
      style="z-index: 2000"
    >
      <div class="toast text-bg-success" id="toastSuccess">
        <div class="toast-body">{{ toastMsg }}</div>
      </div>
      <div class="toast text-bg-danger" id="toastError">
        <div class="toast-body">{{ errorMsg }}</div>
      </div>
    </div>
  </div>
</template>

<script>
/*
  Oficina.vue: componente contenedor que almacena y comparte datos con los hijos.
  Componentes hijos importados desde la misma carpeta 'components'.
*/

import Gestion from "../components/Gestion.vue";
import ClubsR from "../components/ClubsR.vue";
import AlumnosSR from "../components/AlumnosSR.vue";
import AlumnosR from "../components/AlumnosR.vue";
import Constancias from "../components/Constancias.vue";
import Auditoria from "../components/Auditoria.vue";
import Evaluaciones from "../components/Evaluaciones.vue";
import Periodos from "../components/Periodos.vue";
import Reinscripciones from "../components/Reinscripciones.vue";
import { getClubs, getAlumnos, createClub, updateClub, deleteClub, getMonitoresPorClub, getAllMonitoresWithClubs } from "../services/api";
import axios from "axios";
import { BACKEND } from "../services/backend";

export default {
  name: "Oficina",
  components: {
    Gestion,
    ClubsR,
    AlumnosSR,
    AlumnosR,
    Constancias,
    Auditoria,
    Evaluaciones,
    Periodos,
    Reinscripciones,
  },
  data() {
    return {
     usuarioActual: {
       nombre: "",
       apellidoP: "",
       apellidoM: "",
       tipo: "",
       foto: "https://cdn-icons-png.flaticon.com/512/847/847969.png", // fallback
     },
      currentView: "ClubsR",
      clubs: [], // se cargará desde API más adelante

      carreras: [
        { id: 1, nombre: 'Ingeniería Civil' },
        { id: 2, nombre: 'Ingeniería Industrial' },
        { id: 3, nombre: 'Ingeniería en Sistemas Computacionales' },
        { id: 4, nombre: 'Ingeniería en Gestión Empresarial' },
        { id: 5, nombre: 'Licenciatura en Administración' },
        { id: 6, nombre: 'Licenciatura en Arquitectura' },
        { id: 7, nombre: 'Ingeniería en Mecatrónica' },
      ],

      alumnos: [
        {
          nombre: "Ana",
          apellidoP: "Torres",
          apellidoM: "López",
          carrera: "Ingeniería en Sistemas",
          semestre: 4,
          control: "20201122",
          telefono: "5544332211",
          club: "Club de Robótica",
          faltas: 1,
          asistencias: { "01/11": true, "08/11": true, "15/11": false },
        },
      ],

      // usuarios para Gestion (ejemplo)
      usuarios: [
        {
          id: 1,
          nombre: "admin",
          tipo: "oficina",
          correo: "admin@ejemplo.com",
        },
        {
          id: 2,
          nombre: "monitor1",
          tipo: "monitor",
          correo: "mon1@ejemplo.com",
        },
      ],

      fechas: ["01/11", "08/11", "15/11"],

      // auditoría: {fecha, usuario, accion, tipo, descripcion}
      auditoria: [
        {
          fecha: new Date().toLocaleString(),
          usuario: "admin",
          accion: "Inicializar",
          tipo: "sistema",
          descripcion: "Datos iniciales cargados",
        },
      ],

      // lista temporal de alumnos sin registrar (para asignaciones)
      unregisteredList: [],

      toastMsg: "",
      errorMsg: "",
    };
  },
  methods: {
   resolveFotoUrl(foto) {
     if (!foto || typeof foto !== "string") return "https://cdn-icons-png.flaticon.com/512/847/847969.png";
     if (foto.startsWith("blob:")) return "https://cdn-icons-png.flaticon.com/512/847/847969.png";
     if (/^https?:\/\//i.test(foto)) return foto;
     const path = foto.startsWith("/") ? foto.slice(1) : foto;
     return `${BACKEND}/${path}`;
   },
   async cargarUsuarioActual() {
     try {
       const usuarioId = sessionStorage.getItem("usuarioId");
       console.log("usuarioId desde sessionStorage:", usuarioId); // DEBUG
    
       if (!usuarioId) {
         console.error("No hay usuarioId en sessionStorage");
         this.cerrarSesion();
         return;
       }

       const response = await axios.get(`${BACKEND}/Usuarios.php?id=${usuarioId}`);

       if (response.data.status === "success" && response.data.data) {
         this.usuarioActual = {
           ...response.data.data,
           foto: this.resolveFotoUrl(response.data.data?.foto)
         };
       } else {
         console.warn("Usuario no encontrado o respuesta inválida, cerrando sesión.");
         this.cerrarSesion();
         return;
       }
     } catch (error) {
       if (error?.response?.status === 404) {
         console.warn("usuarioId no existe en backend. Se limpia sesión.");
         this.cerrarSesion();
         return;
       }
       console.error("Error cargando usuario:", error);
     }
   },
    setView(view) {
      this.currentView = view;
    },

    cerrarSesion() {
     sessionStorage.removeItem("usuarioId");
     sessionStorage.removeItem("usuarioNombre");
     sessionStorage.removeItem("usuarioTipo");
      this.$router.push("/");
    },

    async loadClubs() {
      try {
        const rows = await getClubs();
        console.log("GET CLUBS RESPONSE:", rows);
        // Obtener todos los monitores
        const todosLosMonitores = await getAllMonitoresWithClubs();
        
        console.log('Todos los monitores:', todosLosMonitores);
        
        // Mapear a estructura de UI
        this.clubs = rows.map((r) => ({
          id: Number(r.id),
          nombre: r.nombre,
          tipo: r.tipo || 'CULTURAL',
          descripcion: r.descripcion,
          cupo: r.cupo_limite,
          ocupados: r.cupo_ocupado != null ? Number(r.cupo_ocupado) : 0,
          id_responsable: r.id_responsable,
          creado_en: r.creado_en,
          monitores: [] // inicializar array vacío para monitores
        }));
        
        // Asignar monitores a sus clubs
        for (const monitor of todosLosMonitores) {
          console.log(monitor.imagen);
          const club = this.clubs.find(c => c.id === Number(monitor.club_asignado));
          if (club) {
            club.monitores.push({
              id: monitor.id,
              nombre: monitor.nombre,
              apellidoP: monitor.apellidoP,
              apellidoM: monitor.apellidoM,
              usuario: monitor.usuario
            });
          }
        }
        
        console.log('Clubs cargados con monitores:', this.clubs);
      } catch (e) {
        this.showError(e.message || "No se pudo cargar clubs");
      }
    },

    // Refresco manual de clubs sin disparar la cadena completa de mounted()
    async refreshClubs() {
      await this.loadClubs();
    },

    async loadAlumnos() {
      try {
        const rows = await getAlumnos();
        // Mapear alumnos desde BD y resolver nombre de club y carrera por id
        this.alumnos = rows.map((r) => {
          const clubObj = Array.isArray(this.clubs)
            ? this.clubs.find((c) => c.id === Number(r.id_club))
            : null;
          const carreraObj = Array.isArray(this.carreras)
            ? this.carreras.find((c) => c.id === Number(r.carrera_id))
            : null;
          return {
            id: r.id,
            nombre: r.nombre,
            apellidoP: r.apellidoP,
            apellidoM: r.apellidoM,
            carrera: carreraObj ? carreraObj.nombre : '',
            semestre: r.semestre_id ?? '',
            control: r.numeroControl,
            telefono: r.telefono || '',
            // Guardamos tanto id como nombre para que el conteo por ID/nombre funcione
            clubId: r.id_club != null ? Number(r.id_club) : null,
            club_id: r.id_club != null ? Number(r.id_club) : null,
            club: clubObj ? clubObj.nombre : '',
            faltas: 0,
            asistencias: {},
          };
        });
        // Recalcular ocupados con los alumnos cargados
        this.recomputeOcupados();
        this.logAction('Sistema', 'Cargar', 'alumno', `Se cargaron ${this.alumnos.length} alumnos desde BD`);
        this.showToast('Alumnos cargados');
      } catch (e) {
        this.showError(e.message || 'No se pudo cargar alumnos');
      }
    },

    // ------------- Handlers emitidos por hijos -------------
    async handleAddClub(club, actor = "Usuario Oficina") {
      try {
        const payload = {
          nombre: club.nombre,
          tipo: club.tipo || 'CULTURAL',
          descripcion: club.descripcion ?? null,
          cupo_limite: Number(club.cupo) || 0,
          id_responsable: club.id_responsable ?? null,
        };
        const saved = await createClub(payload);
        const savedRow = saved?.data ?? saved;
        const mapped = {
          id: Number(savedRow.id),
          nombre: savedRow.nombre,
          tipo: savedRow.tipo || 'CULTURAL',
          descripcion: savedRow.descripcion,
          cupo: savedRow.cupo_limite,
          ocupados: 0,
          id_responsable: savedRow.id_responsable,
          creado_en: savedRow.creado_en,
        };
        if (!mapped.nombre) {
          await this.loadClubs();
          this.showToast("Club agregado correctamente");
          return;
        }
        this.clubs.unshift(mapped);
        this.logAction(
          actor,
          "Insertar",
          "club",
          `Se creó el club "${mapped.nombre}"`
        );
        this.showToast("Club agregado correctamente");
      } catch (e) {
        this.showError(e.message || "Error al crear el club");
      }
    },

    async handleEditClub({ id, club }, actor = "Usuario Oficina") {
      try {
        const index = this.clubs.findIndex((c) => Number(c.id) === Number(id));
        const current = this.clubs[index];
        if (!current || !current.id) throw new Error("Club sin id");
        const payload = {
          nombre: club.nombre ?? current.nombre,
          tipo: club.tipo ?? current.tipo,
          descripcion: club.descripcion ?? current.descripcion,
          cupo_limite: club.cupo !== undefined ? Number(club.cupo) : current.cupo,
          id_responsable: club.id_responsable ?? current.id_responsable,
        };
        const saved = await updateClub(current.id, payload);
        const savedRow = saved?.data ?? saved;
        const mapped = {
          id: Number(savedRow.id),
          nombre: savedRow.nombre,
          tipo: savedRow.tipo || current.tipo || 'CULTURAL',
          descripcion: savedRow.descripcion,
          cupo: savedRow.cupo_limite,
          ocupados: current.ocupados || 0,
          id_responsable: savedRow.id_responsable,
          creado_en: savedRow.creado_en,
        };
        this.clubs[index] = mapped;
        this.logAction(
          actor,
          "Editar",
          "club",
          `Se editó el club "${current.nombre}" -> "${mapped.nombre}"`
        );
        this.showToast("Club actualizado");
      } catch (e) {
        this.showError(e.message || "Error al actualizar el club");
      }
    },

    async handleDeleteClub(id, actor = "Usuario Oficina") {
      try {
        const index = this.clubs.findIndex((c) => Number(c.id) === Number(id));
        const current = this.clubs[index];
        if (!current || !current.id) throw new Error("Club sin id");
        await deleteClub(current.id);
        this.clubs.splice(index, 1);
        this.logAction(actor, "Eliminar", "club", `Se eliminó el club "${current.nombre}"`);
        this.showToast("Club eliminado");
      } catch (e) {
        this.showError(e.message || "Error al eliminar el club");
      }
    },

    handleAddAlumno(alumno, actor = "Usuario Oficina") {
      // validaciones ya hechas en componente hijo; aquí solo inserta y loggea
      this.alumnos.push({
        ...alumno,
        faltas: alumno.faltas || 0,
        asistencias: alumno.asistencias || {},
      });
      this.logAction(
        actor,
        "Insertar",
        "alumno",
        `Se registró al alumno "${alumno.nombre} ${alumno.apellidoP}"`
      );
      this.showToast("Alumno registrado correctamente");
    },

    handleUpdateAlumno({ index, alumno }, actor = 'Usuario Oficina') {
      if (index < 0 || index >= this.alumnos.length) return;
      const prev = this.alumnos[index];
      this.alumnos.splice(index, 1, { ...prev, ...alumno });
      this.logAction(actor, 'Editar', 'alumno', `Se actualizó el alumno "${alumno.nombre || prev.nombre} ${alumno.apellidoP || prev.apellidoP}"`);
      this.showToast('Alumno actualizado');
    },

    handleDeleteAlumno(index, actor = "Usuario Oficina") {
      const name = `${this.alumnos[index]?.nombre || ""} ${
        this.alumnos[index]?.apellidoP || ""
      }`;
      this.alumnos.splice(index, 1);
      this.logAction(
        actor,
        "Eliminar",
        "alumno",
        `Se eliminó al alumno "${name}"`
      );
      this.showToast("Alumno eliminado");
    },

    // Asignar un alumno (desde AlumnosSR) a un club (respeta cupo)
    handleAssignAlumno({ alumnoIndex, clubNombre }, actor = "Usuario Oficina") {
      const unregistered = this.unregisteredList || []; // handled in component hijo (emit)
      const alumno = this.unregisteredList
        ? this.unregisteredList[alumnoIndex]
        : null;
      // The AlumnosSR emits assign-alumno with alumno object; for simplicity expect child to pass alumno object
      // But parent will accept both forms: object or index + alumno provided in payload
      if (
        typeof alumnoIndex === "number" &&
        this.unregisteredList &&
        this.unregisteredList[alumnoIndex]
      ) {
        const al = this.unregisteredList.splice(alumnoIndex, 1)[0];
        // find club and increment ocupados if available
        const club = this.clubs.find((c) => c.nombre === clubNombre);
        if (club && club.ocupados < club.cupo) {
          club.ocupados++;
          // push to registered alumnos
          this.alumnos.push({
            ...al,
            club: clubNombre,
            faltas: 0,
            asistencias: {},
          });
          this.logAction(
            actor,
            "Insertar",
            "alumno",
            `Alumno "${al.nombre} ${al.apellidoP}" asignado a "${clubNombre}"`
          );
          this.showToast(`Alumno asignado a ${clubNombre}`);
        } else {
          this.showError("No hay cupo disponible en ese club");
        }
      } else if (alumnoIndex && alumnoIndex.nombre) {
        // payload is object {alumno, clubNombre}
        const al = alumnoIndex;
        const club = this.clubs.find((c) => c.nombre === clubNombre);
        if (club && club.ocupados < club.cupo) {
          club.ocupados++;
          this.alumnos.push({
            ...al,
            club: clubNombre,
            faltas: 0,
            asistencias: {},
          });
          this.logAction(
            actor,
            "Insertar",
            "alumno",
            `Alumno "${al.nombre} ${al.apellidoP}" asignado a "${clubNombre}"`
          );
          this.showToast(`Alumno asignado a ${clubNombre}`);
        } else {
          this.showError("No hay cupo disponible en ese club");
        }
      } else {
        this.showError("Asignación inválida");
      }
    },

    handleImportUnregistered(list) {
      // almacena temporalmente en memoria local la lista de sin registrar
      // para que AlumnosSR pueda administrarla
      this.unregisteredList = Array.isArray(list) ? list : [];
      this.logAction(
        "Sistema",
        "Insertar",
        "alumnos_sin_registrar",
        `Se importaron ${this.unregisteredList.length} registros`
      );
      this.showToast("Registros importados (sin registrar)");
    },

    handleEditClubConfirm(payload) {
      // wrapper placeholder if child wants direct parent edit call
      this.handleEditClub(payload);
    },

    handleEditAlumno() {
      // placeholder - se puede implementar si se agrega editar alumnos
    },

    // eliminadas versiones recursivas duplicadas de delete

    handleLog(entry) {
      this.logAction(
        entry.usuario || "Usuario Oficina",
        entry.accion,
        entry.tipo || "-",
        entry.descripcion || ""
      );
    },

    handleFilterUsers(filter) {
      // filter is object {tipo: 'oficina'|'monitor'|''}
      // devuelve lista filtrada (hijo puede solicitar)
      return this.usuarios.filter((u) =>
        filter.tipo ? u.tipo === filter.tipo : true
      );
    },

    handleSetAlumnos(list, actor = 'Sistema') {
      this.alumnos = Array.isArray(list) ? list : [];
      this.logAction(actor, 'Cargar', 'alumno', `Se cargaron ${this.alumnos.length} alumnos desde BD`);
      this.showToast('Alumnos cargados');
    },

    // Recalcula ocupados por club combinando conteo por id y por nombre, y respetando cupo_ocupado si existe
    recomputeOcupados() {
      const norm = (s) => (s == null ? '' : String(s)).trim().toLowerCase();
      const alumnos = Array.isArray(this.alumnos) ? this.alumnos : [];
      const clubs = Array.isArray(this.clubs) ? this.clubs : [];
      this.clubs = clubs.map((club) => {
        const id = club && club.id != null ? club.id : null;
        const porId = alumnos.filter((a) => {
          const alumnoClubId =
            a && a.clubId != null
              ? a.clubId
              : a && a.club_id != null
                ? a.club_id
                : a && a.club && a.club.id != null
                  ? a.club.id
                  : null;
          return alumnoClubId != null && String(alumnoClubId) === String(id);
        }).length;
        const porNombre = alumnos.filter((a) => {
          const nombreAlumnoClub =
            a && a.clubNombre != null
              ? a.clubNombre
              : a && typeof a.club === 'string'
                ? a.club
                : a && a.club && a.club.nombre
                  ? a.club.nombre
                  : '';
          return norm(nombreAlumnoClub) === norm(club.nombre);
        }).length;
        const base = club && club.cupo_ocupado != null ? Number(club.cupo_ocupado) : (club.ocupados || 0);
        const ocupados = Math.max(base, porId, porNombre);
        return { ...club, ocupados };
      });
    },

    // auditoría helper
    logAction(usuario, accion, tipo, descripcion) {
      this.auditoria.unshift({
        fecha: new Date().toLocaleString(),
        usuario,
        accion,
        tipo,
        descripcion,
      });
    },

    // mensajes
    showToast(msg) {
      this.toastMsg = msg;
      try {
        if (window && window.bootstrap && window.bootstrap.Toast) {
          const t = new window.bootstrap.Toast(
            document.getElementById("toastSuccess")
          );
          t.show();
        } else if (typeof bootstrap !== "undefined" && bootstrap.Toast) {
          const t = new bootstrap.Toast(
            document.getElementById("toastSuccess")
          );
          t.show();
        } else {
          console.log("Toast:", msg);
        }
      } catch (e) {
        console.log("Toast:", msg);
      }
    },

    showError(msg) {
      this.errorMsg = msg;
      try {
        if (window && window.bootstrap && window.bootstrap.Toast) {
          const t = new window.bootstrap.Toast(
            document.getElementById("toastError")
          );
          t.show();
        } else if (typeof bootstrap !== "undefined" && bootstrap.Toast) {
          const t = new bootstrap.Toast(document.getElementById("toastError"));
          t.show();
        } else {
          console.error("Error:", msg);
        }
      } catch (e) {
        console.error("Error:", msg);
      }
    },
  },
  async mounted() {
  // Esperar a que sessionStorage esté listo
  await new Promise(resolve => setTimeout(resolve, 100));
  
  const usuarioId = sessionStorage.getItem("usuarioId");
  console.log("usuarioId leído:", usuarioId);
  
  if (!usuarioId) {
    console.error("No hay usuarioId. Redirigiendo a login...");
    this.$router.push("/");
    return;
  }
  
  await this.cargarUsuarioActual();
  await this.loadClubs();
  await this.loadAlumnos();
},
};
</script>

<style scoped>
.sidebar {
  width: 250px;
  background-color: #12343b;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.15);
  border-radius: 5px;
}

.content {
  margin-left: 250px;
  height: 100vh;
  overflow-y: auto;
}

.scrollable {
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}
</style>
