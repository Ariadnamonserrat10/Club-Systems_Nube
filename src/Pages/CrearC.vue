<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";
import { getCarreras, uploadFoto } from "../services/api";
import { BACKEND } from "../services/backend";

const router = useRouter();

const clubs = ref([]);
const selectedClubId = ref(null);
const userType = ref("oficina");
const showSuccessModal = ref(false);
const alertError = ref("");
const alertInfo = ref(true);
const registeredUser = ref(null);
const isLoading = ref(false);
const isPageVisible = ref(true);
const isExiting = ref(false);

const currentStep = ref(1);
const totalSteps = computed(() => userType.value === "oficina" ? 3 : 4);

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
const showPassword = ref(false);
const showConfirmPassword = ref(false);
const generatedPassword = ref(null);

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

const passwordChecked = ref(false);
const passwordCheckOk = ref(false);
const passwordCheckMsg = ref('');

const meetsPolicy = (pwd) => {
  const hasLen8 = pwd.length === 8;
  const hasUpper = /[A-Z]/.test(pwd);
  const hasLower = /[a-z]/.test(pwd);
  const hasDigit = /\d/.test(pwd);
  const hasSpecial = /[^A-Za-z0-9]/.test(pwd);
  return { 
    ok: hasLen8 && hasUpper && hasLower && hasDigit && hasSpecial, 
    hasLen8, hasUpper, hasLower, hasDigit, hasSpecial 
  };
};

const passwordReqs = computed(() => {
  const pwd = form.value.password || '';
  const res = meetsPolicy(pwd);
  return [
    { label: 'Exactamente 8 caracteres', valid: res.hasLen8, key: 'len' },
    { label: 'Al menos 1 mayúscula', valid: res.hasUpper, key: 'upper' },
    { label: 'Al menos 1 minúscula', valid: res.hasLower, key: 'lower' },
    { label: 'Al menos 1 número', valid: res.hasDigit, key: 'digit' },
    { label: 'Al menos 1 carácter especial', valid: res.hasSpecial, key: 'special' },
  ];
});

const checkPassword = () => {
  const res = meetsPolicy(form.value.password || '');
  passwordChecked.value = true;
  passwordCheckOk.value = res.ok;
  if (res.ok) {
    passwordCheckMsg.value = 'La contraseña cumple con todos los requisitos.';
  } else {
    passwordCheckMsg.value = 'La contraseña no cumple con los requisitos de seguridad.';
  }
};

const onPasswordInput = () => {
  generatedPassword.value = null;
  passwordChecked.value = false;
  passwordCheckOk.value = false;
  passwordCheckMsg.value = '';
};

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
  currentStep.value = 1;
};

const handleImageUpload = (event) => {
  const file = event.target.files[0];
  if (file) {
    if (file.size > 2 * 1024 * 1024) {
      alertError.value = "La imagen no debe exceder 2MB";
      return;
    }
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      alertError.value = "Solo se permiten imágenes JPG, PNG o WebP";
      return;
    }
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

const usuarioValid = computed(() => {
  const up = usuarioPolicy(form.value.usuario);
  return up.onlyAlnum && up.len8 && up.hasLetterAndDigit;
});

const passwordsMatch = computed(() => {
  return form.value.password && form.value.password === form.value.confirmPassword;
});

const canProceedToStep2 = computed(() => {
  if (userType.value === 'oficina') {
    return form.value.nombre && form.value.apellidoP && form.value.apellidoM;
  }
  return form.value.nombre && form.value.apellidoP && form.value.apellidoM &&
         form.value.numeroControl && form.value.telefono && form.value.carrera && form.value.semestre;
});

const canProceedToStep3 = computed(() => {
  return usuarioValid.value && form.value.password && passwordsMatch.value && 
         meetsPolicy(form.value.password).ok;
});

const nextStep = () => {
  alertError.value = "";
  
  if (currentStep.value === 1) {
    if (!canProceedToStep2.value) {
      alertError.value = "Por favor completa todos los campos requeridos";
      return;
    }
    currentStep.value = 2;
  } else if (currentStep.value === 2) {
    if (!canProceedToStep3.value) {
      alertError.value = "Por favor completa los campos de acceso correctamente";
      return;
    }
    currentStep.value = 3;
  }
};

const prevStep = () => {
  if (currentStep.value > 1) {
    currentStep.value--;
    alertError.value = "";
  }
};

const obtenerClubs = async () => {
  try {
    const res = await axios.get(`${BACKEND}/Clubs.php`);
    clubs.value = res.data.data;
  } catch (err) {
    console.warn("No se pudieron cargar clubs:", err.message);
  }
};

const copyToClipboard = async (text) => {
  if (!text) return;
  try {
    if (window.navigator && window.navigator.clipboard) {
      await window.navigator.clipboard.writeText(text);
      alert("Contraseña copiada al portapapeles");
    }
  } catch (err) {
    console.error("Error al copiar:", err);
  }
};

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

  isLoading.value = true;

  try {
    let fotoPath = null;
    if (fotoFile.value) {
      const up = await uploadFoto(fotoFile.value);
      fotoPath = up?.file || null;
    }

    const payload = {
      ...form.value,
      foto: fotoPath,
      tipo: userType.value === "oficina" ? "OFICINA" : "MONITOR",
      club_asignado: selectedClubId.value ? Number(selectedClubId.value) : null,
    };

    const response = await axios.post(`${BACKEND}/Registrar.php`, payload);

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
      currentStep.value = 1;
    } else {
      const details = Array.isArray(response.data?.details)
        ? response.data.details.filter(Boolean).join('. ')
        : '';
      alertError.value =
        details || response.data.message || "Error al registrar el usuario.";
    }
  } catch (error) {
    console.error("Error:", error);
    if (error.response) {
      const details = Array.isArray(error.response.data?.details)
        ? error.response.data.details.filter(Boolean).join('. ')
        : '';
      alertError.value =
        details ||
        error.response.data?.message ||
        error.response.data?.error ||
        `Error del servidor: ${error.response.status}`;
    } else {
      alertError.value = "No hubo respuesta del servidor.";
    }
  } finally {
    isLoading.value = false;
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
  showConfirmPassword.value = false;
  passwordChecked.value = false;
  passwordCheckOk.value = false;
  passwordCheckMsg.value = '';
  currentStep.value = 1;
};

const closeModalAndReset = () => {
  showSuccessModal.value = false;
  resetForm();
};

const goToLogin = () => {
  showSuccessModal.value = false;
  isExiting.value = true;
  setTimeout(() => {
    router.push("/");
  }, 300);
};

const navigateToLogin = () => {
  isExiting.value = true;
  setTimeout(() => {
    router.push("/");
  }, 300);
};

onMounted(async () => {
  obtenerClubs();

  try {
    const list = await getCarreras();
    if (Array.isArray(list) && list.length > 0) {
      carreras.value = list;
      return;
    }
  } catch (err) {
    console.warn("No se pudieron cargar carreras:", err.message);
  }

  if (carreras.value.length === 0) {
    carreras.value = carrerasFallback;
  }

  setTimeout(() => {
    isPageVisible.value = true;
  }, 50);
});
</script>

<template>
  <div class="register-page" :class="{ 'page-exiting': isExiting, 'page-visible': isPageVisible }">
    <div class="page-bg">
      <div class="bg-gradient"></div>
      <div class="bg-orbs">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
      </div>
    </div>

    <div class="register-shell">
      <div class="main-workspace">
        
        <div class="workspace-header">
          <div class="header-left">
            <div class="logo-mark">
              <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="16" cy="10" r="5" stroke="currentColor" stroke-width="2"/>
                <path d="M6 28C6 23.0294 10.0294 19 15 19C19.9706 19 24 23.0294 24 28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <circle cx="24" cy="8" r="1.5" fill="currentColor" opacity="0.4"/>
                <circle cx="8" cy="8" r="1.5" fill="currentColor" opacity="0.4"/>
              </svg>
            </div>
            <div class="header-text">
              <h1 class="header-title">Registro de Usuario</h1>
              <p class="header-subtitle">Sistema de Gestión de Clubs Estudiantiles</p>
            </div>
          </div>

          <div class="step-navigation">
            <div class="step-track">
              <div 
                v-for="n in 3" 
                :key="n"
                class="step-dot-wrapper"
              >
                <div 
                  class="step-dot"
                  :class="{ 
                    'step-current': currentStep === n, 
                    'step-done': currentStep > n 
                  }"
                >
                  <span v-if="currentStep <= n" class="dot-number">{{ n }}</span>
                  <svg v-else class="dot-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"/>
                  </svg>
                </div>
                <span class="step-label-mini">{{ ['Datos', 'Acceso', 'Confirmar'][n-1] }}</span>
              </div>
              <div class="connector-lines">
                <div 
                  class="connector"
                  :class="{ 'connector-filled': currentStep > 1 }"
                ></div>
                <div 
                  class="connector"
                  :class="{ 'connector-filled': currentStep > 2 }"
                ></div>
              </div>
            </div>
          </div>
        </div>

        <div class="workspace-divider"></div>

        <div class="workspace-content">
          
          <div class="type-selector-section">
            <div 
              class="type-card"
              :class="{ 'type-active': userType === 'oficina' }"
              @click="selectUserType('oficina')"
            >
              <div class="type-icon-wrap" :class="{ 'icon-active': userType === 'oficina' }">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                  <rect x="3" y="2" width="14" height="16" rx="2"/>
                  <line x1="6" y1="6" x2="7" y2="6"/>
                  <line x1="9" y1="6" x2="10" y2="6"/>
                  <line x1="12" y1="6" x2="13" y2="6"/>
                  <line x1="6" y1="9" x2="7" y2="9"/>
                  <line x1="9" y1="9" x2="10" y2="9"/>
                  <line x1="12" y1="9" x2="13" y2="9"/>
                  <line x1="6" y1="12" x2="7" y2="12"/>
                  <line x1="9" y1="12" x2="10" y2="12"/>
                  <line x1="12" y1="12" x2="13" y2="12"/>
                </svg>
              </div>
              <div class="type-info-wrap">
                <span class="type-name">Oficina</span>
                <span class="type-desc">Administración general</span>
              </div>
              <div class="type-radio" :class="{ 'radio-checked': userType === 'oficina' }">
                <div class="radio-dot"></div>
              </div>
            </div>

            <div 
              class="type-card"
              :class="{ 'type-active': userType === 'monitor' }"
              @click="selectUserType('monitor')"
            >
              <div class="type-icon-wrap" :class="{ 'icon-active': userType === 'monitor' }">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                  <circle cx="10" cy="6" r="3"/>
                  <path d="M3 17C3 13.134 6.13401 10 10 10C13.866 10 17 13.134 17 17" stroke-linecap="round"/>
                </svg>
              </div>
              <div class="type-info-wrap">
                <span class="type-name">Monitor</span>
                <span class="type-desc">Control de clubs y asistencias</span>
              </div>
              <div class="type-radio" :class="{ 'radio-checked': userType === 'monitor' }">
                <div class="radio-dot"></div>
              </div>
            </div>
          </div>

          <transition name="info-expand">
            <div v-if="alertInfo" class="info-banner">
              <div class="banner-icon-wrap">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="10" cy="10" r="8"/>
                  <line x1="10" y1="14" x2="10" y2="10"/>
                  <path d="M10 7H10.01" stroke-linecap="round"/>
                </svg>
              </div>
              <div class="banner-text-wrap">
                <strong>Usuarios de Oficina:</strong> No requieren número de control, carrera ni semestre. Solo datos personales y credenciales de acceso.
              </div>
              <button class="banner-dismiss" @click="alertInfo = false">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="5" x2="15" y2="15"/>
                  <line x1="15" y1="5" x2="5" y2="15"/>
                </svg>
              </button>
            </div>
          </transition>

          <transition name="fade-slow">
            <div v-if="alertError" class="error-banner">
              <div class="banner-icon-wrap error-icon">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="10" cy="10" r="8"/>
                  <line x1="13" y1="7" x2="7" y2="13"/>
                  <line x1="7" y1="7" x2="13" y2="13"/>
                </svg>
              </div>
              <span class="banner-text-wrap">{{ alertError }}</span>
              <button class="banner-dismiss" @click="alertError = ''">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="5" x2="15" y2="15"/>
                  <line x1="15" y1="5" x2="5" y2="15"/>
                </svg>
              </button>
            </div>
          </transition>

          <transition name="step-slide" mode="out-in">
            <div v-if="currentStep === 1" key="step-1" class="form-step-panel">
              
              <div class="step-content-grid">
                <div class="photo-col">
                  <div class="photo-upload-area">
                    <div class="photo-frame">
                      <img
                        v-if="fotoPreview"
                        :src="fotoPreview"
                        alt="Foto"
                        class="profile-photo-img"
                      />
                      <div v-else class="photo-placeholder-icon">
                        <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5">
                          <circle cx="24" cy="18" r="10"/>
                          <path d="M8 42C8 33.1634 15.1634 26 24 26C32.8366 26 40 33.1634 40 42" stroke-linecap="round"/>
                        </svg>
                      </div>
                    </div>
                    <label class="photo-upload-btn">
                      <input type="file" class="hidden-input" accept="image/*" @change="handleImageUpload"/>
                      <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 15H3"/>
                        <path d="M9 3v12"/>
                        <path d="M3 9h12" stroke-linecap="round"/>
                      </svg>
                      <span>{{ fotoPreview ? 'Cambiar foto' : 'Agregar foto' }}</span>
                    </label>
                    <p class="photo-hint-text">JPG, PNG, WebP • Máx. 2MB</p>
                  </div>
                </div>

                <div class="fields-col">
                  <div class="section-header-light">
                    <h3 class="section-title-light">Datos Personales</h3>
                    <span class="section-badge-light">Paso 1 de 3</span>
                  </div>

                  <div class="form-grid-wide">
                    <div class="form-field">
                      <label class="field-label">Nombre(s)</label>
                      <input
                        v-model="form.nombre"
                        class="field-input"
                        type="text"
                        placeholder="Ej: Juan Carlos"
                        autocomplete="given-name"
                      />
                    </div>
                    <div class="form-field">
                      <label class="field-label">Apellido Paterno</label>
                      <input
                        v-model="form.apellidoP"
                        class="field-input"
                        type="text"
                        placeholder="Ej: Pérez"
                        autocomplete="family-name"
                      />
                    </div>
                    <div class="form-field">
                      <label class="field-label">Apellido Materno</label>
                      <input
                        v-model="form.apellidoM"
                        class="field-input"
                        type="text"
                        placeholder="Ej: García"
                        autocomplete="family-name"
                      />
                    </div>
                  </div>

                  <transition name="expand-smooth">
                    <div v-if="userType === 'monitor'" class="academic-section">
                      <div class="section-header-light">
                        <h3 class="section-title-light">Datos Académicos</h3>
                        <span class="section-tag">Solo Monitores</span>
                      </div>

                      <div class="form-grid-wide">
                        <div class="form-field">
                          <label class="field-label">Número de Control</label>
                          <input
                            v-model="form.numeroControl"
                            @input="form.numeroControl = soloNumeros(form.numeroControl)"
                            class="field-input"
                            type="text"
                            placeholder="8 dígitos"
                            maxlength="8"
                          />
                        </div>
                        <div class="form-field">
                          <label class="field-label">Teléfono</label>
                          <input
                            v-model="form.telefono"
                            @input="form.telefono = soloNumeros(form.telefono)"
                            class="field-input"
                            type="tel"
                            placeholder="10 dígitos"
                            maxlength="15"
                          />
                        </div>
                        <div class="form-field">
                          <label class="field-label">Carrera</label>
                          <div class="select-wrapper">
                            <select v-model.number="form.carrera" class="field-select">
                              <option :value="null">Seleccionar carrera</option>
                              <option v-for="c in carreras" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                            </select>
                            <div class="select-arrow">
                              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6L8 10L12 6" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </div>
                          </div>
                        </div>
                        <div class="form-field">
                          <label class="field-label">Semestre</label>
                          <div class="select-wrapper">
                            <select v-model.number="form.semestre" class="field-select">
                              <option :value="null">Seleccionar semestre</option>
                              <option v-for="n in 12" :key="n" :value="n">{{ n }}° Semestre</option>
                            </select>
                            <div class="select-arrow">
                              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6L8 10L12 6" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </div>
                          </div>
                        </div>
                        <div class="form-field full-width">
                          <label class="field-label">Club asignado</label>
                          <div class="select-wrapper">
                            <select v-model="selectedClubId" class="field-select">
                              <option :value="null">-- Ninguno --</option>
                              <option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                            </select>
                            <div class="select-arrow">
                              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 6L8 10L12 6" stroke-linecap="round" stroke-linejoin="round"/>
                              </svg>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>
              </div>
            </div>

            <div v-else-if="currentStep === 2" key="step-2" class="form-step-panel">
              
              <div class="center-content-wrapper">
                <div class="section-header-light centered">
                  <h3 class="section-title-light">Credenciales de Acceso</h3>
                  <span class="section-badge-light">Paso 2 de 3</span>
                </div>

                <div class="credentials-grid">
                  <div class="form-field">
                    <label class="field-label">
                      Usuario
                      <span class="label-hint">(8 caracteres: letras y números)</span>
                    </label>
                    <div class="input-with-icon">
                      <div class="input-icon-left">
                        <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <circle cx="9" cy="5" r="3"/>
                          <path d="M3 16C3 12.6863 5.68629 10 9 10C12.3137 10 15 12.6863 15 16" stroke-linecap="round"/>
                        </svg>
                      </div>
                      <input
                        v-model="form.usuario"
                        @input="form.usuario = soloUsuarioAlnum8(form.usuario)"
                        class="field-input with-left-icon"
                        type="text"
                        placeholder="Ej: ADM00001"
                        maxlength="8"
                      />
                      <div 
                        class="input-status"
                        :class="{ 
                          'status-valid': usuarioValid, 
                          'status-invalid': form.usuario && !usuarioValid 
                        }"
                      >
                        <svg v-if="usuarioValid" viewBox="0 0 18 18" fill="currentColor">
                          <path d="M15.293 4.293L7 12.586L3.707 9.293L2.293 10.707L7 15.414L16.707 5.707L15.293 4.293Z"/>
                        </svg>
                        <svg v-else-if="form.usuario" viewBox="0 0 18 18" fill="currentColor">
                          <path d="M4.293 4.293L9 9L4.293 13.707L5.707 15.293L10.414 10.586L15.121 15.293L16.535 13.879L11.828 9.172L16.535 4.464L15.121 3.05L10.414 7.757L5.707 3.05L4.293 4.293Z"/>
                        </svg>
                      </div>
                    </div>
                  </div>

                  <div class="form-field">
                    <label class="field-label">Contraseña</label>
                    <div class="input-with-icon">
                      <div class="input-icon-left">
                        <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <rect x="3" y="8" width="12" height="7" rx="1.5"/>
                          <path d="M5 8V6C5 3.79086 6.79086 2 9 2C11.2091 2 13 3.79086 13 6V8" stroke-linecap="round"/>
                        </svg>
                      </div>
                      <input
                        v-model="form.password"
                        @input="onPasswordInput"
                        :type="showPassword ? 'text' : 'password'"
                        class="field-input with-left-icon"
                        placeholder="8 caracteres"
                        minlength="8"
                        maxlength="8"
                        autocomplete="new-password"
                      />
                      <button 
                        type="button"
                        class="input-toggle-btn"
                        @click="showPassword = !showPassword"
                      >
                        <svg v-if="!showPassword" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <path d="M1 9C1 9 4 3 9 3C14 3 17 9 17 9C17 9 14 15 9 15C4 15 1 9 1 9Z"/>
                          <circle cx="9" cy="9" r="2.25"/>
                        </svg>
                        <svg v-else viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <path d="M2 2L16 16M2.5 9.5C2.5 9.5 4.5 5.5 9 5.5C10.5 5.5 11.75 6.16667 12.6667 7M15.5 9.5C15.5 9.5 13.5 13.5 9 13.5C8 13.5 7.08333 13.1667 6.33333 12.6667" stroke-linecap="round"/>
                          <path d="M9 11.5C10.3807 11.5 11.5 10.3807 11.5 9C11.5 7.61929 10.3807 6.5 9 6.5" stroke-linecap="round"/>
                        </svg>
                      </button>
                    </div>
                  </div>

                  <div class="form-field">
                    <label class="field-label">Confirmar Contraseña</label>
                    <div class="input-with-icon">
                      <div class="input-icon-left">
                        <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <rect x="3" y="8" width="12" height="7" rx="1.5"/>
                          <path d="M5 8V6C5 3.79086 6.79086 2 9 2C11.2091 2 13 3.79086 13 6V8" stroke-linecap="round"/>
                        </svg>
                      </div>
                      <input
                        v-model="form.confirmPassword"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        class="field-input with-left-icon"
                        placeholder="Repetir contraseña"
                        minlength="8"
                        maxlength="8"
                        autocomplete="new-password"
                      />
                      <button 
                        type="button"
                        class="input-toggle-btn"
                        @click="showConfirmPassword = !showConfirmPassword"
                      >
                        <svg v-if="!showConfirmPassword" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <path d="M1 9C1 9 4 3 9 3C14 3 17 9 17 9C17 9 14 15 9 15C4 15 1 9 1 9Z"/>
                          <circle cx="9" cy="9" r="2.25"/>
                        </svg>
                        <svg v-else viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                          <path d="M2 2L16 16M2.5 9.5C2.5 9.5 4.5 5.5 9 5.5C10.5 5.5 11.75 6.16667 12.6667 7M15.5 9.5C15.5 9.5 13.5 13.5 9 13.5C8 13.5 7.08333 13.1667 6.33333 12.6667" stroke-linecap="round"/>
                          <path d="M9 11.5C10.3807 11.5 11.5 10.3807 11.5 9C11.5 7.61929 10.3807 6.5 9 6.5" stroke-linecap="round"/>
                        </svg>
                      </button>
                      <div 
                        class="input-status"
                        :class="{ 
                          'status-valid': passwordsMatch && form.password, 
                          'status-invalid': form.confirmPassword && !passwordsMatch 
                        }"
                      >
                        <svg v-if="passwordsMatch && form.password" viewBox="0 0 18 18" fill="currentColor">
                          <path d="M15.293 4.293L7 12.586L3.707 9.293L2.293 10.707L7 15.414L16.707 5.707L15.293 4.293Z"/>
                        </svg>
                        <svg v-else-if="form.confirmPassword && !passwordsMatch" viewBox="0 0 18 18" fill="currentColor">
                          <path d="M4.293 4.293L9 9L4.293 13.707L5.707 15.293L10.414 10.586L15.121 15.293L16.535 13.879L11.828 9.172L16.535 4.464L15.121 3.05L10.414 7.757L5.707 3.05L4.293 4.293Z"/>
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="password-actions-row">
                  <button 
                    type="button" 
                    class="action-btn generate-btn"
                    @click="fillWithGenerated"
                  >
                    <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                      <path d="M12 18s6-3 6-9V4l-6-2-6 2v5c0 6 6 9 6 9z"/>
                    </svg>
                    Generar contraseña segura
                  </button>
                  <button 
                    type="button" 
                    class="action-btn check-btn"
                    :class="{ 
                      'check-valid': passwordCheckOk, 
                      'check-invalid': passwordChecked && !passwordCheckOk 
                    }"
                    @click="checkPassword"
                  >
                    <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.75">
                      <path d="M7 9l2 2L15 5"/>
                      <rect x="2" y="4" width="11" height="11" rx="2" ry="2"/>
                    </svg>
                    Verificar seguridad
                  </button>
                </div>

                <transition name="expand-smooth">
                  <div v-if="generatedPassword" class="generated-box">
                    <div class="generated-info">
                      <span class="generated-label">Contraseña generada:</span>
                      <span class="generated-value">{{ generatedPassword }}</span>
                    </div>
                    <button 
                      type="button" 
                      class="copy-mini-btn"
                      @click="copyToClipboard(generatedPassword)"
                    >
                      <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75">
                        <rect x="7" y="7" width="8" height="8" rx="1.5"/>
                        <path d="M4 11H3A2 2 0 0 1 1 9V3A2 2 0 0 1 3 1h7A2 2 0 0 1 12 3v1"/>
                      </svg>
                      Copiar
                    </button>
                  </div>
                </transition>

                <transition name="expand-smooth">
                  <div v-if="form.password || passwordChecked" class="requirements-panel">
                    <h4 class="req-title">Requisitos de seguridad</h4>
                    <div class="req-grid">
                      <div 
                        v-for="req in passwordReqs" 
                        :key="req.key" 
                        class="req-item-card"
                        :class="{ 'req-ok': req.valid }"
                      >
                        <div class="req-icon-mini" :class="{ 'icon-ok': req.valid }">
                          <svg v-if="req.valid" viewBox="0 0 14 14" fill="currentColor">
                            <path d="M11.293 3.293L6 8.586L3.707 6.293L2.293 7.707L6 11.414L12.707 4.707L11.293 3.293Z"/>
                          </svg>
                          <svg v-else viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.75">
                            <circle cx="7" cy="7" r="5.5"/>
                          </svg>
                        </div>
                        <span class="req-text-mini">{{ req.label }}</span>
                      </div>
                    </div>
                  </div>
                </transition>
              </div>
            </div>

            <div v-else-if="currentStep === 3" key="step-3" class="form-step-panel">
              
              <div class="summary-center">
                <div class="summary-icon-wrap">
                  <svg viewBox="0 0 56 56" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="4" y="4" width="48" height="48" rx="12" ry="12"/>
                    <path d="M20 28L24 32L36 20" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </div>
                
                <h2 class="summary-title">Confirmar Registro</h2>
                <p class="summary-subtitle">Revisa los datos antes de continuar</p>

                <div class="summary-cards">
                  <div class="summary-card">
                    <span class="summary-label">Tipo de Usuario</span>
                    <span class="summary-value">
                      <span 
                        class="type-pill"
                        :class="userType === 'oficina' ? 'pill-primary' : 'pill-secondary'"
                      >
                        {{ userType === 'oficina' ? 'Oficina' : 'Monitor' }}
                      </span>
                    </span>
                  </div>
                  
                  <div class="summary-card">
                    <span class="summary-label">Nombre Completo</span>
                    <span class="summary-value">{{ form.nombre }} {{ form.apellidoP }} {{ form.apellidoM }}</span>
                  </div>

                  <div v-if="userType === 'monitor'" class="summary-card">
                    <span class="summary-label">Número de Control</span>
                    <span class="summary-value code-style">{{ form.numeroControl || '-' }}</span>
                  </div>

                  <div class="summary-card">
                    <span class="summary-label">Usuario</span>
                    <span class="summary-value code-style">{{ form.usuario }}</span>
                  </div>

                  <div v-if="selectedClubId" class="summary-card">
                    <span class="summary-label">Club Asignado</span>
                    <span class="summary-value">
                      {{ clubs.find(c => c.id === selectedClubId)?.nombre || 'Asignado' }}
                    </span>
                  </div>
                </div>

                <div class="summary-footer-note">
                  <p>Al hacer clic en "Registrar", se creará la cuenta de usuario con los datos mostrados.</p>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <div class="workspace-footer">
          <div class="footer-left">
            <button 
              type="button"
              class="footer-nav-btn back-btn"
              v-if="currentStep > 1"
              @click="prevStep"
            >
              <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4L5 9L11 14" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              Atrás
            </button>
          </div>

          <div class="footer-center">
            <button 
              type="button"
              class="text-link-btn"
              @click="navigateToLogin"
            >
              <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.75">
                <path d="M10 12L6 8L10 4" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M5 8H14"/>
              </svg>
              Volver al inicio de sesión
            </button>
          </div>

          <div class="footer-right">
            <button
              v-if="currentStep < 3"
              type="button"
              class="footer-nav-btn next-btn"
              @click="nextStep"
            >
              Continuar
              <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M7 4L13 9L7 14" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </button>

            <button
              v-if="currentStep === 3"
              type="button"
              class="footer-nav-btn confirm-btn"
              :disabled="isLoading"
              @click="handleRegister"
            >
              <span v-if="!isLoading" class="btn-content-inline">
                <svg viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 1.5C13.1421 1.5 16.5 4.85786 16.5 9C16.5 13.1421 13.1421 16.5 9 16.5C4.85786 16.5 1.5 13.1421 1.5 9C1.5 4.85786 4.85786 1.5 9 1.5Z"/>
                  <path d="M6.25 9L7.75 10.5L11.75 6.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Confirmar Registro
              </span>
              <span v-else class="btn-loading-inline">
                <span class="spinner-mini"></span>
                Registrando...
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <transition name="modal-fade">
      <div v-if="showSuccessModal" class="modal-backdrop" @click.self="closeModalAndReset"></div>
    </transition>

    <transition name="modal-slide">
      <div v-if="showSuccessModal" class="modal-container">
        <div class="success-modal">
          <div class="success-top">
            <div class="success-icon-large">
              <svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="2.5">
                <circle cx="24" cy="24" r="20"/>
                <path d="M16 24L20 28L32 16" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3 class="success-title-lg">¡Registro Exitoso!</h3>
            <p class="success-sub-lg">El usuario ha sido creado correctamente</p>
          </div>

          <div class="success-details-grid">
            <div class="detail-row">
              <span class="detail-key">Nombre</span>
              <span class="detail-val">{{ registeredUser?.nombre }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-key">Usuario</span>
              <span class="detail-val code">{{ registeredUser?.usuario }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-key">Tipo</span>
              <span class="detail-val">
                <span 
                  class="type-pill"
                  :class="registeredUser?.tipo === 'OFICINA' ? 'pill-primary' : 'pill-secondary'"
                >
                  {{ registeredUser?.tipo }}
                </span>
              </span>
            </div>
          </div>

          <transition name="expand-smooth">
            <div v-if="generatedPassword" class="generated-notice">
              <div class="notice-head">
                <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                  <path d="M10 18s6-3 6-9V4l-6-2-6 2v5c0 6 6 9 6 9z"/>
                </svg>
                <span>Contraseña generada</span>
              </div>
              <div class="notice-pass">{{ generatedPassword }}</div>
              <button 
                type="button"
                class="copy-tiny-btn"
                @click="copyToClipboard(generatedPassword)"
              >
                <svg viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.75">
                  <rect x="6" y="6" width="7" height="7" rx="1.5"/>
                  <path d="M3 9H2A1 1 0 0 1 1 8V3A1 1 0 0 1 2 2h5A1 1 0 0 1 8 3v0.5"/>
                </svg>
                Copiar
              </button>
            </div>
          </transition>

          <div class="success-actions">
            <button 
              type="button" 
              class="action-secondary"
              @click="closeModalAndReset"
            >
              Registrar otro
            </button>
            <button 
              type="button" 
              class="action-primary"
              @click="goToLogin"
            >
              Ir al login
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.register-page {
  width: 100vw;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.page-bg {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #05073a 0%, #080A4C 25%, #0d1b6e 60%, #1a237e 100%);
}

.bg-orbs {
  position: absolute;
  inset: 0;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
  animation: float 20s ease-in-out infinite;
}

.orb-1 {
  width: 500px;
  height: 500px;
  background: #4f46e5;
  top: -200px;
  right: -100px;
  animation-delay: 0s;
}

.orb-2 {
  width: 400px;
  height: 400px;
  background: #06b6d4;
  bottom: -150px;
  left: -100px;
  animation-delay: -7s;
}

.orb-3 {
  width: 300px;
  height: 300px;
  background: #8b5cf6;
  top: 40%;
  left: 50%;
  transform: translateX(-50%);
  animation-delay: -14s;
}

@keyframes float {
  0%, 100% { transform: translateY(0) scale(1); }
  50% { transform: translateY(-30px) scale(1.05); }
}

.register-shell {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 1600px;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-visible .register-shell {
  opacity: 1;
  transform: translateY(0);
}

.page-exiting .register-shell {
  opacity: 0;
  transform: translateY(-20px);
  transition: all 0.3s ease;
}

.main-workspace {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 28px;
  box-shadow: 
    0 50px 150px -20px rgba(0, 0, 0, 0.25),
    0 0 0 1px rgba(255, 255, 255, 0.1),
    inset 0 1px 0 rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  height: calc(100vh - 3rem);
  min-height: 650px;
  max-height: 900px;
  position: relative;
}

.workspace-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2.5rem;
  flex-shrink: 0;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-mark {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 15px rgba(8, 10, 76, 0.3);
}

.logo-mark svg {
  width: 24px;
  height: 24px;
}

.header-text {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.header-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
  letter-spacing: -0.02em;
}

.header-subtitle {
  font-size: 0.75rem;
  color: #64748b;
  margin: 0;
  font-weight: 400;
}

.step-navigation {
  display: flex;
  align-items: center;
}

.step-track {
  display: flex;
  align-items: center;
  position: relative;
  gap: 0;
}

.connector-lines {
  position: absolute;
  top: 14px;
  left: 40px;
  right: 40px;
  display: flex;
  justify-content: space-between;
  pointer-events: none;
}

.connector {
  width: 56px;
  height: 2px;
  background: #e2e8f0;
  border-radius: 1px;
  transition: background 0.4s ease;
}

.connector-filled {
  background: linear-gradient(90deg, #080A4C, #1a237e);
}

.step-dot-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  position: relative;
  z-index: 1;
  width: 80px;
}

.step-dot {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border: 2px solid #e2e8f0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.dot-number {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #64748b;
}

.dot-check {
  width: 14px;
  height: 14px;
  color: white;
}

.step-current {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  border-color: transparent;
  transform: scale(1.1);
  box-shadow: 0 4px 15px rgba(8, 10, 76, 0.3);
}

.step-current .dot-number {
  color: white;
}

.step-done {
  background: #dbeafe;
  border-color: #3b82f6;
}

.step-done .dot-number {
  color: #1e40af;
}

.step-label-mini {
  font-size: 0.6875rem;
  font-weight: 500;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  transition: color 0.3s ease;
}

.step-dot-wrapper:nth-child(2) .step-label-mini { opacity: 0; }

.workspace-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, #e2e8f0 10%, #e2e8f0 90%, transparent);
  flex-shrink: 0;
}

.workspace-content {
  flex: 1;
  padding: 1.5rem 2.5rem;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.type-selector-section {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  max-width: 600px;
  margin: 0 auto 1.25rem;
}

.type-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1.25rem;
  background: #f8fafc;
  border: 2px solid #e2e8f0;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.type-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, rgba(8, 10, 76, 0.02) 0%, rgba(26, 35, 126, 0.02) 100%);
  opacity: 0;
  transition: opacity 0.25s ease;
}

.type-card:hover {
  border-color: #cbd5e1;
  background: white;
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
}

.type-card:hover::before {
  opacity: 1;
}

.type-active {
  background: linear-gradient(135deg, rgba(8, 10, 76, 0.03) 0%, rgba(26, 35, 126, 0.02) 100%);
  border-color: #080A4C;
  box-shadow: 0 4px 15px rgba(8, 10, 76, 0.08);
}

.type-active::before {
  opacity: 1;
}

.type-icon-wrap {
  width: 36px;
  height: 36px;
  background: #f1f5f9;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  transition: all 0.25s ease;
  flex-shrink: 0;
}

.icon-active {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(8, 10, 76, 0.25);
}

.type-icon-wrap svg {
  width: 18px;
  height: 18px;
}

.type-info-wrap {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
  flex: 1;
}

.type-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  transition: color 0.25s ease;
}

.type-active .type-name {
  color: #080A4C;
}

.type-desc {
  font-size: 0.6875rem;
  color: #94a3b8;
  font-weight: 400;
}

.type-radio {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  border: 2px solid #d1d5db;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.25s ease;
  flex-shrink: 0;
}

.radio-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: white;
  transform: scale(0);
  transition: transform 0.2s ease;
}

.radio-checked {
  border-color: #080A4C;
  background: #080A4C;
}

.radio-checked .radio-dot {
  transform: scale(1);
}

.info-banner,
.error-banner {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 12px;
  margin-bottom: 1rem;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
  transition: all 0.3s ease;
}

.info-banner {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  border-left: 3px solid #3b82f6;
}

.error-banner {
  background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
  border-left: 3px solid #ef4444;
}

.banner-icon-wrap {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #1d4ed8;
  margin-top: 1px;
}

.error-icon {
  color: #dc2626;
}

.banner-text-wrap {
  flex: 1;
  font-size: 0.8125rem;
  line-height: 1.5;
}

.info-banner .banner-text-wrap {
  color: #1e40af;
}

.error-banner .banner-text-wrap {
  color: #991b1b;
}

.banner-dismiss {
  background: none;
  border: none;
  padding: 0.25rem;
  border-radius: 6px;
  cursor: pointer;
  opacity: 0.6;
  transition: all 0.2s ease;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-dismiss:hover {
  opacity: 1;
  background: rgba(0, 0, 0, 0.05);
}

.banner-dismiss svg {
  width: 14px;
  height: 14px;
}

.info-banner .banner-dismiss svg {
  color: #1e40af;
}

.error-banner .banner-dismiss svg {
  color: #991b1b;
}

.form-step-panel {
  flex: 1;
  display: flex;
  flex-direction: column;
  animation: fadeInUp 0.3s ease;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.step-content-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 2.5rem;
  flex: 1;
}

.photo-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 0.5rem;
}

.photo-upload-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  padding: 2rem;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 20px;
  border: 2px dashed #e2e8f0;
  width: 100%;
}

.photo-frame {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  overflow: hidden;
  position: relative;
  background: white;
  border: 3px solid #e2e8f0;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.profile-photo-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-placeholder-icon {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #cbd5e1;
}

.photo-placeholder-icon svg {
  width: 60px;
  height: 60px;
}

.hidden-input {
  position: absolute;
  width: 1px;
  height: 1px;
  opacity: 0;
}

.photo-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #475569;
  cursor: pointer;
  transition: all 0.2s ease;
}

.photo-upload-btn:hover {
  border-color: #080A4C;
  color: #080A4C;
  background: #fafbff;
}

.photo-upload-btn svg {
  width: 16px;
  height: 16px;
}

.photo-hint-text {
  font-size: 0.6875rem;
  color: #94a3b8;
  margin: 0;
}

.fields-col {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.section-header-light {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 0.5rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #f1f5f9;
}

.centered {
  justify-content: center;
  gap: 1rem;
  border-bottom: none;
  padding-bottom: 0;
  margin-bottom: 1.5rem;
}

.section-title-light {
  font-size: 0.9375rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.section-badge-light {
  font-size: 0.6875rem;
  padding: 0.25rem 0.75rem;
  background: linear-gradient(135deg, rgba(8, 10, 76, 0.08) 0%, rgba(26, 35, 126, 0.05) 100%);
  color: #080A4C;
  border-radius: 20px;
  font-weight: 600;
}

.section-tag {
  font-size: 0.6875rem;
  padding: 0.25rem 0.625rem;
  background: #e0e7ff;
  color: #1e40af;
  border-radius: 6px;
  font-weight: 600;
}

.form-grid-wide {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.full-width {
  grid-column: 1 / -1;
}

.field-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #334155;
  display: flex;
  align-items: center;
  gap: 0.375rem;
}

.label-hint {
  font-weight: 400;
  color: #94a3b8;
  font-size: 0.6875rem;
}

.field-input {
  width: 100%;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  color: #1e293b;
  background: #fafafa;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  transition: all 0.2s ease;
  outline: none;
  box-sizing: border-box;
}

.field-input::placeholder {
  color: #9ca3af;
}

.field-input:hover {
  border-color: #d1d5db;
  background: white;
}

.field-input:focus {
  border-color: #080A4C;
  background: white;
  box-shadow: 0 0 0 3px rgba(8, 10, 76, 0.08), 0 4px 12px rgba(8, 10, 76, 0.05);
}

.select-wrapper {
  position: relative;
}

.field-select {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  font-size: 0.875rem;
  color: #1e293b;
  background: #fafafa;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  transition: all 0.2s ease;
  outline: none;
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
}

.field-select:hover {
  border-color: #d1d5db;
  background: white;
}

.field-select:focus {
  border-color: #080A4C;
  background: white;
  box-shadow: 0 0 0 3px rgba(8, 10, 76, 0.08), 0 4px 12px rgba(8, 10, 76, 0.05);
}

.select-arrow {
  position: absolute;
  right: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #9ca3af;
  transition: all 0.2s ease;
}

.select-arrow svg {
  width: 14px;
  height: 14px;
}

.field-select:focus + .select-arrow {
  color: #080A4C;
}

.academic-section {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

.center-content-wrapper {
  max-width: 600px;
  margin: 0 auto;
  width: 100%;
}

.credentials-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.input-with-icon {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon-left {
  position: absolute;
  left: 0.875rem;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
  z-index: 1;
  transition: color 0.2s ease;
}

.input-icon-left svg {
  width: 16px;
  height: 16px;
}

.field-input:focus ~ .input-icon-left,
.field-input:not(:placeholder-shown) ~ .input-icon-left {
  color: #080A4C;
}

.with-left-icon {
  padding-left: 2.75rem;
}

.input-toggle-btn {
  position: absolute;
  right: 0.75rem;
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
  transition: all 0.2s ease;
}

.input-toggle-btn:hover {
  color: #080A4C;
  background: #f1f5f9;
}

.input-toggle-btn svg {
  width: 16px;
  height: 16px;
}

.input-status {
  position: absolute;
  right: 0.75rem;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.input-status svg {
  width: 12px;
  height: 12px;
}

.status-valid {
  background: #dcfce7;
  color: #166534;
}

.status-invalid {
  background: #fee2e2;
  color: #991b1b;
}

.password-actions-row {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.action-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1.5px solid;
}

.generate-btn {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}

.generate-btn:hover {
  background: #dcfce7;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(22, 163, 74, 0.15);
}

.generate-btn svg {
  width: 16px;
  height: 16px;
}

.check-btn {
  background: #f8fafc;
  color: #64748b;
  border-color: #e2e8f0;
}

.check-btn:hover {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.check-valid {
  background: #f0fdf4;
  color: #166534;
  border-color: #bbf7d0;
}

.check-invalid {
  background: #fef2f2;
  color: #991b1b;
  border-color: #fecaca;
}

.generated-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.875rem 1.125rem;
  background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
  border-radius: 12px;
  border: 1px solid #bbf7d0;
  margin-bottom: 1rem;
}

.generated-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.generated-label {
  font-size: 0.6875rem;
  color: #166534;
  font-weight: 500;
}

.generated-value {
  font-family: 'Courier New', monospace;
  font-size: 1.125rem;
  font-weight: 700;
  color: #14532d;
  letter-spacing: 2px;
}

.copy-mini-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 0.875rem;
  background: white;
  border: 1.5px solid #166534;
  color: #166534;
  font-size: 0.75rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.copy-mini-btn:hover {
  background: #166534;
  color: white;
}

.copy-mini-btn svg {
  width: 14px;
  height: 14px;
}

.requirements-panel {
  padding: 1rem 1.125rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.req-title {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.75rem;
}

.req-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.5rem;
}

.req-item-card {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  background: white;
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
}

.req-ok {
  background: #f0fdf4;
  border-color: #bbf7d0;
}

.req-icon-mini {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  color: #9ca3af;
  border: 1.5px solid #d1d5db;
}

.icon-ok {
  background: #166534;
  border-color: #166534;
  color: white;
}

.req-icon-mini svg {
  width: 10px;
  height: 10px;
}

.req-text-mini {
  font-size: 0.6875rem;
  color: #64748b;
  font-weight: 500;
  transition: color 0.2s ease;
}

.req-ok .req-text-mini {
  color: #166534;
}

.summary-center {
  max-width: 500px;
  margin: 0 auto;
  text-align: center;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.summary-icon-wrap {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1.25rem;
  color: white;
  box-shadow: 0 10px 30px rgba(8, 10, 76, 0.25);
}

.summary-icon-wrap svg {
  width: 36px;
  height: 36px;
}

.summary-title {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.375rem;
  letter-spacing: -0.02em;
}

.summary-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 1.5rem;
}

.summary-cards {
  display: grid;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.summary-card {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.875rem 1.125rem;
  background: linear-gradient(135deg, #f8fafc 0%, #fafafa 100%);
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  border-left: 3px solid #080A4C;
  text-align: left;
}

.summary-label {
  font-size: 0.6875rem;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.summary-value {
  font-size: 0.9375rem;
  color: #1e293b;
  font-weight: 600;
}

.code-style {
  font-family: 'Courier New', monospace;
  background: white;
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  display: inline-block;
  letter-spacing: 1px;
}

.type-pill {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  color: white;
}

.pill-primary {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
}

.pill-secondary {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.summary-footer-note {
  padding-top: 1rem;
  border-top: 1px dashed #e5e7eb;
}

.summary-footer-note p {
  font-size: 0.75rem;
  color: #9ca3af;
  margin: 0;
}

.workspace-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2.5rem 1.5rem;
  flex-shrink: 0;
  border-top: 1px solid #f1f5f9;
  background: linear-gradient(180deg, transparent 0%, rgba(248, 250, 252, 0.8) 100%);
}

.footer-left,
.footer-center,
.footer-right {
  display: flex;
  align-items: center;
  min-width: 120px;
}

.footer-center {
  justify-content: center;
}

.footer-right {
  justify-content: flex-end;
}

.footer-nav-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1.5px solid;
}

.back-btn {
  background: white;
  color: #475569;
  border-color: #e2e8f0;
}

.back-btn:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
}

.back-btn svg {
  width: 16px;
  height: 16px;
}

.next-btn {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  color: white;
  border-color: transparent;
  box-shadow: 0 4px 15px rgba(8, 10, 76, 0.25);
}

.next-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(8, 10, 76, 0.35);
}

.next-btn svg {
  width: 16px;
  height: 16px;
}

.confirm-btn {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  color: white;
  border-color: transparent;
  box-shadow: 0 4px 15px rgba(5, 150, 105, 0.25);
}

.confirm-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(5, 150, 105, 0.35);
}

.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-content-inline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-content-inline svg {
  width: 18px;
  height: 18px;
}

.btn-loading-inline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spinner-mini {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.text-link-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  background: none;
  border: none;
  padding: 0.5rem 0.875rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  border-radius: 10px;
  transition: all 0.2s ease;
}

.text-link-btn:hover {
  color: #080A4C;
  background: rgba(8, 10, 76, 0.05);
}

.text-link-btn svg {
  width: 14px;
  height: 14px;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1040;
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.modal-container {
  position: fixed;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1050;
  padding: 1rem;
}

.success-modal {
  background: white;
  border-radius: 24px;
  max-width: 420px;
  width: 100%;
  box-shadow: 
    0 50px 100px -20px rgba(0, 0, 0, 0.25),
    0 0 0 1px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.success-top {
  text-align: center;
  padding: 2rem 2rem 1.5rem;
  background: linear-gradient(180deg, #f0fdf4 0%, white 100%);
}

.success-icon-large {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  color: white;
  box-shadow: 0 10px 30px rgba(5, 150, 105, 0.25);
}

.success-icon-large svg {
  width: 36px;
  height: 36px;
}

.success-title-lg {
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.375rem;
}

.success-sub-lg {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.success-details-grid {
  padding: 1rem 2rem;
  display: flex;
  flex-direction: column;
  gap: 0.625rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 10px;
}

.detail-key {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

.detail-val {
  font-size: 0.875rem;
  color: #1e293b;
  font-weight: 600;
}

.detail-val.code {
  font-family: 'Courier New', monospace;
  letter-spacing: 1px;
}

.generated-notice {
  margin: 0.5rem 2rem 1rem;
  padding: 0.875rem 1rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  border: 1px solid #fcd34d;
}

.notice-head {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-direction: column;
}

.notice-head svg {
  width: 18px;
  height: 18px;
  color: #92400e;
}

.notice-head span {
  font-size: 0.6875rem;
  color: #92400e;
  font-weight: 600;
}

.notice-pass {
  font-family: 'Courier New', monospace;
  font-size: 1rem;
  font-weight: 700;
  color: #78350f;
  letter-spacing: 2px;
}

.copy-tiny-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  background: #92400e;
  border: none;
  color: white;
  font-size: 0.6875rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.copy-tiny-btn:hover {
  background: #78350f;
  transform: translateY(-1px);
}

.copy-tiny-btn svg {
  width: 12px;
  height: 12px;
}

.success-actions {
  display: flex;
  gap: 0.75rem;
  padding: 1.25rem 2rem 2rem;
}

.action-secondary,
.action-primary {
  flex: 1;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.action-secondary {
  background: white;
  color: #374151;
  border: 1.5px solid #e5e7eb;
}

.action-secondary:hover {
  background: #f8fafc;
  border-color: #d1d5db;
}

.action-primary {
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 15px rgba(8, 10, 76, 0.25);
}

.action-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(8, 10, 76, 0.35);
}

.modal-fade-enter-active,
.modal-fade-leave-active,
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to,
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-slide-enter-active {
  transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-slide-leave-active {
  transition: all 0.25s ease;
}

.modal-slide-enter-from {
  opacity: 0;
  transform: translateY(25px) scale(0.96);
}

.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(-15px) scale(0.98);
}

.step-slide-enter-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.step-slide-leave-active {
  transition: all 0.25s ease;
}

.step-slide-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.step-slide-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.info-expand-enter-active,
.expand-smooth-enter-active {
  transition: all 0.3s ease;
}

.info-expand-leave-active,
.expand-smooth-leave-active {
  transition: all 0.2s ease;
}

.info-expand-enter-from,
.info-expand-leave-to,
.expand-smooth-enter-from,
.expand-smooth-leave-to {
  opacity: 0;
  max-height: 0;
  overflow: hidden;
  transform: translateY(-5px);
}

.fade-slow-enter-active,
.fade-slow-leave-active {
  transition: opacity 0.4s ease;
}

.fade-slow-enter-from,
.fade-slow-leave-to {
  opacity: 0;
}

@media (max-width: 1400px) {
  .main-workspace {
    max-height: none;
    height: auto;
    min-height: calc(100vh - 3rem);
  }

  .workspace-content {
    flex: none;
  }
}

@media (max-width: 1100px) {
  .step-content-grid {
    grid-template-columns: 240px 1fr;
    gap: 1.75rem;
  }

  .workspace-header,
  .workspace-content,
  .workspace-footer {
    padding-left: 1.75rem;
    padding-right: 1.75rem;
  }
}

@media (max-width: 900px) {
  .register-page {
    padding: 0.75rem;
  }

  .register-shell {
    height: 100vh;
  }

  .main-workspace {
    border-radius: 16px;
    min-height: 100vh;
  }

  .workspace-header {
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem 1.25rem 0.75rem;
  }

  .header-left {
    align-self: flex-start;
  }

  .step-content-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .photo-col {
    order: -1;
  }

  .photo-upload-area {
    max-width: 400px;
    margin: 0 auto;
  }

  .type-selector-section {
    grid-template-columns: 1fr;
  }

  .form-grid-wide {
    grid-template-columns: 1fr;
  }

  .workspace-content {
    padding: 1rem 1.25rem;
  }

  .workspace-footer {
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem 1.25rem 1.5rem;
  }

  .footer-left,
  .footer-center,
  .footer-right {
    width: 100%;
    justify-content: center;
    min-width: auto;
  }

  .footer-nav-btn {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 550px) {
  .register-page {
    padding: 0;
  }

  .main-workspace {
    border-radius: 0;
    min-height: 100vh;
  }

  .header-title {
    font-size: 1rem;
  }

  .header-subtitle {
    display: none;
  }

  .logo-mark {
    width: 36px;
    height: 36px;
    border-radius: 10px;
  }

  .logo-mark svg {
    width: 20px;
    height: 20px;
  }

  .step-dot-wrapper {
    width: 60px;
  }

  .connector-lines {
    left: 30px;
    right: 30px;
  }

  .connector {
    width: 40px;
  }

  .step-label-mini {
    display: none;
  }

  .type-card {
    padding: 0.75rem 1rem;
  }

  .type-icon-wrap {
    width: 32px;
    height: 32px;
  }

  .type-icon-wrap svg {
    width: 16px;
    height: 16px;
  }

  .photo-frame {
    width: 100px;
    height: 100px;
  }

  .photo-upload-area {
    padding: 1.5rem;
  }

  .credentials-grid {
    gap: 0.75rem;
  }

  .password-actions-row {
    flex-direction: column;
  }

  .generated-box {
    flex-direction: column;
    gap: 0.75rem;
    text-align: center;
  }

  .req-grid {
    grid-template-columns: 1fr;
  }

  .summary-cards {
    gap: 0.5rem;
  }

  .summary-card {
    padding: 0.75rem 1rem;
  }

  .success-actions {
    flex-direction: column;
    padding: 1rem 1.5rem 1.5rem;
  }

  .generated-notice {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
    margin-left: 1.5rem;
    margin-right: 1.5rem;
  }

  .success-details-grid {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }

  .success-top {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}
</style>
