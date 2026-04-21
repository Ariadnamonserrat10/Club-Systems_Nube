<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { getCarreras, uploadFoto } from "../services/api";
import { BACKEND } from "../services/backend";

const router = useRouter();

// lista de clubs y selección
const clubs = ref([]);
const selectedClubId = ref(null);

const userType = ref("oficina");
const showSuccessModal = ref(false);
const alertError = ref("");
const alertInfo = ref(true);
const registeredUser = ref(null);

const form = ref({
  nombre: "",
  apellidoP: "",
  apellidoM: "",
  numeroControl: "",
  telefono: "",
  carrera: "",
  semestre: "",
  usuario: "",
  password: "",
  confirmPassword: "",
  foto: null,
});

const fotoPreview = ref(null);
const fotoFile = ref(null);

// catálogo de carreras desde backend
const carreras = ref([]);
const carrerasFallback = [
  { id: 1, nombre: 'Ingeniería Civil' },
  { id: 2, nombre: 'Ingeniería Industrial' },
  { id: 3, nombre: 'Ingeniería en Sistemas Computacionales' },
  { id: 4, nombre: 'Ingeniería en Gestión Empresarial' },
  { id: 5, nombre: 'Licenciatura en Administración' },
  { id: 6, nombre: 'Licenciatura en Arquitectura' },
  { id: 7, nombre: 'Ingeniería en Mecatrónica' },
];

// Mostrar/ocultar contraseña y generador seguro
const showPassword = ref(false);
const generatedPassword = ref(null);

const generatePassword = (length = 8) => {
  const upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
  const lower = 'abcdefghijklmnopqrstuvwxyz';
  const digits = '0123456789';
  const special = '!@#$%^&*';
  const all = upper + lower + digits + special;

  let pwd = '';
  const pick = (set) => set[Math.floor(Math.random() * set.length)];
  pwd += pick(upper);
  pwd += pick(lower);
  pwd += pick(digits);
  pwd += pick(special);

  const remaining = Math.max(0, length - pwd.length);
  if (window.crypto && window.crypto.getRandomValues) {
    const array = new Uint32Array(remaining);
    window.crypto.getRandomValues(array);
    for (let i = 0; i < remaining; i++) {
      pwd += all[array[i] % all.length];
    }
  } else {
    for (let i = 0; i < remaining; i++) {
      pwd += all[Math.floor(Math.random() * all.length)];
    }
  }
  return pwd.split('').sort(() => (Math.random() - 0.5)).join('');
};

const fillWithGenerated = () => {
  const pwd = generatePassword(8);
  form.value.password = pwd;
  form.value.confirmPassword = pwd;
  generatedPassword.value = pwd;
  showPassword.value = true;
  passwordChecked.value = true;
  passwordCheckOk.value = true;
  passwordCheckMsg.value = 'Contraseña generada cumple los requisitos.';
};

const selectUserType = (type) => {
  userType.value = type;
  alertInfo.value = type === "oficina";
  alertError.value = "";
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    fotoFile.value = file;
    if (fotoPreview.value && fotoPreview.value.startsWith("blob:")) {
      URL.revokeObjectURL(fotoPreview.value);
    }
    fotoPreview.value = URL.createObjectURL(file);
  }
};

const resolveFotoUrl = (foto) => {
  if (!foto || typeof foto !== "string") return "";
  if (foto.startsWith("blob:")) return "";
  if (/^https?:\/\//i.test(foto)) return foto;
  const path = foto.startsWith("/") ? foto.slice(1) : foto;
  return `${BACKEND}/${path}`;
};

// validación de complejidad de contraseña ingresada por usuario
const passwordChecked = ref(false);
const passwordCheckOk = ref(false);
const passwordCheckMsg = ref('');

const meetsPolicy = (pwd) => {
  const hasLen8 = pwd.length === 8;
  const hasUpper = /[A-Z]/.test(pwd);
  const hasLower = /[a-z]/.test(pwd);
  const hasDigit = /\d/.test(pwd);
  const hasSpecial = /[^A-Za-z0-9]/.test(pwd);
  return { ok: hasLen8 && hasUpper && hasLower && hasDigit && hasSpecial, hasLen8, hasUpper, hasLower, hasDigit, hasSpecial };
};

const checkPassword = () => {
  const res = meetsPolicy(form.value.password || '');
  passwordChecked.value = true;
  passwordCheckOk.value = res.ok;
  if (res.ok) {
    passwordCheckMsg.value = 'La contraseña cumple con los requisitos.';
  } else {
    const parts = [];
    if (!res.hasLen8) parts.push('exactamente 8 caracteres');
    if (!res.hasUpper) parts.push('al menos 1 mayúscula');
    if (!res.hasLower) parts.push('al menos 1 minúscula');
    if (!res.hasDigit) parts.push('al menos 1 número');
    if (!res.hasSpecial) parts.push('al menos 1 carácter especial');
    passwordCheckMsg.value = 'Falta: ' + parts.join(', ');
  }
};

const onPasswordInput = () => {
  generatedPassword.value = null;
  passwordChecked.value = false;
  passwordCheckOk.value = false;
  passwordCheckMsg.value = '';
};

const normalizarTexto = (valor) => {
  const limpio = String(valor || '')
    .replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]/g, '')
    .replace(/\s+/g, ' ')
    .trim();
  return limpio
    .split(' ')
    .map(p => p ? (p.charAt(0).toUpperCase() + p.slice(1).toLowerCase()) : '')
    .join(' ')
    .trim();
};

const soloNumeros = (valor) => String(valor || '').replace(/\D/g, '');
const soloUsuarioAlnum8 = (valor) => String(valor || '').replace(/[^A-Za-z0-9]/g, '').slice(0, 8);
const usuarioPolicy = (valor) => {
  const v = String(valor || '');
  return {
    onlyAlnum: /^[A-Za-z0-9]*$/.test(v),
    len8: v.length === 8,
    hasLetterAndDigit: /[A-Za-z]/.test(v) && /\d/.test(v)
  };
};

// cargar lista de clubs (si existe endpoint)
const obtenerClubs = async () => {
  try {
    const res = await axios.get(`${BACKEND}/Clubs.php`);
    console.log("RESPUESTA:", res.data);
    console.log("CLUBS ARRAY:", res.data.data);

    clubs.value = res.data.data;
  } catch (err) {
    console.warn("No se pudieron cargar clubs (Clubs.php):", err.message);
  }
};

onMounted(async () => {
  obtenerClubs();

  // cargar carreras desde backend
  try {
    const list = await getCarreras();
    if (Array.isArray(list) && list.length > 0) {
      carreras.value = list;
      return;
    }
  } catch (err) {
    console.warn("No se pudieron cargar carreras (carreras.php):", err.message);
  }

  // Fallback: algunos despliegues exponen PHP bajo /Backend
  try {
    const alt = await axios.get(`${window.location.origin}/Backend/carreras.php`);
    const altData = Array.isArray(alt?.data)
      ? alt.data
      : (Array.isArray(alt?.data?.data) ? alt.data.data : []);
    if (altData.length > 0) {
      carreras.value = altData;
      return;
    }
  } catch (err) {
    console.warn("Fallback de carreras falló (/Backend/carreras.php):", err.message);
  }

  if (carreras.value.length === 0) {
    carreras.value = carrerasFallback;
    alertError.value = "No se pudo cargar el catálogo desde el servidor. Se muestran carreras base de respaldo.";
  }
});

// 🔹 Conexión con PHP y MySQL (corregida y completa)
const handleRegister = async () => {
  alertError.value = "";

  form.value.nombre = normalizarTexto(form.value.nombre);
  form.value.apellidoP = normalizarTexto(form.value.apellidoP);
  form.value.apellidoM = normalizarTexto(form.value.apellidoM);
  form.value.numeroControl = soloNumeros(form.value.numeroControl);
  form.value.telefono = soloNumeros(form.value.telefono);
  form.value.usuario = soloUsuarioAlnum8(form.value.usuario);

  const up = usuarioPolicy(form.value.usuario);
  if (!(up.onlyAlnum && up.len8 && up.hasLetterAndDigit)) {
    alertError.value = "El usuario debe ser alfanumérico, combinar letras y números, y tener exactamente 8 caracteres";
    return;
  }

  if (form.value.password !== form.value.confirmPassword) {
    alertError.value = "Las contraseñas no coinciden";
    return;
  }

  const passwordPolicy = meetsPolicy(form.value.password || '');
  if (!passwordPolicy.ok) {
    alertError.value = "La contraseña debe tener exactamente 8 caracteres, al menos 1 mayúscula, 1 minúscula, 1 número y 1 carácter especial";
    return;
  }

  if (userType.value === "monitor") {
    if (
      !form.value.numeroControl ||
      !form.value.carrera ||
      !form.value.semestre ||
      !form.value.telefono
    ) {
      alertError.value = "Completa todos los campos requeridos para Monitor";
      return;
    }
  }

  try {
    let fotoPath = null;
    if (fotoFile.value) {
      const up = await uploadFoto(fotoFile.value);
      fotoPath = up?.file || null;
    }

    // enviar club_asignado si se seleccionó (mantener compatibilidad con backend)
    const payload = {
      ...form.value,
      foto: fotoPath,
      tipo: userType.value === "oficina" ? "OFICINA" : "MONITOR",
      // el backend espera 'tipo' (OFICINA | MONITOR)
      club_asignado: selectedClubId.value ? Number(selectedClubId.value) : null,
    };

    console.log("Enviando payload:", payload);
    const response = await axios.post(`${BACKEND}/Registrar.php`, payload);
    console.log("Respuesta Registrar.php:", response.status, response.data);

    if (response.data.status === "success") {
      registeredUser.value = {
        nombre: `${form.value.nombre} ${form.value.apellidoP} ${form.value.apellidoM}`,
        tipo: userType.value.toUpperCase(),
        usuario: form.value.usuario,
        numeroControl: form.value.numeroControl || "N/A",
        carrera: form.value.carrera || "N/A",
        telefono: form.value.telefono || "N/A",
        foto: fotoPath,
      };
      showSuccessModal.value = true;
    } else {
      const details = Array.isArray(response.data?.details)
        ? response.data.details.filter(Boolean).join('. ')
        : '';
      alertError.value =
        details || response.data.message || "Error al registrar el usuario.";
    }
  } catch (error) {
    console.error("Axios error completo:", error);
    if (error.response) {
      console.error("Response status:", error.response.status);
      console.error("Response data:", error.response.data);
      const details = Array.isArray(error.response.data?.details)
        ? error.response.data.details.filter(Boolean).join('. ')
        : '';
      alertError.value =
        details ||
        error.response.data?.message ||
        error.response.data?.error ||
        JSON.stringify(error.response.data) ||
        `Error del servidor: ${error.response.status}`;
    } else if (error.request) {
      console.error("No hubo respuesta. Request:", error.request);
      alertError.value =
        "No hubo respuesta del servidor (posible problema de red o URL incorrecta).";
    } else {
      console.error("Error al preparar la petición:", error.message);
      alertError.value = `Error: ${error.message}`;
    }
  }
};

const resetForm = () => {
  form.value = {
    nombre: "",
    apellidoP: "",
    apellidoM: "",
    numeroControl: "",
    telefono: "",
    carrera: "",
    semestre: "",
    usuario: "",
    password: "",
    confirmPassword: "",
    foto: null,
  };
  fotoFile.value = null;
  if (fotoPreview.value && fotoPreview.value.startsWith("blob:")) {
    URL.revokeObjectURL(fotoPreview.value);
  }
  fotoPreview.value = null;
  userType.value = "oficina";
  alertError.value = "";
  alertInfo.value = true;
  generatedPassword.value = null;
  showPassword.value = false;
};

const closeModalAndReset = () => {
  showSuccessModal.value = false;
  resetForm();
};

const goToLogin = () => {
  showSuccessModal.value = false;
  router.push("/");
};
</script>

<template>
  <div class="registro-container">
    <div
      class="card shadow border-0 rounded-4 bg-light p-3 w-100"
      style="max-width: 700px"
    >
      <div class="text-center text-white bg-primary py-3 rounded-3 mb-2">
        <i class="bi bi-person-plus-fill fs-1"></i>
        <h3 class="mt-2 mb-0">Registro de Usuario</h3>
      </div>

      <!-- Alertas -->
      <div
        v-if="alertInfo"
        class="alert alert-info alert-dismissible fade show py-2 mb-2"
      >
        <i class="bi bi-info-circle-fill me-2"></i>
        Los usuarios de OFICINA no requieren número de control, carrera ni
        semestre.
        <button
          type="button"
          class="btn-close"
          @click="alertInfo = false"
        ></button>
      </div>
      <div
        v-if="alertError"
        class="alert alert-danger alert-dismissible fade show py-2 mb-2"
      >
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        {{ alertError }}
        <button
          type="button"
          class="btn-close"
          @click="alertError = ''"
        ></button>
      </div>

      <form @submit.prevent="handleRegister" class="small">
        <!-- Tipo de Usuario -->
        <div class="mb-3 text-center">
          <label class="form-label fw-semibold">Tipo de Usuario</label>
          <div class="d-flex justify-content-center gap-2">
            <button
              type="button"
              class="btn btn-sm"
              :class="
                userType === 'oficina' ? 'btn-primary' : 'btn-outline-primary'
              "
              @click="selectUserType('oficina')"
            >
              <i class="bi bi-building me-1"></i> Oficina
            </button>
            <button
              type="button"
              class="btn btn-sm"
              :class="
                userType === 'monitor' ? 'btn-primary' : 'btn-outline-primary'
              "
              @click="selectUserType('monitor')"
            >
              <i class="bi bi-person-badge me-1"></i> Monitor
            </button>
          </div>
        </div>

        <!-- Imagen -->
        <div class="text-center mb-3">
          <img
            v-if="fotoPreview"
            :src="fotoPreview"
            alt="Foto de perfil"
            class="rounded-circle shadow-sm mb-2 border"
            width="100"
            height="100"
          />
          <div v-else class="placeholder-img mx-auto mb-2"></div>
          <input
            type="file"
            class="form-control w-auto mx-auto"
            accept="image/*"
            @change="handleImageUpload"
          />
        </div>

        <!-- Datos personales -->
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <input
              v-model="form.nombre"
              class="form-control"
              placeholder="Nombre"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.apellidoP"
              class="form-control"
              placeholder="Apellido paterno"
              required
            />
          </div>
          <div class="col-md-4">
            <input
              v-model="form.apellidoM"
              class="form-control"
              placeholder="Apellido materno"
              required
            />
          </div>
        </div>

        <!-- Datos académicos -->
        <div v-if="userType === 'monitor'" class="row g-2 mb-2">
          <div class="col-md-3">
            <input
              v-model="form.numeroControl"
              class="form-control"
              placeholder="No. Control (8 dígitos)"
              pattern="[0-9]{8}"
              required
            />
          </div>
          <div class="col-md-3">
            <input
              v-model="form.telefono"
              class="form-control"
              placeholder="Teléfono (solo números)"
              pattern="[0-9]{10}"
              required
            />
          </div>
          <div class="col-md-3">
            <select v-model.number="form.carrera" class="form-select" required>
              <option value="">Carrera</option>
              <option v-for="c in carreras" :key="c.id" :value="c.id">{{ c.nombre }}</option>
            </select>
          </div>
          <div class="col-md-3">
            <select v-model.number="form.semestre" class="form-select" required>
              <option value="">Semestre</option>
              <option v-for="n in 7" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
        </div>

        <!-- Selector de club: visible solo para tipo monitor -->
        <div v-if="userType === 'monitor'" class="mb-3">
          <label for="clubSelect" class="form-label">Club asignado (opcional)</label>
          <select id="clubSelect" v-model="selectedClubId" class="form-select">
            <option :value="null">-- Ninguno --</option>
            <option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.nombre }}</option>
          </select>
        </div>

        <!-- Datos de acceso -->
        <div class="row g-2 mb-2">
          <div class="col-md-4">
            <input
              v-model="form.usuario"
              @input="form.usuario = soloUsuarioAlnum8(form.usuario)"
              class="form-control"
              placeholder="Usuario"
              maxlength="8"
              required
            />
          </div>
          <div class="col-md-4">
            <div class="input-group">
              <input
                v-model="form.password"
                @input="onPasswordInput"
                :type="showPassword ? 'text' : 'password'"
                class="form-control"
                placeholder="Contraseña (8, con especial)"
                minlength="8"
                maxlength="8"
                required
              />
              <button
                class="btn btn-outline-secondary"
                type="button"
                @click="showPassword = !showPassword"
                :title="showPassword ? 'Ocultar' : 'Mostrar'"
              >
                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
              </button>
            </div>
            <small class="text-muted">Debe incluir mayúscula, minúscula, número y carácter especial.</small>
          </div>
          <div class="col-md-4">
            <div class="d-grid gap-2">
              <input
                v-model="form.confirmPassword"
                :type="showPassword ? 'text' : 'password'"
                class="form-control"
                placeholder="Confirmar contraseña (8 caracteres)"
                minlength="8"
                maxlength="8"
                required
              />
              <div class="d-flex gap-2">
                <button
                  class="btn btn-outline-success flex-fill"
                  type="button"
                  @click="fillWithGenerated"
                  title="Generar contraseña segura (8)"
                >
                  Generar
                </button>
                <button
                  class="btn btn-outline-primary flex-fill"
                  type="button"
                  @click="checkPassword"
                  title="Comprobar que cumple requisitos"
                >
                  Comprobar
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-if="passwordChecked" class="mt-1">
          <small :class="passwordCheckOk ? 'text-success' : 'text-danger'">
            <i :class="passwordCheckOk ? 'bi bi-check-circle' : 'bi bi-x-circle'"></i>
            {{ passwordCheckMsg }}
          </small>
        </div>

        <!-- Botones -->
        <div class="d-flex justify-content-between mt-2">
          <button
            type="button"
            class="btn btn-outline-secondary btn-sm px-3"
            @click="resetForm"
          >
            <i class="bi bi-arrow-clockwise me-1"></i> Limpiar
          </button>
          <button type="submit" class="btn btn-success btn-sm px-4">
            <i class="bi bi-check-circle me-1"></i> Registrar
          </button>
        </div>

        <div class="text-center mt-2">
          <a
            href="#"
            class="text-decoration-none fw-semibold small"
            @click.prevent="router.push('/')"
          >
            <i class="bi bi-arrow-left me-1"></i> Volver al login
          </a>
        </div>
      </form>
    </div>

    <!-- Modal de Éxito -->
    <div v-if="showSuccessModal" class="modal-backdrop fade show"></div>
    <div
      v-if="showSuccessModal"
      class="modal d-block"
      tabindex="-1"
      @click.self="closeModalAndReset"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="bi bi-check-circle-fill me-2"></i>Registro Exitoso
            </h5>
            <button
              type="button"
              class="btn-close btn-close-white"
              @click="closeModalAndReset"
            ></button>
          </div>
          <div class="modal-body text-center">
            <div v-if="registeredUser.foto" class="mb-3">
              <img
                :src="resolveFotoUrl(registeredUser.foto)"
                alt="Foto de usuario"
                class="rounded-circle border"
                width="100"
                height="100"
              />
            </div>
            <h5 class="fw-bold">{{ registeredUser.nombre }}</h5>
            <p class="mb-0">
              <span
                class="badge"
                :class="
                  registeredUser.tipo === 'OFICINA' ? 'bg-primary' : 'bg-info'
                "
              >
                {{ registeredUser.tipo }}
              </span>
            </p>
            <p class="mt-2">Usuario registrado correctamente.</p>
            <div v-if="generatedPassword" class="alert alert-warning small text-start mt-2">
              <div class="d-flex justify-content-between align-items-center">
                <span><strong>Contraseña generada:</strong> {{ generatedPassword }}</span>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-secondary"
                  @click="navigator.clipboard && navigator.clipboard.writeText(generatedPassword)"
                >
                  Copiar
                </button>
              </div>
              <div class="mt-1">Guárdala en un lugar seguro.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-outline-secondary"
              @click="closeModalAndReset"
            >
              Registrar Otro
            </button>
            <button type="button" class="btn btn-primary" @click="goToLogin">
              Ir al Login
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
html,
body {
  height: 100%;
  margin: 0;
  padding: 0;
  overflow-y: auto;
}

.registro-container {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 0.5rem;
  background: linear-gradient(135deg, #2d3561, #5865a8);
}

.card {
  width: 100%;
  margin-bottom: 1rem;
  padding: 1rem;
}

.placeholder-img {
  width: 100px;
  height: 100px;
  background-color: #e9ecef;
  border-radius: 50%;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1040;
}

.modal {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
}
</style>
