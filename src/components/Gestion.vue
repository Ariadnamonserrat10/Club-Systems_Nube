<template>
  <div>
    <h3>Gestionar usuarios</h3>

    <div
      v-if="localMsg"
      class="alert alert-dismissible fade show"
      :class="localMsgType === 'success' ? 'alert-success' : 'alert-danger'"
      role="alert"
    >
      {{ localMsg }}
      <button type="button" class="btn-close" @click="clearLocalMsg"></button>
    </div>

    <div class="row mb-3">
      <div class="col-md-4">
        <select v-model="filterTipo" class="form-select">
          <option value="">Todos</option>
          <option value="OFICINA">Oficina</option>
          <option value="MONITOR">Monitor</option>
        </select>
      </div>
      <div class="col-md-8 text-end">
        <button class="btn btn-primary" @click="loadUsuarios">Refrescar</button>
      </div>
    </div>

    <table class="table table-striped table-bordered">
      <thead class="table-primary">
        <tr>
          <th>Foto</th>
          <th>Nombre</th>
          <th>Usuario</th>
          <th>Tipo</th>
          <th>Club asignado</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in filteredUsers" :key="u.id">
          <td>
            <img :src="resolveFotoUrl(u.foto) || placeholder" alt="foto" width="36" height="36" class="rounded-circle"/>
          </td>
          <td>{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
          <td>{{ u.usuario }}</td>
          <td><span class="badge" :class="u.tipo==='OFICINA'?'bg-primary':'bg-info'">{{ u.tipo }}</span></td>
          <td>{{ u.club_asignado || '-' }}</td>
          <td>
            <button class="btn btn-warning btn-sm me-2" @click="openEdit(u)">Editar</button>
            <button class="btn btn-danger btn-sm" @click="requestDelete(u)">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="modal fade" id="confirmDeleteUser" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Confirmar eliminación</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="mb-0">¿Eliminar usuario {{ pendingDeleteUser?.nombre || '' }}?</p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-danger" @click="confirmDelete">Eliminar</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Editar -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal d-block" tabindex="-1" @click.self="closeModal">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Editar usuario</h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input v-model="form.nombre" class="form-control" @input="soloTexto('nombre')"/>
              </div>
              <div class="col-md-4">
                <label class="form-label">Apellido P.</label>
                <input v-model="form.apellidoP" class="form-control" @input="soloTexto('apellidoP')"/>
              </div>
              <div class="col-md-4">
                <label class="form-label">Apellido M.</label>
                <input v-model="form.apellidoM" class="form-control" @input="soloTexto('apellidoM')"/>
              </div>
              <div class="col-md-4">
                <label class="form-label">Usuario</label>
                <input v-model="form.usuario" class="form-control" @input="soloUsuario('usuario')"/>
              </div>
              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select v-model="form.tipo" class="form-select">
                  <option value="OFICINA">OFICINA</option>
                  <option value="MONITOR">MONITOR</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Club asignado (id)</label>
                <input v-model.number="form.club_asignado" type="number" class="form-control"/>
              </div>
              <div class="col-md-4">
                <label class="form-label">Número de control</label>
                <input
                  v-model="form.numeroControl"
                  class="form-control"
                  maxlength="8"
                  inputmode="numeric"
                  placeholder="8 dígitos"
                  @input="soloNumeros('numeroControl')"
                />
                <small class="text-muted">Debe tener exactamente 8 dígitos numéricos.</small>
              </div>
            </div>
            <div class="row g-2 mt-2">
              <div class="col-md-6">
                <label class="form-label">Nueva contraseña (opcional)</label>
                <input v-model="form.password" type="password" class="form-control"/>
                <small class="text-muted">Requisitos: exactamente 8 caracteres, 1 mayúscula, 1 minúscula y 1 número.</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input v-model="form.telefono" class="form-control" @input="soloNumeros('telefono')"/>
              </div>
              <div class="col-md-6">
                <label class="form-label">Foto (desde dispositivo)</label>
                <input type="file" accept="image/*" class="form-control" @change="onSelectFoto"/>
              </div>
              <div class="col-12 mt-2" v-if="previewFoto">
                <img :src="previewFoto" alt="preview" class="rounded" width="100" height="100"/>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-secondary" @click="closeModal">Cancelar</button>
            <button class="btn btn-primary" @click="save">Guardar</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getUsuarios, updateUsuario, deleteUsuario, uploadFoto } from '../services/api';
import { BACKEND } from '../services/backend';

export default {
  name: 'Gestion',
  props: ['usuarios'],
  data() {
    return {
      filterTipo: '',
      list: [],
      showModal: false,
      form: {},
      selectedId: null,
      fotoFile: null,
      previewFoto: '',
      placeholder: 'https://cdn-icons-png.flaticon.com/512/847/847969.png',
      localMsg: '',
      localMsgType: 'error',
      pendingDeleteUser: null
    };
  },
  computed: {
    filteredUsers() {
      const propUsuarios = Array.isArray(this.usuarios)
        ? this.usuarios
        : (Array.isArray(this.usuarios?.data) ? this.usuarios.data : []);
      const base = this.list.length ? this.list : propUsuarios;
      const arr = base.slice();
      arr.sort((a,b)=> (a.apellidoP||'').localeCompare(b.apellidoP||'') || (a.apellidoM||'').localeCompare(b.apellidoM||'') || (a.nombre||'').localeCompare(b.nombre||''));
      return this.filterTipo ? arr.filter(u => u.tipo === this.filterTipo) : arr;
    }
  },
  methods: {
    notifyError(msg) {
      if (this.$root && typeof this.$root.showError === 'function') {
        this.$root.showError(msg);
        return;
      }
      this.localMsgType = 'error';
      this.localMsg = msg;
      setTimeout(() => this.clearLocalMsg(), 4000);
    },
    notifySuccess(msg) {
      if (this.$root && typeof this.$root.showToast === 'function') {
        this.$root.showToast(msg);
        return;
      }
      this.localMsgType = 'success';
      this.localMsg = msg;
      setTimeout(() => this.clearLocalMsg(), 2500);
    },
    clearLocalMsg() {
      this.localMsg = '';
    },
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
    soloTexto(campo) {
      this.form[campo] = this.normalizarTexto(this.form[campo]);
    },
    soloNumeros(campo) {
      this.form[campo] = (this.form[campo] || '').replace(/\D/g, '');
    },
    soloUsuario(campo) {
      this.form[campo] = (this.form[campo] || '').replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü]/g, '');
    },
    getPasswordPolicyResult(password) {
      const pwd = String(password || '');
      const hasLen8 = pwd.length === 8;
      const hasUpper = /[A-Z]/.test(pwd);
      const hasLower = /[a-z]/.test(pwd);
      const hasDigit = /\d/.test(pwd);
      return { ok: hasLen8 && hasUpper && hasLower && hasDigit, hasLen8, hasUpper, hasLower, hasDigit };
    },
    validateEditPayload(payload) {
      if ((payload.numeroControl || '').trim() !== '' && !/^\d{8}$/.test(payload.numeroControl)) {
        return 'El número de control debe tener exactamente 8 dígitos numéricos.';
      }

      if (payload.tipo === 'MONITOR' && !/^\d{8}$/.test((payload.numeroControl || '').trim())) {
        return 'Para tipo MONITOR, el número de control es obligatorio y debe tener 8 dígitos.';
      }

      if ((payload.telefono || '').trim() !== '' && !/^\d{7,15}$/.test(payload.telefono)) {
        return 'El teléfono debe contener solo números (7 a 15 dígitos).';
      }

      if (payload.password) {
        const p = this.getPasswordPolicyResult(payload.password);
        if (!p.ok) {
          return 'Contraseña inválida. Debe tener exactamente 8 caracteres, al menos 1 mayúscula, 1 minúscula y 1 número.';
        }
      }

      return '';
    },
    resolveFotoUrl(foto) {
      if (!foto || typeof foto !== 'string') return '';
      if (foto.startsWith('blob:')) return '';
      if (/^https?:\/\//i.test(foto)) return foto;
      const path = foto.startsWith('/') ? foto.slice(1) : foto;
      return `${BACKEND}/${path}`;
    },
    async loadUsuarios() {
      try {
        const usuarios = await getUsuarios();
        const safeUsuarios = Array.isArray(usuarios)
          ? usuarios
          : (Array.isArray(usuarios?.data) ? usuarios.data : []);
        this.list = safeUsuarios.map(u => ({
          ...u,
          foto: (typeof u?.foto === 'string' && u.foto.startsWith('blob:')) ? '' : u?.foto
        }));
      } catch (e) {
        console.error(e);
        this.notifyError(e.message || 'Error al cargar usuarios');
      }
    },
    openEdit(u) {
      this.selectedId = u.id;
      this.form = { ...u, password: '' };
      this.previewFoto = this.resolveFotoUrl(u.foto) || '';
      this.fotoFile = null;
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.form = {};
      this.selectedId = null;
      this.fotoFile = null;
      this.previewFoto = '';
    },
    onSelectFoto(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      this.fotoFile = file;
      this.previewFoto = URL.createObjectURL(file);
    },
    async save() {
      try {
        const payload = {
          ...this.form,
          nombre: this.normalizarTexto(this.form.nombre),
          apellidoP: this.normalizarTexto(this.form.apellidoP),
          apellidoM: this.normalizarTexto(this.form.apellidoM),
          usuario: (this.form.usuario || '').replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü]/g, ''),
          numeroControl: (this.form.numeroControl || '').replace(/\D/g, ''),
          telefono: (this.form.telefono || '').replace(/\D/g, ''),
          foto: this.form.foto || ''
        };

        const validationError = this.validateEditPayload(payload);
        if (validationError) {
          this.notifyError(validationError);
          return;
        }

        let fotoPath = this.form.foto || '';
        if (this.fotoFile) {
          const up = await uploadFoto(this.fotoFile);
          fotoPath = up.file; // ruta relativa devuelta por el backend
        }
        payload.foto = fotoPath;
        if (!payload.password) delete payload.password; // no enviar si está vacío
        await updateUsuario(this.selectedId, payload);
        await this.loadUsuarios();
        this.closeModal();
        this.notifySuccess('Usuario actualizado correctamente');
      } catch (e) {
        console.error(e);
        this.notifyError(e.message || 'Error al guardar');
      }
    },
    requestDelete(u) {
      this.pendingDeleteUser = u || null;
      if (!this.pendingDeleteUser) return;
      new bootstrap.Modal(document.getElementById('confirmDeleteUser')).show();
    },
    async confirmDelete() {
      const u = this.pendingDeleteUser;
      if (!u || !u.id) return;
      try {
        await deleteUsuario(u.id);
        await this.loadUsuarios();
        this.notifySuccess('Usuario eliminado correctamente');
        const modalEl = document.getElementById('confirmDeleteUser');
        const instance = bootstrap.Modal.getInstance(modalEl);
        if (instance) instance.hide();
        this.pendingDeleteUser = null;
      } catch (e) {
        console.error(e);
        this.notifyError(e.message || 'Error al eliminar');
      }
    }
  },
  mounted() {
    this.loadUsuarios();
  }
};
</script>

<style scoped>
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1040; }
.modal.d-block { position: fixed; inset: 0; display:flex; align-items:center; justify-content:center; z-index: 1050; }
</style>
