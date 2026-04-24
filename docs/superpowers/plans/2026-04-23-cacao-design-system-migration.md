# CACAO Design System Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace shadcn/ui with the CACAO design system — CSS tokens, primitive components, app shell, Dashboard, feedback components, data components — across all existing pages.

**Architecture:** Bottom-up — tokens → primitives → layout shell → dashboard → feedback → data → page migrations. Each task produces working, testable output before the next starts. All component CSS uses design-system classes (`.btn`, `.badge`, `.card`, etc.) from `docs/design_handoff_cacao/reference/components.css`, ingested into `app.css`. Vue SFCs are thin wrappers that map props to those classes.

**Tech Stack:** Vue 3 · Inertia.js v3 · Tailwind CSS v4 · Laravel 13 · TypeScript · Pest v4

---

### Task 1: Replace `app.css` with CACAO tokens + ingest component CSS

**Files:**
- Modify: `resources/css/app.css`
- Modify: `resources/views/app.blade.php`

- [ ] **Step 1: Overwrite `resources/css/app.css`**

```css
@import url('https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap');
@import 'tailwindcss';
@import 'tw-animate-css';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';

@theme inline {
  --color-tinta:         #131110;
  --color-terracota:     #C8521A;
  --color-pizarra:       #3D3A36;
  --color-papel:         #F4F2EF;
  --color-papel-dark:    #EDEBE7;
  --color-hueso:         #FAFAF8;
  --color-ambar:         #E8895A;

  --font-sans: 'Space Grotesk', ui-sans-serif, system-ui, sans-serif;
  --font-mono: 'JetBrains Mono', ui-monospace, Menlo, monospace;

  --text-xs:   11px;
  --text-sm:   13px;
  --text-base: 14px;
  --text-md:   15px;
  --text-lg:   17px;
  --text-xl:   20px;
  --text-2xl:  24px;
  --text-3xl:  32px;
  --text-4xl:  44px;

  --radius-xs:   3px;
  --radius-sm:   4px;
  --radius-md:   6px;
  --radius-lg:   10px;
  --radius-xl:   14px;
  --radius-pill: 999px;

  /* Dynamic tokens — exposed as Tailwind utilities */
  --color-bg-page:        var(--bg-page);
  --color-bg-surface:     var(--bg-surface);
  --color-bg-surface-2:   var(--bg-surface-2);
  --color-bg-sunken:      var(--bg-sunken);
  --color-accent:         var(--accent);
  --color-accent-hover:   var(--accent-hover);
  --color-accent-soft:    var(--accent-soft);
  --color-accent-fg:      var(--accent-fg);
  --color-text-primary:   var(--text-primary);
  --color-text-secondary: var(--text-secondary);
  --color-text-muted:     var(--text-muted);
  --color-text-inverse:   var(--text-inverse);
  --color-border-base:    var(--border);
  --color-border-strong:  var(--border-strong);
  --color-sidebar-bg:     var(--sidebar-bg);
  --color-sidebar-fg:     var(--sidebar-fg);
  --color-topbar-bg:      var(--topbar-bg);
  --color-topbar-fg:      var(--topbar-fg);
  --color-success:        var(--success);
  --color-success-bg:     var(--success-bg);
  --color-success-fg:     var(--success-fg);
  --color-warning:        var(--warning);
  --color-warning-bg:     var(--warning-bg);
  --color-warning-fg:     var(--warning-fg);
  --color-danger:         var(--danger);
  --color-danger-bg:      var(--danger-bg);
  --color-danger-fg:      var(--danger-fg);
  --color-info:           var(--info);
  --color-info-bg:        var(--info-bg);
  --color-info-fg:        var(--info-fg);
}

/* Light mode tokens */
:root {
  --bg-page:     #EDEBE7;
  --bg-surface:  #FAFAF8;
  --bg-surface-2:#F4F2EF;
  --bg-sunken:   #E8E4DE;
  --accent:        #C8521A;
  --accent-hover:  #A9441A;
  --accent-soft:   #F7E4D7;
  --accent-fg:     #FFFFFF;
  --text-primary:  #131110;
  --text-secondary:#55504A;
  --text-muted:    #8A8278;
  --text-inverse:  #F4F2EF;
  --border:        #E0DDD8;
  --border-strong: #C9C5BE;
  --sidebar-bg:    #3D3A36;
  --sidebar-fg:    #E8E4DF;
  --sidebar-muted: #9C9690;
  --topbar-bg:     #131110;
  --topbar-fg:     #F4F2EF;
  --success:    #4C7A1F; --success-bg: #EAF3DE; --success-fg: #2E4B12;
  --warning:    #B87500; --warning-bg: #FBEFD3; --warning-fg: #6B4500;
  --danger:     #B12A1F; --danger-bg:  #F8DCD7; --danger-fg:  #6E1812;
  --info:       #1F5F8B; --info-bg:    #DCE8F2; --info-fg:    #133C58;
  --shadow-xs: 0 1px 0 rgba(19,17,16,0.04);
  --shadow-sm: 0 1px 2px rgba(19,17,16,0.06), 0 1px 0 rgba(19,17,16,0.03);
  --shadow-md: 0 4px 12px rgba(19,17,16,0.08), 0 1px 2px rgba(19,17,16,0.04);
  --shadow-lg: 0 16px 40px rgba(19,17,16,0.14), 0 2px 6px rgba(19,17,16,0.06);
}

/* Dark mode tokens */
[data-theme="dark"] {
  --bg-page:     #1C1A18;
  --bg-surface:  #242220;
  --bg-surface-2:#2A2826;
  --bg-sunken:   #181614;
  --accent:        #E8895A;
  --accent-hover:  #F0A078;
  --accent-soft:   #3A251C;
  --accent-fg:     #131110;
  --text-primary:  #E8E4DF;
  --text-secondary:#B5AFA8;
  --text-muted:    #7A7570;
  --text-inverse:  #131110;
  --border:        #2E2C2A;
  --border-strong: #3E3B38;
  --sidebar-bg:    #161412;
  --sidebar-fg:    #D8D3CD;
  --sidebar-muted: #7F7A74;
  --topbar-bg:     #0E0C0B;
  --topbar-fg:     #E8E4DF;
  --success:    #89B95A; --success-bg: #0D2810; --success-fg: #B6D891;
  --warning:    #E2A645; --warning-bg: #2B1F0A; --warning-fg: #EFC07B;
  --danger:     #E77465; --danger-bg:  #2E1411; --danger-fg:  #F2A89B;
  --info:       #6FA8CF; --info-bg:    #0F2230; --info-fg:    #A8C8E0;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.4);
  --shadow-md: 0 4px 12px rgba(0,0,0,0.4), 0 1px 2px rgba(0,0,0,0.3);
  --shadow-lg: 0 16px 40px rgba(0,0,0,0.5), 0 2px 6px rgba(0,0,0,0.3);
}

@layer base {
  *, ::after, ::before { border-color: var(--border); }
  *:focus-visible {
    outline: 2px solid var(--accent);
    outline-offset: 2px;
    border-radius: var(--radius-sm);
  }
  button, input, select, textarea { font-family: inherit; }
}

/* Unlayered — wins over @layer base, ensures tokens always apply */
body {
  font-family: var(--font-sans);
  font-size: var(--text-base);
  line-height: 1.5;
  color: var(--text-primary);
  background-color: var(--bg-page);
  -webkit-font-smoothing: antialiased;
}

/* ===================== COMPONENT CLASSES ===================== */

/* --- Buttons --- */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  font-weight: 500;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  cursor: pointer;
  white-space: nowrap;
  transition: background 120ms, color 120ms, border-color 120ms, box-shadow 120ms;
  font-family: var(--font-sans);
  font-size: var(--text-sm);
  text-decoration: none;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }

.btn-sm  { height: 30px; padding: 0 10px; font-size: var(--text-xs); gap: 4px; }
.btn-md  { height: 36px; padding: 0 14px; }
.btn-lg  { height: 44px; padding: 0 20px; font-size: var(--text-md); gap: 8px; }
.btn-icon { padding: 0; aspect-ratio: 1; }

.btn-primary  { background: var(--accent); color: var(--accent-fg); border-color: var(--accent); }
.btn-primary:hover:not(:disabled) { background: var(--accent-hover); border-color: var(--accent-hover); }

.btn-secondary { background: var(--bg-surface-2); color: var(--text-primary); border-color: var(--border); }
.btn-secondary:hover:not(:disabled) { background: var(--bg-sunken); }

.btn-ghost { background: transparent; color: var(--text-primary); border-color: transparent; }
.btn-ghost:hover:not(:disabled) { background: var(--bg-surface-2); }

.btn-danger { background: var(--danger-bg); color: var(--danger-fg); border-color: var(--danger-bg); }
.btn-danger:hover:not(:disabled) { background: var(--danger); color: #fff; border-color: var(--danger); }

.btn-link { background: transparent; color: var(--accent); border-color: transparent; padding-left: 0; padding-right: 0; }
.btn-link:hover:not(:disabled) { text-decoration: underline; }

.btn-tonal { background: var(--accent-soft); color: var(--accent); border-color: var(--accent-soft); }
.btn-tonal:hover:not(:disabled) { background: var(--accent-soft); filter: brightness(0.95); }

.spin {
  width: 14px; height: 14px;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
  flex-shrink: 0;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* --- Badges --- */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 20px;
  padding: 0 8px;
  font-size: var(--text-xs);
  font-weight: 600;
  border-radius: var(--radius-pill);
  border: 1px solid transparent;
  white-space: nowrap;
}
.bdot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; flex-shrink: 0; }

.badge-neutral { background: var(--bg-surface-2); color: var(--text-secondary); border-color: var(--border); }
.badge-success { background: var(--success-bg); color: var(--success-fg); }
.badge-warning { background: var(--warning-bg); color: var(--warning-fg); }
.badge-danger  { background: var(--danger-bg);  color: var(--danger-fg);  }
.badge-info    { background: var(--info-bg);    color: var(--info-fg);    }
.badge-accent  { background: var(--accent-soft); color: var(--accent);   }

/* --- Card --- */
.card {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xs);
  overflow: hidden;
  transition: transform 150ms, box-shadow 150ms;
}
.card:hover { transform: translateY(-1px); box-shadow: var(--shadow-sm); }
.card-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.card-body { padding: 20px; }
.card-foot {
  padding: 12px 20px;
  border-top: 1px solid var(--border);
  background: var(--bg-surface-2);
}

/* --- Stat card --- */
.stat {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 20px;
  box-shadow: var(--shadow-xs);
  position: relative;
  overflow: hidden;
}
.stat.accent::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent);
}
.stat-label { font-size: var(--text-xs); font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; }
.stat-value { font-size: var(--text-4xl); font-weight: 700; color: var(--text-primary); font-variant-numeric: tabular-nums; line-height: 1; margin-bottom: 8px; }
.stat-delta { font-size: 12px; font-weight: 500; }
.stat-delta.up   { color: var(--success); }
.stat-delta.down { color: var(--danger); }
.stat-footer { font-size: var(--text-xs); color: var(--text-muted); margin-top: 8px; }

/* --- Table --- */
.table-wrap {
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}
.table-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  border-bottom: 1px solid var(--border);
  gap: 12px;
}
.table { width: 100%; border-collapse: collapse; font-size: var(--text-sm); }
.table thead tr { border-bottom: 1px solid var(--border); background: var(--bg-surface-2); }
.table th {
  padding: 10px 16px;
  text-align: left;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
.table td { padding: 14px 16px; color: var(--text-primary); border-bottom: 1px solid var(--border); }
.table tbody tr:last-child td { border-bottom: none; }
.table tbody tr:hover td { background: var(--bg-surface-2); }

/* --- Input --- */
.input {
  height: 36px;
  padding: 0 12px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  color: var(--text-primary);
  font-size: var(--text-sm);
  font-family: var(--font-sans);
  width: 100%;
  transition: border-color 120ms, box-shadow 120ms;
}
.input::placeholder { color: var(--text-muted); }
.input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-soft); }

/* --- Alert --- */
.alert {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 12px 16px;
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  font-size: var(--text-sm);
}
.alert-success { background: var(--success-bg); color: var(--success-fg); border-color: var(--success); }
.alert-warning { background: var(--warning-bg); color: var(--warning-fg); border-color: var(--warning); }
.alert-danger  { background: var(--danger-bg);  color: var(--danger-fg);  border-color: var(--danger);  }
.alert-info    { background: var(--info-bg);    color: var(--info-fg);    border-color: var(--info);    }

/* --- Toast --- */
.toast-stack {
  position: fixed;
  bottom: 16px;
  right: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
  z-index: 9999;
  pointer-events: none;
}
.toast {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  background: var(--bg-surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  min-width: 280px;
  max-width: 400px;
  pointer-events: all;
  font-size: var(--text-sm);
  animation: toast-in 200ms ease;
}
.toast.leaving { animation: toast-out 150ms ease forwards; }
@keyframes toast-in  { from { opacity:0; transform: translateX(24px); } to { opacity:1; transform: translateX(0); } }
@keyframes toast-out { from { opacity:1; transform: translateX(0); } to { opacity:0; transform: translateX(24px); } }
@media (prefers-reduced-motion: reduce) {
  .toast, .toast.leaving { animation: none; }
}

/* --- Modal --- */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(19,17,16,0.5);
  backdrop-filter: blur(2px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  animation: fade-in 180ms ease;
}
.modal {
  background: var(--bg-surface);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-lg);
  width: 100%;
  max-height: calc(100vh - 48px);
  overflow-y: auto;
  animation: modal-in 180ms ease;
}
.modal-sm { max-width: 400px; }
.modal-md { max-width: 520px; }
.modal-lg { max-width: 720px; }
.modal-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 20px 24px 0;
  gap: 12px;
}
.modal-head h2 { font-size: var(--text-lg); font-weight: 600; color: var(--text-primary); }
.modal-head p  { font-size: var(--text-sm); color: var(--text-secondary); margin-top: 4px; }
.modal-body { padding: 20px 24px; }
.modal-foot {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 8px;
  padding: 16px 24px;
  border-top: 1px solid var(--border);
}
@keyframes fade-in  { from { opacity:0; } to { opacity:1; } }
@keyframes modal-in { from { opacity:0; transform: scale(0.96); } to { opacity:1; transform: scale(1); } }

/* --- App Shell --- */
.app-shell {
  display: flex;
  height: 100vh;
  overflow: hidden;
  background: var(--bg-page);
}
.app-sidebar {
  width: 240px;
  flex-shrink: 0;
  background: var(--sidebar-bg);
  display: flex;
  flex-direction: column;
  overflow-y: auto;
  overflow-x: hidden;
}
.app-topbar {
  height: 56px;
  background: var(--topbar-bg);
  display: flex;
  align-items: center;
  padding: 0 24px;
  gap: 12px;
  flex-shrink: 0;
}
.app-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.app-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}
@media (min-width: 1280px) { .app-content { padding: 32px; } }

/* Sidebar brand area */
.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 20px 16px 16px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
}
.sidebar-wordmark {
  font-size: var(--text-sm);
  font-weight: 700;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: var(--sidebar-fg);
}

/* Sidebar nav */
.sidebar-group { padding: 12px 8px 4px; }
.sidebar-group-label {
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--sidebar-muted);
  padding: 0 8px;
  margin-bottom: 4px;
}
.sidebar-item {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px;
  border-radius: var(--radius-md);
  color: var(--sidebar-fg);
  font-size: var(--text-sm);
  font-weight: 500;
  text-decoration: none;
  transition: background 120ms;
  cursor: pointer;
}
.sidebar-item:hover:not(.active) { background: rgba(255,255,255,0.07); }
.sidebar-item.active { background: var(--accent); color: var(--accent-fg); }

/* Sidebar footer */
.sidebar-footer {
  margin-top: auto;
  padding: 12px 8px;
  border-top: 1px solid rgba(255,255,255,0.07);
}
.sidebar-user {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: var(--radius-md);
  color: var(--sidebar-fg);
  text-decoration: none;
  transition: background 120ms;
}
.sidebar-user:hover { background: rgba(255,255,255,0.07); }

/* Isotipo */
.iso { display: grid; grid-template-columns: repeat(3,1fr); }
.iso-20 { width: 20px; gap: 2px; }
.iso-28 { width: 28px; gap: 2px; }
.iso-40 { width: 40px; gap: 3px; }
.iso-72 { width: 72px; gap: 4px; }
.iso-120{ width: 120px; gap: 6px; }
.iso .cell { border-radius: 2px; aspect-ratio: 1; background: var(--sidebar-fg); }
.iso .cell.accent { background: var(--accent); }
.iso .cell.empty { background: transparent; }
.iso-on-light .cell { background: var(--text-primary); }
.iso-on-light .cell.accent { background: var(--accent); }
.iso-on-topbar .cell { background: var(--topbar-fg); }
.iso-on-topbar .cell.accent { background: var(--accent); }

/* TodaySchedule */
.tsched { position: relative; height: 64px; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; margin: 0; }
.tsched-slot {
  position: absolute;
  top: 8px;
  height: calc(100% - 16px);
  border-radius: var(--radius-sm);
  padding: 0 8px;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: var(--text-xs);
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
}
.tsched-slot.done { background: var(--bg-sunken); color: var(--text-muted); text-decoration: line-through; }
.tsched-slot.now  { background: var(--accent); color: var(--accent-fg); }
.tsched-slot.next { background: var(--bg-surface-2); color: var(--text-primary); border: 1px dashed var(--border-strong); }
.tsched-now-marker {
  position: absolute;
  top: 0; bottom: 0;
  width: 2px;
  background: var(--accent);
  z-index: 2;
}
```

- [ ] **Step 2: Update `resources/views/app.blade.php`**

Replace the entire file with:

```php
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Detect theme before paint to avoid flash --}}
        <script>
            (function() {
                var stored = localStorage.getItem('cacao-theme');
                if (stored) {
                    document.documentElement.setAttribute('data-theme', stored);
                } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            })();
        </script>

        {{-- Prevent background flash while CSS loads --}}
        <style>
            html { background-color: #EDEBE7; }
            html[data-theme="dark"] { background-color: #1C1A18; }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
    </body>
</html>
```

- [ ] **Step 3: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 4: Build assets and verify no errors**

```bash
vendor/bin/sail npm run build 2>&1 | tail -20
```

Expected: build completes without errors.

- [ ] **Step 5: Commit**

```bash
git add resources/css/app.css resources/views/app.blade.php
git commit -m "feat: replace shadcn tokens with CACAO design system tokens + component CSS"
```

---

### Task 2: `Icon.vue` + `Isotipo.vue`

**Files:**
- Create: `resources/js/components/base/Icon.vue`
- Create: `resources/js/components/base/Isotipo.vue`

- [ ] **Step 1: Create `resources/js/components/base/Icon.vue`**

```vue
<script setup lang="ts">
defineProps<{
    name: string
    size?: number
    stroke?: number
}>()

const defaultSize = 16
const defaultStroke = 1.75

const paths: Record<string, string> = {
    search:       '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    plus:         '<path d="M12 5v14M5 12h14"/>',
    check:        '<path d="M20 6 9 17l-5-5"/>',
    x:            '<path d="M18 6 6 18M6 6l12 12"/>',
    chevronDown:  '<path d="m6 9 6 6 6-6"/>',
    chevronRight: '<path d="m9 6 6 6-6 6"/>',
    chevronLeft:  '<path d="m15 6-6 6 6 6"/>',
    chevronUp:    '<path d="m18 15-6-6-6 6"/>',
    arrowRight:   '<path d="M5 12h14M13 5l7 7-7 7"/>',
    filter:       '<path d="M3 6h18M6 12h12M10 18h4"/>',
    download:     '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
    upload:       '<path d="M12 21V9M7 14l5-5 5 5M4 3h16"/>',
    file:         '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
    folder:       '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
    user:         '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    users:        '<circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0"/><circle cx="17" cy="7" r="3"/><path d="M21 18a5 5 0 0 0-7-4.5"/>',
    book:         '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5V22h16"/>',
    building:     '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M10 21v-4h4v4"/>',
    calendar:     '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    clock:        '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    edit:         '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/>',
    trash:        '<path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/>',
    eye:          '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    more:         '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
    moreV:        '<circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>',
    bell:         '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21a2 2 0 0 0 4 0"/>',
    info:         '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
    alert:        '<path d="M12 3 1 21h22z"/><path d="M12 10v5M12 18h.01"/>',
    home:         '<path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/>',
    grid:         '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
    chart:        '<path d="M3 3v18h18"/><path d="m7 15 4-6 3 3 5-7"/>',
    logout:       '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    sun:          '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/>',
    moon:         '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8"/>',
    code:         '<path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/>',
    mail:         '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
    phone:        '<path d="M5 4h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/>',
    map:          '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
    star:         '<path d="m12 2 3.1 6.9 7.4.8-5.5 5.1 1.6 7.3L12 18.3 5.4 22.1l1.6-7.3L1.5 9.7l7.4-.8z"/>',
    shield:       '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    settings:     '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>',
}
</script>

<template>
    <svg
        :width="size ?? defaultSize"
        :height="size ?? defaultSize"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        :stroke-width="stroke ?? defaultStroke"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        v-html="paths[name] ?? ''"
    />
</template>
```

- [ ] **Step 2: Create `resources/js/components/base/Isotipo.vue`**

```vue
<script setup lang="ts">
withDefaults(defineProps<{
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    variant?: 'default' | 'on-light' | 'on-topbar'
}>(), {
    size: 'md',
    variant: 'default',
})

const sizeMap = { xs: 'iso-20', sm: 'iso-28', md: 'iso-40', lg: 'iso-72', xl: 'iso-120' }
const variantMap = { default: '', 'on-light': 'iso-on-light', 'on-topbar': 'iso-on-topbar' }
</script>

<template>
    <div
        :class="['iso', sizeMap[size], variantMap[variant]]"
        role="img"
        aria-label="CACAO isotipo"
    >
        <span class="cell" />
        <span class="cell" />
        <span class="cell accent" />
        <span class="cell" />
        <span class="cell" />
        <span class="cell empty" />
        <span class="cell" />
        <span class="cell" />
        <span class="cell" />
    </div>
</template>
```

- [ ] **Step 3: Verify in browser — open any page and check there are no console errors**

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/base/
git commit -m "feat: add Icon.vue and Isotipo.vue base components"
```

---

### Task 3: `Button.vue` + `Badge.vue` + `Avatar.vue`

**Files:**
- Create: `resources/js/components/base/Button.vue`
- Create: `resources/js/components/base/Badge.vue`
- Create: `resources/js/components/base/Avatar.vue`

- [ ] **Step 1: Create `resources/js/components/base/Button.vue`**

```vue
<script setup lang="ts">
import Icon from '@/components/base/Icon.vue'

withDefaults(defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger' | 'link' | 'tonal'
    size?: 'sm' | 'md' | 'lg'
    icon?: string
    iconRight?: string
    iconOnly?: boolean
    loading?: boolean
    disabled?: boolean
    type?: 'button' | 'submit' | 'reset'
}>(), {
    variant: 'primary',
    size: 'md',
    type: 'button',
})

const iconSize = { sm: 13, md: 14, lg: 16 }
</script>

<template>
    <button
        :type="type"
        :class="['btn', `btn-${variant}`, `btn-${size}`, iconOnly ? 'btn-icon' : '']"
        :disabled="disabled || loading"
    >
        <span v-if="loading" class="spin" />
        <Icon v-else-if="icon" :name="icon" :size="iconSize[size]" />
        <slot v-if="!iconOnly" />
        <Icon v-if="!loading && iconRight" :name="iconRight" :size="iconSize[size]" />
    </button>
</template>
```

- [ ] **Step 2: Create `resources/js/components/base/Badge.vue`**

```vue
<script setup lang="ts">
withDefaults(defineProps<{
    variant?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'accent'
    dot?: boolean
}>(), {
    variant: 'neutral',
})
</script>

<template>
    <span :class="['badge', `badge-${variant}`]">
        <span v-if="dot" class="bdot" />
        <slot />
    </span>
</template>
```

- [ ] **Step 3: Create `resources/js/components/base/Avatar.vue`**

```vue
<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
    src?: string
    initials?: string
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl'
    colorPreset?: 1 | 2 | 3 | 4 | 5
}>(), {
    size: 'md',
    colorPreset: 1,
})

const sizeMap = { xs: 20, sm: 28, md: 36, lg: 48, xl: 64 }
const fontMap = { xs: 9, sm: 11, md: 13, lg: 16, xl: 22 }

const bgMap: Record<number, string> = {
    1: '#F7E4D7',
    2: '#DCE8F2',
    3: '#EAF3DE',
    4: '#EDE0F5',
    5: '#FBF0D3',
}
const fgMap: Record<number, string> = {
    1: '#7A3010',
    2: '#133C58',
    3: '#2E4B12',
    4: '#4A1F6E',
    5: '#6B4500',
}

const px = computed(() => sizeMap[props.size])
const fs = computed(() => fontMap[props.size])
const style = computed(() => ({
    width: `${px.value}px`,
    height: `${px.value}px`,
    fontSize: `${fs.value}px`,
    background: bgMap[props.colorPreset],
    color: fgMap[props.colorPreset],
    borderRadius: '50%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontWeight: 600,
    flexShrink: 0,
    overflow: 'hidden',
}))
</script>

<template>
    <div :style="style">
        <img v-if="src" :src="src" :alt="initials" style="width:100%;height:100%;object-fit:cover;" />
        <span v-else>{{ initials }}</span>
    </div>
</template>
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/base/Button.vue resources/js/components/base/Badge.vue resources/js/components/base/Avatar.vue
git commit -m "feat: add Button, Badge, Avatar base components"
```

---

### Task 4: `Card.vue` + `StatCard.vue`

**Files:**
- Create: `resources/js/components/base/Card.vue`
- Create: `resources/js/components/base/StatCard.vue`

- [ ] **Step 1: Create `resources/js/components/base/Card.vue`**

```vue
<script setup lang="ts">
defineProps<{ hover?: boolean }>()
</script>

<template>
    <div :class="['card', hover !== false ? '' : 'no-hover']">
        <div v-if="$slots.header" class="card-head">
            <slot name="header" />
        </div>
        <div class="card-body">
            <slot />
        </div>
        <div v-if="$slots.footer" class="card-foot">
            <slot name="footer" />
        </div>
    </div>
</template>
```

- [ ] **Step 2: Create `resources/js/components/base/StatCard.vue`**

```vue
<script setup lang="ts">
withDefaults(defineProps<{
    label: string
    value: string | number
    delta?: string
    deltaDirection?: 'up' | 'down'
    footer?: string
    accent?: boolean
}>(), {
    accent: false,
})
</script>

<template>
    <div :class="['stat', accent ? 'accent' : '']">
        <div class="stat-label">{{ label }}</div>
        <div class="stat-value">{{ value }}</div>
        <div v-if="delta" :class="['stat-delta', deltaDirection ?? 'up']">
            {{ deltaDirection === 'up' ? '↑' : '↓' }} {{ delta }}
        </div>
        <div v-if="footer" class="stat-footer">{{ footer }}</div>
    </div>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/base/Card.vue resources/js/components/base/StatCard.vue
git commit -m "feat: add Card and StatCard base components"
```

---

### Task 5: `AppSidebar.vue` + `AppTopbar.vue` + update layout

**Files:**
- Modify: `resources/js/components/AppSidebar.vue`
- Create: `resources/js/components/AppTopbar.vue`
- Modify: `resources/js/layouts/app/AppSidebarLayout.vue`

- [ ] **Step 1: Overwrite `resources/js/components/AppSidebar.vue`**

```vue
<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Avatar from '@/components/base/Avatar.vue'
import Icon from '@/components/base/Icon.vue'
import Isotipo from '@/components/base/Isotipo.vue'
import { dashboard } from '@/routes'
import { index as rolesIndex } from '@/routes/security/roles'

const page = usePage()

const currentUrl = computed(() => page.url)

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
)

const navGroups = computed(() => {
    const groups = [
        {
            label: 'General',
            items: [
                { icon: 'grid', label: 'Dashboard', href: dashboardUrl.value },
            ],
        },
    ]

    if (
        page.props.auth?.permissions?.includes('roles.view') ||
        page.props.auth?.roles?.includes('Admin')
    ) {
        groups.push({
            label: 'Seguridad',
            items: [
                { icon: 'shield', label: 'Roles', href: rolesIndex.url() },
            ],
        })
    }

    return groups
})

const user = computed(() => page.props.auth?.user)

const initials = computed(() => {
    const name = user.value?.name ?? ''
    return name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()
})

function isActive(href: string): boolean {
    return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

function logout(): void {
    router.post('/logout')
}
</script>

<template>
    <aside class="app-sidebar">
        <div class="sidebar-brand">
            <Isotipo size="sm" />
            <span class="sidebar-wordmark">CACAO</span>
        </div>

        <nav style="flex:1;padding:8px;">
            <div v-for="group in navGroups" :key="group.label" class="sidebar-group">
                <div class="sidebar-group-label">{{ group.label }}</div>
                <Link
                    v-for="item in group.items"
                    :key="item.href"
                    :href="item.href"
                    :class="['sidebar-item', isActive(item.href) ? 'active' : '']"
                >
                    <Icon :name="item.icon" :size="16" />
                    {{ item.label }}
                </Link>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user" style="justify-content:space-between;">
                <div style="display:flex;align-items:center;gap:10px;overflow:hidden;">
                    <Avatar :initials="initials" size="sm" :color-preset="1" />
                    <span style="font-size:13px;font-weight:500;color:var(--sidebar-fg);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ user?.name }}
                    </span>
                </div>
                <button
                    class="btn btn-ghost btn-icon btn-sm"
                    style="color:var(--sidebar-muted);"
                    aria-label="Cerrar sesión"
                    @click="logout"
                >
                    <Icon name="logout" :size="15" />
                </button>
            </div>
        </div>
    </aside>
</template>
```

- [ ] **Step 2: Create `resources/js/components/AppTopbar.vue`**

```vue
<script setup lang="ts">
import type { BreadcrumbItem } from '@/types'

defineProps<{
    breadcrumbs?: BreadcrumbItem[]
}>()
</script>

<template>
    <header class="app-topbar">
        <nav v-if="breadcrumbs && breadcrumbs.length" style="display:flex;align-items:center;gap:6px;flex:1;">
            <template v-for="(crumb, i) in breadcrumbs" :key="i">
                <span v-if="i > 0" style="color:var(--topbar-fg);opacity:0.4;font-size:13px;">/</span>
                <a
                    :href="crumb.href"
                    style="font-size:13px;font-weight:500;color:var(--topbar-fg);text-decoration:none;opacity:0.7;"
                    :style="i === breadcrumbs!.length - 1 ? 'opacity:1;' : ''"
                >{{ crumb.title }}</a>
            </template>
        </nav>
        <div v-else style="flex:1;" />
    </header>
</template>
```

- [ ] **Step 3: Overwrite `resources/js/layouts/app/AppSidebarLayout.vue`**

```vue
<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import AppTopbar from '@/components/AppTopbar.vue'
import Toast from '@/components/feedback/Toast.vue'
import type { BreadcrumbItem } from '@/types'

withDefaults(defineProps<{
    breadcrumbs?: BreadcrumbItem[]
}>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <div class="app-shell">
        <AppSidebar />
        <main class="app-main">
            <AppTopbar :breadcrumbs="breadcrumbs" />
            <div class="app-content">
                <slot />
            </div>
        </main>
    </div>
    <Toast />
</template>
```

Note: `Toast` component is created in Task 7. For now the import will fail. Temporarily comment it out until Task 7 is complete, then uncomment.

- [ ] **Step 4: Update `resources/js/layouts/AppLayout.vue`** — it already delegates to `AppSidebarLayout.vue` so no changes needed. Verify:

```bash
grep -n "AppSidebarLayout" resources/js/layouts/AppLayout.vue
```

Expected: line 2 has the import.

- [ ] **Step 5: Build and open in browser, verify sidebar renders with CACAO styles**

```bash
vendor/bin/sail npm run build 2>&1 | tail -5
vendor/bin/sail artisan route:list --path=/ --method=GET | head -5
```

Visit `/login` or `/` — sidebar should show dark pizarra background, isotipo, and nav items.

- [ ] **Step 6: Commit**

```bash
git add resources/js/components/AppSidebar.vue resources/js/components/AppTopbar.vue resources/js/layouts/app/AppSidebarLayout.vue
git commit -m "feat: rewrite AppSidebar with CACAO tokens, add AppTopbar, update layout"
```

---

### Task 6: `Dashboard.vue`

**Files:**
- Modify: `resources/js/pages/Dashboard.vue`

- [ ] **Step 1: Overwrite `resources/js/pages/Dashboard.vue`**

```vue
<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import StatCard from '@/components/base/StatCard.vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'

const page = usePage()
const user = computed(() => page.props.auth?.user)

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: '/' }],
    },
})

// TodaySchedule
const RANGE_START = 7
const RANGE_HOURS = 12

interface Slot {
    start: number
    end: number
    title: string
    room: string
    section: string
    status: 'done' | 'now' | 'next'
}

const slots: Slot[] = [
    { start: 7,   end: 8.5,  title: 'Cálculo I',        room: 'A-101', section: 'SEC-01', status: 'done' },
    { start: 9,   end: 10.5, title: 'Física II',         room: 'B-202', section: 'SEC-03', status: 'now'  },
    { start: 11,  end: 12.5, title: 'Programación I',    room: 'Lab-1', section: 'SEC-07', status: 'next' },
    { start: 14,  end: 15.5, title: 'Álgebra Lineal',    room: 'A-104', section: 'SEC-02', status: 'next' },
]

const nowDecimal = ref(0)
let timer: ReturnType<typeof setInterval>

function updateNow(): void {
    const d = new Date()
    nowDecimal.value = d.getHours() + d.getMinutes() / 60
}

onMounted(() => { updateNow(); timer = setInterval(updateNow, 60_000) })
onUnmounted(() => clearInterval(timer))

function slotStyle(s: Slot): Record<string, string> {
    const left = ((s.start - RANGE_START) / RANGE_HOURS) * 100
    const width = ((s.end - s.start) / RANGE_HOURS) * 100
    return { left: `${left}%`, width: `${width}%` }
}

const nowPct = computed(() => {
    const pct = ((nowDecimal.value - RANGE_START) / RANGE_HOURS) * 100
    return Math.min(Math.max(pct, 0), 100)
})

// Enrollments table mock
const enrollments = [
    { id: 1, student: 'María González',  subject: 'Cálculo I',     section: 'SEC-01', status: 'approved' as const },
    { id: 2, student: 'Carlos Pérez',    subject: 'Física II',     section: 'SEC-03', status: 'pending'  as const },
    { id: 3, student: 'Ana Rodríguez',   subject: 'Programación I', section: 'SEC-07', status: 'approved' as const },
    { id: 4, student: 'Luis Martínez',   subject: 'Álgebra Lineal', section: 'SEC-02', status: 'rejected' as const },
    { id: 5, student: 'Sofía Hernández', subject: 'Cálculo I',     section: 'SEC-01', status: 'pending'  as const },
]

const statusVariant = { approved: 'success', pending: 'warning', rejected: 'danger' } as const
const statusLabel   = { approved: 'Aprobada', pending: 'Pendiente', rejected: 'Rechazada' } as const

const period = ref<'week' | 'month' | 'period'>('month')
</script>

<template>
    <div style="display:flex;flex-direction:column;gap:24px;">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
            <div>
                <h1 style="font-size:var(--text-2xl);font-weight:700;color:var(--text-primary);margin:0;">
                    Bienvenido{{ user?.name ? ', ' + user.name.split(' ')[0] : '' }}
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:4px 0 0;">
                    Período académico 2025-2 · Semana 14
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="display:inline-flex;background:var(--bg-sunken);border-radius:var(--radius-md);padding:3px;">
                    <button
                        v-for="opt in ['week','month','period'] as const"
                        :key="opt"
                        :class="['btn btn-sm', period === opt ? 'btn-secondary' : 'btn-ghost']"
                        @click="period = opt"
                    >
                        {{ opt === 'week' ? 'Semana' : opt === 'month' ? 'Mes' : 'Período' }}
                    </button>
                </div>
                <Button icon="download" variant="secondary" size="sm">Exportar</Button>
            </div>
        </div>

        <!-- TodaySchedule -->
        <div>
            <div style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:8px;">
                Horario de hoy
            </div>
            <div class="tsched">
                <div
                    class="tsched-now-marker"
                    :style="{ left: nowPct + '%' }"
                />
                <div
                    v-for="s in slots"
                    :key="s.title"
                    :class="['tsched-slot', s.status]"
                    :style="slotStyle(s)"
                >
                    <span>{{ s.title }}</span>
                    <span style="opacity:0.7;">{{ s.room }}</span>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;">
            <StatCard label="Inscripciones activas" value="1,284" delta="+12% vs mes anterior" delta-direction="up" :accent="true" />
            <StatCard label="Secciones abiertas"    value="87"    delta="+3 esta semana" delta-direction="up" />
            <StatCard label="Profesores activos"    value="64"    footer="En 14 departamentos" />
            <StatCard label="Tasa de aprobación"    value="91%"   delta="-2% vs período anterior" delta-direction="down" />
        </div>

        <!-- Enrollments table -->
        <div class="table-wrap">
            <div class="table-toolbar">
                <span style="font-size:var(--text-sm);font-weight:600;color:var(--text-primary);">
                    Inscripciones recientes
                </span>
                <Button variant="ghost" size="sm" icon="filter">Filtrar</Button>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Materia</th>
                        <th>Sección</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in enrollments" :key="e.id">
                        <td style="font-weight:500;">{{ e.student }}</td>
                        <td style="color:var(--text-secondary);">{{ e.subject }}</td>
                        <td style="color:var(--text-muted);">{{ e.section }}</td>
                        <td>
                            <Badge :variant="statusVariant[e.status]" dot>
                                {{ statusLabel[e.status] }}
                            </Badge>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Build and open dashboard in browser**

```bash
vendor/bin/sail npm run build 2>&1 | tail -5
```

Visit the dashboard — you should see the greeting, TodaySchedule strip, 4 stat cards, and enrollments table.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/Dashboard.vue
git commit -m "feat: rebuild Dashboard page with CACAO design system components"
```

---

### Task 7: `useToast.ts` + `Toast.vue`

**Files:**
- Create: `resources/js/components/feedback/useToast.ts`
- Create: `resources/js/components/feedback/Toast.vue`

- [ ] **Step 1: Create `resources/js/components/feedback/useToast.ts`**

```typescript
import { reactive } from 'vue'

export type ToastVariant = 'neutral' | 'success' | 'warning' | 'danger' | 'info'

export interface ToastItem {
    id: number
    message: string
    variant: ToastVariant
    duration: number
    leaving: boolean
    action?: { label: string; onClick: () => void }
}

interface ToastOptions {
    message: string
    variant?: ToastVariant
    duration?: number
    action?: { label: string; onClick: () => void }
}

const state = reactive<{ toasts: ToastItem[] }>({ toasts: [] })
let nextId = 0

function dismiss(id: number): void {
    const item = state.toasts.find(t => t.id === id)
    if (!item) return
    item.leaving = true
    setTimeout(() => {
        const idx = state.toasts.findIndex(t => t.id === id)
        if (idx !== -1) state.toasts.splice(idx, 1)
    }, 160)
}

function toast(opts: ToastOptions): void {
    const id = ++nextId
    state.toasts.push({
        id,
        message: opts.message,
        variant: opts.variant ?? 'neutral',
        duration: opts.duration ?? 4000,
        leaving: false,
        action: opts.action,
    })
    if (opts.duration !== 0) {
        setTimeout(() => dismiss(id), opts.duration ?? 4000)
    }
}

export function useToast() {
    return { toast, dismiss, toasts: state.toasts }
}
```

- [ ] **Step 2: Create `resources/js/components/feedback/Toast.vue`**

```vue
<script setup lang="ts">
import Icon from '@/components/base/Icon.vue'
import { useToast } from '@/components/feedback/useToast'

const { toasts, dismiss } = useToast()

const variantIcon: Record<string, string> = {
    success: 'check',
    warning: 'alert',
    danger:  'x',
    info:    'info',
    neutral: 'info',
}

const variantColor: Record<string, string> = {
    success: 'var(--success)',
    warning: 'var(--warning)',
    danger:  'var(--danger)',
    info:    'var(--info)',
    neutral: 'var(--text-muted)',
}
</script>

<template>
    <div class="toast-stack" aria-live="polite">
        <div
            v-for="t in toasts"
            :key="t.id"
            :class="['toast', t.leaving ? 'leaving' : '']"
            :role="t.variant === 'danger' ? 'alert' : 'status'"
        >
            <Icon
                :name="variantIcon[t.variant]"
                :size="16"
                :style="{ color: variantColor[t.variant], flexShrink: 0 }"
            />
            <span style="flex:1;font-size:var(--text-sm);">{{ t.message }}</span>
            <button
                v-if="t.action"
                class="btn btn-link btn-sm"
                style="flex-shrink:0;"
                @click="t.action!.onClick(); dismiss(t.id)"
            >
                {{ t.action.label }}
            </button>
            <button
                class="btn btn-ghost btn-icon btn-sm"
                style="flex-shrink:0;color:var(--text-muted);"
                :aria-label="'Cerrar notificación'"
                @click="dismiss(t.id)"
            >
                <Icon name="x" :size="14" />
            </button>
        </div>
    </div>
</template>
```

- [ ] **Step 3: Uncomment Toast import in `AppSidebarLayout.vue`** (added in Task 5 as a comment)

The import was already written in Task 5. Remove the comment markers if present.

- [ ] **Step 4: Build and test toast manually** — open browser console and run:

```js
// In browser console on any authenticated page:
// This won't work directly but build should succeed with no errors
```

Run build:
```bash
vendor/bin/sail npm run build 2>&1 | tail -5
```

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/feedback/
git commit -m "feat: add Toast component and useToast composable"
```

---

### Task 8: `Modal.vue`

**Files:**
- Create: `resources/js/components/feedback/Modal.vue`

- [ ] **Step 1: Create `resources/js/components/feedback/Modal.vue`**

```vue
<script setup lang="ts">
import { nextTick, watch } from 'vue'
import Icon from '@/components/base/Icon.vue'

const props = withDefaults(defineProps<{
    open: boolean
    title?: string
    description?: string
    size?: 'sm' | 'md' | 'lg'
    closeOnOverlay?: boolean
}>(), {
    size: 'md',
    closeOnOverlay: true,
})

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

function close(): void {
    emit('update:open', false)
}

function onKey(e: KeyboardEvent): void {
    if (e.key === 'Escape') close()
}

watch(() => props.open, (val) => {
    if (val) {
        document.addEventListener('keydown', onKey)
        nextTick(() => {
            const modal = document.querySelector('.modal') as HTMLElement | null
            modal?.focus()
        })
    } else {
        document.removeEventListener('keydown', onKey)
    }
})
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="modal-backdrop"
            @click.self="closeOnOverlay ? close() : undefined"
        >
            <div
                :class="['modal', `modal-${size}`]"
                tabindex="-1"
                role="dialog"
                :aria-modal="true"
                :aria-labelledby="title ? 'modal-title' : undefined"
            >
                <div class="modal-head">
                    <div>
                        <h2 v-if="title" id="modal-title">{{ title }}</h2>
                        <p v-if="description">{{ description }}</p>
                    </div>
                    <button
                        class="btn btn-ghost btn-icon btn-sm"
                        style="color:var(--text-muted);flex-shrink:0;"
                        aria-label="Cerrar"
                        @click="close"
                    >
                        <Icon name="x" :size="16" />
                    </button>
                </div>
                <div class="modal-body">
                    <slot />
                </div>
                <div v-if="$slots.footer" class="modal-foot">
                    <slot name="footer" />
                </div>
            </div>
        </div>
    </Teleport>
</template>
```

- [ ] **Step 2: Build**

```bash
vendor/bin/sail npm run build 2>&1 | tail -5
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/feedback/Modal.vue
git commit -m "feat: add Modal component with Teleport, focus-trap, Esc-close"
```

---

### Task 9: `Alert.vue`

**Files:**
- Create: `resources/js/components/feedback/Alert.vue`

- [ ] **Step 1: Create `resources/js/components/feedback/Alert.vue`**

```vue
<script setup lang="ts">
import { ref } from 'vue'
import Icon from '@/components/base/Icon.vue'

withDefaults(defineProps<{
    variant?: 'success' | 'warning' | 'danger' | 'info'
    title?: string
    dismissible?: boolean
}>(), {
    variant: 'info',
    dismissible: false,
})

const visible = ref(true)

const icons = { success: 'check', warning: 'alert', danger: 'alert', info: 'info' }
</script>

<template>
    <div v-if="visible" :class="['alert', `alert-${variant}`]">
        <Icon :name="icons[variant]" :size="16" style="flex-shrink:0;margin-top:1px;" />
        <div style="flex:1;">
            <strong v-if="title" style="display:block;margin-bottom:2px;font-weight:600;">{{ title }}</strong>
            <slot />
        </div>
        <button
            v-if="dismissible"
            class="btn btn-ghost btn-icon btn-sm"
            style="flex-shrink:0;align-self:flex-start;"
            aria-label="Cerrar"
            @click="visible = false"
        >
            <Icon name="x" :size="14" />
        </button>
    </div>
</template>
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/components/feedback/Alert.vue
git commit -m "feat: add Alert component"
```

---

### Task 10: Migrate `security/Roles/Index.vue` + role modals

**Files:**
- Modify: `resources/js/pages/security/Roles/Index.vue`
- Modify: `resources/js/components/security/CreateRoleModal.vue`
- Modify: `resources/js/components/security/EditRoleModal.vue`
- Modify: `resources/js/components/security/DeleteRoleModal.vue`

- [ ] **Step 1: Overwrite `resources/js/pages/security/Roles/Index.vue`**

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Badge from '@/components/base/Badge.vue'
import Button from '@/components/base/Button.vue'
import DeleteRoleModal from '@/components/security/DeleteRoleModal.vue'
import EditRoleModal from '@/components/security/EditRoleModal.vue'
import CreateRoleModal from '@/components/security/CreateRoleModal.vue'
import { index } from '@/routes/security/roles'
import type { Role } from '@/types'

type Props = {
    roles: Role[]
    permissions: string[]
    can: {
        create: boolean
        update: boolean
        delete: boolean
        assignPermissions: boolean
    }
}

const props = defineProps<Props>()

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Seguridad', href: '#' },
            { title: 'Roles', href: index.url() },
        ],
    },
})

const editingRole = ref<Role | null>(null)
const deletingRole = ref<Role | null>(null)
const showCreate = ref(false)
</script>

<template>
    <Head title="Roles" />

    <div style="display:flex;flex-direction:column;gap:24px;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;">
            <div>
                <h1 style="font-size:var(--text-xl);font-weight:700;color:var(--text-primary);margin:0 0 4px;">
                    Roles
                </h1>
                <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">
                    Gestiona los roles y permisos del sistema
                </p>
            </div>
            <Button
                v-if="props.can.create"
                icon="plus"
                variant="primary"
                @click="showCreate = true"
            >
                Nuevo rol
            </Button>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos</th>
                        <th>Usuarios</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="role in props.roles" :key="role.id">
                        <td>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-weight:500;">{{ role.name }}</span>
                                <Badge v-if="role.isAdmin" variant="accent">Admin</Badge>
                            </div>
                        </td>
                        <td style="color:var(--text-secondary);">
                            <span v-if="role.permissions.length">{{ role.permissions.join(', ') }}</span>
                            <span v-else style="font-style:italic;color:var(--text-muted);">Sin permisos</span>
                        </td>
                        <td style="color:var(--text-muted);">{{ role.usersCount }}</td>
                        <td>
                            <div style="display:flex;align-items:center;justify-content:flex-end;gap:4px;">
                                <template v-if="!role.isAdmin">
                                    <Button
                                        v-if="props.can.update"
                                        variant="ghost"
                                        size="sm"
                                        icon-only
                                        icon="edit"
                                        :aria-label="`Editar ${role.name}`"
                                        @click="editingRole = role"
                                    />
                                    <Button
                                        v-if="props.can.delete"
                                        variant="ghost"
                                        size="sm"
                                        icon-only
                                        icon="trash"
                                        :aria-label="`Eliminar ${role.name}`"
                                        @click="deletingRole = role"
                                    />
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!props.roles.length">
                        <td colspan="4" style="text-align:center;color:var(--text-muted);padding:32px 16px;">
                            No hay roles registrados
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <CreateRoleModal
        :open="showCreate"
        :permissions="props.permissions"
        :can-assign-permissions="props.can.assignPermissions"
        @update:open="(v) => { if (!v) showCreate = false }"
    />

    <EditRoleModal
        v-if="editingRole"
        :role="editingRole"
        :permissions="props.permissions"
        :can-assign-permissions="props.can.assignPermissions"
        :open="true"
        @update:open="(v) => { if (!v) editingRole = null }"
    />

    <DeleteRoleModal
        v-if="deletingRole"
        :role="deletingRole"
        :open="true"
        @update:open="(v) => { if (!v) deletingRole = null }"
    />
</template>
```

- [ ] **Step 2: Overwrite `resources/js/components/security/CreateRoleModal.vue`**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { store } from '@/routes/security/roles'
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions'

defineProps<{
    open: boolean
    permissions: string[]
    canAssignPermissions?: boolean
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Nuevo rol"
        description="Crea un nuevo rol para asignar a los usuarios del sistema."
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="store.form()"
            class="space-y-5"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:6px;">
                <label for="create-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input
                    id="create-name"
                    name="name"
                    class="input"
                    placeholder="Nombre del rol"
                    required
                />
                <InputError :message="errors.name" />
            </div>

            <div v-if="canAssignPermissions && permissions.length" style="display:grid;gap:10px;">
                <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Permisos</span>
                <div
                    v-for="(groupPerms, group) in groupPermissions(permissions)"
                    :key="group"
                >
                    <p style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;margin:0 0 6px;">
                        {{ permissionGroupLabel(group) }}
                    </p>
                    <div style="display:grid;gap:4px;padding-left:8px;">
                        <label
                            v-for="permission in groupPerms"
                            :key="permission"
                            style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                :value="permission"
                                style="width:14px;height:14px;accent-color:var(--accent);"
                            />
                            {{ permission }}
                        </label>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar</Button>
            </template>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 3: Read `EditRoleModal.vue` and `DeleteRoleModal.vue` to understand their current structure**

```bash
cat resources/js/components/security/EditRoleModal.vue
cat resources/js/components/security/DeleteRoleModal.vue
```

- [ ] **Step 4: Overwrite `resources/js/components/security/EditRoleModal.vue`** using the same pattern as CreateRoleModal but with the edit route and pre-filled values. Read the file first, then adapt it to use `Modal.vue`, native `<input class="input">`, and `Button.vue`.

Key differences from Create:
- Uses `update` route with role id
- Pre-fills `name` input with `role.name`
- Pre-checks permissions matching `role.permissions`

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { ref } from 'vue'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import InputError from '@/components/InputError.vue'
import { update } from '@/routes/security/roles'
import { groupPermissions, permissionGroupLabel } from '@/utils/permissions'
import type { Role } from '@/types'

const props = defineProps<{
    open: boolean
    role: Role
    permissions: string[]
    canAssignPermissions?: boolean
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()
const formKey = ref(0)

function close(v: boolean): void {
    emit('update:open', v)
    if (!v) formKey.value++
}
</script>

<template>
    <Modal
        :open="open"
        title="Editar rol"
        :description="`Modifica el rol ${props.role.name}.`"
        size="md"
        @update:open="close"
    >
        <Form
            :key="formKey"
            v-bind="update(props.role.id).form()"
            class="space-y-5"
            v-slot="{ errors, processing }"
            @success="close(false)"
        >
            <div style="display:grid;gap:6px;">
                <label for="edit-name" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Nombre
                </label>
                <input
                    id="edit-name"
                    name="name"
                    class="input"
                    :value="props.role.name"
                    required
                />
                <InputError :message="errors.name" />
            </div>

            <div v-if="canAssignPermissions && permissions.length" style="display:grid;gap:10px;">
                <span style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">Permisos</span>
                <div
                    v-for="(groupPerms, group) in groupPermissions(permissions)"
                    :key="group"
                >
                    <p style="font-size:var(--text-xs);font-weight:600;color:var(--text-muted);text-transform:uppercase;margin:0 0 6px;">
                        {{ permissionGroupLabel(group) }}
                    </p>
                    <div style="display:grid;gap:4px;padding-left:8px;">
                        <label
                            v-for="permission in groupPerms"
                            :key="permission"
                            style="display:flex;align-items:center;gap:8px;font-size:var(--text-sm);cursor:pointer;"
                        >
                            <input
                                type="checkbox"
                                name="permissions[]"
                                :value="permission"
                                :checked="props.role.permissions.includes(permission)"
                                style="width:14px;height:14px;accent-color:var(--accent);"
                            />
                            {{ permission }}
                        </label>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="primary" :loading="processing">Guardar</Button>
            </template>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 5: Overwrite `resources/js/components/security/DeleteRoleModal.vue`**

```vue
<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import Modal from '@/components/feedback/Modal.vue'
import { destroy } from '@/routes/security/roles'
import type { Role } from '@/types'

const props = defineProps<{
    open: boolean
    role: Role
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

function close(v: boolean): void {
    emit('update:open', v)
}
</script>

<template>
    <Modal
        :open="open"
        title="Eliminar rol"
        :description="`¿Estás seguro de que deseas eliminar el rol &quot;${props.role.name}&quot;? Esta acción no se puede deshacer.`"
        size="sm"
        @update:open="close"
    >
        <Form
            v-bind="destroy(props.role.id).form()"
            v-slot="{ processing }"
            @success="close(false)"
        >
            <template #footer>
                <Button variant="secondary" @click="close(false)">Cancelar</Button>
                <Button type="submit" variant="danger" :loading="processing">Eliminar</Button>
            </template>
        </Form>
    </Modal>
</template>
```

- [ ] **Step 6: Build**

```bash
vendor/bin/sail npm run build 2>&1 | tail -10
```

Expected: no errors. If TypeScript errors appear for `destroy` route, check that `@/routes/security/roles` exports `destroy`.

- [ ] **Step 7: Run existing Roles tests**

```bash
vendor/bin/sail artisan test --compact --filter=Role
```

Expected: all pass.

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/security/Roles/Index.vue resources/js/components/security/
git commit -m "feat: migrate Roles page and modals to CACAO design system"
```

---

### Task 11: Update `useAppearance.ts` — switch to `data-theme`

**Files:**
- Modify: `resources/js/composables/useAppearance.ts`
- Modify: `resources/js/components/AppearanceTabs.vue`

- [ ] **Step 1: Overwrite `resources/js/composables/useAppearance.ts`**

```typescript
import type { ComputedRef, Ref } from 'vue'
import { computed, onMounted, ref } from 'vue'
import type { Appearance, ResolvedAppearance } from '@/types'

export type { Appearance, ResolvedAppearance }

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>
    resolvedAppearance: ComputedRef<ResolvedAppearance>
    updateAppearance: (value: Appearance) => void
}

function applyTheme(value: Appearance): void {
    if (typeof window === 'undefined') return

    let theme: 'dark' | 'light'
    if (value === 'system') {
        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    } else {
        theme = value
    }

    document.documentElement.setAttribute('data-theme', theme)
}

const setCookie = (name: string, value: string, days = 365): void => {
    if (typeof document === 'undefined') return
    document.cookie = `${name}=${value};path=/;max-age=${days * 86400};SameSite=Lax`
}

const prefersDark = (): boolean => {
    if (typeof window === 'undefined') return false
    return window.matchMedia('(prefers-color-scheme: dark)').matches
}

export function updateTheme(value: Appearance): void {
    applyTheme(value)
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') return

    const stored = localStorage.getItem('cacao-theme') as Appearance | null
    applyTheme(stored ?? 'system')

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        const current = localStorage.getItem('cacao-theme') as Appearance | null
        if (!current || current === 'system') applyTheme('system')
    })
}

const appearance = ref<Appearance>('system')

export function useAppearance(): UseAppearanceReturn {
    onMounted(() => {
        const saved = localStorage.getItem('cacao-theme') as Appearance | null
        if (saved) appearance.value = saved
    })

    const resolvedAppearance = computed<ResolvedAppearance>(() => {
        if (appearance.value === 'system') return prefersDark() ? 'dark' : 'light'
        return appearance.value
    })

    function updateAppearance(value: Appearance): void {
        appearance.value = value
        localStorage.setItem('cacao-theme', value)
        setCookie('cacao-theme', value)
        applyTheme(value)
    }

    return { appearance, resolvedAppearance, updateAppearance }
}
```

- [ ] **Step 2: Overwrite `resources/js/components/AppearanceTabs.vue`**

```vue
<script setup lang="ts">
import Icon from '@/components/base/Icon.vue'
import { useAppearance } from '@/composables/useAppearance'

const { appearance, updateAppearance } = useAppearance()

const tabs = [
    { value: 'light',  icon: 'sun',     label: 'Claro'  },
    { value: 'dark',   icon: 'moon',    label: 'Oscuro' },
    { value: 'system', icon: 'settings', label: 'Sistema' },
] as const
</script>

<template>
    <div style="display:inline-flex;gap:4px;background:var(--bg-sunken);border-radius:var(--radius-md);padding:3px;">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            :class="['btn', 'btn-sm', appearance === tab.value ? 'btn-secondary' : 'btn-ghost']"
            @click="updateAppearance(tab.value)"
        >
            <Icon :name="tab.icon" :size="13" />
            {{ tab.label }}
        </button>
    </div>
</template>
```

- [ ] **Step 3: Build and test dark mode toggle manually**

```bash
vendor/bin/sail npm run build 2>&1 | tail -5
```

Visit `/settings/appearance`, click the dark/light toggle — page should switch themes correctly.

- [ ] **Step 4: Commit**

```bash
git add resources/js/composables/useAppearance.ts resources/js/components/AppearanceTabs.vue
git commit -m "feat: switch theme mechanism from .dark class to data-theme attribute"
```

---

### Task 12: Migrate auth layout + Login page

**Files:**
- Modify: `resources/js/layouts/auth/AuthSimpleLayout.vue`
- Modify: `resources/js/pages/auth/Login.vue`

- [ ] **Step 1: Overwrite `resources/js/layouts/auth/AuthSimpleLayout.vue`**

```vue
<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import Isotipo from '@/components/base/Isotipo.vue'
import { home } from '@/routes'

defineProps<{
    title?: string
    description?: string
}>()
</script>

<template>
    <div style="min-height:100svh;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:24px;background:var(--bg-page);padding:24px;">
        <div style="width:100%;max-width:400px;">
            <div style="display:flex;flex-direction:column;gap:32px;">
                <div style="display:flex;flex-direction:column;align-items:center;gap:16px;">
                    <Link :href="home()" style="display:flex;align-items:center;gap:10px;text-decoration:none;">
                        <Isotipo size="sm" variant="on-light" />
                        <span style="font-size:15px;font-weight:700;letter-spacing:4px;text-transform:uppercase;color:var(--text-primary);">
                            CACAO
                        </span>
                    </Link>
                    <div style="text-align:center;">
                        <h1 style="font-size:var(--text-xl);font-weight:600;color:var(--text-primary);margin:0 0 6px;">{{ title }}</h1>
                        <p style="font-size:var(--text-sm);color:var(--text-muted);margin:0;">{{ description }}</p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
```

- [ ] **Step 2: Overwrite `resources/js/pages/auth/Login.vue`**

```vue
<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import Button from '@/components/base/Button.vue'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { register } from '@/routes'
import { store } from '@/routes/login'
import { request } from '@/routes/password'

defineOptions({
    layout: {
        title: 'Iniciar sesión',
        description: 'Ingresa tu correo y contraseña para acceder',
    },
})

defineProps<{
    status?: string
    canResetPassword: boolean
    canRegister: boolean
}>()
</script>

<template>
    <Head title="Iniciar sesión" />

    <div v-if="status" style="margin-bottom:16px;text-align:center;font-size:var(--text-sm);font-weight:500;color:var(--success);">
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        style="display:flex;flex-direction:column;gap:20px;"
    >
        <div style="display:grid;gap:16px;">
            <div style="display:grid;gap:6px;">
                <label for="email" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                    Correo electrónico
                </label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    class="input"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="correo@ejemplo.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div style="display:grid;gap:6px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <label for="password" style="font-size:var(--text-sm);font-weight:500;color:var(--text-primary);">
                        Contraseña
                    </label>
                    <TextLink v-if="canResetPassword" :href="request()" style="font-size:var(--text-xs);" :tabindex="5">
                        ¿Olvidaste tu contraseña?
                    </TextLink>
                </div>
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="input"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Contraseña"
                />
                <InputError :message="errors.password" />
            </div>

            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;font-size:var(--text-sm);">
                <input
                    id="remember"
                    type="checkbox"
                    name="remember"
                    :tabindex="3"
                    style="width:14px;height:14px;accent-color:var(--accent);"
                />
                Recuérdame
            </label>

            <Button
                type="submit"
                variant="primary"
                size="lg"
                :loading="processing"
                :disabled="processing"
                :tabindex="4"
                style="width:100%;margin-top:4px;"
            >
                Iniciar sesión
            </Button>
        </div>

        <div v-if="canRegister" style="text-align:center;font-size:var(--text-sm);color:var(--text-muted);">
            ¿No tienes cuenta?
            <TextLink :href="register()" :tabindex="5">Regístrate</TextLink>
        </div>
    </Form>
</template>
```

- [ ] **Step 3: Build and test login page visually**

```bash
vendor/bin/sail npm run build 2>&1 | tail -5
```

Visit `/login` — should show CACAO isotipo + wordmark, styled form with token colors.

- [ ] **Step 4: Run auth tests**

```bash
vendor/bin/sail artisan test --compact --filter=Auth
```

Expected: all pass.

- [ ] **Step 5: Commit**

```bash
git add resources/js/layouts/auth/AuthSimpleLayout.vue resources/js/pages/auth/Login.vue
git commit -m "feat: migrate auth layout and Login page to CACAO design system"
```

---

### Task 13: Delete `components/ui/` and remove orphaned imports

**Files:**
- Delete: `resources/js/components/ui/` (entire directory)
- Modify: any file still importing from `@/components/ui/`

- [ ] **Step 1: Identify remaining imports from `@/components/ui/`**

```bash
grep -rl "@/components/ui" resources/js/ --include="*.vue" --include="*.ts"
```

List all files returned.

- [ ] **Step 2: For each file returned, remove or replace the import**

Common replacements:
- `Button` from `@/components/ui/button` → `Button` from `@/components/base/Button.vue`
- `Badge` from `@/components/ui/badge` → `Badge` from `@/components/base/Badge.vue`
- `Dialog`, `DialogContent` etc. → `Modal` from `@/components/feedback/Modal.vue`
- `Input`, `Label` from shadcn → native `<input class="input">` + `<label>`
- `Spinner` from `@/components/ui/spinner` → use `Button` with `loading` prop instead
- `Checkbox` → native `<input type="checkbox">`

For each auth page not yet migrated (Register, ForgotPassword, ResetPassword, VerifyEmail, ConfirmPassword): apply same pattern as Login — replace shadcn `Input`/`Label`/`Button`/`Checkbox` with native inputs + `Button.vue`.

- [ ] **Step 3: Verify no remaining imports**

```bash
grep -rl "@/components/ui" resources/js/ --include="*.vue" --include="*.ts"
```

Expected: no output.

- [ ] **Step 4: Delete the `ui/` directory**

```bash
rm -rf resources/js/components/ui
```

- [ ] **Step 5: Remove `lucide-vue-next` imports** — they were used for icons in shadcn components. Replace any remaining lucide imports with `Icon.vue` calls.

```bash
grep -rl "lucide-vue-next" resources/js/ --include="*.vue" --include="*.ts"
```

For each file, replace the lucide icon component with `<Icon :name="..." />`.

- [ ] **Step 6: Build — must succeed with 0 errors**

```bash
vendor/bin/sail npm run build 2>&1 | tail -20
```

If TypeScript errors about missing types appear, check `@/types` for `BreadcrumbItem`, `NavItem`, `Role` — these are defined in `resources/js/types/index.d.ts` and are unrelated to shadcn.

- [ ] **Step 7: Run full test suite**

```bash
vendor/bin/sail artisan test --compact
```

Expected: all pass.

- [ ] **Step 8: Run Pint**

```bash
vendor/bin/sail bin pint --dirty --format agent
```

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "feat: remove shadcn/ui — migration to CACAO design system complete"
```

---

## Self-Review

**Spec coverage check:**
- ✅ CSS tokens (`app.css`) — Task 1
- ✅ `data-theme` dark mode — Task 1 + Task 11
- ✅ `app.blade.php` update — Task 1
- ✅ `Icon.vue` (38 icons) — Task 2
- ✅ `Isotipo.vue` — Task 2
- ✅ `Button.vue` (6 variants × 3 sizes) — Task 3
- ✅ `Badge.vue` (6 variants) — Task 3
- ✅ `Avatar.vue` — Task 3
- ✅ `Card.vue` + `StatCard.vue` — Task 4
- ✅ `AppSidebar.vue` rewrite — Task 5
- ✅ `AppTopbar.vue` — Task 5
- ✅ `AppSidebarLayout.vue` update — Task 5
- ✅ `Dashboard.vue` — Task 6
- ✅ `Toast.vue` + `useToast.ts` — Task 7
- ✅ `Modal.vue` — Task 8
- ✅ `Alert.vue` — Task 9
- ✅ `Roles/Index.vue` + modals — Task 10
- ✅ `useAppearance.ts` → `data-theme` — Task 11
- ✅ Auth layout + Login — Task 12
- ✅ Delete `components/ui/` — Task 13
