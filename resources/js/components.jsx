import React, { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';
import { Head, Link, router } from '@inertiajs/react';
import QRCode from 'qrcode';

// The default preset tiers offered in the admin plan form — plans differ by duration/price
// alone, nothing else. Admins can still enter a custom duration outside this list.
export const PLAN_DURATIONS = [
    { days: 7, label: 'Free Trial — 7 days' },
    { days: 30, label: '1 Month' },
    { days: 90, label: '3 Months' },
    { days: 270, label: '9 Months' },
];

export function formatDuration(days) {
    const preset = PLAN_DURATIONS.find((d) => d.days === days);
    if (preset) return preset.label.replace(' — 7 days', '');
    if (days % 30 === 0) return `${days / 30} Months`;
    return `${days} days`;
}

/** Darkens a "#rrggbb" hex colour toward black by scaling each channel — used to keep the
 *  UPI QR's accent-tinted modules high-contrast without depending on CSS color-mix() support. */
function blendTowardBlack(hex, ratio) {
    const m = /^#([0-9a-f]{6})$/i.exec(hex || '');
    if (!m) return '#000000';
    const num = parseInt(m[1], 16);
    const channel = (shift) => Math.round(((num >> shift) & 255) * ratio).toString(16).padStart(2, '0');
    return `#${channel(16)}${channel(8)}${channel(0)}`;
}

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
    card: <><rect x="2" y="5" width="20" height="14" rx="2" />{P('M2 10h20')}</>,
    bell: <>{P('M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9')}{P('M13.73 21a2 2 0 0 1-3.46 0')}</>,
};

export const ChipIcon = ({ icon }) => (
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
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

export const PasswordInput = React.forwardRef(function PasswordInput({ icon, style, ...props }, ref) {
    const [show, setShow] = useState(false);
    return (
        <div className={icon ? "input-icon-wrap" : ""} style={{ position: 'relative', ...style }}>
            {icon && (
                <svg className="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    {icon}
                </svg>
            )}
            <input ref={ref} type={show ? 'text' : 'password'} {...props} style={{ ...props.style, paddingRight: 40, width: '100%' }} />
            <button
                type="button"
                onClick={() => setShow(!show)}
                style={{ position: 'absolute', right: 8, top: '50%', transform: 'translateY(-50%)', background: 'none', border: 'none', cursor: 'pointer', color: 'var(--muted)', padding: 4 }}
                tabIndex="-1"
                title={show ? "Hide password" : "Show password"}
            >
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    {show ? (
                        <><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22"/></>
                    ) : (
                        <><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></>
                    )}
                </svg>
            </button>
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

function getCsrfCookie() {
    const match = document.cookie.match(/(^| )XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[2]) : '';
}

function timeAgo(dateString) {
    const seconds = Math.max(0, Math.floor((Date.now() - new Date(dateString).getTime()) / 1000));
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return new Date(dateString).toLocaleDateString();
}

/**
 * Topbar notification bell + dropdown. Data-shape agnostic — pass `getMessage`
 * to extract display text, since admin notifications (`{message}`) and user
 * database notifications (`{data: {message}}`) don't share a schema.
 */
export function NotificationBell({ unreadCount = 0, recentUrl, markReadUrl, markAllReadUrl, viewAllHref, getMessage }) {
    const [open, setOpen] = useState(false);
    const [items, setItems] = useState(null);
    const [count, setCount] = useState(unreadCount);
    const ref = useRef(null);

    useEffect(() => setCount(unreadCount), [unreadCount]);

    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const load = () => {
        fetch(recentUrl, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((d) => { setItems(d.notifications || []); setCount(d.unread_count || 0); })
            .catch(() => setItems([]));
    };

    const toggle = () => {
        const next = !open;
        setOpen(next);
        if (next) load();
    };

    const post = (url) => fetch(url, { method: 'POST', headers: { 'X-XSRF-TOKEN': getCsrfCookie() } }).then(load);
    const markRead = (id) => post(markReadUrl(id));
    const markAllRead = () => post(markAllReadUrl);

    return (
        <div className="notif-bell-wrap" ref={ref}>
            <button className="icon-btn notif-bell-btn" onClick={toggle} aria-label="Notifications" title="Notifications">
                <ChipIcon icon={Icons.bell} />
                {count > 0 && <span className="notif-badge">{count > 9 ? '9+' : count}</span>}
            </button>
            {open && (
                <div className="notif-pop">
                    <div className="notif-pop-head">
                        <span>Notifications</span>
                        {count > 0 && <button type="button" className="btn-link" onClick={markAllRead}>Mark all read</button>}
                    </div>
                    <div className="notif-pop-list">
                        {items === null ? (
                            <div className="notif-pop-empty">Loading…</div>
                        ) : items.length === 0 ? (
                            <div className="notif-pop-empty">You're all caught up.</div>
                        ) : items.map((n) => (
                            <div
                                key={n.id}
                                className={`notif-item${n.read_at ? '' : ' unread'}`}
                                onClick={() => !n.read_at && markRead(n.id)}
                                role={n.read_at ? undefined : 'button'}
                            >
                                <p>{getMessage(n)}</p>
                                <span className="notif-item-time">{timeAgo(n.created_at)}</span>
                            </div>
                        ))}
                    </div>
                    {viewAllHref && (
                        <Link href={viewAllHref} className="notif-pop-footer">View all</Link>
                    )}
                </div>
            )}
        </div>
    );
}

/** UPI "pay & submit reference" flow used on the Billing page and trial-expired paywall. */
export function UpiPaymentModal({ plan, upiId, upiPayeeName, currencySymbol = '₹', onClose, onSubmitted }) {
    const [qrDataUrl, setQrDataUrl] = useState(null);
    const [txnRef, setTxnRef] = useState('');
    const [screenshot, setScreenshot] = useState(null);
    const [busy, setBusy] = useState(false);
    const [errors, setErrors] = useState({});
    const [copied, setCopied] = useState(false);

    const upiLink = `upi://pay?pa=${encodeURIComponent(upiId || '')}&pn=${encodeURIComponent(upiPayeeName || 'BulkApply')}&am=${encodeURIComponent(plan.price)}&cu=INR&tn=${encodeURIComponent(`BulkApply ${plan.name} plan`)}`;

    // QR modules use a deep, mostly-black shade of the active accent (never the bright
    // accent itself) — reads as "branded but standard", and stays high-contrast for scanning.
    // Redraws live if the user switches theme/accent while the modal is open.
    useEffect(() => {
        if (!upiId) return;

        const draw = () => {
            const accentHex = getComputedStyle(document.documentElement).getPropertyValue('--primary-dark').trim();
            const dark = blendTowardBlack(accentHex, 0.55);
            QRCode.toDataURL(upiLink, { width: 200, margin: 1, color: { dark, light: '#ffffff' } })
                .then(setQrDataUrl)
                .catch(() => {
                    // Fall back to a plain black/white QR if the accent colour couldn't be used.
                    QRCode.toDataURL(upiLink, { width: 200, margin: 1 }).then(setQrDataUrl).catch(() => {});
                });
        };

        draw();
        const observer = new MutationObserver(draw);
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme', 'data-accent'] });
        return () => observer.disconnect();
    }, [upiLink, upiId]);

    const copyUpiId = () => {
        navigator.clipboard?.writeText(upiId);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    };

    const submit = (e) => {
        e.preventDefault();
        setBusy(true);
        setErrors({});
        const fd = new FormData();
        fd.append('plan_id', plan.id);
        fd.append('transaction_ref', txnRef);
        if (screenshot) fd.append('screenshot', screenshot);
        router.post('/billing/payment-requests', fd, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errs) => { setErrors(errs); setBusy(false); },
            onSuccess: () => { setBusy(false); onSubmitted?.(); onClose(); },
        });
    };

    const modal = (
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className="modal modal-sm">
                <button className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <h3 className="modal-title">Pay via UPI</h3>
                <p className="hint" style={{ marginTop: 6 }}>
                    Scan the QR or pay to the UPI ID below using GPay, PhonePe, Paytm, or any UPI app — then submit your transaction reference.
                </p>

                {upiId ? (
                    <>
                        <div className="upi-qr-frame">
                            {qrDataUrl ? (
                                <img src={qrDataUrl} alt="UPI QR code" width={200} height={200} />
                            ) : (
                                <div className="upi-qr-frame-loading" style={{ width: 200, height: 200 }} />
                            )}
                        </div>

                        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 8 }}>
                            <strong>{upiId}</strong>
                            <button type="button" className="btn btn-ghost btn-sm" onClick={copyUpiId}>{copied ? 'Copied ✓' : 'Copy'}</button>
                        </div>
                        <p style={{ textAlign: 'center', color: 'var(--muted)', fontSize: 13, margin: '4px 0 16px' }}>
                            Amount: {currencySymbol}{plan.price} · {plan.name} plan
                        </p>

                        <a href={upiLink} className="btn btn-primary btn-block" style={{ marginBottom: 20 }}>
                            Open UPI app to pay
                        </a>
                    </>
                ) : (
                    <p className="field-error" style={{ marginTop: 12 }}>
                        No UPI ID has been configured yet — contact support to complete this payment.
                    </p>
                )}

                <form onSubmit={submit}>
                    <div style={{ marginBottom: 14 }}>
                        <label>UPI Transaction ID / UTR Number</label>
                        <input
                            type="text"
                            autoFocus
                            required
                            value={txnRef}
                            onChange={(e) => setTxnRef(e.target.value)}
                            placeholder="e.g. 234567891234"
                        />
                        {errors.transaction_ref && <p className="field-error">{errors.transaction_ref}</p>}
                    </div>
                    <div style={{ marginBottom: 20 }}>
                        <label>Payment screenshot (optional)</label>
                        <input type="file" accept="image/*,.pdf" onChange={(e) => setScreenshot(e.target.files[0])} />
                        {errors.screenshot && <p className="field-error">{errors.screenshot}</p>}
                    </div>
                    <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="btn btn-primary" disabled={busy || !txnRef}>
                            {busy ? 'Submitting…' : "I've paid — submit for verification"}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );

    return createPortal(modal, document.body);
}
