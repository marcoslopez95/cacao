# CACAO — Contexto del proyecto para Claude Code

> Este archivo es la fuente de verdad para cualquier agente o desarrollador que trabaje en este proyecto.
> Léelo completo antes de escribir cualquier línea de código.

---

## ¿Qué es CACAO?

**CACAO** = Control Académico, Cursos y Administración de Operaciones.

Sistema de gestión académica integral para instituciones educativas venezolanas (todos los niveles: primaria, secundaria, universitario). Nombre con identidad venezolana — el cacao venezolano es reconocido mundialmente como símbolo de calidad.

---

## Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2+ · Laravel 11 |
| Frontend | React o Vue con Inertia.js (por definir) |
| Base de datos | PostgreSQL |
| Autenticación | Laravel Sanctum |
| Package manager | pnpm |
| CSS | Tailwind CSS v3 con tokens personalizados |
| Tipografía | Space Grotesk (Google Fonts) |

---

## Arquitectura general

- **Patrón**: Modular monolito Laravel con separación por dominios
- **API**: REST interna consumida por Inertia.js (no API pública por ahora)
- **Multi-nivel educativo**: el sistema soporta tanto educación básica/secundaria como universitaria
- **Representantes**: los estudiantes de nivel no universitario pueden tener representantes legales con acceso al sistema

---

## Módulos del sistema

### 1. Académico
- Categorías de carrera (Ingeniería, Humanidades, Economía, etc.)
- Carreras → Pensums (una carrera puede tener N pensums, uno activo)
- Materias → pertenecen a un pensum, gestionadas por un departamento
- Prelaciones: auto-referencial en materia (para ver B debes haber aprobado A)
- Secciones: cada materia tiene N secciones con cupo máximo

### 2. Personas
- **Estudiantes**: datos personales + geográficos + socioeconómicos + educativos
- **Profesores**: adscritos a un departamento, pueden dar N materias
- **Representantes**: vinculados a estudiantes (para nivel no universitario)
- **Usuarios**: modelo único de auth con rol (admin, profesor, estudiante, representante)

### 3. Inscripciones
- Validación automática de prelaciones antes de aprobar
- Control de cupos por sección
- Estados: pendiente → aprobada / rechazada
- La inscripción es la entidad pivote central del sistema

### 4. Infraestructura
- Aulas: tipo (teórica / laboratorio), capacidad, edificio
- Una sección puede tener aula teórica + aula laboratorio (espacios físicos distintos)
- Horarios por sección: día, hora inicio, hora fin, tipo (teoría/lab)
- Departamentos: agrupan profesores por área

### 5. Evaluaciones y notas
- Actividades: quiz, test (opción múltiple con corrección automática), entrega de archivo
- Las entregas de archivo son revisadas y calificadas por el profesor con comentario
- Asistencia: cargada manualmente por el profesor por sección
- Notas finales: cargadas por el profesor con observación opcional

### 6. Recursos y comunicación
- Materiales de apoyo por sección (profesor sube, estudiante descarga)
- Evaluación de profesores por parte de los estudiantes (por período)

---

## Modelo de datos — entidades principales

```
CATEGORIA_CARRERA → CARRERA → PENSUM → MATERIA
MATERIA → PRELACION (auto-referencial)
MATERIA → SECCION → HORARIO
SECCION → AULA (teoría) + AULA (laboratorio, nullable)
DEPARTAMENTO → PROFESOR → SECCION
DEPARTAMENTO → MATERIA

ESTUDIANTE → DATOS_GEOGRAFICOS (1:1)
ESTUDIANTE → DATOS_SOCIOECONOMICOS (1:1)
ESTUDIANTE → DATOS_EDUCATIVOS (1:1)
ESTUDIANTE → REPRESENTANTE (1:N, para nivel no universitario)

ESTUDIANTE + SECCION → INSCRIPCION (pivote central)
INSCRIPCION → NOTA
INSCRIPCION → ASISTENCIA
INSCRIPCION → ENTREGA_ESTUDIANTE

SECCION → EVALUACION_ACTIVIDAD → PREGUNTA_QUIZ → OPCION_RESPUESTA
ENTREGA_ESTUDIANTE → RESPUESTA_ESTUDIANTE

SECCION → MATERIAL_APOYO
PROFESOR → EVALUACION_PROFESOR (emitida por estudiante)

USER → PROFESOR | ESTUDIANTE | REPRESENTANTE (polimórfico)
```

---

## Roles y permisos

| Rol | Acceso |
|-----|--------|
| `admin` | Todo el sistema. Gestión de carreras, pensums, materias, aulas, usuarios, reportes |
| `profesor` | Sus secciones: notas, asistencia, materiales, evaluaciones, ver estudiantes inscritos |
| `estudiante` | Sus inscripciones, notas, horario, materiales, evaluaciones, evaluar profesores |
| `representante` | Ver notas, asistencia e inscripciones del estudiante vinculado (solo lectura) |

---

## Convenciones de código Laravel

### Estructura de carpetas
```
app/
  Http/
    Controllers/
      Admin/        ← controladores del panel admin
      Profesor/     ← controladores del portal profesor
      Estudiante/   ← controladores del portal estudiante
    Requests/       ← Form Requests por módulo
    Resources/      ← API Resources
  Models/           ← modelos Eloquent
  Services/         ← lógica de negocio (no en controladores)
  Policies/         ← autorización por modelo
```

### Convenciones generales
- **Idioma del código**: inglés (variables, métodos, clases, migraciones)
- **Idioma de UI y docs**: español latino (Venezuela)
- **Modelos**: singular PascalCase — `Student`, `Subject`, `Section`, `Enrollment`
- **Tablas**: plural snake_case — `students`, `subjects`, `sections`, `enrollments`
- **Migraciones**: descriptivas — `create_students_table`, `add_geographic_data_to_students`
- **Servicios**: `EnrollmentService`, `GradeService`, `PrerequisiteValidator`
- **Evitar lógica en controladores** — los controladores solo orquestan, la lógica va en Services
- **Form Requests** para toda validación de entrada
- **Policies** para toda autorización (nunca `if ($user->role === 'admin')` directo)

### Nomenclatura de rutas
```
/admin/*          ← panel administrativo
/profesor/*       ← portal del profesor
/estudiante/*     ← portal del estudiante
/representante/*  ← portal del representante
```

---

## Identidad visual — CACAO

### Tipografía
- **Fuente única**: Space Grotesk (Google Fonts)
- Pesos en uso: 300, 400, 500, 600, 700

### Paleta de colores (tokens Tailwind en tailwind.config.js)
```js
colors: {
  tinta:     '#131110',  // color primario, UI base
  terracota: '#C8521A',  // acento, CTAs, isotipo
  pizarra:   '#3D3A36',  // sidebar, paneles oscuros
  papel:     '#F4F2EF',  // fondos, superficies
  ambar:     '#E8895A',  // hover terracota, estados activos
  gris: {
    DEFAULT: '#888780',
    light:   '#C8C6C2',
    borde:   '#E0DDD8',
  }
}
```

### Isotipo
- Cuadrícula 3×3 de módulos cuadrados
- 7 módulos en color `tinta`, 1 módulo en `terracota` (posición [0,2] = esquina superior derecha), 1 espacio vacío (posición [1,2])
- Radio de esquina de cada módulo = 20% del lado
- Gap entre módulos = 25% del lado de cada uno
- **Nunca mover el módulo terracota de su posición**

### Componente Isotipo (referencia React/Vue)
```html
<!-- 3x3 grid, módulo [0,2] = terracota, [1,2] = vacío -->
<div class="grid grid-cols-3 gap-[3px] w-[20px]">
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="bg-terracota rounded-[2px] aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="invisible aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
  <span class="bg-tinta rounded-[2px] aspect-square"></span>
</div>
```

---

## Estado actual del proyecto

- [x] Definición de requerimientos completa
- [x] Diagrama entidad-relación completo (25 entidades)
- [x] Identidad visual definida (brand book completo)
- [ ] Migraciones Laravel
- [ ] Modelos Eloquent con relaciones
- [ ] Seeders con datos de prueba
- [ ] Autenticación y roles (Sanctum + Policies)
- [ ] Módulo académico (carreras, pensums, materias)
- [ ] Módulo personas (estudiantes, profesores)
- [ ] Módulo inscripciones
- [ ] Módulo infraestructura (aulas, horarios)
- [ ] Módulo evaluaciones
- [ ] Módulo recursos
- [ ] Interfaces por rol

---

## Documentación relacionada (en `/docs`)

| Archivo | Contenido |
|---------|-----------|
| `CLAUDE.md` | Este archivo — contexto general para Claude Code |
| `brand/brandbook.html` | Brand book visual completo con identidad CACAO |
| `brand/BRAND.md` | Guía de marca en texto plano |
| `brand/tokens.css` | Variables CSS de la marca |
| `brand/tailwind.config.brand.js` | Extensión de Tailwind con tokens CACAO |

---

## Notas importantes para Claude Code

1. **Antes de crear cualquier migración**, verifica que el modelo de datos de este archivo es consistente con lo que ya existe en `database/migrations/`.
2. **El idioma de la UI es español latinoamericano** — nunca España. Usar "les", "tienen", "ustedes", nunca "vosotros" ni "os".
3. **La entidad `Enrollment` (inscripcion)** es el pivote central — todo historial académico cuelga de ella.
4. **Las prelaciones** se validan en `PrerequisiteValidator` service antes de crear cualquier enrollment.
5. **Un aula es siempre una fila en la tabla `classrooms`** — el tipo (teórica/laboratorio) es un campo enum, no una tabla separada.
6. **Space Grotesk es la única fuente** — no agregar otras fuentes al proyecto.
