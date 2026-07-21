import React, { useState, useEffect, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';

const ACCENTS = [
    { id: 'indigo',  label: 'Indigo',  c: ['#6366f1', '#a855f7'] },
    { id: 'violet',  label: 'Violet',  c: ['#8b5cf6', '#d946ef'] },
    { id: 'emerald', label: 'Emerald', c: ['#10b981', '#22d3ee'] },
    { id: 'ocean',   label: 'Ocean',   c: ['#0ea5e9', '#6366f1'] },
    { id: 'sunset',  label: 'Sunset',  c: ['#fb7185', '#f59e0b'] },
    { id: 'rose',    label: 'Rose',    c: ['#f43f5e', '#ec4899'] },
];

const NAV = [
    { href: '/dashboard',    label: 'Dashboard', match: '/dashboard', icon: 'grid' },
    { href: '/search',       label: 'Find Jobs', match: '/search',    icon: 'search' },
    { href: '/jobs',         label: 'Applications', match: '/jobs',   icon: 'list' },
    { href: '/resume-check', label: 'Resume Check', match: '/resume-check', icon: 'doc' },
    { href: '/templates',    label: 'Templates', match: '/templates', icon: 'mail' },
    { href: '/profile',      label: 'Settings',  match: '/profile',   icon: 'cog' },
];

function Icon({ name }) {
    const paths = {
        grid: <><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></>,
        search: <><circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></>,
        list: <><line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" /><line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" /></>,
        mail: <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 5L2 7" /></>,
        doc: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><path d="m9 15 2 2 4-4" /></>,
        cog: <><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" /></>,
    };
    return (
        <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {paths[name]}
        </svg>
    );
}

function useTheme() {
    const getCurrent = () => {
        const attr = document.documentElement.getAttribute('data-theme');
        if (attr) return attr;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    };
    const [theme, setTheme] = useState(getCurrent);
    const toggle = () => {
        const next = getCurrent() === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        setTheme(next);
    };
    return [theme, toggle];
}

function ThemeMenu({ theme, onToggleTheme }) {
    const [openMenu, setOpenMenu] = useState(false);
    const [accent, setAccent] = useState(() => document.documentElement.getAttribute('data-accent') || 'indigo');
    const ref = useRef(null);

    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpenMenu(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const pick = (id) => {
        document.documentElement.setAttribute('data-accent', id);
        localStorage.setItem('accent', id);
        setAccent(id);
    };
    const setMode = (mode) => { if ((theme === 'dark') !== (mode === 'dark')) onToggleTheme(); };

    return (
        <div className="theme-menu-wrap" ref={ref}>
            <button className="icon-btn" onClick={() => setOpenMenu((o) => !o)} title="Theme &amp; colours" aria-label="Theme and colours">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="13.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" /><circle cx="17.5" cy="10.5" r="1.5" fill="currentColor" stroke="none" />
                    <circle cx="8.5" cy="7.5" r="1.5" fill="currentColor" stroke="none" /><circle cx="6.5" cy="12.5" r="1.5" fill="currentColor" stroke="none" />
                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c1.7 0 2.5-1.3 2.5-2.5 0-.6-.2-1.1-.6-1.5-.4-.4-.6-.9-.6-1.5 0-1.2.8-2 2-2H17c2.8 0 5-2.2 5-5 0-4.4-4.5-7.5-10-7.5z" />
                </svg>
            </button>
            {openMenu && (
                <div className="theme-pop">
                    <div className="pop-label">Accent theme</div>
                    <div className="swatches">
                        {ACCENTS.map((a) => (
                            <button key={a.id} type="button" className={`swatch${accent === a.id ? ' active' : ''}`} onClick={() => pick(a.id)}>
                                <span className="dot" style={{ background: `linear-gradient(135deg, ${a.c[0]}, ${a.c[1]})` }} />
                                {a.label}
                            </button>
                        ))}
                    </div>
                    <div className="mode-row">
                        <button type="button" className={`mode-btn${theme !== 'dark' ? ' active' : ''}`} onClick={() => setMode('light')}>☀️ Light</button>
                        <button type="button" className={`mode-btn${theme === 'dark' ? ' active' : ''}`} onClick={() => setMode('dark')}>🌙 Dark</button>
                    </div>
                </div>
            )}
        </div>
    );
}

let toastSeq = 0;

/**
 * Always-visible toast notifications for every form/button submission —
 * anchored to the viewport (not the page's scroll position), so a success or
 * error message is seen even if the button that triggered it was scrolled
 * far down a long table. Reads Laravel session flash from every completed
 * Inertia visit, and also surfaces validation failures as a toast.
 */
function ToastHost() {
    const [toasts, setToasts] = useState([]);

    const push = (type, message) => {
        if (!message) return;
        const id = ++toastSeq;
        setToasts((t) => [...t, { id, type, message }]);
        setTimeout(() => setToasts((t) => t.filter((x) => x.id !== id)), 6000);
    };
    const dismiss = (id) => setToasts((t) => t.filter((x) => x.id !== id));

    useEffect(() => {
        const offSuccess = router.on('success', (event) => {
            const flash = event.detail.page.props.flash || {};
            push('success', flash.status);
            push('error', flash.error);
        });
        const offError = router.on('error', (event) => {
            const errors = Object.values(event.detail.errors || {});
            if (errors.length) push('error', errors[0]);
        });
        return () => { offSuccess(); offError(); };
    }, []);

    if (toasts.length === 0) return null;

    return (
        <div className="toast-host">
            {toasts.map((t) => (
                <div key={t.id} className={`toast toast-${t.type}`}>
                    <span className="toast-ico">{t.type === 'success' ? '✓' : '!'}</span>
                    <span className="toast-msg">{t.message}</span>
                    <button className="toast-close" onClick={() => dismiss(t.id)} aria-label="Dismiss">✕</button>
                </div>
            ))}
        </div>
    );
}

/** Thin animated bar under the topbar while any Inertia request is in flight. */
function ProgressBar() {
    const [active, setActive] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setActive(true));
        const offFinish = router.on('finish', () => setActive(false));
        return () => { offStart(); offFinish(); };
    }, []);

    if (!active) return null;
    return <div className="topbar-progress"><div className="topbar-progress-bar" /></div>;
}

export default function Layout({ children }) {
    const { props, url } = usePage();
    const user = props.auth?.user;
    const errors = props.errors || {};
    // Open the Gmail account we actually send FROM (not the browser default).
    const gmailSentUrl = props.mailFrom
        ? `https://mail.google.com/mail/u/?authuser=${encodeURIComponent(props.mailFrom)}#sent`
        : 'https://mail.google.com/mail/u/0/#sent';
    const [theme, toggleTheme] = useTheme();
    const [open, setOpen] = useState(false);
    const [isCollapsed, setIsCollapsed] = useState(() => localStorage.getItem('sidebar_collapsed') === 'true');
    const toggleCollapse = () => {
        const next = !isCollapsed;
        setIsCollapsed(next);
        localStorage.setItem('sidebar_collapsed', next);
    };

    // Close mobile sidebar on navigation.
    useEffect(() => { setOpen(false); }, [url]);

    const current = NAV.find((n) => url.startsWith(n.match));
    const errorList = Object.values(errors);

    const initials = (user?.name || 'U')
        .split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

    return (
        <div className={`app-shell${props.impersonating ? ' has-impersonation-banner' : ''}`}>
            {props.impersonating && (
                <div className="impersonation-banner">
                    Viewing as {user?.name} ({user?.email}) —
                    <button type="button" onClick={() => router.post('/admin/impersonate/return')}>Return to admin</button>
                </div>
            )}
            <aside className={`sidebar${open ? ' open' : ''}${isCollapsed ? ' collapsed' : ''}`}>
                <div className="sidebar-brand">
                    <div className="brand-inner">
                        <span className="dot">B</span> <span className="brand-text">Bulk<span>Apply</span></span>
                    </div>
                    <button className="icon-btn desktop-toggle" onClick={toggleCollapse} aria-label="Toggle Sidebar" title={isCollapsed ? 'Expand menu' : 'Collapse menu'}>
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
                <nav className="sidebar-nav">
                    <div className="sidebar-section">Menu</div>
                    {NAV.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`nav-item${url.startsWith(item.match) ? ' active' : ''}`}
                            title={isCollapsed ? item.label : undefined}
                        >
                            <Icon name={item.icon} /> <span className="nav-label">{item.label}</span>
                        </Link>
                    ))}
                    <div className="sidebar-section">Tools</div>
                    <Link href="/extension" className={`nav-item${url.startsWith('/extension') ? ' active' : ''}`} title={isCollapsed ? 'Browser Extension' : undefined}>
                        <Icon name="cog" /> <span className="nav-label">Browser Extension</span>
                    </Link>
                    {user?.isAdmin && (
                        <Link href="/admin" className="nav-item" title={isCollapsed ? 'Admin Panel' : undefined}>
                            <Icon name="cog" /> <span className="nav-label">Admin Panel</span>
                        </Link>
                    )}
                    <a className="nav-item" href={gmailSentUrl} target="_blank" rel="noopener" title={isCollapsed ? 'Gmail — Sent' : undefined}>
                        <Icon name="mail" /> <span className="nav-label">Gmail — Sent ↗</span>
                    </a>
                </nav>
                <div className="sidebar-footer">
                    <div className="sidebar-user">
                        <span className="avatar">{initials}</span>
                        <div className="user-info">
                            <div className="name">{user?.name || 'User'}</div>
                            <div className="email">{user?.email}</div>
                        </div>
                    </div>
                    <button
                        type="button"
                        className="nav-item"
                        style={{ width: '100%', marginTop: 4 }}
                        onClick={() => router.post('/logout')}
                    >
                        <svg className="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        <span className="nav-label">Logout</span>
                    </button>
                </div>
            </aside>

            {open && <div className="sidebar-backdrop" onClick={() => setOpen(false)} />}

            <div className={`main${isCollapsed ? ' collapsed' : ''}`}>
                <header className="topbar">
                    <button className="hamburger" onClick={() => setOpen(true)} aria-label="Open menu">☰</button>
                    <span className="page-title">{current?.label || 'BulkApply'}</span>
                    <div className="spacer" />
                    <button className="icon-btn" onClick={toggleTheme} title="Toggle light / dark" aria-label="Toggle theme">
                        {theme === 'dark' ? '☀️' : '🌙'}
                    </button>
                    <ThemeMenu theme={theme} onToggleTheme={toggleTheme} />
                    <ProgressBar />
                </header>

                <div className="content animate-enter">
                    {errorList.length > 0 && (
                        <div className="alert alert-error">
                            <div className="alert-body">
                                <strong>Please fix:</strong>
                                <ul>{errorList.map((e, i) => <li key={i}>{e}</li>)}</ul>
                            </div>
                        </div>
                    )}
                    {children}
                </div>
            </div>

            <ToastHost />
        </div>
    );
}
