import React from 'react';
import { useForm, router } from '@inertiajs/react';
import { PageHead, Badge } from '../components';

const PLACEHOLDERS = ['{job_title}', '{company}', '{recruiter_name}', '{location}', '{job_url}', '{your_name}'];

function Placeholders() {
    return (
        <p className="hint">
            Placeholders: {PLACEHOLDERS.map((p) => <code key={p} style={{ marginRight: 6 }}>{p}</code>)}
        </p>
    );
}

function CreateForm() {
    const { data, setData, post, processing, reset } = useForm({
        name: '', subject: '', body: '', is_default: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/templates', { onSuccess: () => reset() });
    };

    return (
        <div className="card">
            <h2>Create New Template</h2>
            <Placeholders />
            <form onSubmit={submit}>
                <div className="row">
                    <div>
                        <label>Template name</label>
                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Engineering roles" required />
                    </div>
                    <div>
                        <label>Email subject</label>
                        <input type="text" value={data.subject} onChange={(e) => setData('subject', e.target.value)}
                            placeholder="Application for {job_title} at {company}" required />
                    </div>
                </div>
                <label>Email body</label>
                <textarea rows={8} value={data.body} onChange={(e) => setData('body', e.target.value)}
                    placeholder="Dear {recruiter_name}, ..." required />
                <div style={{ marginTop: 12, display: 'flex', alignItems: 'center', gap: 16 }}>
                    <button type="submit" className="btn btn-primary" disabled={processing}>Save Template</button>
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

    const save = (e) => {
        e.preventDefault();
        put(`/templates/${template.id}`);
    };

    const destroy = () => {
        if (confirm('Delete this template?')) router.delete(`/templates/${template.id}`);
    };

    return (
        <div className="card">
            <form onSubmit={save}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12 }}>
                    <div>
                        <h2 style={{ display: 'inline' }}>{template.name}</h2>
                        {template.is_default && <Badge status="sent"><span style={{ marginLeft: 8 }}>Default</span></Badge>}
                    </div>
                    <div style={{ display: 'flex', gap: 8 }}>
                        <button type="submit" className="btn btn-ghost btn-sm" disabled={processing}>Save</button>
                        <button type="button" className="btn btn-danger btn-sm" onClick={destroy}>Delete</button>
                    </div>
                </div>
                <div className="row">
                    <div>
                        <label>Name</label>
                        <input type="text" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    </div>
                    <div>
                        <label>Subject</label>
                        <input type="text" value={data.subject} onChange={(e) => setData('subject', e.target.value)} required />
                    </div>
                </div>
                <label>Body</label>
                <textarea rows={6} value={data.body} onChange={(e) => setData('body', e.target.value)} required />
                <label className="inline" style={{ marginTop: 10 }}>
                    <input type="checkbox" checked={data.is_default}
                        onChange={(e) => setData('is_default', e.target.checked)} /> Default template
                </label>
            </form>
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
                <div className="card empty">No templates yet. Create one above or use the profile template.</div>
            ) : (
                templates.map((t) => <TemplateCard key={t.id} template={t} />)
            )}
        </>
    );
}
