import React, { useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { PageHead, IconField, Icons, ChipIcon } from '../components';

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
}

const PLACEHOLDERS = ['{job_title}', '{company}', '{recruiter_name}', '{location}', '{job_url}', '{your_name}'];

export default function Profile({ profile, jobSites, defaultBody }) {
    const { data, setData, post, processing } = useForm({
        full_name: profile.full_name || '',
        email: profile.email || '',
        phone: profile.phone || '',
        location: profile.location || '',
        preferred_role: profile.preferred_role || '',
        preferred_sites: profile.preferred_sites || [],
        skills: profile.skills || '',
        email_subject: profile.email_subject || 'Application for {job_title} at {company}',
        email_body: profile.email_body || defaultBody || '',
        send_start_hour: profile.send_start_hour ?? '',
        send_end_hour: profile.send_end_hour ?? '',
        send_weekdays_only: !!profile.send_weekdays_only,
        max_emails_per_hour: profile.max_emails_per_hour ?? 0,
        followup_days: profile.followup_days ?? 0,
        webhook_url: profile.webhook_url || '',
        mail_username: profile.mail_username || '',
        mail_password: '',
        mail_from_name: profile.mail_from_name || '',
        mail_disconnect: false,
        resume: null,
        cover_letter: null,
    });

    const [parseStatus, setParseStatus] = useState('');
    const [parsing, setParsing] = useState(false);

    const toggleSite = (key) => {
        setData('preferred_sites', data.preferred_sites.includes(key)
            ? data.preferred_sites.filter((s) => s !== key)
            : [...data.preferred_sites, key]);
    };

    const submit = (e) => {
        e.preventDefault();
        post('/profile', { forceFormData: true, preserveScroll: true });
    };

    const parseResume = () => {
        if (!data.resume) { setParseStatus('Please select a resume file first.'); return; }
        const fd = new FormData();
        fd.append('resume', data.resume);
        setParsing(true);
        setParseStatus('Parsing…');
        fetch('/profile/parse-resume', {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': getCookie('XSRF-TOKEN'), Accept: 'application/json' },
            body: fd,
        })
            .then((r) => r.json())
            .then((res) => {
                const filled = [];
                const next = {};
                if (res.name && !data.full_name) { next.full_name = res.name; filled.push('name'); }
                if (res.email && !data.email) { next.email = res.email; filled.push('email'); }
                if (res.phone && !data.phone) { next.phone = res.phone; filled.push('phone'); }
                if (res.skills && !data.skills) { next.skills = res.skills; filled.push('skills'); }
                setData((d) => ({ ...d, ...next }));
                setParseStatus(filled.length ? `Extracted: ${filled.join(', ')}` : 'Could not extract info from this file.');
                setParsing(false);
            })
            .catch(() => { setParseStatus('Parsing failed.'); setParsing(false); });
    };

    return (
        <form onSubmit={submit}>
            <PageHead title="Profile & Settings"
                subtitle="Your resume, cover letter, email template, and sending settings." />

            <div className="card">
                <h2>Your details</h2>
                <p className="hint">Used to fill <code>{'{your_name}'}</code> in the template and as the reply-to address.</p>
                <div className="row">
                    <div>
                        <label>Full name</label>
                        <input type="text" value={data.full_name} onChange={(e) => setData('full_name', e.target.value)} />
                    </div>
                    <div>
                        <label>Your email (reply-to)</label>
                        <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="tel" value={data.phone} onChange={(e) => setData('phone', e.target.value)} />
                    </div>
                </div>
                <div className="row">
                    <div>
                        <label>Location</label>
                        <input type="text" value={data.location} onChange={(e) => setData('location', e.target.value)} placeholder="e.g. New York, NY" />
                    </div>
                    <div>
                        <label>Preferred Job Role</label>
                        <input type="text" value={data.preferred_role} onChange={(e) => setData('preferred_role', e.target.value)} placeholder="e.g. Software Engineer" />
                    </div>
                </div>
                <label style={{ marginTop: 12 }}>Preferred Job Sites</label>
                <div className="chip-group">
                    {Object.entries(jobSites).map(([key, name]) => (
                        <label key={key} className={`chip${data.preferred_sites.includes(key) ? ' checked' : ''}`}>
                            <input type="checkbox" checked={data.preferred_sites.includes(key)} onChange={() => toggleSite(key)} />
                            {name}
                        </label>
                    ))}
                </div>

                <label style={{ marginTop: 12 }}>Your Skills</label>
                <textarea value={data.skills} onChange={(e) => setData('skills', e.target.value)}
                    placeholder="e.g. PHP, Laravel, MySQL, REST API, Git" style={{ minHeight: 70 }} />
                <p className="hint" style={{ margin: '6px 0 0' }}>
                    Comma-separated. On <Link href="/search">Find Jobs</Link>, each result shows a Skills column —
                    skills you list here are highlighted so you can spot the best-fit jobs at a glance.
                </p>
            </div>

            <div className="card">
                <h2>Documents</h2>
                <p className="hint">PDF, DOC or DOCX, up to 10 MB each. These are attached to every application email.</p>
                <div className="row">
                    <div>
                        <label>Resume</label>
                        <input type="file" accept=".pdf,.doc,.docx" onChange={(e) => setData('resume', e.target.files[0])} />
                        {profile.resume_name && <p className="muted" style={{ fontSize: 12, margin: '6px 0 0' }}>Current: {profile.resume_name}</p>}
                    </div>
                    <div>
                        <label>Cover letter</label>
                        <input type="file" accept=".pdf,.doc,.docx" onChange={(e) => setData('cover_letter', e.target.files[0])} />
                        {profile.cover_letter_name && <p className="muted" style={{ fontSize: 12, margin: '6px 0 0' }}>Current: {profile.cover_letter_name}</p>}
                    </div>
                </div>
                <div style={{ marginTop: 12 }}>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={parseResume} disabled={parsing}>Auto-fill from resume</button>
                    <span className="muted" style={{ fontSize: 12, marginLeft: 8 }}>{parseStatus}</span>
                </div>
            </div>

            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.mail} /></span>
                    <div>
                        <h2 style={{ margin: 0 }}>Email Sending</h2>
                        <p className="hint" style={{ margin: '3px 0 0' }}>
                            Applications send through YOUR OWN Gmail — never a shared account. Your App Password
                            is encrypted and only ever used to send on your behalf.
                        </p>
                    </div>
                </div>

                {profile.mail_connected && !data.mail_disconnect ? (
                    <div className="mail-status mail-status-connected">
                        <ChipIcon icon={Icons.check} />
                        Connected as <strong>{profile.mail_username}</strong>
                        <button type="button" className="btn-link" style={{ marginLeft: 'auto', color: 'var(--red)' }}
                            onClick={() => setData('mail_disconnect', true)}>
                            Disconnect
                        </button>
                    </div>
                ) : (
                    <>
                        {data.mail_disconnect ? (
                            <div className="mail-status mail-status-pending">
                                Will disconnect <strong>{profile.mail_username}</strong> when you save.
                                <button type="button" className="btn-link" style={{ marginLeft: 'auto' }}
                                    onClick={() => setData('mail_disconnect', false)}>
                                    Cancel
                                </button>
                            </div>
                        ) : (
                            <div className="mail-status mail-status-off">
                                <ChipIcon icon={Icons.alert} />
                                Not connected — applications can't be emailed until you connect an account below.
                            </div>
                        )}

                        <div className="row" style={{ marginTop: 14 }}>
                            <IconField icon={Icons.mail} type="email" placeholder="yourname@gmail.com"
                                value={data.mail_username} onChange={(e) => setData('mail_username', e.target.value)} />
                            <IconField icon={Icons.tag} type="password" autoComplete="new-password"
                                placeholder="16-character App Password"
                                value={data.mail_password} onChange={(e) => setData('mail_password', e.target.value)} />
                        </div>
                        <label>Sender name <span className="muted" style={{ fontWeight: 400 }}>(optional — shown to recruiters)</span></label>
                        <input type="text" value={data.mail_from_name} onChange={(e) => setData('mail_from_name', e.target.value)}
                            placeholder={data.full_name || 'e.g. Varsha PV'} />

                        <details className="mail-howto">
                            <summary>How do I get an App Password?</summary>
                            <ol>
                                <li>Turn on <strong>2-Step Verification</strong> at <code>myaccount.google.com/security</code>.</li>
                                <li>Go to <code>myaccount.google.com/apppasswords</code>.</li>
                                <li>Name it "BulkApply" and click Create.</li>
                                <li>Copy the 16-character password (remove spaces) and paste it above.</li>
                            </ol>
                            <p className="hint" style={{ margin: 0 }}>
                                This is a Google-generated password just for this app — not your real Gmail password.
                                It's encrypted before being stored and only used to send your own applications.
                            </p>
                        </details>
                    </>
                )}
            </div>

            <div className="card">
                <h2>Default email template</h2>
                <p className="hint">
                    Placeholders get replaced per job: {PLACEHOLDERS.map((p) => <code key={p} style={{ marginRight: 6 }}>{p}</code>)}.
                    You can also create <Link href="/templates">multiple templates</Link>.
                </p>
                <label>Subject</label>
                <input type="text" value={data.email_subject} onChange={(e) => setData('email_subject', e.target.value)} />
                <label>Body</label>
                <textarea rows={10} value={data.email_body} onChange={(e) => setData('email_body', e.target.value)} />
            </div>

            <div className="card">
                <h2>Email Scheduling</h2>
                <p className="hint">Control when emails are sent. Leave hours empty to send anytime.</p>
                <div className="row">
                    <div>
                        <label>Send window start (hour, 0-23)</label>
                        <input type="text" value={data.send_start_hour} onChange={(e) => setData('send_start_hour', e.target.value)} placeholder="e.g. 9" />
                    </div>
                    <div>
                        <label>Send window end (hour, 0-23)</label>
                        <input type="text" value={data.send_end_hour} onChange={(e) => setData('send_end_hour', e.target.value)} placeholder="e.g. 17" />
                    </div>
                </div>
                <label className="inline" style={{ marginTop: 12 }}>
                    <input type="checkbox" checked={data.send_weekdays_only} onChange={(e) => setData('send_weekdays_only', e.target.checked)} />
                    Only send on weekdays (Mon–Fri)
                </label>
            </div>

            <div className="card">
                <h2>Rate Limiting</h2>
                <p className="hint">Limit how many emails are sent per hour to stay within provider limits. Set to 0 for unlimited.</p>
                <div className="row">
                    <div>
                        <label>Max emails per hour</label>
                        <input type="text" value={data.max_emails_per_hour} onChange={(e) => setData('max_emails_per_hour', e.target.value)} placeholder="0 = unlimited" />
                    </div>
                </div>
            </div>

            <div className="card">
                <h2>Follow-up Emails</h2>
                <p className="hint">Automatically send a follow-up email N days after the initial application if no reply. Set to 0 to disable. Requires scheduler: <code>php artisan schedule:work</code></p>
                <div className="row">
                    <div>
                        <label>Follow up after (days)</label>
                        <input type="text" value={data.followup_days} onChange={(e) => setData('followup_days', e.target.value)} placeholder="0 = disabled" />
                    </div>
                </div>
            </div>

            <div className="card">
                <h2>Webhook / Notifications</h2>
                <p className="hint">Receive a POST request to this URL when an email is sent or fails. Payload includes event, company, email, and status.</p>
                <label>Webhook URL</label>
                <input type="url" value={data.webhook_url} onChange={(e) => setData('webhook_url', e.target.value)} placeholder="https://hooks.slack.com/..." />
            </div>

            <button type="submit" className="btn btn-primary" disabled={processing}>
                {processing ? 'Saving…' : 'Save profile & settings'}
            </button>
        </form>
    );
}
