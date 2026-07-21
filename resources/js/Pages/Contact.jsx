import React from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';

export default function Contact() {
    const { props } = usePage();
    const flash = props.flash || {};
    const form = useForm({ type: 'contact', name: '', email: '', subject: '', message: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post('/contact', { onSuccess: () => form.reset('subject', 'message') });
    };

    return (
        <div style={{ maxWidth: 560, margin: '60px auto', padding: '0 20px' }}>
            <Head title="Contact — BulkApply" />
            <Link href="/" style={{ fontWeight: 700, fontSize: 20 }}>BulkApply</Link>
            <div className="card" style={{ marginTop: 24 }}>
                <h2>Contact us</h2>
                <p className="muted">Questions, feedback, feature requests, or bug reports — send them here.</p>

                {flash.status && <div className="alert alert-success" style={{ marginBottom: 16 }}><div className="alert-body">{flash.status}</div></div>}

                <form onSubmit={submit}>
                    <select value={form.data.type} onChange={(e) => form.setData('type', e.target.value)}>
                        <option value="contact">General question</option>
                        <option value="feedback">Feedback</option>
                        <option value="feature_request">Feature request</option>
                        <option value="bug_report">Bug report</option>
                    </select>
                    <input type="text" placeholder="Your name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} style={{ marginTop: 10 }} required />
                    {form.errors.name && <div style={{ color: 'var(--red)', fontSize: 12.5, marginTop: 4 }}>{form.errors.name}</div>}
                    <input type="email" placeholder="Your email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} style={{ marginTop: 10 }} required />
                    {form.errors.email && <div style={{ color: 'var(--red)', fontSize: 12.5, marginTop: 4 }}>{form.errors.email}</div>}
                    <input type="text" placeholder="Subject (optional)" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} style={{ marginTop: 10 }} />
                    <textarea placeholder="Message" value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} style={{ marginTop: 10, minHeight: 120, width: '100%' }} required />
                    {form.errors.message && <div style={{ color: 'var(--red)', fontSize: 12.5, marginTop: 4 }}>{form.errors.message}</div>}
                    <button type="submit" className="btn btn-primary btn-sm" style={{ marginTop: 14 }} disabled={form.processing}>Send message</button>
                </form>
            </div>
        </div>
    );
}

Contact.layout = (page) => page;
