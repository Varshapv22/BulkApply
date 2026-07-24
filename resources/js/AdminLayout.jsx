import React, { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { Link, usePage, router } from '@inertiajs/react';
import { ChipIcon, Icons, NotificationBell, PasswordInput } from './components';

const NAV = [
    { href: '/admin', label: 'Dashboard', match: '/admin', exact: true, icon: 'briefcase' },
    { href: '/admin/users', label: 'Users', match: '/admin/users', icon: 'user' },
    { href: '/admin/plans', label: 'Plans', match: '/admin/plans', icon: 'tag' },
    { href: '/admin/subscriptions', label: 'Subscriptions', match: '/admin/subscriptions', icon: 'card' },
    { href: '/admin/payment-requests', label: 'Payment Requests', match: '/admin/payment-requests', icon: 'card' },
    { href: '/admin/applications', label: 'Applications', match: '/admin/applications', icon: 'list' },
    { href: '/admin/resumes', label: 'Resumes', match: '/admin/resumes', icon: 'upload' },
    { href: '/admin/queue', label: 'Queue', match: '/admin/queue', icon: 'clock' },
    { href: '/admin/job-sources', label: 'Job Sources', match: '/admin/job-sources', icon: 'globe' },
    { href: '/admin/extension', label: 'Extension', match: '/admin/extension', icon: 'sparkle' },
    { href: '/admin/reports', label: 'Reports', match: '/admin/reports', icon: 'doc' },
    { href: '/admin/analytics', label: 'Analytics', match: '/admin/analytics', icon: 'target' },
    { href: '/admin/notifications', label: 'Notifications', match: '/admin/notifications', icon: 'alert' },
    { href: '/admin/support', label: 'Support', match: '/admin/support', icon: 'chat' },
    { href: '/admin/cms', label: 'CMS', match: '/admin/cms', icon: 'doc' },
    { href: '/admin/api', label: 'API & Integrations', match: '/admin/api', icon: 'globe' },
    { href: '/admin/webhooks', label: 'Webhooks', match: '/admin/webhooks', icon: 'send' },
    { href: '/admin/settings', label: 'Settings', match: '/admin/settings', icon: 'save' },
    { href: '/admin/security', label: 'Security', match: '/admin/security', icon: 'check' },
    { href: '/admin/audit-logs', label: 'Audit Logs', match: '/admin/audit-logs', icon: 'list' },
    { href: '/admin/backup', label: 'Backup', match: '/admin/backup', icon: 'save' },
    { href: '/admin/storage', label: 'Storage', match: '/admin/storage', icon: 'building' },
    { href: '/admin/monitoring', label: 'Monitoring', match: '/admin/monitoring', icon: 'target' },
    { href: '/admin/logs', label: 'Logs', match: '/admin/logs', icon: 'doc' },
    { href: '/admin/database-tools', label: 'Database Tools', match: '/admin/database-tools', icon: 'globe' },
    { href: '/admin/features', label: 'Feature Management', match: '/admin/features', icon: 'sparkle' },
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

/** Same account-settings modal as the main app shell, kept local so AdminLayout has no dependency on Layout.jsx. */
function ProfileModal({ user, onClose }) {
    const [tab, setTab] = useState('profile');

    const [profileData, setProfileData] = useState({ name: user?.name || '', email: user?.email || '' });
    const [profileErrors, setProfileErrors] = useState({});
    const [profileBusy, setProfileBusy] = useState(false);

    const [passData, setPassData] = useState({ current_password: '', password: '', password_confirmation: '' });
    const [passErrors, setPassErrors] = useState({});
    const [passBusy, setPassBusy] = useState(false);

    const submitProfile = (e) => {
        e.preventDefault();
        setProfileBusy(true);
        setProfileErrors({});
        router.put('/account', profileData, {
            preserveScroll: true,
            onError: (errs) => { setProfileErrors(errs); setProfileBusy(false); },
            onSuccess: () => onClose(),
        });
    };

    const submitPassword = (e) => {
        e.preventDefault();
        setPassBusy(true);
        setPassErrors({});
        router.put('/account/password', passData, {
            preserveScroll: true,
            onError: (errs) => { setPassErrors(errs); setPassBusy(false); },
            onSuccess: () => onClose(),
        });
    };

    const modal = (
        <div
            style={{ position: 'fixed', inset: 0, background: 'rgba(8,10,20,.6)', backdropFilter: 'blur(4px)', zIndex: 9999, display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 20 }}
            onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div style={{ background: 'var(--card)', border: '1px solid var(--border)', borderRadius: 20, maxWidth: 460, width: '100%', maxHeight: '85vh', overflowY: 'auto', padding: 28, position: 'relative', boxShadow: 'var(--shadow-lg)' }}>
                <button className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <h3 style={{ margin: '0 0 20px', fontSize: 18, fontWeight: 700, color: 'var(--heading)' }}>Account Settings</h3>

                <div className="acct-tabs">
                    <button type="button" className={`acct-tab${tab === 'profile' ? ' active' : ''}`} onClick={() => setTab('profile')}>
                        Edit Profile
                    </button>
                    <button type="button" className={`acct-tab${tab === 'password' ? ' active' : ''}`} onClick={() => setTab('password')}>
                        Change Password
                    </button>
                </div>

                {tab === 'profile' ? (
                    <form onSubmit={submitProfile} style={{ marginTop: 20 }}>
                        <div style={{ marginBottom: 14 }}>
                            <label>Name</label>
                            <input type="text" autoFocus value={profileData.name} onChange={(e) => setProfileData(d => ({ ...d, name: e.target.value }))} />
                            {profileErrors.name && <p className="field-error">{profileErrors.name}</p>}
                        </div>
                        <div style={{ marginBottom: 20 }}>
                            <label>Email</label>
                            <input type="email" value={profileData.email} onChange={(e) => setProfileData(d => ({ ...d, email: e.target.value }))} />
                            {profileErrors.email && <p className="field-error">{profileErrors.email}</p>}
                        </div>
                        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                            <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={profileBusy}>
                                {profileBusy ? 'Saving…' : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                ) : (
                    <form onSubmit={submitPassword} style={{ marginTop: 20 }}>
                        <div style={{ marginBottom: 14 }}>
                            <label>Current Password</label>
                            <PasswordInput autoComplete="current-password" autoFocus value={passData.current_password} onChange={(e) => setPassData(d => ({ ...d, current_password: e.target.value }))} />
                            {passErrors.current_password && <p className="field-error">{passErrors.current_password}</p>}
                        </div>
                        <div style={{ marginBottom: 14 }}>
                            <label>New Password</label>
                            <PasswordInput autoComplete="new-password" value={passData.password} onChange={(e) => setPassData(d => ({ ...d, password: e.target.value }))} />
                            {passErrors.password && <p className="field-error">{passErrors.password}</p>}
                        </div>
                        <div style={{ marginBottom: 20 }}>
                            <label>Confirm New Password</label>
                            <PasswordInput autoComplete="new-password" value={passData.password_confirmation} onChange={(e) => setPassData(d => ({ ...d, password_confirmation: e.target.value }))} />
                        </div>
                        <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                            <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
                            <button type="submit" className="btn btn-primary" disabled={passBusy}>
                                {passBusy ? 'Updating…' : 'Update Password'}
                            </button>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );

    return createPortal(modal, document.body);
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
    const [profileModalOpen, setProfileModalOpen] = useState(false);

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
                    <button type="button" className="nav-item" style={{ width: '100%' }} onClick={() => router.post('/logout')}>
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
                    {user && (
                        <NotificationBell
                            unreadCount={props.unreadNotifications || 0}
                            recentUrl="/admin/notifications/recent"
                            markReadUrl={(id) => `/admin/notifications/${id}/read`}
                            markAllReadUrl="/admin/notifications/mark-all-read"
                            viewAllHref="/admin/notifications"
                            getMessage={(n) => n.message}
                        />
                    )}
                    {user && (
                        <div
                            className="topbar-user"
                            onClick={() => setProfileModalOpen(true)}
                            title="Edit account"
                            role="button"
                            tabIndex={0}
                            onKeyDown={(e) => e.key === 'Enter' && setProfileModalOpen(true)}
                        >
                            <span className="avatar">{initials}</span>
                            <span className="topbar-user-name">{user?.name || 'Admin'}</span>
                        </div>
                    )}
                    <ProgressBar />
                </header>

                <div className="content animate-enter">
                    {children}
                </div>
            </div>

            <ToastHost />

            {profileModalOpen && (
                <ProfileModal user={user} onClose={() => setProfileModalOpen(false)} />
            )}
        </div>
    );
}
