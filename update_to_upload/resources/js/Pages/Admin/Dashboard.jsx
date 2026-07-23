import React from 'react';
import { PageHead, Stat, Icons } from '../../components';
import AdminLayout from '../../AdminLayout';

export default function AdminDashboard({ userCounts, applicationCounts, emailSuccessRate, totalResumes, queueCounts }) {
    return (
        <>
            <PageHead title="Admin Dashboard" subtitle="Platform-wide overview across all users." />

            <div className="stats">
                <Stat label="Total Users" value={userCounts.total} icon={Icons.user} accent="primary" />
                <Stat label="Active Users" value={userCounts.active} icon={Icons.check} accent="green" />
                <Stat label="New Users Today" value={userCounts.new_today} icon={Icons.sparkle} accent="violet" />
                <Stat label="Total Applications" value={applicationCounts.total} icon={Icons.briefcase} accent="primary" />
                <Stat label="Pending Applications" value={applicationCounts.pending} icon={Icons.clock} accent="amber" />
                <Stat label="Sent Applications" value={applicationCounts.sent} icon={Icons.send} accent="green" />
                <Stat label="Failed Applications" value={applicationCounts.failed} icon={Icons.alert} accent="red" />
                <Stat label="Email Success Rate" value={`${emailSuccessRate}%`} icon={Icons.rate} accent="green" />
                <Stat label="Total Resumes Uploaded" value={totalResumes} icon={Icons.upload} accent="blue" />
                <Stat label="Active Queue Jobs" value={queueCounts.active} icon={Icons.clock} accent="sky" />
                <Stat label="Failed Queue Jobs" value={queueCounts.failed} icon={Icons.alert} accent="red" />
            </div>
        </>
    );
}

AdminDashboard.layout = (page) => <AdminLayout>{page}</AdminLayout>;
