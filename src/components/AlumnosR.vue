<template>
  <div>
    <h3>Alumnos registrados</h3>

    <div class="toolbar">
      <button class="btn btn-primary" @click="openAddModal">Agregar alumno</button>
    </div>

    <table class="table">
      <thead>
        <tr>
          <th>Nombre completo</th>
          <th class="col-carrera">Carrera</th>
          <th class="col-sem">Semestre</th>
          <th class="col-control">Número de control</th>
          <th class="col-tel">Teléfono</th>
          <th class="col-club">Club</th>
          <th class="col-acciones">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(alumno, index) in alumnos" :key="alumno.id || alumno.control || index">
          <td>{{ alumno.nombre }} {{ alumno.apellidoP }} {{ alumno.apellidoM }}</td>
          <td class="col-carrera" :title="alumno.carrera">{{ displayCarrera(alumno.carrera) }}</td>
          <td class="col-sem">{{ alumno.semestre }}</td>
          <td class="col-control">{{ alumno.control }}</td>
          <td class="col-tel">{{ alumno.telefono }}</td>
          <td class="col-club">{{ alumno.club }}</td>
          <td class="col-acciones">
            <div class="actions">
              <button class="btn btn-warning btn-sm" @click="startEdit(index)">Modificar</button>
              <button class="btn btn-danger btn-sm" @click="confirmDelete(index)">Eliminar</button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal agregar (sin Bootstrap) -->
    <div v-if="showAddModal" class="modal-backdrop" @click.self="showAddModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h5>Agregar alumno</h5>
          <button class="btn-close" @click="showAddModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="grid">
            <input v-model="form.nombre" class="input" placeholder="Nombre(s)" @input="soloTexto('nombre')" />
            <input v-model="form.apellidoP" class="input" placeholder="Apellido paterno" @input="soloTexto('apellidoP')" />
            <input v-model="form.apellidoM" class="input" placeholder="Apellido materno" @input="soloTexto('apellidoM')" />

            <select v-model="form.carrera" class="input">
              <option disabled value="">Selecciona carrera</option>
              <option v-for="c in carreras" :key="c.id">{{ c.nombre }}</option>
            </select>

            <select v-model="form.semestre" class="input">
              <option disabled value="">Selecciona semestre</option>
              <option v-for="n in 7" :key="n">{{ n }}</option>
            </select>

            <input v-model="form.control" maxlength="8" class="input" placeholder="Número de control (8 dígitos)" @input="soloNumeros('control')" />
            <input v-model="form.telefono" class="input" placeholder="Teléfono" @input="soloNumeros('telefono')" />

            <select v-model="form.clubId" class="input">
              <option disabled value="">Selecciona club</option>
              <option v-for="c in clubs" :key="c.id || c.nombre" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>
        </div>
        <div class="modal-footer modal-actions">
          <button class="btn btn-light" @click="showAddModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="saveRegistered" :disabled="loading">Guardar</button>
        </div>
      </div>
    </div>

    <!-- Modal editar (sin Bootstrap) -->
    <div v-if="showEditModal" class="modal-backdrop" @click.self="showEditModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h5>Modificar alumno</h5>
          <button class="btn-close" @click="showEditModal = false">×</button>
        </div>
        <div class="modal-body">
          <div class="grid">
            <input v-model="editForm.nombre" class="input" placeholder="Nombre(s)" @input="soloTextoEdit('nombre')" />
            <input v-model="editForm.apellidoP" class="input" placeholder="Apellido paterno" @input="soloTextoEdit('apellidoP')" />
            <input v-model="editForm.apellidoM" class="input" placeholder="Apellido materno" @input="soloTextoEdit('apellidoM')" />

            <select v-model="editForm.carrera" class="input">
              <option disabled value="">Selecciona carrera</option>
              <option v-for="c in carreras" :key="c.id">{{ c.nombre }}</option>
            </select>

            <select v-model="editForm.semestre" class="input">
              <option disabled value="">Selecciona semestre</option>
              <option v-for="n in 7" :key="n">{{ n }}</option>
            </select>

            <input v-model="editForm.control" maxlength="8" class="input" placeholder="Número de control (8 dígitos)" @input="soloNumerosEdit('control')" />
            <input v-model="editForm.telefono" class="input" placeholder="Teléfono" @input="soloNumerosEdit('telefono')" />

            <select v-model="editForm.clubId" class="input">
              <option disabled value="">Selecciona club</option>
              <option v-for="c in clubs" :key="c.id || c.nombre" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>
        </div>
        <div class="modal-footer modal-actions">
          <button class="btn btn-light" @click="showEditModal = false">Cancelar</button>
          <button class="btn btn-primary" @click="updateRegistered" :disabled="loading">Actualizar</button>
        </div>
      </div>
    </div>

    <!-- Confirmación eliminar (sin Bootstrap) -->
    <div v-if="showDeleteModal" class="modal-backdrop" @click.self="showDeleteModal = false">
      <div class="modal-card">
        <div class="modal-header">
          <h5>Confirmar eliminación</h5>
          <button class="btn-close" @click="showDeleteModal = false">×</button>
        </div>
        <div class="modal-body">
          <p>¿Seguro que desea eliminar este alumno?</p>
        </div>
        <div class="modal-footer modal-actions">
          <button class="btn btn-light" @click="showDeleteModal = false">Cancelar</button>
          <button class="btn btn-danger" @click="deleteConfirmed">Eliminar</button>
        </div>
      </div>
    </div>

    <!-- Mensajes simples -->
    <div v-if="toastMsg" class="toast-local success">{{ toastMsg }}</div>
    <div v-if="errorMsg" class="toast-local error">{{ errorMsg }}</div>
  </div>
</template>

<script>
import { createAlumno, updateAlumno } from '../services/api';

export default {
  name: 'AlumnosR',
  props: ['alumnos', 'clubs', 'carreras'],
  data() {
    return {
      form: { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '', clubId: '' },
      editForm: { id: null, nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '', clubId: '' },
      editingIndex: null,
      pendingDeleteIndex: null,
      showAddModal: false,
      showEditModal: false,
      showDeleteModal: false,
      loading: false,
      toastMsg: '',
      errorMsg: '',
      carreraAlias: {
        'Ingeniería Civil': 'ICIV',
        'Ingeniería Industrial': 'IIND',
        'Ingeniería en Sistemas Computacionales': 'ISC',
        'Ingeniería en Gestión Empresarial': 'ING',
        'Licenciatura en Administración': 'ADMON',
        'Licenciatura en Arquitectura': 'ARQU',
        'Ingeniería en Mecatrónica': 'IMCT',
      },
    };
  },
  methods: {
    normalizarTexto(valor) {
      const limpio = (valor || '')
        .replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '')
        .replace(/\s+/g, ' ')
        .trimStart();
      return limpio
        .split(' ')
        .map(p => p ? (p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()) : '')
        .join(' ')
        .trimEnd();
    },
    openAddModal() {
      this.form = { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '', clubId: '' };
      this.showAddModal = true;
    },
    soloTexto(campo) {
      this.form[campo] = this.normalizarTexto(this.form[campo]);
    },
    soloTextoEdit(campo) {
      this.editForm[campo] = this.normalizarTexto(this.editForm[campo]);
    },
    soloNumeros(campo) {
      this.form[campo] = (this.form[campo] || '').replace(/\D/g, '');
    },
    soloNumerosEdit(campo) {
      this.editForm[campo] = (this.editForm[campo] || '').replace(/\D/g, '');
    },
    showToast(msg) { this.toastMsg = msg; setTimeout(() => this.toastMsg = '', 2500); },
    showError(msg) { this.errorMsg = msg; setTimeout(() => this.errorMsg = '', 3500); },

    displayCarrera(nombre) {
      return this.carreraAlias[nombre] || nombre || '';
    },

    async saveRegistered() {
      if (!this.form.nombre || !/^\d{8}$/.test(this.form.control) || (this.form.telefono && !/^\d+$/.test(this.form.telefono)) || !this.form.clubId) {
        return this.showError('Verifique los campos (control 8 dígitos, teléfono numérico, club obligatorio)');
      }
      try {
        this.loading = true;
        const id_club = this.form.clubId ? Number(this.form.clubId) : null;
        const carObj = Array.isArray(this.carreras) ? this.carreras.find(c => c.nombre === this.form.carrera) : null;
        const carrera_id = carObj && carObj.id ? carObj.id : null;
        const payload = {
          nombre: this.form.nombre,
          apellidoP: this.form.apellidoP,
          apellidoM: this.form.apellidoM,
          numeroControl: this.form.control,
          telefono: this.form.telefono || null,
          carrera_id,
          semestre_id: this.form.semestre ? Number(this.form.semestre) : null,
          id_club,
        };
        const saved = await createAlumno(payload);
        // emitir agregado local con id y solicitar recarga desde padre
        const clubName = (Array.isArray(this.clubs) && id_club) ? (this.clubs.find(c => Number(c.id) === id_club)?.nombre || '') : this.form.club;
        this.$emit('add-alumno', {
          id: saved.id,
          nombre: saved.nombre,
          apellidoP: saved.apellidoP,
          apellidoM: saved.apellidoM,
          carrera: this.form.carrera,
          semestre: this.form.semestre,
          control: saved.numeroControl,
          telefono: saved.telefono,
          club: clubName,
        }, 'Usuario Oficina');
        this.$emit('request-reload-alumnos');
        this.showAddModal = false;
        this.form = { nombre: '', apellidoP: '', apellidoM: '', carrera: '', semestre: '', control: '', telefono: '', club: '', clubId: '' };
        this.showToast('Alumno registrado en BD');
      } catch (e) {
        this.showError(e.message || 'Error al registrar alumno');
      } finally {
        this.loading = false;
      }
    },

    startEdit(index) {
      this.editingIndex = index;
      const a = this.alumnos[index];
      this.editForm = { ...a };
      // inicializar clubId a partir del nombre actual
      if (a && a.club && Array.isArray(this.clubs)) {
        const found = this.clubs.find(c => c.nombre === a.club);
        this.editForm.clubId = found ? found.id : '';
      } else {
        this.editForm.clubId = '';
      }
      this.showEditModal = true;
    },

    async updateRegistered() {
      if (!this.editForm || !this.editForm.id) {
        return this.showError('Alumno sin id para actualizar');
      }
      if (this.editForm.control && !/^\d{8}$/.test(this.editForm.control)) {
        return this.showError('El número de control debe tener 8 dígitos');
      }
      try {
        this.loading = true;
        const id_club = this.editForm.clubId ? Number(this.editForm.clubId) : null;
        const carObj = Array.isArray(this.carreras) ? this.carreras.find(c => c.nombre === this.editForm.carrera) : null;
        const carrera_id = carObj && carObj.id ? carObj.id : null;
        const payload = {
          nombre: this.editForm.nombre,
          apellidoP: this.editForm.apellidoP,
          apellidoM: this.editForm.apellidoM,
          numeroControl: this.editForm.control,
          telefono: this.editForm.telefono || null,
          carrera_id,
          semestre_id: this.editForm.semestre ? Number(this.editForm.semestre) : null,
          id_club,
        };
        await updateAlumno(this.editForm.id, payload);
        const clubName = (Array.isArray(this.clubs) && id_club) ? (this.clubs.find(c => Number(c.id) === id_club)?.nombre || this.editForm.club) : this.editForm.club;
        this.$emit('update-alumno', { index: this.editingIndex, alumno: { ...this.editForm, club: clubName } }, 'Usuario Oficina');
        this.$emit('request-reload-alumnos');
        this.showEditModal = false;
        this.showToast('Alumno actualizado en BD');
      } catch (e) {
        this.showError(e.message || 'Error al actualizar alumno');
      } finally {
        this.loading = false;
      }
    },

    confirmDelete(index) {
      this.pendingDeleteIndex = index;
      this.showDeleteModal = true;
    },
    deleteConfirmed() {
      this.$emit('delete-alumno', this.pendingDeleteIndex, 'Usuario Oficina');
      this.showDeleteModal = false;
    }
  }
};
</script>

<style scoped>
.toolbar {
  display: flex; justify-content: flex-start; align-items: center; margin: 12px 0;
}
.table {
  width: 100%; border-collapse: collapse; background: #fff; table-layout: fixed;
}
.table th, .table td { border: 1px solid #ddd; padding: 8px; }
.table thead th { background: #e3f2fd; text-align: left; }

.btn { padding: 6px 12px; border-radius: 6px; border: 1px solid #999; background: #f5f5f5; cursor: pointer; }
.btn-primary { background: #1976d2; color: #fff; border-color: #1976d2; }
.btn-light { background: #fafafa; color: #333; border-color: #ddd; }
.btn-danger { background: #d32f2f; color: #fff; border-color: #d32f2f; }
.btn-warning { background: #f9a825; color: #fff; border-color: #f9a825; }
.btn-sm { padding: 4px 10px; font-size: 0.875rem; }
.btn:disabled { opacity: .6; cursor: not-allowed; }
.btn:hover { filter: brightness(0.95); }
.btn-close { border: none; background: transparent; font-size: 22px; line-height: 1; cursor: pointer; }

.input { padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-width: 0; }
.grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }

.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.modal-card { width: min(800px, 95%); background: #fff; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.2); display: flex; flex-direction: column; }
.modal-header { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid #eee; }
.modal-body { padding: 14px; }
.modal-footer { padding: 10px 14px; display: flex; justify-content: flex-end; border-top: 1px solid #eee; }
.modal-actions { gap: 10px; }

.toast-local { position: fixed; right: 12px; bottom: 12px; padding: 10px 14px; border-radius: 6px; color: #fff; z-index: 2100; }
.toast-local.success { background: #43a047; }
.toast-local.error { background: #e53935; }
.actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
}
.actions .btn {
  min-width: 88px;
  white-space: nowrap;
  text-align: center;
}
.col-carrera { max-width: 160px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.col-sem { width: 80px; text-align: center; }
.col-control { width: 130px; }
.col-tel { width: 140px; }
.col-club { max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.col-acciones { width: 210px; }

@media (max-width: 1200px) {
  .actions {
    flex-direction: column;
    align-items: stretch;
  }
  .actions .btn {
    width: 100%;
  }
}
@media (max-width: 900px) {
  .toolbar { flex-wrap: wrap; gap: 10px; }
  .table { table-layout: auto; min-width: 980px; }
  .modal-card { width: calc(100vw - 20px); max-height: calc(100dvh - 20px); overflow-y: auto; }
}
@media (max-width: 576px) {
  .grid { grid-template-columns: 1fr; }
  .modal-footer { flex-wrap: wrap; gap: 8px; }
  .modal-footer .btn { flex: 1 1 120px; }
}
</style>
