import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { PageHead, Stat, Badge, Icons, PasswordInput } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function ConfigRow({ config }) {
    const [value, setValue] = useState('');
    const [editing, setEditing] = useState(false);

    const save = () => {
        router.post(`/admin/api/${config.id}`, { value, active: config.active }, {
            preserveScroll: true,
            onSuccess: () => { setEditing(false); setValue(''); },
        });
    };

    return (
        <tr>
            <td>{config.label}<div className="muted" style={{ fontSize: 12 }}>{config.description}</div></td>
            <td>{config.has_value ? <Badge status="sent">Set</Badge> : <Badge status="pending">Not set</Badge>}</td>
            <td>
                {editing ? (
                    <div style={{ display: 'flex', gap: 6 }}>
                        <PasswordInput placeholder="New value" value={value} onChange={(e) => setValue(e.target.value)} style={{ width: 220 }} />
                        <button className="btn btn-primary btn-sm" onClick={save}>Save</button>
                        <button className="btn btn-ghost btn-sm" onClick={() => setEditing(false)}>Cancel</button>
                    </div>
                ) : (
                    <button className="btn btn-ghost btn-sm" onClick={() => setEditing(true)}>{config.has_value ? 'Change' : 'Set value'}</button>
                )}
            </td>
        </tr>
    );
}

export default function AdminApiIndex({ configs, stats, recentRequests }) {
    return (
        <>
            <PageHead title="API & Integrations" subtitle="Manage API credentials and monitor API request activity." />

            <div className="stats">
                <Stat label="Total API Requests" value={stats.total} icon={Icons.globe} accent="primary" />
                <Stat label="Failed Requests" value={stats.failed} icon={Icons.alert} accent="red" />
                <Stat label="Avg Response Time" value={`${stats.avgDurationMs}ms`} icon={Icons.clock} accent="blue" />
            </div>

            <div className="card">
                <h2>API Configuration</h2>
                <div className="table-wrap">
                    <table>
                        <thead><tr><th>Integration</th><th>Status</th><th></th></tr></thead>
                        <tbody>{configs.map((c) => <ConfigRow key={c.id} config={c} />)}</tbody>
                    </table>
                </div>
            </div>

            <div className="card">
                <h2>Recent API Requests</h2>
                {recentRequests.length === 0 ? <p className="muted">No API requests logged yet.</p> : (
                    <div style={{ overflowX: 'auto' }}>
                        <table>
                            <thead><tr><th>Method</th><th>Endpoint</th><th>Status</th><th>Duration</th><th>When</th></tr></thead>
                            <tbody>
                                {recentRequests.map((r) => (
                                    <tr key={r.id}>
                                        <td>{r.method}</td>
                                        <td>{r.endpoint}</td>
                                        <td><Badge status={r.status >= 400 ? 'failed' : 'sent'}>{r.status}</Badge></td>
                                        <td>{r.duration_ms}ms</td>
                                        <td>{new Date(r.created_at).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </>
    );
}

AdminApiIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
