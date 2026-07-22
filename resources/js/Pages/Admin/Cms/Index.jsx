import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { PageHead, Badge, Icons, ChipIcon } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function PageForm({ page, onDone }) {
    const isNew = !page;
    const form = useForm({
        slug: page?.slug || '',
        title: page?.title || '',
        content: page?.content || '',
        status: page?.status || 'draft',
    });

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (isNew) form.post('/admin/cms', opts);
        else form.put(`/admin/cms/${page.id}`, opts);
    };

    return (
        <form onSubmit={submit} className="card card-pad-sm" style={{ marginBottom: 16 }}>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 10 }}>
                <input placeholder="Slug (e.g. about)" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} disabled={!isNew} required />
                <input placeholder="Title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} required />
                <select value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            {form.errors.slug && <div style={{ color: 'var(--red)', fontSize: 12.5, marginTop: 6 }}>{form.errors.slug}</div>}
            <textarea placeholder="Page content" value={form.data.content} onChange={(e) => form.setData('content', e.target.value)} style={{ marginTop: 10, minHeight: 140, width: '100%' }} />
            <div style={{ marginTop: 12 }}>
                <button type="submit" className="btn btn-primary btn-sm" disabled={form.processing}>{isNew ? 'Create page' : 'Save changes'}</button>
                {!isNew && <button type="button" className="btn btn-ghost btn-sm" onClick={onDone} style={{ marginLeft: 8 }}>Cancel</button>}
            </div>
        </form>
    );
}

export default function AdminCmsIndex({ pages }) {
    const [editing, setEditing] = useState(null);
    const [creating, setCreating] = useState(false);

    const destroy = (page) => { if (confirm(`Delete "${page.title}"?`)) router.delete(`/admin/cms/${page.id}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="CMS Pages" subtitle="DB-backed content for public pages, served at /p/{slug}." />

            {editing ? (
                <PageForm page={editing} onDone={() => setEditing(null)} />
            ) : creating ? (
                <PageForm onDone={() => setCreating(false)} />
            ) : (
                <button className="btn btn-primary btn-sm" style={{ marginBottom: 16 }} onClick={() => setCreating(true)}>
                    <ChipIcon icon={Icons.plus} /> New page
                </button>
            )}

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Slug</th><th>Title</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                        <tbody>
                            {pages.map((p) => (
                                <tr key={p.id}>
                                    <td>/p/{p.slug}</td>
                                    <td>{p.title}</td>
                                    <td><Badge status={p.status === 'published' ? 'sent' : 'pending'}>{p.status}</Badge></td>
                                    <td>{new Date(p.updated_at).toLocaleDateString()}</td>
                                    <td style={{ display: 'flex', gap: 6 }}>
                                        {p.status === 'published' && <a className="btn btn-ghost btn-sm" href={`/p/${p.slug}`} target="_blank" rel="noopener">View</a>}
                                        <button className="btn btn-ghost btn-sm" onClick={() => setEditing(p)}>Edit</button>
                                        <button className="btn btn-danger btn-sm" onClick={() => destroy(p)}>Delete</button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AdminCmsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
