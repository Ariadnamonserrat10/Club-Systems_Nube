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
                <input v-model="form.usuario" class="form-control" maxlength="8" @input="soloUsuario('usuario')"/>
                <small class="d-block" :class="usuarioPolicy.onlyAlnum ? 'text-success' : 'text-danger'">Solo letras y números.</small>
                <small class="d-block" :class="usuarioPolicy.len8 ? 'text-success' : 'text-danger'">Exactamente 8 caracteres.</small>
                <small class="d-block" :class="usuarioPolicy.hasLetterAndDigit ? 'text-success' : 'text-danger'">Debe combinar letras y números.</small>
              </div>
              <div class="col-md-4">
                <label class="form-label">Tipo</label>
                <select v-model="form.tipo" class="form-select" @change="onTipoChange">
                  <option value="OFICINA">OFICINA</option>
                  <option value="MONITOR">MONITOR</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Club asignado (id)</label>
                <input v-model.number="form.club_asignado" type="number" class="form-control"/>
              </div>
              <div class="col-md-4" v-if="form.tipo === 'MONITOR'">
                <label class="form-label">Número de control</label>
                <input
                  v-model="form.numeroControl"
                  class="form-control"
                  maxlength="8"
                  inputmode="numeric"
                  placeholder="8 dígitos"
                  @input="soloNumeros('numeroControl')"
                />
                <small class="text-muted">Obligatorio para MONITOR: exactamente 8 dígitos numéricos.</small>
              </div>
            </div>
            <div class="row g-2 mt-2">
              <!-- Sección de contraseña con toggle -->
              <div class="col-12">
                <label class="form-label">Contraseña</label>
                <!-- Vista bloqueada: indica que hay contraseña establecida -->
                <div v-if="!cambiarPassword" class="d-flex gap-2 align-items-center flex-wrap">
                  <input
                    type="password"
                    class="form-control"
                    value="placeholder"
                    disabled
                    autocomplete="off"
                    style="max-width:160px"
                  />
                  <small class="text-muted fst-italic">La contraseña está cifrada y no puede visualizarse.</small>
                  <button class="btn btn-outline-warning ms-auto" type="button" @click="iniciarCambioPassword">
                    Cambiar contraseña
                  </button>
                </div>
                <!-- Vista de edición: campos para nueva contraseña -->
                <div v-else class="row g-2">
                  <div class="col-md-6">
                    <label class="form-label">Nueva contraseña</label>
                    <div class="d-grid gap-2">
                      <input
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        minlength="8"
                        maxlength="8"
                        autocomplete="new-password"
                        @beforeinput="onPasswordBeforeInput('password', $event)"
                        @keydown="onPasswordKeydown('password', $event)"
                        @paste.prevent="onPasswordPaste('password', $event)"
                        @input="onPasswordInput($event)"
                      />
                      <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary flex-fill" type="button" @click="showPassword = !showPassword">
                          {{ showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña' }}
                        </button>
                        <button class="btn btn-outline-success flex-fill" type="button" @click="fillWithGeneratedPassword">
                          Generar
                        </button>
                        <button class="btn btn-outline-danger flex-fill" type="button" @click="cancelarCambioPassword">
                          Cancelar
                        </button>
                      </div>
                    </div>
                    <small class="d-block" :class="passwordPolicy.hasLen8 ? 'text-success' : 'text-danger'">Exactamente 8 caracteres.</small>
                    <small class="d-block" :class="passwordPolicy.hasUpper ? 'text-success' : 'text-danger'">Al menos 1 mayúscula.</small>
                    <small class="d-block" :class="passwordPolicy.hasLower ? 'text-success' : 'text-danger'">Al menos 1 minúscula.</small>
                    <small class="d-block" :class="passwordPolicy.hasDigit ? 'text-success' : 'text-danger'">Al menos 1 número.</small>
                    <small class="d-block" :class="passwordPolicy.hasSpecial ? 'text-success' : 'text-danger'">Al menos 1 carácter especial.</small>
                    <small v-if="passwordCheckMsg" class="d-block mt-1" :class="passwordCheckOk ? 'text-success' : 'text-danger'">{{ passwordCheckMsg }}</small>
                  </div>
                  <div class="col-md-6" v-if="form.password">
                    <label class="form-label">Confirmar nueva contraseña</label>
                    <div class="d-grid gap-2">
                      <input
                        v-model="form.confirmPassword"
                        :type="showPassword ? 'text' : 'password'"
                        class="form-control"
                        minlength="8"
                        maxlength="8"
                        autocomplete="new-password"
                        @beforeinput="onPasswordBeforeInput('confirmPassword', $event)"
                        @keydown="onPasswordKeydown('confirmPassword', $event)"
                        @paste.prevent="onPasswordPaste('confirmPassword', $event)"
                        @input="onConfirmPasswordInput($event)"
                      />
                      <button class="btn btn-outline-primary w-100" type="button" @click="checkPasswordMatch">
                        Comprobar
                      </button>
                    </div>
                    <small v-if="passwordsChecked" class="d-block mt-1" :class="passwordsMatch ? 'text-success' : 'text-danger'">
                      {{ passwordsMatch ? 'Las contraseñas coinciden.' : 'Escribe y comprueba que coincidan.' }}
                    </small>
                  </div>
                </div>
              </div>
              <div class="col-md-6" v-if="form.tipo === 'MONITOR'">
                <label class="form-label">Teléfono</label>
                <input v-model="form.telefono" class="form-control" @input="soloNumeros('telefono')"/>
                <small class="text-muted">Obligatorio para MONITOR: solo números (7 a 15 dígitos).</small>
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
      showPassword: false,
      showCurrentPassword: false,
      cambiarPassword: false,
      passwordCheckOk: false,
      passwordCheckMsg: '',
      passwordsMatch: false,
      passwordsChecked: false,
      placeholder: 'https://cdn-icons-png.flaticon.com/512/847/847969.png',
      localMsg: '',
      localMsgType: 'error',
      pendingDeleteUser: null
    };
  },
  computed: {
    usuarioPolicy() {
      return this.getUsuarioPolicyResult(this.form?.usuario || '');
    },
    passwordPolicy() {
      return this.getPasswordPolicyResult(this.form?.password || '');
    },
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
      this.form[campo] = (this.form[campo] || '').replace(/[^A-Za-z0-9]/g, '').slice(0, 8);
    },
    onTipoChange() {
      if (this.form.tipo === 'OFICINA') {
        this.form.numeroControl = '';
        this.form.telefono = '';
      }
    },
    getUsuarioPolicyResult(usuario) {
      const val = String(usuario || '');
      const onlyAlnum = /^[A-Za-z0-9]*$/.test(val);
      const len8 = val.length === 8;
      const hasLetterAndDigit = /[A-Za-z]/.test(val) && /\d/.test(val);
      return { ok: onlyAlnum && len8 && hasLetterAndDigit, onlyAlnum, len8, hasLetterAndDigit };
    },
    getPasswordPolicyResult(password) {
      const pwd = String(password || '');
      const hasLen8 = pwd.length === 8;
      const hasUpper = /[A-Z]/.test(pwd);
      const hasLower = /[a-z]/.test(pwd);
      const hasDigit = /\d/.test(pwd);
      const hasSpecial = /[^A-Za-z0-9]/.test(pwd);
      return { ok: hasLen8 && hasUpper && hasLower && hasDigit && hasSpecial, hasLen8, hasUpper, hasLower, hasDigit, hasSpecial };
    },
    generatePassword(length = 8) {
      const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      const lower = 'abcdefghijklmnopqrstuvwxyz';
      const digits = '0123456789';
      const special = '!@#$%^&*';
      const all = upper + lower + digits + special;

      const pick = (set) => set[Math.floor(Math.random() * set.length)];
      let pwd = pick(upper) + pick(lower) + pick(digits) + pick(special);
      const remaining = Math.max(0, length - pwd.length);

      if (window.crypto && window.crypto.getRandomValues) {
        const arr = new Uint32Array(remaining);
        window.crypto.getRandomValues(arr);
        for (let i = 0; i < remaining; i++) {
          pwd += all[arr[i] % all.length];
        }
      } else {
        for (let i = 0; i < remaining; i++) {
          pwd += pick(all);
        }
      }

      return pwd.split('').sort(() => Math.random() - 0.5).join('');
    },
    fillWithGeneratedPassword() {
      const generated = this.generatePassword(8);
      this.form.password = generated;
      this.form.confirmPassword = generated;
      this.showPassword = false;
      this.passwordCheckOk = true;
      this.passwordCheckMsg = 'Contraseña generada y válida.';
      this.passwordsMatch = true;
      this.passwordsChecked = true;
    },
    onPasswordInput(event) {
      const trimmed = String(this.form.password || '').slice(0, 8);
      this.form.password = trimmed;
      if (event?.target && event.target.value !== trimmed) {
        event.target.value = trimmed;
      }
      this.passwordCheckMsg = '';
      this.passwordCheckOk = false;
      this.passwordsMatch = false;
      this.passwordsChecked = false;
    },
    onConfirmPasswordInput(event) {
      const trimmed = String(this.form.confirmPassword || '').slice(0, 8);
      this.form.confirmPassword = trimmed;
      if (event?.target && event.target.value !== trimmed) {
        event.target.value = trimmed;
      }
      this.passwordsMatch = false;
      this.passwordsChecked = false;
    },
    onPasswordBeforeInput(field, event) {
      const inputType = String(event?.inputType || '');
      if (!inputType.startsWith('insert')) return;

      const target = event.target;
      const current = String(this.form[field] || '');
      const selectionStart = typeof target.selectionStart === 'number' ? target.selectionStart : current.length;
      const selectionEnd = typeof target.selectionEnd === 'number' ? target.selectionEnd : current.length;
      const selectedLen = Math.max(0, selectionEnd - selectionStart);

      if (current.length - selectedLen >= 8) {
        event.preventDefault();
      }
    },
    onPasswordKeydown(field, event) {
      const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
      if (event.ctrlKey || event.metaKey || allowedKeys.includes(event.key)) {
        return;
      }

      const target = event.target;
      const current = String(this.form[field] || '');
      const selectionStart = typeof target.selectionStart === 'number' ? target.selectionStart : current.length;
      const selectionEnd = typeof target.selectionEnd === 'number' ? target.selectionEnd : current.length;
      const selectedLen = Math.max(0, selectionEnd - selectionStart);

      if (current.length - selectedLen >= 8) {
        event.preventDefault();
      }
    },
    onPasswordPaste(field, event) {
      const clip = event.clipboardData?.getData('text') || '';
      const target = event.target;
      const current = String(this.form[field] || '');
      const selectionStart = typeof target.selectionStart === 'number' ? target.selectionStart : current.length;
      const selectionEnd = typeof target.selectionEnd === 'number' ? target.selectionEnd : current.length;

      const before = current.slice(0, selectionStart);
      const after = current.slice(selectionEnd);
      const maxPaste = Math.max(0, 8 - (before.length + after.length));
      const clipped = clip.slice(0, maxPaste);

      this.form[field] = `${before}${clipped}${after}`.slice(0, 8);
      this.passwordsMatch = false;
      this.passwordsChecked = false;
      if (field === 'password') {
        this.passwordCheckMsg = '';
        this.passwordCheckOk = false;
      }
    },
    iniciarCambioPassword() {
      this.cambiarPassword = true;
      this.form.password = '';
      this.form.confirmPassword = '';
      this.showPassword = false;
      this.passwordCheckMsg = '';
      this.passwordCheckOk = false;
      this.passwordsMatch = false;
      this.passwordsChecked = false;
    },
    cancelarCambioPassword() {
      this.cambiarPassword = false;
      this.form.password = '';
      this.form.confirmPassword = '';
      this.showPassword = false;
      this.passwordCheckMsg = '';
      this.passwordCheckOk = false;
      this.passwordsMatch = false;
      this.passwordsChecked = false;
    },
    checkPasswordMatch() {
      this.passwordsChecked = true;
      const policy = this.getPasswordPolicyResult(this.form.password || '');
      if (!policy.ok) {
        this.passwordCheckOk = false;
        this.passwordCheckMsg = 'La contraseña no cumple la política.';
        this.passwordsMatch = false;
        return;
      }
      this.form.confirmPassword = String(this.form.password || '').slice(0, 8);
      this.passwordCheckOk = true;
      this.passwordCheckMsg = 'La contraseña cumple la política.';
      this.passwordsMatch = (this.form.password || '') === (this.form.confirmPassword || '');
    },
    validateEditPayload(payload) {
      const userPolicy = this.getUsuarioPolicyResult(payload.usuario);
      if (!userPolicy.ok) {
        return 'Usuario inválido. Debe ser alfanumérico, combinar letras y números, y tener exactamente 8 caracteres.';
      }

      if ((payload.numeroControl || '').trim() !== '' && !/^\d{8}$/.test(payload.numeroControl)) {
        return 'El número de control debe tener exactamente 8 dígitos numéricos.';
      }

      if (payload.tipo === 'MONITOR') {
        if (!/^\d{8}$/.test((payload.numeroControl || '').trim())) {
          return 'Para tipo MONITOR, el número de control es obligatorio y debe tener 8 dígitos.';
        }
        if (!/^\d{7,15}$/.test((payload.telefono || '').trim())) {
          return 'Para tipo MONITOR, el teléfono es obligatorio y debe contener solo números (7 a 15 dígitos).';
        }
      }

      if (payload.password) {
        const p = this.getPasswordPolicyResult(payload.password);
        if (!p.ok) {
          return 'Contraseña inválida. Debe tener exactamente 8 caracteres, al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial.';
        }
        if (payload.password !== (this.form.confirmPassword || '')) {
          return 'La confirmación de contraseña no coincide.';
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
      this.form = { ...u, password: '', confirmPassword: '' };
      this.previewFoto = this.resolveFotoUrl(u.foto) || '';
      this.fotoFile = null;
      this.showPassword = false;
      this.showCurrentPassword = false;
      this.cambiarPassword = false;
      this.passwordCheckOk = false;
      this.passwordCheckMsg = '';
      this.passwordsMatch = false;
      this.passwordsChecked = false;
      this.showModal = true;
    },
    closeModal() {
      this.showModal = false;
      this.form = {};
      this.selectedId = null;
      this.fotoFile = null;
      this.previewFoto = '';
      this.showPassword = false;
      this.showCurrentPassword = false;
      this.cambiarPassword = false;
      this.passwordCheckOk = false;
      this.passwordCheckMsg = '';
      this.passwordsMatch = false;
      this.passwordsChecked = false;
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
          usuario: (this.form.usuario || '').replace(/[^A-Za-z0-9]/g, '').slice(0, 8),
          password: (this.form.password || '').slice(0, 8),
          confirmPassword: (this.form.confirmPassword || '').slice(0, 8),
          numeroControl: (this.form.numeroControl || '').replace(/\D/g, ''),
          telefono: (this.form.telefono || '').replace(/\D/g, ''),
          foto: this.form.foto || ''
        };

        if (payload.tipo === 'OFICINA') {
          payload.numeroControl = '';
          payload.telefono = '';
        }

        if (!this.cambiarPassword) {
          payload.password = '';
          payload.confirmPassword = '';
        }

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
