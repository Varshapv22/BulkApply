import React from 'react';
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
};

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
