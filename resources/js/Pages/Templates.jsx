import React, { useRef, useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { PageHead, Badge, Icons, IconField, ChipIcon, EmptyState, useConfirm } from '../components';

const PLACEHOLDERS = ['{job_title}', '{company}', '{recruiter_name}', '{location}', '{job_url}', '{your_name}'];

/**
 * Insert `text` at the current cursor position of `el` (input/textarea),
 * update React state via `setValue`, and restore focus + cursor position.
 */
function insertAtCursor(el, value, setValue, text) {
    if (!el) { setValue(value + text); return; }
    const start = el.selectionStart ?? value.length;
    const end = el.selectionEnd ?? value.length;
    const next = value.slice(0, start) + text + value.slice(end);
    setValue(next);
    requestAnimationFrame(() => {
        el.focus();
        el.selectionStart = el.selectionEnd = start + text.length;
    });
}

function PlaceholderChips({ onInsert }) {
    return (
        <div className="ph-chips">
            <span className="hint" style={{ margin: 0 }}>Placeholders — click to insert:</span>
            {PLACEHOLDERS.map((p) => (
                <button type="button" key={p} className="ph-chip" onClick={() => onInsert(p)}>{p}</button>
            ))}
        </div>
    );
}

/**
 * One modal handles both creating a new template and editing an existing
 * one — same fields, same validation, just a different submit verb/target
 * and starting values. Keeps the page itself to a browsable card grid
 * instead of a stack of permanently-open forms.
 */
function TemplateModal({ mode, template, onClose }) {
    const isEdit = mode === 'edit';
    const { data, setData, post, put, processing, errors } = useForm({
        name: template?.name ?? '',
        subject: template?.subject ?? '',
        body: template?.body ?? '',
        is_default: !!template?.is_default,
    });
    const subjectRef = useRef(null);
    const bodyRef = useRef(null);
    const lastFocused = useRef('body');

    const submit = (e) => {
        e.preventDefault();
        const opts = { onSuccess: onClose };
        if (isEdit) put(`/templates/${template.id}`, opts);
        else post('/templates', opts);
    };

    const insert = (text) => {
        if (lastFocused.current === 'subject') {
            insertAtCursor(subjectRef.current, data.subject, (v) => setData('subject', v), text);
        } else {
            insertAtCursor(bodyRef.current, data.body, (v) => setData('body', v), text);
        }
    };

    return (
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            {/* The base .modal scrolls as one block, so a tall form (like this one)
                can scroll its own title/close button out of view — e.g. the browser
                auto-scrolling to the body textarea on focus. Splitting into a fixed
                header + a separately-scrolling form body keeps the close button and
                title reachable no matter how far the form itself has scrolled. */}
            <div className="modal" style={{ maxWidth: 680, padding: 0, overflow: 'hidden', display: 'flex', flexDirection: 'column' }}>
                <div style={{ position: 'relative', flexShrink: 0, padding: '24px 24px 0' }}>
                    <button className="modal-close" onClick={onClose} aria-label="Close"><ChipIcon icon={Icons.x} /></button>
                    <h3 className="modal-title">{isEdit ? 'Edit template' : 'New template'}</h3>
                </div>

                <form onSubmit={submit} style={{ overflowY: 'auto', padding: '0 24px 24px' }}>
                    <PlaceholderChips onInsert={insert} />

                    <div className="row" style={{ marginTop: 4 }}>
                        <IconField icon={Icons.tag} type="text" value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Engineering roles" required />
                        <IconField icon={Icons.mail} type="text" value={data.subject} ref={subjectRef}
                            onFocus={() => { lastFocused.current = 'subject'; }}
                            onChange={(e) => setData('subject', e.target.value)}
                            placeholder="Application for {job_title} at {company}" required />
                    </div>
                    {(errors.name || errors.subject) && (
                        <p className="hint" style={{ color: 'var(--red)', margin: '6px 0 0' }}>{errors.name || errors.subject}</p>
                    )}

                    <label style={{ marginTop: 14 }}>Email body</label>
                    <textarea rows={8} value={data.body} ref={bodyRef}
                        onFocus={() => { lastFocused.current = 'body'; }}
                        onChange={(e) => setData('body', e.target.value)}
                        placeholder="Dear {recruiter_name}, ..." required />
                    {errors.body && <p className="hint" style={{ color: 'var(--red)', margin: '6px 0 0' }}>{errors.body}</p>}

                    <label className="inline" style={{ marginTop: 14 }}>
                        <input type="checkbox" checked={data.is_default}
                            onChange={(e) => setData('is_default', e.target.checked)} /> Set as default template
                    </label>

                    <div className="modal-actions" style={{ marginTop: 22 }}>
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="btn btn-primary" disabled={processing}>
                            <ChipIcon icon={Icons.save} /> {isEdit ? 'Save changes' : 'Create template'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

function NewTemplateTile({ onClick }) {
    return (
        <button type="button" className="tpl-card tpl-card-new" onClick={onClick}>
            <span className="tpl-card-new-ico"><ChipIcon icon={Icons.plus} /></span>
            <strong>New template</strong>
            <span className="hint" style={{ margin: 0 }}>Write once, reuse on every send</span>
        </button>
    );
}

function TemplateCard({ template, onEdit, onSetDefault, onDelete, busy }) {
    return (
        <div className={`tpl-card${template.is_default ? ' is-default' : ''}`}>
            <div className="tpl-card-head">
                <span className="co-avatar">{(template.name || '?')[0].toUpperCase()}</span>
                <div style={{ flex: 1, minWidth: 0 }}>
                    <strong className="tpl-card-name">{template.name}</strong>
                    {template.is_default
                        ? <Badge status="sent">Default</Badge>
                        : (
                            <button type="button" className="btn-link tpl-card-setdefault" disabled={busy} onClick={() => onSetDefault(template)}>
                                Set as default
                            </button>
                        )}
                </div>
            </div>

            <div className="tpl-card-subject">
                <ChipIcon icon={Icons.mail} /> <span>{template.subject}</span>
            </div>
            <p className="tpl-card-body">{template.body}</p>

            <div className="tpl-card-actions">
                <button type="button" className="btn btn-ghost btn-sm" onClick={() => onEdit(template)}>
                    <ChipIcon icon={Icons.save} /> Edit
                </button>
                <button type="button" className="btn btn-danger btn-sm" disabled={busy} onClick={() => onDelete(template)}>
                    <ChipIcon icon={Icons.trash} /> Delete
                </button>
            </div>
        </div>
    );
}

export default function Templates({ templates }) {
    const [modal, setModal] = useState(null); // null | { mode: 'create' } | { mode: 'edit', template }
    const [busyId, setBusyId] = useState(null);
    const { confirm, dialog } = useConfirm();

    const setDefault = (template) => {
        setBusyId(template.id);
        router.put(`/templates/${template.id}`, {
            name: template.name, subject: template.subject, body: template.body, is_default: true,
        }, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };

    const destroy = async (template) => {
        const ok = await confirm({
            title: 'Delete this template?',
            message: `"${template.name}" will be permanently removed.`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (!ok) return;
        setBusyId(template.id);
        router.delete(`/templates/${template.id}`, { preserveScroll: true, onFinish: () => setBusyId(null) });
    };

    return (
        <>
            <PageHead title="Email Templates"
                subtitle="Create multiple templates for different job types. The default is used unless you choose another when sending." />

            {templates.length === 0 ? (
                <div className="card">
                    <EmptyState icon="mail" title="No templates yet">
                        <button type="button" className="btn-link" onClick={() => setModal({ mode: 'create' })}>Create one</button>,
                        {' '}or leave it — your profile's default template is used automatically when applying.
                    </EmptyState>
                </div>
            ) : (
                <div className="tpl-grid">
                    <NewTemplateTile onClick={() => setModal({ mode: 'create' })} />
                    {templates.map((t) => (
                        <TemplateCard key={t.id} template={t} busy={busyId === t.id}
                            onEdit={(tpl) => setModal({ mode: 'edit', template: tpl })}
                            onSetDefault={setDefault} onDelete={destroy} />
                    ))}
                </div>
            )}

            {modal && (
                <TemplateModal mode={modal.mode} template={modal.template} onClose={() => setModal(null)} />
            )}
            {dialog}
        </>
    );
}
