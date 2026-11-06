# Portal Académico — Backend (Laravel 12 + PostgreSQL)

Backend del **Examen 3: Consumo de API y Desarrollo de Interfaz Web** (SII ITC).

Este repositorio es el **intermediario seguro** entre el frontend (React + Vite) y la API institucional del SII del ITC. Además guarda en su propia base de datos (PostgreSQL en Supabase) el **directorio de profesores**, que es la funcionalidad adicional del equipo, con su propio backend de administración.

> Este README documenta todo lo construido y sirve de contrato para el frontend: rutas, formatos de respuesta, errores y estructura real de los datos del SII.

---

## URL del backend y resumen de endpoints

**Backend desplegado (Render):** `https://portal-academico-sii.onrender.com`
**Base de la API:** `https://portal-academico-sii.onrender.com/api`
**Local:** `http://localhost:8001/api`

> En el plan gratuito de Render el servicio puede dormirse tras un rato sin tráfico: la primera petición puede tardar. Conviene abrirlo unos minutos antes de una demo.

| Método | Ruta | Token | Para qué sirve |
|--------|------|-------|----------------|
| POST | `/api/auth/login` | No | Login de alumno (valida contra el SII) |
| GET | `/api/auth/me` | Alumno | Confirmar que la sesión sigue activa |
| POST | `/api/auth/logout` | Alumno | Cerrar sesión |
| GET | `/api/academico/perfil` | Alumno | Dashboard (incluye `foto` en base64) |
| GET | `/api/academico/calificaciones` | Alumno | Calificaciones por periodo y materia |
| GET | `/api/academico/kardex` | Alumno | Historial académico completo |
| GET | `/api/academico/horarios` | Alumno | Horario del semestre actual |
| GET | `/api/profesores` | Alumno | Directorio (`?buscar=` y `?clave_materia=`) |
| GET | `/api/profesores/{id}` | Alumno | Detalle de un profesor |
| POST | `/api/admin/login` | No | Login de administrador (5 intentos por minuto) |
| GET | `/api/admin/me` | Admin | Datos del administrador |
| GET | `/api/admin/profesores` | Admin | Lista de profesores (lectura para el panel) |
| GET | `/api/admin/profesores/{id}` | Admin | Detalle de un profesor |
| POST | `/api/admin/profesores` | Admin | Crear profesor |
| PUT | `/api/admin/profesores/{id}` | Admin | Editar profesor |
| DELETE | `/api/admin/profesores/{id}` | Admin | Borrar profesor (y sus horarios y materias) |
| POST | `/api/admin/profesores/{id}/horarios` | Admin | Agregar horario de atención |
| PUT | `/api/admin/horarios/{id}` | Admin | Editar horario |
| DELETE | `/api/admin/horarios/{id}` | Admin | Borrar horario |
| POST | `/api/admin/profesores/{id}/materias` | Admin | Asignar materia |
| PUT | `/api/admin/materias/{id}` | Admin | Editar materia |
| DELETE | `/api/admin/materias/{id}` | Admin | Quitar materia |
| GET | `/up` | No | Health check (lo usa Render) |

Los tokens de alumno y de administrador **no son intercambiables**: cada uno solo sirve en sus rutas.

---

## Índice

1. [Contexto del proyecto](#1-contexto-del-proyecto)
2. [Arquitectura y decisiones de diseño](#2-arquitectura-y-decisiones-de-diseño)
3. [Estado del proyecto](#3-estado-del-proyecto)
4. [Instalación y ejecución local](#4-instalación-y-ejecución-local)
5. [Cómo habla el frontend con este backend](#5-cómo-habla-el-frontend-con-este-backend)
6. [Endpoints](#6-endpoints)
7. [Estructura de las respuestas (datos reales del SII)](#7-estructura-de-las-respuestas-datos-reales-del-sii)
8. [Funcionalidad adicional: directorio de profesores](#8-funcionalidad-adicional-directorio-de-profesores)
9. [Base de datos](#9-base-de-datos)
10. [Seguridad](#10-seguridad)
11. [Probar con curl](#11-probar-con-curl)
12. [Problemas frecuentes](#12-problemas-frecuentes)
13. [Despliegue](#13-despliegue)
14. [Decisiones y pendientes del equipo](#14-decisiones-y-pendientes-del-equipo)
15. [Tecnologías y estructura del código](#15-tecnologías-y-estructura-del-código)
16. [Capturas de pantalla](#16-capturas-de-pantalla)

---

## 1. Contexto del proyecto

### Qué pide el examen

Una aplicación web donde el estudiante pueda:

1. **Autenticarse** con su correo y contraseña institucionales.
2. Ver un **dashboard** con la información de su perfil.
3. Consultar sus **calificaciones** (con búsqueda y filtros, y colores de desempeño).
4. Consultar su **kardex** y su **horario** (secciones independientes).
5. Usar una **funcionalidad adicional** propuesta por el equipo, con su propio backend de administración.

Reglas clave del examen:

- La API del SII es la **única fuente de verdad** para la autenticación y los datos académicos.
- Los puntos 1 al 4 **no se pueden simular**: deben consumir datos reales. Los datos temporales en el frontend solo sirven durante el desarrollo.
- Solo la funcionalidad adicional puede usar datos simulados.
- Entregables: repositorio en GitHub, README con instalación, descripción de la tecnología y capturas, y publicación en Vercel o similar.
- Exposición con demo en vivo, participando todos los integrantes.

### Reparto del equipo

| Rol | Tecnología | Repositorio |
|-----|------------|-------------|
| Backend | Laravel 12 + PostgreSQL (Supabase) | `portal-academico-backend` (este repo) |
| Frontend | React + Vite + React Router + Axios | `portal-academico-frontend` |

---

## 2. Arquitectura y decisiones de diseño

```
Navegador (React)  ──►  Este backend (Laravel)  ──►  API del SII ITC
                                │
                                └──►  PostgreSQL en Supabase (profesores y administradores)
```

**Qué hace el backend con el SII:**

1. **Login:** recibe correo y contraseña del frontend y se los reenvía al SII. Si el SII devuelve un JWT, el alumno entra. **No hay usuarios propios de alumnos**: el que el SII acepta es el que entra.
2. **Sesión:** el JWT del SII se guarda **en el servidor** (cache, 55 minutos) y al frontend se le entrega un **token propio** de 64 caracteres. El frontend nunca ve el JWT del SII.
3. **Consulta en vivo:** con el JWT guardado, cada petición del frontend se traduce en una consulta al SII.
4. **Sin alterar datos:** los datos académicos no se modifican ni se guardan. Solo se envuelven en `{ "ok": true, "data": ... }`.
5. **Errores traducidos:** credenciales incorrectas, campos vacíos, SII caído o sesión expirada se convierten en respuestas claras con su código HTTP.

**Dos mundos de autenticación, separados a propósito:**

| Sesión | Cómo se obtiene | Para qué sirve | Middleware |
|--------|-----------------|----------------|------------|
| **Alumno** | `POST /api/auth/login` (valida contra el SII) | `/api/auth/*`, `/api/academico/*`, `/api/profesores` | `sii.token` |
| **Administrador** | `POST /api/admin/login` (valida contra la tabla `users`) | `/api/admin/*` | `auth:sanctum` |

Un token de alumno **no** sirve en las rutas de administración, ni uno de administrador en las del alumno. Por eso existen rutas de lectura separadas para el admin (`GET /api/admin/profesores`).

---

## 3. Estado del proyecto

| Pieza | Estado |
|-------|--------|
| Proyecto Laravel 12 conectado a Supabase | Listo |
| `SiiApiService` (cliente de la API del SII) | Listo |
| Login, sesión y logout de alumnos | Listo y probado |
| Middleware `sii.token` (protección de rutas) | Listo y probado |
| Perfil, calificaciones, kardex y horarios | Listo y probado |
| Directorio de profesores (lectura para alumnos) | Listo y probado |
| Lectura de profesores para administradores | Listo |
| Login de administrador (Sanctum, con límite de intentos) | Listo y probado |
| CRUD de profesores, horarios de atención y materias | Listo y probado |
| RLS activado en todas las tablas de Supabase | Listo |
| CORS restringido al frontend (`FRONTEND_URL`) | Listo |
| Seeders (5 profesores simulados y administrador) | Listo |
| Despliegue en Render (Docker), verificado con login y rutas de alumno | Listo |
| Pruebas de integración con el frontend desplegado | **Pendiente** |
| Capturas y demo en vivo | **Pendiente** |

---

## 4. Instalación y ejecución local

Requisitos: PHP 8.2 o superior con la extensión `pdo_pgsql`, Composer y Git.

```bash
git clone https://github.com/TU_USUARIO/portal-academico-backend.git
cd portal-academico-backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura el `.env` con los datos de la base de datos (en Supabase: botón *Connect* → **Session pooler**):

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-xxxx.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.xxxxxxxx
DB_PASSWORD=tu_password

SII_API_URL=https://sii.celaya.tecnm.mx/api
FRONTEND_URL=http://localhost:5173

ADMIN_NAME=Administrador
ADMIN_EMAIL=correo_del_admin@ejemplo.com
ADMIN_PASSWORD=una_contraseña_larga
```

Notas sobre el `.env`:

- El **usuario** del Session pooler lleva el identificador del proyecto: `postgres.xxxxxxxx`, no solo `postgres`.
- **No se necesita ninguna llave de API de Supabase**: Laravel se conecta directo a PostgreSQL.
- `FRONTEND_URL` acepta varias URLs separadas por coma y sin espacios.
- El `.env` **nunca se sube** al repositorio; solo `.env.example`, sin secretos.

Luego:

```bash
php artisan migrate
php artisan db:seed --class=ProfesorSeeder
php artisan db:seed --class=AdminSeeder
php artisan serve --port=8001
```

El backend queda en `http://localhost:8001`.

> **Usa el puerto 8001 si tienes Coolify.** Coolify ocupa el 8000 y responde un `404` de nginx con un enlace a `coolify.io/docs`, que **no** es de Laravel.

### Para quien hace el frontend

En el frontend, el `.env` debe llevar:

```
VITE_API_URL=http://localhost:8001/api
```

Para producción, esa línea apunta al backend desplegado:

```
VITE_API_URL=https://portal-academico-sii.onrender.com/api
``` El backend solo acepta peticiones del navegador desde el origen de `FRONTEND_URL` (por defecto `http://localhost:5173`); si el frontend corre en otro puerto, hay que avisar para ajustar esa variable.

---

## 5. Cómo habla el frontend con este backend

### Reglas generales

- Todas las rutas empiezan con `/api`.
- Enviar siempre `Accept: application/json` y, en los `POST` y `PUT`, `Content-Type: application/json`.
- Las rutas privadas llevan `Authorization: Bearer <token>`, donde `<token>` es **solo** el valor de `data.token` (sin comillas ni el JSON completo).
- Formato de respuesta:

```json
{ "ok": true, "message": "opcional", "data": { } }
```

```json
{ "ok": false, "message": "Texto claro para mostrar al usuario" }
```

- Los **errores de validación** (422) usan el formato estándar de Laravel:

```json
{ "message": "The email field is required.", "errors": { "email": ["The email field is required."] } }
```

### Códigos de respuesta

| Código | Cuándo ocurre | Qué debe hacer el frontend |
|--------|---------------|----------------------------|
| 200 / 201 | Todo bien (201 al crear) | Mostrar los datos |
| 401 | Credenciales incorrectas (login) | Mostrar "Credenciales incorrectas" |
| 401 | Token faltante, inválido o expirado (rutas privadas) | **Borrar el token y mandar al login** |
| 404 | Registro no encontrado | Mostrar mensaje de no encontrado |
| 422 | Campos vacíos o inválidos | Mostrar el error del campo |
| 429 | Demasiados intentos (login de administrador) | Pedir esperar un minuto |
| 502 | El SII respondió con un error | "Intenta de nuevo más tarde" |
| 503 | El SII no está disponible | "Servidor del SII no disponible" |

### Duración de la sesión

El JWT del SII dura aproximadamente una hora. El backend guarda la sesión del alumno **55 minutos**. Pasado ese tiempo cualquier ruta privada responde `401` y el frontend debe regresar al login. El token del administrador, de Sanctum, no tiene expiración automática hasta que se cierre la sesión.

### Ejemplo de cliente con Axios (alumno)

```js
import axios from "axios";

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem("token");
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (err) => {
    // Un 401 fuera del login significa sesión expirada
    const esLogin = err.config?.url?.includes("/auth/login");
    if (err.response?.status === 401 && !esLogin) {
      sessionStorage.removeItem("token");
      window.location.href = "/login";
    }
    return Promise.reject(err);
  }
);

export default api;
```

El panel de administración necesita un cliente **aparte** que mande el token de administrador. Si se usa el cliente del alumno en una ruta de admin, o al revés, la respuesta será `401`.

---

## 6. Endpoints

### Autenticación de alumnos

| Método | Ruta | Token | Descripción |
|--------|------|-------|-------------|
| POST | `/api/auth/login` | No | Inicia sesión contra el SII |
| GET | `/api/auth/me` | Alumno | Confirma que la sesión sigue activa |
| POST | `/api/auth/logout` | Alumno | Cierra la sesión (el token deja de servir) |

**Login, petición:**

```json
{ "email": "l20000000@celaya.tecnm.mx", "password": "********" }
```

**Login, respuesta 200:**

```json
{ "ok": true, "message": "Sesión iniciada", "data": { "token": "cadena de 64 caracteres" } }
```

**Login, respuesta 401:** `{ "ok": false, "message": "Credenciales incorrectas" }`
**Me, respuesta 200:** `{ "ok": true, "message": "Sesión activa" }` (no devuelve datos del alumno; esos salen de `/academico/perfil`)
**Logout, respuesta 200:** `{ "ok": true, "message": "Sesión cerrada" }`

### Información académica (token de alumno)

| Método | Ruta | Sección |
|--------|------|---------|
| GET | `/api/academico/perfil` | Dashboard |
| GET | `/api/academico/calificaciones` | Calificaciones |
| GET | `/api/academico/kardex` | Kardex |
| GET | `/api/academico/horarios` | Horario |

Consultan el SII **en vivo** y devuelven sus datos **sin modificarlos**, dentro de `{ "ok": true, "data": ... }`. Por eso las particularidades del SII (sección 7) llegan tal cual al frontend.

### Directorio de profesores (token de alumno)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/api/profesores` | Lista con horarios de atención y materias |
| GET | `/api/profesores?buscar=laura` | Filtra por nombre o correo (sin distinguir mayúsculas) |
| GET | `/api/profesores?clave_materia=IDD-2502` | Profesores que dan esa materia |
| GET | `/api/profesores/{id}` | Detalle de un profesor (`404` si no existe) |

### Administración (token de administrador)

Primero se inicia sesión en `POST /api/admin/login` con el correo y la contraseña del administrador (máximo 5 intentos por minuto). Devuelve `{ "ok": true, "data": { "token": "..." } }`, que se manda como `Authorization: Bearer <token>`.

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/api/admin/login` | Login del administrador |
| GET | `/api/admin/me` | Datos del administrador (`name`, `email`) |
| GET | `/api/admin/profesores` | Lista de profesores (misma respuesta que la del alumno) |
| GET | `/api/admin/profesores/{id}` | Detalle de un profesor |
| POST | `/api/admin/profesores` | Crear profesor |
| PUT | `/api/admin/profesores/{id}` | Editar profesor (se pueden mandar solo los campos que cambian) |
| DELETE | `/api/admin/profesores/{id}` | Borrar profesor (se borran también sus horarios y materias) |
| POST | `/api/admin/profesores/{id}/horarios` | Agregar horario de atención |
| PUT | `/api/admin/horarios/{id}` | Editar horario (se mandan los tres campos) |
| DELETE | `/api/admin/horarios/{id}` | Borrar horario |
| POST | `/api/admin/profesores/{id}/materias` | Asignar materia |
| PUT | `/api/admin/materias/{id}` | Editar materia asignada |
| DELETE | `/api/admin/materias/{id}` | Quitar materia |

**Campos y validaciones:**

| Recurso | Campos | Reglas |
|---------|--------|--------|
| Profesor | `nombre`, `correo`, `cubiculo`, `departamento` | `nombre` obligatorio (máx. 255); `correo` con formato válido y único; el resto opcional |
| Horario | `dia`, `hora_inicio`, `hora_fin` | `dia` solo `lunes`, `martes`, `miercoles`, `jueves`, `viernes` o `sabado` (sin acentos); horas en formato `HH:MM`; `hora_fin` posterior a `hora_inicio` |
| Materia | `clave_materia`, `nombre_materia`, `clave_periodo`, `letra_grupo` | `clave_materia` obligatoria (máx. 50); el resto opcional |

Un dato inválido responde `422`; un id que no existe, `404`; crear responde `201`.

---

## 7. Estructura de las respuestas (datos reales del SII)

Los ejemplos usan **datos inventados**, pero la estructura es la real.

### 7.1 Perfil: `GET /api/academico/perfil`

```json
{
  "ok": true,
  "data": {
    "numero_control": "20000000",
    "persona": "NOMBRE DE EJEMPLO",
    "email": "l20000000@celaya.tecnm.mx",
    "semestre": 12,
    "num_mat_rep_no_acreditadas": "2",
    "creditos_acumulados": "216",
    "promedio_ponderado": "82.95",
    "promedio_aritmetico": "64.67",
    "materias_cursadas": "60",
    "materias_reprobadas": "13",
    "materias_aprobadas": "47",
    "creditos_complementarios": 0,
    "porcentaje_avance": 83,
    "num_materias_rep_primera": 1,
    "num_materias_rep_segunda": null,
    "percentaje_avance_cursando": 54,
    "foto": "<imagen en base64>"
  }
}
```

Particularidades:

- Muchos números llegan **como texto** (`"216"`, `"82.95"`) y otros como número. Conviene convertirlos con `Number(...)` antes de usarlos en gráficas.
- `percentaje_avance_cursando` se escribe así (con "percentaje"). Hay que usar ese nombre exacto.
- `num_materias_rep_segunda` puede ser `null`.
- La `foto` es una imagen en **base64** y pesa bastante. Hay que revisar si el texto ya trae el prefijo `data:image/...;base64,`: si lo trae, se usa directo en `<img src={foto} />`; si no, se arma `data:image/jpeg;base64,${foto}`. Conviene mostrar un placeholder mientras carga.

### 7.2 Calificaciones: `GET /api/academico/calificaciones`

```json
{
  "ok": true,
  "data": [
    {
      "periodo": {
        "clave_periodo": "26/2",
        "anio": 2026,
        "descripcion_periodo": "Agosto - Diciembre 2026"
      },
      "materias": [
        {
          "materia": {
            "id_grupo": 55820,
            "nombre_materia": "CIBERSEGURIDAD",
            "clave_materia": "IDD-2502",
            "letra_grupo": "A"
          },
          "calificaiones": [
            { "id_calificacion": 1, "numero_calificacion": 1, "calificacion": "82" },
            { "id_calificacion": 2, "numero_calificacion": 2, "calificacion": "77" },
            { "id_calificacion": 3, "numero_calificacion": 3, "calificacion": null },
            { "id_calificacion": 4, "numero_calificacion": 4, "calificacion": null }
          ]
        }
      ]
    }
  ]
}
```

Particularidades:

- `data` es un **arreglo de periodos**; cada periodo trae un arreglo de `materias`.
- **La llave de las notas se llama `calificaiones`** (sin la "c"). Es un error de escritura del SII y el backend la pasa tal cual, sin corregirla. **Hay que escribirla exactamente así** en el frontend.
- Cada materia tiene **4 unidades**. La `calificacion` es **texto** (`"82"`) o `null` si todavía no está capturada.
- **No existe una calificación final.** El examen pide mostrarla, así que hay que acordar cómo calcularla (por ejemplo, promedio de las unidades capturadas) y explicarlo en la exposición.
- No trae el nombre del profesor.

### 7.3 Kardex: `GET /api/academico/kardex`

```json
{
  "ok": true,
  "data": {
    "porcentaje_avance": 83.08,
    "kardex": [
      {
        "nombre_materia": "Cálculo Diferencial",
        "clave_materia": "ACF-2301",
        "periodo": "21/1",
        "creditos": "5",
        "calificacion": "70",
        "descripcion": "NORMAL / ORDINARIO",
        "semestre": 1
      }
    ]
  }
}
```

Particularidades:

- Aquí `data` es un **objeto** (no un arreglo) con `porcentaje_avance` (número) y la lista `kardex`.
- `calificacion` puede ser `"0"` (no acreditada), `"AC"` (actividades complementarias) o un número como texto.
- `descripcion` indica cómo se cursó: `NORMAL / ORDINARIO`, `REPETICIÓN` o `CURSO ESPECIAL`.
- **Una misma materia puede aparecer varias veces** (reprobada y luego repetida). El kardex es un historial: no conviene deduplicar sin pensarlo.
- Algunos periodos son de verano, como `"24/V"`.
- `creditos` llega como texto.
- Se recomienda agrupar por `semestre` para presentarlo de forma estructurada.

### 7.4 Horarios: `GET /api/academico/horarios`

```json
{
  "ok": true,
  "data": [
    {
      "periodo": { "clave_periodo": "26/2", "anio": 2026, "descripcion_periodo": "Agosto - Diciembre 2026" },
      "horario": [
        {
          "id_grupo": 55820,
          "letra_grupo": "A",
          "nombre_materia": "CIBERSEGURIDAD",
          "clave_materia": "IDD-2502",
          "clave_turno": "C.Celaya",
          "nombre_plan": "Ingeniería en Sistemas Computacionales",
          "letra_nivel": "L",
          "lunes": "12:00-13:00",
          "lunes_clave_salon": "LRC",
          "martes": "11:00-13:00",
          "martes_clave_salon": "LRC",
          "miercoles": null,
          "miercoles_clave_salon": null,
          "jueves": "11:00-13:00",
          "jueves_clave_salon": "LRC",
          "viernes": null,
          "viernes_clave_salon": null,
          "sabado": null,
          "sabado_clave_salon": null
        }
      ]
    }
  ]
}
```

Particularidades:

- Cada clase trae **un campo por día** (`lunes`, `martes`, `miercoles`, `jueves`, `viernes`, `sabado`, sin acentos) con un texto `"HH:MM-HH:MM"` o `null`. El salón va en `<día>_clave_salon`.
- Para armar una tabla por día y hora hay que **separar el texto** en hora de inicio y fin. Una clase de `"11:00-13:00"` ocupa dos bloques de una hora.
- Puede haber materias **sin ningún horario** (todos los días en `null`, por ejemplo un grupo virtual). La interfaz debe contemplarlo.
- No trae el nombre del profesor.

### 7.5 Errores del SII

Con un token inválido o vencido, el SII responde `401` con `{ "error": "Token is invalid or expired" }`. El backend lo traduce a su propio formato (`{ "ok": false, "message": "La sesión expiró, inicia sesión de nuevo" }`), por lo que el frontend nunca recibe el formato del SII.

---

## 8. Funcionalidad adicional: directorio de profesores

### Propuesta

**Problema.** El SII muestra al alumno sus materias, grupos y horarios, pero no le dice quién imparte cada materia, dónde encontrarlo ni cuándo atiende. Para una asesoría, el alumno termina preguntando por pasillos o grupos de mensajería.

**Solución.** Un directorio de profesores que muestra, para cada docente, su correo, su cubículo, sus horarios de atención y las materias que imparte. Se puede buscar por nombre o por correo y filtrar por materia, para llegar directo al profesor de una materia que el alumno está cursando.

**Beneficios concretos para los estudiantes:**

- Encontrar a un profesor y su horario de atención sin depender de terceros.
- Ahorrar tiempo: del horario de una materia se pasa directo a quién la imparte.
- Contactar al docente por correo con el dato correcto.

**Administración.** Un administrador inicia sesión en un backend propio, separado del SII, y da de alta, edita y borra profesores, sus horarios de atención y las materias que imparten, de modo que la información se mantenga actualizada sin tocar la base de datos.

**Datos.** El SII no trae información de docentes en ninguno de sus cinco endpoints, así que los datos son **simulados**, lo cual el examen permite para esta funcionalidad. Los profesores se ligan a las materias reales mediante `clave_materia`, la misma clave que usa el SII.

### Datos de ejemplo del seeder

El `ProfesorSeeder` carga 5 profesores simulados, uno por cada materia del periodo actual (nombres, correos y cubículos inventados). Se puede ejecutar varias veces sin duplicar: busca por correo y reemplaza sus horarios y materias.

| Profesor | Materia (`clave_materia`) |
|----------|---------------------------|
| Dra. Laura Ramírez Soto | CIBERSEGURIDAD (`IDD-2502`) |
| M.C. Carlos Hernández Vega | TÓPICOS AVANZADOS DE DESARROLLO WEB (`IDD-2504`) |
| Ing. Marisol Torres Cruz | Administración de Redes (`SCA-1002`) |
| Dr. Jorge Mendoza Ibarra | Simulación (`SCD-1022`) |
| M.T.I. Ana Beatriz Salazar Rueda | DESARROLLOS MÓVILES Y TECNOLOGÍAS EN LA NUBE (`IDD-2503`) |

### Respuesta de `GET /api/profesores`

```json
{
  "ok": true,
  "data": [
    {
      "id": 1,
      "nombre": "Dra. Laura Ramírez Soto",
      "correo": "laura.ramirez@celaya.tecnm.mx",
      "cubiculo": "Edificio K, cubículo 3",
      "departamento": "Sistemas y Computación",
      "created_at": "2026-11-05T06:22:08.000000Z",
      "updated_at": "2026-11-05T06:22:08.000000Z",
      "horarios": [
        { "id": 1, "profesor_id": 1, "dia": "lunes", "hora_inicio": "10:00:00", "hora_fin": "12:00:00" }
      ],
      "materias": [
        { "id": 1, "profesor_id": 1, "clave_materia": "IDD-2502", "nombre_materia": "CIBERSEGURIDAD", "clave_periodo": null, "letra_grupo": null }
      ]
    }
  ]
}
```

- Los días usan el mismo formato que el SII (sin acentos) y las horas llegan como `"HH:MM:SS"`.
- `clave_periodo` y `letra_grupo` en `null` significan que el profesor aplica a **cualquier periodo o grupo** de esa materia.
- `GET /api/profesores/{id}` tiene la misma forma, con `data` como un solo objeto.
- Los **ids de horarios y materias cambian** cada vez que se vuelve a correr el seeder (borra y recrea). No conviene guardarlos fuera de la base.

---

## 9. Base de datos

Las tablas se crean con **migraciones** de Laravel (no a mano), de modo que cualquiera que clone el repo obtiene la misma estructura con `php artisan migrate`.

| Tabla | Para qué sirve |
|-------|----------------|
| `users` | Administradores del directorio (Laravel trae esta tabla por defecto) |
| `personal_access_tokens` | Tokens de Sanctum para el administrador |
| `cache` y `cache_locks` | Aquí se guarda la sesión del alumno (token propio → JWT del SII) |
| `sessions`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `migrations` | Tablas por defecto de Laravel |
| `profesores` | `nombre`, `correo` (único), `cubiculo`, `departamento` |
| `horarios_atencion` | `profesor_id`, `dia`, `hora_inicio`, `hora_fin` |
| `materias_profesores` | `profesor_id`, `clave_materia` (indexada), `nombre_materia`, `clave_periodo`, `letra_grupo` |

- `horarios_atencion` y `materias_profesores` se borran en cascada al borrar un profesor.
- **No se guardan calificaciones, kardex ni horarios del SII**: siempre se consultan en vivo.
- Las migraciones por defecto de Laravel no se tocan.

---

## 10. Seguridad

- **El JWT del SII nunca llega al navegador.** Se guarda en el servidor y el frontend solo maneja un token propio que no sirve fuera de este backend.
- **No se guardan contraseñas de alumnos:** solo se reenvían al SII.
- **El `.env` no se sube al repositorio.** Las credenciales del administrador se leen del `.env` en el seeder, nunca del código.
- **RLS activado en todas las tablas de Supabase.** Supabase expone sus tablas por una API pública; con RLS y sin políticas, nadie puede leerlas ni escribirlas por ahí. Laravel no se ve afectado porque entra con el usuario `postgres` del pooler.
- **Login de administrador con límite** de 5 intentos por minuto.
- **CORS restringido** al origen de `FRONTEND_URL`.
- Rutas privadas devuelven `401` sin sesión válida, y los dos tipos de token no se pueden intercambiar.
- Al compartir capturas o pedir ayuda, **no se debe mostrar ningún token completo** ni datos personales.

---

## 11. Probar con curl

Todos los ejemplos asumen el backend en `http://localhost:8001`. Las variables solo viven en la terminal donde se definieron; si abres otra terminal, hay que repetir el login.

**Login de alumno y guardar el token (la contraseña no se muestra ni queda en el historial):**

```bash
read -p "Correo: " EMAIL
read -s -p "Password: " PASS; echo
TOKEN=$(curl -s -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"$EMAIL\",\"password\":\"$PASS\"}" \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
echo ${#TOKEN}   # debe ser mayor que 0
```

**Consultar los datos del alumno:**

```bash
curl -s http://localhost:8001/api/academico/calificaciones \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" | python3 -m json.tool

curl -s http://localhost:8001/api/academico/kardex \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN" -o kardex.json

curl -s http://localhost:8001/api/profesores \
  -H "Accept: application/json" -H "Authorization: Bearer $TOKEN"
```

Si `| python3 -m json.tool` abre el intérprete de Python (`>>>`), se sale con `exit()` o `Ctrl+D`.

**Login de administrador y crear un profesor:**

```bash
read -p "Correo admin: " AEMAIL
read -s -p "Password admin: " APASS; echo
ATOKEN=$(curl -s -X POST http://localhost:8001/api/admin/login \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d "{\"email\":\"$AEMAIL\",\"password\":\"$APASS\"}" \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

curl -s -X POST http://localhost:8001/api/admin/profesores \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -H "Authorization: Bearer $ATOKEN" \
  -d '{"nombre":"Prof. de Prueba","correo":"prueba@celaya.tecnm.mx","cubiculo":"K-9"}'
```

**Resultados esperados** (verificados durante el desarrollo):

| Prueba | Código |
|--------|--------|
| Login de alumno con contraseña mala | 401 |
| Login con campos vacíos | 422 |
| Cualquier ruta privada sin token | 401 |
| Logout y luego usar el mismo token | 401 |
| Perfil, calificaciones, kardex, horarios y profesores con token de alumno | 200 |
| Login de administrador con contraseña mala | 401 |
| Crear, editar y borrar profesor, horario y materia (con token de admin) | 201, 200, 200 |
| Borrar un registro que ya no existe | 404 |
| Horario con horas al revés, día inválido o materia sin clave | 422 |
| Token de alumno en una ruta de admin | 401 |

---

## 12. Problemas frecuentes

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| `404` con `Server: nginx` y un enlace a `coolify.io/docs` | Coolify ocupa el puerto 8000 | Usar `php artisan serve --port=8001` |
| `401` con `Unauthenticated.` en una ruta de admin | Token ausente o no es de administrador | Hacer login en `/api/admin/login` y usar ese token |
| `401` con `Sesión no válida o expirada` | Token de alumno ausente, vencido (más de 55 min) o ruta del grupo de alumnos con token de admin | Volver a iniciar sesión; revisar qué token manda el cliente |
| La variable `$TOKEN` o `$ATOKEN` está vacía | Terminal nueva, o login fallido (por ejemplo, correo del SII en el login de admin) | `echo ${#TOKEN}`; repetir el login con las credenciales correctas |
| `422` al crear un horario | Día con acento o inválido, o `hora_fin` anterior a `hora_inicio` | Usar `miercoles` y `sabado` sin acento; horas `HH:MM` |
| El frontend lee `undefined` en calificaciones | Busca `calificaciones` y el SII manda `calificaiones` | Usar la llave sin la "c" |
| El dashboard de admin muestra 0 profesores | Usa el endpoint del alumno con token de admin | Consumir `GET /api/admin/profesores` |
| Error de CORS en el navegador | `FRONTEND_URL` no coincide con el origen del frontend | Ajustar la variable: `https://` completo, sin barra al final, sin espacios entre URLs |
| `Tenant or user not found` al conectar a Supabase | `DB_USERNAME` sin el identificador del proyecto | Usar `postgres.xxxxxxxx` del Session pooler |
| `/api/profesores` da `500` | Falla de conexión a la base | `tail -n 30 storage/logs/laravel.log` |
| El build en Render falla con `requires php >=8.4.1` | El `Dockerfile` usa una versión de PHP menor | Usar `FROM php:8.4-apache` |
| Solo el login real y las rutas que usan la base dan `500`; `/up` y el login con correo falso funcionan | Render no se conecta a Supabase (por ejemplo `password authentication failed`) | Volver a pegar `DB_PASSWORD` sin comillas ni espacios, o resetear la contraseña a una de letras y números y actualizarla en Render y en local |
| Un `500` pelado sin ningún detalle ni log | Falta `LOG_CHANNEL=stderr`, o no existe `storage/logs` en la imagen | Agregar la variable y mantener el `mkdir -p storage/...` del `Dockerfile` |
| La raíz `/` da `500` | La API no usa la vista de bienvenida de Laravel, y esa ruta web también usa la base | No afecta a la API; usar `/up` y `/api/...` para probar |
| `curl` da `código: 000` | `$URL` vacía en una terminal nueva, o el servicio todavía despliega | Definir `URL=...` y revisar `/up` |

---

## 13. Despliegue

El backend está desplegado en **Render** como Web Service con runtime **Docker** (Render no trae PHP nativo). La base de datos es la misma de Supabase.

### Archivos de despliegue

`Dockerfile` (PHP 8.4 con Apache y `pdo_pgsql`; las dependencias del proyecto exigen PHP 8.4 o superior):

```dockerfile
FROM php:8.4-apache

RUN apt-get update && apt-get install -y libpq-dev libzip-dev unzip git \
    && docker-php-ext-install pdo_pgsql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
```

`docker/start.sh` (Render entrega el puerto en la variable `PORT`):

```sh
#!/bin/sh
set -e
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
```

`.dockerignore`:

```
.env
.git
vendor
node_modules
storage/logs/*
```

### Pasos en Render

1. **New → Web Service**, conectar el repositorio de GitHub y elegir la rama `main`.
2. **Language:** Docker.
3. **Health Check Path:** `/up`.
4. Cargar las variables de entorno (tabla de abajo) y crear el servicio.
5. No se corren `migrate` ni los seeders en el despliegue: la base de Supabase ya tiene las tablas y los datos. Además, `ProfesorSeeder` borra y recrea horarios y materias, por lo que ejecutarlo en cada deploy borraría las ediciones del administrador.

### Variables de entorno

| Variable | Local | Render |
|----------|-------|--------|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` | **`false`** |
| `APP_KEY` | la del proyecto | una nueva: `php artisan key:generate --show` (con el prefijo `base64:`) |
| `APP_URL` | `http://localhost:8001` | `https://portal-academico-sii.onrender.com` |
| `LOG_CHANNEL` | `stack` | `stderr` (para ver los errores en *Logs*) |
| `CACHE_STORE` | `database` | `database` (ahí vive la sesión del alumno) |
| `DB_CONNECTION` | `pgsql` | `pgsql` |
| `DB_HOST` | host del Session pooler | el mismo |
| `DB_PORT` | `5432` | `5432` |
| `DB_DATABASE` | `postgres` | `postgres` |
| `DB_USERNAME` | `postgres.xxxxxxxx` | el mismo |
| `DB_PASSWORD` | contraseña de la base | la misma, **sin comillas ni espacios** |
| `SII_API_URL` | `https://sii.celaya.tecnm.mx/api` | la misma |
| `FRONTEND_URL` | `http://localhost:5173` | URL del frontend en Vercel (varias separadas por coma) |

`ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD` solo las usa el `AdminSeeder` (ya ejecutado), así que no hacen falta en Render.

### Verificación del despliegue

```bash
URL=https://portal-academico-sii.onrender.com
curl -s -o /dev/null -w "up: %{http_code}\n" $URL/up
```

Con login y rutas de alumno, y con login y CRUD de administrador, todo debe dar `200` o `201`, y `401` si se cruzan los tokens. Ver la sección 11 para los comandos.

### Al publicar el frontend

Poner la URL exacta del frontend en `FRONTEND_URL` (con `https://`, sin barra al final; varias URLs separadas por coma y sin espacios). Render reinicia el servicio al guardar. Sin este paso, el navegador del frontend publicado no podrá llamar al backend.

---

## 14. Decisiones y pendientes del equipo

**Decisiones tomadas:**

- Los profesores son datos **simulados**, ligados a las materias por `clave_materia`.
- Los datos académicos se entregan **sin modificar**, incluida la llave `calificaiones` con el error de escritura del SII.
- La **foto** del perfil viaja dentro de `/academico/perfil` (en base64).
- Los **números como texto** se dejan como llegan del SII; el frontend los convierte.
- La sesión del alumno vive en el servidor (cache, 55 minutos) y la del administrador usa Sanctum.

**Pendientes:**

1. **Calificación final.** El SII solo da 4 unidades por materia y el examen pide una calificación final: hay que acordar la fórmula (por ejemplo, promedio de las unidades capturadas) y explicarla.
2. **Despliegue del frontend** y poner su URL en `FRONTEND_URL` del backend.
3. **Pruebas de integración** con el frontend desplegado.
4. **Capturas** de pantalla para este README.
5. **Exposición** con demo en vivo, participando todos los integrantes.
6. Opcional: validar un rol de administrador en las rutas de Sanctum. Hoy `auth:sanctum` acepta cualquier token de la tabla `users`, que solo contiene administradores.

---

## 15. Tecnologías y estructura del código

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 12 (PHP 8.2+), Laravel Sanctum |
| Base de datos | PostgreSQL en Supabase (Session pooler) |
| API institucional | SII ITC, `https://sii.celaya.tecnm.mx/api` |
| Frontend | React + Vite, React Router, Axios |
| Despliegue | Frontend en Vercel; backend en un servidor compatible con Laravel |

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php          # login, me y logout de alumnos
│   │   ├── AcademicoController.php     # perfil, calificaciones, kardex y horarios
│   │   ├── ProfesorController.php      # directorio de profesores (lectura)
│   │   ├── AdminAuthController.php     # login y datos del administrador
│   │   ├── AdminProfesorController.php # crear, editar y borrar profesores
│   │   ├── AdminHorarioController.php  # horarios de atención
│   │   └── AdminMateriaController.php  # materias de cada profesor
│   └── Middleware/SiiToken.php         # protege las rutas del alumno
├── Models/                             # Profesor, HorarioAtencion, MateriaProfesor, User
└── Services/SiiApiService.php          # cliente de la API del SII
bootstrap/app.php                       # alias del middleware sii.token
config/
├── cors.php                            # orígenes permitidos (FRONTEND_URL)
└── services.php                        # URL del SII (SII_API_URL)
database/
├── migrations/                         # tablas por defecto y directorio de profesores
└── seeders/                            # ProfesorSeeder y AdminSeeder
routes/api.php                          # todas las rutas de la API
Dockerfile, docker/start.sh, .dockerignore  # despliegue en Render
```

**Cómo funciona la sesión del alumno, paso a paso:**

1. `AuthController::login` valida los campos y llama a `SiiApiService::login`.
2. Si el SII devuelve un JWT, se genera un token aleatorio de 64 caracteres y se guarda en cache la relación `sii_token:<hash del token propio>` → JWT del SII.
3. El middleware `SiiToken` lee el `Bearer` de cada petición, busca el JWT en cache y, si no existe, responde `401`.
4. `AcademicoController` usa ese JWT para consultar el SII y traduce los errores: `503` si el SII no responde, `401` si el SII rechaza el token (y borra la sesión) y `502` ante cualquier otro error.

---

## 16. Capturas de pantalla

_Pendiente: se agregarán cuando el sistema esté integrado y desplegado._