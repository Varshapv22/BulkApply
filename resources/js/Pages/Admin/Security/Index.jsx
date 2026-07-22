import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function TwoFactorSetup({ enabled }) {
    const { props } = usePage();
    const [step, setStep] = useState('idle'); // idle | setup | done
    const [code, setCode] = useState('');
    const secret = props.twoFactorSecret;
    const uri = props.twoFactorUri;
    const recoveryCodes = props.recoveryCodes;

    const startSetup = () => router.post('/admin/security/2fa/enable', {}, { preserveScroll: true, onSuccess: () => setStep('setup') });
    const confirm = (e) => {
        e.preventDefault();
        router.post('/admin/security/2fa/confirm', { code }, { preserveScroll: true, onSuccess: () => setStep('done') });
    };
    const disable = () => { if (confirm('Disable two-factor authentication?')) router.post('/admin/security/2fa/disable', {}, { preserveScroll: true }); };

    if (enabled && step !== 'done') {
        return (
            <div className="card">
                <h2>Two-Factor Authentication</h2>
                <p><Badge status="sent">Enabled</Badge> Your account is protected with an authenticator app.</p>
                <button className="btn btn-danger btn-sm" onClick={disable}>Disable 2FA</button>
            </div>
        );
    }

    return (
        <div className="card">
            <h2>Two-Factor Authentication</h2>
            {step === 'idle' && (
                <>
                    <p className="muted">Not enabled. Protect your admin account with an authenticator app (Google Authenticator, Authy, etc.).</p>
                    <button className="btn btn-primary btn-sm" onClick={startSetup}>Enable 2FA</button>
                </>
            )}
            {step === 'setup' && secret && (
                <div>
                    <p>Add this key to your authenticator app (manual entry — no QR image), then enter the 6-digit code it generates:</p>
                    <code style={{ display: 'block', padding: 10, background: 'var(--hover)', borderRadius: 8, marginBottom: 10, wordBreak: 'break-all' }}>{secret}</code>
                    <p className="muted" style={{ fontSize: 12 }}>otpauth URI: {uri}</p>
                    <form onSubmit={confirm} style={{ display: 'flex', gap: 8, marginTop: 10 }}>
                        <input type="text" placeholder="123456" value={code} onChange={(e) => setCode(e.target.value)} style={{ maxWidth: 160 }} />
                        <button type="submit" className="btn btn-primary btn-sm">Confirm</button>
                    </form>
                </div>
            )}
            {step === 'done' && recoveryCodes && (
                <div>
                    <p><Badge status="sent">Enabled</Badge></p>
                    <p className="muted">Save these recovery codes somewhere safe — each works once if you lose access to your authenticator:</p>
                    <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 6, fontFamily: 'monospace', background: 'var(--hover)', padding: 12, borderRadius: 8 }}>
                        {recoveryCodes.map((c) => <span key={c}>{c}</span>)}
                    </div>
                </div>
            )}
        </div>
    );
}

function IpRules({ ipRules }) {
    const [form, setForm] = useState({ ip_or_cidr: '', type: 'block', note: '' });

    const add = (e) => {
        e.preventDefault();
        router.post('/admin/security/ip-rules', form, { preserveScroll: true, onSuccess: () => setForm({ ip_or_cidr: '', type: 'block', note: '' }) });
    };
    const remove = (id) => router.delete(`/admin/security/ip-rules/${id}`, { preserveScroll: true });

    return (
        <div className="card">
            <h2>IP Rules</h2>
            <form onSubmit={add} style={{ display: 'flex', gap: 8, marginBottom: 16, flexWrap: 'wrap' }}>
                <input type="text" placeholder="IP or CIDR (e.g. 1.2.3.4 or 1.2.3.0/24)" value={form.ip_or_cidr} onChange={(e) => setForm({ ...form, ip_or_cidr: e.target.value })} style={{ maxWidth: 260 }} required />
                <select value={form.type} onChange={(e) => setForm({ ...form, type: e.target.value })} style={{ width: 'auto' }}>
                    <option value="block">Block</option>
                    <option value="allow">Allow (informational only)</option>
                </select>
                <input type="text" placeholder="Note (optional)" value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} style={{ maxWidth: 200 }} />
                <button type="submit" className="btn btn-primary btn-sm">Add rule</button>
            </form>

            {ipRules.length === 0 ? <p className="muted">No IP rules configured.</p> : (
                <div className="table-wrap">
                    <table>
                        <thead><tr><th>IP / CIDR</th><th>Type</th><th>Note</th><th></th></tr></thead>
                        <tbody>
                            {ipRules.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.ip_or_cidr}</td>
                                    <td><Badge status={r.type === 'block' ? 'failed' : 'sent'}>{r.type}</Badge></td>
                                    <td>{r.note || '—'}</td>
                                    <td><button className="btn btn-ghost btn-sm" onClick={() => remove(r.id)}>Remove</button></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

export default function AdminSecurityIndex({ twoFactorEnabled, loginHistory, failedAttempts, suspendedUsers, ipRules, activeSessions }) {
    return (
        <>
            <PageHead title="Security" subtitle="Two-factor auth, login history, and IP access rules." />

            <div className="stats">
                <Stat label="Failed Logins (24h)" value={failedAttempts} icon={Icons.alert} accent="red" />
                <Stat label="Suspended Users" value={suspendedUsers.length} icon={Icons.user} accent="amber" />
                <Stat label="Your Active Sessions" value={activeSessions} icon={Icons.check} accent="green" />
            </div>

            <TwoFactorSetup enabled={twoFactorEnabled} />
            <IpRules ipRules={ipRules} />

            <div className="card">
                <h2>Login History</h2>
                <div style={{ overflowX: 'auto' }}>
                    <table>
                        <thead><tr><th>Email</th><th>Result</th><th>IP</th><th>When</th></tr></thead>
                        <tbody>
                            {loginHistory.map((l) => (
                                <tr key={l.id}>
                                    <td>{l.email}</td>
                                    <td><Badge status={l.successful ? 'sent' : 'failed'}>{l.successful ? 'Success' : 'Failed'}</Badge></td>
                                    <td>{l.ip}</td>
                                    <td>{new Date(l.created_at).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AdminSecurityIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
