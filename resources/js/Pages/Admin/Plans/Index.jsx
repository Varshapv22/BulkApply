import React, { useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import { PageHead, Badge, Icons, ChipIcon } from '../../../components';
import AdminLayout from '../../../AdminLayout';

const EMPTY = {
    name: '', price: 0, billing_interval: 'monthly',
    email_limit: '', resume_limit: '', daily_application_limit: '', queue_priority: 0, storage_limit_mb: '',
    chrome_extension_access: true, ats_checker_access: true, api_access: true,
};

function PlanForm({ plan, onDone }) {
    const form = useForm(plan ? {
        name: plan.name, price: plan.price, billing_interval: plan.billing_interval,
        email_limit: plan.email_limit ?? '', resume_limit: plan.resume_limit ?? '',
        daily_application_limit: plan.daily_application_limit ?? '', queue_priority: plan.queue_priority,
        storage_limit_mb: plan.storage_limit_mb ?? '',
        chrome_extension_access: plan.chrome_extension_access, ats_checker_access: plan.ats_checker_access, api_access: plan.api_access,
    } : EMPTY);

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onDone };
        if (plan) form.put(`/admin/plans/${plan.id}`, opts);
        else form.post('/admin/plans', { ...opts, onSuccess: () => { form.reset(); onDone(); } });
    };

    return (
        <form onSubmit={submit} className="card card-pad-sm" style={{ marginBottom: 16 }}>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(160px, 1fr))', gap: 10 }}>
                <input placeholder="Plan name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                <input type="number" step="0.01" placeholder="Price" value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} required />
                <select value={form.data.billing_interval} onChange={(e) => form.setData('billing_interval', e.target.value)}>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
                <input type="number" placeholder="Email limit (blank = unlimited)" value={form.data.email_limit} onChange={(e) => form.setData('email_limit', e.target.value)} />
                <input type="number" placeholder="Resume limit (blank = unlimited)" value={form.data.resume_limit} onChange={(e) => form.setData('resume_limit', e.target.value)} />
                <input type="number" placeholder="Daily application limit" value={form.data.daily_application_limit} onChange={(e) => form.setData('daily_application_limit', e.target.value)} />
                <input type="number" placeholder="Queue priority" value={form.data.queue_priority} onChange={(e) => form.setData('queue_priority', e.target.value)} />
                <input type="number" placeholder="Storage limit (MB)" value={form.data.storage_limit_mb} onChange={(e) => form.setData('storage_limit_mb', e.target.value)} />
            </div>
            <div style={{ display: 'flex', gap: 16, marginTop: 12, flexWrap: 'wrap' }}>
                <label style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                    <input type="checkbox" checked={form.data.chrome_extension_access} onChange={(e) => form.setData('chrome_extension_access', e.target.checked)} /> Chrome extension
                </label>
                <label style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                    <input type="checkbox" checked={form.data.ats_checker_access} onChange={(e) => form.setData('ats_checker_access', e.target.checked)} /> ATS checker
                </label>
                <label style={{ display: 'flex', gap: 6, alignItems: 'center' }}>
                    <input type="checkbox" checked={form.data.api_access} onChange={(e) => form.setData('api_access', e.target.checked)} /> API access
                </label>
            </div>
            <div style={{ marginTop: 12 }}>
                <button type="submit" className="btn btn-primary btn-sm" disabled={form.processing}>{plan ? 'Save changes' : 'Create plan'}</button>
                {plan && <button type="button" className="btn btn-ghost btn-sm" onClick={onDone} style={{ marginLeft: 8 }}>Cancel</button>}
            </div>
        </form>
    );
}

export default function AdminPlansIndex({ plans }) {
    const [editing, setEditing] = useState(null);
    const [creating, setCreating] = useState(false);

    const toggleActive = (plan) => router.post(`/admin/plans/${plan.id}/toggle-active`, {}, { preserveScroll: true });
    const destroy = (plan) => { if (confirm(`Delete plan "${plan.name}"?`)) router.delete(`/admin/plans/${plan.id}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Plans" subtitle="Define subscription tiers and their limits." />

            {editing ? (
                <PlanForm plan={editing} onDone={() => setEditing(null)} />
            ) : creating ? (
                <PlanForm onDone={() => setCreating(false)} />
            ) : (
                <button className="btn btn-primary btn-sm" style={{ marginBottom: 16 }} onClick={() => setCreating(true)}>
                    <ChipIcon icon={Icons.plus} /> New plan
                </button>
            )}

            <div className="card">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th><th>Price</th><th>Email limit</th><th>Resume limit</th>
                            <th>Daily apps</th><th>Subscribers</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {plans.map((p) => (
                            <tr key={p.id}>
                                <td>{p.name}</td>
                                <td>${p.price}/{p.billing_interval === 'monthly' ? 'mo' : 'yr'}</td>
                                <td>{p.email_limit ?? 'Unlimited'}</td>
                                <td>{p.resume_limit ?? 'Unlimited'}</td>
                                <td>{p.daily_application_limit ?? 'Unlimited'}</td>
                                <td>{p.subscriptions_count}</td>
                                <td><Badge status={p.is_active ? 'sent' : 'failed'}>{p.is_active ? 'Active' : 'Disabled'}</Badge></td>
                                <td style={{ display: 'flex', gap: 6 }}>
                                    <button className="btn btn-ghost btn-sm" onClick={() => setEditing(p)}>Edit</button>
                                    <button className="btn btn-ghost btn-sm" onClick={() => toggleActive(p)}>{p.is_active ? 'Disable' : 'Enable'}</button>
                                    <button className="btn btn-danger btn-sm" onClick={() => destroy(p)}>Delete</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </>
    );
}

AdminPlansIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
