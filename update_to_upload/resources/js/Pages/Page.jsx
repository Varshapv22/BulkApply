import React from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Page({ title, content }) {
    return (
        <div style={{ maxWidth: 720, margin: '60px auto', padding: '0 20px' }}>
            <Head title={`${title} — BulkApply`} />
            <Link href="/" style={{ fontWeight: 700, fontSize: 20 }}>BulkApply</Link>
            <div className="card" style={{ marginTop: 24 }}>
                <h1>{title}</h1>
                <div style={{ whiteSpace: 'pre-wrap', lineHeight: 1.7 }}>{content}</div>
            </div>
        </div>
    );
}

Page.layout = (page) => page;
