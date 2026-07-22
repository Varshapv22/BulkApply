import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { PageHead } from '../../../components';
import AdminLayout from '../../../AdminLayout';

const GROUP_LABELS = { general: 'General', auth: 'Authentication', uploads: 'Uploads' };

function SettingField({ setting }) {
    const [value, setValue] = useState(setting.value ?? '');

    const save = (v) => router.post(`/admin/settings/${setting.id}`, { value: v }, { preserveScroll: true });

    if (setting.type === 'boolean') {
        const checked = setting.value === '1' || setting.value === 1;
        return (
            <label style={{ display: 'flex', alignItems: 'center', gap: 10, padding: '10px 0' }}>
                <input type="checkbox" defaultChecked={checked} onChange={(e) => save(e.target.checked ? '1' : '0')} />
                <div>
                    <div>{setting.label}</div>
                </div>
            </label>
        );
    }

    return (
        <div style={{ padding: '10px 0' }}>
            <label style={{ display: 'block', marginBottom: 6, fontSize: 13, fontWeight: 600 }}>{setting.label}</label>
            <div style={{ display: 'flex', gap: 8 }}>
                <input
                    type={setting.type === 'integer' ? 'number' : 'text'}
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    style={{ maxWidth: 320 }}
                />
                <button className="btn btn-primary btn-sm" onClick={() => save(value)}>Save</button>
            </div>
        </div>
    );
}

export default function AdminSettingsIndex({ settings }) {
    return (
        <>
            <PageHead title="Settings" subtitle="System-wide configuration." />

            {Object.entries(settings).map(([group, items]) => (
                <div className="card" key={group}>
                    <h2>{GROUP_LABELS[group] || group}</h2>
                    {items.map((s) => <SettingField key={s.id} setting={s} />)}
                </div>
            ))}
        </>
    );
}

AdminSettingsIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
