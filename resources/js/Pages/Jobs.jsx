import React, { useState, useEffect, useRef } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons, EmptyState, IconField, ChipIcon, Spinner } from '../components';

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
}

const PIPELINE_ICONS = {
    applied: Icons.send, replied: Icons.chat, interview: Icons.calendar,
    offer: Icons.trophy, rejected: Icons.xCircle,
};

function PipelineDropdown({ value, labels, onChange }) {
    const [open, setOpen] = useState(false);
    const ref = useRef(null);
    const current = value || 'applied';

    useEffect(() => {
        const onDoc = (e) => { if (ref.current && !ref.current.contains(e.target)) setOpen(false); };
        document.addEventListener('mousedown', onDoc);
        return () => document.removeEventListener('mousedown', onDoc);
    }, []);

    const pick = (key) => { onChange(key); setOpen(false); };

    return (
        <div className="pipe-dd" ref={ref}>
            <button type="button" className={`pipe-trigger pipe-${current}`} onClick={() => setOpen((o) => !o)}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    {PIPELINE_ICONS[current] || Icons.send}
                </svg>
                {labels[current] || current}
                <svg className="pipe-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    {Icons.chevronDown}
                </svg>
            </button>
            {open && (
                <div className="pipe-menu">
                    {Object.entries(labels).map(([key, label]) => (
                        <button type="button" key={key} className={`pipe-opt${key === current ? ' active' : ''}`} onClick={() => pick(key)}>
                            <span className={`pipe-opt-ico pipe-${key}`}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                                    {PIPELINE_ICONS[key] || Icons.send}
                                </svg>
                            </span>
                            {label}
                            {key === current && (
                                <svg className="pipe-opt-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                                    {Icons.check}
                                </svg>
                            )}
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}

function ImportAndAdd() {
    const importForm = useForm({ csv: null });
    const addForm = useForm({ company: '', job_title: '', recruiter_name: '', recruiter_email: '' });
    const [dragOver, setDragOver] = useState(false);
    const fileRef = useRef(null);

    const doImport = (e) => {
        e.preventDefault();
        importForm.post('/jobs/import', { forceFormData: true, onSuccess: () => importForm.reset() });
    };
    const doAdd = (e) => {
        e.preventDefault();
        addForm.post('/jobs', { onSuccess: () => addForm.reset() });
    };
    const pickFile = (file) => { if (file) importForm.setData('csv', file); };

    return (
        <details className="card add-jobs-card">
            <summary>
                <span className="add-jobs-summary-ico"><ChipIcon icon={Icons.plus} /></span>
                <div>
                    <div className="add-jobs-summary-title">Import CSV or Add Manually</div>
                    <div className="hint" style={{ margin: 0 }}>Bring in existing leads or add a single job by hand.</div>
                </div>
                <svg className="add-jobs-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.3" strokeLinecap="round" strokeLinejoin="round">
                    {Icons.chevronDown}
                </svg>
            </summary>

            <div className="add-jobs-body">
                <div className="add-jobs-panel">
                    <h2 style={{ fontSize: 14.5 }}>Import from CSV</h2>
                    <form onSubmit={doImport}>
                        <div
                            className={`dropzone${dragOver ? ' drag' : ''}${importForm.data.csv ? ' filled' : ''}`}
                            onClick={() => fileRef.current?.click()}
                            onDragOver={(e) => { e.preventDefault(); setDragOver(true); }}
                            onDragLeave={() => setDragOver(false)}
                            onDrop={(e) => { e.preventDefault(); setDragOver(false); pickFile(e.dataTransfer.files[0]); }}
                        >
                            <ChipIcon icon={Icons.upload} />
                            {importForm.data.csv ? (
                                <span className="dropzone-file">{importForm.data.csv.name}</span>
                            ) : (
                                <>
                                    <span><strong>Click to choose</strong> or drag a CSV file here</span>
                                    <span className="muted" style={{ fontSize: 11.5 }}>.csv or .txt</span>
                                </>
                            )}
                            <input ref={fileRef} type="file" accept=".csv,.txt" required hidden
                                onChange={(e) => pickFile(e.target.files[0])} />
                        </div>
                        <div style={{ marginTop: 14, display: 'flex', gap: 10, alignItems: 'center' }}>
                            <button type="submit" className="btn btn-primary btn-sm" disabled={importForm.processing || !importForm.data.csv}>
                                {importForm.processing ? 'Importing…' : 'Import'}
                            </button>
                            <a className="btn-link" href="/jobs/template">Download template</a>
                        </div>
                    </form>
                </div>

                <div className="add-jobs-divider" />

                <div className="add-jobs-panel">
                    <h2 style={{ fontSize: 14.5 }}>Add one manually</h2>
                    <form onSubmit={doAdd}>
                        <div className="row">
                            <IconField icon={Icons.building} type="text" placeholder="Company *" required
                                value={addForm.data.company} onChange={(e) => addForm.setData('company', e.target.value)} />
                            <IconField icon={Icons.briefcase} type="text" placeholder="Job title"
                                value={addForm.data.job_title} onChange={(e) => addForm.setData('job_title', e.target.value)} />
                        </div>
                        <div className="row" style={{ marginTop: 12 }}>
                            <IconField icon={Icons.user} type="text" placeholder="Recruiter name"
                                value={addForm.data.recruiter_name} onChange={(e) => addForm.setData('recruiter_name', e.target.value)} />
                            <IconField icon={Icons.mail} type="email" placeholder="Recruiter email *" required
                                value={addForm.data.recruiter_email} onChange={(e) => addForm.setData('recruiter_email', e.target.value)} />
                        </div>
                        <button type="submit" className="btn btn-primary btn-sm" style={{ marginTop: 14 }} disabled={addForm.processing}>
                            <ChipIcon icon={Icons.plus} /> Add job
                        </button>
                    </form>
                </div>
            </div>
        </details>
    );
}

function Filters({ filters, pipelineLabels }) {
    const [f, setF] = useState({
        search: filters.search || '', status: filters.status || '',
        pipeline: filters.pipeline || '', sort: filters.sort || 'created_at',
    });

    const apply = (e) => {
        e.preventDefault();
        router.get('/jobs', f, { preserveState: true, preserveScroll: true });
    };
    const clear = () => router.get('/jobs');
    const hasActive = filters.search || filters.status || filters.pipeline;

    return (
        <div className="card card-pad-sm">
            <form onSubmit={apply} style={{ display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' }}>
                <input type="text" placeholder="Search company, title, recruiter..." style={{ flex: 1, minWidth: 200 }}
                    value={f.search} onChange={(e) => setF({ ...f, search: e.target.value })} />
                <select value={f.status} onChange={(e) => setF({ ...f, status: e.target.value })} style={{ width: 'auto' }}>
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="queued">Queued</option>
                    <option value="sent">Sent</option>
                    <option value="failed">Failed</option>
                </select>
                <select value={f.pipeline} onChange={(e) => setF({ ...f, pipeline: e.target.value })} style={{ width: 'auto' }}>
                    <option value="">All stages</option>
                    {Object.entries(pipelineLabels).map(([k, label]) => <option key={k} value={k}>{label}</option>)}
                </select>
                <select value={f.sort} onChange={(e) => setF({ ...f, sort: e.target.value })} style={{ width: 'auto' }}>
                    <option value="created_at">Newest first</option>
                    <option value="company">Company</option>
                    <option value="status">Status</option>
                    <option value="sent_at">Sent date</option>
                </select>
                <button type="submit" className="btn btn-ghost">Filter</button>
                {hasActive && <button type="button" className="btn-link" style={{ color: 'var(--red)' }} onClick={clear}>Clear</button>}
            </form>
        </div>
    );
}

function PreviewModal({ jobId, onClose }) {
    const [state, setState] = useState({ loading: true, to: '', subject: '', body: '' });

    React.useEffect(() => {
        fetch('/jobs/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'),
                Accept: 'application/json',
            },
            body: JSON.stringify({ job_id: jobId }),
        })
            .then((r) => r.json())
            .then((d) => setState({ loading: false, to: d.to, subject: d.subject, body: d.body }))
            .catch(() => setState({ loading: false, to: 'Error loading preview', subject: '', body: '' }));
    }, [jobId]);

    return (
        <div className="modal-overlay" onClick={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className="modal">
                <button className="modal-close" onClick={onClose}>✕</button>
                <h2>Email Preview</h2>
                <p className="hint">This is how the email will look with placeholders filled in.</p>
                <div style={{ marginBottom: 8 }}><span className="muted" style={{ fontSize: 12 }}>TO: </span><strong>{state.loading ? 'Loading…' : state.to}</strong></div>
                <div style={{ marginBottom: 12 }}><span className="muted" style={{ fontSize: 12 }}>SUBJECT: </span><strong>{state.subject}</strong></div>
                <div style={{ borderTop: '1px solid var(--border)', paddingTop: 12, fontSize: 14, lineHeight: 1.7 }}
                    dangerouslySetInnerHTML={{ __html: state.body }} />
            </div>
        </div>
    );
}

export default function Jobs({ jobs, hasDocuments, templates, pipelineLabels, counts, filters }) {
    const [previewId, setPreviewId] = useState(null);
    const [templateId, setTemplateId] = useState('');
    const [sendingAll, setSendingAll] = useState(false);
    const [clearingAll, setClearingAll] = useState(false);
    const [busyIds, setBusyIds] = useState(() => new Set());

    const setBusy = (id, busy) => setBusyIds((s) => {
        const next = new Set(s);
        busy ? next.add(id) : next.delete(id);
        return next;
    });

    const sendAll = () => {
        if (!confirm(`Queue and email ${counts.pending} application(s) now?`)) return;
        setSendingAll(true);
        router.post('/jobs/send', { email_template_id: templateId || null }, {
            onFinish: () => setSendingAll(false),
        });
    };
    const sendOne = (id) => {
        setBusy(id, true);
        router.post(`/jobs/${id}/send`, {}, { onFinish: () => setBusy(id, false) });
    };
    const clearAll = () => {
        if (!confirm('Delete ALL jobs? This cannot be undone.')) return;
        setClearingAll(true);
        router.post('/jobs/clear', {}, { onFinish: () => setClearingAll(false) });
    };
    const destroy = (id) => {
        if (!confirm('Delete this job?')) return;
        setBusy(id, true);
        router.delete(`/jobs/${id}`, { onFinish: () => setBusy(id, false) });
    };
    const updatePipeline = (id, value) =>
        router.patch(`/jobs/${id}/pipeline`, { pipeline_status: value }, { preserveScroll: true });

    return (
        <>
            <PageHead title="Applications"
                subtitle={<>Track all your job applications. <Link href="/search">Find Jobs</Link> to auto-search and apply, or import a CSV / add manually below.</>} />

            {!hasDocuments && (
                <div className="alert alert-warn">
                    Upload your resume and cover letter on <Link href="/profile">Settings</Link> before applying.
                </div>
            )}

            <div className="stats">
                <Stat label="Total" value={counts.total} accent="primary" icon={Icons.list} />
                <Stat label="To send" value={counts.pending} accent="amber" icon={Icons.clock} />
                <Stat label="Sent" value={counts.sent} accent="green" icon={Icons.send} />
                <Stat label="Failed" value={counts.failed} accent="red" icon={Icons.alert} />
            </div>

            <ImportAndAdd />
            <Filters filters={filters} pipelineLabels={pipelineLabels} />

            <div className="card">
                <div className="toolbar">
                    {templates.length > 0 && (
                        <select value={templateId} onChange={(e) => setTemplateId(e.target.value)} style={{ width: 'auto' }}>
                            <option value="">Use profile template</option>
                            {templates.map((t) => (
                                <option key={t.id} value={t.id}>{t.name}{t.is_default ? ' (default)' : ''}</option>
                            ))}
                        </select>
                    )}
                    <button className="btn btn-primary" disabled={counts.pending === 0 || sendingAll} onClick={sendAll}>
                        {sendingAll ? <><Spinner /> Sending…</> : `Send ${counts.pending} pending`}
                    </button>
                    <div className="spacer" />
                    <a href="/jobs/export" className="btn btn-ghost btn-sm">Export CSV</a>
                    {counts.total > 0 && (
                        <button className="btn-link" style={{ color: 'var(--red)' }} onClick={clearAll} disabled={clearingAll}>
                            {clearingAll ? <><Spinner dark size={12} /> Clearing…</> : 'Clear all'}
                        </button>
                    )}
                </div>

                {jobs.length === 0 ? (
                    <EmptyState icon="briefcase" title="No applications yet">
                        <Link href="/search">Find Jobs</Link> to search and auto-apply, or import a CSV above.
                    </EmptyState>
                ) : (
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Company / Role</th><th>Source</th><th>Status</th>
                                    <th>Pipeline</th><th>Tracking</th><th>Sent</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                {jobs.map((job) => (
                                    <tr key={job.id}>
                                        <td>
                                            <div className="co-cell">
                                                <span className="co-avatar">{(job.company || '?')[0].toUpperCase()}</span>
                                                <div style={{ minWidth: 0 }}>
                                                    <strong>{job.company}</strong><br />
                                                    <span className="muted">{job.job_title || '—'}</span>
                                                    {job.job_url && <> · <a href={job.job_url} target="_blank" rel="noopener">link</a></>}
                                                    {job.apply_type === 'link' && job.apply_url &&
                                                        <> · <a href={job.apply_url} target="_blank" rel="noopener" style={{ color: 'var(--amber)' }}>Apply on portal</a></>}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            {job.source ? <Badge status="queued">{job.source}</Badge> : (
                                                <>
                                                    <span className="muted">{job.recruiter_name || '—'}</span><br />
                                                    <span className="muted" style={{ fontSize: 11 }}>{job.recruiter_email}</span>
                                                </>
                                            )}
                                        </td>
                                        <td>
                                            <Badge status={job.status} />
                                            {job.status === 'failed' && job.error &&
                                                <><br /><span className="muted" style={{ fontSize: 11 }} title={job.error}>{job.error_short}</span></>}
                                        </td>
                                        <td>
                                            <PipelineDropdown value={job.pipeline_status} labels={pipelineLabels}
                                                onChange={(v) => updatePipeline(job.id, v)} />
                                        </td>
                                        <td className="muted" style={{ fontSize: 12 }}>
                                            {job.opened_at && <span style={{ color: 'var(--green)' }} title={`Opened ${job.opened_at}`}>Opened<br /></span>}
                                            {job.clicked_at && <span style={{ color: 'var(--blue)' }} title={`Clicked ${job.clicked_at}`}>Clicked<br /></span>}
                                            {job.followup_count > 0 && <span>{job.followup_count}x follow-up</span>}
                                            {!job.opened_at && !job.clicked_at && job.followup_count === 0 && '—'}
                                        </td>
                                        <td className="muted">{job.sent_at || '—'}</td>
                                        <td style={{ whiteSpace: 'nowrap', textAlign: 'right' }}>
                                            {job.status !== 'sent' &&
                                                <button className="btn btn-ghost btn-sm" disabled={busyIds.has(job.id)} onClick={() => sendOne(job.id)}>
                                                    {busyIds.has(job.id) ? <Spinner dark size={12} /> : 'Send'}
                                                </button>}
                                            {' '}
                                            <button className="btn btn-ghost btn-sm" onClick={() => setPreviewId(job.id)} title="Preview email">Preview</button>
                                            {' '}
                                            <button className="btn btn-danger btn-sm" disabled={busyIds.has(job.id)} onClick={() => destroy(job.id)}>
                                                {busyIds.has(job.id) ? <Spinner dark size={12} /> : '✕'}
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>

            {previewId && <PreviewModal jobId={previewId} onClose={() => setPreviewId(null)} />}
        </>
    );
}
