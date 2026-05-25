# Diseño: Rediseño de Agentes del Arnés SDD

**Fecha:** 2026-05-25
**Estado:** Aprobado

---

## Contexto

El arnés SDD actual tiene 4 agentes: `leader`, `spec_author`, `implementer`, `reviewer`. El `reviewer` actual hace de todo: verifica arquitectura, corre tests y aprueba tasks. Este diseño lo reemplaza con una separación clara de responsabilidades en 3 agentes especializados: `reviewer` (calidad de código), `senior_tester` (contrato de tests) y `qa` (integración E2E y aprobación final).

---

## Agentes resultantes (6 en total)

| Agente | Rol | ¿Modifica código app? | ¿Tiene poder de bloqueo? |
|--------|-----|----------------------|--------------------------|
| `leader` | Orquesta el flujo | No | — |
| `spec_author` | Escribe specs | No | — |
| `senior_tester` | Contrato de tests (PRE + POST) | Solo archivos de test | Sí (POST) |
| `implementer` | Implementa tasks | Sí | — |
| `reviewer` | Calidad de código | No | Sí |
| `qa` | E2E con Dusk + aprobación final | Solo tests Browser/ | Sí (gate final) |

---

## Flujo completo por task

```
spec_author → [aprobación humana]
    ↓
senior_tester PRE
  · lee requirements.md + design.md
  · escribe Acceptance tests en tests/Feature/{Feature}/Acceptance/
  · incluye assertDatabaseHas/Count/Missing donde aplica
  · corre suite → confirma que tests están en ROJO
  · si ambigüedad en requisitos → para y reporta al humano
    ↓
implementer
  · hace pasar los Acceptance tests (NO puede modificarlos)
  · escribe sus propios tests fuera de Acceptance/
    ↓
reviewer  [GATE 1]
  · SOLID, PHP 8 typing estricto, PHPDoc completo
  · ❌ rechaza con archivo:línea → vuelve al implementer
  · ✅ aprueba → continúa
    ↓
senior_tester POST  [GATE 2]
  · revisa tests del implementer: calidad, casos edge, DB assertions
  · corre suite completa — nada puede quedar roto
  · ❌ rechaza con evidencia → vuelve al implementer
  · ✅ aprueba → continúa
    ↓
QA  [GATE FINAL]
  · extrae casos de uso de requirements.md
  · guarda/actualiza specs/qa/{dominio}/{flujo}.md
  · escribe/corre tests Dusk en tests/Browser/{Dominio}/
  · ❌ rechaza → reporta UC fallido + agente responsable → vuelve al punto correcto
  · ✅ aprueba → leader marca task [x] y actualiza progress/current.md
```

---

## Agente: `senior_tester`

### Modo PRE (antes del implementer)

**Responsabilidades:**
- Leer `specs/{feature}/requirements.md` y `design.md`
- Escribir acceptance/integration tests (Pest Feature) que definen el contrato de comportamiento esperado
- Guardar en `tests/Feature/{Feature}/Acceptance/` — estos archivos son **intocables** por el implementer
- Incluir assertions de DB en todos los tests donde se persistan datos: `assertDatabaseHas`, `assertDatabaseCount`, `assertDatabaseMissing`
- Correr la suite y confirmar que los nuevos tests están en ROJO (si pasan sin implementación, el setup está mal)
- Si detecta ambigüedad en los requisitos antes de escribir: parar, reportar al humano con el requisito exacto + las dos interpretaciones posibles + recomendación. No asume ni inventa.

**Reglas:**
- Solo crea/modifica archivos en `tests/Feature/{Feature}/Acceptance/`
- No toca código de la aplicación
- No modifica tests fuera de `Acceptance/`

### Modo POST (después del reviewer)

**Responsabilidades:**
- Revisar los tests que el implementer creó fuera de `Acceptance/`
- Verificar que sean competentes: cubren casos edge, incluyen assertions de DB donde aplica, no duplican acceptance tests existentes, desacoplan lógica real
- Correr la suite completa (`vendor/bin/sail artisan test --compact`) — nada debe estar roto
- Reportar resultado con evidencia concreta

**Formato de aprobación:**
```
✅ senior_tester POST — APROBADO
Tests del implementer revisados: [lista de archivos]
Suite completa: PASS (X tests, 0 failures)
```

**Formato de rechazo:**
```
❌ senior_tester POST — RECHAZADO

Problema 1: [archivo:línea] — [descripción: test superficial / sin DB assertion / duplica UC-XX]
Problema 2: [archivo:línea] — [descripción]

Suite: [PASS/FAIL — si FAIL, incluir output del error]
Acción requerida: [qué debe corregir el implementer]
```

---

## Agente: `reviewer`

**Responsabilidades — calidad de código únicamente:**

1. **SOLID:** cada método tiene una responsabilidad, dependencias inyectadas, sin violaciones de LSP/ISP/DIP
2. **PHP 8 typing estricto:** todos los parámetros y returns tipados, sin `mixed` innecesario
3. **PHPDoc completo** en cada método público:
   - `@param` con tipo exacto
   - `@return` con tipos genéricos cuando aplica (`Collection<int, User>`, `array<string, string>`)
   - El tipo genérico del `@return` debe coincidir con el return type hint de la firma
4. **Naming:** variables y métodos descriptivos, sin abreviaciones crípticas
5. **Sin dead code**, sin comentarios que expliquen el "qué" en lugar del "por qué"

**Ejemplo de PHPDoc aceptable:**
```php
/**
 * Retorna los usuarios activos paginados.
 *
 * @param  int  $perPage
 * @return \Illuminate\Pagination\LengthAwarePaginator<\App\Models\User>
 */
public function paginate(int $perPage = 15): LengthAwarePaginator
```

**Ejemplo de PHPDoc rechazable:**
```php
// Sin @return genérico, sin @param tipado
public function paginate($perPage) // sin type hint
```

**Formato de aprobación:**
```
✅ reviewer — APROBADO
Archivos revisados: [lista]
SOLID: OK | Typing: OK | PHPDoc: OK | Naming: OK
```

**Formato de rechazo:**
```
❌ reviewer — RECHAZADO

Problema 1: app/Actions/User/CreateUserAction.php:34 — @return sin tipo genérico (Collection en lugar de Collection<int, User>)
Problema 2: app/Http/Wrappers/User/UserWrapper.php:12 — parámetro $d sin tipo hint

Acción requerida: [qué debe corregir el implementer]
```

**Reglas:**
- NO corre tests — eso es responsabilidad de `senior_tester`
- NO verifica arquitectura (FormRequest→Wrapper→Action→Resource) — eso lo verifica el implementer siguiendo CLAUDE.md
- Solo lee y reporta — nunca modifica código

---

## Agente: `qa`

**Responsabilidades:**

1. **Extraer casos de uso** de `specs/{feature}/requirements.md`
2. **Documentar en `specs/qa/{dominio}/{flujo}.md`** (archivo acumulativo por flujo de negocio)
3. **Escribir/actualizar tests Dusk** en `tests/Browser/{Dominio}/`
4. **Correr tests Dusk** y reportar por caso de uso
5. **Aprobar o rechazar** la task (gate final)

**Estructura de carpetas QA:**
```
specs/qa/
  usuarios/
    crear-usuario.md
    editar-usuario.md
  inscripciones/
    crear-inscripcion.md
  calificaciones/
    cargar-notas.md
  autenticacion/
    login-multi-rol.md
tests/Browser/
  Usuarios/
    CreateUserTest.php
  Inscripciones/
    CreateEnrollmentTest.php
```

**Formato de caso de uso en `specs/qa/{dominio}/{flujo}.md`:**
```markdown
## UC-{n} — [Nombre descriptivo]
**Precondición:** [estado del sistema antes de ejecutar]
**Pasos:** [lo que el usuario hace en el browser, paso a paso]
**Resultado esperado:** [qué debe verse en pantalla + qué debe estar en DB]
**Test Dusk:** tests/Browser/{Dominio}/{File}.php::{método}
**Feature de origen:** {feature-id}
**Última verificación:** YYYY-MM-DD
```

**Regla de dominios:** el nombre de carpeta sigue el dominio de negocio, no el ID del feature del arnés. Un flujo como "crear usuario" puede ser afectado por múltiples features.

**Formato de aprobación:**
```
✅ QA — APROBADO
Casos de uso verificados: UC-01, UC-02, UC-03
Tests Dusk: PASS
specs/qa actualizado: [archivos]
```

**Formato de rechazo:**
```
❌ QA — RECHAZADO

UC-02 FALLIDO: Admin crea usuario con rol estudiante
  Esperado: usuario aparece en tabla con rol "Estudiante"
  Obtenido: tabla vacía / error 500 en submit
  Agente responsable: implementer (lógica de persistencia)

UC-03 FALLIDO: Validación falla si email duplicado
  Esperado: mensaje de error inline en campo email
  Obtenido: página en blanco
  Agente responsable: implementer (manejo de errores en frontend)
```

**Reglas:**
- QA prueba integración web completa (browser → front → back → DB), no APIs directamente
- Consulta `specs/qa/{dominio}/` antes de escribir nuevos UCs para evitar duplicados y detectar regresiones
- Solo el QA puede marcar una task como lista para que el leader la apruebe

---

## Cambios al `leader`

El leader debe actualizar su protocolo de delegación para el nuevo flujo de 5 fases por task:

1. Verificar que `tests/Feature/{Feature}/Acceptance/` existe antes de delegar al `implementer`
2. Después del `implementer`: delegar a `reviewer` (GATE 1)
3. Si reviewer aprueba: delegar a `senior_tester` en modo POST (GATE 2)
4. Si senior_tester aprueba: delegar a `qa` (GATE FINAL)
5. Si QA aprueba: marcar task `[x]` en `tasks.md` y actualizar `progress/current.md`
6. Si cualquier gate rechaza: enrutar al agente indicado en el reporte de rechazo

---

## Cambios al `implementer`

Una restricción adicional:
- **NUNCA modificar ni eliminar archivos en `tests/Feature/{Feature}/Acceptance/`**
- Si un acceptance test parece incorrecto, reportarlo al `senior_tester` vía el `leader` — no editarlo directamente

---

## Estructura de archivos de agentes

```
.claude/agents/
  leader.md          ← actualizar protocolo de delegación
  spec_author.md     ← sin cambios
  senior_tester.md   ← nuevo (reemplaza parte del reviewer actual)
  implementer.md     ← agregar restricción sobre Acceptance/
  reviewer.md        ← refocused a calidad de código
  qa.md              ← nuevo
```
