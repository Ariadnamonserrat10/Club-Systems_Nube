<template>
  <div>
    <h3 v-if="!modalOnly">Clubs registrados</h3>
    <div v-if="!modalOnly" class="d-flex justify-content-between align-items-center my-3">
      <div>
        <button v-if="canManage" class="btn btn-success me-2" @click="openModal">Agregar club</button>
        <button class="btn btn-outline-secondary" @click="$emit('refresh')" title="Refrescar lista">
          <i class="bi bi-arrow-clockwise"></i> Refrescar
        </button>
      </div>
    </div>

    <div v-if="!modalOnly && clubs && clubs.length">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Descripción</th>
            <th>Cupo</th>
            <th>Ocupados</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <!-- Usamos clubsConOcupados en lugar de clubs -->
          <tr v-for="(club, index) in clubsConOcupados" :key="(club.id || club.nombre) + '-' + index">
            <td>{{ club.nombre }}</td>
            <td>
              <span :class="['badge', club.tipo === 'DEPORTIVO' ? 'bg-primary' : 'bg-secondary']">
                {{ club.tipo || 'CULTURAL' }}
              </span>
            </td>
            <td>{{ club.descripcion }}</td>
            <td>{{ club.cupo }}</td>
            <td>{{ club.ocupados }}</td>
            <td>
              <button class="btn btn-outline-primary btn-sm me-2" @click="verAsistencias(club)">Ver lista y asistencias</button>
              <button v-if="canManage" class="btn btn-warning btn-sm me-2" @click="startEdit(club)">Editar</button>
              <button v-if="canManage" class="btn btn-danger btn-sm" @click="confirmDelete(club)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else-if="!modalOnly" class="text-muted">No hay clubs registrados.</p>

    <div v-if="!modalOnly && clubSeleccionado" class="card border-0 shadow-sm mt-4">
      <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:#4c38ff">
        <strong>{{ clubSeleccionado.nombre }} — Lista y asistencias</strong>
        <button class="btn btn-sm btn-light" @click="clubSeleccionado = null">Cerrar</button>
      </div>
      <div class="card-body">
        <div v-if="cargandoDetalle" class="text-center py-3">Cargando asistencias...</div>
        <div v-else-if="errorDetalle" class="alert alert-danger mb-0">{{ errorDetalle }}</div>
        <div v-else class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Alumno</th><th>Número de control</th><th v-for="fecha in detalleClub.fechas" :key="fecha" class="text-center">{{ fechaCorta(fecha) }}</th><th class="text-center">Faltas</th></tr></thead>
            <tbody>
              <tr v-for="alumno in detalleClub.alumnos" :key="alumno.id"><td>{{ nombreAlumno(alumno) }}</td><td>{{ alumno.numeroControl }}</td><td v-for="fecha in detalleClub.fechas" :key="fecha" class="text-center"><span :class="asistio(alumno.id, fecha) ? 'text-success' : 'text-danger'">{{ asistio(alumno.id, fecha) ? '✓' : '✕' }}</span></td><td class="text-center fw-bold">{{ faltasAlumno(alumno.id) }}</td></tr>
              <tr v-if="!detalleClub.alumnos.length"><td :colspan="detalleClub.fechas.length + 3" class="text-center text-muted py-4">No hay alumnos inscritos en este club.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Modal agregar/editar -->
    <div class="modal fade" id="modalClubsR" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingIndex === null ? 'Agregar nuevo club' : 'Editar club' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-2">
              <label class="form-label mb-1 fw-semibold">Nombre del club</label>
              <input v-model="localClub.nombre" class="form-control" placeholder="Ej: Danza Folclórica" @input="soloTextoClub" />
              <div class="form-text">Solo letras y espacios. Cada palabra debe iniciar con mayúscula.</div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-1 fw-semibold">Tipo de club</label>
              <select v-model="localClub.tipo" class="form-select">
                <option value="CULTURAL">Cultural (danza, teatro, música…)</option>
                <option value="DEPORTIVO">Deportivo (fútbol, voleibol, basquetbol…)</option>
              </select>
              <div class="form-text">Selecciona la categoría que corresponde a la actividad del club.</div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-1 fw-semibold">Descripción</label>
              <input v-model="localClub.descripcion" class="form-control" placeholder="Ej: Grupo De Baile Tradicional" @input="soloTextoDescripcion" />
              <div class="form-text">Descripción breve de la actividad que realiza el club.</div>
            </div>
            <div class="mb-2">
              <label class="form-label mb-1 fw-semibold">Cupo máximo</label>
              <input v-model.number="localClub.cupo" type="number" min="1" max="50" class="form-control" placeholder="Ej: 25" />
              <div class="form-text">Número máximo de alumnos permitidos (entre 1 y 50).</div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-primary" @click="saveClub">{{ editingIndex === null ? 'Guardar' : 'Actualizar' }}</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm delete modal -->
    <div class="modal fade" id="confirmDeleteClub" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>¿Seguro que desea eliminar este club?</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger" @click="deleteConfirmed">Eliminar</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { getAsistenciasPorClub } from '../services/api';

export default {
  name: 'ClubsR',
  // Ahora recibimos alumnos también
  props: {
    clubs: { type: Array, default: () => [] },
    fechas: { type: Array, default: () => [] },
    alumnos: { type: Array, default: () => [] },
    canManage: { type: Boolean, default: false },
    modalOnly: { type: Boolean, default: false }
  },
  data() {
    return {
      localClub: { nombre: '', tipo: 'CULTURAL', descripcion: '', cupo: 0 },
      editingIndex: null,
      pendingDeleteIndex: null,
      clubSeleccionado: null,
      detalleClub: { fechas: [], alumnos: [], asistencias: {} },
      cargandoDetalle: false,
      errorDetalle: ''
    };
  },
  computed: {
    // Mapea campos que vienen de la BD a un formato uniforme y calcula ocupados.
    clubsConOcupados() {
      const rawClubs = Array.isArray(this.clubs) ? this.clubs : [];
      const alumnos = Array.isArray(this.alumnos) ? this.alumnos : [];
      const norm = (s) => {
        const str = s == null ? '' : String(s);
        return str
          .trim()
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '');
      };

      return rawClubs.map((c) => {
        // Normalización de campos desde la BD
        const id = c.id ?? c.ID ?? c.id_club ?? null;
        const nombre = c.nombre ?? c.Name ?? c.titulo ?? '';
        const tipo = c.tipo ?? c.Type ?? 'CULTURAL';
        const descripcion = c.descripcion ?? c.description ?? '';
        const cupo = c.cupo != null ? c.cupo : c.cupo_limite != null ? c.cupo_limite : 0;
        // Preferimos valor de BD si existe
        let ocupados = c.ocupados != null ? c.ocupados : c.cupo_ocupado != null ? c.cupo_ocupado : null;

        // Si no viene de BD o viene 0, intentamos contar alumnos inscritos
        if (ocupados == null || ocupados === 0) {
          // Primero intento por id si el club tiene id
          let ocupadosPorId = 0;
          if (id != null) {
            ocupadosPorId = alumnos.filter((a) => {
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
          }

          // Luego intento por nombre (por si los alumnos no traen id del club)
          const ocupadosPorNombre = alumnos.filter((a) => {
            const nombreAlumnoClub =
              a && a.clubNombre != null
                ? a.clubNombre
                : a && typeof a.club === 'string'
                  ? a.club
                  : a && a.club && a.club.nombre
                    ? a.club.nombre
                    : '';
            return norm(nombreAlumnoClub) === norm(nombre);
          }).length;

          ocupados = Math.max(ocupadosPorId, ocupadosPorNombre);
        }

        return { ...c, id, nombre, tipo, descripcion, cupo, ocupados };
      });
    }
  },
  methods: {
    async verAsistencias(club) {
      this.clubSeleccionado = club;
      this.cargandoDetalle = true;
      this.errorDetalle = '';
      try { this.detalleClub = await getAsistenciasPorClub(club.id); }
      catch (error) { this.errorDetalle = error.message || 'No se pudieron cargar las asistencias'; }
      finally { this.cargandoDetalle = false; }
    },
    nombreAlumno(alumno) { return `${alumno.nombre || ''} ${alumno.apellidoP || ''} ${alumno.apellidoM || ''}`.replace(/\s+/g, ' ').trim(); },
    asistio(id, fecha) { return Boolean(this.detalleClub.asistencias?.[id]?.[fecha]); },
    faltasAlumno(id) { return this.detalleClub.fechas.filter(fecha => !this.asistio(id, fecha)).length; },
    fechaCorta(fecha) { const parts = String(fecha).split('-'); return parts.length === 3 ? `${parts[2]}/${parts[1]}` : fecha; },
    emitError(msg) {
      this.$emit('show-error', msg);
    },
    normalizarTexto(valor) {
      // No forzar trim/capitalización mientras el usuario escribe para no romper espacios.
      return (valor || '').replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '');
    },
    formatearTitulo(valor) {
      const limpio = (valor || '')
        .replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '')
        .replace(/\s+/g, ' ')
        .trim();
      if (!limpio) return '';
      return limpio
        .split(' ')
        .map((p) => p ? (p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()) : '')
        .join(' ');
    },
    soloTextoClub() {
      this.localClub.nombre = this.normalizarTexto(this.localClub.nombre);
    },
    soloTextoDescripcion() {
      this.localClub.descripcion = this.normalizarTexto(this.localClub.descripcion);
    },
    openModal() {
      if (!this.canManage) return;
      this.editingIndex = null;
      this.localClub = { nombre: '', tipo: 'CULTURAL', descripcion: '', cupo: 0 };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    startEdit(club) {
      if (!this.canManage) return;
      this.editingIndex = club?.id ?? null;
      const c = club || {};
      this.localClub = { nombre: c.nombre, tipo: c.tipo || 'CULTURAL', descripcion: c.descripcion, cupo: c.cupo };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    validarClub() {
      const nombre = (this.localClub.nombre || '').trim();
      const descripcion = (this.localClub.descripcion || '').trim();
      const cupo = Number(this.localClub.cupo);

      if (!nombre) return 'El nombre del club es obligatorio.';
      if (nombre.length > 100) return 'El nombre debe tener máximo 100 caracteres.';
      if (!/^[A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]*$/.test(nombre)) {
        return 'El nombre solo permite letras y espacios, y debe iniciar con mayúscula.';
      }

      if (!descripcion) return 'La descripción del club es obligatoria.';
      if (!/^[A-ZÁÉÍÓÚÑ][A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]*$/.test(descripcion)) {
        return 'La descripción solo permite texto y debe iniciar con mayúscula.';
      }

      if (!Number.isInteger(cupo) || cupo < 1 || cupo > 50) {
        return 'El cupo debe ser un número entero entre 1 y 50.';
      }

      return '';
    },
    saveClub() {
      if (!this.canManage) return;
      // Formatear al guardar, sin interferir con la escritura del usuario.
      this.localClub.nombre = this.formatearTitulo(this.localClub.nombre);
      this.localClub.descripcion = this.formatearTitulo(this.localClub.descripcion);
      const error = this.validarClub();
      if (error) {
        this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Error', tipo: 'club', descripcion: 'Campos obligatorios' });
        this.emitError(error);
        return;
      }
      if (this.editingIndex === null) {
        this.$emit('add-club', { ...this.localClub }, 'Usuario Oficina');
      } else {
        this.$emit('edit-club', { id: this.editingIndex, club: { ...this.localClub } }, 'Usuario Oficina');
      }
      // Mover foco antes de cerrar para evitar el warning de aria-hidden
      if (document.activeElement) document.activeElement.blur();
      bootstrap.Modal.getInstance(document.getElementById('modalClubsR')).hide();
    },
    confirmDelete(club) {
      if (!this.canManage) return;
      this.pendingDeleteIndex = club?.id ?? null;
      new bootstrap.Modal(document.getElementById('confirmDeleteClub')).show();
    },
    deleteConfirmed() {
      if (!this.canManage) return;
      this.$emit('delete-club', this.pendingDeleteIndex, 'Usuario Oficina');
      // Mover foco antes de cerrar para evitar el warning de aria-hidden
      if (document.activeElement) document.activeElement.blur();
      bootstrap.Modal.getInstance(document.getElementById('confirmDeleteClub')).hide();
    }
  },
  mounted() {
    // Solución global al warning de aria-hidden:
    // Cada vez que Bootstrap VAYA A ocultar cualquiera de los modales,
    // movemos el foco fuera antes de que se aplique aria-hidden="true".
    const blurOnHide = () => {
      if (document.activeElement && document.activeElement !== document.body) {
        document.activeElement.blur();
      }
    };
    ['modalClubsR', 'confirmDeleteClub'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.addEventListener('hide.bs.modal', blurOnHide);
    });
  }
};
</script>
