import React, { useRef } from 'react';
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

function CreateForm() {
    const { data, setData, post, processing, reset } = useForm({
        name: '', subject: '', body: '', is_default: false,
    });
    const subjectRef = useRef(null);
    const bodyRef = useRef(null);
    const lastFocused = useRef('body');

    const submit = (e) => {
        e.preventDefault();
        post('/templates', { onSuccess: () => reset() });
    };

    const insert = (text) => {
        if (lastFocused.current === 'subject') {
            insertAtCursor(subjectRef.current, data.subject, (v) => setData('subject', v), text);
        } else {
            insertAtCursor(bodyRef.current, data.body, (v) => setData('body', v), text);
        }
    };

    return (
        <div className="card hero-card">
            <div className="hero-card-head">
                <span className="hero-card-ico"><ChipIcon icon={Icons.mail} /></span>
                <div>
                    <h2 style={{ margin: 0 }}>Create New Template</h2>
                    <p className="hint" style={{ margin: '3px 0 0' }}>
                        Tailor the subject and body for a specific type of role, then reuse it when applying.
                    </p>
                </div>
            </div>

            <PlaceholderChips onInsert={insert} />

            <form onSubmit={submit}>
                <div className="row" style={{ marginTop: 4 }}>
                    <IconField icon={Icons.tag} type="text" value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="e.g. Engineering roles" required />
                    <IconField icon={Icons.mail} type="text" value={data.subject} ref={subjectRef}
                        onFocus={() => { lastFocused.current = 'subject'; }}
                        onChange={(e) => setData('subject', e.target.value)}
                        placeholder="Application for {job_title} at {company}" required />
                </div>
                <label>Email body</label>
                <textarea rows={8} value={data.body} ref={bodyRef}
                    onFocus={() => { lastFocused.current = 'body'; }}
                    onChange={(e) => setData('body', e.target.value)}
                    placeholder="Dear {recruiter_name}, ..." required />
                <div style={{ marginTop: 14, display: 'flex', alignItems: 'center', gap: 18, flexWrap: 'wrap' }}>
                    <button type="submit" className="btn btn-primary" disabled={processing}>
                        <ChipIcon icon={Icons.save} /> Save Template
                    </button>
                    <label className="inline">
                        <input type="checkbox" checked={data.is_default}
                            onChange={(e) => setData('is_default', e.target.checked)} /> Set as default
                    </label>
                </div>
            </form>
        </div>
    );
}

function TemplateCard({ template }) {
    const { data, setData, put, processing } = useForm({
        name: template.name, subject: template.subject, body: template.body,
        is_default: !!template.is_default,
    });

    const { confirm, dialog } = useConfirm();

    const save = (e) => {
        e.preventDefault();
        put(`/templates/${template.id}`);
    };

    const destroy = async () => {
        const ok = await confirm({
            title: 'Delete this template?',
            message: `"${template.name}" will be permanently removed.`,
            confirmLabel: 'Delete',
            danger: true,
        });
        if (ok) router.delete(`/templates/${template.id}`);
    };

    return (
        <div className="card template-card">
            <form onSubmit={save}>
                <div className="template-card-head">
                    <span className="co-avatar">{(template.name || '?')[0].toUpperCase()}</span>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                            <strong style={{ fontSize: 15 }}>{template.name}</strong>
                            {template.is_default && <Badge status="sent">Default</Badge>}
                        </div>
                    </div>
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button type="submit" className="btn btn-ghost btn-sm" disabled={processing}>
                            <ChipIcon icon={Icons.save} /> Save
                        </button>
                        <button type="button" className="btn btn-danger btn-sm" onClick={destroy}>
                            <ChipIcon icon={Icons.trash} />
                        </button>
                    </div>
                </div>
                <div className="row">
                    <IconField icon={Icons.tag} type="text" value={data.name}
                        onChange={(e) => setData('name', e.target.value)} required />
                    <IconField icon={Icons.mail} type="text" value={data.subject}
                        onChange={(e) => setData('subject', e.target.value)} required />
                </div>
                <label>Body</label>
                <textarea rows={6} value={data.body} onChange={(e) => setData('body', e.target.value)} required />
                <label className="inline" style={{ marginTop: 10 }}>
                    <input type="checkbox" checked={data.is_default}
                        onChange={(e) => setData('is_default', e.target.checked)} /> Default template
                </label>
            </form>
            {dialog}
        </div>
    );
}

export default function Templates({ templates }) {
    return (
        <>
            <PageHead title="Email Templates"
                subtitle="Create multiple templates for different job types. The default is used unless you choose another when sending." />
            <CreateForm />
            {templates.length === 0 ? (
                <div className="card">
                    <EmptyState icon="mail" title="No templates yet">
                        Create one above, or leave it — your profile's default template is used automatically when applying.
                    </EmptyState>
                </div>
            ) : (
                templates.map((t) => <TemplateCard key={t.id} template={t} />)
            )}
        </>
    );
}
