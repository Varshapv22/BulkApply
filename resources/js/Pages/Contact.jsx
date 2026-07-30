import React from 'react';
import { useForm, Link, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import { ChipIcon, Icons, FileDropzone, SUPPORT_TICKET_TYPES } from '../components';

const FIELD_LABEL = { fontSize: 12.5, fontWeight: 600, color: 'var(--muted)' };
const FIELD_ERROR = { color: 'var(--red)', fontSize: 12.5, marginTop: 4 };

function InfoRow({ icon, title, children }) {
    return (
        <div style={{ display: 'flex', gap: 12 }}>
            <span style={{
                display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                width: 34, height: 34, borderRadius: 10, flexShrink: 0,
                background: 'var(--hover)', color: 'var(--primary)',
            }}>
                <ChipIcon icon={Icons[icon]} />
            </span>
            <div>
                <div style={{ fontSize: 13.5, fontWeight: 700, color: 'var(--heading)' }}>{title}</div>
                <div className="muted" style={{ fontSize: 12.5, marginTop: 2, lineHeight: 1.5 }}>{children}</div>
            </div>
        </div>
    );
}

export default function Contact() {
    const { props } = usePage();
    const flash = props.flash || {};
    const user = props.user;
    const form = useForm({ type: 'contact', name: user?.name || '', email: user?.email || '', subject: '', message: '', attachment: null });

    const submit = (e) => {
        e.preventDefault();
        form.post('/contact', { onSuccess: () => form.reset('subject', 'message', 'attachment') });
    };

    return (
        <div style={{ minHeight: '100vh', background: 'var(--bg)' }}>
            <Head title="Contact & Support — BulkApply" />

            <div style={{ maxWidth: 1040, margin: '0 auto', padding: '32px 24px 72px' }}>
                <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 12, flexWrap: 'wrap' }}>
                    <Link href={user ? '/dashboard' : '/'} style={{ fontWeight: 700, fontSize: 20, color: 'var(--heading)' }}>← BulkApply</Link>
                    {user && <Link href="/support/tickets" className="btn btn-ghost btn-sm">My Tickets</Link>}
                </div>

                <div style={{ marginTop: 32, textAlign: 'center' }}>
                    <h1 style={{ fontSize: 32, fontWeight: 800, letterSpacing: '-0.02em', color: 'var(--heading)', margin: 0 }}>Contact & Support</h1>
                    <p className="muted" style={{ marginTop: 10, fontSize: 15.5, maxWidth: 560, marginLeft: 'auto', marginRight: 'auto' }}>
                        Questions, feedback, feature requests, or bug reports — tell us what's up and we'll get back to you, usually within 24–48 hours.
                    </p>
                </div>

                {flash.status && (
                    <div className="alert alert-success" style={{ marginTop: 24, maxWidth: 720, marginLeft: 'auto', marginRight: 'auto' }}>
                        <div className="alert-body">{flash.status}</div>
                    </div>
                )}

                <div className="contact-grid" style={{ display: 'grid', gap: 24, marginTop: 32, alignItems: 'start' }}>
                    <form onSubmit={submit} className="card" style={{ padding: 32 }}>
                        <label style={{ ...FIELD_LABEL, display: 'block', marginBottom: 10 }}>What's this about?</label>
                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(160px, 100%), 1fr))', gap: 10 }}>
                            {Object.entries(SUPPORT_TICKET_TYPES).map(([key, meta]) => {
                                const active = form.data.type === key;
                                return (
                                    <button
                                        key={key}
                                        type="button"
                                        onClick={() => form.setData('type', key)}
                                        style={{
                                            display: 'flex', alignItems: 'center', gap: 10, textAlign: 'left',
                                            padding: '13px 14px', borderRadius: 12, cursor: 'pointer',
                                            border: `1.5px solid ${active ? 'var(--primary)' : 'var(--border)'}`,
                                            background: active ? 'var(--primary-bg, var(--hover))' : 'transparent',
                                            color: 'inherit', font: 'inherit', transition: 'border-color .15s, background .15s',
                                        }}
                                    >
                                        <span style={{
                                            display: 'inline-flex', alignItems: 'center', justifyContent: 'center',
                                            width: 32, height: 32, borderRadius: 9, flexShrink: 0,
                                            background: active ? 'var(--primary)' : 'var(--hover)',
                                            color: active ? '#fff' : 'var(--muted)',
                                        }}>
                                            <ChipIcon icon={Icons[meta.icon]} />
                                        </span>
                                        <span style={{ fontSize: 13.5, fontWeight: 600 }}>{meta.label}</span>
                                    </button>
                                );
                            })}
                        </div>

                        <div style={{ height: 1, background: 'var(--border)', margin: '26px 0' }} />

                        <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(min(240px, 100%), 1fr))', gap: 16 }}>
                            <div>
                                <label style={FIELD_LABEL}>Your name</label>
                                <input type="text" placeholder="Jane Doe" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} style={{ marginTop: 6 }} required />
                                {form.errors.name && <div style={FIELD_ERROR}>{form.errors.name}</div>}
                            </div>
                            <div>
                                <label style={FIELD_LABEL}>Your email</label>
                                <input type="email" placeholder="jane@example.com" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} style={{ marginTop: 6 }} required />
                                {form.errors.email && <div style={FIELD_ERROR}>{form.errors.email}</div>}
                            </div>
                        </div>

                        <div style={{ marginTop: 16 }}>
                            <label style={FIELD_LABEL}>Subject (optional)</label>
                            <input type="text" placeholder="Short summary" value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} style={{ marginTop: 6 }} />
                        </div>

                        <div style={{ marginTop: 16 }}>
                            <label style={FIELD_LABEL}>Message</label>
                            <textarea placeholder="Describe your question, feedback, or the bug you ran into — the more detail, the faster we can help." value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} style={{ marginTop: 6, minHeight: 150, width: '100%' }} required />
                            {form.errors.message && <div style={FIELD_ERROR}>{form.errors.message}</div>}
                        </div>

                        <div style={{ marginTop: 16 }}>
                            <label style={FIELD_LABEL}>Attachment (optional) — a screenshot really helps for bug reports</label>
                            <div style={{ marginTop: 6 }}>
                                <FileDropzone value={form.data.attachment} onChange={(file) => form.setData('attachment', file)} accept=".png,.jpg,.jpeg,.pdf" />
                            </div>
                            {form.errors.attachment && <div style={FIELD_ERROR}>{form.errors.attachment}</div>}
                        </div>

                        <button type="submit" className="btn btn-primary" style={{ marginTop: 24, minWidth: 180 }} disabled={form.processing}>
                            {form.processing ? 'Sending…' : 'Send message'}
                        </button>
                    </form>

                    <div style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
                        <div className="card" style={{ padding: 22, display: 'flex', flexDirection: 'column', gap: 18 }}>
                            <InfoRow icon="clock" title="Response time">We typically reply within 24–48 hours, often sooner.</InfoRow>
                            <InfoRow icon="mail" title="Prefer email?">Reach us directly at <a href="mailto:support@bulkapply.com">support@bulkapply.com</a>.</InfoRow>
                            {user ? (
                                <InfoRow icon="chat" title="Already sent something?">
                                    Track replies and follow up anytime from <Link href="/support/tickets">My Tickets</Link>.
                                </InfoRow>
                            ) : (
                                <InfoRow icon="user" title="Have an account?">
                                    <Link href="/login">Sign in</Link> first to track replies to your tickets.
                                </InfoRow>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

Contact.layout = (page) => page;
