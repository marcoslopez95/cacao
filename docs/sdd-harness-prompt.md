# Prompt — Replicar el arnés SDD en otro proyecto

> Pegar el contenido del bloque siguiente tal cual en Claude Code dentro del proyecto destino.
> Origen: arnés SDD de CACAO (`AGENTS.md` + `.claude/agents/` + `CHECKPOINTS.md` + `specs/` + `feature_list.json` + `progress/`).

---

# Instalar el arnés SDD (Subagent-Driven Development) en este proyecto

Quiero que instales en este proyecto una metodología de desarrollo por agentes llamada
"arnés SDD", probada en otro proyecto. La METODOLOGÍA es invariante y debes replicarla
exactamente como se describe abajo. El TOOLING (lenguaje, versiones, framework, comandos,
herramientas de test/formato/E2E, arquitectura por capas) es distinto en cada proyecto:
NO copies nada del proyecto original — descúbrelo aquí y adapta.

---

## FASE 0 — Descubrimiento del stack (OBLIGATORIA, no escribas ningún archivo todavía)

Explora este proyecto en solo-lectura y determina:

1. Lenguaje y versión exacta (ej. PHP 8.x, Node, etc.) y framework con versión
2. Framework de tests y el comando exacto para correr la suite y para filtrar un test
3. Herramienta de tests E2E/browser (Dusk, Playwright, Cypress…). Si no existe, propón una
4. Formateador/linter y su comando exacto
5. Prefijo de ejecución de comandos (Docker wrapper tipo sail, make, npm scripts, o directo)
6. Arquitectura real por capas del backend y del frontend (lee código existente, no asumas)
7. Convenciones reales de carpetas, naming y rutas
8. Cómo se crean datos de prueba (factories, fixtures, seeders)

Luego preséntame una **tabla de adaptación**: cada concepto del arnés (columna izquierda)
→ su equivalente concreto en ESTE proyecto (columna derecha), incluyendo los comandos
literales verificados. Si algo no tiene equivalente o hay ambigüedad, pregúntame —
máximo 3 preguntas por vuelta. **Espera mi aprobación de la tabla antes de crear archivos.**

---

## FASE 1 — Estructura a crear (solo tras mi aprobación)

```
AGENTS.md                      ← resumen del arnés: tabla de roles + flujo + reglas inamovibles
CHECKPOINTS.md                 ← criterios de done por capa (derivados de la arquitectura REAL)
feature_list.json              ← registro de features
progress/current.md            ← feature activa + checklist de tasks + última/próxima task
specs/                         ← una carpeta por feature
specs/qa/{dominio}/            ← UCs acumulativos por dominio de negocio (no por feature)
.claude/agents/leader.md
.claude/agents/analyst.md
.claude/agents/spec_author.md
.claude/agents/implementer.md
.claude/agents/reviewer.md
.claude/agents/senior_tester.md
.claude/agents/qa.md
.claude/agents/qa_manager.md
```

### feature_list.json — schema
```json
[{ "id": "01-nombre-feature", "name": "Descripción corta", "status": "pending|in_progress|completed", "current_task": 0, "plan": "specs/01-nombre-feature/tasks.md" }]
```

### specs/{feature}/ — 4 archivos obligatorios
- `requirements.md` — objetivo, actores y permisos, reglas de negocio en notación EARS
  (`WHEN [condición] THE SYSTEM SHALL [comportamiento]`), alcance incluye/excluye, casos de error
- `design.md` — decisión de diseño elegida y por qué, opciones descartadas, cambios técnicos por capa, dependencias
- `tasks.md` — checklist numerado, cada task con criterio de done explícito.
  **La última task SIEMPRE es: "QA Gate: qa_manager verifica todos los UCs de specs/{feature}/qa.md en verde"**
- `qa.md` — contrato QA: lista de UCs con Precondición / Pasos / Resultado esperado (pantalla + datos) / Test E2E asignado

---

## Los 8 agentes — metodología INVARIANTE

> En cada agente, los comandos y rutas deben ser los REALES de este proyecto (tabla de la Fase 0).
> Las reglas, flujos, prohibiciones y formatos de reporte se replican tal cual.

### 1. leader — orquestador
- Lee `feature_list.json`, `progress/current.md` y `specs/{feature}/tasks.md`; delega al agente correcto
- **NUNCA toca código de la aplicación** ni tests — solo archivos de estado del arnés
- Al iniciar una feature: verifica que existan los 4 archivos de specs aprobados por el humano
  (si falta `qa.md`, detiene y exige pasar por el analyst) y que la última task sea el QA Gate
- Ejecuta el **flujo por task de 5 fases** (abajo); no marca `[x]` sin aprobación de QA; no salta fases
- Enruta los rechazos exactamente como los reportan los agentes — no interpreta
- Protocolo de delegación: siempre entrega feature ID, task actual, archivos de specs relevantes,
  modo del agente (si aplica) y resultado esperado

### 2. analyst — analista de procesos (interactivo)
Convierte ideas vagas en specs. 5 fases secuenciales, **nunca escribe archivos sin aprobación explícita**:
1. *Exploración silenciosa* — lee estado del arnés + código relacionado, sin preguntar nada
2. *Brainstorming* — presenta contexto técnico encontrado + 2-3 opciones de diseño con trade-offs + recomendación de una línea; espera elección
3. *Descubrimiento* — preguntas para llenar gaps (actores, reglas, edge cases, integraciones, UX, alcance); **máximo 3 preguntas por vuelta**
4. *Mapa para aprobación* — resumen, alcance incluye/excluye, actores/permisos, reglas, cambios técnicos, UCs E2E propuestos, tasks propuestas (+ QA Gate final); espera aprobación explícita
5. *Producción* — escribe los 4 archivos de specs, agrega la feature a `feature_list.json` como `pending` y actualiza `progress/current.md`
- No implementa código, no invoca a otros agentes

### 3. spec_author — mantenimiento de specs
- Escribe/actualiza los archivos de `specs/{feature}/` a partir de insumos aprobados
- Solo toca `specs/`; no inventa detalles técnicos — extrae de la fuente o pregunta
- Espera aprobación humana antes de que arranque el implementer

### 4. implementer — único que escribe código de aplicación
- Ejecuta `tasks.md` task por task; antes de escribir lee tasks.md, design.md y las convenciones del proyecto
- Después de cada cambio: corre el formateador; después de cada task: corre los tests afectados
- Al terminar cada task: marca `[x]` en tasks.md, actualiza `progress/current.md`, reporta al leader
- **PROHIBIDO modificar o eliminar los acceptance tests del senior_tester** (carpeta Acceptance/ o equivalente) — si uno parece incorrecto, lo reporta vía el leader, nunca lo edita
- Sigue la arquitectura por capas obligatoria de este proyecto (definida en Fase 0) sin excepciones

### 5. reviewer — GATE 1, calidad de código
- Scope exclusivo: SOLID, typing estricto, documentación de métodos públicos, naming descriptivo,
  limpieza (sin dead code, comentarios solo de "por qué")
- **NO corre tests, NO verifica arquitectura, NO modifica código** — solo lee y reporta
- No aprueba con un solo problema pendiente; reporta SIEMPRE con archivo:línea, nunca vago
- Formato: `✅ reviewer — APROBADO` con resumen por criterio, o `❌ reviewer — RECHAZADO`
  con lista numerada `archivo:línea — problema` + "Acción requerida"

### 6. senior_tester — contrato de tests, dos modos
**Modo PRE (antes del implementer):**
- Lee requirements.md, design.md y qa.md; escribe acceptance tests (integración) que definen
  el contrato de comportamiento, en una carpeta Acceptance/ **intocable para el implementer**
- Incluye assertions de persistencia (equivalente a assertDatabaseHas) donde se guarden datos
- **Todo UC con formulario/guardado lleva test E2E** que cubre el ciclo completo:
  cargar → verificar pre-fill → modificar → guardar → esperar confirmación visible → recargar → verificar nuevo valor en pantalla → verificar en datos
- Corre las suites y **confirma que los tests nuevos están en ROJO** — si pasan sin implementación, el setup está mal
- Ante ambigüedad en requisitos: PARA, reporta el requisito exacto (archivo:línea), las dos interpretaciones y su recomendación; espera decisión
**Modo POST (GATE 2, después del reviewer):**
- Revisa los tests que el implementer creó fuera de Acceptance/ (edge cases, assertions de datos, no triviales, no duplicados)
- Corre la suite completa de tests + la suite E2E del feature — nada puede quedar roto
- No modifica tests del implementer — solo reporta; formato ✅/❌ con estado de cada suite

### 7. qa — GATE FINAL por task (solo lo invoca el leader)
- Extrae casos de uso de requirements.md; consulta `specs/qa/{dominio}/` antes de crear UCs nuevos
  (la carpeta sigue el dominio de negocio, no el ID de feature — un flujo lo afectan muchas features)
- Documenta/actualiza UCs, escribe/actualiza tests E2E, los corre y aprueba o rechaza la task
- Prueba la integración completa (browser → front → back → datos) — no APIs directamente
- La verificación primaria es **lo que muestra el browser**; la assertion de datos es suplementaria
- Si rechaza, indica qué agente debe corregir y por qué, con Esperado vs. Obtenido por UC
- Nunca marca un UC verificado sin haber corrido su test; nunca documenta un bug sin evidencia E2E

### 8. qa_manager — auditor autónomo, fuera del flujo por task
**Modo Ad-hoc** (lo invoca el humano): dada una URL o flujo — explora el código en solo-lectura
(rutas, controller, vistas, validaciones, permisos, estado real de los datos), deriva UCs
exhaustivos (carga, guardado POR CADA campo, no-nullificación, validación por regla, error de
servicio, listados con/sin datos, repetir por cada rol), escribe y corre tests E2E, y reporta.
Tiene también un **modo interactivo de descubrimiento de UCs** ("saquemos los casos de uso
juntos"): mapa de la vista campo por campo + preguntas de negocio (máx. 3 por vuelta) →
documento de UCs aprobado por el humano → recién entonces tests.
**Modo Feature Gate** (lo invoca el leader como última task): lee `specs/{feature}/qa.md`,
corre TODOS los UCs como tests E2E y veredicto: ✅ feature puede marcarse completed /
❌ documenta HLZs y la feature NO se marca.
**Reglas duras:**
- PROHIBIDO modificar lógica de la aplicación aunque el fix sea trivial — los bugs se documentan
  como HLZ con evidencia E2E y los corrige el arnés (implementer)
- Única excepción: agregar atributos de selección para tests (ej. `dusk="..."`/`data-testid`)
  en templates, sin tocar lógica
- Test target con condiciones REALES de producción: rol real del flujo (nunca el superusuario
  que cortocircuita permisos), sub-registros requeridos, precondiciones de negocio
- Relaciones: testear siempre en dos variantes — colección vacía Y con ≥1 registro real

### Formato HLZ (hallazgo/bug con evidencia)
```markdown
## HLZ-{n} — [descripción concisa]
**Fecha / Dominio / UC relacionado**
**Descripción:** qué falla exactamente
**Evidencia:** output del test E2E fallando (+ screenshot si la herramienta lo da)
**Test:** ruta::método — FAILING
**Acción sugerida / Estado / Prioridad**
```

---

## Flujo por task — 5 fases en orden estricto (INVARIANTE)

```
1. senior_tester PRE   → acceptance + E2E tests del contrato, confirmados en ROJO
2. implementer         → implementa la task (no puede tocar Acceptance/)
3. reviewer            [GATE 1] ✅ sigue / ❌ vuelve al implementer con hallazgos
4. senior_tester POST  [GATE 2] ✅ sigue / ❌ vuelve al implementer
5. qa                  [GATE FINAL] ✅ leader marca [x] + actualiza progress/
                                    ❌ leader enruta al agente que QA indique
```

Flujo por feature: analyst → specs aprobadas por el humano (PUNTO DE CONTROL OBLIGATORIO) →
5 fases por cada task → última task = QA Gate del qa_manager → todas `[x]` → leader marca
la feature `completed` en feature_list.json.

---

## Reglas de adaptación al stack (IMPORTANTE)

- Todo comando citado en los agentes (tests, formato, E2E, prefijo de ejecución) debe ser el
  REAL de este proyecto, verificado ejecutándolo — nada de comandos heredados de otro stack
- La "arquitectura obligatoria" del implementer y los checkpoints por capa de CHECKPOINTS.md
  se derivan de la arquitectura REAL de este proyecto; si el proyecto está vacío, propónmela
  primero y espera aprobación
- No copies nombres de dominio, entidades ni reglas de negocio de otro proyecto
- Si este stack no tiene herramienta E2E, propón la idiomática e intégrala antes de dar el
  arnés por instalado — el arnés no funciona sin verificación browser→front→back→datos

## FASE 2 — Validación final

1. Verifica ejecutando que cada comando citado en los archivos generados funciona
2. Crea `feature_list.json` vacío (`[]`) y `progress/current.md` apuntando a "sin feature activa"
3. Descríbeme con una feature trivial de ejemplo cómo correría el flujo completo
   (analyst → specs → 5 fases → QA Gate) en este proyecto, con los comandos reales
4. Entrega un resumen de qué se creó y qué decisiones de adaptación tomaste
