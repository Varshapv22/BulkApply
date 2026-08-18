import React, { useState, useEffect, useRef } from 'react';
import { createPortal } from 'react-dom';
import { Link, router } from '@inertiajs/react';
import {
    DndContext, DragOverlay, PointerSensor, KeyboardSensor,
    useSensor, useSensors, useDraggable, useDroppable, rectIntersection,
} from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { PageHead, Badge, Icons, EmptyState, Spinner, useConfirm, CompanyInsightButton } from '../components';

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
 * window size.
 */
function PipelineDropdown({ value, labels, onChange }) {
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
        <div className="pipe-dd pipe-dd-sm">
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

function Filters({ filters }) {
    const [f, setF] = useState({
        search: filters.search || '', sort: filters.sort || 'sent_at',
    });

    const apply = (e) => {
        e.preventDefault();
        router.get('/pipeline', f, { preserveState: true, preserveScroll: true });
    };
    const clear = () => router.get('/pipeline');
    const hasActive = filters.search;

    return (
        <div className="card card-pad-sm">
            <form onSubmit={apply} style={{ display: 'flex', gap: 10, alignItems: 'center', flexWrap: 'wrap' }}>
                <input type="text" placeholder="Search company, title, recruiter..." style={{ flex: 1, minWidth: 200 }}
                    value={f.search} onChange={(e) => setF({ ...f, search: e.target.value })} />
                <select value={f.sort} onChange={(e) => setF({ ...f, sort: e.target.value })} style={{ width: 'auto' }}>
                    <option value="sent_at">Sent date</option>
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
                <p className="hint">This is how the email looked with placeholders filled in.</p>
                <div style={{ marginBottom: 8 }}><span className="muted" style={{ fontSize: 12 }}>TO: </span><strong>{state.loading ? 'Loading…' : state.to}</strong></div>
                <div style={{ marginBottom: 12 }}><span className="muted" style={{ fontSize: 12 }}>SUBJECT: </span><strong>{state.subject}</strong></div>
                <div style={{ borderTop: '1px solid var(--border)', paddingTop: 12, fontSize: 14, lineHeight: 1.7, overflowX: 'auto' }}
                    dangerouslySetInnerHTML={{ __html: state.body }} />
            </div>
        </div>
    );
}

function BoardEmpty() {
    return <div className="board-empty-col">No applications here yet.</div>;
}

function JobCard({ job, pipelineLabels, busy, onPreview, onDelete, onPipelineChange, overlay }) {
    const draggable = useDraggable({ id: job.id, disabled: overlay });
    const { attributes, listeners, setNodeRef, transform, isDragging } = draggable;

    const style = transform ? { transform: CSS.Translate.toString(transform) } : undefined;

    return (
        <div ref={overlay ? undefined : setNodeRef} style={style} {...(overlay ? {} : attributes)} {...(overlay ? {} : listeners)}
            className={`board-card is-draggable${isDragging ? ' is-dragging' : ''}${overlay ? ' drag-overlay' : ''}`}>
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

            <div className="muted" style={{ fontSize: 11, display: 'flex', flexDirection: 'column', gap: 2 }}>
                {job.opened_at && <span style={{ color: 'var(--green)' }}>Opened</span>}
                {job.clicked_at && <span style={{ color: 'var(--blue)' }}>Clicked</span>}
                {job.followup_count > 0 && <span>{job.followup_count}x follow-up</span>}
                {job.sent_at && <span>Sent {job.sent_at}</span>}
            </div>

            <PipelineDropdown value={job.pipeline_status} labels={pipelineLabels}
                onChange={(v) => onPipelineChange(job.id, v)} />

            <div className="board-card-actions">
                <button className="btn btn-ghost btn-sm" onClick={() => onPreview(job.id)} title="Preview email">Preview</button>
                <button className="btn btn-danger btn-sm" disabled={busy} onClick={() => onDelete(job.id)}>
                    {busy ? <Spinner dark size={12} /> : '✕'}
                </button>
            </div>
        </div>
    );
}

function Column({ id, label, colorKey, jobsInColumn, pipelineLabels, busyIds, onPreview, onDelete, onPipelineChange }) {
    const droppable = useDroppable({ id });
    const { setNodeRef, isOver } = droppable;

    return (
        <div ref={setNodeRef} className={`board-col${isOver ? ' is-drag-over' : ''}`}>
            <div className="board-col-head">
                <span style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                    <span className={`board-col-dot pipe-${colorKey}`} />
                    {label}
                </span>
                <Badge status="neutral">{jobsInColumn.length}</Badge>
            </div>
            <div className="board-col-body">
                {jobsInColumn.length === 0 ? (
                    <BoardEmpty />
                ) : (
                    jobsInColumn.map((job) => (
                        <JobCard key={job.id} job={job} pipelineLabels={pipelineLabels} busy={busyIds.has(job.id)}
                            onPreview={onPreview} onDelete={onDelete} onPipelineChange={onPipelineChange} />
                    ))
                )}
            </div>
        </div>
    );
}

function Board({ jobs, pipelineLabels, busyIds, onPreview, onDelete, onPipelineChange }) {
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

    const columns = Object.entries(pipelineLabels).map(([key, label]) => ({
        key, label,
        jobs: displayJobs.filter((j) => (j.pipeline_status || 'applied') === key),
    }));

    const handleDragEnd = ({ active, over }) => {
        setActiveId(null);
        if (!over) return;
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
                {columns.map((c) => (
                    <Column key={c.key} id={c.key} label={c.label} colorKey={c.key} jobsInColumn={c.jobs}
                        pipelineLabels={pipelineLabels} busyIds={busyIds}
                        onPreview={onPreview} onDelete={onDelete} onPipelineChange={onPipelineChange} />
                ))}
            </div>
            <DragOverlay>
                {activeJob && (
                    <JobCard job={activeJob} pipelineLabels={pipelineLabels} busy={false} overlay
                        onPreview={() => {}} onDelete={() => {}} onPipelineChange={() => {}} />
                )}
            </DragOverlay>
        </DndContext>
    );
}

export default function Pipeline({ jobs, pipelineLabels, filters }) {
    const [previewId, setPreviewId] = useState(null);
    const [busyIds, setBusyIds] = useState(() => new Set());
    const { confirm, dialog } = useConfirm();

    const setBusy = (id, busy) => setBusyIds((s) => {
        const next = new Set(s);
        busy ? next.add(id) : next.delete(id);
        return next;
    });

    const updatePipeline = (id, value) =>
        router.patch(`/jobs/${id}/pipeline`, { pipeline_status: value }, { preserveScroll: true });

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
            <PageHead title="Pipeline"
                subtitle={<>Drag a card between stages to track where each sent application stands. Manage sending on <Link href="/jobs">Applications</Link>.</>} />

            <Filters filters={filters} />

            {jobs.length === 0 ? (
                <div className="card">
                    <EmptyState icon="briefcase" title="Nothing sent yet">
                        Once you send applications from <Link href="/jobs">Applications</Link>, they'll show up here so you can track replies, interviews, and offers.
                    </EmptyState>
                </div>
            ) : (
                <Board jobs={jobs} pipelineLabels={pipelineLabels} busyIds={busyIds}
                    onPreview={setPreviewId} onDelete={destroy} onPipelineChange={updatePipeline} />
            )}

            {previewId && <PreviewModal jobId={previewId} onClose={() => setPreviewId(null)} />}
            {dialog}
        </>
    );
}
