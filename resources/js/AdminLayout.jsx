import React, { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { ChipIcon, Icons } from './components';

const NAV = [
    { href: '/admin', label: 'Dashboard', match: '/admin', exact: true, icon: 'briefcase' },
    { href: '/admin/users', label: 'Users', match: '/admin/users', icon: 'user' },
    { href: '/admin/plans', label: 'Plans', match: '/admin/plans', icon: 'tag' },
    { href: '/admin/applications', label: 'Applications', match: '/admin/applications', icon: 'list' },
    { href: '/admin/resumes', label: 'Resumes', match: '/admin/resumes', icon: 'upload' },
    { href: '/admin/queue', label: 'Queue', match: '/admin/queue', icon: 'clock' },
    { href: '/admin/job-sources', label: 'Job Sources', match: '/admin/job-sources', icon: 'globe' },
    { href: '/admin/extension', label: 'Extension', match: '/admin/extension', icon: 'sparkle' },
];

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

/** Same toast/progress behaviour as the main app shell, kept local so AdminLayout has no dependency on Layout.jsx. */
let toastSeq = 0;
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

export default function AdminLayout({ children }) {
    const { props, url } = usePage();
    const user = props.auth?.user;
    const [theme, toggleTheme] = useTheme();
    const [open, setOpen] = useState(false);

    useEffect(() => { setOpen(false); }, [url]);

    const isActive = (item) => (item.exact ? url === item.match : url.startsWith(item.match));
    const current = NAV.find(isActive);

    const initials = (user?.name || 'A')
        .split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

    return (
        <div className="app-shell">
            <aside className={`sidebar${open ? ' open' : ''}`}>
                <div className="sidebar-brand">
                    <div className="brand-inner">
                        <span className="dot">B</span> <span className="brand-text">Bulk<span>Apply</span> <small style={{ opacity: 0.6, fontWeight: 400 }}>Admin</small></span>
                    </div>
                </div>
                <nav className="sidebar-nav">
                    <div className="sidebar-section">Admin</div>
                    {NAV.map((item) => (
                        <Link key={item.href} href={item.href} className={`nav-item${isActive(item) ? ' active' : ''}`}>
                            <ChipIcon icon={Icons[item.icon]} /> <span className="nav-label">{item.label}</span>
                        </Link>
                    ))}
                    <div className="sidebar-section">Back to app</div>
                    <Link href="/dashboard" className="nav-item">
                        <ChipIcon icon={Icons.list} /> <span className="nav-label">Return to dashboard</span>
                    </Link>
                </nav>
                <div className="sidebar-footer">
                    <div className="sidebar-user">
                        <span className="avatar">{initials}</span>
                        <div className="user-info">
                            <div className="name">{user?.name || 'Admin'}</div>
                            <div className="email">{user?.email}</div>
                        </div>
                    </div>
                    <button type="button" className="nav-item" style={{ width: '100%', marginTop: 4 }} onClick={() => router.post('/logout')}>
                        <ChipIcon icon={Icons.xCircle} /> <span className="nav-label">Logout</span>
                    </button>
                </div>
            </aside>

            {open && <div className="sidebar-backdrop" onClick={() => setOpen(false)} />}

            <div className="main">
                <header className="topbar">
                    <button className="hamburger" onClick={() => setOpen(true)} aria-label="Open menu">☰</button>
                    <span className="page-title">{current?.label || 'Admin'}</span>
                    <div className="spacer" />
                    <button className="icon-btn" onClick={toggleTheme} title="Toggle light / dark" aria-label="Toggle theme">
                        {theme === 'dark' ? '☀️' : '🌙'}
                    </button>
                    <ProgressBar />
                </header>

                <div className="content animate-enter">
                    {children}
                </div>
            </div>

            <ToastHost />
        </div>
    );
}
