# Requirements — User Form Views

**Feature:** `05-user-form-views`
**Scope:** Frontend only — Vue UI sin conexión a backend (no FormRequests, Actions ni Wrappers nuevos)
**Depends on:** ninguno (UI standalone)

---

## Objetivo

Crear la página `/security/users/create` (y preparar la base para `/security/users/{user}/edit`) con el formulario completo de creación/edición de usuarios del sistema CACAO, siguiendo el diseño aprobado en `cacao/project/CACAO Usuario - Crear.html`.

---

## Funcionalidades requeridas

### Step 0 — Role Picker
- El usuario debe elegir un rol antes de ver el formulario
- 4 cards: Administrador, Estudiante, Profesor, Representante
- Cada card muestra ícono del rol, descripción y cantidad de secciones del formulario
- Single-click selecciona, double-click o botón "Continuar" avanza
- Botón "Cancelar" vuelve al índice de usuarios
- Se puede cambiar el rol desde el formulario con el pill "cambiar" en el header

### Formulario por tabs
- Tabs horizontales en la parte superior, numerados (01, 02, …)
- Los tabs y sus secciones varían según el rol elegido:
  - Admin (3 tabs): Identidad · Salud · Documentos
  - Estudiante (6 tabs): Identidad · Salud · Académico · Familia · Socioeconómico · Documentos
  - Profesor (4 tabs): Identidad · Salud · Profesional · Documentos
  - Representante (4 tabs): Identidad · Salud · Representante · Documentos
- El tab muestra indicador visual si está completo o tiene campos sin terminar

### Sidebar de navegación
- Lista de las secciones del tab activo con dot de estado (vacía / parcial / completa)
- Click en sección hace scroll suave a la sección
- Scroll spy actualiza cuál sección está activa

### Secciones del formulario (17 en total)
- Cada sección es una card con: número, título, subtítulo, etiqueta de rol si aplica
- Footer de sección: texto de estado + botón "Guardar sección"
- Modo creación: sección editable por defecto, "Guardar sección" marca como guardada
- Estado visual: vacía (default) → parcial (algunos campos) → completa (campos requeridos llenos) → editando
- Bloques repetibles (direcciones, idiomas, documentos, beneficios, representantes): cards apiladas con botón "+ Agregar"
- Bloques condicionales: se revelan al activar un toggle (indígena, discapacidad, remesas, etc.)

### Header del formulario
- Título "Nuevo usuario" + tag "MODO CREACIÓN"
- Pill del rol con botón "cambiar"
- Barra de progreso con porcentaje completado
- Indicador de autosave (Sin cambios / Guardando… / Guardado hace Xs)

### Footer sticky
- Izquierda: texto de progreso (`X% completo · N de M secciones`) + autosave
- Derecha: Descartar · Guardar borrador · Crear usuario (disabled hasta 50%)

### Estado reactivo local
- Toda la data del formulario vive en `useUserForm.ts` como `reactive<UserFormData>({})`
- No se envía al servidor (UI prototype)
- Autosave es simulado: al cambiar cualquier dato → "Guardando…" por 600ms → "Guardado hace Xs"

---

## Secciones del formulario

| # | Título | Roles | Tipo |
|---|--------|-------|------|
| 1 | Identidad personal | Todos | Campos simples + avatar upload |
| 2 | Credenciales de acceso | Todos | Email + password con strength meter |
| 3 | Dirección | Todos | Repetible |
| 4 | Perfil demográfico | Todos | Campos + condicionales (indígena, migrante, deporte) |
| 5 | Salud | Todos | IMC calculado, discapacidad condicional, seguro, emergencia |
| 6 | Consentimientos | Todos | Checkboxes con versión de política |
| 7 | Documentos adjuntos | Todos | Repetible con file zone |
| 8 | Perfil académico | Estudiante | Código, estatus, modalidad, GPA |
| 9 | Antecedentes educativos | Estudiante | Institución previa, historial, educación familiar |
| 10 | Idiomas | Estudiante | Repetible |
| 11 | Perfil familiar | Estudiante | Composición del hogar |
| 12 | Perfil socioeconómico | Estudiante | Ingresos, remesas, empleo, becas |
| 13 | Beneficios institucionales | Estudiante | Repetible |
| 14 | Vivienda | Estudiante | Hacinamiento calculado, servicios checkboxes |
| 15 | Representantes | Estudiante | Repetible con búsqueda de usuario existente |
| 16 | Perfil del representante | Representante | Datos laborales |
| 17 | Perfil del personal | Profesor | Contrato, dedicación, coordinación condicional |

---

## Primitivos UI nuevos requeridos

| Componente | Descripción |
|---|---|
| `AppFormField` | Wrapper: label + required/optional/adminOnly + help/error/ok |
| `AppDocInput` | Selector de tipo de documento + número |
| `AppTelInput` | Código de país + número de teléfono |
| `AppPasswordInput` | Input contraseña con botón mostrar/ocultar |
| `AppPasswordStrength` | Barra de fortaleza de contraseña (5 niveles) |
| `AppToggle` | Switch on/off con label y sub opcional |
| `AppToggleCard` | Toggle como card con texto descriptivo |
| `AppPillRadios` | Grupo de radios como pills horizontales |
| `AppRepeatable` | Contenedor de items repetibles con add/remove |
| `AppFileZone` | Zona drag-drop para un archivo |
| `AppAvatarUpload` | Uploader de foto de perfil con iniciales |

---

## Fuera de alcance en este arnés

- Conectar el formulario al backend (submit real, validación server-side)
- Vista de edición `/security/users/{user}/edit`
- Tests Pest (se escriben cuando se conecte el backend)
- Validación inline en tiempo real (se agrega al conectar backend)
