import React from 'react';
import { PageHead, Stat, Badge, Icons } from '../../../components';
import AdminLayout from '../../../AdminLayout';

export default function AdminMonitoringIndex({ cpuLoad, memory, disk, database, queue, redis }) {
    return (
        <>
            <PageHead title="Monitoring" subtitle="Server, database, and queue health." />

            <div className="stats">
                <Stat label="CPU Load (1m)" value={cpuLoad ? cpuLoad['1min'] : 'N/A'} icon={Icons.target} accent="primary" />
                <Stat label="Memory Used" value={memory ? `${memory.used_percent}%` : 'N/A'} icon={Icons.building} accent="blue" />
                <Stat label="Disk Free" value={`${disk.free_gb} GB`} icon={Icons.save} accent="green" />
                <Stat label="Queue Pending" value={queue.pending} icon={Icons.clock} accent="amber" />
            </div>

            <div className="card">
                <h2>Database</h2>
                <p><Badge status={database.connected ? 'sent' : 'failed'}>{database.connected ? 'Connected' : 'Disconnected'}</Badge> Driver: {database.driver}</p>
                {database.error && <p className="muted">{database.error}</p>}
            </div>

            <div className="card">
                <h2>Queue</h2>
                <p>Connection: <code>{queue.connection}</code></p>
                <p>Pending jobs: {queue.pending} · Failed jobs: {queue.failed}</p>
            </div>

            <div className="card">
                <h2>Redis</h2>
                <p className="muted">{redis}</p>
            </div>

            {memory && (
                <div className="card">
                    <h2>Memory</h2>
                    <p>{memory.available_mb} MB available of {memory.total_mb} MB total ({memory.used_percent}% used)</p>
                </div>
            )}

            {cpuLoad && (
                <div className="card">
                    <h2>CPU Load Average</h2>
                    <p>1 min: {cpuLoad['1min']} · 5 min: {cpuLoad['5min']} · 15 min: {cpuLoad['15min']}</p>
                </div>
            )}
        </>
    );
}

AdminMonitoringIndex.layout = (page) => <AdminLayout>{page}</AdminLayout>;
