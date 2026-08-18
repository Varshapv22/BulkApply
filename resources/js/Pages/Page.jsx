import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { formatDuration } from '../components';
import LandingHeader from '../LandingHeader';
import '../../css/landing.css';

function Icon({ path }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            {path}
        </svg>
    );
}

const CHECK = <polyline points="20 6 9 17 4 12" />;

const KICKERS = {
    pricing: 'Plans & billing',
    faq: 'Support',
    privacy: 'Legal',
    terms: 'Legal',
};

// Legal copy was last rewritten alongside this page redesign — keep in sync
// with cms_content updates in the same change.
const LAST_UPDATED = {
    privacy: '29 July 2026',
    terms: '29 July 2026',
};

// The CMS stores plain text: blank lines separate blocks, a block's first
// line is its heading when more lines follow, and lines starting with "- "
// render as a bullet list. Turns that into real typographic hierarchy
// instead of a <pre> dump.
function renderDoc(raw) {
    const blocks = raw.split(/\n{2,}/).map((b) => b.trim()).filter(Boolean);

    return blocks.map((block, i) => {
        const lines = block.split('\n');
        const isList = lines.length > 1 && lines.every((l) => l.startsWith('- '));

        if (isList) {
            return (
                <ul className="lp-doc-list" key={i}>
                    {lines.map((l, j) => <li key={j}>{l.replace(/^- /, '')}</li>)}
                </ul>
            );
        }

        if (lines.length === 1) {
            return <p className="lp-doc-p" key={i}>{lines[0]}</p>;
        }

        const [heading, ...rest] = lines;
        const restIsList = rest.every((l) => l.startsWith('- '));

        return (
            <div className="lp-doc-block" key={i}>
                <h2>{heading}</h2>
                {restIsList
                    ? <ul className="lp-doc-list">{rest.map((l, j) => <li key={j}>{l.replace(/^- /, '')}</li>)}</ul>
                    : rest.map((l, j) => <p className="lp-doc-p" key={j}>{l}</p>)}
            </div>
        );
    });
}

function formatPrice(price) {
    const n = Number(price);
    return n === 0 ? 'Free' : `₹${Number.isInteger(n) ? n : n.toFixed(2)}`;
}

function PlanCards({ plans }) {
    if (!plans || !plans.length) return null;
    return (
        <div className="lp-doc-plans">
            {plans.map((plan) => {
                const isFree = plan.duration_days === 7;
                return (
                    <div className={`lp-doc-plan${isFree ? ' is-free' : ''}`} key={plan.name}>
                        {isFree && <span className="lp-doc-plan-badge">Start here</span>}
                        <div className="lp-doc-plan-name">{plan.name}</div>
                        <div className="lp-doc-plan-price">
                            {formatPrice(plan.price)}
                            <span> / {isFree ? '7-day trial' : formatDuration(plan.duration_days)}</span>
                        </div>
                        <ul>
                            <li><Icon path={CHECK} /> Every feature, unlimited use</li>
                            <li><Icon path={CHECK} /> Unlimited resumes &amp; applications</li>
                        </ul>
                    </div>
                );
            })}
        </div>
    );
}

export default function Page({ slug, title, content, plans }) {
    const kicker = KICKERS[slug] || 'BulkApply';
    const lastUpdated = LAST_UPDATED[slug];

    return (
        <div className="lp-page">
            <Head title={`${title} — BulkApply`}>
                <meta name="description" content={`${title} for BulkApply — bulk job application automation for Kerala's tech job market.`} />
            </Head>

            {/* ---------- Nav ---------- */}
            <LandingHeader />

            {/* ---------- Page header ---------- */}
            <section className="lp-doc-hero">
                <div className="lp-shell">
                    <span className="lp-kicker">{kicker}</span>
                    <h1>{title}</h1>
                    {lastUpdated && <div className="lp-doc-meta">Last updated {lastUpdated}</div>}
                </div>
            </section>

            {/* ---------- Content ---------- */}
            <section className="lp-section lp-doc-section">
                <div className={`lp-shell lp-doc-shell${slug === 'pricing' ? ' lp-doc-shell-wide' : ''}`}>
                    <PlanCards plans={plans} />
                    <div className="lp-doc-card">
                        {renderDoc(content)}
                    </div>
                </div>
            </section>

            {/* ---------- Footer ---------- */}
            <footer className="lp-footer">
                <div className="lp-shell">
                    <div className="lp-footer-top">
                        <div className="lp-brand"><span className="dot">B</span> BulkApply</div>
                        <div className="lp-footer-links">
                            <Link href="/#features">Features</Link>
                            <Link href="/#how-it-works">How it works</Link>
                            <Link href="/p/pricing">Pricing</Link>
                            <Link href="/manual">Manual</Link>
                            <Link href="/p/faq">FAQ</Link>
                            <Link href="/p/privacy">Privacy</Link>
                            <Link href="/p/terms">Terms</Link>
                            <Link href="/contact">Contact</Link>
                        </div>
                    </div>
                    <div className="lp-footer-bottom">
                        <span>© {new Date().getFullYear()} BulkApply. A self-hosted job application automation platform.</span>
                        <span>Made for job seekers in Kerala and beyond.</span>
                    </div>
                </div>
            </footer>
        </div>
    );
}

Page.layout = (page) => page;
