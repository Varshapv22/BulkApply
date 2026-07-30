import React from 'react';
import { Head, Link } from '@inertiajs/react';
import LandingHeader from '../LandingHeader';
import '../../css/landing.css';

function Check() {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
            <polyline points="20 6 9 17 4 12" />
        </svg>
    );
}

function Frown() {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="12" r="9.5" />
            <path d="M16 16.5c-.8-1.3-2.2-2-4-2s-3.2.7-4 2" />
            <line x1="9" y1="9.5" x2="9.01" y2="9.5" />
            <line x1="15" y1="9.5" x2="15.01" y2="9.5" />
        </svg>
    );
}

function Icon({ path }) {
    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
            {path}
        </svg>
    );
}

const ICONS = {
    search: <><circle cx="11" cy="11" r="7" /><line x1="21" y1="21" x2="16.65" y2="16.65" /></>,
    resume: <><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" /><path d="m9 15 2 2 4-4" /></>,
    mail: <><rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-10 5L2 7" /></>,
    puzzle: <><path d="M19.4 7.9c-.05.3.06.65.3.88l1.5 1.5c.47.47.7 1.1.7 1.7 0 .6-.23 1.24-.7 1.7l-1.6 1.6a1 1 0 0 1-.84.28c-.47-.07-.8-.48-.97-.93a2.5 2.5 0 1 0-3.21 3.21c.44.17.86.5.93.97a1 1 0 0 1-.28.84l-1.6 1.6a2.4 2.4 0 0 1-3.4 0l-1.57-1.57a1 1 0 0 0-.88-.28c-.49.07-.84.5-1.02.97a2.5 2.5 0 1 1-3.24-3.24c.47-.18.9-.53.97-1.02a1 1 0 0 0-.29-.88L2.1 12.9a2.4 2.4 0 0 1 0-3.4l1.6-1.62a1 1 0 0 1 .92-.3c.5.06.85.48 1.03.95a2.5 2.5 0 1 0 3.25-3.25c-.46-.18-.89-.53-.95-1.02a1 1 0 0 1 .3-.92l1.62-1.6a2.4 2.4 0 0 1 3.4 0l1.57 1.57c.23.23.56.34.88.29.49-.07.84-.5 1.02-.97a2.5 2.5 0 1 1 3.24 3.24c-.47.18-.9.53-.97 1.02z" /></>,
    chart: <><path d="M3 3v18h18" /><path d="m19 9-5 5-4-4-3 3" /></>,
    inbox: <><path d="M22 12h-6l-2 3h-4l-2-3H2" /><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z" /></>,
    shield: <><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path d="m9 12 2 2 4-4" /></>,
    upload: <><path d="M12 3v12m0 0-4-4m4 4 4-4" /><path d="M4 19h16" /></>,
    pin: <><path d="M12 22s7-6.5 7-12a7 7 0 1 0-14 0c0 5.5 7 12 7 12z" /><circle cx="12" cy="10" r="3" /></>,
    building: <><rect x="4" y="3" width="16" height="18" rx="1" /><path d="M9 21v-4h6v4" /><path d="M9 7h1M14 7h1M9 11h1M14 11h1M9 15h1M14 15h1" /></>,
    arrow: <><line x1="5" y1="12" x2="19" y2="12" /><polyline points="12 5 19 12 12 19" /></>,
};

const IMG = '/images/marketing';

// Real sourcing — three IT parks BulkApply scrapes directly, plus the boards
// and ATSes the browser extension one-click-applies on. Icons are simple
// brand-tinted monograms, not traced logo artwork.
const SOURCES = [
    { name: 'Technopark', svg: ICONS.building, bg: 'var(--lp-primary-soft)', fg: 'var(--lp-primary-dark)' },
    { name: 'Infopark', svg: ICONS.building, bg: 'var(--lp-primary-soft)', fg: 'var(--lp-primary-dark)' },
    { name: 'Cyberpark', svg: ICONS.building, bg: 'var(--lp-primary-soft)', fg: 'var(--lp-primary-dark)' },
    { name: 'LinkedIn', glyph: 'in', bg: '#0A66C2', fg: '#fff' },
    { name: 'Indeed', glyph: 'id', bg: '#2164F3', fg: '#fff' },
    { name: 'Naukri', glyph: 'N', bg: '#2B3A67', fg: '#fff' },
    { name: 'Glassdoor', glyph: 'G', bg: '#0CAA41', fg: '#fff' },
    { name: 'Greenhouse', glyph: 'GH', bg: '#24A47F', fg: '#fff' },
    { name: 'Lever', glyph: 'L', bg: '#1B1B1F', fg: '#fff' },
];

export default function Welcome() {
    return (
        <div className="lp-page">
            <Head>
                <title>Bulk Job Application Automation for Kerala Tech Jobs</title>
                <meta
                    name="description"
                    content="Search Technopark, Infopark, Cyberpark and the wider web in one place, send personalised applications with your resume in one click, and track every open, click and reply. Free for 7 days."
                />
                <meta property="og:title" content="BulkApply — Apply to hundreds of jobs, on autopilot" />
                <meta
                    property="og:description"
                    content="Search Kerala's tech parks and the whole web, bulk-apply with a personalised email, and track your pipeline from one dashboard."
                />
                <meta property="og:image" content={`${IMG}/dashboard.png`} />
                <meta property="og:type" content="website" />
            </Head>

            {/* ---------- Nav ---------- */}
            <LandingHeader />

            {/* ---------- Hero ---------- */}
            <section className="lp-hero">
                <div className="lp-shell">
                    <div className="lp-hero-inner">
                        <span className="lp-eyebrow">Built for Kerala's tech job market</span>
                        <h1>Apply to hundreds of jobs. Track every reply. On autopilot.</h1>
                        <p className="lp-hero-lead">
                            BulkApply searches Technopark, Infopark, Cyberpark and the wider web in one place, sends a personalised
                            application with your resume attached in one click, and tracks every open, click and reply — all from
                            one dashboard.
                        </p>
                        <div className="lp-hero-ctas">
                            <Link href="/register" className="lp-btn lp-btn-primary lp-btn-lg">Start your 7-day free trial</Link>
                            <a href="#how-it-works" className="lp-btn lp-btn-ghost lp-btn-lg">See how it works</a>
                        </div>
                        <div className="lp-hero-trust">
                            <span><Icon path={ICONS.shield} /> No credit card required</span>
                            <span><Icon path={ICONS.shield} /> 7-day free trial</span>
                            <span><Icon path={ICONS.shield} /> Self-hosted · pay by UPI</span>
                        </div>
                    </div>

                    <div className="lp-hero-shot">
                        <div className="lp-shot-frame">
                            <div className="lp-shot-chrome"><span /><span /><span /></div>
                            <img src={`${IMG}/dashboard.png`} alt="BulkApply dashboard showing total jobs, success rate, open rate and application pipeline" loading="eager" />
                        </div>
                    </div>
                </div>
            </section>

            {/* ---------- Problem vs solution ---------- */}
            <section className="lp-section" style={{ paddingBottom: 0 }}>
                <div className="lp-shell">
                    <div className="lp-section-head lp-center">
                        <div className="lp-kicker">Why bulk apply</div>
                        <h2>Same job boards. A completely different experience.</h2>
                        <p>You don't need another tab to check — you need one place that already checked them all.</p>
                    </div>

                    <div className="lp-vs">
                        <div className="lp-vs-panel lp-vs-problem">
                            <div className="lp-vs-label">Applying the old way</div>
                            <div className="lp-vs-points">
                                <div className="lp-vs-point"><Frown /> Ten tabs open, one job found</div>
                                <div className="lp-vs-point"><Frown /> The same resume for every role</div>
                                <div className="lp-vs-point"><Frown /> No idea if anyone ever opened it</div>
                                <div className="lp-vs-point"><Frown /> Hours lost copy-pasting details</div>
                            </div>
                            <div className="lp-vs-cluster-label">The tabs you'd normally juggle, one by one</div>
                            <div className="lp-vs-cluster">
                                {SOURCES.map((s) => (
                                    <div className="lp-vs-chip" key={s.name}>
                                        <span className="lp-source-icon" style={{ background: s.bg, color: s.fg }}>
                                            {s.svg ? <Icon path={s.svg} /> : s.glyph}
                                        </span>
                                        <span className="lp-vs-chip-name">{s.name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>

                        <div className="lp-vs-divider"><span>V/S</span></div>

                        <div className="lp-vs-panel lp-vs-solution">
                            <div className="lp-vs-label">Applying with BulkApply</div>
                            <div className="lp-vs-points">
                                <div className="lp-vs-point is-good"><Check /> One dashboard for every platform</div>
                                <div className="lp-vs-point is-good"><Check /> A personalised resume &amp; message, every send</div>
                                <div className="lp-vs-point is-good"><Check /> Know the moment it's opened or clicked</div>
                                <div className="lp-vs-point is-good"><Check /> One click to apply, not one hour</div>
                            </div>
                            <div className="lp-vs-cluster-label">All searched and saved from one dashboard</div>
                            <div className="lp-vs-cluster is-tidy">
                                {SOURCES.map((s) => (
                                    <div className="lp-source-tile" key={s.name}>
                                        <span className="lp-source-icon" style={{ background: s.bg, color: s.fg }}>
                                            {s.svg ? <Icon path={s.svg} /> : s.glyph}
                                        </span>
                                        <span className="lp-source-name">{s.name}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ---------- How it works ---------- */}
            <section className="lp-section" id="how-it-works">
                <div className="lp-shell">
                    <div className="lp-section-head lp-center">
                        <div className="lp-kicker">How it works</div>
                        <h2>Three steps, not three hundred tabs</h2>
                        <p>No more copy-pasting your resume into forty different portals one at a time.</p>
                    </div>
                    <div className="lp-steps">
                        <div className="lp-step lp-step-1">
                            <div className="lp-step-top">
                                <span className="lp-step-ico"><Icon path={ICONS.search} /></span>
                                <span className="lp-step-num">1</span>
                            </div>
                            <h3>Search once, everywhere</h3>
                            <p>Pull live listings straight from Technopark, Infopark and Cyberpark, or search LinkedIn, Indeed and Naukri — or paste any company's careers page.</p>
                        </div>

                        <div className="lp-step-arrow"><Icon path={ICONS.arrow} /></div>

                        <div className="lp-step lp-step-2">
                            <div className="lp-step-top">
                                <span className="lp-step-ico"><Icon path={ICONS.mail} /></span>
                                <span className="lp-step-num">2</span>
                            </div>
                            <h3>Apply in one click</h3>
                            <p>Select the roles you want. BulkApply sends a personalised email per job — your resume attached, your template's placeholders filled in automatically.</p>
                        </div>

                        <div className="lp-step-arrow"><Icon path={ICONS.arrow} /></div>

                        <div className="lp-step lp-step-3">
                            <div className="lp-step-top">
                                <span className="lp-step-ico"><Icon path={ICONS.chart} /></span>
                                <span className="lp-step-num">3</span>
                            </div>
                            <h3>Track what happens next</h3>
                            <p>See opens, clicks and Gmail replies matched straight to each application, and move every one through Applied → Replied → Interview → Offer.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ---------- Feature showcase ---------- */}
            <section className="lp-section lp-section-alt" id="features">
                <div className="lp-shell">
                    <div className="lp-section-head">
                        <div className="lp-kicker">Your dashboard</div>
                        <h2>Every application, one screen</h2>
                    </div>

                    <div className="lp-feature-row">
                        <div className="lp-feature-copy">
                            <h3>Know exactly where your job search stands</h3>
                            <p>
                                Total jobs, sent count, success rate and open rate at a glance — plus a day-by-day activity chart and
                                a pipeline breakdown so you always know how many replies are sitting in Interview versus Offer.
                            </p>
                            <ul className="lp-feature-list">
                                <li><Check /> Success rate and open/click tracking per email sent</li>
                                <li><Check /> This week vs. last week, so you can tell if you're slowing down</li>
                                <li><Check /> Recent activity feed of every send, right down to the recruiter</li>
                            </ul>
                        </div>
                        <div className="lp-feature-shot">
                            <img src={`${IMG}/dashboard.png`} alt="BulkApply dashboard with activity chart and pipeline breakdown" loading="lazy" />
                        </div>
                    </div>

                    <div className="lp-feature-row lp-reverse">
                        <div className="lp-feature-copy">
                            <h3>Search Kerala's tech parks — and everywhere else</h3>
                            <p>
                                Technopark, Infopark and Cyberpark pull live listings directly. Everything else — LinkedIn, Indeed,
                                Naukri, or a specific company's careers page — pulls matching results from across the web, in the
                                same search box.
                            </p>
                            <ul className="lp-feature-list">
                                <li><Check /> One search box for tech parks, job boards, or a single company</li>
                                <li><Check /> Sort by relevance, most recent, or highest salary</li>
                                <li><Check /> Optional "find company emails" lookup for direct outreach</li>
                            </ul>
                        </div>
                        <div className="lp-feature-shot">
                            <img src={`${IMG}/search.png`} alt="BulkApply Find Jobs search page with Technopark, Infopark and Cyberpark quick picks" loading="lazy" />
                        </div>
                    </div>

                    <div className="lp-feature-row">
                        <div className="lp-feature-copy">
                            <h3>Never lose track of an application again</h3>
                            <p>
                                Import a CSV of leads or add jobs by hand, then send everything pending in one batch. Every row keeps
                                its own status, pipeline stage and tracking — nothing falls through the cracks in a spreadsheet.
                            </p>
                            <ul className="lp-feature-list">
                                <li><Check /> Bulk send with a live progress bar, or send one row at a time</li>
                                <li><Check /> Move each application through your pipeline as it progresses</li>
                                <li><Check /> Export to CSV any time — your data is never locked in</li>
                            </ul>
                        </div>
                        <div className="lp-feature-shot">
                            <img src={`${IMG}/jobs.png`} alt="BulkApply Applications table with pipeline stages and bulk send" loading="lazy" />
                        </div>
                    </div>
                </div>
            </section>

            {/* ---------- Secondary feature grid ---------- */}
            <section className="lp-section">
                <div className="lp-shell">
                    <div className="lp-section-head lp-center">
                        <div className="lp-kicker">And everything around it</div>
                        <h2>The parts that make bulk applying actually work</h2>
                    </div>
                    <div className="lp-grid">
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.resume} /></div>
                            <h3>Resume ATS check</h3>
                            <p>See exactly how your resume survives an Applicant Tracking System, with a ranked list of what to fix first before you start applying.</p>
                        </div>
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.mail} /></div>
                            <h3>Reusable email templates</h3>
                            <p>Write once, personalise every time — templates auto-fill the job title, company, recruiter and your details from placeholders.</p>
                        </div>
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.puzzle} /></div>
                            <h3>Browser extension</h3>
                            <p>Save a job straight from LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse or Lever to your dashboard in one click as you browse.</p>
                        </div>
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.inbox} /></div>
                            <h3>Gmail reply matching</h3>
                            <p>Sync your inbox and every recruiter reply is matched automatically to the application it answers — no manual searching.</p>
                        </div>
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.shield} /></div>
                            <h3>Two-factor authentication</h3>
                            <p>Your resume, applications and email account stay protected with optional 2FA on every sign-in.</p>
                        </div>
                        <div className="lp-card">
                            <div className="lp-card-ico"><Icon path={ICONS.pin} /></div>
                            <h3>Self-hosted, your data</h3>
                            <p>Nothing about your job search lives on someone else's growth dashboard. Billing is simple UPI — no card required.</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* ---------- Final CTA with the login screenshot ---------- */}
            <section className="lp-section lp-section-alt">
                <div className="lp-shell lp-final">
                    <div className="lp-final-copy">
                        <div className="lp-kicker">Get started</div>
                        <h2>Your dashboard is one sign-up away</h2>
                        <p>
                            Create an account, add your resume, and run your first search — free for 7 days, no credit card needed.
                            Already have one? Sign back in and pick up where you left off.
                        </p>
                        <div className="lp-hero-ctas">
                            <Link href="/register" className="lp-btn lp-btn-primary lp-btn-lg">Start your 7-day free trial</Link>
                            <Link href="/login" className="lp-btn lp-btn-ghost lp-btn-lg">Log in</Link>
                        </div>
                        <div className="lp-price-note"><Icon path={ICONS.shield} /> After your trial, plans are billed simply by UPI — 1, 3 or 9 months, no auto-renewal surprises.</div>
                    </div>
                    <div className="lp-final-shot">
                        <img src={`${IMG}/login.png`} alt="BulkApply sign-in page" loading="lazy" />
                    </div>
                </div>
            </section>

            {/* ---------- Footer ---------- */}
            <footer className="lp-footer">
                <div className="lp-shell">
                    <div className="lp-footer-top">
                        <div className="lp-brand"><span className="dot">B</span> BulkApply</div>
                        <div className="lp-footer-links">
                            <a href="#features">Features</a>
                            <a href="#how-it-works">How it works</a>
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

// Public marketing page — never wrapped in the authenticated app's sidebar shell.
Welcome.layout = (page) => page;
