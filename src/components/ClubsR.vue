<template>
  <div>
    <h3>Clubs registrados</h3>
    <div class="d-flex justify-content-between align-items-center my-3">
      <button class="btn btn-success" @click="openModal">Agregar club</button>
    </div>

    <div v-if="clubs && clubs.length">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>Nombre</th>
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
            <td>{{ club.descripcion }}</td>
            <td>{{ club.cupo }}</td>
            <td>{{ club.ocupados }}</td>
            <td>
              <button class="btn btn-warning btn-sm me-2" @click="startEdit(club)">Editar</button>
              <button class="btn btn-danger btn-sm" @click="confirmDelete(club)">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p v-else class="text-muted">No hay clubs registrados.</p>

    <!-- Modal agregar/editar -->
    <div class="modal fade" id="modalClubsR" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ editingIndex === null ? 'Agregar nuevo club' : 'Editar club' }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input v-model="localClub.nombre" class="form-control mb-2" placeholder="Nombre del club" />
            <input v-model="localClub.descripcion" class="form-control mb-2" placeholder="Descripción" />
            <input v-model.number="localClub.cupo" type="number" min="1" class="form-control mb-2" placeholder="Cupo máximo" />
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
export default {
  name: 'ClubsR',
  // Ahora recibimos alumnos también
  props: ['clubs', 'fechas', 'alumnos'],
  data() {
    return {
      localClub: { nombre: '', descripcion: '', cupo: 0 },
      editingIndex: null,
      pendingDeleteIndex: null
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

        return { ...c, id, nombre, descripcion, cupo, ocupados };
      });
    }
  },
  methods: {
    openModal() {
      this.editingIndex = null;
      this.localClub = { nombre: '', descripcion: '', cupo: 0 };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    startEdit(club) {
      this.editingIndex = club?.id ?? null;
      const c = club || {};
      this.localClub = { nombre: c.nombre, descripcion: c.descripcion, cupo: c.cupo };
      new bootstrap.Modal(document.getElementById('modalClubsR')).show();
    },
    saveClub() {
      if (!this.localClub.nombre || !this.localClub.descripcion || this.localClub.cupo <= 0) {
        this.$emit('log', { usuario: 'Usuario Oficina', accion: 'Error', tipo: 'club', descripcion: 'Campos obligatorios' });
        return this.$root.showError ? this.$root.showError('Todos los campos son obligatorios') : null;
      }
      if (this.editingIndex === null) {
        this.$emit('add-club', { ...this.localClub }, 'Usuario Oficina');
      } else {
        this.$emit('edit-club', { id: this.editingIndex, club: { ...this.localClub } }, 'Usuario Oficina');
      }
      bootstrap.Modal.getInstance(document.getElementById('modalClubsR')).hide();
    },
    confirmDelete(club) {
      this.pendingDeleteIndex = club?.id ?? null;
      new bootstrap.Modal(document.getElementById('confirmDeleteClub')).show();
    },
    deleteConfirmed() {
      this.$emit('delete-club', this.pendingDeleteIndex, 'Usuario Oficina');
      bootstrap.Modal.getInstance(document.getElementById('confirmDeleteClub')).hide();
    }
  }
};
</script>