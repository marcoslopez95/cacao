/* global React, ReactDOM, Isotipo, Icon, Button, Badge, CacaoTweaks,
   FundamentosSection, ButtonsSection, BadgesSection, FormsSection,
   TablesSection, ListsSection, CardsSection, NavigationSection,
   FeedbackSection, AvatarsSection, DashboardDemo */
const { useState, useEffect } = React;

const NAV = [
  { group: 'Inicio', items: [
    { id: 'fundamentos', label: 'Fundamentos' },
  ]},
  { group: 'Componentes', items: [
    { id: 'buttons', label: 'Botones' },
    { id: 'badges', label: 'Badges y chips' },
    { id: 'forms', label: 'Formularios' },
    { id: 'avatars', label: 'Avatares' },
    { id: 'cards', label: 'Cards' },
    { id: 'tables', label: 'Tablas' },
    { id: 'lists', label: 'Listas' },
    { id: 'navigation', label: 'Navegación' },
    { id: 'feedback', label: 'Feedback' },
  ]},
  { group: 'En contexto', items: [
    { id: 'dashboard', label: 'Dashboard admin · demo' },
  ]},
];

const SECTIONS = {
  fundamentos: { title: 'Fundamentos', comp: FundamentosSection },
  buttons:     { title: 'Botones', comp: ButtonsSection },
  badges:      { title: 'Badges y chips', comp: BadgesSection },
  forms:       { title: 'Formularios', comp: FormsSection },
  avatars:     { title: 'Avatares', comp: AvatarsSection },
  cards:       { title: 'Cards', comp: CardsSection },
  tables:      { title: 'Tablas', comp: TablesSection },
  lists:       { title: 'Listas', comp: ListsSection },
  navigation:  { title: 'Navegación', comp: NavigationSection },
  feedback:    { title: 'Feedback', comp: FeedbackSection },
  dashboard:   { title: 'Dashboard admin · demo', comp: DashboardDemo },
};

const App = () => {
  const [section, setSection] = useState(() => localStorage.getItem('cacao-ds-section') || 'fundamentos');
  const [theme, setTheme] = useState(() => localStorage.getItem('cacao-ds-theme') || 'light');

  useEffect(() => {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('cacao-ds-theme', theme);
  }, [theme]);
  useEffect(() => {
    localStorage.setItem('cacao-ds-section', section);
    window.scrollTo({ top: 0 });
  }, [section]);

  const current = SECTIONS[section] || SECTIONS.fundamentos;
  const Comp = current.comp;
  const isDemo = section === 'dashboard';

  return (
    <div className="app-shell" data-screen-label={`CACAO · ${current.title}`}>
      <aside className="ds-sidebar">
        <div className="ds-sidebar-brand">
          <Isotipo size="iso-28"/>
          <div>
            <div className="wordmark">CACAO</div>
            <div className="tagline">Sistema de diseño</div>
          </div>
        </div>

        {NAV.map(group => (
          <div key={group.group} className="ds-nav">
            <div className="ds-nav-group-title">{group.group}</div>
            {group.items.map(item => (
              <button
                key={item.id}
                className={`ds-nav-item ${section === item.id ? 'active' : ''}`}
                onClick={() => setSection(item.id)}>
                <span className="dot"/>
                <span>{item.label}</span>
              </button>
            ))}
          </div>
        ))}

        <div style={{ padding: '16px 20px', marginTop: 20, borderTop: '1px solid var(--border)' }}>
          <div style={{ fontSize: 11, color:'var(--text-muted)', textTransform:'uppercase', letterSpacing:'0.06em', fontWeight: 600, marginBottom: 10 }}>Tema</div>
          <div className="segmented" style={{ width:'100%' }}>
            <button className={theme==='light'?'active':''} onClick={()=>setTheme('light')} style={{ flex: 1, display:'inline-flex', alignItems:'center', gap: 6, justifyContent:'center' }}>
              <Icon name="sun" size={13}/> Light
            </button>
            <button className={theme==='dark'?'active':''} onClick={()=>setTheme('dark')} style={{ flex: 1, display:'inline-flex', alignItems:'center', gap: 6, justifyContent:'center' }}>
              <Icon name="moon" size={13}/> Dark
            </button>
          </div>
        </div>

        <div style={{ padding: '16px 20px', fontSize: 11, color:'var(--text-muted)', fontFamily:'var(--font-mono)', lineHeight: 1.5 }}>
          Plan maestro v1.0<br/>
          Space Grotesk · Tailwind v4
        </div>
      </aside>

      <main className="ds-main">
        <div className="ds-topbar">
          <nav className="ds-topbar-crumbs">
            <span>CACAO</span>
            <span className="sep">/</span>
            <span>Design System</span>
            <span className="sep">/</span>
            <span className="current">{current.title}</span>
          </nav>
          <span className="ds-topbar-spacer"/>
          <Button variant="ghost" size="sm" icon="code">Guía de uso</Button>
          <button className="theme-toggle" onClick={()=>setTheme(theme==='light'?'dark':'light')}>
            <Icon name={theme==='light'?'moon':'sun'} size={13}/>
            {theme==='light' ? 'Dark' : 'Light'}
          </button>
        </div>

        <div className="ds-content" style={isDemo ? { maxWidth: '100%', padding: '24px' } : undefined}>
          <Comp/>
        </div>
      </main>

      <CacaoTweaks theme={theme}/>
    </div>
  );
};

ReactDOM.createRoot(document.getElementById('app')).render(<App/>);
