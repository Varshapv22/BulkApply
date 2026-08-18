import React, { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';

// Features/How it works are same-document anchors on "/": a plain <a> lets the
// browser scroll in place there and does a normal navigation from other pages.
// Pricing/FAQ are real routes, so they go through Inertia's <Link>.
const NAV_LINKS = [
    { href: '/#features', label: 'Features', anchor: true },
    { href: '/#how-it-works', label: 'How it works', anchor: true },
    { href: '/p/pricing', label: 'Pricing' },
    { href: '/manual', label: 'Manual' },
    { href: '/p/faq', label: 'FAQ' },
];

function NavLink({ href, label, anchor, onClick }) {
    return anchor
        ? <a href={href} onClick={onClick}>{label}</a>
        : <Link href={href} onClick={onClick}>{label}</Link>;
}

export default function LandingHeader() {
    const [open, setOpen] = useState(false);

    // Lock body scroll while the mobile menu is open, and let Escape close it.
    useEffect(() => {
        if (!open) return;
        document.body.style.overflow = 'hidden';
        const onKey = (e) => e.key === 'Escape' && setOpen(false);
        window.addEventListener('keydown', onKey);
        return () => {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', onKey);
        };
    }, [open]);

    return (
        <header className={`lp-nav${open ? ' is-open' : ''}`}>
            <div className="lp-shell">
                <div className="lp-nav-pill">
                    <Link href="/" className="lp-brand"><span className="dot">B</span> BulkApply</Link>

                    <nav className="lp-nav-links">
                        {NAV_LINKS.map((l) => <NavLink key={l.href} {...l} />)}
                    </nav>

                    <div className="lp-nav-cta">
                        <Link href="/login" className="lp-btn lp-btn-ghost">Log in</Link>
                        <Link href="/register" className="lp-btn lp-btn-primary">Start free trial</Link>
                    </div>

                    <button
                        type="button"
                        className="lp-nav-toggle"
                        aria-label={open ? 'Close menu' : 'Open menu'}
                        aria-expanded={open}
                        onClick={() => setOpen((v) => !v)}
                    >
                        <span />
                        <span />
                        <span />
                    </button>
                </div>

                <div className="lp-nav-mobile">
                    <nav>
                        {NAV_LINKS.map((l) => <NavLink key={l.href} {...l} onClick={() => setOpen(false)} />)}
                    </nav>
                    <div className="lp-nav-mobile-cta">
                        <Link href="/login" className="lp-btn lp-btn-ghost lp-btn-block" onClick={() => setOpen(false)}>Log in</Link>
                        <Link href="/register" className="lp-btn lp-btn-primary lp-btn-block" onClick={() => setOpen(false)}>Start free trial</Link>
                    </div>
                </div>
            </div>
        </header>
    );
}
