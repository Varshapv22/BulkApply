import React, { useEffect, useRef, useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import LandingHeader from '../LandingHeader';
import '../../css/landing.css';
import '../../css/manual.css';

const IMG = '/images/marketing';

// ─── Icon primitives ───────────────────────────────────────────────────────
function Icon({ d, children }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8"
            strokeLinecap="round" strokeLinejoin="round">
            {d ? <path d={d} /> : children}
        </svg>
    );
}
const ICONS = {
    dashboard: <><rect x="3" y="3" width="7" height="7" rx="1" /><rect x="14" y="3" width="7" height="7" rx="1" /><rect x="14" y="14" width="7" height="7" rx="1" /><rect x="3" y="14" width="7" height="7" rx="1" /></>,
    profile:   <><circle cx="12" cy="8" r="4" /><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7" /></>,
    doc:       <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><line x1="9" y1="13" x2="15" y2="13" /><line x1="9" y1="17" x2="13" y2="17" /></>,
    mail:      <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 5L2 7" /></>,
    clock:     <><circle cx="12" cy="12" r="9" /><polyline points="12 6 12 12 16 14" /></>,
    jobs:      <><rect x="2" y="7" width="20" height="14" rx="2" /><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" /><line x1="12" y1="12" x2="12" y2="16" /><line x1="10" y1="14" x2="14" y2="14" /></>,
    send:      <><line x1="22" y1="2" x2="11" y2="13" /><polygon points="22 2 15 22 11 13 2 9 22 2" /></>,
    pipeline:  <><path d="M22 12H2" /><path d="m15 5 7 7-7 7" /></>,
    template:  <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><path d="m9 15 2 2 4-4" /></>,
    search:    <><circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></>,
    inbox:     <><path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" /></>,
    ats:       <><path d="M9 11l3 3L22 4" /><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" /></>,
    billing:   <><rect x="1" y="4" width="22" height="16" rx="2" /><line x1="1" y1="10" x2="23" y2="10" /></>,
    support:   <><circle cx="12" cy="12" r="9" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><line x1="12" y1="17" x2="12.01" y2="17" /></>,
    extension: <><path d="M19.4 7.9c-.05.3.06.65.3.88l1.5 1.5a2.4 2.4 0 0 1 0 3.4l-1.6 1.6a1 1 0 0 1-.84.28c-.47-.07-.8-.48-.97-.93a2.5 2.5 0 1 0-3.21 3.21c.44.17.86.5.93.97a1 1 0 0 1-.28.84l-1.6 1.6a2.4 2.4 0 0 1-3.4 0l-1.57-1.57a1 1 0 0 0-.88-.28c-.49.07-.84.5-1.02.97a2.5 2.5 0 1 1-3.24-3.24c.47-.18.9-.53.97-1.02a1 1 0 0 0-.29-.88L2.1 12.9a2.4 2.4 0 0 1 0-3.4l1.6-1.62a1 1 0 0 1 .92-.3c.5.06.85.48 1.03.95a2.5 2.5 0 1 0 3.25-3.25c-.46-.18-.89-.53-.95-1.02a1 1 0 0 1 .3-.92l1.62-1.6a2.4 2.4 0 0 1 3.4 0l1.57 1.57c.23.23.56.34.88.29.49-.07.84-.5 1.02-.97a2.5 2.5 0 1 1 3.24 3.24z" /></>,
    admin:     <><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></>,
    check:     <polyline points="20 6 9 17 4 12" />,
    info:      <><circle cx="12" cy="12" r="9" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></>,
    tip:       <><circle cx="12" cy="12" r="9" /><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" /><line x1="12" y1="17" x2="12.01" y2="17" /></>,
    warn:      <><path d="m10.29 3.86-8.45 14.59A2 2 0 0 0 3.59 21H20.4a2 2 0 0 0 1.73-3l-8.45-14.59a2 2 0 0 0-3.46 0z" /><line x1="12" y1="9" x2="12" y2="13" /><line x1="12" y1="17" x2="12.01" y2="17" /></>,
};

// ─── TOC sections definition ────────────────────────────────────────────────
const SECTIONS = [
    { id: 'getting-started',     label: 'Getting Started',          icon: ICONS.profile,   group: 'user' },
    { id: 'dashboard',           label: 'Dashboard',                icon: ICONS.dashboard, group: 'user' },
    { id: 'profile',             label: 'Profile Setup',            icon: ICONS.profile,   group: 'user' },
    { id: 'documents',           label: 'Resumes & Cover Letters',  icon: ICONS.doc,       group: 'user' },
    { id: 'email-config',        label: 'Email Configuration',      icon: ICONS.mail,      group: 'user' },
    { id: 'send-schedule',       label: 'Sending Schedule',         icon: ICONS.clock,     group: 'user' },
    { id: 'job-applications',    label: 'Job Applications',         icon: ICONS.jobs,      group: 'user' },
    { id: 'bulk-send',           label: 'Bulk Send',                icon: ICONS.send,      group: 'user' },
    { id: 'pipeline',            label: 'Pipeline Tracking',        icon: ICONS.pipeline,  group: 'user' },
    { id: 'templates',           label: 'Email Templates',          icon: ICONS.template,  group: 'user' },
    { id: 'find-jobs',           label: 'Find Jobs',                icon: ICONS.search,    group: 'user' },
    { id: 'replies',             label: 'Company Replies',          icon: ICONS.inbox,     group: 'user' },
    { id: 'ats-check',           label: 'Resume ATS Checker',       icon: ICONS.ats,       group: 'user' },
    { id: 'billing',             label: 'Billing & Plans',          icon: ICONS.billing,   group: 'user' },
    { id: 'support',             label: 'Support Tickets',          icon: ICONS.support,   group: 'user' },
    { id: 'extension',           label: 'Browser Extension',        icon: ICONS.extension, group: 'user' },
    { id: 'admin-overview',      label: 'Admin Overview',           icon: ICONS.admin,     group: 'admin' },
    { id: 'admin-users',         label: 'User Management',          icon: ICONS.profile,   group: 'admin' },
    { id: 'admin-plans',         label: 'Plans & Payments',         icon: ICONS.billing,   group: 'admin' },
    { id: 'admin-features',      label: 'Feature Flags',            icon: ICONS.extension, group: 'admin' },
    { id: 'admin-system',        label: 'System & Monitoring',      icon: ICONS.admin,     group: 'admin' },
];

// ─── Small reusable components ───────────────────────────────────────────────

function Check() {
    return (
        <svg className="mn-check-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <polyline points="20 6 9 17 4 12" />
        </svg>
    );
}

function StepBadge({ n }) {
    return <span className="mn-step-badge">{n}</span>;
}

function SectionHead({ id, icon, title, subtitle }) {
    return (
        <div className="mn-sec-head" id={id}>
            <div className="mn-sec-icon"><Icon>{icon}</Icon></div>
            <div>
                <h2 className="mn-sec-title">{title}</h2>
                {subtitle && <p className="mn-sec-sub">{subtitle}</p>}
            </div>
        </div>
    );
}

function Screenshot({ src, alt, caption }) {
    return (
        <figure className="mn-shot">
            <div className="mn-shot-frame">
                <div className="mn-shot-chrome"><span /><span /><span /></div>
                <img src={src} alt={alt} loading="lazy" />
            </div>
            {caption && <figcaption className="mn-shot-caption">{caption}</figcaption>}
        </figure>
    );
}

function Callout({ type = 'info', children }) {
    const map = { info: ICONS.info, tip: ICONS.tip, warn: ICONS.warn };
    return (
        <div className={`mn-callout mn-callout-${type}`}>
            <span className="mn-callout-ico"><Icon>{map[type]}</Icon></span>
            <div className="mn-callout-body">{children}</div>
        </div>
    );
}

function Table({ headers, rows }) {
    return (
        <div className="mn-table-wrap">
            <table className="mn-table">
                <thead>
                    <tr>{headers.map((h, i) => <th key={i}>{h}</th>)}</tr>
                </thead>
                <tbody>
                    {rows.map((row, i) => (
                        <tr key={i}>{row.map((cell, j) => <td key={j}>{cell}</td>)}</tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function CheckList({ items }) {
    return (
        <ul className="mn-checklist">
            {items.map((item, i) => (
                <li key={i}><Check /><span>{item}</span></li>
            ))}
        </ul>
    );
}

function Steps({ steps }) {
    return (
        <ol className="mn-steps">
            {steps.map((s, i) => (
                <li key={i} className="mn-step-item">
                    <StepBadge n={i + 1} />
                    <div className="mn-step-body">
                        {typeof s === 'string' ? <span>{s}</span> : s}
                    </div>
                </li>
            ))}
        </ol>
    );
}

function Divider() {
    return <hr className="mn-divider" />;
}

function AdminBadge() {
    return <span className="mn-admin-badge">Admin only</span>;
}

// ─── Main page ───────────────────────────────────────────────────────────────
export default function Manual() {
    const [activeId, setActiveId] = useState('getting-started');
    const observerRef = useRef(null);

    // Highlight the TOC item whose section is in view
    useEffect(() => {
        const sections = document.querySelectorAll('[data-mn-section]');
        observerRef.current = new IntersectionObserver(
            (entries) => {
                entries.forEach((e) => {
                    if (e.isIntersecting) setActiveId(e.target.id);
                });
            },
            { rootMargin: '-20% 0px -70% 0px' }
        );
        sections.forEach((s) => observerRef.current.observe(s));
        return () => observerRef.current?.disconnect();
    }, []);

    const scrollTo = (id, e) => {
        e.preventDefault();
        document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const userSections  = SECTIONS.filter((s) => s.group === 'user');
    const adminSections = SECTIONS.filter((s) => s.group === 'admin');

    return (
        <div className="lp-page">
            <Head title="User Manual — BulkApply">
                <meta name="description" content="Complete guide to using BulkApply — bulk job application automation. Covers every feature for users and administrators." />
            </Head>

            <LandingHeader />

            {/* ── Hero ── */}
            <section className="mn-hero">
                <div className="lp-shell">
                    <span className="lp-eyebrow">Documentation</span>
                    <h1 className="mn-hero-title">BulkApply User Manual</h1>
                    <p className="mn-hero-lead">
                        Everything you need to know — from setting up your profile to sending hundreds of
                        applications, tracking replies, and managing the platform as an admin.
                    </p>
                    <div className="mn-hero-pills">
                        <a href="#getting-started" className="lp-btn lp-btn-primary" onClick={(e) => scrollTo('getting-started', e)}>
                            Get started
                        </a>
                        <a href="#admin-overview" className="lp-btn lp-btn-ghost" onClick={(e) => scrollTo('admin-overview', e)}>
                            Admin guide
                        </a>
                    </div>
                </div>
            </section>

            {/* ── Body: sidebar + content ── */}
            <div className="mn-layout lp-shell">

                {/* Sidebar TOC */}
                <aside className="mn-sidebar">
                    <div className="mn-toc">
                        <div className="mn-toc-group-label">User Guide</div>
                        {userSections.map((s) => (
                            <a
                                key={s.id}
                                href={`#${s.id}`}
                                className={`mn-toc-link${activeId === s.id ? ' is-active' : ''}`}
                                onClick={(e) => scrollTo(s.id, e)}
                            >
                                <span className="mn-toc-ico"><Icon>{s.icon}</Icon></span>
                                {s.label}
                            </a>
                        ))}
                        <div className="mn-toc-group-label" style={{ marginTop: 20 }}>Admin Guide</div>
                        {adminSections.map((s) => (
                            <a
                                key={s.id}
                                href={`#${s.id}`}
                                className={`mn-toc-link${activeId === s.id ? ' is-active' : ''}`}
                                onClick={(e) => scrollTo(s.id, e)}
                            >
                                <span className="mn-toc-ico"><Icon>{s.icon}</Icon></span>
                                {s.label}
                            </a>
                        ))}
                    </div>
                </aside>

                {/* Content */}
                <main className="mn-content">

                    {/* ══════════════════════════════════════
                        GETTING STARTED
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="getting-started" className="mn-section">
                        <SectionHead
                            id="getting-started"
                            icon={ICONS.profile}
                            title="Getting Started"
                            subtitle="Create your account and be ready to send your first application in minutes."
                        />

                        <h3 className="mn-h3">1 — Register</h3>
                        <Steps steps={[
                            'Go to the home page and click Start free trial.',
                            'Enter your name, email address, and a password.',
                            'Check your inbox and verify your email if prompted.',
                            'You are taken to your Dashboard automatically.',
                        ]} />

                        <Screenshot
                            src={`${IMG}/login.png`}
                            alt="BulkApply login page"
                            caption="The login / sign-up screen — no credit card required for the 7-day free trial."
                        />

                        <Callout type="info">
                            New accounts include a <strong>7-day free trial</strong> with full access to every feature.
                            When the trial ends you need an active plan to continue sending.
                        </Callout>

                        <h3 className="mn-h3" style={{ marginTop: 32 }}>Quick-start checklist</h3>
                        <p className="mn-body">Complete these steps before sending your first application:</p>
                        <CheckList items={[
                            'Fill in your personal info on the Profile page',
                            'Upload at least one resume (PDF or DOCX)',
                            'Upload or paste a cover letter',
                            'Connect your Gmail / SMTP email sender',
                            'Write your default email subject and body',
                            'Add jobs manually, import via CSV, or use Find Jobs',
                            'Click Send All on the Jobs page',
                        ]} />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        DASHBOARD
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="dashboard" className="mn-section">
                        <SectionHead
                            id="dashboard"
                            icon={ICONS.dashboard}
                            title="Dashboard"
                            subtitle="Your activity hub — every key metric at a glance."
                        />

                        <Screenshot
                            src={`${IMG}/dashboard.png`}
                            alt="BulkApply dashboard showing stats, activity chart and pipeline"
                            caption="The Dashboard: stat cards, a 30-day activity chart, email tracking, pipeline breakdown and recent activity."
                        />

                        <Table
                            headers={['Widget', 'What it shows']}
                            rows={[
                                ['Total / Pending / Sent / Failed', 'Live counts of all your job applications by status'],
                                ['Sent Rate', 'Percentage of applications successfully delivered'],
                                ['This Week vs Last Week', 'Applications added this week compared to last week'],
                                ['30-Day Activity Chart', 'Daily bar chart of total added vs. sent applications'],
                                ['Top 5 Companies', 'Companies you have applied to most'],
                                ['Recent Activity', 'Last 10 sent or failed applications with timestamps'],
                                ['Email Tracking', 'How many emails were opened and clicked'],
                                ['Pipeline Breakdown', 'Count of applications in each pipeline stage'],
                            ]}
                        />

                        <Callout type="tip">
                            Dashboard data is cached for 60 seconds per user, so numbers refresh automatically on your next page load.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        PROFILE
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="profile" className="mn-section">
                        <SectionHead
                            id="profile"
                            icon={ICONS.profile}
                            title="Profile Setup"
                            subtitle="Your personal information is used in every email and in job matching."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → Profile</strong> to edit your profile.</p>

                        <Table
                            headers={['Field', 'Used for']}
                            rows={[
                                ['Full Name', 'Fills {your_name} in emails'],
                                ['Email', 'Fills {your_email} in emails'],
                                ['Phone', 'Fills {your_phone} in emails'],
                                ['Country / State / District', 'Composed into {your_location}'],
                                ['Preferred Role', 'Pre-fills the job search field and ATS analysis'],
                                ['Skills', 'Matched against job descriptions in search results'],
                                ['Bio', 'Short professional summary (for your reference)'],
                                ['LinkedIn / Portfolio URL', 'Your links, stored for reference'],
                                ['Profile Photo', 'Optional avatar shown in the app'],
                            ]}
                        />

                        <Callout type="tip">
                            Keep <strong>Preferred Role</strong> and <strong>Skills</strong> up to date — they drive the skill-match
                            badges on Find Jobs results.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        DOCUMENTS
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="documents" className="mn-section">
                        <SectionHead
                            id="documents"
                            icon={ICONS.doc}
                            title="Resumes & Cover Letters"
                            subtitle="Manage a library of documents and pick the right one per application."
                        />

                        <div className="mn-two-col">
                            <div className="mn-col-card">
                                <h3 className="mn-col-title">Resumes</h3>
                                <CheckList items={[
                                    'Upload PDF, DOCX, or DOC files',
                                    'Store multiple resumes for different roles',
                                    'Mark one as Default — attached to all new jobs',
                                    'Auto-parse: fills name, email, phone & skills from the file',
                                    'Delete resumes you no longer need',
                                ]} />
                            </div>
                            <div className="mn-col-card">
                                <h3 className="mn-col-title">Cover Letters</h3>
                                <CheckList items={[
                                    'Upload a file or paste text directly',
                                    'Store multiple cover letters',
                                    'Mark one as Default',
                                    'Default is attached to all new applications automatically',
                                    'Delete outdated cover letters',
                                ]} />
                            </div>
                        </div>

                        <Callout type="warn">
                            You must have at least one resume <strong>and</strong> one cover letter before you
                            can send any application.
                        </Callout>

                        <h3 className="mn-h3">Auto-parse resume</h3>
                        <Steps steps={[
                            'On the Profile page, click Auto-parse next to a newly uploaded resume.',
                            'BulkApply reads the file and extracts your name, email, phone, and skills.',
                            'Review the extracted values and save your profile.',
                        ]} />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        EMAIL CONFIG
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="email-config" className="mn-section">
                        <SectionHead
                            id="email-config"
                            icon={ICONS.mail}
                            title="Email Configuration"
                            subtitle="Connect your own email address so applications are sent directly from you."
                        />

                        <p className="mn-body">
                            BulkApply sends applications from <strong>your own email address</strong> using SMTP
                            credentials. This ensures emails look personal and pass spam filters better than a
                            shared platform sender.
                        </p>

                        <Table
                            headers={['Field', 'Description']}
                            rows={[
                                ['Email (Username)', 'Your Gmail or other SMTP email address'],
                                ['Password / App Password', 'Your email password or app-specific password'],
                                ['From Name', 'The name recipients see (e.g. "Jane Doe")'],
                            ]}
                        />

                        <h3 className="mn-h3">Gmail App Password setup</h3>
                        <Steps steps={[
                            'Go to your Google Account → Security.',
                            'Enable 2-Step Verification if not already on.',
                            <>Search for <strong>App Passwords</strong> and click it.</>,
                            'Create a new app password — choose "Mail" and "Other device".',
                            'Copy the 16-character code shown.',
                            'Paste it as the Password in BulkApply. Never use your main Gmail password.',
                        ]} />

                        <Callout type="warn">
                            Your email password is stored <strong>encrypted</strong> and is never transmitted
                            back to the browser. Click <strong>Disconnect Email</strong> to remove it at any time.
                        </Callout>

                        <h3 className="mn-h3">Application Email (Subject & Body)</h3>
                        <p className="mn-body">
                            Write the default subject and body used for all applications. Use{' '}
                            <strong>placeholders</strong> like <code>{'{job_title}'}</code> and <code>{'{company}'}</code> —
                            they are replaced with real values at send time.
                        </p>
                        <div className="mn-code-block">
                            <div className="mn-code-label">Example subject</div>
                            <pre>{`Application for {job_title} at {company}`}</pre>
                            <div className="mn-code-label" style={{ marginTop: 14 }}>Example body</div>
                            <pre>{`Dear {recruiter_name},

I am writing to apply for the {job_title} position at {company}.
I have attached my resume and cover letter for your review.

Best regards,
{your_name}
{your_phone} | {your_email}`}</pre>
                        </div>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        SEND SCHEDULE
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="send-schedule" className="mn-section">
                        <SectionHead
                            id="send-schedule"
                            icon={ICONS.clock}
                            title="Sending Schedule & Rate Limits"
                            subtitle="Control exactly when and how fast BulkApply sends emails on your behalf."
                        />

                        <Table
                            headers={['Setting', 'Description']}
                            rows={[
                                ['Send Start Hour', 'Earliest hour to send (0–23, 24-hour clock)'],
                                ['Send End Hour', 'Latest hour to send'],
                                ['Weekdays Only', 'If enabled, no emails are sent on Saturday or Sunday'],
                                ['Max Emails per Hour', 'Rate cap — set to 0 for no limit'],
                                ['Follow-up Days', 'Days after sending before an automatic follow-up. Set 0 to disable'],
                            ]}
                        />

                        <Callout type="tip">
                            <strong>Example:</strong> Start 9, End 17, Weekdays Only → emails are sent only
                            Monday–Friday between 9 AM and 5 PM. This mimics a human sender and reduces
                            spam filter triggers.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        JOB APPLICATIONS
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="job-applications" className="mn-section">
                        <SectionHead
                            id="job-applications"
                            icon={ICONS.jobs}
                            title="Job Applications"
                            subtitle="The main board — add, manage, and track every application."
                        />

                        <Screenshot
                            src={`${IMG}/jobs.png`}
                            alt="BulkApply job applications table with status and pipeline columns"
                            caption="The Jobs page: searchable table with status badges, pipeline stages, tracking info, and bulk actions."
                        />

                        <h3 className="mn-h3">Adding jobs</h3>
                        <div className="mn-three-col">
                            <div className="mn-feature-mini">
                                <div className="mn-feature-mini-head">Manually</div>
                                <p>Click <strong>Add Job</strong>, fill in Company (required), Recruiter Email (required), Job Title, and any other fields. Save.</p>
                            </div>
                            <div className="mn-feature-mini">
                                <div className="mn-feature-mini-head">CSV Import</div>
                                <p>Download the <strong>CSV Template</strong>, fill it in, and upload. Duplicates (same email + company) are skipped automatically.</p>
                            </div>
                            <div className="mn-feature-mini">
                                <div className="mn-feature-mini-head">From Find Jobs</div>
                                <p>Search live listings, tick the ones you want, and click <strong>Auto Apply</strong> to import and send in one step.</p>
                            </div>
                        </div>

                        <h3 className="mn-h3" style={{ marginTop: 32 }}>Application statuses</h3>
                        <Table
                            headers={['Status', 'Meaning']}
                            rows={[
                                ['Pending', 'Added, not yet sent'],
                                ['Queued', 'In the background queue, being sent now'],
                                ['Sent', 'Email delivered successfully'],
                                ['Failed', 'Send failed — hover the row to see the error reason'],
                            ]}
                        />

                        <h3 className="mn-h3" style={{ marginTop: 32 }}>Managing your jobs</h3>
                        <Table
                            headers={['Action', 'How']}
                            rows={[
                                ['Search', 'Type in the search bar — filters by company, title, recruiter name/email, location'],
                                ['Filter by status', 'Click Pending / Sent / Failed pill buttons'],
                                ['Filter by pipeline', 'Use the pipeline dropdown'],
                                ['Sort', 'Click column headers (Company, Title, Status, Date)'],
                                ['Preview email', 'Click the eye icon — see the rendered email before sending'],
                                ['Send one job', 'Click the send icon on any pending/failed row'],
                                ['Delete', 'Click the trash icon on a row'],
                                ['Export to CSV', 'Click Export CSV — downloads all applications with tracking data'],
                                ['Clear all', 'Click Clear All — permanent, no undo'],
                            ]}
                        />

                        <h3 className="mn-h3" style={{ marginTop: 32 }}>CSV import format</h3>
                        <p className="mn-body">Required columns and accepted aliases:</p>
                        <Table
                            headers={['Column', 'Required', 'Accepted aliases']}
                            rows={[
                                ['company', 'Yes', 'company_name, organization, employer'],
                                ['recruiter_email', 'Yes', 'email, mail, contact_email'],
                                ['job_title', 'No', 'title, role, position'],
                                ['recruiter_name', 'No', 'recruiter, contact, name'],
                                ['job_url', 'No', 'url, link, job_link'],
                                ['location', 'No', 'city, place'],
                                ['notes', 'No', 'note, comments, remark'],
                            ]}
                        />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        BULK SEND
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="bulk-send" className="mn-section">
                        <SectionHead
                            id="bulk-send"
                            icon={ICONS.send}
                            title="Bulk Send"
                            subtitle="Queue all pending applications and email them in the background."
                        />

                        <Steps steps={[
                            'Make sure your profile has a resume, cover letter, and connected email sender.',
                            <>On the <strong>Jobs page</strong>, optionally select an Email Template from the dropdown.</>,
                            <>Click <strong>Send All</strong>.</>,
                            'All Pending and Failed jobs are queued and sent in the background.',
                            'A progress bar appears showing total / processed / failed counts in real time.',
                            <>Click <strong>Cancel Send</strong> to stop remaining queued emails — jobs already dispatched finish; the rest reset to Pending.</>,
                        ]} />

                        <Callout type="info">
                            <strong>Plan limits:</strong> If your plan has a monthly email quota, the batch is automatically
                            capped. You will see a message if some jobs were skipped because the limit was reached.
                        </Callout>

                        <Callout type="tip">
                            You can also send a <strong>single application</strong> at any time by clicking the
                            send icon on any row — useful for retrying a failed one or testing your setup.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        PIPELINE
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="pipeline" className="mn-section">
                        <SectionHead
                            id="pipeline"
                            icon={ICONS.pipeline}
                            title="Pipeline Tracking"
                            subtitle="Move each application through hiring stages to track your progress."
                        />

                        <div className="mn-pipeline-row">
                            {[
                                { stage: 'Applied',   color: 'primary', desc: 'Application sent' },
                                { stage: 'Replied',   color: 'blue',    desc: 'Recruiter responded' },
                                { stage: 'Interview', color: 'amber',   desc: 'Interview scheduled' },
                                { stage: 'Rejected',  color: 'red',     desc: 'Position rejected' },
                                { stage: 'Offer',     color: 'green',   desc: 'Offer received 🎉' },
                            ].map((p, i, arr) => (
                                <React.Fragment key={p.stage}>
                                    <div className={`mn-pipe-stage mn-pipe-${p.color}`}>
                                        <span className="mn-pipe-label">{p.stage}</span>
                                        <span className="mn-pipe-desc">{p.desc}</span>
                                    </div>
                                    {i < arr.length - 1 && (
                                        <span className="mn-pipe-arrow">
                                            <Icon d="M9 18l6-6-6-6" />
                                        </span>
                                    )}
                                </React.Fragment>
                            ))}
                        </div>

                        <p className="mn-body" style={{ marginTop: 24 }}>
                            Update the pipeline status via the dropdown on each row in the Jobs table.
                            The Pipeline Breakdown widget on your Dashboard reflects these stages in real time.
                        </p>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        TEMPLATES
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="templates" className="mn-section">
                        <SectionHead
                            id="templates"
                            icon={ICONS.template}
                            title="Email Templates"
                            subtitle="Save reusable email templates for different roles or industries."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → Templates</strong>.</p>

                        <CheckList items={[
                            'Create templates with a Name, Subject, and Body',
                            'Mark one as Default — pre-selected in the bulk send dialog',
                            'Use any placeholder in both subject and body',
                            'Edit or delete templates at any time',
                            'Select any template from the dropdown when sending',
                        ]} />

                        <h3 className="mn-h3" style={{ marginTop: 24 }}>Available placeholders</h3>
                        <Table
                            headers={['Placeholder', 'Replaced with']}
                            rows={[
                                ['{job_title}',      'The job title (falls back to "the role" if blank)'],
                                ['{company}',        'Company name'],
                                ['{recruiter_name}', 'Recruiter name (falls back to "Hiring Manager")'],
                                ['{location}',       'Job location'],
                                ['{job_url}',        'URL of the job listing'],
                                ['{your_name}',      'Your full name (from Profile)'],
                                ['{your_location}',  'Your location (from Profile)'],
                                ['{your_email}',     'Your email address (from Profile)'],
                                ['{your_phone}',     'Your phone number (from Profile)'],
                            ]}
                        />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        FIND JOBS
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="find-jobs" className="mn-section">
                        <SectionHead
                            id="find-jobs"
                            icon={ICONS.search}
                            title="Find Jobs"
                            subtitle="Search live job listings from multiple sources and apply in one click."
                        />

                        <Screenshot
                            src={`${IMG}/search.png`}
                            alt="BulkApply Find Jobs search page with skill match badges"
                            caption="The Find Jobs page: search across IT parks, job boards, and company career pages — results show skill match badges."
                        />

                        <h3 className="mn-h3">Search filters</h3>
                        <Table
                            headers={['Field', 'Description']}
                            rows={[
                                ['Role / Keywords', 'Job title or keywords (e.g. "React Developer")'],
                                ['Location', 'City, state, or country'],
                                ['Company', 'Narrow results to a specific company name'],
                                ['Preferred Sites', 'Pre-selected from your profile (Indeed, LinkedIn, Glassdoor…)'],
                                ['Sort By', 'Relevance, Date, or Salary'],
                                ['Full-time only', 'Filter to full-time positions'],
                                ['Find Contacts', 'Attempt to find recruiter email addresses'],
                            ]}
                        />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Skill matching</h3>
                        <p className="mn-body">
                            When you have skills listed on your Profile, each result shows:
                        </p>
                        <div className="mn-two-col">
                            <div className="mn-col-card">
                                <div className="mn-feature-mini-head" style={{ color: 'var(--lp-good)' }}>Matched skills</div>
                                <p>Skills from your profile that appear in the job description — shown as green badges.</p>
                            </div>
                            <div className="mn-col-card">
                                <div className="mn-feature-mini-head">Other skills</div>
                                <p>Skills mentioned in the job but not in your profile — shown as grey badges to highlight gaps.</p>
                            </div>
                        </div>

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Job sources</h3>
                        <Table
                            headers={['Source', 'Type']}
                            rows={[
                                ['Adzuna', 'Aggregated web job listings'],
                                ['Infopark', 'Kerala IT park — scraped directly'],
                                ['Technopark', 'Kerala IT park — scraped directly'],
                                ['Cyberpark', 'Kerala IT park — scraped directly'],
                                ['KINFRA Hi-Tech Park', 'Kozhikode industrial park'],
                                ['Smart City Kozhikode', 'Smart city tech listings'],
                                ['Malabar Business Center', 'Regional business center'],
                                ['LinkedIn / Indeed / Naukri / Glassdoor', 'Via browser extension'],
                            ]}
                        />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Auto Apply</h3>
                        <Steps steps={[
                            'Tick the checkboxes on the results you want to apply for.',
                            'Optionally choose a specific resume from the dropdown.',
                            'Click Auto Apply.',
                            'Jobs with a recruiter email are queued for immediate email delivery.',
                            'Jobs without an email are added as Pending with an apply link — you visit them manually.',
                            'You are redirected to the Jobs page with a summary.',
                        ]} />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Saved Search Alerts</h3>
                        <p className="mn-body">After a search:</p>
                        <Steps steps={[
                            'Click Save This Search and give it a name.',
                            'Toggle the alert Active.',
                            'BulkApply periodically checks for new listings matching this search.',
                            'You receive a notification when new jobs are found.',
                            'Manage alerts from the Find Jobs sidebar — toggle on/off or delete.',
                        ]} />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        COMPANY REPLIES
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="replies" className="mn-section">
                        <SectionHead
                            id="replies"
                            icon={ICONS.inbox}
                            title="Company Replies"
                            subtitle="A unified inbox that syncs recruiter replies to their original applications."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → Replies</strong>.</p>
                        <p className="mn-body">
                            BulkApply connects to your email via IMAP and pulls replies from recruiters,
                            matching each one to the application it answers.
                        </p>

                        <Table
                            headers={['Feature', 'Description']}
                            rows={[
                                ['Sync Now', 'Manually pull new replies (limit: 6 syncs/min)'],
                                ['Search', 'Search by subject, sender name, or email'],
                                ['Unread only', 'Toggle to show just unread replies'],
                                ['Mark as read', 'Click a reply to mark it read'],
                                ['Mark All Read', 'Clear the unread badge in one action'],
                                ['Company / Job link', 'Each reply shows the linked company name and job title'],
                            ]}
                        />

                        <Table
                            headers={['Counter', 'Meaning']}
                            rows={[
                                ['Total', 'All synced replies'],
                                ['Unread', 'Replies not yet read'],
                                ['Matched', 'Replies successfully linked to an application'],
                            ]}
                        />

                        <Callout type="info">
                            IMAP sync uses the same Gmail credentials you entered in the Email Configuration section.
                            No separate setup is needed.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ATS CHECK
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="ats-check" className="mn-section">
                        <SectionHead
                            id="ats-check"
                            icon={ICONS.ats}
                            title="Resume ATS Checker"
                            subtitle="Analyse your resume for Applicant Tracking System compatibility."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → ATS Check</strong>.</p>
                        <p className="mn-body">
                            The tool reads your active resume and generates a scored report highlighting issues
                            that ATS software commonly penalises.
                        </p>

                        <CheckList items={[
                            'Checks file format compatibility',
                            'Analyses heading structure and section labels',
                            'Scores keyword density against your Preferred Role',
                            'Flags formatting issues (tables, columns, images)',
                            'Shows a prioritised list of improvements',
                        ]} />

                        <Callout type="info">
                            This feature must be enabled by your administrator. If the page shows a 403 error,
                            contact your admin to enable the <strong>Resume ATS Checker</strong> feature flag.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        BILLING
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="billing" className="mn-section">
                        <SectionHead
                            id="billing"
                            icon={ICONS.billing}
                            title="Billing & Plans"
                            subtitle="View subscription plans and submit a payment request."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → Billing</strong>.</p>

                        <h3 className="mn-h3">Subscribing — step by step</h3>
                        <Steps steps={[
                            'Choose a plan and click Subscribe.',
                            'Make a UPI payment to the UPI ID shown on the page.',
                            'Enter your Transaction Reference number exactly as shown in your UPI app.',
                            'Optionally upload a payment screenshot (JPG, PNG, or PDF — max 5 MB).',
                            'Click Submit.',
                            'An admin reviews the payment. Once approved, your plan activates automatically.',
                            'You receive an email notification on approval or rejection.',
                        ]} />

                        <Table
                            headers={['Subscription Status', 'Meaning']}
                            rows={[
                                ['Trial', 'Free trial period is active — full access'],
                                ['Active', 'Plan is running — full access'],
                                ['Expired', 'Plan ended — features may be restricted'],
                            ]}
                        />

                        <Callout type="info">
                            You can only have one pending payment request per plan at a time.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        SUPPORT
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="support" className="mn-section">
                        <SectionHead
                            id="support"
                            icon={ICONS.support}
                            title="Support Tickets"
                            subtitle="Submit a support request and track the conversation with the admin team."
                        />

                        <h3 className="mn-h3">Submitting a ticket</h3>
                        <Steps steps={[
                            'Go to the Contact page (visible to guests and logged-in users).',
                            'Fill in your name, email, subject, and message.',
                            'Optionally attach a file.',
                            'Submit — a ticket is created and the admin is notified.',
                        ]} />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Managing your tickets</h3>
                        <p className="mn-body">Navigate to <strong>Sidebar → Support → My Tickets</strong>.</p>
                        <CheckList items={[
                            'See all submitted tickets with their status (Open / In Progress / Resolved / Closed)',
                            'Click a ticket to view the full conversation thread',
                            'Add a reply to respond to an admin message',
                            'Download any attachments shared by the admin',
                        ]} />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        BROWSER EXTENSION
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="extension" className="mn-section">
                        <SectionHead
                            id="extension"
                            icon={ICONS.extension}
                            title="Browser Extension"
                            subtitle="Capture job listings from major job boards with one click while you browse."
                        />

                        <p className="mn-body">Navigate to <strong>Sidebar → Extension</strong> for install instructions.</p>

                        <h3 className="mn-h3">Supported sites</h3>
                        <div className="mn-ext-grid">
                            {['LinkedIn', 'Indeed', 'Naukri', 'Glassdoor', 'Greenhouse', 'Lever'].map((s) => (
                                <div key={s} className="mn-ext-chip">{s}</div>
                            ))}
                        </div>

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>How to use</h3>
                        <Steps steps={[
                            'Install the BulkApply Chrome extension from the Extension page.',
                            'Browse to a job listing on any supported site.',
                            'Click the BulkApply extension icon in your browser toolbar.',
                            'The job details (company, title, recruiter info, apply link) are captured automatically.',
                            'The job appears in your Jobs list instantly — ready to send.',
                        ]} />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ADMIN OVERVIEW
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="admin-overview" className="mn-section">
                        <SectionHead
                            id="admin-overview"
                            icon={ICONS.admin}
                            title="Admin Guide"
                            subtitle="The admin panel gives full control over users, plans, system health, and features."
                        />

                        <AdminBadge />

                        <p className="mn-body" style={{ marginTop: 12 }}>
                            Access the admin panel at <code>/admin</code>. You must have the <strong>admin role</strong> to enter.
                        </p>

                        <Screenshot
                            src={`${IMG}/dashboard.png`}
                            alt="BulkApply admin dashboard"
                            caption="The Admin Dashboard: platform-wide stats for users, applications, queue, and resumes."
                        />

                        <Table
                            headers={['Admin Card', 'Metric shown']}
                            rows={[
                                ['Total / Active Users', 'All accounts and active accounts'],
                                ['New Today', 'Accounts registered today'],
                                ['Total Applications', 'All job applications across all users'],
                                ['Pending / Queued / Sent / Failed', 'Application status breakdown'],
                                ['Email Success Rate', '% of applications successfully sent'],
                                ['Queue (Active / Failed)', 'Background jobs waiting or failed'],
                                ['Total Resumes', 'Uploaded resume files across all users'],
                            ]}
                        />
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ADMIN USERS
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="admin-users" className="mn-section">
                        <SectionHead
                            id="admin-users"
                            icon={ICONS.profile}
                            title="User Management"
                            subtitle="View, control, and impersonate any user account."
                        />

                        <AdminBadge />

                        <p className="mn-body" style={{ marginTop: 12 }}>Navigate to <strong>Admin → Users</strong>.</p>

                        <Table
                            headers={['Action', 'Description']}
                            rows={[
                                ['Toggle Active', 'Enable or disable login access for the user'],
                                ['Verify Email', 'Manually mark a user\'s email as verified'],
                                ['Reset Password', 'Generate and email a new temporary password'],
                                ['Change Role', 'Promote to admin or demote to regular user'],
                                ['Login As (Impersonate)', 'Log in as the user to debug issues — click Return to Admin to exit'],
                                ['Update Trial End Date', 'Extend or shorten the free trial period'],
                                ['Assign Subscription', 'Manually assign a plan without a payment'],
                                ['Cancel Subscription', 'Remove the user\'s active plan'],
                                ['Export CSV', 'Download the full user list as CSV'],
                                ['Delete User', 'Permanently delete the account and all associated data'],
                            ]}
                        />

                        <Callout type="warn">
                            Deleting a user is permanent and removes all their applications, resumes, and data.
                        </Callout>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ADMIN PLANS & PAYMENTS
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="admin-plans" className="mn-section">
                        <SectionHead
                            id="admin-plans"
                            icon={ICONS.billing}
                            title="Plans & Payments"
                            subtitle="Create subscription plans and review user payment requests."
                        />

                        <AdminBadge />

                        <h3 className="mn-h3" style={{ marginTop: 12 }}>Managing plans</h3>
                        <p className="mn-body">Navigate to <strong>Admin → Plans</strong>.</p>
                        <Table
                            headers={['Field', 'Description']}
                            rows={[
                                ['Name', 'Plan display name (e.g. "Pro Monthly")'],
                                ['Price', 'Plan price in your currency'],
                                ['Duration Days', 'How many days the plan lasts'],
                                ['Email Limit', 'Max emails per billing period (null = unlimited)'],
                                ['Description', 'Features shown on the Billing page'],
                                ['Active', 'Whether the plan is visible to users'],
                            ]}
                        />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Reviewing payment requests</h3>
                        <p className="mn-body">Navigate to <strong>Admin → Payment Requests</strong>.</p>
                        <Steps steps={[
                            'Find the pending request in the list.',
                            'Click View Screenshot to download and verify the UPI confirmation.',
                            'Cross-check the transaction reference number.',
                            <>Click <strong>Approve</strong> to activate the plan — the user receives a confirmation notification.</>,
                            <>Click <strong>Reject</strong> with a reason if the payment cannot be verified — the user receives a rejection notification.</>,
                        ]} />

                        <h3 className="mn-h3" style={{ marginTop: 28 }}>Free Access</h3>
                        <p className="mn-body">Navigate to <strong>Admin → Free Access</strong> to grant any user unlimited access without payment. Search for a user, set an optional expiry, and click Grant. To revoke, click Revoke next to the entry.</p>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ADMIN FEATURES
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="admin-features" className="mn-section">
                        <SectionHead
                            id="admin-features"
                            icon={ICONS.extension}
                            title="Feature Flags"
                            subtitle="Toggle individual platform features on or off without deploying code."
                        />

                        <AdminBadge />

                        <p className="mn-body" style={{ marginTop: 12 }}>Navigate to <strong>Admin → Features</strong>.</p>

                        <Table
                            headers={['Flag', 'Controls']}
                            rows={[
                                ['Resume ATS Checker', 'The /resume-check page'],
                                ['Job Search page', 'The /search (Find Jobs) page'],
                                ['Company Insights', 'Company insights endpoint'],
                                ['Chrome Extension API', 'The API used by the browser extension'],
                                ['Resume auto-parse on upload', 'Auto-fill profile from resume'],
                                ['Email open/click tracking', 'Tracking pixel and click redirect'],
                                ['Follow-up emails', 'Automatic follow-up job dispatcher'],
                                ['Webhook notifications', 'Outbound webhook calls'],
                                ['Saved search alerts', 'Saved search background checker'],
                                ['Automatic Gmail sync', 'Background IMAP sync job'],
                            ]}
                        />

                        <p className="mn-body" style={{ marginTop: 16 }}>
                            Job sources (Adzuna, Infopark, Technopark, Cyberpark, etc.) also appear here as
                            individual toggles and can be reordered from <strong>Admin → Job Sources</strong>.
                        </p>
                    </section>

                    <Divider />

                    {/* ══════════════════════════════════════
                        ADMIN SYSTEM
                    ══════════════════════════════════════ */}
                    <section data-mn-section id="admin-system" className="mn-section">
                        <SectionHead
                            id="admin-system"
                            icon={ICONS.admin}
                            title="System & Monitoring"
                            subtitle="Everything for keeping the platform healthy — queue, logs, backups, and security."
                        />

                        <AdminBadge />

                        <div className="mn-admin-grid">
                            {[
                                { title: 'Queue Monitor', path: 'Admin → Queue',           desc: 'View active batch sends, cancel a running batch, and delete failed background jobs.' },
                                { title: 'Analytics',     path: 'Admin → Analytics',       desc: 'Platform-wide charts: signups, applications sent per day, open and click rates.' },
                                { title: 'Reports',       path: 'Admin → Reports',         desc: 'Export User, Application, or Revenue reports as CSV.' },
                                { title: 'Support',       path: 'Admin → Support',         desc: 'Manage all user support tickets — reply, update status, and download attachments.' },
                                { title: 'CMS Pages',     path: 'Admin → CMS',             desc: 'Create and edit public pages (Terms, Privacy, FAQ, etc.) at /p/{slug}.' },
                                { title: 'API Config',    path: 'Admin → API',             desc: 'Update API credentials (e.g. Adzuna App ID and key).' },
                                { title: 'Webhooks',      path: 'Admin → Webhooks',        desc: 'View outbound webhook delivery logs and retry failed deliveries.' },
                                { title: 'Settings',      path: 'Admin → Settings',        desc: 'Edit platform settings — file upload limits, UPI payee info, branding.' },
                                { title: 'Security',      path: 'Admin → Security',        desc: 'Enable/disable admin 2FA and manage IP allowlist/blocklist rules.' },
                                { title: 'Audit Logs',    path: 'Admin → Audit Logs',      desc: 'Full chronological trail of every administrative action with actor, IP, and diff.' },
                                { title: 'Backup',        path: 'Admin → Backup',          desc: 'Run a database backup, download archives, or delete old ones.' },
                                { title: 'Storage',       path: 'Admin → Storage',         desc: 'View disk usage by category and clean the view cache.' },
                                { title: 'Monitoring',    path: 'Admin → Monitoring',      desc: 'Real-time system health: uptime, PHP version, queue worker, memory.' },
                                { title: 'Logs',          path: 'Admin → Logs',            desc: 'View the Laravel application log for errors and warnings.' },
                                { title: 'Database Tools',path: 'Admin → Database Tools',  desc: 'Run safe Artisan commands: migrate, clear cache, optimize — without SSH.' },
                            ].map((item) => (
                                <div key={item.title} className="mn-admin-card">
                                    <div className="mn-admin-card-title">{item.title}</div>
                                    <div className="mn-admin-card-path">{item.path}</div>
                                    <p className="mn-admin-card-desc">{item.desc}</p>
                                </div>
                            ))}
                        </div>

                        <Callout type="info" style={{ marginTop: 28 }}>
                            <strong>Security tip:</strong> Use <strong>IP Rules</strong> (Admin → Security) to allowlist
                            only trusted IP addresses for admin panel access. Add your own IP first before adding an
                            allowlist rule to avoid locking yourself out.
                        </Callout>
                    </section>

                    {/* Bottom CTA */}
                    <div className="mn-bottom-cta">
                        <h3>Ready to start applying?</h3>
                        <p>Set up your profile, upload your resume, and send your first batch of applications today.</p>
                        <div className="mn-bottom-cta-btns">
                            <Link href="/register" className="lp-btn lp-btn-primary lp-btn-lg">Start your free trial</Link>
                            <Link href="/login" className="lp-btn lp-btn-ghost lp-btn-lg">Log in</Link>
                        </div>
                    </div>

                </main>
            </div>

            {/* Footer */}
            <footer className="lp-footer">
                <div className="lp-shell">
                    <div className="lp-footer-top">
                        <div className="lp-brand"><span className="dot">B</span> BulkApply</div>
                        <div className="lp-footer-links">
                            <a href="/#features">Features</a>
                            <a href="/#how-it-works">How it works</a>
                            <Link href="/p/pricing">Pricing</Link>
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

Manual.layout = (page) => page;
