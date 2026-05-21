# Demo Seeder — Design Spec

**Fecha:** 2026-05-21
**Feature ID:** 03-demo-seeder
**Estado:** aprobado

---

## Objetivo

Crear un `DemoSeeder` modular que siembre datos realistas y completos en la base de datos, sirviendo tanto para demostraciones a stakeholders como para desarrollo local. El seeder refleja el estado actual del sistema y se actualiza con cada nueva feature.

---

## Arquitectura

### Estructura de archivos

```
database/seeders/
├── DemoSeeder.php                    ← orquestador único
└── Demo/
    ├── DemoAcademicSeeder.php        ← categorías, carreras, pensums, materias, prelaciones
    ├── DemoInfrastructureSeeder.php  ← edificios, aulas
    ├── DemoPeriodSeeder.php          ← períodos, lapsos
    ├── DemoProfessorsSeeder.php      ← profesores + usuarios
    ├── DemoStudentsSeeder.php        ← estudiantes + representantes + usuarios
    ├── DemoSectionsSeeder.php        ← secciones universitarias + bachillerato + horarios
    └── DemoEnrollmentSeeder.php      ← inscripciones con estados variados
```

### Orden de ejecución en el orquestador

`DemoSeeder` invoca los sub-seeders en este orden (respeta dependencias de FK):

1. `DemoAcademicSeeder`
2. `DemoInfrastructureSeeder`
3. `DemoPeriodSeeder`
4. `DemoProfessorsSeeder`
5. `DemoStudentsSeeder`
6. `DemoSectionsSeeder`
7. `DemoEnrollmentSeeder`

### Contrato de idempotencia

Cada sub-seeder usa `firstOrCreate()` o `updateOrCreate()` basado en un campo único natural (código de carrera, email de usuario, código de materia). **Nunca `create()` sin verificar existencia previa.** El comando `php artisan db:seed --class=DemoSeeder` puede ejecutarse repetidamente sin duplicar datos.

---

## Datos del demo

### Instituciones

| Nivel | Nombre |
|-------|--------|
| Universitario | Universidad Tecnológica CACAO (UTCACAO) |
| Secundaria | Unidad Educativa CACAO |

### Estructura académica universitaria

| Entidad | Volumen | Detalle |
|---------|---------|---------|
| Categorías de carrera | 3 | Ingeniería · Ciencias Económicas · Humanidades y Educación |
| Carreras | 5 | Ing. Informática · Ing. Civil · Contaduría Pública · Administración · Educación Mención Matemática |
| Pensums | 5 | Uno activo por carrera, 8 semestres |
| Materias | ~40 | ~8 por pensum |
| Prelaciones | ~20 | Cadenas reales (Cálculo I→II, Programación I→II→III, etc.) |

### Infraestructura

| Entidad | Volumen | Detalle |
|---------|---------|---------|
| Edificios | 2 | Edificio A (Ingeniería) · Edificio B (Ciencias/Humanidades) |
| Aulas | ~15 | Mezcla de teóricas y laboratorios, capacidades variadas |

### Períodos

| Entidad | Volumen | Detalle |
|---------|---------|---------|
| Períodos | 2 | 2025-II (finalizado) · 2026-I (activo) |
| Lapsos | 4 | 2 lapsos por período |

### Personas universitarias

| Entidad | Volumen | Credenciales |
|---------|---------|--------------|
| Profesores | 12 | prof01@utcacao.edu.ve … prof12@utcacao.edu.ve / `password` |
| Estudiantes universitarios | 120 | est01@utcacao.edu.ve … est120@utcacao.edu.ve / `password` |

### Personas de bachillerato

| Entidad | Volumen | Detalle |
|---------|---------|---------|
| Estudiantes de secundaria | 20 | Con representante vinculado |
| Representantes | 20 | rep01@utcacao.edu.ve … rep20@utcacao.edu.ve / `password` |

### Secciones y horarios

| Entidad | Volumen | Detalle |
|---------|---------|---------|
| Secciones universitarias | ~60 | 2 por materia del semestre 2026-I, cupos variados (20–40) |
| Secciones de bachillerato | 10 | 1A–1C, 2A–2C, 3A, 4A, 5A |
| Horarios | ~120 | Lun–Vie, franjas 7am–1pm y 1pm–7pm |

### Inscripciones (estado actual del sistema)

| Estado | Cantidad | Detalles |
|--------|----------|---------|
| `draft` | 30 estudiantes | 4–6 materias inscritas c/u |
| `confirmed` | 30 estudiantes | 4–6 materias inscritas c/u |
| `approved` | 30 estudiantes | 4–6 materias inscritas c/u |

Todas las inscripciones respetan prelaciones y cupos. 30 estudiantes quedan sin inscripción (permiten demostrar el flujo de inicio).

---

## Cómo ejecutar

```bash
# Sembrar sobre una BD ya migrada
vendor/bin/sail artisan db:seed --class=DemoSeeder

# Sembrar desde cero (limpiar + migrar + sembrar)
vendor/bin/sail artisan migrate:fresh --seed
# O solo el demo después de un fresh:
vendor/bin/sail artisan migrate:fresh && vendor/bin/sail artisan db:seed --class=DemoSeeder
```

`DemoSeeder` llama a `DatabaseSeeder` como su primer paso, por lo que es completamente autónomo. No es necesario correr `DatabaseSeeder` por separado.

---

## Tests

Un `DemoSeederTest` feature test (`tests/Feature/DemoSeederTest.php`) que:
- Ejecuta `DemoSeeder::run()`
- Verifica conteos mínimos con `assertDatabaseCount` por entidad
- Verifica que los conteos no se dupliquen en una segunda ejecución (idempotencia)

No verifica cada fila individualmente — solo que el volumen esperado existe sin errores.

---

## Regla de mantenimiento

Cada nueva feature en `feature_list.json` debe incluir en su `tasks.md` una task explícita:

> **Task demo-update:** Crear/actualizar sub-seeder en `database/seeders/Demo/` para que `DemoSeeder` refleje la nueva feature. Incluir datos realistas y test de conteo.

Esta regla garantiza que `DemoSeeder` siempre representa el estado actual del sistema.

---

## Criterios de done

- [ ] `php artisan db:seed --class=DemoSeeder` corre sin errores sobre BD limpia
- [ ] Segunda ejecución no duplica datos
- [ ] Todos los usuarios demo pueden hacer login con `password`
- [ ] La página de inscripción muestra el catálogo completo con el usuario `est01@utcacao.edu.ve`
- [ ] `DemoSeederTest` pasa: `vendor/bin/sail artisan test --compact --filter=DemoSeeder`
- [ ] Pint sin errores: `vendor/bin/sail bin pint --dirty --format agent`
