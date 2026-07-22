import React, { useState } from 'react';
import { createPortal } from 'react-dom';
import { Head } from '@inertiajs/react';

export function PageHead({ title, subtitle }) {
    return (
        <div className="page-head">
            <Head title={title} />
            <h1>{title}</h1>
            {subtitle && <p>{subtitle}</p>}
        </div>
    );
}

/* A small inline icon set (stroke icons) reused across pages. */
const P = (d) => <path d={d} />;
export const Icons = {
    briefcase: <><rect x="2" y="7" width="20" height="14" rx="2" />{P('M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2')}</>,
    send: <>{P('m22 2-7 20-4-9-9-4Z')}{P('M22 2 11 13')}</>,
    clock: <><circle cx="12" cy="12" r="9" />{P('M12 7v5l3 2')}</>,
    alert: <>{P('M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z')}{P('M12 9v4')}{P('M12 17h.01')}</>,
    target: <><circle cx="12" cy="12" r="9" /><circle cx="12" cy="12" r="5" /><circle cx="12" cy="12" r="1" /></>,
    eye: <>{P('M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z')}<circle cx="12" cy="12" r="3" /></>,
    click: <>{P('M9 9l5 12 1.8-5.2L21 14 9 9z')}{P('M7.2 2.2 8 5.1')}{P('m5.1 7.2-2.9-.8')}{P('M2.2 16.8 5.1 16')}{P('m16.8 5.1-.8 2.9')}</>,
    rate: <>{P('M3 3v18h18')}{P('m19 9-5 5-4-4-3 3')}</>,
    list: <>{P('M8 6h13')}{P('M8 12h13')}{P('M8 18h13')}{P('M3 6h.01')}{P('M3 12h.01')}{P('M3 18h.01')}</>,
    mail: <><rect x="2" y="4" width="20" height="16" rx="2" />{P('m22 7-10 5L2 7')}</>,
    globe: <><circle cx="12" cy="12" r="9" />{P('M3 12h18')}{P('M12 3a15 15 0 0 1 0 18')}{P('M12 3a15 15 0 0 0 0 18')}</>,
    search: <><circle cx="11" cy="11" r="7" />{P('m21 21-4.35-4.35')}</>,
    pin: <>{P('M12 21s-7-7.33-7-12a7 7 0 0 1 14 0c0 4.67-7 12-7 12z')}<circle cx="12" cy="9" r="2.5" /></>,
    building: <><rect x="4" y="3" width="16" height="18" rx="1" />{P('M9 21v-4h6v4')}{P('M9 7h1')}{P('M14 7h1')}{P('M9 11h1')}{P('M14 11h1')}{P('M9 15h1')}{P('M14 15h1')}</>,
    sparkle: <>{P('M12 3v3')}{P('M12 18v3')}{P('M3 12h3')}{P('M18 12h3')}{P('m5.6 5.6 2.1 2.1')}{P('m16.3 16.3 2.1 2.1')}{P('m5.6 18.4 2.1-2.1')}{P('m16.3 7.7 2.1-2.1')}</>,
    chat: <>{P('M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z')}</>,
    calendar: <><rect x="3" y="4" width="18" height="18" rx="2" />{P('M16 2v4')}{P('M8 2v4')}{P('M3 10h18')}</>,
    trophy: <>{P('M8 21h8')}{P('M12 17v4')}{P('M7 4h10v5a5 5 0 0 1-10 0V4z')}{P('M7 5H4a1 1 0 0 0-1 1v1a4 4 0 0 0 4 4')}{P('M17 5h3a1 1 0 0 1 1 1v1a4 4 0 0 1-4 4')}</>,
    xCircle: <><circle cx="12" cy="12" r="9" />{P('m15 9-6 6')}{P('m9 9 6 6')}</>,
    check: <>{P('M20 6 9 17l-5-5')}</>,
    chevronDown: <>{P('m6 9 6 6 6-6')}</>,
    user: <>{P('M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2')}<circle cx="12" cy="7" r="4" /></>,
    upload: <>{P('M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4')}{P('M17 8l-5-5-5 5')}{P('M12 3v12')}</>,
    plus: <>{P('M12 5v14')}{P('M5 12h14')}</>,
    tag: <>{P('M12 2H2v10l9.29 9.29a1 1 0 0 0 1.42 0l8.58-8.58a1 1 0 0 0 0-1.42L12 2Z')}<circle cx="7" cy="7" r="1.5" fill="currentColor" stroke="none" /></>,
    save: <>{P('M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z')}{P('M17 21v-8H7v8')}{P('M7 3v5h8')}</>,
    trash: <>{P('M3 6h18')}{P('M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6')}{P('M10 11v6')}{P('M14 11v6')}{P('M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2')}</>,
};

export const ChipIcon = ({ icon }) => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        {icon}
    </svg>
);

export const IconField = React.forwardRef(function IconField({ icon, as = 'input', children, ...props }, ref) {
    const Tag = as;
    return (
        <div className="input-icon-wrap">
            <svg className="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                {icon}
            </svg>
            <Tag ref={ref} {...props}>{children}</Tag>
        </div>
    );
});

export function Spinner({ dark = false, size = 16 }) {
    return <span className={`spinner${dark ? ' dark' : ''}`} style={{ width: size, height: size }} />;
}

export function EmptyState({ icon = 'search', title, children }) {
    return (
        <div className="empty">
            <div className="empty-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    {Icons[icon] || Icons.search}
                </svg>
            </div>
            <div className="empty-title">{title}</div>
            <div className="empty-sub">{children}</div>
        </div>
    );
}

export function Stat({ label, value, accent = 'primary', icon }) {
    return (
        <div className="stat">
            <div className="stat-top">
                <span className={`stat-icon accent-${accent}`}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        {icon || Icons.briefcase}
                    </svg>
                </span>
            </div>
            <div className="num">{value}</div>
            <div className="lbl">{label}</div>
        </div>
    );
}

export function Badge({ status, children }) {
    return <span className={`badge ${status}`}>{children ?? status}</span>;
}

/** Promise-based replacement for native confirm(): const { confirm, dialog } = useConfirm(); ... await confirm({ title, message, confirmLabel, danger }); render {dialog} once near the component's other conditional modals. */
export function useConfirm() {
    const [state, setState] = useState(null);

    const confirm = (opts) => new Promise((resolve) => setState({ ...opts, resolve }));
    const close = (result) => { state?.resolve(result); setState(null); };

    const dialog = state ? createPortal(
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) close(false); }}>
            <div className="modal modal-sm">
                <h3 className="modal-title">{state.title || 'Are you sure?'}</h3>
                <p className="hint" style={{ margin: '0 0 22px' }}>{state.message}</p>
                <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                    <button type="button" className="btn btn-ghost" onClick={() => close(false)}>Cancel</button>
                    <button type="button" autoFocus
                        className={state.danger ? 'btn btn-danger-solid' : 'btn btn-primary'}
                        onClick={() => close(true)}>
                        {state.confirmLabel || 'Confirm'}
                    </button>
                </div>
            </div>
        </div>, document.body
    ) : null;

    return { confirm, dialog };
}
