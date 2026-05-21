# Requisitos — Testing: Vitest + Laravel Dusk

**Feature:** `04-testing-vitest-dusk`

---

## Objetivo

Agregar dos capas de testing que complementan los tests Pest existentes:

1. **Vitest** — tests unitarios de composables Vue y utilidades TypeScript
2. **Laravel Dusk** — tests E2E en browser real usando el container Selenium ya configurado en `compose.yaml`

Los tests Pest de backend no se tocan. Esta feature solo añade infraestructura y tests nuevos.

---

## Actores

| Actor | Descripción |
|-------|-------------|
| Desarrollador | Ejecuta la suite completa o tests específicos durante desarrollo |
| CI | Ejecuta `vitest run` y `artisan dusk` en pipelines |

---

## Reglas de negocio

### Vitest
- WHEN se ejecuta `npm run test:run`, THE SYSTEM SHALL correr todos los tests en `tests/js/` y reportar pass/fail
- WHEN se ejecuta `npm run test:coverage`, THE SYSTEM SHALL generar reporte de cobertura en `coverage/`
- WHEN un composable tiene lógica de permisos CASL, THE SYSTEM SHALL testear el resultado para cada rol (admin, profesor, estudiante, representante)
- WHEN un composable usa `useForm` de Inertia, THE SYSTEM SHALL mockear Inertia en `tests/js/setup.ts` — nunca depender de servidor real

### Laravel Dusk
- WHEN se ejecuta `vendor/bin/sail dusk`, THE SYSTEM SHALL conectar al container `selenium/standalone-chromium` existente en `compose.yaml`
- WHEN un test Dusk necesita un usuario autenticado, THE SYSTEM SHALL usar `loginAs()` con factories — nunca credenciales hardcodeadas
- WHEN un test Dusk visita una ruta protegida sin autenticación, THE SYSTEM SHALL verificar redirección a `/login`
- Los tests Dusk se escriben con sintaxis Pest — consistente con el resto del proyecto

---

## Pirámide de testing resultante

```
[Dusk]   E2E: Auth, Dashboard, Enrollment, Academic, Admin
[Pest]   Feature: HTTP, controllers, policies, DB  ← ya existe, no se modifica
[Vitest] Unit: composables, utils, lógica pura
```

---

## Fuera de alcance

- Component tests con `@vue/test-utils` montando componentes Vue (cubierto por Dusk)
- Visual regression tests
- Performance / load tests
- Tests de rutas Academic/Admin que aún no estén implementadas en el frontend (esos tests se marcan como `skip` con nota)
