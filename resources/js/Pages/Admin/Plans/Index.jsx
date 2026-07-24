import React, { useState } from 'react';
import { createPortal } from 'react-dom';
import { useForm, router, usePage } from '@inertiajs/react';
import { PageHead, Badge, Icons, ChipIcon, PLAN_DURATIONS, formatDuration } from '../../../components';
import AdminLayout from '../../../AdminLayout';

const EMPTY = { name: '', price: 0, duration_days: 30 };

const isPreset = (days) => PLAN_DURATIONS.some((d) => d.days === days);

function PlanFormModal({ plan, onClose }) {
    const { props } = usePage();
    const currencySymbol = props.currencySymbol || '₹';
    const form = useForm(plan ? { name: plan.name, price: plan.price, duration_days: plan.duration_days } : EMPTY);
    const [customDuration, setCustomDuration] = useState(plan ? !isPreset(plan.duration_days) : false);

    const submit = (e) => {
        e.preventDefault();
        const opts = { preserveScroll: true, onSuccess: onClose };
        if (plan) form.put(`/admin/plans/${plan.id}`, opts);
        else form.post('/admin/plans', { ...opts, onSuccess: () => { form.reset(); onClose(); } });
    };

    const modal = (
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className="modal modal-sm">
                <button type="button" className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <h3 className="modal-title">{plan ? 'Edit plan' : 'New plan'}</h3>

                <form onSubmit={submit}>
                    <div style={{ marginBottom: 14 }}>
                        <label>Plan name</label>
                        <input type="text" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} required />
                        {form.errors.name && <p className="field-error">{form.errors.name}</p>}
                    </div>

                    <div style={{ marginBottom: 14 }}>
                        <label>Price ({currencySymbol})</label>
                        <input type="number" step="0.01" min="0" value={form.data.price} onChange={(e) => form.setData('price', e.target.value)} required />
                        {form.errors.price && <p className="field-error">{form.errors.price}</p>}
                    </div>

                    <div style={{ marginBottom: customDuration ? 14 : 20 }}>
                        <label>Duration</label>
                        <select
                            value={customDuration ? 'custom' : form.data.duration_days}
                            onChange={(e) => {
                                if (e.target.value === 'custom') { setCustomDuration(true); return; }
                                setCustomDuration(false);
                                form.setData('duration_days', Number(e.target.value));
                            }}
                        >
                            {PLAN_DURATIONS.map((d) => <option key={d.days} value={d.days}>{d.label}</option>)}
                            <option value="custom">Custom…</option>
                        </select>
                    </div>

                    {customDuration && (
                        <div style={{ marginBottom: 20 }}>
                            <label>Duration in days</label>
                            <input
                                type="number"
                                min="1"
                                value={form.data.duration_days}
                                onChange={(e) => form.setData('duration_days', Number(e.target.value))}
                                required
                            />
                            {form.errors.duration_days && <p className="field-error">{form.errors.duration_days}</p>}
                        </div>
                    )}

                    <div style={{ display: 'flex', gap: 10, justifyContent: 'flex-end' }}>
                        <button type="button" className="btn btn-ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="btn btn-primary" disabled={form.processing}>
                            {form.processing ? 'Saving…' : plan ? 'Save changes' : 'Create plan'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );

    return createPortal(modal, document.body);
}

export default function AdminPlansIndex({ plans }) {
    const { props } = usePage();
    const currencySymbol = props.currencySymbol || '₹';
    const [editing, setEditing] = useState(null);
    const [creating, setCreating] = useState(false);

    const toggleActive = (plan) => router.post(`/admin/plans/${plan.id}/toggle-active`, {}, { preserveScroll: true });
    const destroy = (plan) => { if (confirm(`Delete plan "${plan.name}"?`)) router.delete(`/admin/plans/${plan.id}`, { preserveScroll: true }); };

    return (
        <>
            <PageHead title="Plans" subtitle="Free trial, 1 Month, 3 Month, and 9 Month plans — priced by duration only." />

            <button className="btn btn-primary btn-sm" style={{ marginBottom: 16 }} onClick={() => setCreating(true)}>
                <ChipIcon icon={Icons.plus} /> New plan
            </button>

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th><th>Price</th><th>Duration</th><th>Subscribers</th><th>Status</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {plans.map((p) => (
                                <tr key={p.id}>
                                    <td>{p.name}</td>
                                    <td>{currencySymbol}{p.price}</td>
                                    <td>{formatDuration(p.duration_days)}</td>
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
            </div>

            {creating && <PlanFormModal onClose={() => setCreating(false)} />}
            {editing && <PlanFormModal plan={editing} onClose={() => setEditing(null)} />}
        </>
    );
}

AdminPlansIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
