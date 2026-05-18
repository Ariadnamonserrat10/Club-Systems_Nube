<template>
  <div class="container">
    <div class="left-panel">
      <div class="geometric-shape shape-1"></div>
      <div class="geometric-shape shape-2"></div>
      <div class="geometric-shape shape-3"></div>

      <div class="left-content">
        <h1>Inicio</h1>
        <p>Sistema de Gestión<br />de Clubs Estudiantiles</p>
        <a @click.prevent="$router.push('/crear-cuenta')" class="btn-create"
          >Crear Cuenta</a
        >
      </div>
    </div>

    <div class="right-panel">
      <div class="logo-section">
        <div class="logo-icon">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path
              d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 
              1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 
              1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
            />
            <circle cx="18" cy="8" r="2" />
            <circle cx="6" cy="8" r="2" />
          </svg>
        </div>
        <h2>Inicio</h2>
      </div>

      <div v-if="errorMessage" class="error-message">{{ errorMessage }}</div>
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
      </div>

      <form @submit.prevent="handleLogin">
        <div class="user-type-selector">
          <button
            type="button"
            class="type-btn"
            :class="{ active: selectedUserType === 'oficina' }"
            @click="selectUserType('oficina')"
          >
            Oficina
          </button>
          <button
            type="button"
            class="type-btn"
            :class="{ active: selectedUserType === 'monitor' }"
            @click="selectUserType('monitor')"
          >
            Monitor
          </button>
        </div>

        <div class="form-group">
          <div class="input-container">
            <input
              class="user-input"
              v-model="usuario"
              type="text"
              placeholder="Usuario"
              maxlength="8"
              @input="sanitizeUsuario"
              required
            />
            <svg
              class="input-icon"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 
                1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 
                1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
              />
            </svg>
          </div>
        </div>

        <div class="form-group">
          <div class="input-container">
            <input
              class="user-input"
              v-model="password"
              type="password"
              placeholder="Contraseña (8 caracteres)"
              minlength="8"
              maxlength="8"
              required
            />
            <svg
              class="input-icon"
              viewBox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 
                3.24 7 6v2H6c-1.1 0-2 .9-2 
                2v10c0 1.1.9 2 2 
                2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 
                9c-1.1 0-2-.9-2-2s.9-2 
                2-2 2 .9 2 2-.9 2-2 
                2zm3.1-9H8.9V6c0-1.71 1.39-3.1 
                3.1-3.1 1.71 0 3.1 1.39 
                3.1 3.1v2z"
              />
            </svg>
          </div>
        </div>

        <div class="forgot-password">
          <a
            href="#"
            @click.prevent="
              showMessage(
                'error',
                'Contacta al administrador para recuperar tu contraseña'
              )
            "
          >
            ¿Olvidó su contraseña?
          </a>
        </div>

        <button type="submit" class="btn-login">Entrar</button>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { BACKEND } from '../services/backend';

export default {
  name: "Login",
  data() {
    return {
      selectedUserType: "oficina",
      usuario: "",
      password: "",
      errorMessage: "",
      successMessage: "",
    };
  },
  methods: {
    selectUserType(type) {
      this.selectedUserType = type;
    },
    showMessage(type, message) {
      this.errorMessage = "";
      this.successMessage = "";
      if (type === "error") this.errorMessage = message;
      else this.successMessage = message;

      setTimeout(() => {
        this.errorMessage = "";
        this.successMessage = "";
      }, 5000);
    },
    sanitizeUsuario() {
      this.usuario = String(this.usuario || '')
        .replace(/[^A-Za-z0-9]/g, '')
        .slice(0, 8);
    },
    getUsuarioPolicyResult(usuario) {
      const val = String(usuario || '');
      const onlyAlnum = /^[A-Za-z0-9]*$/.test(val);
      const len8 = val.length === 8;
      const hasLetterAndDigit = /[A-Za-z]/.test(val) && /\d/.test(val);
      return { ok: onlyAlnum && len8 && hasLetterAndDigit, onlyAlnum, len8, hasLetterAndDigit };
    },
    async handleLogin() {
      this.errorMessage = "";
      this.sanitizeUsuario();
      if (!this.usuario || !this.password) {
        this.showMessage("error", "Por favor, completa todos los campos");
        return;
      }
      const usuarioPolicy = this.getUsuarioPolicyResult(this.usuario);
      if (!usuarioPolicy.ok) {
        this.showMessage("error", "El usuario debe tener exactamente 8 caracteres, solo letras y números, y combinar ambos");
        return;
      }
      if (this.password.length !== 8) {
        this.showMessage("error", "La contraseña debe tener exactamente 8 caracteres");
        return;
      }
      try {
        console.log("Iniciando POST a Login.php con:", {
          usuario: this.usuario,
          password: this.password
        });

        const response = await axios.post(
          `${BACKEND}/Login.php`,
          {
            usuario: this.usuario,
            password: this.password,
            userType: this.selectedUserType
          }
        );

        console.log("Respuesta del servidor:", response.data);

        if (response.data.status === "success") {
          console.log("✅ Login exitoso! ID:", response.data.id);
          sessionStorage.setItem("usuarioId", String(response.data.id));
          sessionStorage.setItem("usuarioNombre", response.data.nombre);
          sessionStorage.setItem("usuarioTipo", response.data.tipo);
          
          // Redirigir según el tipo de usuario
          if (response.data.tipo === "OFICINA") {
            this.$router.push("/oficina");
          } else if (response.data.tipo === "MONITOR") {
            this.$router.push("/monitor");
          }
        } else {
          console.log("Login rechazado:", response.data);
          this.showMessage('error', response.data.message || "Error al iniciar sesión");
        }
      } catch (error) {
        console.error("Error en POST:", error);
        console.error("Response data:", error.response?.data);
        console.error("Status:", error.response?.status);
        this.showMessage('error', error.response?.data?.message || "No se pudo conectar al servidor");
      }
    },
  },
};
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html,
body,
#app {
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  overflow: hidden;
  background: #0F4C81;
}

.container {
  display: flex;
  width: 96vw;
  max-width: 1160px;
  min-height: 80vh;
  margin: auto;
  background: transparent;
  border-radius: 24px;
  overflow: hidden;
  animation: slideIn 0.6s ease-out;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: scale(0.92) translateY(30px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

.left-panel {
  flex: 1;
  background: linear-gradient(135deg, #0F4C81 0%, #0a3b61 45%, #081f44 100%);
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 40px;
  overflow: hidden;
}

.geometric-shape {
  position: absolute;
  background: rgba(255, 255, 255, 0.13);
  animation: float 6s ease-in-out infinite;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0) rotate(45deg);
  }
  50% {
    transform: translateY(-20px) rotate(45deg);
  }
}

.shape-1 {
  width: 360px;
  height: 360px;
  top: -120px;
  left: -140px;
  border-radius: 28px;
}

.shape-2 {
  width: 240px;
  height: 240px;
  bottom: -70px;
  right: -70px;
  transform: rotate(30deg);
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.16);
}

.shape-3 {
  width: 160px;
  height: 160px;
  top: 48%;
  left: 12%;
  transform: rotate(60deg);
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.1);
}

.left-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
}

.left-content h1 {
  font-size: 3rem;
  margin-bottom: 20px;
  font-weight: 300;
  letter-spacing: 2px;
}

.left-content p {
  font-size: 1.15rem;
  opacity: 0.95;
  margin-bottom: 40px;
}

.btn-create {
  padding: 15px 40px;
  background: rgba(255, 255, 255, 0.18);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-radius: 32px;
  font-size: 1.05em;
  cursor: pointer;
  transition: all 0.3s;
  backdrop-filter: blur(10px);
  text-decoration: none;
  display: inline-block;
}

.btn-create:hover {
  background: white;
  color: #080A4C;
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(255, 255, 255, 0.3);
}

.right-panel {
  flex: 1;
  padding: 60px 50px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  background: #f4f6ff;
}

.logo-section {
  text-align: center;
  margin-bottom: 40px;
}

.logo-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 20px;
  background: linear-gradient(135deg, #080A4C 0%, #0a3b61 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 5px 15px rgba(15, 23, 110, 0.25);
}

.logo-icon svg {
  width: 50px;
  height: 50px;
  fill: white;
}

.logo-section h2 {
  color: #080A4C;
  font-size: 1.9em;
  margin-bottom: 10px;
}

.user-type-selector {
  display: flex;
  gap: 10px;
  margin-bottom: 30px;
  justify-content: center;
}

.type-btn {
  flex: 1;
  padding: 12px 20px;
  background: white;
  border: 2px solid #d8dce9;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  color: #5f6789;
  font-weight: 600;
  font-size: 1em;
}

.type-btn:hover {
  border-color: #080A4C;
  color: #080A4C;
  transform: translateY(-2px);
}

.type-btn.active {
  background: linear-gradient(135deg, #080A4C 0%, #0a3b61 100%);
  color: white;
  border-color: #080A4C;
  box-shadow: 0 4px 15px rgba(15, 23, 110, 0.25);
}

.form-group {
  margin-bottom: 25px;
}

.input-container {
  position: relative;
}

.input-icon {
  position: absolute;
  left: 15px;
  top: 50%;
  transform: translateY(-50%);
  width: 20px;
  height: 20px;
  fill: #080A4C;
  transition: all 0.3s;
}

.form-group input {
  width: 100%;
  padding: 15px 15px 15px 50px;
  border: 2px solid #d8dce9;
  border-radius: 12px;
  font-size: 1em;
  transition: all 0.3s;
  background: white;
}

.form-group input:focus {
  outline: none;
  border-color: #080A4C;
  box-shadow: 0 0 0 3px rgba(8, 10, 76, 0.15);
}

.forgot-password {
  text-align: right;
  margin-bottom: 25px;
}

.forgot-password a {
  color: #080A4C;
  text-decoration: none;
  font-size: 0.95em;
}

.btn-login {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #080A4C 0%, #0a3b61 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1em;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 5px 15px rgba(15, 23, 110, 0.3);
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(15, 23, 110, 0.4);
}

.error-message,
.success-message {
  border-radius: 10px;
  padding: 14px;
  margin-bottom: 20px;
}

.error-message {
  background: #fdecea;
  color: #811b29;
  border-left: 4px solid #f5c6cb;
}

.success-message {
  background: #e9f7ef;
  color: #155724;
  border-left: 4px solid #c3e6cb;
}

@media (max-width: 768px) {
  .container {
    flex-direction: column;
    width: 95vw;
    height: auto;
  }
  .right-panel {
    padding: 40px 25px;
  }
}

.user-input {
  color: #101f44;
}
</style>
