import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { PageHead, Badge, Stat, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminUserShow({ user, profile, resumes, applicationCounts, allRoles, currentPlan, plans }) {
    const [role, setRole] = useState(user.roles[0] || '');
    const [planId, setPlanId] = useState(currentPlan?.id || '');

    const post = (url, data = {}, opts = {}) => router.post(url, data, { preserveScroll: true, ...opts });

    const changePlan = (id) => {
        setPlanId(id);
        if (id) post(`/admin/users/${user.id}/subscription`, { plan_id: id });
        else router.delete(`/admin/users/${user.id}/subscription`, { preserveScroll: true });
    };

    return (
        <>
            <PageHead title={user.name} subtitle={user.email} />

            <Link href="/admin/users" className="btn btn-ghost btn-sm" style={{ marginBottom: 16, display: 'inline-block' }}>← Back to Users</Link>

            <div className="stats">
                <Stat label="Total Applications" value={applicationCounts.total} icon={Icons.briefcase} accent="primary" />
                <Stat label="Sent" value={applicationCounts.sent} icon={Icons.send} accent="green" />
                <Stat label="Failed" value={applicationCounts.failed} icon={Icons.alert} accent="red" />
                <Stat label="Resumes" value={resumes.length} icon={Icons.upload} accent="blue" />
            </div>

            <div className="card">
                <h2>Account</h2>
                <table>
                    <tbody>
                        <tr><td>Status</td><td><Badge status={user.is_active ? 'sent' : 'failed'}>{user.is_active ? 'Active' : 'Suspended'}</Badge></td></tr>
                        <tr><td>Email verified</td><td>{user.email_verified_at ? new Date(user.email_verified_at).toLocaleString() : <Badge status="pending">Not verified</Badge>}</td></tr>
                        <tr><td>Last login</td><td>{user.last_login_at ? `${new Date(user.last_login_at).toLocaleString()} from ${user.last_login_ip || 'unknown IP'}` : 'Never'}</td></tr>
                        <tr><td>Joined</td><td>{new Date(user.created_at).toLocaleString()}</td></tr>
                        <tr>
                            <td>Role</td>
                            <td>
                                <select value={role} onChange={(e) => { setRole(e.target.value); post(`/admin/users/${user.id}/role`, { role: e.target.value || null }); }} style={{ width: 'auto' }}>
                                    <option value="">No role (regular user)</option>
                                    {allRoles.map((r) => <option key={r} value={r}>{r}</option>)}
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Plan</td>
                            <td>
                                <select value={planId} onChange={(e) => changePlan(e.target.value)} style={{ width: 'auto' }}>
                                    <option value="">No plan (unlimited)</option>
                                    {plans.map((p) => <option key={p.id} value={p.id}>{p.name} (${p.price})</option>)}
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', marginTop: 16 }}>
                    <button className="btn btn-ghost btn-sm" onClick={() => post(`/admin/users/${user.id}/toggle-active`)}>
                        {user.is_active ? 'Suspend account' : 'Activate account'}
                    </button>
                    {!user.email_verified_at && (
                        <button className="btn btn-ghost btn-sm" onClick={() => post(`/admin/users/${user.id}/verify-email`)}>Mark email verified</button>
                    )}
                    <button className="btn btn-ghost btn-sm" onClick={() => { if (confirm('Generate a new password for this user?')) post(`/admin/users/${user.id}/reset-password`); }}>Reset password</button>
                    <button className="btn btn-ghost btn-sm" onClick={() => { if (confirm(`Log in as ${user.name}? You'll act as this user until you return to admin.`)) post(`/admin/users/${user.id}/login-as`); }}>Login as user</button>
                    <button
                        className="btn btn-danger btn-sm"
                        onClick={() => { if (confirm('Delete this user and all their data? This cannot be undone.')) router.delete(`/admin/users/${user.id}`); }}
                    >
                        Delete user
                    </button>
                </div>
            </div>

            {profile && (
                <div className="card">
                    <h2>Profile</h2>
                    <table>
                        <tbody>
                            <tr><td>Full name</td><td>{profile.full_name || '—'}</td></tr>
                            <tr><td>Contact email</td><td>{profile.email || '—'}</td></tr>
                            <tr><td>Phone</td><td>{profile.phone || '—'}</td></tr>
                            <tr><td>Location</td><td>{profile.location || '—'}</td></tr>
                            <tr><td>Preferred role</td><td>{profile.preferred_role || '—'}</td></tr>
                            <tr><td>Skills</td><td>{profile.skills || '—'}</td></tr>
                            <tr><td>Documents uploaded</td><td>{profile.has_documents ? 'Yes' : 'No'}</td></tr>
                            <tr>
                                <td>Email sender</td>
                                <td>{profile.has_mail_credentials ? `Connected (${profile.mail_username})` : 'Not connected'}</td>
                            </tr>
                            <tr><td>Max emails/hour</td><td>{profile.max_emails_per_hour ?? '—'}</td></tr>
                            <tr><td>Follow-up days</td><td>{profile.followup_days ?? '—'}</td></tr>
                            <tr><td>Webhook URL</td><td>{profile.webhook_url || '—'}</td></tr>
                        </tbody>
                    </table>
                </div>
            )}

            <div className="card">
                <h2>Resumes</h2>
                {resumes.length === 0 ? <p className="muted">No resumes uploaded.</p> : (
                    <table>
                        <thead><tr><th>Name</th><th>Default</th><th>Uploaded</th></tr></thead>
                        <tbody>
                            {resumes.map((r) => (
                                <tr key={r.id}>
                                    <td>{r.name}</td>
                                    <td>{r.is_default ? <Badge status="sent">Default</Badge> : '—'}</td>
                                    <td>{new Date(r.created_at).toLocaleDateString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        </>
    );
}

AdminUserShow.layout = (page) => <AdminLayout>{page}</AdminLayout>;
