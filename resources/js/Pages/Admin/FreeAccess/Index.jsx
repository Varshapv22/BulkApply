import React, { useEffect, useRef, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageHead, Badge, IconField, Icons, formatDuration, useConfirm } from '../../../components';
import AdminLayout from '../../../AdminLayout';

function UserPicker({ selected, onSelect }) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState([]);
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const boxRef = useRef(null);

    useEffect(() => {
        if (selected) return;
        if (query.trim().length < 2) {
            setResults([]);
            return;
        }
        setLoading(true);
        const handle = setTimeout(() => {
            fetch(`/admin/free-access/search-users?q=${encodeURIComponent(query.trim())}`, {
                headers: { Accept: 'application/json' },
            })
                .then((r) => r.json())
                .then((data) => { setResults(data); setOpen(true); })
                .finally(() => setLoading(false));
        }, 300);
        return () => clearTimeout(handle);
    }, [query, selected]);

    useEffect(() => {
        const onClickOutside = (e) => {
            if (boxRef.current && !boxRef.current.contains(e.target)) setOpen(false);
        };
        document.addEventListener('mousedown', onClickOutside);
        return () => document.removeEventListener('mousedown', onClickOutside);
    }, []);

    if (selected) {
        return (
            <div className="user-picker-selected" style={{ display: 'flex', alignItems: 'center', gap: 8, border: '1px solid var(--border)', borderRadius: 8, padding: '8px 10px' }}>
                <div style={{ flex: 1 }}>
                    <div>{selected.name}</div>
                    <div className="muted" style={{ fontSize: 12 }}>{selected.email}</div>
                </div>
                <button type="button" className="btn btn-ghost btn-sm" onClick={() => { onSelect(null); setQuery(''); }}>Change</button>
            </div>
        );
    }

    return (
        <div ref={boxRef} style={{ position: 'relative' }}>
            <IconField
                icon={Icons.search}
                type="text"
                placeholder="Search user by name or email…"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                onFocus={() => results.length && setOpen(true)}
            />
            {open && (
                <div className="dropdown-panel" style={{ position: 'absolute', top: '100%', left: 0, right: 0, zIndex: 20, background: 'var(--surface)', border: '1px solid var(--border)', borderRadius: 8, marginTop: 4, maxHeight: 240, overflowY: 'auto', boxShadow: '0 8px 24px rgba(0,0,0,0.12)' }}>
                    {loading && <div style={{ padding: 10 }} className="muted">Searching…</div>}
                    {!loading && results.length === 0 && query.trim().length >= 2 && (
                        <div style={{ padding: 10 }} className="muted">No users found.</div>
                    )}
                    {!loading && results.map((u) => (
                        <div
                            key={u.id}
                            style={{ padding: '8px 10px', cursor: 'pointer' }}
                            onMouseDown={() => { onSelect(u); setOpen(false); }}
                            className="dropdown-item"
                        >
                            <div>{u.name}</div>
                            <div className="muted" style={{ fontSize: 12 }}>{u.email}</div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function AdminFreeAccessIndex({ grants, plans, filters }) {
    const { props } = usePage();
    const currencySymbol = props.currencySymbol || '₹';

    const [f, setF] = useState({
        search: filters.search || '',
        status: filters.status || '',
    });

    const applyFilters = (next) => {
        const merged = { ...f, ...next };
        setF(merged);
        router.get('/admin/free-access', merged, { preserveState: true, replace: true });
    };

    const [selectedUser, setSelectedUser] = useState(null);
    const [planId, setPlanId] = useState(plans[0]?.id || '');
    const [busy, setBusy] = useState(false);
    const { confirm, dialog } = useConfirm();

    const grant = (e) => {
        e.preventDefault();
        if (!selectedUser || !planId) return;
        setBusy(true);
        router.post('/admin/free-access', { user_id: selectedUser.id, plan_id: planId }, {
            preserveScroll: true,
            onSuccess: () => { setSelectedUser(null); },
            onFinish: () => setBusy(false),
        });
    };

    const revoke = async (grantRow) => {
        const ok = await confirm({
            title: 'Revoke free access?',
            message: `"${grantRow.plan?.name}" access for ${grantRow.user?.name} will be cancelled immediately.`,
            confirmLabel: 'Revoke',
        });
        if (!ok) return;
        router.delete(`/admin/free-access/${grantRow.id}`, { preserveScroll: true });
    };

    return (
        <>
            <PageHead title="Free Access" subtitle="Grant any user free plan access, and revoke it at any time." />

            <div className="card card-pad-sm">
                <h3 style={{ marginTop: 0 }}>Grant free access</h3>
                <form onSubmit={grant} style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'flex-end' }}>
                    <div style={{ minWidth: 280, flex: 1 }}>
                        <label style={{ display: 'block', fontSize: 12, marginBottom: 4 }} className="muted">User</label>
                        <UserPicker selected={selectedUser} onSelect={setSelectedUser} />
                    </div>
                    <div style={{ minWidth: 220 }}>
                        <label style={{ display: 'block', fontSize: 12, marginBottom: 4 }} className="muted">Plan</label>
                        <select value={planId} onChange={(e) => setPlanId(e.target.value)} style={{ width: '100%' }}>
                            {plans.map((p) => (
                                <option key={p.id} value={p.id}>
                                    {p.name} ({currencySymbol}{p.price} / {formatDuration(p.duration_days)})
                                </option>
                            ))}
                        </select>
                    </div>
                    <button type="submit" className="btn btn-primary" disabled={busy || !selectedUser || !planId}>
                        {busy ? 'Granting…' : 'Grant Free Access'}
                    </button>
                </form>
            </div>

            <div className="card card-pad-sm">
                <div style={{ display: 'flex', gap: 10, flexWrap: 'wrap', alignItems: 'center' }}>
                    <IconField
                        icon={Icons.search}
                        type="text"
                        placeholder="Search user name or email…"
                        value={f.search}
                        onChange={(e) => applyFilters({ search: e.target.value })}
                        style={{ minWidth: 220 }}
                    />
                    <select value={f.status} onChange={(e) => applyFilters({ status: e.target.value })} style={{ width: 'auto' }}>
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>

            <div className="card">
                <div className="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>User</th><th>Plan</th><th>Granted By</th><th>Status</th><th>Starts</th><th>Ends</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {grants.data.map((g) => (
                                <tr key={g.id}>
                                    <td>
                                        {g.user ? <Link href={`/admin/users/${g.user.id}`}>{g.user.name}</Link> : '—'}
                                        <div className="muted" style={{ fontSize: 12 }}>{g.user?.email}</div>
                                    </td>
                                    <td>{g.plan ? `${g.plan.name} (${currencySymbol}${g.plan.price} / ${formatDuration(g.plan.duration_days)})` : '—'}</td>
                                    <td>{g.granted_by?.name || '—'}</td>
                                    <td>
                                        <Badge status={g.status === 'active' ? 'sent' : g.status === 'cancelled' ? 'failed' : 'neutral'}>
                                            {g.status}
                                        </Badge>
                                    </td>
                                    <td>{g.starts_at ? new Date(g.starts_at).toLocaleDateString() : '—'}</td>
                                    <td>{g.ends_at ? new Date(g.ends_at).toLocaleDateString() : '—'}</td>
                                    <td>
                                        {g.status === 'active' && (
                                            <button className="btn btn-danger btn-sm" onClick={() => revoke(g)}>Revoke</button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {grants.data.length === 0 && (
                                <tr><td colSpan={7} className="empty">No free-access grants match these filters.</td></tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {grants.links && grants.links.length > 3 && (
                    <div style={{ display: 'flex', gap: 6, marginTop: 16, flexWrap: 'wrap' }}>
                        {grants.links.map((link, i) => (
                            <button
                                key={i}
                                className={`btn btn-sm ${link.active ? 'btn-primary' : 'btn-ghost'}`}
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {dialog}
        </>
    );
}

AdminFreeAccessIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
