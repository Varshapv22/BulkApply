import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Link, useForm, router } from '@inertiajs/react';
import {
    DndContext, DragOverlay, PointerSensor, KeyboardSensor,
    useSensor, useSensors, useDraggable, useDroppable, rectIntersection,
} from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { PageHead, Stat, Badge, Icons, EmptyState, IconField, ChipIcon, Spinner, useConfirm, CompanyInsightButton } from '../components';

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
}

const PIPELINE_ICONS = {
    applied: Icons.send, replied: Icons.chat, interview: Icons.calendar,
    offer: Icons.trophy, rejected: Icons.xCircle,
};

/**
 * Lives inside a board column with its own scroll (`.board-col-body`,
 * overflow-y: auto) — an absolutely positioned menu inside a scrolling
 * ancestor gets clipped to it instead of floating above the board.
 * Rendering the menu into a portal at document.body, positioned from the
 * trigger's live bounding rect, sidesteps that clipping entirely and keeps
 * the menu on-screen (clamped to the viewport) at any scroll position or
 * window size. `size="sm"` renders the compact in-card variant.
 */
function PipelineDropdown({ value, labels, onChange, size }) {
    const [open, setOpen] = useState(false);
    const [pos, setPos] = useState(null);
    const triggerRef = useRef(null);
    const menuRef = useRef(null);
    const current = value || 'applied';

    const place = () => {
        const trigger = triggerRef.current;
        if (!trigger) return;
        const r = trigger.getBoundingClientRect();
        const menuWidth = 190;
        const margin = 12;
        const left = Math.min(r.left, window.innerWidth - menuWidth - margin);
        setPos({ top: r.bottom + 6, left: Math.max(margin, left) });
    };

    useEffect(() => {
        if (!open) return;
        place();

        const onDoc = (e) => {
            if (triggerRef.current?.contains(e.target)) return;
            if (menuRef.current?.contains(e.target)) return;
            setOpen(false);
        };
        // Any scroll (including the table's own horizontal scroll) or resize
        // can move the trigger out from under a stale menu position — close
        // it rather than let it drift.
        const close = () => setOpen(false);

        document.addEventListener('mousedown', onDoc);
        window.addEventListener('scroll', close, true);
        window.addEventListener('resize', close);
        return () => {
            document.removeEventListener('mousedown', onDoc);
            window.removeEventListener('scroll', close, true);
            window.removeEventListener('resize', close);
        };
    }, [open]);

    const pick = (key) => { onChange(key); setOpen(false); };

    return (
        <div className={`pipe-dd${size === 'sm' ? ' pipe-dd-sm' : ''}`}>
            <button type="button" ref={triggerRef} className={`pipe-trigger pipe-${current}`} onClick={() => setOpen((o) => !o)}>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round">
                    {PIPELINE_ICONS[current] || Icons.send}
                </svg>
                {labels[current] || current}
                <svg className="pipe-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                    {Icons.chevronDown}
                </svg>
            </button>
            {open && pos && createPortal(
                <div className="pipe-menu" ref={menuRef} style={{ position: 'fixed', top: pos.top, left: pos.left }}>
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
                </div>,
                document.body
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

function Filters({ filters }) {
    // Status/stage no longer need their own selects — the board's columns
    // already are those two dimensions. Filtering by one server-side would
    // empty most columns, which defeats a board's point of showing the
    // whole funnel at once. Search still narrows cards within each column;
    // sort still controls each column's internal ordering.
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

function BoardEmpty({ isQueue }) {
    return (
        <div className="board-empty-col">
            {isQueue ? "Nothing to send — add jobs above or check Find Jobs." : 'No applications here yet.'}
        </div>
    );
}

function JobCard({ job, variant, pipelineLabels, busy, onSend, onPreview, onDelete, onPipelineChange }) {
    const draggable = useDraggable({ id: job.id, disabled: variant !== 'pipeline' });
    const { attributes, listeners, setNodeRef, transform, isDragging } = draggable;

    const style = transform ? { transform: CSS.Translate.toString(transform) } : undefined;
    const isFailed = variant === 'queue' && job.status === 'failed';

    return (
        <div ref={setNodeRef} style={style} {...attributes} {...(variant === 'pipeline' ? listeners : {})}
            className={`board-card${variant === 'pipeline' ? ' is-draggable' : ''}${isDragging ? ' is-dragging' : ''}${isFailed ? ' is-failed' : ''}${variant === 'overlay' ? ' drag-overlay' : ''}`}>
            <div className="co-cell">
                <span className="co-avatar">{(job.company || '?')[0].toUpperCase()}</span>
                <div className="co-info" style={{ minWidth: 0 }}>
                    <strong style={{ display: 'block' }}>{job.company}</strong>
                    <span className="muted">{job.job_title || '—'}</span>
                    <CompanyInsightButton company={job.company} role={job.job_title} />
                </div>
            </div>

            {job.source ? (
                <Badge status="queued">{job.source}</Badge>
            ) : (
                <div className="muted" style={{ fontSize: 11.5 }}>
                    {job.recruiter_name || job.recruiter_email || '—'}
                </div>
            )}

            {variant === 'queue' && (
                <div>
                    <Badge status={job.status} />
                    {job.status === 'failed' && job.error && (
                        <span className="muted" style={{ fontSize: 11, marginLeft: 6 }} title={job.error}>{job.error_short}</span>
                    )}
                </div>
            )}

            {variant === 'pipeline' && (
                <div className="muted" style={{ fontSize: 11, display: 'flex', flexDirection: 'column', gap: 2 }}>
                    {job.opened_at && <span style={{ color: 'var(--green)' }}>Opened</span>}
                    {job.clicked_at && <span style={{ color: 'var(--blue)' }}>Clicked</span>}
                    {job.followup_count > 0 && <span>{job.followup_count}x follow-up</span>}
                    {job.sent_at && <span>Sent {job.sent_at}</span>}
                </div>
            )}

            {variant === 'pipeline' && (
                <PipelineDropdown value={job.pipeline_status} labels={pipelineLabels} size="sm"
                    onChange={(v) => onPipelineChange(job.id, v)} />
            )}

            <div className="board-card-actions">
                {/* Once a send is dispatched the job sits in "queued" until a background
                    worker actually delivers it — the button must stay disabled through
                    that whole window (not just the click's own request), or a second
                    click before the worker runs would dispatch a duplicate send. */}
                {variant === 'queue' && job.status === 'queued' && (
                    <button className="btn btn-ghost btn-sm" disabled>
                        <Spinner dark size={12} /> Queued
                    </button>
                )}
                {variant === 'queue' && job.status !== 'sent' && job.status !== 'queued' && job.apply_type !== 'easy_apply' && (
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

function Column({ id, label, colorKey, jobsInColumn, isQueue, pipelineLabels, busyIds, onSend, onPreview, onDelete, onPipelineChange }) {
    const droppable = useDroppable({ id, disabled: isQueue });
    const { setNodeRef, isOver } = droppable;

    return (
        <div ref={isQueue ? undefined : setNodeRef} className={`board-col${isOver ? ' is-drag-over' : ''}`}>
            <div className="board-col-head">
                <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span className={`board-col-dot${colorKey ? ` pipe-${colorKey}` : ''}`} />
                    {label}
                </span>
                <Badge status="neutral">{jobsInColumn.length}</Badge>
            </div>
            <div className="board-col-body">
                {jobsInColumn.length === 0 ? (
                    <BoardEmpty isQueue={isQueue} />
                ) : (
                    jobsInColumn.map((job) => (
                        <JobCard key={job.id} job={job} variant={isQueue ? 'queue' : 'pipeline'}
                            pipelineLabels={pipelineLabels} busy={busyIds.has(job.id)}
                            onSend={onSend} onPreview={onPreview} onDelete={onDelete} onPipelineChange={onPipelineChange} />
                    ))
                )}
            </div>
        </div>
    );
}

function Board({ jobs, pipelineLabels, busyIds, onSend, onPreview, onDelete, onPipelineChange }) {
    const [activeId, setActiveId] = useState(null);
    // Optimistic column moves: applied the instant a drop lands so the card
    // doesn't wait for the round trip; cleared once the request settles
    // (the next Inertia prop set already reflects the real value by then).
    const [pendingMoves, setPendingMoves] = useState({});

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
        useSensor(KeyboardSensor),
    );

    const displayJobs = jobs.map((j) => (pendingMoves[j.id] ? { ...j, pipeline_status: pendingMoves[j.id] } : j));
    const activeJob = activeId ? displayJobs.find((j) => j.id === activeId) : null;

    const queueJobs = displayJobs.filter((j) => j.status !== 'sent');
    const columns = Object.entries(pipelineLabels).map(([key, label]) => ({
        key, label,
        jobs: displayJobs.filter((j) => j.status === 'sent' && (j.pipeline_status || 'applied') === key),
    }));

    const handleDragEnd = ({ active, over }) => {
        setActiveId(null);
        if (!over) return; // dropped on Queue (not droppable) or nowhere — snaps back, no request
        const job = jobs.find((j) => j.id === active.id);
        if (!job || (job.pipeline_status || 'applied') === over.id) return; // same column — no-op

        setPendingMoves((m) => ({ ...m, [active.id]: over.id }));
        router.patch(`/jobs/${active.id}/pipeline`, { pipeline_status: over.id }, {
            preserveScroll: true, preserveState: true,
            onFinish: () => setPendingMoves((m) => { const n = { ...m }; delete n[active.id]; return n; }),
        });
    };

    return (
        <DndContext sensors={sensors} collisionDetection={rectIntersection}
            onDragStart={({ active }) => setActiveId(active.id)}
            onDragEnd={handleDragEnd} onDragCancel={() => setActiveId(null)}>
            <div className="board">
                <Column id="queue" label="Queue" isQueue jobsInColumn={queueJobs}
                    pipelineLabels={pipelineLabels} busyIds={busyIds}
                    onSend={onSend} onPreview={onPreview} onDelete={onDelete} onPipelineChange={onPipelineChange} />
                {columns.map((c) => (
                    <Column key={c.key} id={c.key} label={c.label} colorKey={c.key} jobsInColumn={c.jobs}
                        pipelineLabels={pipelineLabels} busyIds={busyIds}
                        onSend={onSend} onPreview={onPreview} onDelete={onDelete} onPipelineChange={onPipelineChange} />
                ))}
            </div>
            <DragOverlay>
                {activeJob && (
                    <JobCard job={activeJob} variant="overlay" pipelineLabels={pipelineLabels} busy={false}
                        onSend={() => {}} onPreview={() => {}} onDelete={() => {}} onPipelineChange={() => {}} />
                )}
            </DragOverlay>
        </DndContext>
    );
}

export default function Jobs({ jobs, hasDocuments, templates, pipelineLabels, counts, filters, activeBatch }) {
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
                    <EmptyState icon="briefcase" title="No applications yet">
                        <Link href="/search">Find Jobs</Link> to search and auto-apply, or import a CSV above.
                    </EmptyState>
                </div>
            ) : (
                <Board jobs={jobs} pipelineLabels={pipelineLabels} busyIds={busyIds}
                    onSend={sendOne} onPreview={setPreviewId} onDelete={destroy} onPipelineChange={updatePipeline} />
            )}

            {previewId && <PreviewModal jobId={previewId} onClose={() => setPreviewId(null)} />}
            {dialog}
        </>
    );
}
