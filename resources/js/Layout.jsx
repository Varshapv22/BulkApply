import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Link, usePage, router } from '@inertiajs/react';
import { NotificationBell, PasswordInput, UpiPaymentModal, formatDuration } from './components';

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
    { href: '/replies',      label: 'Replies',   match: '/replies',   icon: 'inbox', badge: 'unreadReplies' },
    { href: '/resume-check', label: 'Resume Check', match: '/resume-check', icon: 'doc' },
    { href: '/templates',    label: 'Templates', match: '/templates', icon: 'mail' },
    { href: '/billing',      label: 'Billing',   match: '/billing',   icon: 'card' },
    { href: '/profile',      label: 'Settings',  match: '/profile',   icon: 'cog' },
];

function Icon({ name, className = 'h-[18px] w-[18px] shrink-0' }) {
    const paths = {
        grid: <><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /></>,
        search: <><circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></>,
        list: <><line x1="8" y1="6" x2="21" y2="6" /><line x1="8" y1="12" x2="21" y2="12" /><line x1="8" y1="18" x2="21" y2="18" /><line x1="3" y1="6" x2="3.01" y2="6" /><line x1="3" y1="12" x2="3.01" y2="12" /><line x1="3" y1="18" x2="3.01" y2="18" /></>,
        mail: <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 5L2 7" /></>,
        doc: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><path d="m9 15 2 2 4-4" /></>,
        cog: <><circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" /></>,
        card: <><rect x="2" y="5" width="20" height="14" rx="2" /><line x1="2" y1="10" x2="22" y2="10" /></>,
        inbox: <><path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" /></>,
        puzzle: <><path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.48-.968-.925a2.501 2.501 0 1 0-3.214 3.214c.446.166.855.497.925.968a.979.979 0 0 1-.276.837l-1.61 1.61a2.404 2.404 0 0 1-3.408 0l-1.568-1.568a1.026 1.026 0 0 0-.877-.29c-.493.074-.84.504-1.02.968a2.5 2.5 0 1 1-3.237-3.237c.464-.18.894-.527.967-1.02a1.026 1.026 0 0 0-.289-.877l-1.568-1.568a2.404 2.404 0 0 1 0-3.408l1.611-1.611c.24-.24.581-.353.917-.312.492.06.844.484 1.02.951a2.5 2.5 0 1 0 3.259-3.259c-.467-.176-.891-.528-.951-1.02-.041-.336.072-.677.312-.917l1.611-1.611a2.404 2.404 0 0 1 3.408 0l1.568 1.568c.23.23.556.338.877.29.493-.074.84-.504 1.02-.968a2.5 2.5 0 1 1 3.237 3.237c-.464.18-.894.527-.967 1.02z" /></>,
        shield: <><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></>,
    };
    return (
        <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {paths[name]}
        </svg>
    );
}

function ThemeIcon({ icon }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {icon === 'sun'
                ? <><circle cx="12" cy="12" r="4" /><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" /></>
                : <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />}
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

    const popClass = 'absolute right-0 top-12 z-[60] w-[236px] animate-[fadeUp_.16s_ease_both] rounded-2xl border border-border bg-card p-3.5 shadow-app-lg '
        + 'max-sm:fixed max-sm:left-auto max-sm:right-2.5 max-sm:top-[calc(var(--banner-h,0px)+var(--topbar-h,66px)+6px)] max-sm:w-[min(360px,calc(100vw-20px))]';
    const modeBtnClass = (active) => `flex flex-1 items-center justify-center gap-1.5 rounded-[11px] border px-2 py-2.5 text-[12.5px] font-semibold [&_svg]:h-3.5 [&_svg]:w-3.5 [&_svg]:shrink-0 ${
        active ? 'border-primary bg-primary-soft text-primary-dark' : 'border-border bg-card-2 text-text'
    }`;

    return (
        <div className="relative" ref={ref}>
            <button
                className={`inline-flex h-10 w-10 items-center justify-center rounded-xl transition-all active:scale-95 ${
                    openMenu ? 'bg-primary-soft text-primary' : 'text-muted hover:bg-hover hover:text-heading'
                }`}
                onClick={() => setOpenMenu((o) => !o)}
                title="Theme &amp; colours"
                aria-label="Theme and colours"
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <circle cx="13.5" cy="6.5" r="1.5" fill="currentColor" stroke="none" /><circle cx="17.5" cy="10.5" r="1.5" fill="currentColor" stroke="none" />
                    <circle cx="8.5" cy="7.5" r="1.5" fill="currentColor" stroke="none" /><circle cx="6.5" cy="12.5" r="1.5" fill="currentColor" stroke="none" />
                    <path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c1.7 0 2.5-1.3 2.5-2.5 0-.6-.2-1.1-.6-1.5-.4-.4-.6-.9-.6-1.5 0-1.2.8-2 2-2H17c2.8 0 5-2.2 5-5 0-4.4-4.5-7.5-10-7.5z" />
                </svg>
            </button>
            {openMenu && (
                <div className={popClass}>
                    <div className="mx-0.5 mb-2.5 text-[11px] font-bold uppercase tracking-wider text-muted">Accent theme</div>
                    <div className="grid grid-cols-3 gap-2.5">
                        {ACCENTS.map((a) => (
                            <button
                                key={a.id}
                                type="button"
                                onClick={() => pick(a.id)}
                                className={`flex flex-col items-center gap-1.5 rounded-xl border bg-card-2 px-1 py-2 text-[11px] font-semibold transition-[border-color,transform] hover:-translate-y-0.5 hover:border-border-strong ${
                                    accent === a.id ? 'border-primary text-heading shadow-[0_0_0_3px_var(--ring)]' : 'border-border text-muted'
                                }`}
                            >
                                <span className="h-[30px] w-[30px] rounded-full" style={{ background: `linear-gradient(135deg, ${a.c[0]}, ${a.c[1]})` }} />
                                {a.label}
                            </button>
                        ))}
                    </div>
                    <div className="mt-3.5 flex gap-2">
                        <button type="button" className={modeBtnClass(theme !== 'dark')} onClick={() => setMode('light')}>
                            <ThemeIcon icon="sun" /> Light
                        </button>
                        <button type="button" className={modeBtnClass(theme === 'dark')} onClick={() => setMode('dark')}>
                            <ThemeIcon icon="moon" /> Dark
                        </button>
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
            className="modal-overlay"
            onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}
        >
            <div className="modal modal-sm">
                <button className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <h3 className="modal-title">Account Settings</h3>

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
                        <div className="modal-actions">
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
                        <div className="modal-actions">
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

/** Thin animated bar under the topbar while any Inertia request is in flight. */
/**
 * The standard "visit in progress" bar used by YouTube/GitHub/Linear/Vercel:
 * a thin, flat, full-width fill at the very top of the viewport. Inertia
 * rarely reports a real byte percentage (our JSON responses seldom send
 * Content-Length), so this simulates a decelerating trickle up to 94% the
 * same way NProgress does, and snaps to 100% the instant the visit actually
 * finishes — a real progress event (if one arrives) overrides the simulation
 * whenever it's further along.
 */
function ProgressBar() {
    const [state, setState] = useState({ active: false, percent: 0 });
    const timerRef = useRef(null);

    useEffect(() => {
        const stopTimer = () => { if (timerRef.current) { clearInterval(timerRef.current); timerRef.current = null; } };

        const offStart = router.on('start', () => {
            stopTimer();
            setState({ active: true, percent: 8 });
            timerRef.current = setInterval(() => {
                setState((s) => {
                    if (!s.active || s.percent >= 94) return s;
                    const step = s.percent < 40 ? Math.random() * 8 : s.percent < 70 ? Math.random() * 4 : Math.random() * 1.5;
                    return { active: true, percent: Math.min(94, s.percent + step) };
                });
            }, 220);
        });
        const offProgress = router.on('progress', (event) => {
            const pct = event.detail?.progress?.percentage;
            if (typeof pct === 'number') setState((s) => (s.active ? { active: true, percent: Math.max(s.percent, pct) } : s));
        });
        const offFinish = router.on('finish', () => {
            stopTimer();
            setState((s) => ({ ...s, percent: 100 }));
            setTimeout(() => setState({ active: false, percent: 0 }), 300);
        });

        return () => { offStart(); offProgress(); offFinish(); stopTimer(); };
    }, []);

    if (!state.active) return null;
    return (
        <div className="pointer-events-none fixed inset-x-0 top-[var(--banner-h,0px)] z-[100] h-[3px] bg-transparent">
            <div
                className="h-full bg-gradient-to-r from-primary to-primary-2 transition-[width] duration-200 ease-out"
                style={{ width: `${state.percent}%` }}
            />
        </div>
    );
}

function BlockingModal({ children }) {
    return createPortal(
        <div className="modal-overlay" style={{ zIndex: 9999, cursor: 'default' }}>
            <div className="modal" style={{ maxWidth: 700, width: '100%' }}>
                {children}
            </div>
        </div>,
        document.body
    );
}

function TrialExpiredModal({ plans, upiId, upiPayeeName, pendingPlanIds, currencySymbol }) {
    const [payingPlan, setPayingPlan] = useState(null);
    const [justSubmittedId, setJustSubmittedId] = useState(null);
    const pending = new Set([...(pendingPlanIds || []), ...(justSubmittedId ? [justSubmittedId] : [])]);

    return (
        <BlockingModal>
            <div style={{ textAlign: 'center', padding: '8px 0 20px' }}>
                <div style={{ fontSize: 42, marginBottom: 8 }}>⏰</div>
                <h2 className="modal-title" style={{ fontSize: 22, marginBottom: 6 }}>Your Free Trial Has Ended</h2>
                <p style={{ color: 'var(--muted)', fontSize: 14 }}>
                    Your 7-day free trial is over. Choose a plan below to continue using BulkApply.
                </p>
            </div>

            {plans.length === 0 ? (
                <p style={{ textAlign: 'center', color: 'var(--muted)', padding: '20px 0' }}>
                    No plans are available right now. Please contact support.
                </p>
            ) : (
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(180px, 100%), 1fr))', gap: 14, marginTop: 8 }}>
                    {plans.map((plan) => (
                        <div key={plan.id} className="card card-pad-sm" style={{ border: '1.5px solid var(--border-strong)', display: 'flex', flexDirection: 'column', gap: 8 }}>
                            <div style={{ fontWeight: 700, fontSize: 15, color: 'var(--heading)' }}>{plan.name}</div>
                            <div style={{ fontSize: 22, fontWeight: 800, color: 'var(--primary)' }}>
                                {currencySymbol}{plan.price}
                                <span style={{ fontSize: 12, fontWeight: 500, color: 'var(--muted)' }}>
                                    {' '}/ {formatDuration(plan.duration_days)}
                                </span>
                            </div>
                            <button
                                type="button"
                                className="btn btn-primary btn-block"
                                disabled={pending.has(plan.id)}
                                onClick={() => setPayingPlan(plan)}
                                style={{ marginTop: 'auto' }}
                            >
                                {pending.has(plan.id) ? 'Pending verification' : 'Pay via UPI'}
                            </button>
                        </div>
                    ))}
                </div>
            )}

            <p style={{ textAlign: 'center', color: 'var(--muted)', fontSize: 13, marginTop: 20 }}>
                {pending.size > 0 ? 'An admin will verify your payment and activate your plan shortly.' : 'Pick a plan above and pay via UPI to request an upgrade.'}
            </p>

            <div style={{ textAlign: 'center', marginTop: 16 }}>
                <button type="button" className="btn btn-ghost" onClick={() => router.post('/logout')}>Log out</button>
            </div>

            {payingPlan && (
                <UpiPaymentModal
                    plan={payingPlan}
                    upiId={upiId}
                    upiPayeeName={upiPayeeName}
                    currencySymbol={currencySymbol}
                    onClose={() => setPayingPlan(null)}
                    onSubmitted={() => setJustSubmittedId(payingPlan.id)}
                />
            )}
        </BlockingModal>
    );
}

function OnboardingModal({ onClose }) {
    return (
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className="modal modal-sm">
                <button className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <div style={{ textAlign: 'center', padding: '8px 0 4px' }}>
                    <div style={{ fontSize: 38, marginBottom: 10 }}>👋</div>
                    <h2 className="modal-title" style={{ fontSize: 20, marginBottom: 6 }}>Welcome to BulkApply!</h2>
                    <p style={{ color: 'var(--muted)', fontSize: 14, lineHeight: 1.6 }}>
                        Add your name, phone, location, and a resume so we can personalise and send applications on your behalf.
                    </p>
                </div>
                <div style={{ display: 'flex', gap: 10, justifyContent: 'center', marginTop: 20, flexWrap: 'wrap' }}>
                    <button type="button" className="btn btn-ghost" onClick={onClose}>Maybe later</button>
                    <button type="button" className="btn btn-primary" onClick={() => router.visit('/profile')}>Complete Profile</button>
                </div>
            </div>
        </div>
    );
}

function AccountSuspendedModal() {
    return (
        <BlockingModal>
            <div style={{ textAlign: 'center', padding: '24px 0' }}>
                <div style={{ fontSize: 42, marginBottom: 12 }}>🚫</div>
                <h2 className="modal-title" style={{ fontSize: 22, marginBottom: 8 }}>Account Suspended</h2>
                <p style={{ color: 'var(--muted)', fontSize: 14, maxWidth: 380, margin: '0 auto' }}>
                    Your account has been suspended by an administrator. Please contact support to resolve this.
                </p>
            </div>
        </BlockingModal>
    );
}

export default function Layout({ children }) {
    const { props, url } = usePage();
    const user = props.auth?.user;
    const trial = props.trial;
    const plans = props.plans || [];
    const errors = props.errors || {};
    const needsOnboarding = props.needsOnboarding;
    // Open the Gmail account we actually send FROM (not the browser default).
    const gmailSentUrl = props.mailFrom
        ? `https://mail.google.com/mail/u/?authuser=${encodeURIComponent(props.mailFrom)}#sent`
        : 'https://mail.google.com/mail/u/0/#sent';
    const [theme, toggleTheme] = useTheme();
    const [open, setOpen] = useState(false);
    const [profileModalOpen, setProfileModalOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const userMenuRef = useRef(null);
    const [onboardingDismissed, setOnboardingDismissed] = useState(false);
    const [isCollapsed, setIsCollapsed] = useState(() => localStorage.getItem('sidebar_collapsed') === 'true');
    const toggleCollapse = () => {
        const next = !isCollapsed;
        setIsCollapsed(next);
        localStorage.setItem('sidebar_collapsed', next);
    };

    // Close mobile sidebar on navigation.
    useEffect(() => { setOpen(false); }, [url]);

    // Close the user menu on outside click.
    useEffect(() => {
        const onDoc = (e) => { if (userMenuRef.current && !userMenuRef.current.contains(e.target)) setUserMenuOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const current = NAV.find((n) => url.startsWith(n.match));
    const errorList = Object.values(errors);

    const initials = (user?.name || 'U')
        .split(' ').map((w) => w[0]).slice(0, 2).join('').toUpperCase();

    // Sidebar/topbar style helpers — collapse (icon-only) only ever applies at
    // lg+; below that the sidebar is always a full-width mobile drawer.
    const navItemClass = (active) => `group relative flex items-center gap-3 rounded-[11px] px-3.5 py-2.5 text-[13.5px] font-medium no-underline transition-colors ${
        active
            ? 'bg-gradient-to-br from-primary to-primary-2 font-semibold text-white shadow-[0_4px_14px_-4px_color-mix(in_srgb,var(--primary)_55%,transparent)]'
            : 'text-sidebar-text hover:bg-white/[0.06] hover:text-white'
    } ${isCollapsed ? 'lg:justify-center lg:px-0 lg:py-3' : ''}`;
    const navIconClass = (active) => `h-[18px] w-[18px] shrink-0 transition-opacity ${active ? 'opacity-100' : 'opacity-70 group-hover:opacity-100'}`;
    const navLabelClass = `truncate ${isCollapsed ? 'lg:hidden' : ''}`;
    const navBadgeClass = (active) => `ml-auto flex h-[18px] min-w-[20px] shrink-0 items-center justify-center rounded-full px-1.5 text-[10.5px] font-bold ${
        active ? 'bg-white/30 text-white' : 'bg-primary text-white'
    } ${isCollapsed ? 'lg:hidden' : ''}`;
    const sectionLabelClass = `px-3 pb-1.5 pt-4 text-[10.5px] font-bold uppercase tracking-wider text-sidebar-text-muted first:pt-2 transition-all duration-300 ${
        isCollapsed ? 'lg:h-0 lg:overflow-hidden lg:py-0 lg:opacity-0' : ''
    }`;
    const userPopClass = 'absolute right-0 top-12 z-[60] w-60 animate-[fadeUp_.16s_ease_both] rounded-2xl border border-border bg-card p-2 shadow-app-lg '
        + 'max-sm:fixed max-sm:left-auto max-sm:right-2.5 max-sm:top-[calc(var(--banner-h,0px)+var(--topbar-h,66px)+6px)] max-sm:w-[min(360px,calc(100vw-20px))]';

    return (
        <div className={`app-shell${props.impersonating ? ' has-impersonation-banner' : ''}`}>
            <ProgressBar />
            {props.impersonating && (
                <div className="impersonation-banner">
                    <span className="impersonation-banner-text">Viewing as {user?.name} ({user?.email})</span>
                    <button type="button" onClick={() => router.post('/admin/impersonate/return')}>Return to admin</button>
                </div>
            )}
            <aside
                className={`fixed inset-y-0 left-0 z-50 flex w-[min(280px,84vw)] flex-col overflow-hidden whitespace-nowrap bg-sidebar-bg text-sidebar-text shadow-[4px_0_24px_rgba(0,0,0,.08)] transition-[width,transform] duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] lg:w-64 ${
                    open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                } ${isCollapsed ? 'lg:w-[88px]' : ''}`}
            >
                <div className={`flex items-center gap-3 border-b border-white/[0.07] px-5 py-5 transition-all duration-300 ${isCollapsed ? 'lg:flex-col lg:justify-center lg:gap-4 lg:px-0 lg:pt-5' : 'justify-between pr-3'}`}>
                    <div className="flex items-center gap-2">
                        <span className="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[10px] bg-gradient-to-br from-primary to-primary-2 text-base font-extrabold text-white shadow-[0_3px_10px_-2px_color-mix(in_srgb,var(--primary)_60%,transparent)]">B</span>
                        <span className={`bg-gradient-to-br from-primary to-primary-2 bg-clip-text text-xl font-extrabold tracking-tight text-transparent ${isCollapsed ? 'lg:hidden' : ''}`}>Bulk<span>Apply</span></span>
                    </div>
                    <button
                        className={`hidden h-8 w-8 shrink-0 items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover lg:inline-flex ${isCollapsed ? 'lg:bg-hover' : ''}`}
                        onClick={toggleCollapse}
                        aria-label="Toggle Sidebar"
                        title={isCollapsed ? 'Expand menu' : 'Collapse menu'}
                    >
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                </div>
                <nav className="flex flex-1 flex-col gap-1 overflow-y-auto px-3.5 pb-1.5 pt-2">
                    <div className={sectionLabelClass}>Menu</div>
                    {NAV.map((item) => {
                        const isActive = url.startsWith(item.match);
                        const count = item.badge ? props[item.badge] : 0;
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={navItemClass(isActive)}
                                title={isCollapsed ? item.label : undefined}
                            >
                                <Icon name={item.icon} className={navIconClass(isActive)} />
                                <span className={navLabelClass}>{item.label}</span>
                                {count > 0 && <span className={navBadgeClass(isActive)}>{count > 99 ? '99+' : count}</span>}
                            </Link>
                        );
                    })}
                    <div className={sectionLabelClass}>Tools</div>
                    <Link href="/extension" className={navItemClass(url.startsWith('/extension'))} title={isCollapsed ? 'Browser Extension' : undefined}>
                        <Icon name="puzzle" className={navIconClass(url.startsWith('/extension'))} />
                        <span className={navLabelClass}>Browser Extension</span>
                    </Link>
                    {user?.isAdmin && (
                        <Link href="/admin" className={navItemClass(url.startsWith('/admin'))} title={isCollapsed ? 'Admin Panel' : undefined}>
                            <Icon name="shield" className={navIconClass(url.startsWith('/admin'))} />
                            <span className={navLabelClass}>Admin Panel</span>
                        </Link>
                    )}
                    <a className={navItemClass(false)} href={gmailSentUrl} target="_blank" rel="noopener" title={isCollapsed ? 'Gmail — Sent' : undefined}>
                        <Icon name="mail" className={navIconClass(false)} />
                        <span className={navLabelClass}>Gmail — Sent ↗</span>
                    </a>
                </nav>
                <div className="border-t border-white/[0.07] p-3">
                    <div className="flex items-center gap-2.5 rounded-xl bg-white/[0.03] p-[7px] transition-colors hover:bg-white/[0.06]" title={isCollapsed ? (user?.name || 'Account') : undefined}>
                        <span className="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[10px] bg-gradient-to-br from-primary to-primary-2 text-[13px] font-bold text-white">{initials}</span>
                        <div className={`flex min-w-0 flex-1 flex-col gap-px ${isCollapsed ? 'lg:hidden' : ''}`}>
                            <span className="truncate text-[13px] font-semibold text-white">{user?.name || 'User'}</span>
                            <span className="truncate text-[11px] text-sidebar-text-muted">{user?.email}</span>
                        </div>
                        <button
                            type="button"
                            className={`inline-flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-[9px] text-sidebar-text-muted transition-colors hover:bg-white/[0.08] hover:text-white ${isCollapsed ? 'lg:hidden' : ''}`}
                            onClick={() => router.post('/logout')}
                            title="Log out"
                            aria-label="Log out"
                        >
                            <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </aside>

            {open && <div className="fixed inset-0 z-40 bg-black/50 backdrop-blur-[2px] lg:hidden" onClick={() => setOpen(false)} />}

            <div className={`flex min-w-0 flex-1 flex-col transition-[margin-left] duration-300 ease-[cubic-bezier(0.4,0,0.2,1)] lg:ml-64 ${isCollapsed ? 'lg:ml-[88px]' : ''}`}>
                <header className="sticky top-[var(--banner-h,0px)] z-40 flex h-[66px] items-center gap-4 border-b border-border bg-card/90 px-6 backdrop-blur-md max-sm:gap-2.5 max-sm:px-3 max-[420px]:gap-1.5">
                    <button className="hidden h-9 w-9 shrink-0 items-center justify-center text-2xl text-text max-lg:flex" onClick={() => setOpen(true)} aria-label="Open menu">☰</button>
                    <span className="min-w-0 truncate font-display text-[16px] font-bold text-heading max-sm:text-sm">{current?.label || 'BulkApply'}</span>
                    <div className="min-w-0 flex-1" />
                    <button className="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-muted transition-all hover:bg-hover hover:text-heading active:scale-95 max-[420px]:hidden max-sm:h-9 max-sm:w-9 [&_svg]:h-[18px] [&_svg]:w-[18px]" onClick={toggleTheme} title="Toggle light / dark" aria-label="Toggle theme">
                        <ThemeIcon icon={theme === 'dark' ? 'sun' : 'moon'} />
                    </button>
                    <ThemeMenu theme={theme} onToggleTheme={toggleTheme} />
                    {user && (
                        <NotificationBell
                            unreadCount={props.unreadNotifications || 0}
                            recentUrl="/notifications/recent"
                            markReadUrl={(id) => `/notifications/${id}/read`}
                            markAllReadUrl="/notifications/mark-all-read"
                            getMessage={(n) => n.data?.message || 'Notification'}
                        />
                    )}
                    {user && (
                        <div className="relative shrink-0" ref={userMenuRef}>
                            <div
                                className={`flex cursor-pointer items-center gap-2.5 rounded-full py-1.5 pl-1.5 pr-3.5 transition-colors max-sm:gap-0 max-sm:p-1 ${
                                    userMenuOpen ? 'bg-hover' : 'hover:bg-hover'
                                }`}
                                onClick={() => setUserMenuOpen((o) => !o)}
                                title={user?.name || 'Account'}
                                role="button"
                                tabIndex={0}
                                onKeyDown={(e) => e.key === 'Enter' && setUserMenuOpen((o) => !o)}
                            >
                                <span className="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-full bg-primary text-[13px] font-bold text-white max-sm:h-8 max-sm:w-8">{initials}</span>
                                <span className="max-w-[160px] truncate text-[13.5px] font-semibold text-heading max-sm:hidden">{user?.name || 'User'}</span>
                            </div>
                            {userMenuOpen && (
                                <div className={userPopClass}>
                                    <div className="mb-1.5 flex items-center gap-2.5 border-b border-border px-2.5 pb-3 pt-2">
                                        <span className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-[13px] font-bold text-white">{initials}</span>
                                        <div className="flex min-w-0 flex-col">
                                            <span className="truncate text-[13.5px] font-bold text-heading">{user?.name || 'User'}</span>
                                            <span className="truncate text-xs text-muted">{user?.email}</span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2.5 text-left text-[13.5px] font-semibold text-text hover:bg-hover"
                                        onClick={() => { setUserMenuOpen(false); setProfileModalOpen(true); }}
                                    >
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><circle cx="12" cy="8" r="4" /><path d="M4 21v-1a7 7 0 0 1 7-7h2a7 7 0 0 1 7 7v1" /></svg>
                                        Account settings
                                    </button>
                                    <button
                                        type="button"
                                        className="flex w-full items-center gap-2.5 rounded-[10px] px-2.5 py-2.5 text-left text-[13.5px] font-semibold text-rose-600 hover:bg-rose-600/10"
                                        onClick={() => router.post('/logout')}
                                    >
                                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" /></svg>
                                        Log out
                                    </button>
                                </div>
                            )}
                        </div>
                    )}
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
            {profileModalOpen && (
                <ProfileModal user={user} onClose={() => setProfileModalOpen(false)} />
            )}
            {user && user.is_active === false && <AccountSuspendedModal />}
            {trial?.expired && (
                <TrialExpiredModal
                    plans={plans}
                    upiId={props.upiId}
                    upiPayeeName={props.upiPayeeName}
                    pendingPlanIds={props.pendingPlanIds}
                    currencySymbol={props.currencySymbol}
                />
            )}
            {user && user.is_active !== false && !trial?.expired && needsOnboarding && !onboardingDismissed && !url.startsWith('/profile') && (
                <OnboardingModal onClose={() => setOnboardingDismissed(true)} />
            )}
        </div>
    );
}
