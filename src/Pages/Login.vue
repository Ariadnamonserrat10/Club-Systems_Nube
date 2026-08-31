<template>
  <div class="login-container">
    <div class="login-layout">
      <div class="brand-panel">
        <div class="brand-content">
          <div class="logo-mark">
            <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="16" cy="12" r="5" stroke="currentColor" stroke-width="2"/>
              <path d="M7 28C7 23.0294 11.0294 19 16 19C20.9706 19 25 23.0294 25 28" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <circle cx="24" cy="10" r="2" fill="currentColor" opacity="0.5"/>
              <circle cx="8" cy="10" r="2" fill="currentColor" opacity="0.5"/>
            </svg>
          </div>
          <h1 class="brand-title">Sistema de Gestión</h1>
          <p class="brand-subtitle">Clubs Estudiantiles</p>
          <div class="brand-divider"></div>
          <p class="brand-description">
            Plataforma institucional para la administración y control de actividades estudiantiles.
          </p>
        </div>
      </div>

      <div class="form-panel">
        <div class="form-card">
          <div class="form-header">
            <h2 class="form-title">Iniciar Sesión</h2>
            <p class="form-subtitle">Ingresa tus credenciales para continuar</p>
          </div>

          <transition name="fade">
            <div v-if="errorMessage" class="alert alert-error">
              <svg class="alert-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M10 7V11" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <path d="M10 15H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
              <span>{{ errorMessage }}</span>
            </div>
          </transition>

          <form @submit.prevent="handleLogin" class="login-form">
            <div class="type-selector">
              <button
                type="button"
                class="type-btn"
                :class="{ active: selectedUserType === 'oficina' }"
                @click="selectUserType('oficina')"
              >
                <svg class="type-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M5 7H6M8 7H9M11 7H12M5 9H6M8 9H9M11 9H12M5 11H6M8 11H9M11 11H12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="type-label">Oficina</span>
                <div class="type-indicator" :class="{ active: selectedUserType === 'oficina' }"></div>
              </button>

              <button
                type="button"
                class="type-btn"
                :class="{ active: selectedUserType === 'monitor' }"
                @click="selectUserType('monitor')"
              >
                <svg class="type-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M3 16C3 12.6863 5.68629 10 9 10C12.3137 10 15 12.6863 15 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <span class="type-label">Monitor</span>
                <div class="type-indicator" :class="{ active: selectedUserType === 'monitor' }"></div>
              </button>
            </div>

            <div class="form-group">
              <label class="form-label">Usuario</label>
              <div class="input-wrapper">
                <svg class="input-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M3 16C3 12.6863 5.68629 10 9 10C12.3137 10 15 12.6863 15 16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input
                  v-model="usuario"
                  type="text"
                  class="form-input"
                  placeholder="Ingresa tu usuario"
                  maxlength="8"
                  @input="sanitizeUsuario"
                  required
                  autocomplete="username"
                />
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Contraseña</label>
              <div class="input-wrapper">
                <svg class="input-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <rect x="3" y="8" width="12" height="8" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M6 8V6C6 4.34315 7.34315 3 9 3C10.6569 3 12 4.34315 12 6V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                </svg>
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  class="form-input"
                  placeholder="Ingresa tu contraseña"
                  minlength="8"
                  required
                  autocomplete="current-password"
                />
                <button 
                  type="button"
                  class="input-toggle"
                  @click="showPassword = !showPassword"
                >
                  <svg v-if="!showPassword" class="toggle-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M1 9C1 9 4 3 9 3C14 3 17 9 17 9C17 9 14 15 9 15C4 15 1 9 1 9Z" stroke="currentColor" stroke-width="1.5"/>
                    <circle cx="9" cy="9" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                  </svg>
                  <svg v-else class="toggle-icon" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M2 2L16 16M2.5 9.5C2.5 9.5 4.5 5.5 9 5.5C10.5 5.5 11.75 6.16667 12.6667 7M15.5 9.5C15.5 9.5 13.5 13.5 9 13.5C8 13.5 7.08333 13.1667 6.33333 12.6667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M9 11.5C10.3807 11.5 11.5 10.3807 11.5 9C11.5 7.61929 10.3807 6.5 9 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  </svg>
                </button>
              </div>
            </div>

            <button 
              type="submit" 
              class="btn-primary"
              :disabled="isLoading"
            >
              <span v-if="!isLoading" class="btn-content">
                Entrar
                <svg class="btn-arrow" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M3.75 9H14.25M10.5 4.5L14.25 9L10.5 13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </span>
              <span v-else class="btn-loading">
                <span class="spinner"></span>
                Iniciando sesión...
              </span>
            </button>
          </form>

          <div class="form-divider">
            <span class="divider-text"></span>
          </div>

          <div class="form-actions">
            <button 
              type="button"
              class="link-btn"
              @click="showForgotPassword"
            >
              <svg class="link-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="3" y="7" width="10" height="6" rx="1" stroke="currentColor" stroke-width="1.25"/>
                <path d="M5 7V5C5 3.89543 5.89543 3 7 3C8.10457 3 9 3.89543 9 5V7" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
              </svg>
              ¿Olvidaste tu contraseña?
            </button>

            <router-link v-if="false" to="/crear-cuenta" class="link-btn link-primary">
              <svg class="link-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="8" cy="5" r="2" stroke="currentColor" stroke-width="1.25"/>
                <path d="M3 13C3 10.7909 5.23858 9 8 9C10.7614 9 13 10.7909 13 13" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
                <path d="M8 3V7M6 5H10" stroke="currentColor" stroke-width="1.25" stroke-linecap="round"/>
              </svg>
              Crear cuenta nueva
            </router-link>
          </div>
         </div>
       </div>
     </div>

     <transition name="modal-fade">
       <div v-if="showForgotModal" class="modal-backdrop" @click.self="closeForgotModal"></div>
     </transition>

     <transition name="modal-slide">
       <div v-if="showForgotModal" class="modal-container">
         <div class="info-modal">
           <div class="modal-header">
             <div class="modal-icon">
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <circle cx="12" cy="12" r="10"/>
                 <path d="M12 16v-4"/>
                 <path d="M12 8h.01"/>
               </svg>
             </div>
             <h3 class="modal-title">Recuperar Contraseña</h3>
             <button class="modal-close" @click="closeForgotModal">
               <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                 <path d="M18 6L6 18M6 6l12 12"/>
               </svg>
             </button>
           </div>
           <div class="modal-body">
             <p class="modal-text">
               Para recuperar tu contraseña, por favor contacta al administrador del sistema.
             </p>
             <div class="info-card">
               <div class="info-item">
                 <svg class="info-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                 </svg>
                 <span class="info-item-label">Correo electrónico</span>
               </div>
               <div class="info-item">
                 <svg class="info-item-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                 </svg>
                 <span class="info-item-label">Extensión administrativa</span>
               </div>
             </div>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn-primary" @click="closeForgotModal">
               Entendido
             </button>
           </div>
         </div>
       </div>
     </transition>
   </div>
 </template>

<script>
import axios from 'axios';
import { BACKEND } from '../services/backend';
import { authService } from '../services/auth';

export default {
  name: "Login",
   data() {
     return {
       selectedUserType: "oficina",
       usuario: "",
       password: "",
       showPassword: false,
       errorMessage: "",
       isLoading: false,
       showForgotModal: false,
     };
   },
  methods: {
    selectUserType(type) {
      this.selectedUserType = type;
      this.errorMessage = "";
    },
     showForgotPassword() {
       this.showForgotModal = true;
     },
     closeForgotModal() {
       this.showForgotModal = false;
     },
    sanitizeUsuario() {
      this.usuario = String(this.usuario || '')
        .replace(/[^A-Za-z0-9]/g, '')
        .slice(0, 8);
    },
    async handleLogin() {
      this.errorMessage = "";
      this.sanitizeUsuario();
      
      if (!this.usuario || !this.password) {
        this.errorMessage = "Por favor, completa todos los campos";
        return;
      }
      
      if (this.usuario.length !== 8) {
        this.errorMessage = "El usuario debe tener exactamente 8 caracteres";
        return;
      }
      
      if (this.password.length < 8) {
        this.errorMessage = "La contraseña debe tener al menos 8 caracteres";
        return;
      }
      
      this.isLoading = true;
      
      try {
        const credentials = {
          usuario: this.usuario,
          password: this.password,
          userType: this.selectedUserType
        };

        const result = await authService.login(credentials);
          
        if (["SUPERADMIN", "ADMIN", "OFICINA"].includes(result.tipo)) {
          this.$router.push("/oficina");
        } else if (result.tipo === "MONITOR") {
          this.$router.push("/monitor");
        }
      } catch (error) {
        this.errorMessage = error.response?.data?.message || error.message || "Error al iniciar sesión";
      } finally {
        this.isLoading = false;
      }
    },
  },
};
</script>

<style scoped>
.login-container {
  width: 100vw;
  height: 100vh;
  overflow: hidden;
}

.login-layout {
  display: flex;
  width: 100%;
  height: 100%;
}

.brand-panel {
  flex: 0 0 42%;
  background: linear-gradient(180deg, #080A4C 0%, #0d1b6e 50%, #0a1358 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.brand-panel::before {
  content: '';
  position: absolute;
  width: 300px;
  height: 300px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.03);
  top: -100px;
  right: -100px;
}

.brand-panel::after {
  content: '';
  position: absolute;
  width: 200px;
  height: 200px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.02);
  bottom: -50px;
  left: -50px;
}

.brand-content {
  position: relative;
  z-index: 1;
  max-width: 320px;
  padding: 0 2rem;
  animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.logo-mark {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1.5rem;
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.15);
}

.logo-mark svg {
  width: 32px;
  height: 32px;
}

.brand-title {
  font-size: clamp(1.5rem, 3vw, 2rem);
  font-weight: 600;
  color: white;
  margin-bottom: 0.25rem;
  letter-spacing: -0.02em;
}

.brand-subtitle {
  font-size: clamp(0.875rem, 2vw, 1rem);
  font-weight: 400;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 1.5rem;
}

.brand-divider {
  width: 40px;
  height: 2px;
  background: rgba(255, 255, 255, 0.3);
  margin-bottom: 1.5rem;
}

.brand-description {
  font-size: 0.875rem;
  color: rgba(255, 255, 255, 0.6);
  line-height: 1.6;
}

.form-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  padding: 2rem;
}

.form-card {
  width: 100%;
  max-width: 400px;
  animation: fadeInUp 0.5s ease-out 0.1s both;
}

.form-header {
  text-align: center;
  margin-bottom: 2rem;
}

.form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.form-subtitle {
  font-size: 0.875rem;
  color: #64748b;
}

.alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  animation: shake 0.4s ease-out;
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-4px); }
  75% { transform: translateX(4px); }
}

.alert-error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  margin-top: 1px;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.type-selector {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.type-btn {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.875rem 1rem;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
}

.type-btn:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.type-btn.active {
  border-color: #080A4C;
  background: #fafbff;
}

.type-icon {
  width: 18px;
  height: 18px;
  color: #64748b;
  transition: color 0.2s ease;
}

.type-btn.active .type-icon {
  color: #080A4C;
}

.type-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #475569;
  transition: color 0.2s ease;
}

.type-btn.active .type-label {
  color: #080A4C;
  font-weight: 600;
}

.type-indicator {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: transparent;
  transition: background 0.2s ease;
}

.type-indicator.active {
  background: linear-gradient(90deg, #080A4C 0%, #1a237e 100%);
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.8125rem;
  font-weight: 600;
  color: #334155;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 1rem;
  width: 16px;
  height: 16px;
  color: #94a3b8;
  transition: color 0.2s ease;
  pointer-events: none;
  z-index: 1;
}

.form-input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 2.75rem;
  font-size: 0.9375rem;
  color: #1e293b;
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  transition: all 0.2s ease;
  outline: none;
}

.form-input::placeholder {
  color: #94a3b8;
}

.form-input:hover {
  border-color: #cbd5e1;
}

.form-input:focus {
  border-color: #080A4C;
  box-shadow: 0 0 0 3px rgba(8, 10, 76, 0.08);
}

.form-input:focus + .input-icon,
.form-input:not(:placeholder-shown) + .input-icon {
  color: #080A4C;
}

.input-toggle {
  position: absolute;
  right: 0.75rem;
  width: 32px;
  height: 32px;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  transition: all 0.2s ease;
}

.input-toggle:hover {
  color: #080A4C;
  background: #f1f5f9;
}

.toggle-icon {
  width: 16px;
  height: 16px;
}

.btn-primary {
  position: relative;
  width: 100%;
  padding: 0.875rem 1.5rem;
  font-size: 0.9375rem;
  font-weight: 600;
  color: white;
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  border: none;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s ease;
  overflow: hidden;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 8px 25px rgba(8, 10, 76, 0.3);
}

.btn-primary:active:not(:disabled) {
  transform: translateY(0);
}

.btn-primary:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-arrow {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.btn-primary:hover .btn-arrow {
  transform: translateX(2px);
}

.btn-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.form-divider {
  display: flex;
  align-items: center;
  margin: 1.5rem 0;
}

.divider-text {
  flex: 1;
  height: 1px;
  background: #e2e8f0;
}

.form-actions {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.link-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s ease;
}

.link-btn:hover {
  color: #475569;
  background: #f8fafc;
  border-color: #cbd5e1;
}

.link-btn.link-primary {
  color: #080A4C;
  border-color: rgba(8, 10, 76, 0.2);
  background: rgba(8, 10, 76, 0.03);
}

.link-btn.link-primary:hover {
  background: rgba(8, 10, 76, 0.06);
  border-color: rgba(8, 10, 76, 0.3);
}

.link-icon {
  width: 14px;
  height: 14px;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 1040;
  backdrop-filter: blur(4px);
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

.info-modal {
  background: white;
  border-radius: 20px;
  max-width: 400px;
  width: 100%;
  box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  padding: 1.5rem 1.5rem 1rem;
  background: linear-gradient(135deg, #f8f9ff 0%, #f0f2f9 100%);
  border-bottom: 1px solid #e5e7eb;
  position: relative;
}

.modal-icon {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #080A4C 0%, #1a237e 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 1rem;
  color: white;
}

.modal-icon svg {
  width: 20px;
  height: 20px;
}

.modal-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.modal-close {
  position: absolute;
  right: 1rem;
  top: 1rem;
  width: 32px;
  height: 32px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  transition: all 0.2s ease;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.modal-close svg {
  width: 18px;
  height: 18px;
}

.modal-body {
  padding: 1.5rem;
}

.modal-text {
  font-size: 0.9375rem;
  color: #4b5563;
  line-height: 1.6;
  margin-bottom: 1.25rem;
}

.info-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  border-radius: 8px;
  transition: background 0.2s ease;
}

.info-item:hover {
  background: white;
}

.info-item + .info-item {
  border-top: 1px solid #e5e7eb;
}

.info-item-icon {
  width: 20px;
  height: 20px;
  color: #080A4C;
  flex-shrink: 0;
}

.info-item-label {
  font-size: 0.875rem;
  color: #374151;
  font-weight: 500;
}

.modal-footer {
  padding: 1rem 1.5rem 1.5rem;
}

.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.modal-slide-enter-active {
  transition: all 0.3s ease;
}

.modal-slide-leave-active {
  transition: all 0.25s ease;
}

.modal-slide-enter-from {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}

.modal-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}

@media (max-width: 1024px) {
  .brand-panel {
    flex: 0 0 38%;
  }
}

@media (max-width: 768px) {
  .login-layout {
    flex-direction: column;
  }

  .brand-panel {
    flex: 0 0 auto;
    padding: 1.5rem 1rem;
  }

  .brand-content {
    max-width: 100%;
    padding: 0;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .brand-divider {
    display: none;
  }

  .brand-description {
    display: none;
  }

  .logo-mark {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-bottom: 1rem;
  }

  .logo-mark svg {
    width: 26px;
    height: 26px;
  }

  .form-panel {
    padding: 1.5rem 1rem;
  }

  .form-card {
    max-width: 100%;
  }
}

@media (max-width: 480px) {
  .type-selector {
    grid-template-columns: 1fr;
  }

  .form-panel {
    padding: 1rem;
  }

  .form-header {
    margin-bottom: 1.5rem;
  }

  .login-form {
    gap: 1rem;
  }
}
</style>
