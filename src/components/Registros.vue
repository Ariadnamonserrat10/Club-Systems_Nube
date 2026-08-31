<template>
  <div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div><h4 class="text-primary mb-1">Registros complementarios</h4><small class="text-muted">Documentos, inventario e información básica de emergencia</small></div>
      <button class="btn btn-outline-secondary btn-sm" @click="cargarTodo">Actualizar</button>
    </div>
    <div v-if="mensaje" class="alert" :class="error ? 'alert-danger' : 'alert-success'">{{ mensaje }}</div>
    <ul class="nav nav-tabs mb-3">
      <li class="nav-item" v-for="item in tabs" :key="item.id"><button class="nav-link" :class="{ active: tab === item.id }" @click="tab = item.id">{{ item.nombre }}</button></li>
    </ul>

    <div v-if="tab === 'documentos'" class="row g-3">
      <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body">
        <h5>Registrar comprobante</h5>
        <label class="form-label">Tipo</label><select v-model="documento.tipo" class="form-select mb-2"><option>PAGO</option><option>REPOSICION_CONSTANCIA</option><option>MATERIAL</option><option>OTRO</option></select>
        <label class="form-label">Alumno (opcional)</label><select v-model="documento.alumno_id" class="form-select mb-2"><option value="">Sin alumno</option><option v-for="a in alumnos" :key="a.id" :value="a.id">{{ nombreAlumno(a) }}</option></select>
        <input v-model="documento.concepto" class="form-control mb-2" placeholder="Concepto">
        <input v-model="documento.monto" class="form-control mb-2" type="number" min="0" step="0.01" placeholder="Monto">
        <input class="form-control mb-3" type="file" accept="application/pdf,image/jpeg,image/png" @change="documento.archivo = $event.target.files[0]">
        <small class="text-muted d-block mb-2">PDF, JPG o PNG; máximo 5 MB.</small>
        <button class="btn btn-primary w-100" :disabled="procesando || !documento.archivo" @click="guardarDocumento">Guardar y generar folio</button>
      </div></div></div>
      <div class="col-lg-8"><div class="card shadow-sm"><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Folio</th><th>Tipo</th><th>Concepto</th><th>Fecha</th><th></th></tr></thead><tbody>
        <tr v-for="d in documentos" :key="d.id"><td><code>{{ d.folio }}</code></td><td>{{ d.tipo }}</td><td>{{ d.concepto || '-' }}</td><td>{{ fecha(d.creado_en) }}</td><td><button class="btn btn-sm btn-outline-primary" @click="descargar(d)">Ver</button></td></tr>
        <tr v-if="!documentos.length"><td colspan="5" class="text-center text-muted py-4">Sin documentos</td></tr>
      </tbody></table></div></div></div>
    </div>

    <div v-if="tab === 'materiales'" class="row g-3">
      <div class="col-lg-4"><div class="card shadow-sm"><div class="card-body"><h5>Nuevo material</h5>
        <input v-model="material.nombre" class="form-control mb-2" placeholder="Nombre">
        <textarea v-model="material.descripcion" class="form-control mb-2" placeholder="Descripción"></textarea>
        <input v-model.number="material.cantidad_total" class="form-control mb-2" type="number" min="0" placeholder="Cantidad">
        <select v-model="material.club_id" class="form-select mb-3"><option value="">Uso general</option><option v-for="c in clubs" :key="c.id" :value="c.id">{{ c.nombre }}</option></select>
        <button class="btn btn-primary w-100" :disabled="procesando || !material.nombre" @click="guardarMaterial">Registrar</button>
      </div></div></div>
      <div class="col-lg-8"><div class="card shadow-sm"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>Material</th><th>Club</th><th>Disponible</th><th>Movimiento</th></tr></thead><tbody>
        <tr v-for="m in materiales" :key="m.id"><td>{{ m.nombre }}</td><td>{{ m.club_nombre || 'General' }}</td><td><span class="badge" :class="m.cantidad_disponible ? 'bg-success' : 'bg-danger'">{{ m.cantidad_disponible }} / {{ m.cantidad_total }}</span></td><td><button class="btn btn-sm btn-outline-success me-1" @click="mover(m, 'ENTRADA')">Entrada</button><button class="btn btn-sm btn-outline-warning" @click="mover(m, 'SALIDA')">Salida</button></td></tr>
      </tbody></table></div></div></div>
    </div>

    <div v-if="tab === 'medicos'" class="row justify-content-center"><div class="col-xl-8"><div class="card shadow-sm"><div class="card-body">
      <h5>Información básica para emergencias</h5><p class="text-muted">No se guardan expedientes, diagnósticos extensos, recetas ni documentos clínicos.</p>
      <select v-model="alumnoMedico" class="form-select mb-3" @change="cargarMedicos"><option value="">Selecciona un alumno</option><option v-for="a in alumnos" :key="a.id" :value="a.id">{{ nombreAlumno(a) }}</option></select>
      <div v-if="alumnoMedico" class="row g-2"><div class="col-md-6"><input v-model="medicos.alergias" class="form-control" placeholder="Alergias importantes"></div><div class="col-md-6"><input v-model="medicos.restricciones_fisicas" class="form-control" placeholder="Restricciones físicas"></div><div class="col-12"><input v-model="medicos.condicion_emergencia" class="form-control" placeholder="Condición que requiera atención inmediata"></div><div class="col-md-6"><input v-model="medicos.contacto_emergencia" class="form-control" placeholder="Contacto de emergencia"></div><div class="col-md-6"><input v-model="medicos.telefono_emergencia" class="form-control" placeholder="Teléfono (solo números)"></div><div class="col-12"><textarea v-model="medicos.observaciones" class="form-control" maxlength="500" placeholder="Observaciones breves"></textarea></div><div class="col-12"><button class="btn btn-primary" :disabled="procesando" @click="guardarMedicos">Guardar información</button></div></div>
    </div></div></div></div>
  </div>
</template>

<script>
import { getDocumentos, uploadDocumento, getMateriales, createMaterial, registrarMovimientoMaterial, getDatosMedicos, saveDatosMedicos } from '../services/api';
export default {
  name: 'Registros', props: { clubs: { type: Array, default: () => [] }, alumnos: { type: Array, default: () => [] } },
  data: () => ({ tab: 'documentos', tabs: [{ id: 'documentos', nombre: 'Folios y comprobantes' }, { id: 'materiales', nombre: 'Materiales' }, { id: 'medicos', nombre: 'Emergencias' }], documentos: [], materiales: [], procesando: false, mensaje: '', error: false, documento: { tipo: 'PAGO', alumno_id: '', concepto: '', monto: '', archivo: null }, material: { nombre: '', descripcion: '', cantidad_total: 0, club_id: '' }, alumnoMedico: '', medicos: {} }),
  mounted() { this.cargarTodo(); }, methods: {
    nombreAlumno(a) { return `${a.nombre} ${a.apellidoP} ${a.apellidoM || ''}`.trim(); }, fecha(v) { return v ? new Date(v).toLocaleDateString('es-MX') : '-'; }, avisar(texto, error = false) { this.mensaje = texto; this.error = error; },
    async cargarTodo() { try { [this.documentos, this.materiales] = await Promise.all([getDocumentos(), getMateriales()]); } catch (e) { this.avisar(e.message, true); } },
    async descargar(documento) { try { const response = await fetch(`/api/documentos/${documento.id}`, { headers: { Authorization: `Bearer ${sessionStorage.getItem('auth_token') || ''}` } }); if (!response.ok) throw new Error('No se pudo abrir el documento'); const blob = await response.blob(); const url = URL.createObjectURL(blob); window.open(url, '_blank', 'noopener'); setTimeout(() => URL.revokeObjectURL(url), 60000); } catch (e) { this.avisar(e.message, true); } },
    async guardarDocumento() { this.procesando = true; try { const form = new FormData(); Object.entries(this.documento).forEach(([k, v]) => { if (v !== '' && v != null) form.append(k === 'archivo' ? 'archivo' : k, v); }); const result = await uploadDocumento(form); this.avisar(`Documento registrado con folio ${result.data.folio}`); this.documento = { tipo: 'PAGO', alumno_id: '', concepto: '', monto: '', archivo: null }; await this.cargarTodo(); } catch (e) { this.avisar(e.message, true); } finally { this.procesando = false; } },
    async guardarMaterial() { this.procesando = true; try { await createMaterial({ ...this.material, club_id: this.material.club_id || null }); this.material = { nombre: '', descripcion: '', cantidad_total: 0, club_id: '' }; this.avisar('Material registrado'); await this.cargarTodo(); } catch (e) { this.avisar(e.message, true); } finally { this.procesando = false; } },
    async mover(material, tipo) { const raw = window.prompt(`Cantidad para ${tipo.toLowerCase()}:`); if (!raw) return; const motivo = window.prompt('Motivo del movimiento:'); if (!motivo) return; try { await registrarMovimientoMaterial(material.id, { tipo, cantidad: Number(raw), motivo }); this.avisar('Movimiento registrado'); await this.cargarTodo(); } catch (e) { this.avisar(e.message, true); } },
    async cargarMedicos() { if (!this.alumnoMedico) return; try { const result = await getDatosMedicos(this.alumnoMedico); this.medicos = result.data || {}; } catch (e) { this.avisar(e.message, true); } },
    async guardarMedicos() { this.procesando = true; try { await saveDatosMedicos(this.alumnoMedico, this.medicos); this.avisar('Información de emergencia guardada'); } catch (e) { this.avisar(e.message, true); } finally { this.procesando = false; } }
  }
};
</script>
