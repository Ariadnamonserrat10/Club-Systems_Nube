<template>
  <div class="alumnos-sr-container">
    <h3 class="title">Alumnos sin registrar</h3>

    <div class="toolbar">
      <div class="toolbar-left">
        <button class="btn-import" @click="triggerCSV" :disabled="loading">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
          </svg>
          Importar CSV
        </button>
        <input type="file" ref="csvInput" style="display: none" accept=".csv" @change="handleCSVUpload" />
      </div>
      <div class="toolbar-right">
        <span class="text-hint">Los registros muestran hasta 3 opciones de club propuestas</span>
      </div>
    </div>

    <!-- Mensajes de estado -->
    <div v-if="mensaje" :class="['alerta', mensajeTipo]">{{ mensaje }}</div>

    <!-- Estado vacío -->
    <div v-if="!unregistered.length && !loading" class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="#cbd5e1">
        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
      </svg>
      <p>No hay alumnos sin registrar. Importa un CSV para comenzar.</p>
    </div>

    <!-- Spinner -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
      <p>{{ loadingMsg }}</p>
    </div>

    <!-- Tabla temporal -->
    <div v-if="unregistered.length" class="table-card">
      <div class="table-header-info">
        <span class="badge-count">{{ unregistered.length }} alumno(s) pendientes de asignar</span>
        <button class="btn-limpiar" @click="limpiarTabla">Limpiar tabla</button>
      </div>
      <div class="table-scroll">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Nombre completo</th>
              <th>No. Control</th>
              <th>Teléfono</th>
              <th>Carrera</th>
              <th>Semestre</th>
              <th>Estado</th>
              <th>Opciones de club</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(u, idx) in unregistered" :key="idx" :class="{ 'row-asignado': u.estado === 'ASIGNADO', 'row-error': u.estado === 'ERROR' }">
              <td class="td-num">{{ idx + 1 }}</td>
              <td class="td-nombre">{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
              <td class="td-control">{{ u.numeroControl || '—' }}</td>
              <td class="td-telefono">{{ u.telefono || '—' }}</td>
              <td class="td-carrera">
                <span v-if="u.carrera_id">{{ u.carreraNombre }}</span>
                <span v-else class="text-warn">⚠ Sin mapear</span>
              </td>
              <td class="td-semestre">{{ u.semestre_id || '—' }}</td>
              <td class="td-estado">
                <span :class="['badge-estado', getBadgeClass(u.estado)]">
                  {{ u.estado || 'PENDIENTE' }}
                </span>
              </td>
              <td class="td-opciones">
                <div class="opciones-list">
                  <span
                    v-for="(opt, oIdx) in u.opciones.slice(0,4)"
                    :key="oIdx"
                    class="badge-opcion"
                    :class="{ 'opcion-otro': oIdx === 3 }"
                  >
                    {{ oIdx + 1 }}. {{ opt }}
                  </span>
                  <span v-if="!u.opciones.length" class="text-muted">—</span>
                </div>
              </td>
              <td class="td-acciones">
                <div v-if="u.estado === 'PENDIENTE' || u.estado === 'ERROR'" class="acciones-botones">
                  <button
                    v-for="(opt, oIdx) in u.opcionesClub.slice(0,3)"
                    :key="oIdx"
                    class="btn-asignar"
                    :disabled="u.guardando"
                    @click="asignarAlumno(u, idx, opt)"
                    :title="opt.nombre"
                  >
                    {{ oIdx + 1 }}. {{ opt.nombre }}
                  </button>
                  <span v-if="!u.opcionesClub.length" class="text-warn">Sin clubs válidos</span>
                  <button class="btn-eliminar" @click="eliminarFila(idx)" :disabled="u.guardando">Eliminar</button>
                </div>
                <div v-else-if="u.estado === 'ASIGNADO'" class="acciones-asignado">
                  <span class="check-ok">✓ Guardado en BD</span>
                  <button class="btn-eliminar-sm" @click="eliminarFila(idx)">✕</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { BACKEND } from '../services/backend';

export default {
  name: 'AlumnosSR',
  props: {
    clubs: { type: Array, default: () => [] },
    carreras: { type: Array, default: () => [] },
    alumnos: { type: Array, default: () => [] },
  },
  data() {
    return {
      unregistered: [],
      carrerasMap: [],   // [{ id, nombre }] cargado desde API
      loading: false,
      loadingMsg: '',
      mensaje: '',
      mensajeTipo: 'info',
    };
  },
  async mounted() {
    await this.cargarCarreras();
  },
  methods: {
    // ─── Carreras ──────────────────────────────────────────
    async cargarCarreras() {
      try {
        const res = await fetch(`${BACKEND}/carreras`, { headers: { Authorization: `Bearer ${sessionStorage.getItem('auth_token') || ''}` } });
        const json = await res.json();
        // Acepta { data: [...] } o arreglo directo
        this.carrerasMap = Array.isArray(json) ? json : (json.data || []);
       } catch (e) {
        // Silencioso
      }
    },

    resolverCarreraId(nombreCarreraCSV) {
      if (!nombreCarreraCSV) return null;
      const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
      const match = this.carrerasMap.find(c => norm(c.nombre) === norm(nombreCarreraCSV));
      return match ? match.id : null;
    },

    resolverCarreraNombre(nombreCarreraCSV) {
      if (!nombreCarreraCSV) return nombreCarreraCSV;
      const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
      const match = this.carrerasMap.find(c => norm(c.nombre) === norm(nombreCarreraCSV));
      return match ? match.nombre : `⚠ ${nombreCarreraCSV}`;
    },

    // ─── Resolver opciones de club ──────────────────────────
    resolverOpcionesClub(opcionesTexto) {
      // Busca clubs por nombre usando el prop `clubs` del padre
      if (!this.clubs || !this.clubs.length) return [];
      const norm = (s) => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
      return opcionesTexto
        .map(txt => {
          const found = this.clubs.find(c => norm(c.nombre) === norm(txt));
          return found ? { id: found.id, nombre: found.nombre } : null;
        })
        .filter(Boolean);
    },

    // ─── CSV ────────────────────────────────────────────────
    triggerCSV() {
      this.$refs.csvInput.click();
    },

    handleCSVUpload(event) {
      const file = event.target.files[0];
      if (!file) return;
      event.target.value = '';

      this.loading = true;
      this.loadingMsg = 'Leyendo archivo CSV...';

      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const parsed = this.parseCSV(e.target.result);
          // Agregar nuevos sin duplicar por numeroControl
          const existentes = new Set(this.unregistered.map(u => u.numeroControl));
          let nuevos = 0;
          for (const row of parsed) {
            if (!existentes.has(row.numeroControl)) {
              this.unregistered.push(row);
              nuevos++;
            }
          }
          this.mostrarMensaje(
            `Se cargaron ${nuevos} alumno(s) nuevo(s). ${parsed.length - nuevos} omitido(s) por duplicado.`,
            'success'
          );
        } catch (err) {
          this.mostrarMensaje('Error al parsear el CSV: ' + err.message, 'error');
        } finally {
          this.loading = false;
        }
      };
      reader.onerror = () => {
        this.mostrarMensaje('No se pudo leer el archivo.', 'error');
        this.loading = false;
      };
      reader.readAsText(file, 'UTF-8');
    },

    parseCSV(text) {
      // Normalizar saltos de línea Windows/Mac/Linux
      const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n').filter(l => l.trim() !== '');
      if (lines.length < 2) throw new Error('El CSV no tiene datos.');

      const headers = this.splitCSVLine(lines[0]);

      const find = (keywords) =>
        headers.findIndex(h => keywords.some(kw => h.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(kw)));

      const iNombre   = find(['nombre', 'apellido']);
      const iTel      = find(['telef']);
      const iControl  = find(['control']);
      const iSemestre = find(['semestre']);
      const iCarrera  = find(['carrera']);
      const iOp1      = find(['primera']);
      const iOp2      = find(['segunda']);
      const iOp3      = find(['tercera']);
      const iOtro     = find(['otro', 'escriba']);

      const results = [];

      for (let i = 1; i < lines.length; i++) {
        const cols = this.splitCSVLine(lines[i]);
        if (cols.every(c => !c.trim())) continue;

        const get = (idx) => (idx >= 0 && cols[idx] ? cols[idx].trim() : '');

        // Separar nombre completo (Nombre ApellidoP ApellidoM)
        const partes = get(iNombre).split(/\s+/).filter(Boolean);
        const nombre    = partes[0] || '';
        const apellidoP = partes[1] || '';
        const apellidoM = partes.slice(2).join(' ') || '';

        const carreraTexto = get(iCarrera);
        const carrera_id   = this.resolverCarreraId(carreraTexto);
        const carreraNombre = this.resolverCarreraNombre(carreraTexto);

        const opcionesTexto = [get(iOp1), get(iOp2), get(iOp3), get(iOtro)].filter(Boolean);
        const opcionesClub  = this.resolverOpcionesClub(opcionesTexto);

        results.push({
          nombre,
          apellidoP,
          apellidoM,
          numeroControl : get(iControl),
          telefono      : get(iTel),
          carrera_id,
          carreraNombre,
          semestre_id   : get(iSemestre) ? parseInt(get(iSemestre)) : null,
          opciones      : opcionesTexto,   // texto original para mostrar
          opcionesClub,                    // [{ id, nombre }] mapeados
          estado        : 'PENDIENTE',
          guardando     : false,
          errorMsg      : '',
        });
      }

      return results;
    },

    splitCSVLine(line) {
      const result = [];
      let cur = '';
      let inQ = false;
      for (let i = 0; i < line.length; i++) {
        const ch = line[i];
        if (ch === '"') {
          // Comilla doble escapada ""
          if (inQ && line[i + 1] === '"') { cur += '"'; i++; }
          else { inQ = !inQ; }
        } else if (ch === ',' && !inQ) {
          result.push(cur); cur = '';
        } else {
          cur += ch;
        }
      }
      result.push(cur);
      return result;
    },

    // ─── Asignar alumno a club (POST a alumnos.php) ─────────
    async asignarAlumno(alumno, idx, club) {
      alumno.guardando = true;
      alumno.errorMsg  = '';

      const payload = {
        nombre       : alumno.nombre,
        apellidoP    : alumno.apellidoP,
        apellidoM    : alumno.apellidoM,
        numeroControl: alumno.numeroControl,
        telefono     : alumno.telefono || '',
        carrera_id   : alumno.carrera_id,
        semestre_id  : alumno.semestre_id,
        id_club      : club.id,
      };

      try {
        const res = await fetch(`${BACKEND}/alumnos`, {
          method : 'POST',
          headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${sessionStorage.getItem('auth_token') || ''}` },
          body   : JSON.stringify(payload),
        });

        const json = await res.json();

        if (res.ok) {
          // Marcar como asignado en la tabla temporal
          this.unregistered[idx] = {
            ...alumno,
            estado   : 'ASIGNADO',
            guardando: false,
            clubAsignado: club.nombre,
          };
          this.$emit('alumno-registrado', json.data); // notificar al padre
          this.mostrarMensaje(`✓ ${alumno.nombre} ${alumno.apellidoP} asignado a "${club.nombre}"`, 'success');
        } else {
          alumno.estado    = 'ERROR';
          alumno.guardando = false;
          alumno.errorMsg  = json.message || json.error || 'Error desconocido';
          this.mostrarMensaje(`Error: ${alumno.errorMsg}`, 'error');
        }
      } catch (e) {
        alumno.estado    = 'ERROR';
        alumno.guardando = false;
        alumno.errorMsg  = 'Sin conexión al servidor';
        this.mostrarMensaje('Sin conexión al servidor.', 'error');
      }
    },

    // ─── Utilidades ─────────────────────────────────────────
    getBadgeClass(estado) {
      if (estado === 'ASIGNADO') return 'asignado';
      if (estado === 'ERROR')    return 'rechazado';
      return 'pendiente';
    },

    eliminarFila(idx) {
      this.unregistered.splice(idx, 1);
    },

    limpiarTabla() {
      if (confirm('¿Limpiar todos los registros pendientes?')) {
        this.unregistered = [];
      }
    },

    mostrarMensaje(texto, tipo = 'info') {
      this.mensaje     = texto;
      this.mensajeTipo = tipo;
      setTimeout(() => { this.mensaje = ''; }, 5000);
    },
  },
};
</script>

<style scoped>
.alumnos-sr-container { padding: 20px; }
.title { color: #2d3561; font-size: 1.5rem; font-weight: 600; margin-bottom: 20px; text-align: center; }

/* Toolbar */
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding: 14px 16px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.toolbar-left { display: flex; gap: 12px; }
.text-hint { font-size: 0.85rem; color: #6c757d; }
.btn-import { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: #17a2b8; border: none; color: white; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background .2s; }
.btn-import:hover:not(:disabled) { background: #138496; }
.btn-import:disabled { opacity: .5; cursor: not-allowed; }

/* Alertas */
.alerta { padding: 12px 16px; border-radius: 8px; margin-bottom: 14px; font-size: .9rem; font-weight: 500; }
.alerta.success { background: #d4edda; color: #155724; }
.alerta.error   { background: #f8d7da; color: #721c24; }
.alerta.info    { background: #d1ecf1; color: #0c5460; }

/* Empty & loading */
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.empty-state p { margin-top: 16px; color: #6c757d; }
.loading-overlay { display: flex; flex-direction: column; align-items: center; padding: 40px; gap: 12px; }
.spinner { width: 36px; height: 36px; border: 4px solid #e2e8f0; border-top-color: #17a2b8; border-radius: 50%; animation: spin .8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Tabla */
.table-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.table-header-info { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f0f0f0; }
.badge-count { background: #e9ecef; color: #495057; padding: 4px 12px; border-radius: 20px; font-size: .8rem; font-weight: 600; }
.btn-limpiar { padding: 6px 14px; background: transparent; border: 1px solid #6c757d; color: #6c757d; border-radius: 6px; font-size: .8rem; cursor: pointer; }
.btn-limpiar:hover { background: #6c757d; color: white; }
.table-scroll { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.table thead { background: #f8f9fa; }
.table th { padding: 12px 10px; text-align: left; font-weight: 600; color: #495057; text-transform: uppercase; font-size: .7rem; letter-spacing: .5px; border-bottom: 2px solid #dee2e6; white-space: nowrap; }
.table td { padding: 12px 10px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
.table tbody tr:hover { background: #f8f9ff; }
.row-asignado { opacity: .65; }
.row-error td { background: #fff5f5; }
.td-num { color: #adb5bd; font-size: .8rem; width: 32px; }
.td-nombre { font-weight: 500; color: #333; min-width: 160px; }
.td-control, .td-telefono, .td-semestre { color: #555; white-space: nowrap; }

/* Badges estado */
.badge-estado { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
.asignado { background: #d4edda; color: #155724; }
.rechazado { background: #f8d7da; color: #721c24; }
.pendiente { background: #fff3cd; color: #856404; }

/* Opciones */
.opciones-list { display: flex; flex-direction: column; gap: 3px; min-width: 160px; }
.badge-opcion { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: .72rem; font-weight: 500; background: #6c757d; color: white; }
.opcion-otro { background: #868e96; font-style: italic; }
.text-muted { color: #adb5bd; font-size: .82rem; }
.text-warn  { color: #e67e22; font-size: .82rem; font-weight: 500; }

/* Acciones */
.acciones-botones { display: flex; flex-wrap: wrap; gap: 5px; min-width: 200px; }
.btn-asignar { padding: 5px 10px; background: #28a745; border: none; color: white; border-radius: 6px; font-size: .75rem; font-weight: 500; cursor: pointer; white-space: nowrap; max-width: 140px; overflow: hidden; text-overflow: ellipsis; transition: background .15s; }
.btn-asignar:hover:not(:disabled) { background: #218838; }
.btn-asignar:disabled { opacity: .5; cursor: not-allowed; }
.btn-eliminar { padding: 5px 10px; background: transparent; border: 1px solid #dc3545; color: #dc3545; border-radius: 6px; font-size: .75rem; font-weight: 500; cursor: pointer; transition: all .15s; }
.btn-eliminar:hover:not(:disabled) { background: #dc3545; color: white; }
.acciones-asignado { display: flex; align-items: center; gap: 8px; }
.check-ok { color: #28a745; font-weight: 600; font-size: .82rem; }
.btn-eliminar-sm { background: transparent; border: none; color: #adb5bd; cursor: pointer; font-size: 1rem; padding: 2px 6px; border-radius: 4px; }
.btn-eliminar-sm:hover { color: #dc3545; }
@media (max-width: 900px) {
  .alumnos-sr-container { padding: 0; }
  .toolbar { flex-wrap: wrap; gap: 10px; }
  .toolbar-left { flex-wrap: wrap; }
  .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .table { min-width: 1050px; }
}
@media (max-width: 576px) {
  .title { font-size: 1.25rem; text-align: left; }
  .toolbar, .table-header-info { padding: 10px; }
  .btn-import { width: 100%; justify-content: center; }
  .text-hint { width: 100%; }
}
</style>
