import React, { useState, useEffect, useRef } from 'react';
import { Link, useForm, router } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons, EmptyState, IconField, ChipIcon, Spinner, useConfirm, CompanyInsightButton, avatarAccent } from '../components';

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
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

function Filters({ filters }) {
    const [f, setF] = useState({
        search: filters.search || '', sort: filters.sort || 'created_at',
    });

    const apply = (e) => {
        e.preventDefault();
        router.get('/jobs', f, { preserveState: true, preserveScroll: true });
    };
    const clear = () => router.get('/jobs');
    const hasActive = filters.search;

    return (
        <div className="card card-pad-sm">
            <form onSubmit={apply} style={{ display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' }}>
                <input type="text" placeholder="Search company, title, recruiter..." style={{ flex: 1, minWidth: 200 }}
                    value={f.search} onChange={(e) => setF({ ...f, search: e.target.value })} />
                <select value={f.sort} onChange={(e) => setF({ ...f, sort: e.target.value })} style={{ width: 'auto' }}>
                    <option value="created_at">Newest first</option>
                    <option value="company">Company</option>
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
                {/* Rendered email HTML is arbitrary — scroll it inside the modal rather
                    than letting a wide table push the whole page sideways. */}
                <div style={{ borderTop: '1px solid var(--border)', paddingTop: 12, fontSize: 14, lineHeight: 1.7, overflowX: 'auto' }}
                    dangerouslySetInnerHTML={{ __html: state.body }} />
            </div>
        </div>
    );
}

function BatchProgress({ batch, onCancel, cancelling }) {
    const { total, processed, failed, pending } = batch;
    const pct = total > 0 ? Math.round((processed / total) * 100) : 0;

    return (
        <div className="alert alert-warn" style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', gap: 12, flexWrap: 'wrap' }}>
                <span>
                    Sending in the background: <strong>{processed}/{total}</strong> processed
                    {failed > 0 && <> · <span style={{ color: 'var(--red)' }}>{failed} failed</span></>}
                    {pending > 0 && <> · {pending} remaining</>}
                </span>
                <button type="button" className="btn-link" style={{ color: 'var(--red)' }} onClick={onCancel} disabled={cancelling}>
                    {cancelling ? 'Cancelling…' : 'Cancel remaining'}
                </button>
            </div>
            <div className="batch-progress-track">
                <div className="batch-progress-fill" style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

function JobCard({ job, busy, onSend, onPreview, onDelete }) {
    return (
        <div className={`queue-row${job.status === 'failed' ? ' is-failed' : ''}`}>
            <div className="co-cell">
                <span className={`co-avatar accent-${avatarAccent(job.company)}`}>{(job.company || '?')[0].toUpperCase()}</span>
                <div className="co-info" style={{ minWidth: 0 }}>
                    <strong style={{ display: 'block' }}>{job.company}</strong>
                    <span className="muted">{job.job_title || '—'}</span>
                    <CompanyInsightButton company={job.company} role={job.job_title} />
                </div>
            </div>

            <div className="queue-row-meta">
                {job.source ? (
                    <Badge status="queued">{job.source}</Badge>
                ) : (
                    job.recruiter_name || job.recruiter_email || '—'
                )}
            </div>

            <div className="queue-row-meta">
                <Badge status={job.status} />
                {job.status === 'failed' && job.error && (
                    <span className="muted" style={{ fontSize: 11, marginLeft: 6 }} title={job.error}>{job.error_short}</span>
                )}
            </div>

            <div className="queue-row-actions">
                {/* Once a send is dispatched the job sits in "queued" until a background
                    worker actually delivers it — the button must stay disabled through
                    that whole window (not just the click's own request), or a second
                    click before the worker runs would dispatch a duplicate send. */}
                {job.status === 'queued' && (
                    <button className="btn btn-ghost btn-sm" disabled>
                        <Spinner dark size={12} /> Queued
                    </button>
                )}
                {job.status !== 'queued' && job.apply_type !== 'easy_apply' && (
                    <button className="btn btn-ghost btn-sm" disabled={busy} onClick={() => onSend(job.id)}>
                        {busy ? <Spinner dark size={12} /> : 'Send'}
                    </button>
                )}
                <button className="btn btn-ghost btn-sm" onClick={() => onPreview(job.id)} title="Preview email">Preview</button>
                <button className="btn btn-danger btn-sm" disabled={busy} onClick={() => onDelete(job.id)}>
                    {busy ? <Spinner dark size={12} /> : '✕'}
                </button>
            </div>
        </div>
    );
}

function QueueList({ jobs, busyIds, onSend, onPreview, onDelete }) {
    return (
        <div className="card">
            <div className="toolbar" style={{ marginBottom: 14 }}>
                <h2 style={{ margin: 0, fontSize: 15 }}>Queue</h2>
                <Badge status="neutral">{jobs.length}</Badge>
            </div>
            <div className="queue-list">
                {jobs.map((job) => (
                    <JobCard key={job.id} job={job} busy={busyIds.has(job.id)}
                        onSend={onSend} onPreview={onPreview} onDelete={onDelete} />
                ))}
            </div>
        </div>
    );
}

export default function Jobs({ jobs, hasDocuments, templates, counts, filters, activeBatch }) {
    const [previewId, setPreviewId] = useState(null);
    const [templateId, setTemplateId] = useState('');
    const [sendingAll, setSendingAll] = useState(false);
    const [clearingAll, setClearingAll] = useState(false);
    const [cancelling, setCancelling] = useState(false);
    const [busyIds, setBusyIds] = useState(() => new Set());
    const { confirm, dialog } = useConfirm();

    // A single "Send" doesn't create a batch (that's only for bulk sends), so
    // without this an individually-queued job just sits showing "Queued" on
    // screen forever — the background worker finishes it in a few seconds,
    // but nothing tells the page to go look. Poll while anything is queued,
    // same as the batch-progress polling below.
    const hasQueued = jobs.some((j) => j.status === 'queued');
    useEffect(() => {
        if (!activeBatch && !hasQueued) return;
        const interval = setInterval(() => {
            router.reload({ only: ['jobs', 'counts', 'activeBatch'], preserveScroll: true, preserveState: true });
        }, 3000);
        return () => clearInterval(interval);
    }, [activeBatch, hasQueued]);

    const setBusy = (id, busy) => setBusyIds((s) => {
        const next = new Set(s);
        busy ? next.add(id) : next.delete(id);
        return next;
    });

    const sendAll = async () => {
        const ok = await confirm({
            title: 'Send applications?',
            message: `Queue and email ${counts.pending} application(s) now?`,
            confirmLabel: 'Send now',
        });
        if (!ok) return;
        setSendingAll(true);
        router.post('/jobs/send', { email_template_id: templateId || null }, {
            onFinish: () => setSendingAll(false),
        });
    };
    const sendOne = (id) => {
        setBusy(id, true);
        router.post(`/jobs/${id}/send`, {}, { onFinish: () => setBusy(id, false) });
    };
    const clearAll = async () => {
        const ok = await confirm({
            title: 'Delete all jobs?',
            message: 'This will permanently delete every job in your list. This cannot be undone.',
            confirmLabel: 'Delete all',
            danger: true,
        });
        if (!ok) return;
        setClearingAll(true);
        router.post('/jobs/clear', {}, { onFinish: () => setClearingAll(false) });
    };
    const cancelSend = async () => {
        const ok = await confirm({
            title: 'Cancel remaining sends?',
            message: 'Any applications still queued will not be sent.',
            confirmLabel: 'Cancel sends',
            danger: true,
        });
        if (!ok) return;
        setCancelling(true);
        router.post('/jobs/send-cancel', {}, { preserveScroll: true, onFinish: () => setCancelling(false) });
    };
    const destroy = async (id) => {
        const ok = await confirm({
            title: 'Delete this job?',
            message: 'This removes the job and its tracking history. This cannot be undone.',
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;
        setBusy(id, true);
        router.delete(`/jobs/${id}`, { onFinish: () => setBusy(id, false) });
    };
    return (
        <>
            <PageHead title="Applications"
                subtitle={<>Send and manage your applications here. <Link href="/search">Find Jobs</Link> to auto-search and apply, import a CSV / add manually below, or track outcomes on the <Link href="/pipeline">Pipeline</Link> board.</>} />

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

            {activeBatch && <BatchProgress batch={activeBatch} onCancel={cancelSend} cancelling={cancelling} />}

            <ImportAndAdd />
            <Filters filters={filters} />

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
                    <button className="btn btn-primary" disabled={counts.pending === 0 || sendingAll || !!activeBatch} onClick={sendAll}>
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
            </div>

            {jobs.length === 0 ? (
                <div className="card">
                    <EmptyState icon="briefcase" title={counts.total === 0 ? 'No applications yet' : 'Nothing left to send'}>
                        {counts.total === 0 ? (
                            <><Link href="/search">Find Jobs</Link> to search and auto-apply, or import a CSV above.</>
                        ) : (
                            <>Every application has been sent. Track replies and outcomes on the <Link href="/pipeline">Pipeline</Link> board.</>
                        )}
                    </EmptyState>
                </div>
            ) : (
                <QueueList jobs={jobs} busyIds={busyIds} onSend={sendOne} onPreview={setPreviewId} onDelete={destroy} />
            )}

            {previewId && <PreviewModal jobId={previewId} onClose={() => setPreviewId(null)} />}
            {dialog}
        </>
    );
}
