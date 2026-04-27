<template>
  <div class="alumnos-sr-container">
    <h3 class="title">Alumnos sin registrar</h3>

    <div class="toolbar">
      <div class="toolbar-left">
        <button class="btn-import" @click="triggerCSV">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M9 16h6v-6h4l-7-7-7 7h4zm-4 2h14v2H5z"/>
          </svg>
          Importar CSV
        </button>
        <input type="file" ref="csvInput" style="display: none" accept=".csv" @change="handleCSVUpload" />
      </div>
      <div class="toolbar-right">
        <span class="text-hint">Los registros muestran 3 opciones de club propuestas</span>
      </div>
    </div>

    <div v-if="!unregistered || unregistered.length === 0" class="empty-state">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="#cbd5e1">
        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
      </svg>
      <p>No hay alumnos sin registrar</p>
    </div>

    <div v-if="unregistered && unregistered.length" class="table-card">
      <table class="table">
        <thead>
          <tr>
            <th class="th-nombre">Nombre</th>
            <th class="th-control">No. Control</th>
            <th class="th-telefono">Teléfono</th>
            <th class="th-carrera">Carrera</th>
            <th class="th-semestre">Semestre</th>
            <th class="th-estado">Estado</th>
            <th class="th-opciones">Opciones de club</th>
            <th class="th-acciones">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(u, idx) in unregistered" :key="u.id || idx">
            <td class="td-nombre">{{ u.nombre }} {{ u.apellidoP }} {{ u.apellidoM }}</td>
            <td class="td-control">{{ u.numeroControl || u.numero_control || '—' }}</td>
            <td class="td-telefono">{{ u.telefono || '—' }}</td>
            <td class="td-carrera">{{ u.carrera_id || '—' }}</td>
            <td class="td-semestre">{{ u.semestre_id || '—' }}</td>
            <td class="td-estado">
              <span :class="['badge-estado', getBadgeClass(u.estado)]">
                {{ u.estado || 'PENDIENTE' }}
              </span>
            </td>
            <td class="td-opciones">
              <div class="opciones-list">
                <span v-if="u.opciones && u.opciones.length" v-for="(opt, oIndex) in u.opciones.slice(0,3)" :key="oIndex" class="badge-opcion">
                  {{ oIndex + 1 }}. {{ opt }}
                </span>
                <span v-else class="text-muted">—</span>
              </div>
            </td>
            <td class="td-acciones">
              <div class="acciones-botones">
                <template v-if="!u.estado || u.estado === 'PENDIENTE'">
                  <button v-if="u.opciones && u.opciones[0]" class="btn-asignar" @click="assignToClub(u, 0)">Asignar 1</button>
                  <button v-if="u.opciones && u.opciones[1]" class="btn-asignar" @click="assignToClub(u, 1)">Asignar 2</button>
                  <button v-if="u.opciones && u.opciones[2]" class="btn-asignar" @click="assignToClub(u, 2)">Asignar 3</button>
                </template>
                <button class="btn-eliminar" @click="deleteRecord(u, idx)">Eliminar</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AlumnosSR',
  props: ['clubs', 'carreras', 'alumnos'],
  data() {
    return {
      unregistered: [
        { id: 1, nombre: 'Juan', apellidoP: 'Pérez', apellidoM: 'García', numeroControl: '20210001', telefono: '9511234567', carrera_id: 3, semestre_id: 3, estado: 'PENDIENTE', opciones: ['Club de Básquetbol', 'Club de Ajedrez', 'Club de Música'] },
        { id: 2, nombre: 'Maria', apellidoP: 'López', apellidoM: 'Rodríguez', numeroControl: '20210002', telefono: '9512345678', carrera_id: 1, semestre_id: 2, estado: 'PENDIENTE', opciones: ['Club de Fútbol', 'Club de Danza'] },
      ],
    };
  },
  methods: {
    getBadgeClass(estado) {
      if (estado === 'APROBADO') return 'aprobado';
      if (estado === 'RECHAZADO') return 'rechazado';
      return 'pendiente';
    },
    getOpciones(u) {
      return u.opciones || [];
    },
    triggerCSV() {
      this.$refs.csvInput.click();
    },
    handleCSVUpload(event) {
      console.log('CSV upload:', event.target.files);
    },
    assignToClub(alumno, optIndex) {
      console.log('Assign:', alumno, optIndex);
    },
    deleteRecord(alumno, index) {
      this.unregistered.splice(index, 1);
    },
  },
};
</script>

<style scoped>
.alumnos-sr-container { padding: 20px; }
.title { color: #2d3561; font-size: 1.5rem; font-weight: 600; margin-bottom: 20px; text-align: center; }
.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 16px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.toolbar-left { display: flex; gap: 12px; }
.toolbar-right { display: flex; align-items: center; }
.text-hint { font-size: 0.85rem; color: #6c757d; }
.btn-import { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: #17a2b8; border: none; color: white; border-radius: 8px; font-weight: 500; cursor: pointer; }
.btn-import:hover { background: #138496; }
.empty-state { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.empty-state p { margin-top: 16px; color: #6c757d; font-size: 1rem; }
.table-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); overflow: hidden; }
.table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.table thead { background: #f8f9fa; }
.table th { padding: 14px 12px; text-align: left; font-weight: 600; color: #495057; text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; }
.table td { padding: 14px 12px; border-bottom: 1px solid #f0f0f0; }
.table tbody tr:hover { background: #f8f9ff; }
.th-nombre { min-width: 180px; }
.th-control, .th-telefono, .th-carrera, .th-semestre, .th-estado, .th-opciones, .th-acciones { min-width: 80px; }
.td-nombre { font-weight: 500; color: #333; }
.td-control, .td-telefono, .td-carrera, .td-semestre { color: #555; }
.badge-estado { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.aprobado { background: #d4edda; color: #155724; }
.rechazado { background: #f8d7da; color: #721c24; }
.pendiente { background: #fff3cd; color: #856404; }
.opciones-list { display: flex; flex-direction: column; gap: 4px; }
.badge-opcion { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; background: #6c757d; color: white; }
.acciones-botones { display: flex; flex-wrap: wrap; gap: 6px; }
.btn-asignar { padding: 6px 12px; background: #28a745; border: none; color: white; border-radius: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer; }
.btn-asignar:hover { background: #218838; }
.btn-eliminar { padding: 6px 12px; background: transparent; border: 1px solid #dc3545; color: #dc3545; border-radius: 6px; font-size: 0.8rem; font-weight: 500; cursor: pointer; }
.btn-eliminar:hover { background: #dc3545; color: white; }
.text-muted { color: #6c757d; font-size: 0.85rem; }
</style>