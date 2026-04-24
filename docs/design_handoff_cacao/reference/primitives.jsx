/* global React */
const { useState, useEffect, useMemo, useRef, createContext, useContext } = React;

// ================= ICONS =================
const Icon = ({ name, size = 16, stroke = 1.75 }) => {
  const paths = {
    search: <><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></>,
    plus: <><path d="M12 5v14M5 12h14"/></>,
    check: <><path d="M20 6 9 17l-5-5"/></>,
    x: <><path d="M18 6 6 18M6 6l12 12"/></>,
    chevronDown: <><path d="m6 9 6 6 6-6"/></>,
    chevronRight: <><path d="m9 6 6 6-6 6"/></>,
    chevronLeft: <><path d="m15 6-6 6 6 6"/></>,
    chevronUp: <><path d="m18 15-6-6-6 6"/></>,
    arrowRight: <><path d="M5 12h14M13 5l7 7-7 7"/></>,
    filter: <><path d="M3 6h18M6 12h12M10 18h4"/></>,
    download: <><path d="M12 3v12M7 10l5 5 5-5M4 21h16"/></>,
    upload: <><path d="M12 21V9M7 14l5-5 5 5M4 3h16"/></>,
    file: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></>,
    folder: <><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></>,
    user: <><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></>,
    users: <><circle cx="9" cy="8" r="4"/><path d="M3 21a6 6 0 0 1 12 0"/><circle cx="17" cy="7" r="3"/><path d="M21 18a5 5 0 0 0-7-4.5"/></>,
    book: <><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5z"/><path d="M4 19.5V22h16"/></>,
    building: <><rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h2M14 7h2M8 11h2M14 11h2M8 15h2M14 15h2M10 21v-4h4v4"/></>,
    calendar: <><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></>,
    clock: <><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></>,
    edit: <><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/></>,
    trash: <><path d="M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6M10 11v6M14 11v6"/></>,
    eye: <><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></>,
    more: <><circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/></>,
    moreV: <><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/></>,
    bell: <><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21a2 2 0 0 0 4 0"/></>,
    info: <><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></>,
    alert: <><path d="M12 3 1 21h22z"/><path d="M12 10v5M12 18h.01"/></>,
    home: <><path d="m3 10 9-7 9 7v10a2 2 0 0 1-2 2h-4v-7H9v7H5a2 2 0 0 1-2-2z"/></>,
    grid: <><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></>,
    chart: <><path d="M3 3v18h18"/><path d="m7 15 4-6 3 3 5-7"/></>,
    logout: <><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></>,
    sun: <><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/></>,
    moon: <><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8"/></>,
    code: <><path d="m16 18 6-6-6-6M8 6l-6 6 6 6"/></>,
    mail: <><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></>,
    phone: <><path d="M5 4h4l2 5-3 2a11 11 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></>,
    map: <><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></>,
    star: <><path d="m12 2 3.1 6.9 7.4.8-5.5 5.1 1.6 7.3L12 18.3 5.4 22.1l1.6-7.3L1.5 9.7l7.4-.8z"/></>,
  };
  const p = paths[name];

  if (!p) {
return null;
}

  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none"
         stroke="currentColor" strokeWidth={stroke} strokeLinecap="round" strokeLinejoin="round">
      {p}
    </svg>
  );
};

// ================= ISOTIPO =================
const Isotipo = ({ size = 'iso-40' }) => (
  <div className={`iso ${size}`} role="img" aria-label="CACAO isotipo">
    <span className="cell"/>
    <span className="cell"/>
    <span className="cell accent"/>{/* [0,2] FIJO */}
    <span className="cell"/>
    <span className="cell"/>
    <span className="cell empty"/>{/* [1,2] VACÍO */}
    <span className="cell"/>
    <span className="cell"/>
    <span className="cell"/>
  </div>
);

// ================= BUTTON =================
const Button = ({ variant = 'primary', size = 'md', icon, iconRight, loading, disabled, children, iconOnly, className = '', ...rest }) => {
  const classes = [
    'btn',
    `btn-${variant}`,
    `btn-${size}`,
    iconOnly ? 'btn-icon' : '',
    className
  ].join(' ');

  return (
    <button className={classes} disabled={disabled || loading} {...rest}>
      {loading && <span className="spin" />}
      {!loading && icon && <Icon name={icon} size={size === 'sm' ? 13 : size === 'lg' ? 16 : 14} />}
      {!iconOnly && children}
      {!loading && iconRight && <Icon name={iconRight} size={size === 'sm' ? 13 : 14} />}
    </button>
  );
};

// ================= BADGE =================
const Badge = ({ variant = 'neutral', dot, children }) => (
  <span className={`badge badge-${variant}`}>
    {dot && <span className="bdot" />}
    {children}
  </span>
);

// ================= Helpers =================
const Box = ({ className = '', children, ...rest }) => <div className={className} {...rest}>{children}</div>;

// Export globals for other babel scripts
Object.assign(window, { Icon, Isotipo, Button, Badge, Box });
