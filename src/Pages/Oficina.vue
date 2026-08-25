<template>
  <div class="app-container">
    <!-- Mobile Hamburger Toggle -->
    <button 
      class="sidebar-toggle-mobile" 
      @click="sidebarOpen = !sidebarOpen"
      v-if="isMobile"
    >
      <span class="hamburger-line" :class="{ 'active': sidebarOpen }"></span>
      <span class="hamburger-line" :class="{ 'active': sidebarOpen }"></span>
      <span class="hamburger-line" :class="{ 'active': sidebarOpen }"></span>
    </button>

    <!-- Mobile Overlay -->
    <div 
      class="sidebar-overlay" 
      :class="{ 'active': sidebarOpen }"
      @click="sidebarOpen = false"
      v-if="isMobile"
    ></div>

    <!-- Sidebar -->
    <div
      class="sidebar"
      :class="{ 'sidebar-collapsed': sidebarCollapsed, 'sidebar-mobile-open': sidebarOpen }"
    >
      <div class="sidebar-inner">
        <!-- Logo / App Name -->
        <div class="app-logo-section">
          <div class="app-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="currentColor" opacity="0.9"/>
              <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.7"/>
              <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" opacity="0.5"/>
            </svg>
          </div>
          <span class="app-name" v-if="!sidebarCollapsed">Club Systems</span>
          <button 
            class="collapse-btn" 
            @click="sidebarCollapsed = !sidebarCollapsed"
            v-if="!isMobile"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </button>
        </div>

        <!-- Profile Section -->
        <div class="profile-section">
          <div class="profile-avatar-wrapper">
            <div class="avatar-glow"></div>
            <template v-if="resolveFotoUrl(usuarioActual.foto) && !usuarioActualImageError">
              <img
                :src="resolveFotoUrl(usuarioActual.foto)"
                alt="Usuario"
                class="profile-avatar"
                @error="handleImageError"
              />
            </template>
            <div v-else class="profile-avatar fallback-avatar">
              <span class="avatar-initials">{{ getUserInitials() }}</span>
            </div>
            <div class="status-indicator online"></div>
          </div>
          <div class="profile-info" v-if="!sidebarCollapsed">
            <span class="profile-name">{{ usuarioActual.nombre }} {{ usuarioActual.apellidoP }}</span>
            <span class="profile-role">
              <span class="role-dot"></span>
              {{ usuarioActual.tipo === 'OFICINA' ? 'Oficina' : usuarioActual.tipo }}
            </span>
          </div>
        </div>

        <!-- Menu Divider -->
        <div class="menu-divider" v-if="!sidebarCollapsed"></div>

        <!-- Navigation Menu -->
        <nav class="nav-menu">
         <div class="menu-section">
             <span class="menu-section-title" v-if="!sidebarCollapsed">Gestión</span>
             
             <a 
               class="nav-item" 
               :class="{ 'nav-item-active': currentView === 'Gestion' }"
               @click.prevent="setView('Gestion')"
             >
               <span class="nav-icon">
                 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke-linecap="round"/>
                   <circle cx="9" cy="7" r="4" stroke-linecap="round"/>
                   <path d="M23 21v-2a4 4 0 00-3-3.87" stroke-linecap="round"/>
                   <path d="M16 3.13a4 4 0 010 7.75" stroke-linecap="round"/>
                 </svg>
               </span>
               <span class="nav-label" v-if="!sidebarCollapsed">Usuarios</span>
               <span class="nav-active-indicator" v-if="currentView === 'Gestion' && !sidebarCollapsed"></span>
             </a>

             <a 
               class="nav-item" 
               :class="{ 'nav-item-active': currentView === 'ClubsR' }"
               @click.prevent="setView('ClubsR')"
             >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16" stroke-linecap="round"/>
                  <rect x="7" y="3" width="10" height="18" rx="2" stroke="none" fill="currentColor" opacity="0.15"/>
                  <path d="M12 9h.01" stroke-linecap="round"/>
                  <path d="M12 12h.01" stroke-linecap="round"/>
                  <path d="M12 15h.01" stroke-linecap="round"/>
                  <path d="M12 18h.01" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Clubs</span>
              <span class="nav-active-indicator" v-if="currentView === 'ClubsR' && !sidebarCollapsed"></span>
            </a>
          </div>

          <div class="menu-section">
            <span class="menu-section-title" v-if="!sidebarCollapsed">Alumnos</span>
            
            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'AlumnosSR' }"
              @click.prevent="setView('AlumnosSR')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round"/>
                  <path d="M12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round"/>
                  <circle cx="19" cy="6" r="3" fill="#f59e0b" stroke="none"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Sin registrar</span>
              <span class="nav-badge" v-if="!sidebarCollapsed">Pendiente</span>
              <span class="nav-active-indicator" v-if="currentView === 'AlumnosSR' && !sidebarCollapsed"></span>
            </a>

            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'AlumnosR' }"
              @click.prevent="setView('AlumnosR')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke-linecap="round"/>
                  <circle cx="9" cy="7" r="4" stroke-linecap="round"/>
                  <path d="M23 21v-2a4 4 0 00-3-3.87" stroke-linecap="round"/>
                  <path d="M16 3.13a4 4 0 010 7.75" stroke-linecap="round"/>
                  <path d="M8 13v.01" stroke-linecap="round"/>
                  <path d="M12 13v.01" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Registrados</span>
              <span class="nav-active-indicator" v-if="currentView === 'AlumnosR' && !sidebarCollapsed"></span>
            </a>
          </div>

          <div class="menu-section">
            <span class="menu-section-title" v-if="!sidebarCollapsed">Académico</span>
            
            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Evaluaciones' }"
              @click.prevent="setView('Evaluaciones')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round"/>
                  <polyline points="14,2 14,8 20,8" stroke-linecap="round"/>
                  <line x1="16" y1="13" x2="8" y2="13" stroke-linecap="round"/>
                  <line x1="16" y1="17" x2="8" y2="17" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Evaluaciones</span>
              <span class="nav-active-indicator" v-if="currentView === 'Evaluaciones' && !sidebarCollapsed"></span>
            </a>

            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Periodos' }"
              @click.prevent="setView('Periodos')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke-linecap="round"/>
                  <line x1="16" y1="2" x2="16" y2="6" stroke-linecap="round"/>
                  <line x1="8" y1="2" x2="8" y2="6" stroke-linecap="round"/>
                  <line x1="3" y1="10" x2="21" y2="10" stroke-linecap="round"/>
                  <circle cx="12" cy="16" r="2" fill="#10b981" stroke="none"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Periodos</span>
              <span class="nav-active-indicator" v-if="currentView === 'Periodos' && !sidebarCollapsed"></span>
            </a>

            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Reinscripciones' }"
              @click.prevent="setView('Reinscripciones')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="17,1 21,5 17,9" stroke-linecap="round"/>
                  <polyline points="7,23 3,19 7,15" stroke-linecap="round"/>
                  <path d="M3.51 9a9 9 0 0114.85-3.36L21 9" stroke-linecap="round"/>
                  <path d="M20.49 15a9 9 0 01-14.85 3.36L3 15" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Reinscripciones</span>
              <span class="nav-active-indicator" v-if="currentView === 'Reinscripciones' && !sidebarCollapsed"></span>
            </a>
          </div>

          <div class="menu-section">
            <span class="menu-section-title" v-if="!sidebarCollapsed">Reportes</span>
            
            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Informe' }"
              @click.prevent="setView('Informe')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 17v-2" stroke-linecap="round"/>
                  <path d="M9 13v-2" stroke-linecap="round"/>
                  <path d="M9 9V7" stroke-linecap="round"/>
                  <rect x="3" y="3" width="18" height="18" rx="2" stroke-linecap="round"/>
                  <path d="M13 7h4" stroke-linecap="round"/>
                  <path d="M13 11h4" stroke-linecap="round"/>
                  <path d="M13 15h4" stroke-linecap="round"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Informe</span>
              <span class="nav-active-indicator" v-if="currentView === 'Informe' && !sidebarCollapsed"></span>
            </a>

            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Constancias' }"
              @click.prevent="setView('Constancias')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" stroke-linecap="round"/>
                  <polyline points="14,2 14,8 20,8" stroke-linecap="round"/>
                  <path d="M9 15l2 2 4-4" stroke-linecap="round" stroke-width="2.5"/>
                  <rect x="7" y="4" width="10" height="16" rx="2" fill="currentColor" opacity="0.08"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Constancias</span>
              <span class="nav-active-indicator" v-if="currentView === 'Constancias' && !sidebarCollapsed"></span>
            </a>
          </div>

          <div class="menu-section">
            <span class="menu-section-title" v-if="!sidebarCollapsed">Sistema</span>
            
            <a 
              class="nav-item" 
              :class="{ 'nav-item-active': currentView === 'Auditoria' }"
              @click.prevent="setView('Auditoria')"
            >
              <span class="nav-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10" stroke-linecap="round"/>
                  <path d="M12 6v6l4 2" stroke-linecap="round"/>
                  <circle cx="12" cy="12" r="3" fill="currentColor" opacity="0.2"/>
                </svg>
              </span>
              <span class="nav-label" v-if="!sidebarCollapsed">Auditoría</span>
              <span class="nav-active-indicator" v-if="currentView === 'Auditoria' && !sidebarCollapsed"></span>
            </a>
          </div>
        </nav>

        <!-- Logout Button -->
        <div class="logout-section">
          <button class="logout-btn" @click="cerrarSesion">
            <span class="logout-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4" stroke-linecap="round"/>
                <polyline points="16,17 21,12 16,7" stroke-linecap="round"/>
                <line x1="21" y1="12" x2="9" y2="12" stroke-linecap="round"/>
              </svg>
            </span>
            <span class="logout-label" v-if="!sidebarCollapsed">Cerrar sesión</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido dinámico -->
    <div class="content flex-grow-1 p-4 bg-light overflow-auto">
      <div v-if="currentView === 'Informe'" class="card mb-4 shadow-sm p-3 report-panel">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Desde</label>
            <input type="date" class="form-control" v-model="informeDesde" />
          </div>
          <div class="col-md-4">
            <label class="form-label">Hasta</label>
            <input type="date" class="form-control" v-model="informeHasta" />
          </div>
          <div class="col-md-4 d-grid">
            <button class="btn btn-primary" @click="generarInformeOficina">
              Generar informe
            </button>
          </div>
        </div>
        <div class="mt-3 d-flex flex-column flex-md-row justify-content-between gap-2 align-items-center">
          <p class="mb-0 text-muted small">
            El rango debe estar dentro de un mismo mes y aplicará a todos los clubs.
          </p>
          <button
            class="btn btn-outline-primary"
            :disabled="!informeGenerado || !informeTabla.length"
            @click="descargarInformePDF"
          >
            Descargar PDF
          </button>
        </div>
        <div v-if="informeError" class="alert alert-danger mt-3 py-2">
          {{ informeError }}
        </div>
        <div v-if="informeGenerado && informeTabla.length" class="table-responsive mt-3">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light text-center">
              <tr>
                <th>Club</th>
                <th>Alumno</th>
                <th>Asistencias</th>
                <th>Faltas</th>
                <th>Total</th>
                <th>% Asistencia</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(fila, index) in informeTabla" :key="index">
                <td>{{ fila.club }}</td>
                <td>{{ fila.nombre }}</td>
                <td class="text-center text-success">{{ fila.asistencias }}</td>
                <td class="text-center text-danger">{{ fila.faltas }}</td>
                <td class="text-center">{{ fila.total }}</td>
                <td class="text-center">{{ fila.porcentaje }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <component
        v-else
        :is="currentView"
        :clubs="clubs"
        :alumnos="alumnos"
        :usuarios="usuarios"
        :fechas="fechas"
        :auditoria="auditoria"
        :carreras="carreras"
        :periodoActivo="periodoActivo"
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
        @navigate="setView"
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
import Dashboard from "../components/Dashboard.vue";
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
import jsPDF from "jspdf";
import api from "../services/http";
import { BACKEND } from "../services/backend";
import { authService } from "../services/auth";

export default {
  name: "Oficina",
  components: {
    Gestion,
    Dashboard,
    ClubsR,
    AlumnosSR,
    AlumnosR,
    Constancias,
    Auditoria,
    Evaluaciones,
    Periodos,
    Reinscripciones,
  },
  emits: ["navigate"],
  data() {
     return {
      usuarioActual: {
        nombre: "",
        apellidoP: "",
        apellidoM: "",
        tipo: "",
        foto: null,
      },
      usuarioActualImageError: false,
       currentView: "Dashboard",
      clubs: [],
      periodoActivo: null,

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
          usuario: "Sistema",
          accion: "INICIAR",
          tipo: "sistema",
          descripcion: "Sesión iniciada",
        },
      ],

      // lista temporal de alumnos sin registrar (para asignaciones)
      unregisteredList: [],

      informeDesde: "",
      informeHasta: "",
      informeError: "",
      informeTabla: [],
      informeGenerado: false,

       toastMsg: "",
      errorMsg: "",
      autoRefreshInterval: null,
      
      sidebarCollapsed: false,
      sidebarOpen: false,
      isMobile: false,
    };
  },
   beforeUnmount() {
     if (this.autoRefreshInterval) {
       clearInterval(this.autoRefreshInterval);
       this.autoRefreshInterval = null;
     }
     window.removeEventListener('resize', this.checkMobile);
   },
   methods: {
    checkMobile() {
      this.isMobile = window.innerWidth < 768;
      if (!this.isMobile) {
        this.sidebarOpen = false;
      }
    },
    resolveFotoUrl(foto) {
     if (!foto || typeof foto !== "string") return null;
     if (foto.startsWith("blob:")) return null;
     if (/^https?:\/\//i.test(foto)) return foto;
     
     let path = foto.startsWith("/") ? foto.slice(1) : foto;
     
     if (path.startsWith("Backend/")) {
       path = path.slice(8);
     }
     if (path.startsWith("/")) {
       path = path.slice(1);
     }
     
     return `${BACKEND}/${path}`;
   },
   
   getUserInitials() {
     const nombre = (this.usuarioActual.nombre || '').trim();
     const apellidoP = (this.usuarioActual.apellidoP || '').trim();
     
     let initials = '';
     if (nombre) initials += nombre.charAt(0).toUpperCase();
     if (apellidoP) initials += apellidoP.charAt(0).toUpperCase();
     
      return initials || 'U';
    },
    
    handleImageError() {
      this.usuarioActualImageError = true;
    },
    
    async cargarUsuarioActual() {
      this.usuarioActualImageError = false;
      try {
        const usuarioId = sessionStorage.getItem("usuarioId");
    
        if (!usuarioId) {
          this.cerrarSesion();
          return;
        }

        const response = await axios.get(`${BACKEND}/Usuarios.php?id=${usuarioId}`);

        if (response.data.status === "success" && response.data.data) {
          this.usuarioActual = {
            ...response.data.data,
            foto: response.data.data?.foto
          };
        } else {
          this.cerrarSesion();
          return;
        }
      } catch (error) {
       if (error?.response?.status === 404) {
         this.cerrarSesion();
         return;
       }
     }
   },
    setView(view) {
      this.currentView = view;
    },

    cerrarSesion() {
      authService.logout('current').then(() => {
        this.$router.push("/");
      }).catch(() => {
        authService.clearAuth();
        this.$router.push("/");
      });
    },

    async loadClubs() {
      try {
        const rows = await getClubs();
        // Obtener todos los monitores
        const todosLosMonitores = await getAllMonitoresWithClubs();
        
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
      } catch (e) {
        this.showError(e.message || "No se pudo cargar clubs");
      }
    },

    // Refresco manual de clubs sin disparar la cadena completa de mounted()
    async refreshClubs() {
      await this.loadClubs();
    },

    async cargarPeriodoActivo() {
      try {
        const res = await axios.get(`${BACKEND}/Periodos.php?action=activo`);
        if (res.data.status === 'success' && res.data.data) {
          this.periodoActivo = res.data.data;
        }
      } catch (error) {
        // Silencioso en producción
      }
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
        // Auto-refresh: no audit logging for read operations
       } catch (e) {
         this.showError(e.message || 'No se pudo cargar alumnos');
       }
     },

     async loadAuditoria() {
       try {
         const response = await api.get('/auditoria.php?limit=200');
         if (response.data && response.data.status === 'success' && Array.isArray(response.data.data)) {
           this.auditoria = response.data.data;
         }
       } catch (e) {
         // Silencioso - no mostrar errores
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
      // Auto-refresh: no audit logging for read operations
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

    // auditoría helper - SOLO para almacenamiento local temporal
    // El backend genera los registros de auditoría reales
    async logAction(usuario, accion, tipo, descripcion) {
      const entry = {
        fecha: new Date().toLocaleString(),
        usuario,
        accion,
        tipo,
        descripcion,
      };
      
      // Solo guardar localmente para UI temporal
      // El backend se encarga del logging real de auditoría
      this.auditoria.unshift(entry);
      
      // NOTA: POST directo a auditoria.php está restringido por seguridad
      // El backend genera los logs automáticamente en cada operación
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
        }
      } catch (e) {
        // Silencioso en producción
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
        }
      } catch (e) {
        // Silencioso en producción
      }
    },
    parseDateValue(value) {
      if (!value) return null;
      if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return new Date(value);
      }
      if (/^\d{2}[\/\-]\d{2}$/.test(value)) {
        const [day, month] = value.split(/[/\-]/).map((v) => Number(v));
        const year = new Date().getFullYear();
        return new Date(year, month - 1, day);
      }
      if (/^\d{2}[\/\-]\d{2}[\/\-]\d{4}$/.test(value)) {
        const parts = value.split(/[/\-]/).map((v) => Number(v));
        return new Date(parts[2], parts[1] - 1, parts[0]);
      }
      const parsed = new Date(value);
      return Number.isNaN(parsed.getTime()) ? null : parsed;
    },
    mismasFechaMesUnico(desde, hasta) {
      return (
        desde instanceof Date &&
        hasta instanceof Date &&
        desde.getFullYear() === hasta.getFullYear() &&
        desde.getMonth() === hasta.getMonth()
      );
    },
    fechasEnRangoOficina() {
      const desde = this.parseDateValue(this.informeDesde);
      const hasta = this.parseDateValue(this.informeHasta);
      if (!desde || !hasta || desde > hasta) return [];
      return (this.fechas || []).filter((fecha) => {
        const fechaDate = this.parseDateValue(fecha);
        return fechaDate && fechaDate >= desde && fechaDate <= hasta;
      });
    },
    generarInformeOficina() {
      this.informeError = "";
      this.informeTabla = [];
      this.informeGenerado = false;

      const desde = this.parseDateValue(this.informeDesde);
      const hasta = this.parseDateValue(this.informeHasta);
      if (!desde || !hasta) {
        this.informeError = "Selecciona fechas válidas de inicio y fin.";
        return;
      }
      if (desde > hasta) {
        this.informeError = "La fecha de inicio no puede ser mayor a la fecha de fin.";
        return;
      }
      if (!this.mismasFechaMesUnico(desde, hasta)) {
        this.informeError = "El rango debe estar dentro de un mismo mes.";
        return;
      }
      const diffDays = Math.ceil((hasta - desde) / (1000 * 60 * 60 * 24)) + 1;
      if (diffDays > 31) {
        this.informeError = "El rango debe ser de máximo 31 días.";
        return;
      }
      const fechasRango = this.fechasEnRangoOficina();
      if (!fechasRango.length) {
        this.informeError = "No hay fechas registradas dentro del rango seleccionado.";
        return;
      }
      this.informeTabla = (this.alumnos || []).map((alumno) => {
        const nombre = `${alumno.nombre || ''} ${alumno.apellidoP || ''} ${alumno.apellidoM || ''}`.trim();
        const asistencias = fechasRango.reduce(
          (total, fecha) => total + ((alumno.asistencias || {})[fecha] ? 1 : 0),
          0
        );
        const total = fechasRango.length;
        const faltas = total - asistencias;
        const porcentaje = total ? Math.round((asistencias * 100) / total) : 0;
        return {
          club: alumno.club || "Sin club",
          nombre,
          asistencias,
          faltas,
          total,
          porcentaje,
        };
      }).sort((a, b) => {
        const club = a.club.localeCompare(b.club);
        return club !== 0 ? club : a.nombre.localeCompare(b.nombre);
      });
      this.informeGenerado = true;
    },
    descargarInformePDF() {
      if (!this.informeGenerado || !this.informeTabla.length) return;
      const doc = new jsPDF({ orientation: "portrait", unit: "pt", format: "letter" });
      const title = "Informe de Asistencias por Club";
      doc.setFontSize(16);
      doc.text(title, 40, 40);
      doc.setFontSize(10);
      doc.text(`Periodo: ${this.informeDesde} - ${this.informeHasta}`, 40, 60);
      doc.text(`Generado: ${new Date().toLocaleString()}`, 40, 76);
      const headers = ["Club", "Alumno", "Asistencias", "Faltas", "Total", "% Asistencia"];
      let y = 100;
      const rowHeight = 18;
      const pageHeight = 750;
      const margin = 40;
      doc.setFontSize(9);
      doc.text(headers.join("   "), margin, y);
      y += rowHeight;
      this.informeTabla.forEach((fila, index) => {
        const line = `${fila.club}   ${fila.nombre}   ${fila.asistencias}   ${fila.faltas}   ${fila.total}   ${fila.porcentaje}%`;
        if (y > pageHeight) {
          doc.addPage();
          y = 50;
        }
        doc.text(line, margin, y);
        y += rowHeight;
      });
      doc.save(`informe_asistencias_${this.informeDesde}_${this.informeHasta}.pdf`);
    },
  },
  async mounted() {
  this.checkMobile();
  window.addEventListener('resize', this.checkMobile);
  
  // Esperar a que sessionStorage esté listo
  await new Promise(resolve => setTimeout(resolve, 100));
  
  const usuarioId = sessionStorage.getItem("usuarioId");
  
  if (!usuarioId) {
    this.$router.push("/");
    return;
  }
  
   await this.cargarUsuarioActual();
   await this.loadClubs();
   await this.loadAlumnos();
   await this.cargarPeriodoActivo();
   await this.loadAuditoria();
   
   // Auto-refresh cada 10 segundos
  this.autoRefreshInterval = setInterval(async () => {
    try {
      await Promise.all([
        this.loadClubs(),
        this.loadAlumnos(),
        this.cargarPeriodoActivo()
      ]);
    } catch (e) {
      // Silencioso - no mostrar errores en auto-refresh
    }
    }, 10000);
  },
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

* {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

.app-container {
  display: flex;
  min-height: 100vh;
  position: relative;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 50%, #e2e8f0 100%);
}

/* Mobile Toggle Button */
.sidebar-toggle-mobile {
  position: fixed;
  top: 16px;
  left: 16px;
  z-index: 1000;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  border: none;
  background: linear-gradient(135deg, #1e3a5f 0%, #080A4C 100%);
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 5px;
  box-shadow: 0 4px 20px rgba(30, 58, 95, 0.3);
  transition: all 0.3s ease;
}

.sidebar-toggle-mobile:hover {
  transform: scale(1.05);
  box-shadow: 0 6px 25px rgba(30, 58, 95, 0.4);
}

.hamburger-line {
  width: 22px;
  height: 2px;
  background: white;
  border-radius: 2px;
  transition: all 0.3s ease;
}

.hamburger-line.active:nth-child(1) {
  transform: translateY(7px) rotate(45deg);
}

.hamburger-line.active:nth-child(2) {
  opacity: 0;
  transform: translateX(-20px);
}

.hamburger-line.active:nth-child(3) {
  transform: translateY(-7px) rotate(-45deg);
}

/* Mobile Overlay */
.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  z-index: 899;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}

.sidebar-overlay.active {
  opacity: 1;
  visibility: visible;
}

/* Sidebar */
.sidebar {
  width: 280px;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 900;
  background: linear-gradient(180deg, #080A4C 0%, #0d1b4d 30%, #0a1628 70%, #050630 100%);
  display: flex;
  flex-direction: column;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 
    8px 0 32px rgba(0, 0, 0, 0.15),
    4px 0 16px rgba(0, 0, 0, 0.08);
}

.sidebar::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: 
    radial-gradient(ellipse at 0% 20%, rgba(99, 102, 241, 0.12) 0%, transparent 70%),
    radial-gradient(ellipse at 100% 80%, rgba(13, 71, 161, 0.08) 0%, transparent 70%);
  pointer-events: none;
}

.sidebar-inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 20px 16px;
  position: relative;
  z-index: 1;
}

/* Collapsed Sidebar */
.sidebar-collapsed {
  width: 80px;
}

.sidebar-collapsed .sidebar-inner {
  padding: 20px 12px;
}

/* Mobile Open */
.sidebar-mobile-open {
  transform: translateX(0) !important;
}

/* Logo Section */
.app-logo-section {
  display: flex;
  align-items: center;
  padding: 12px 12px;
  margin-bottom: 24px;
  position: relative;
}

.app-icon {
  width: 44px;
  height: 44px;
  border-radius: 14px;
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #4338ca 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 
    0 4px 20px rgba(99, 102, 241, 0.4),
    0 0 40px rgba(99, 102, 241, 0.15);
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.app-icon::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  animation: shimmerLogo 3s infinite;
}

@keyframes shimmerLogo {
  0% { transform: translateX(-50%); }
  100% { transform: translateX(150%); }
}

.app-icon svg {
  width: 24px;
  height: 24px;
}

.app-name {
  margin-left: 12px;
  font-size: 1.1rem;
  font-weight: 700;
  color: white;
  letter-spacing: 0.3px;
  white-space: nowrap;
}

.collapse-btn {
  margin-left: auto;
  background: rgba(255, 255, 255, 0.06);
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.3s ease;
}

.collapse-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: white;
  transform: rotate(180deg);
}

.collapse-btn svg {
  width: 18px;
  height: 18px;
}

.sidebar-collapsed .collapse-btn {
  transform: rotate(180deg);
}

.sidebar-collapsed .collapse-btn:hover {
  transform: rotate(0deg);
}

/* Profile Section */
.profile-section {
  display: flex;
  align-items: center;
  padding: 16px 12px;
  margin-bottom: 8px;
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  position: relative;
  overflow: hidden;
}

.profile-avatar-wrapper {
  position: relative;
  flex-shrink: 0;
}

.avatar-glow {
  position: absolute;
  top: -4px;
  left: -4px;
  right: -4px;
  bottom: -4px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #4f46e5, #8b5cf6);
  opacity: 0.6;
  filter: blur(8px);
  animation: avatarPulse 3s ease-in-out infinite;
}

@keyframes avatarPulse {
  0%, 100% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 0.7; transform: scale(1.05); }
}

.profile-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  object-fit: cover;
  position: relative;
  z-index: 1;
  border: 3px solid rgba(255, 255, 255, 0.2);
}

.fallback-avatar {
  background: linear-gradient(135deg, #667eea 0%, #7c3aed 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-initials {
  font-size: 1.1rem;
  font-weight: 700;
  color: white;
  letter-spacing: 0.5px;
}

.status-indicator {
  position: absolute;
  bottom: 2px;
  right: 2px;
  width: 14px;
  height: 14px;
  border-radius: 50%;
  background: #10b981;
  border: 2px solid #0a1628;
  z-index: 2;
}

.status-indicator.online::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.3);
  animation: statusPulse 2s ease-in-out infinite;
}

@keyframes statusPulse {
  0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
  50% { transform: translate(-50%, -50%) scale(2.5); opacity: 0; }
}

.profile-info {
  margin-left: 12px;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.profile-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: white;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.profile-role {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.6);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}

.role-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #10b981;
  animation: roleDotPulse 2s ease-in-out infinite;
}

@keyframes roleDotPulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.3); }
}

/* Menu Divider */
.menu-divider {
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  margin: 16px 12px;
}

/* Navigation */
.nav-menu {
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
  margin: 8px 0;
}

.nav-menu::-webkit-scrollbar {
  width: 4px;
}

.nav-menu::-webkit-scrollbar-track {
  background: transparent;
}

.nav-menu::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 2px;
}

.menu-section {
  margin-bottom: 8px;
}

.menu-section-title {
  font-size: 0.65rem;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.35);
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 8px 14px 4px;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 12px 14px;
  margin: 2px 4px;
  border-radius: 12px;
  color: rgba(255, 255, 255, 0.75);
  text-decoration: none;
  cursor: pointer;
  position: relative;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}

.nav-item::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(90deg, transparent);
  opacity: 0;
  transition: opacity 0.3s ease;
  border-radius: 12px;
}

.nav-item:hover {
  background: linear-gradient(135deg, rgba(255, 255, 255, 0.04));
}

.nav-item-active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2));
  color: white;
  box-shadow: 
    0 4px 15px rgba(99, 102, 241, 0.15);
}

.nav-item-active::before {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(79, 70, 229, 0.15));
  opacity: 1;
}

.nav-icon {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}

.nav-icon svg {
  width: 20px;
  height: 20px;
  stroke-width: 1.8;
}

.nav-item:hover .nav-icon {
  transform: scale(1.1);
}

.nav-item-active .nav-icon {
  color: white;
}

.nav-label {
  margin-left: 12px;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  transition: all 0.3s ease;
}

.nav-item-active .nav-label {
  font-weight: 600;
}

.nav-badge {
  margin-left: auto;
  font-size: 0.65rem;
  padding: 3px 8px;
  border-radius: 20px;
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
  font-weight: 600;
  animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
}

.nav-active-indicator {
  position: absolute;
  right: 4px;
  width: 3px;
  height: 20px;
  border-radius: 3px;
  background: linear-gradient(180deg, #6366f1, #8b5cf6);
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
}

.sidebar-collapsed .nav-label,
.sidebar-collapsed .menu-section-title,
.sidebar-collapsed .app-name,
.sidebar-collapsed .profile-info,
.sidebar-collapsed .nav-badge,
.sidebar-collapsed .nav-active-indicator {
  display: none;
}

.sidebar-collapsed .nav-item {
  justify-content: center;
  padding: 14px;
}

.sidebar-collapsed .profile-section {
  justify-content: center;
  padding: 16px 10px;
}

.sidebar-collapsed .app-logo-section {
  justify-content: center;
}

/* Logout Section */
.logout-section {
  margin-top: auto;
  padding-top: 12px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.logout-btn {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 12px 14px;
  margin: 4px;
  border-radius: 12px;
  background: transparent;
  border: none;
  color: rgba(239, 68, 68, 0.85);
  cursor: pointer;
  transition: all 0.3s ease;
}

.logout-btn:hover {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.08));
  color: #ef4444;
  transform: translateX(2px);
}

.logout-icon {
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logout-icon svg {
  width: 20px;
  height: 20px;
  stroke-width: 1.8;
}

.logout-label {
  margin-left: 12px;
  font-size: 0.875rem;
  font-weight: 500;
}

.sidebar-collapsed .logout-btn {
  justify-content: center;
}

.sidebar-collapsed .logout-label {
  display: none;
}

/* Content Area */
.content {
  margin-left: 280px;
  height: 100vh;
  overflow-y: auto;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  transition: margin-left 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar-collapsed + .content,
.content:has(+ .sidebar-collapsed) {
  margin-left: 80px;
}

/* Cards */
.card {
  background: rgba(255, 255, 255, 0.98);
  border-radius: 20px;
  border: none;
  box-shadow: 
    0 4px 20px rgba(0, 0, 0, 0.06),
    0 1px 3px rgba(0, 0, 0, 0.03);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    transform: translateX(-100%);
    width: 280px;
  }

  .content {
    margin-left: 0;
  }

  .sidebar-collapsed {
    transform: translateX(-100%);
  }

  .sidebar-toggle-mobile {
    display: flex;
  }

  .app-container {
    padding-left: 0;
  }
}

@media (min-width: 769px) {
  .sidebar-toggle-mobile,
  .sidebar-overlay {
    display: none;
  }
}

/* Toast */
.toast-container {
  z-index: 2000;
}

/* Page transitions */
:deep(.fade-enter-active),
:deep(.fade-leave-active) {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

:deep(.fade-enter),
:deep(.fade-leave-to) {
  opacity: 0;
  transform: translateY(10px);
}
</style>
