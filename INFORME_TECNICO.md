# INFORME TÉCNICO DEL PROYECTO
## Sistema de Gestión de Clubs Estudiantiles — Club Systems

---

## 1. IDENTIFICACIÓN DEL PROYECTO

| Campo | Valor |
|-------|-------|
| **Nombre del proyecto** | Club Systems — Sistema de Gestión de Clubs Estudiantiles |
| **Institución** | Instituto Tecnológico de Tlaxiaco (TecNM) |
| **Rama** | `Ariadna` |
| **Propósito** | Administrar registro, asistencia, evaluaciones, constancias y períodos académicos de clubs estudiantiles |
| **Tipo de sistema** | Aplicación web SPA + aplicación de escritorio (Electron) |

---

## 2. ARQUITECTURA GENERAL

El sistema sigue una arquitectura **cliente-servidor** con separación total entre frontend y backend.

```
┌──────────────────────────────────────────────────────────┐
│                    CLIENTE (Frontend)                      │
│  Vue 3 SPA  ──── Vite :5173 ──── Axios ────┐             │
│  Bootstrap 5.3 + Bootstrap Icons            │             │
│  jsPDF / html2pdf.js (generación PDF)       │             │
├──────────────────────────────────────────────────────────┤
│                    SERVIDOR (Backend)                      │
│  PHP 8.1 (sin framework) ──── Apache/Nginx                │
│  22 endpoints REST ──── MySQL ──── Token Auth             │
├──────────────────────────────────────────────────────────┤
│                    BASE DE DATOS                           │
│  MySQL / InnoDB ──── 11 tablas ──── localhost:3306        │
└──────────────────────────────────────────────────────────┘
```

### 2.1 Flujo de comunicación

```
Navegador (SPA) ──HTTP──> Vite Dev Proxy (:5173/api/*)
    │                            │
    │                    ┌───────┘
    ▼                    ▼
Backend PHP (:80/Backend/*)
    │
    ▼
MySQL (:3306/sistema_clubs)
```

---

## 3. TECNOLOGÍAS UTILIZADAS

### 3.1 Frontend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| Vue 3 | ^3.5 | Framework SPA con Composition API |
| Vite | ^7.0 | Bundler y servidor de desarrollo |
| Bootstrap | ^5.3 | Framework CSS responsivo |
| Bootstrap Icons | ^1.11 | Librería de iconos |
| Axios | ^1.7 | Cliente HTTP con interceptores |
| Vue Router | ^4.4 | Enrutamiento SPA (hash-based) |
| jsPDF | ^2.5 | Generación de PDFs por código |
| html2pdf.js | — | Conversión HTML a PDF |
| Pinia | ^3.0 | Manejo de estado global |

### 3.2 Backend

| Tecnología | Versión | Propósito |
|------------|---------|-----------|
| PHP | ^8.1 | Lenguaje de servidor |
| MySQL | 8.0+ | Base de datos relacional |
| mysqli / PDO | — | Conexión dual a base de datos |
| bcrypt | — | Hash de contraseñas |
| SHA-256 | — | Hashing de tokens de autenticación |

### 3.3 Desktop

| Tecnología | Propósito |
|------------|-----------|
| Electron | Empaquetado como aplicación de escritorio |
| electron-builder | Build y distribución |

### 3.4 DevOps / Deploy

| Plataforma | Archivo de configuración |
|-----------|--------------------------|
| Docker / Cloud Run | `Dockerfile` |
| Google App Engine | `app.yaml` |
| Netlify | `netlify.toml` |
| Firebase Hosting | `firebase.json` |
| Azure Static Web Apps | `staticwebapp.config.json` |
| Heroku | `Procfile` |
| GitHub Actions (Azure CI/CD) | `.github/workflows/azure-static-web-apps-*.yml` |

---

## 4. ESTRUCTURA DEL PROYECTO

```
Club-Systems_Nube/
│
├── index.html                          # Entry point SPA (Bootstrap CDN)
├── package.json                        # Dependencias Node.js
├── composer.json                       # Dependencias PHP
├── vite.config.js                      # Configuración Vite + proxy /api
├── .env                                # Variables de entorno (desarrollo)
├── .env.production                     # Variables de entorno (producción)
│
├── sql/
│   ├── completo.sql                    # Esquema completo + datos iniciales
│   ├── tokens.sql                      # Tabla tokens + procedimientos
│   ├── database_setup.sql              # Tablas evaluaciones + auditoria
│   └── update_db.sql                   # Migración de períodos
│
├── Backend/
│   ├── db.php                          # Conexión MySQL (mysqli + PDO)
│   ├── config.php                      # CORS + configuración de la app
│   ├── cors.php                        # Headers CORS
│   ├── Login.php                       # Autenticación (rate limiting)
│   ├── Logout.php                      # Cierre de sesión
│   ├── TokenManager.php                # Gestión de tokens (creación, validación, refresco)
│   ├── AuthMiddleware.php              # Middleware de autorización por roles
│   ├── Me.php                          # Perfil del usuario autenticado
│   ├── Usuarios.php                    # CRUD de usuarios
│   ├── Registrar.php                   # Registro de usuarios
│   ├── obtenerUsuario.php              # Datos de un usuario específico
│   ├── Alumnos.php                     # CRUD de alumnos
│   ├── Clubs.php                       # CRUD de clubs con auditoría
│   ├── Periodos.php                    # CRUD + cierre/reapertura de períodos
│   ├── Reinscripcion.php               # Flujo de reinscripción
│   ├── asistencias.php                 # Registro de asistencias
│   ├── evaluacion.php                  # Obtener evaluaciones
│   ├── saveEvaluacion.php              # Guardar evaluaciones
│   ├── getEvaluatedStudents.php        # Lista de estudiantes evaluados
│   ├── auditoria.php                   # Visualización de bitácora
│   ├── AuditHelper.php                 # Logger de auditoría (singleton)
│   ├── firmas.php                      # Configuración de firmas/constancias
│   ├── upload.php                      # Subida de fotos
│   ├── getClubs.php                    # Lista simple de clubs
│   ├── getMonitores.php                # Monitores por club
│   ├── carreras.php                    # CRUD de carreras
│   ├── asignarMonitor.php              # Asignación monitor-club
│   ├── Sessions.php                    # Gestión de sesiones activas
│   ├── RefreshToken.php                # Refresco de token
│   ├── validation.php                  # Validadores de texto/números
│   └── verificar_estructura.php        # Diagnóstico de base de datos
│
├── src/
│   ├── main.js                         # Bootstrap de Vue + router
│   ├── App.vue                         # Componente raíz
│   │
│   ├── Pages/
│   │   ├── Login.vue                   # Pantalla de inicio de sesión
│   │   ├── CrearC.vue                  # Registro multi-paso
│   │   ├── Oficina.vue                 # Dashboard de oficina (sidebar)
│   │   └── Monitor.vue                 # Panel de monitor
│   │
│   ├── components/
│   │   ├── Dashboard.vue               # Tarjetas de resumen del sistema
│   │   ├── Monitor.vue                 # Cuadrícula de asistencias
│   │   ├── MonitorComponent.vue        # Re-export de Monitor.vue
│   │   ├── ClubsR.vue                  # Gestión de clubs (tabla + modal)
│   │   ├── AlumnosSR.vue               # Importación CSV + asignación
│   │   ├── Reinscripciones.vue         # Tabla de reinscripciones
│   │   ├── Evaluaciones.vue            # Evaluación multicriterio
│   │   ├── Constancias.vue             # Generación de constancias PDF
│   │   ├── Periodos.vue                # CRUD de períodos
│   │   ├── Auditoria.vue               # Bitácora con filtros
│   │   └── Gestion.vue                 # Gestión de usuarios
│   │
│   └── services/
│       ├── api.js                      # Función fetch con auto-auth + retry
│       ├── http.js                     # Instancia Axios con interceptores
│       ├── auth.js                     # Estado de autenticación, roles
│       ├── backend.js                  # Resolución de URL del backend
│       ├── imageUtils.js               # URL de fotos + iniciales
│       └── logger.js                   # Logging según entorno
│
├── electron/
│   ├── main.js                         # Ventana de Electron (carga :5173)
│   └── preload.js                      # Script de precarga
│
└── constancia-template/
    └── FORMATO.md                      # Formato de constancia institucional
```

---

## 5. BASE DE DATOS

### 5.1 Diagrama de tablas

```
┌─────────────┐       ┌──────────────┐       ┌──────────────┐
│   usuarios  │       │    clubs     │       │   periodos   │
├─────────────┤       ├──────────────┤       ├──────────────┤
│ id (PK)     │       │ id (PK)      │       │ id (PK)      │
│ nombre      │──────>│ nombre       │<──────│ nombre       │
│ apellidoP   │       │ descripcion  │       │ fecha_inicio │
│ apellidoM   │       │ horario      │       │ fecha_fin    │
│ numeroCtrl  │       │ asesor       │       │ estado       │
│ telefono    │       │ periodo_id   │       │ (ACT/CERR)   │
│ carrera_id  │       │ limite_alumn │       └──────────────┘
│ semestre_id │       └──────────────┘              │
│ usuario     │              │                      │
│ password    │              │                      │
│ tipo        │              ▼                      │
│ club_asigna │       ┌──────────────┐              │
│ foto        │       │   alumnos    │              │
└─────────────┘       ├──────────────┤              │
       │              │ id (PK)      │              │
       │              │ numeroCtrl   │              │
       │              │ nombre       │              │
       │              │ ...          │              │
       │              │ id_club(FK)──┘              │
       │              │ periodo_id(FK)──────────────┘
       │              │ estado_periodo              │
       │              │ (ACT/ACR/REP)               │
       │              └──────────────┘              │
       │                      │                     │
       │                      ▼                     │
       │              ┌──────────────┐              │
       │              │ asistencias  │              │
       │              ├──────────────┤              │
       │              │ id (PK)      │              │
       │              │ id_alumno(FK)│              │
       │              │ fecha        │              │
       │              │ presente     │              │
       │              └──────────────┘              │
       │                                            │
       ▼                                            │
┌─────────────┐       ┌──────────────────┐          │
│    tokens   │       │  evaluaciones    │          │
├─────────────┤       ├──────────────────┤          │
│ id (PK)     │       │ id (PK)          │          │
│ token(SHA2) │       │ nombre_estudiante│          │
│ selector    │       │ nombre_club      │          │
│ user_id(FK) │       │ periodo_realizac │          │
│ tipo        │       │ criterio_1..7    │          │
│ expires_at  │       │ observaciones    │          │
│ activo      │       │ valor_numerico   │          │
│ ip          │       │ nivel_desempeno  │          │
│ user_agent  │       └──────────────────┘          │
│ fingerprint │                                     │
│ creado_en   │       ┌──────────────────┐          │
└─────────────┘       │  auditoria       │          │
                       ├──────────────────┤          │
┌─────────────┐       │ id (PK)          │          │
│   carreras  │       │ id_usuario(FK)───┘          │
├─────────────┤       │ usuario                     │
│ id (PK)     │       │ accion                      │
│ nombre      │       │ tipo                        │
└─────────────┘       │ descripcion                 │
                       │ fecha                       │
┌─────────────┐       └──────────────────┘
│  semestres  │
├─────────────┤       ┌──────────────────┐
│ id (PK)     │       │  app_config      │
│ nombre      │       ├──────────────────┤
└─────────────┘       │ clave (PK)       │
                       │ valor            │
                       │ actualizado_en   │
                       └──────────────────┘
```

### 5.2 Descripción de tablas

| Tabla | Registros clave | Propósito |
|-------|----------------|-----------|
| `usuarios` | id, nombre, usuario, password (bcrypt), tipo (OFICINA/MONITOR), club_asignado | Usuarios del sistema |
| `clubs` | id, nombre, descripcion, horario, asesor, periodo_id, limite_alumnos | Clubs estudiantiles |
| `alumnos` | id, numeroControl (UNIQUE+periodo), nombre, id_club, periodo_id, estado_periodo | Alumnos inscritos en clubs |
| `periodos` | id, nombre, fecha_inicio, fecha_fin, estado (ACTIVO/CERRADO) | Períodos académicos |
| `evaluaciones` | id, criterio_1..7, valor_numerico, nivel_desempeno | Evaluaciones por criterio |
| `asistencias` | id, id_alumno, fecha, presente (UNIQUE alumno+fecha) | Control de asistencia |
| `auditoria` | id, id_usuario, usuario, accion, tipo, descripcion, fecha | Bitácora de operaciones |
| `tokens` | id, token (SHA-256), selector, user_id, tipo, expires_at, activo, ip, user_agent, fingerprint | Autenticación por tokens |
| `carreras` | id, nombre | Catálogo de carreras |
| `semestres` | id, nombre | Catálogo de semestres |
| `app_config` | clave (PK), valor, actualizado_en | Configuración global |
| `historial_configuracion` | periodo_id, clave, valor | Configuración por período |

---

## 6. SISTEMA DE AUTENTICACIÓN Y SEGURIDAD

### 6.1 Flujo de autenticación

```
Login ──> Validar credenciales (bcrypt) ──> Generar tokens:
    ├── SESSION token   → 2 horas
    ├── CSRF token      → 1 hora
    └── REMEMBER token  → 2 semanas (opcional)

Cada petición:
    Header: Authorization: Bearer <session_token>
    Header: X-CSRF-Token: <csrf_token> (en mutaciones)
```

### 6.2 Características de seguridad

| Mecanismo | Detalle |
|-----------|---------|
| Hash de contraseñas | bcrypt |
| Hash de tokens | SHA-256 (selector + hash almacenado) |
| Límite de sesiones | Máximo 5 sesiones activas por usuario |
| Rate limiting | 5 intentos cada 300s por IP+usuario |
| Fingerprint | userAgent + screen.width + screen.height + colorDepth + timezone |
| Roles | OFICINA (admin) / MONITOR (limitado) |
| CSRF | Token en cabecera para peticiones de escritura |
| Auditoría | Todas las operaciones CRUD registradas |

### 6.3 Middleware de autorización

- `AuthMiddleware::validateToken()` — Valida token de sesión
- `AuthMiddleware::requireOficina()` — Solo usuarios OFICINA
- `AuthMiddleware::requireMonitor()` — Solo usuarios MONITOR
- `AuthMiddleware::requireRole('OFICINA','MONITOR')` — Roles específicos

---

## 7. MÓDULOS FUNCIONALES

### 7.1 Módulo de Autenticación (Login/Logout)

- Pantalla de inicio de sesión con validación
- Registro de nuevos usuarios con código de verificacion
- Recuperación de contraseña (token RESET)
- Cierre de sesión (individual, otros dispositivos, todas)

### 7.2 Módulo de Dashboard

- Tarjetas de resumen: total clubs, alumnos, usuarios activos
- Gráficos o indicadores de período actual
- Acceso rápido a funciones principales

### 7.3 Módulo de Clubs

- CRUD completo de clubs estudiantiles
- Asignación de horario y asesor
- Límite de alumnos por club
- Asociación a período académico
- Asignación de monitores a clubs

### 7.4 Módulo de Alumnos

- CRUD de alumnos
- Importación masiva vía CSV (con validación)
- Asignación a clubs por período
- Estado por período: ACTIVO, ACREDITADO, REPROBADO
- Control de duplicados por número de control + período

### 7.5 Módulo de Asistencias

- Cuadrícula de asistencia (alumnos × fechas)
- Checkbox de presente/ausente
- Cálculo automático de porcentaje de asistencia
- Validación de unicidad (alumno + fecha)

### 7.6 Módulo de Evaluaciones

- Evaluación multicriterio (7 criterios numéricos)
- Cálculo automático de nivel de desempeño:
  - ≤1 falta → EXCELENTE
  - 2 faltas → BUENO
  - >2 faltas → REGULAR
- Lista de estudiantes ya evaluados

### 7.7 Módulo de Períodos

- CRUD de períodos académicos
- Cierre de período:
  - Alumnos con ≥80% asistencia → ACREDITADO
  - Alumnos con <80% asistencia → REPROBADO
- Reapertura de período cerrado
- Configuración de firmas por período

### 7.8 Módulo de Reinscripciones

- Lista de alumnos ACREDITADOS de períodos anteriores
- Reinscripción a nuevo período
- Opción de cambio de club
- Validación de límite de alumnos

### 7.9 Módulo de Constancias (PDF)

- Generación de constancias en PDF
- Formato institucional (ISO 9001:2015)
- Firmas configurables por período
- Datos del alumno, club, período y evaluación
- Vista previa antes de descargar

### 7.10 Módulo de Auditoría

- Bitácora de todas las operaciones del sistema
- Filtros por usuario, acción, tipo, fecha
- Registro de login/logout, CRUD, cambios de período
- Singleton `AuditHelper` con ventana de deduplicación

### 7.11 Módulo de Gestión de Usuarios

- CRUD de usuarios del sistema
- Asignación de roles (OFICINA/MONITOR)
- Asignación de club a monitores
- Visualización de foto de perfil

---

## 8. API REST — ENDPOINTS

| Método | Endpoint | Propósito | Auth |
|--------|----------|-----------|------|
| POST | `/Backend/Login.php` | Iniciar sesión | No |
| POST | `/Backend/Logout.php` | Cerrar sesión | Sí |
| GET | `/Backend/Me.php` | Perfil del usuario | Sí |
| GET/POST | `/Backend/Usuarios.php` | CRUD usuarios | Sí (OFICINA) |
| POST | `/Backend/Registrar.php` | Registrar usuario | No |
| GET | `/Backend/obtenerUsuario.php` | Usuario por ID | Sí |
| GET/POST | `/Backend/Alumnos.php` | CRUD alumnos | Sí |
| GET/POST | `/Backend/Clubs.php` | CRUD clubs | Sí |
| GET/POST | `/Backend/Periodos.php` | CRUD períodos | Sí |
| POST | `/Backend/Reinscripcion.php` | Reinscribir alumno | Sí |
| GET/POST | `/Backend/asistencias.php` | Asistencias | Sí |
| GET | `/Backend/evaluacion.php` | Obtener evaluación | Sí |
| POST | `/Backend/saveEvaluacion.php` | Guardar evaluación | Sí |
| GET | `/Backend/getEvaluatedStudents.php` | Evaluados | Sí |
| GET | `/Backend/auditoria.php` | Bitácora | Sí (OFICINA) |
| GET/POST | `/Backend/firmas.php` | Firmas | Sí |
| POST | `/Backend/upload.php` | Subir foto | Sí |
| GET | `/Backend/getClubs.php` | Lista clubs | Sí |
| GET | `/Backend/getMonitores.php` | Monitores por club | Sí |
| GET/POST | `/Backend/carreras.php` | CRUD carreras | Sí |
| POST | `/Backend/asignarMonitor.php` | Asignar monitor | Sí |
| GET | `/Backend/Sessions.php` | Sesiones activas | Sí |
| POST | `/Backend/RefreshToken.php` | Refrescar token | Sí |

---

## 9. REGLAS DE NEGOCIO

### 9.1 Ciclo de vida de un período

```
ACTIVO ──Cerrar──> CERRADO
                       │
                       ├── Alumnos ACTIVO → ACREDITADO (asist ≥80%)
                       │                    REPROBADO  (asist <80%)
                       │
                       └── Reabrir ──> ACTIVO
```

### 9.2 Reinscripción

```
Alumno ACREDITADO (período N)
    │
    └── Reinscribir a período N+1
            ├── Mismo club
            └── Cambiar de club (si hay cupo)
```

### 9.3 Cálculo de evaluación

```
Nivel Desempeño:
    EXCELENTE  ← faltas ≤ 1
    BUENO      ← faltas = 2
    REGULAR    ← faltas > 2

Valor numérico: promedio de criterios 1-7
```

### 9.4 Límites y restricciones

- Máximo 5 sesiones simultáneas por usuario
- Máximo 5 intentos de login cada 300 segundos
- Número de control único por período (alumnos)
- Una asistencia por alumno por fecha
- Límite de alumnos configurable por club

---

## 10. FRONTEND — COMPONENTES VUE

### 10.1 Páginas principales

| Componente | Líneas | Funcionalidad |
|-----------|--------|---------------|
| `Login.vue` | 766 | Formulario de inicio de sesión |
| `CrearC.vue` | 754 | Registro multi-paso |
| `Oficina.vue` | 580 | Dashboard con sidebar de navegación |
| `Monitor.vue` | ~50 | Contenedor del panel monitor |

### 10.2 Componentes funcionales

| Componente | Líneas | Funcionalidad |
|-----------|--------|---------------|
| `Dashboard.vue` | 300 | Resumen con tarjetas informativas |
| `Monitor.vue` | 904 | Cuadrícula de asistencias |
| `ClubsR.vue` | 287 | Gestión de clubs (tabla + modal) |
| `AlumnosSR.vue` | 442 | Importación CSV + asignación |
| `Reinscripciones.vue` | 248 | Tabla de reinscripción |
| `Evaluaciones.vue` | 622 | Evaluación multicriterio |
| `Constancias.vue` | 1714 | Generación de constancias PDF |
| `Periodos.vue` | 350 | CRUD de períodos |
| `Auditoria.vue` | 255 | Bitácora con filtros |
| `Gestion.vue` | 718 | CRUD de usuarios |

### 10.3 Servicios

| Archivo | Propósito |
|---------|-----------|
| `api.js` | Cliente HTTP con auto-autenticación y reintentos |
| `http.js` | Instancia Axios con interceptores de auth y error |
| `auth.js` | Estado de sesión, roles, login/logout |
| `backend.js` | Resolución de URL del backend según entorno |
| `imageUtils.js` | Utilidades para fotos de perfil e iniciales |
| `logger.js` | Logging condicional según entorno |

---

## 11. DESPLIEGUE

### 11.1 Múltiples plataformas soportadas

```
Desarrollo:
  npm run dev        → Vite :5173 + PHP :80/Backend

Producción Cloud:
  Dockerfile         → Imagen PHP-Apache para Cloud Run
  app.yaml           → Google App Engine
  netlify.toml       → Netlify (frontend estático)
  firebase.json      → Firebase Hosting
  staticwebapp.config.json → Azure Static Web Apps
  Procfile           → Heroku

Escritorio:
  electron-builder   → Aplicación nativa Windows/Mac/Linux
```

### 11.2 CI/CD

- **Azure Static Web Apps**: GitHub Actions workflow en `.github/workflows/`
- Build automático al hacer push a la rama `Ariadna`

### 11.3 Variables de entorno

| Variable | Desarrollo | Producción |
|----------|-----------|------------|
| `VITE_API_URL` | `/api` | `http://localhost/Backend` |
| `VITE_BACKEND_URL` | `/api` | — |

---

## 12. CONSIDERACIONES TÉCNICAS ADICIONALES

### 12.1 Conexión a base de datos

El archivo `Backend/db.php` mantiene **dos conexiones simultáneas**:
- **mysqli** — para consultas tradicionales
- **PDO** — para consultas preparadas con parámetros

### 12.2 Singleton de auditoría

`AuditHelper.php` implementa un singleton que:
- Agrupa logs en memoria durante la misma petición
- Ventana de deduplicación para evitar registros duplicados
- Inserta en lote al finalizar la petición

### 12.3 Manejo de errores

- Errores de base de datos capturados con try-catch
- Respuestas JSON consistentes: `{ success: bool, message: string, data?: any }`
- Códigos HTTP apropiados (200, 400, 401, 403, 404, 500)

### 12.4 CORS

`Backend/cors.php` y `Backend/config.php` configuran:
- Orígenes permitidos (incluye localhost:5173 y dominios de producción)
- Métodos HTTP permitidos (GET, POST, PUT, DELETE, OPTIONS)
- Cabeceras permitidas (Authorization, X-CSRF-Token, Content-Type)

---

## 13. REQUISITOS DEL SISTEMA

### 13.1 Desarrollo

| Componente | Requisito |
|-----------|-----------|
| Node.js | ^18.0 |
| PHP | ^8.1 |
| MySQL | ^8.0 |
| npm | ^9.0 |

### 13.2 Producción

| Componente | Requisito |
|-----------|-----------|
| Servidor web | Apache 2.4+ o Nginx |
| PHP | ^8.1 con extensiones: mysqli, pdo_mysql, bcrypt, json, mbstring |
| MySQL | ^8.0 con InnoDB |
| RAM recomendada | 512 MB mínimo, 1 GB recomendado |

---

## 14. CONCLUSIONES

El sistema **Club Systems** es una aplicación web completa para la gestión de clubs estudiantiles, construida con una arquitectura moderna (Vue 3 + PHP 8.1) y desplegable en múltiples plataformas cloud. Su diseño modular permite escalar funcionalidades de manera independiente, y su sistema de autenticación basado en tokens con rate limiting y CSRF proporciona un nivel adecuado de seguridad para el contexto institucional.

Los módulos implementados cubren la totalidad del ciclo de vida de un club estudiantil: desde la inscripción de alumnos, pasando por el control de asistencia y evaluaciones, hasta la generación de constancias oficiales y la auditoría de todas las operaciones realizadas.

---

*Documento generado el 24 de junio de 2026*
