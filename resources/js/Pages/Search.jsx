import React, { useState, useEffect } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { PageHead, Badge, Icons, Spinner, EmptyState, ChipIcon, IconField, CompanyInsightButton, timeAgo } from '../components';

const MAX_SKILL_CHIPS = 4;

function SkillChips({ matched = [], other = [], hasProfileSkills }) {
    if (matched.length === 0 && other.length === 0) {
        return <span className="muted" style={{ fontSize: 12 }}>—</span>;
    }
    const shown = [...matched, ...other].slice(0, MAX_SKILL_CHIPS);
    const extra = matched.length + other.length - shown.length;

    return (
        <div className="skill-chips">
            {shown.map((s, i) => (
                <span key={i} className={`skill-chip${matched.includes(s) ? ' matched' : ''}`}
                    title={matched.includes(s) ? `${s} — matches your skills` : s}>
                    {matched.includes(s) && '✓ '}{s}
                </span>
            ))}
            {extra > 0 && <span className="skill-chip more">+{extra}</span>}
            {!hasProfileSkills && matched.length === 0 && other.length > 0 && (
                <span className="muted" style={{ fontSize: 10.5, width: '100%' }}>
                    Add your skills in <Link href="/profile" style={{ fontSize: 10.5 }}>Settings</Link> to see matches
                </span>
            )}
        </div>
    );
}

/* Shimmering placeholder rows shown while the search runs. */
function SearchingCard({ findContacts }) {
    return (
        <div className="card">
            <div className="searching-box">
                <Spinner dark size={22} />
                <div className="txt">
                    <div className="t1">Searching for jobs…</div>
                    <div className="t2">
                        {findContacts
                            ? 'Also looking up company emails & websites — this can take ~10 seconds.'
                            : 'Fetching matching vacancies.'}
                    </div>
                </div>
            </div>
            <div style={{ display: 'grid', gap: 10, marginTop: 14 }}>
                {[92, 100, 96, 88, 99].map((w, i) => (
                    <div key={i} className="skel" style={{ height: 42, width: `${w}%` }} />
                ))}
            </div>
        </div>
    );
}

const QUICK_SITES = ['Technopark', 'Infopark', 'Cyberpark', 'KINFRA', 'Smart City Kozhikode', 'Malabar Business Center'];

// Big aggregator platforms the backend won't scrape directly (mirrors
// SiteJobService::PLATFORMS) — alerts aren't offered for these since their
// "results" are just an aggregated web search with a disclaimer, not real
// platform-specific listings. Client-side mirror of the server check so the
// Save button doesn't invite a request that will 422.
const ALERT_BLOCKED_PLATFORMS = [
    'indeed', 'naukri', 'linkedin', 'monster', 'foundit', 'glassdoor',
    'shine', 'timesjobs', 'instahyre', 'wellfound', 'angellist', 'ziprecruiter',
    'dice', 'simplyhired',
];
function isAlertBlockedSite(site) {
    const lower = (site || '').trim().toLowerCase();
    return lower !== '' && ALERT_BLOCKED_PLATFORMS.some((p) => lower.includes(p));
}

function Switch({ checked, onChange, label, hint }) {
    return (
        <label className="switch-row">
            <span className={`switch${checked ? ' on' : ''}`} onClick={() => onChange(!checked)}>
                <span className="knob" />
            </span>
            <span className="switch-txt">
                {label} {hint && <span className="muted" style={{ fontSize: 12, fontWeight: 400 }}>{hint}</span>}
            </span>
        </label>
    );
}

function SkillsBar({ profile }) {
    const skills = (profile.skills || '').split(',').map((s) => s.trim()).filter(Boolean);

    if (skills.length === 0) {
        return (
            <div className="skills-bar">
                <ChipIcon icon={Icons.target} />
                <span>
                    Add your skills (or auto-fill them from your resume) to see which jobs match you best.
                </span>
                <Link href="/profile" className="btn btn-ghost btn-sm" style={{ marginLeft: 'auto' }}>Add skills</Link>
            </div>
        );
    }

    return (
        <div className="skills-bar">
            <ChipIcon icon={Icons.target} />
            <span className="skills-bar-label">Matching against your skills:</span>
            <div className="skills-bar-chips">
                {skills.slice(0, 8).map((s) => <span key={s} className="skill-chip matched">{s}</span>)}
                {skills.length > 8 && <span className="skill-chip more">+{skills.length - 8}</span>}
            </div>
            <Link href="/profile" className="btn-link" style={{ marginLeft: 'auto', flexShrink: 0 }}>Edit</Link>
        </div>
    );
}

function SearchForm({ profile, onSearching, searched }) {
    const { data, setData, post, processing } = useForm({
        role: profile.preferred_role || '',
        location: profile.location || '',
        company: '',
        site: '',
        sort_by: 'relevance',
        full_time: false,
        find_contacts: false,
    });
    const [saving, setSaving] = useState(false);

    const hasSite = data.site.trim() !== '';
    const hasCompany = data.company.trim() !== '';
    const canSave = searched && data.role.trim() !== '' && !isAlertBlockedSite(data.site);

    const submit = (e) => {
        e.preventDefault();
        post('/search', {
            preserveScroll: true,
            onStart: () => onSearching({ active: true, findContacts: data.find_contacts && !hasSite }),
            onFinish: () => onSearching({ active: false, findContacts: false }),
        });
    };

    const saveSearch = () => {
        setSaving(true);
        router.post('/saved-searches', { role: data.role, location: data.location, site: data.site }, {
            preserveScroll: true,
            onFinish: () => setSaving(false),
        });
    };

    return (
        <div className="card hero-card">
            <div className="hero-card-head">
                <span className="hero-card-ico"><ChipIcon icon={Icons.search} /></span>
                <div>
                    <h2 style={{ margin: 0 }}>Find your next role</h2>
                    <p className="hint" style={{ margin: '3px 0 0' }}>
                        Search a role across the web, or jump straight to a tech park / platform below.
                    </p>
                </div>
            </div>

            <form onSubmit={submit}>
                <div className="row">
                    <div style={{ flex: 2 }}>
                        <label>Job Role / Title {hasSite || hasCompany ? '' : '*'}</label>
                        <IconField icon={Icons.briefcase} type="text" required={!hasSite && !hasCompany} value={data.role}
                            onChange={(e) => setData('role', e.target.value)}
                            placeholder="e.g. Software Engineer, Data Analyst, Product Manager" />
                    </div>
                    <div>
                        <label>Location</label>
                        <IconField icon={Icons.pin} type="text" value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            placeholder="e.g. Kerala, New York, Remote" />
                    </div>
                </div>

                <div className="row" style={{ marginTop: 12 }}>
                    <div style={{ flex: 1 }}>
                        <label>
                            Company{' '}
                            <span className="muted" style={{ fontWeight: 400 }}>
                                (optional — leave Role blank to list all jobs at that company)
                            </span>
                        </label>
                        <IconField icon={Icons.building} type="text" value={data.company}
                            onChange={(e) => setData('company', e.target.value)}
                            placeholder="e.g. Google, Infosys, TCS, Wipro" />
                    </div>
                </div>

                <div className="or-divider"><span>or search a specific site</span></div>

                <div>
                    <label>Job site, platform or company <span className="muted" style={{ fontWeight: 400 }}>(optional)</span></label>
                    <IconField icon={Icons.building} type="text" value={data.site}
                        onChange={(e) => setData('site', e.target.value)}
                        placeholder="e.g. Technopark, Infopark, Cyberpark, KINFRA, or a careers-page URL" />
                    <div className="quick-picks">
                        {QUICK_SITES.map((s) => (
                            <button type="button" key={s}
                                className={`quick-pick${data.site === s ? ' active' : ''}`}
                                onClick={() => setData('site', data.site === s ? '' : s)}>
                                {s}
                            </button>
                        ))}
                    </div>
                    <p className="hint" style={{ margin: '10px 0 0' }}>
                        Tech parks (Technopark, Infopark, Cyberpark) pull live listings directly; platforms and
                        company names pull matching results from across the web. Leave blank to search everywhere.
                    </p>
                </div>

                {!hasSite && (
                    <div className="search-options-row">
                        <div style={{ minWidth: 180 }}>
                            <label>Sort by</label>
                            <select value={data.sort_by} onChange={(e) => setData('sort_by', e.target.value)}>
                                <option value="relevance">Relevance</option>
                                <option value="date">Most recent</option>
                                <option value="salary">Highest salary</option>
                            </select>
                        </div>
                        <Switch checked={data.full_time} onChange={(v) => setData('full_time', v)} label="Full-time only" />
                        <Switch checked={data.find_contacts} onChange={(v) => setData('find_contacts', v)}
                            label="Find company emails" hint="(slower)" />
                    </div>
                )}

                <div style={{ display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' }}>
                    <button type="submit" className="btn btn-primary btn-lg" disabled={processing}>
                        {processing ? <><Spinner /> Searching…</> : <>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                {Icons.search}
                            </svg>
                            Search Jobs
                        </>}
                    </button>
                    {searched && (
                        <button type="button" className="btn btn-ghost" disabled={!canSave || saving} onClick={saveSearch}
                            title={isAlertBlockedSite(data.site)
                                ? "Alerts aren't supported for this platform — it only shows aggregated web results."
                                : 'Get notified when new listings match this search'}>
                            {saving ? <><Spinner /> Saving…</> : 'Save this search'}
                        </button>
                    )}
                </div>
            </form>
        </div>
    );
}

function SavedSearchesPanel({ searches }) {
    const [busyId, setBusyId] = useState(null);

    if (!searches || searches.length === 0) return null;

    const toggle = (s) => {
        setBusyId(s.id);
        router.post(`/saved-searches/${s.id}/toggle`, {}, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };
    const remove = (s) => {
        setBusyId(s.id);
        router.delete(`/saved-searches/${s.id}`, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };

    return (
        <div className="card">
            <div className="hero-card-head">
                <span className="hero-card-ico"><ChipIcon icon={Icons.target} /></span>
                <div>
                    <h2 style={{ margin: 0 }}>Saved searches</h2>
                    <p className="hint" style={{ margin: '3px 0 0' }}>
                        We'll check these in the background and notify you when new listings show up.
                    </p>
                </div>
            </div>
            <div style={{ display: 'grid', gap: 10, marginTop: 14 }}>
                {searches.map((s) => (
                    <div key={s.id} style={{
                        display: 'flex', alignItems: 'center', gap: 12, flexWrap: 'wrap',
                        padding: '10px 14px', borderRadius: 8, border: '1px solid var(--border)',
                    }}>
                        <div style={{ flex: 1, minWidth: 180 }}>
                            <strong>{s.role || 'Any role'}</strong>
                            {s.location && <span className="muted"> · {s.location}</span>}
                            {s.site && <span className="muted"> · {s.site}</span>}
                            <div className="muted" style={{ fontSize: 11, marginTop: 2 }}>
                                {s.last_checked_at ? `Last checked ${timeAgo(s.last_checked_at)}` : 'Not checked yet'}
                            </div>
                        </div>
                        <Switch checked={s.is_active} onChange={() => toggle(s)} label={s.is_active ? 'Active' : 'Paused'} />
                        <button type="button" className="btn btn-ghost btn-sm" disabled={busyId === s.id} onClick={() => remove(s)}>
                            Delete
                        </button>
                    </div>
                ))}
            </div>
        </div>
    );
}

export default function Search({ profile, jobSites, results, searched, searchError, hasDocuments, resumes = [], savedSearches = [] }) {
    const [selected, setSelected] = useState(() => new Set());
    const [searching, setSearching] = useState({ active: false, findContacts: false });
    const [selectedResumeId, setSelectedResumeId] = useState('');

    // Fresh results → clear stale selection.
    useEffect(() => { setSelected(new Set()); }, [results]);

    const toggle = (i) => {
        const next = new Set(selected);
        next.has(i) ? next.delete(i) : next.add(i);
        setSelected(next);
    };
    const toggleAll = () => {
        setSelected(selected.size === results.length ? new Set() : new Set(results.map((_, i) => i)));
    };

    const [applying, setApplying] = useState(false);

    const apply = () => {
        const jobs = [...selected].map((i) => {
            const j = results[i];
            return {
                company: j.company, job_title: j.job_title, location: j.location,
                recruiter_email: j.recruiter_email, job_url: j.job_url,
                apply_url: j.apply_url, source: j.source,
            };
        });
        setApplying(true);
        router.post('/search/apply', { jobs, resume_id: selectedResumeId || null }, { onFinish: () => setApplying(false) });
    };

    return (
        <>
            <PageHead title="Find Jobs"
                subtitle="Search for job vacancies across multiple sites. Select matching jobs and auto-apply in one click." />

            {!hasDocuments && (
                <div className="alert alert-warn">
                    Upload your resume and cover letter on <Link href="/profile">Settings</Link> before applying.
                </div>
            )}

            <SearchForm profile={profile} onSearching={setSearching} searched={searched} />

            <SavedSearchesPanel searches={savedSearches} />

            <SkillsBar profile={profile} />

            {searching.active && <SearchingCard findContacts={searching.findContacts} />}

            {!searching.active && searchError && <div className="alert alert-error"><div className="alert-body">{searchError}</div></div>}

            {!searching.active && searched && !searchError && (
                <div className="card">
                    <div className="toolbar">
                        <h2 style={{ margin: 0 }}>{results.length} Job(s) Found</h2>
                        <div className="spacer" />
                        {results.length > 0 && (
                            <button className="btn btn-ghost btn-sm" onClick={toggleAll}>
                                {selected.size === results.length ? 'Deselect All' : 'Select All'}
                            </button>
                        )}
                    </div>

                    {results.length === 0 ? (
                        <EmptyState icon="search" title="No jobs found">
                            Nothing matched your criteria. Try a broader role, another location,
                            or search a tech park like Technopark or Infopark.
                        </EmptyState>
                    ) : (
                        <>
                            <div className="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style={{ width: 30 }}>
                                                <input type="checkbox" checked={selected.size === results.length}
                                                    onChange={toggleAll} />
                                            </th>
                                            <th>Role</th><th>Company</th><th>Location</th><th>Skills</th><th>Company email</th><th>Website</th><th>Apply Via</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {results.map((job, i) => (
                                            <tr key={i} className={selected.has(i) ? 'selected' : ''}
                                                onClick={(e) => { if (e.target.tagName !== 'A' && e.target.tagName !== 'INPUT') toggle(i); }}
                                                style={{ cursor: 'pointer' }}>
                                                <td><input type="checkbox" checked={selected.has(i)} onChange={() => toggle(i)} /></td>
                                                <td>
                                                    <strong>{job.job_title}</strong>
                                                    <br /><span className="muted" style={{ fontSize: 11 }}>{job.source}</span>
                                                </td>
                                                <td>
                                                    <div className="co-cell">
                                                        <span className="co-avatar">{(job.company || '?')[0].toUpperCase()}</span>
                                                        <div className="co-info">
                                                            <span className="co-name">{job.company}</span>
                                                            <CompanyInsightButton company={job.company} role={job.job_title}
                                                                website={job.company_website} />
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{job.location || '—'}</td>
                                                <td>
                                                    <SkillChips matched={job.skills_matched} other={job.skills_other}
                                                        hasProfileSkills={profile.has_skills} />
                                                </td>
                                                <td>
                                                    {job.company_email
                                                        ? <a className="cell-chip" href={`mailto:${job.company_email}`} title={job.company_email}>
                                                            <ChipIcon icon={Icons.mail} />{job.company_email}
                                                          </a>
                                                        : <span className="muted">—</span>}
                                                </td>
                                                <td>
                                                    {job.company_website
                                                        ? <a className="cell-chip" href={job.company_website} target="_blank" rel="noopener" title={job.company_website}>
                                                            <ChipIcon icon={Icons.globe} />{job.company_website.replace(/^https?:\/\/(www\.)?/, '').replace(/\/$/, '')}
                                                          </a>
                                                        : <span className="muted">—</span>}
                                                </td>
                                                <td>
                                                    {job.apply_type === 'email'
                                                        ? <Badge status="sent"><span title={job.recruiter_email}>Email</span></Badge>
                                                        : job.apply_url
                                                            ? <a href={job.apply_url} target="_blank" rel="noopener" className="badge pending" style={{ textDecoration: 'none' }}>
                                                                {/google\.com/i.test(job.apply_url) ? 'Google Jobs' : 'Portal'}
                                                              </a>
                                                            : <span className="muted">—</span>}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div style={{ marginTop: 16, display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                                {resumes.length > 0 && (
                                    <select value={selectedResumeId} onChange={e => setSelectedResumeId(e.target.value)} style={{ padding: '8px 12px', borderRadius: 6, border: '1px solid var(--border)' }}>
                                        <option value="">Default Resume</option>
                                        {resumes.map(r => (
                                            <option key={r.id} value={r.id}>{r.name}</option>
                                        ))}
                                    </select>
                                )}
                                <button className="btn btn-primary" disabled={selected.size === 0 || applying} onClick={apply}>
                                    {applying ? <><Spinner /> Applying…</> : 'Apply to Selected Jobs'}
                                </button>
                                <span className="muted" style={{ fontSize: 13 }}>{selected.size} selected</span>
                                <span className="muted" style={{ fontSize: 12 }}>
                                    (Email jobs will be sent automatically. Portal jobs will be added to your list with apply links.)
                                </span>
                            </div>
                        </>
                    )}
                </div>
            )}
        </>
    );
}
