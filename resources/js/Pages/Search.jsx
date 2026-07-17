import React, { useState } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { PageHead, Badge } from '../components';

function SearchForm({ profile }) {
    const { data, setData, post, processing } = useForm({
        role: profile.preferred_role || '',
        location: profile.location || '',
        site: '',
        sort_by: 'relevance',
        full_time: false,
    });

    const hasSite = data.site.trim() !== '';

    const submit = (e) => {
        e.preventDefault();
        post('/search', { preserveScroll: true });
    };

    return (
        <div className="card">
            <h2>Search Criteria</h2>
            <p className="hint">
                Search a role across the web, or paste a specific careers-site URL (or company name)
                to pull jobs from that site.
            </p>
            <form onSubmit={submit}>
                <div className="row">
                    <div style={{ flex: 2 }}>
                        <label>Job Role / Title {hasSite ? '' : '*'}</label>
                        <input type="text" required={!hasSite} value={data.role} onChange={(e) => setData('role', e.target.value)}
                            placeholder="e.g. Software Engineer, Data Analyst, Product Manager" />
                    </div>
                    <div>
                        <label>Location</label>
                        <input type="text" value={data.location} onChange={(e) => setData('location', e.target.value)}
                            placeholder="e.g. Kerala, New York, Remote" />
                    </div>
                </div>

                <div>
                    <label>Job site, platform or company <span className="muted" style={{ fontWeight: 400 }}>(optional)</span></label>
                    <input type="text" value={data.site} onChange={(e) => setData('site', e.target.value)}
                        placeholder="e.g. Technopark, Infopark, Cyberpark, Indeed, Naukri, or a careers-page URL" />
                    <p className="hint" style={{ margin: '6px 0 0' }}>
                        Type a Kerala tech park (Technopark, Infopark, Cyberpark) to pull its live listings, a platform
                        (Indeed, Naukri, LinkedIn…) or company for aggregated results, or paste a careers-page URL.
                        Leave blank to search across the web.
                    </p>
                </div>

                {!hasSite && (
                    <div className="row" style={{ marginTop: 8, alignItems: 'flex-end' }}>
                        <div>
                            <label>Sort by</label>
                            <select value={data.sort_by} onChange={(e) => setData('sort_by', e.target.value)}>
                                <option value="relevance">Relevance</option>
                                <option value="date">Most recent</option>
                                <option value="salary">Highest salary</option>
                            </select>
                        </div>
                        <div>
                            <label className="inline" style={{ height: 42 }}>
                                <input type="checkbox" checked={data.full_time} onChange={(e) => setData('full_time', e.target.checked)} />
                                Full-time only
                            </label>
                        </div>
                    </div>
                )}

                <button type="submit" className="btn btn-primary" style={{ marginTop: 16 }} disabled={processing}>
                    {processing ? 'Searching…' : 'Search Jobs'}
                </button>
            </form>
        </div>
    );
}

export default function Search({ profile, jobSites, results, searched, searchError, hasDocuments }) {
    const [selected, setSelected] = useState(() => new Set());

    const toggle = (i) => {
        const next = new Set(selected);
        next.has(i) ? next.delete(i) : next.add(i);
        setSelected(next);
    };
    const toggleAll = () => {
        setSelected(selected.size === results.length ? new Set() : new Set(results.map((_, i) => i)));
    };

    const apply = () => {
        const jobs = [...selected].map((i) => {
            const j = results[i];
            return {
                company: j.company, job_title: j.job_title, location: j.location,
                recruiter_email: j.recruiter_email, job_url: j.job_url,
                apply_url: j.apply_url, source: j.source,
            };
        });
        router.post('/search/apply', { jobs });
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

            <SearchForm profile={profile} />

            {searchError && <div className="alert alert-error">{searchError}</div>}

            {searched && !searchError && (
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
                        <div className="empty">No jobs found matching your criteria. Try broadening your search.</div>
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
                                            <th>Job</th><th>Company</th><th>Location</th><th>Source</th><th>Apply Via</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {results.map((job, i) => (
                                            <tr key={i}>
                                                <td><input type="checkbox" checked={selected.has(i)} onChange={() => toggle(i)} /></td>
                                                <td>
                                                    <strong>{job.job_title}</strong>
                                                    {job.description && <><br /><span className="muted" style={{ fontSize: 12 }}>{job.description}</span></>}
                                                </td>
                                                <td>
                                                    {job.employer_logo && (
                                                        <img src={job.employer_logo} alt="" style={{ width: 20, height: 20, borderRadius: 4, verticalAlign: 'middle', marginRight: 4 }} />
                                                    )}
                                                    {job.company}
                                                </td>
                                                <td>{job.location || '—'}</td>
                                                <td><Badge status="queued">{job.source}</Badge></td>
                                                <td>
                                                    {job.apply_type === 'email'
                                                        ? <Badge status="sent"><span title={job.recruiter_email}>Email</span></Badge>
                                                        : <a href={job.apply_url} target="_blank" rel="noopener" className="badge pending" style={{ textDecoration: 'none' }}>Portal</a>}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            <div style={{ marginTop: 16, display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
                                <button className="btn btn-primary" disabled={selected.size === 0} onClick={apply}>
                                    Apply to Selected Jobs
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
