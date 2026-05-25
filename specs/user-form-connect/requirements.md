# Requirements — User Form Connect

**Feature:** `06-user-form-connect`
**Scope:** Full-stack — conectar las vistas de usuario al backend con guardado progresivo por sección
**Depends on:** `user-form-views` (componentes UI ya construidos)

---

## Contexto

El arnés `user-form-views` construyó la UI completa del formulario de usuario (17 secciones, 4 roles, tabs, sidebar, progress). Toda la lógica de guardado era local/simulada. Este arnés conecta cada sección al backend real.

## Problema central

El usuario (admin) puede perder todo el trabajo si hay un corte eléctrico o de internet mientras llena un formulario de 17 secciones. El sistema debe permitir guardar por tramos.

---

## Requerimientos funcionales

### RF-01 — Creación de usuario en dos pasos

El usuario admin:
1. Elige el rol (admin/student/professor/guardian)
2. Llena S01 (identidad) + S02 (credenciales) + elige `password_mode`
3. Hace clic en "Crear usuario" → el registro se crea en la base de datos
4. La página redirige a la vista de completar perfil (Edit.vue)

### RF-02 — `password_mode` en S02

S02 debe incluir 3 pills (`AppPillRadios`):
- `link` — enviar link de activación (oculta el campo de contraseña)
- `manual` — el admin escribe la contraseña (muestra el campo)
- `random` — el sistema genera una contraseña aleatoria y la muestra en el toast post-creación

### RF-03 — Guardado progresivo por sección en Edit.vue

Cada sección (S03–S17) tiene un botón "Guardar sección" que persiste esa sección al backend independientemente. Si falla la conexión después de guardar S05, S01–S05 ya están en BD.

### RF-04 — Pre-carga de datos existentes

Al abrir Edit.vue (ya sea inmediatamente después de crear el usuario o al volver en otro momento), cada sección muestra los datos ya guardados en la BD.

### RF-05 — Feedback de guardado

- Durante el save de una sección: spinner/estado de carga en el botón
- En éxito: badge "Guardado" aparece en la cabecera de la sección + toast
- En error: errores de validación se muestran inline en los campos correspondientes

### RF-06 — Reutilización de Edit.vue para edición futura

La vista Edit.vue debe funcionar tanto para "completar perfil recién creado" como para "editar usuario existente". La URL con el user ID es canónica.

---

## Requerimientos no funcionales

- Sin guardado de secciones de perfil (S03+) hasta que el usuario exista en BD
- S15 (Representantes) queda como stub en este arnés — vinculación de guardians es un feature separado
- Los uploads reales de documentos (S07) quedan como stub — solo se guarda el tipo/metadata por ahora
- Pint después de cada cambio PHP
- Tests Pest para el controlador `edit()` y para el cambio de redirect en `store()`
