import React, { useState } from 'react';
import { createPortal } from 'react-dom';
import { Link, useForm } from '@inertiajs/react';
import { PageHead, IconField, Icons, ChipIcon, PasswordInput } from '../components';

function getCookie(name) {
    const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? decodeURIComponent(match[2]) : '';
}

const PLACEHOLDERS = ['{job_title}', '{company}', '{recruiter_name}', '{location}', '{job_url}', '{your_name}'];

function truncate(text, n) {
    if (!text) return '';
    return text.length > n ? text.slice(0, n).trim() + '…' : text;
}

function ModalShell({ title, onClose, children, small, footer }) {
    return createPortal(
        <div className="modal-overlay" onMouseDown={(e) => { if (e.target === e.currentTarget) onClose(); }}>
            <div className={`modal${small ? ' modal-sm' : ''}`}>
                <button type="button" className="modal-close" onClick={onClose} aria-label="Close">✕</button>
                <h3 className="modal-title">{title}</h3>
                {children}
                <div style={{ display: 'flex', justifyContent: 'flex-end', gap: 10, marginTop: 22 }}>
                    {footer || <button type="button" className="btn btn-primary" onClick={onClose}>Done</button>}
                </div>
            </div>
        </div>,
        document.body
    );
}

function DetailsModal({ data, setData, jobSites, photoPreview, onPhotoChange, removePhoto, toggleSite, onClose }) {
    return (
        <ModalShell title="Edit Details" onClose={onClose}>
            <p className="hint">Used to fill <code>{'{your_name}'}</code> in the template and as the reply-to address.</p>

            <div className="row" style={{ alignItems: 'center' }}>
                <div style={{ flex: '0 0 auto' }}>
                    {photoPreview ? (
                        <img src={photoPreview} alt="Profile photo" style={{ width: 64, height: 64, borderRadius: '50%', objectFit: 'cover', display: 'block' }} />
                    ) : (
                        <span className="co-avatar" style={{ width: 64, height: 64, fontSize: 24 }}>{(data.full_name || '?')[0].toUpperCase()}</span>
                    )}
                </div>
                <div style={{ flex: 1 }}>
                    <label>Profile photo</label>
                    <input type="file" accept="image/*" onChange={onPhotoChange} />
                    {photoPreview && (
                        <button type="button" className="btn-link" style={{ fontSize: 13, color: 'var(--red)', marginTop: 4 }} onClick={removePhoto}>
                            Remove photo
                        </button>
                    )}
                </div>
            </div>

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

            <label style={{ marginTop: 12 }}>Bio / professional summary</label>
            <textarea value={data.bio} onChange={(e) => setData('bio', e.target.value)}
                placeholder="A short summary of your background and what you're looking for" style={{ minHeight: 70 }} />

            <div className="row" style={{ marginTop: 12 }}>
                <div>
                    <label>LinkedIn URL</label>
                    <input type="url" value={data.linkedin_url} onChange={(e) => setData('linkedin_url', e.target.value)}
                        placeholder="https://linkedin.com/in/yourname" />
                </div>
                <div>
                    <label>Portfolio / website URL</label>
                    <input type="url" value={data.portfolio_url} onChange={(e) => setData('portfolio_url', e.target.value)}
                        placeholder="https://yourname.dev" />
                </div>
            </div>
        </ModalShell>
    );
}

function ResumeModal({ resumeData, setResumeData, uploadResume, processingResume, parseResume, parsing, parseStatus, onClose }) {
    return (
        <ModalShell
            title="Add Resume"
            onClose={onClose}
            small
            footer={
                <>
                    <button type="button" className="btn btn-ghost" onClick={parseResume} disabled={parsing || !resumeData.resume}>
                        {parsing ? 'Parsing…' : 'Auto-fill Profile'}
                    </button>
                    <button type="button" className="btn btn-primary" onClick={uploadResume} disabled={processingResume || !resumeData.resume}>
                        {processingResume ? 'Uploading…' : 'Upload'}
                    </button>
                </>
            }
        >
            <p className="hint">PDF, DOC or DOCX, up to 10MB.</p>
            <div>
                <label>Resume file</label>
                <input type="file" accept=".pdf,.doc,.docx" onChange={(e) => setResumeData('resume', e.target.files[0])} />
            </div>
            {parseStatus && <p className="hint" style={{ margin: '10px 0 0' }}>{parseStatus}</p>}
        </ModalShell>
    );
}

function EmailModal({ data, setData, profile, onClose }) {
    return (
        <ModalShell title="Email Sending" onClose={onClose}>
            <p className="hint">
                Applications send through YOUR OWN Gmail — never a shared account. Your App Password
                is encrypted and only ever used to send on your behalf.
            </p>

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
                        <PasswordInput icon={Icons.tag} autoComplete="new-password"
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
        </ModalShell>
    );
}

function TemplateModal({ data, setData, onClose }) {
    return (
        <ModalShell title="Default Email Template" onClose={onClose}>
            <p className="hint">
                Placeholders get replaced per job: {PLACEHOLDERS.map((p) => <code key={p} style={{ marginRight: 6 }}>{p}</code>)}.
                You can also create <Link href="/templates">multiple templates</Link>.
            </p>
            <label>Subject</label>
            <input type="text" value={data.email_subject} onChange={(e) => setData('email_subject', e.target.value)} />
            <label>Body</label>
            <textarea rows={10} value={data.email_body} onChange={(e) => setData('email_body', e.target.value)} />
        </ModalShell>
    );
}

function AutomationModal({ data, setData, onClose }) {
    return (
        <ModalShell title="Automation & Notifications" onClose={onClose}>
            <div className="modal-section">
                <h4>Email Scheduling</h4>
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

            <div className="modal-section">
                <h4>Rate Limiting</h4>
                <p className="hint">Limit how many emails are sent per hour to stay within provider limits. Set to 0 for unlimited.</p>
                <label>Max emails per hour</label>
                <input type="text" value={data.max_emails_per_hour} onChange={(e) => setData('max_emails_per_hour', e.target.value)} placeholder="0 = unlimited" />
            </div>

            <div className="modal-section">
                <h4>Follow-up Emails</h4>
                <p className="hint">Automatically send a follow-up email N days after the initial application if no reply. Set to 0 to disable. Requires scheduler: <code>php artisan schedule:work</code></p>
                <label>Follow up after (days)</label>
                <input type="text" value={data.followup_days} onChange={(e) => setData('followup_days', e.target.value)} placeholder="0 = disabled" />
            </div>

            <div className="modal-section">
                <h4>Webhook / Notifications</h4>
                <p className="hint">Receive a POST request to this URL when an email is sent or fails. Payload includes event, company, email, and status.</p>
                <label>Webhook URL</label>
                <input type="url" value={data.webhook_url} onChange={(e) => setData('webhook_url', e.target.value)} placeholder="https://hooks.slack.com/..." />
            </div>
        </ModalShell>
    );
}

export default function Profile({ profile, jobSites, defaultBody, resumes = [] }) {
    const { data, setData, post, processing } = useForm({
        full_name: profile.full_name || '',
        email: profile.email || '',
        phone: profile.phone || '',
        location: profile.location || '',
        preferred_role: profile.preferred_role || '',
        preferred_sites: profile.preferred_sites || [],
        skills: profile.skills || '',
        bio: profile.bio || '',
        linkedin_url: profile.linkedin_url || '',
        portfolio_url: profile.portfolio_url || '',
        photo: null,
        photo_remove: false,
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
        cover_letter: null,
    });

    const { data: resumeData, setData: setResumeData, post: postResume, processing: processingResume, reset: resetResume } = useForm({
        resume: null
    });

    const [parseStatus, setParseStatus] = useState('');
    const [parsing, setParsing] = useState(false);
    const [photoPreview, setPhotoPreview] = useState(profile.photo_url || null);
    const [modal, setModal] = useState(null); // 'details' | 'resume' | 'email' | 'template' | 'automation' | null
    const closeModal = () => setModal(null);

    const onPhotoChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setData((d) => ({ ...d, photo: file, photo_remove: false }));
        setPhotoPreview(URL.createObjectURL(file));
    };

    const removePhoto = () => {
        setData((d) => ({ ...d, photo: null, photo_remove: true }));
        setPhotoPreview(null);
    };

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
        if (!resumeData.resume) { setParseStatus('Please select a file to parse.'); return; }
        const fd = new FormData();
        fd.append('resume', resumeData.resume);
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

    const uploadResume = (e) => {
        e.preventDefault();
        postResume('/resumes', {
            preserveScroll: true,
            onSuccess: () => { resetResume('resume'); setParseStatus(''); closeModal(); },
        });
    };

    const sendingWindowText = (data.send_start_hour !== '' && data.send_end_hour !== '')
        ? `${data.send_start_hour}:00–${data.send_end_hour}:00${data.send_weekdays_only ? ' · weekdays only' : ''}`
        : `Anytime${data.send_weekdays_only ? ' · weekdays only' : ''}`;
    const rateLimitText = Number(data.max_emails_per_hour) > 0 ? `${data.max_emails_per_hour} / hour` : 'Unlimited';
    const followupText = Number(data.followup_days) > 0 ? `After ${data.followup_days} day${data.followup_days == 1 ? '' : 's'}` : 'Disabled';
    const webhookText = data.webhook_url ? 'Configured' : 'Not set';

    return (
        <form onSubmit={submit}>
            <PageHead title="Profile & Settings"
                subtitle="Your resume, cover letter, email template, and sending settings." />

            {/* Your details — summary */}
            <div className="card">
                <div className="card-head-row">
                    <h2>Your details</h2>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => setModal('details')}>Edit Details</button>
                </div>
                <div className="row" style={{ alignItems: 'center' }}>
                    <div style={{ flex: '0 0 auto' }}>
                        {photoPreview ? (
                            <img src={photoPreview} alt="Profile photo" style={{ width: 52, height: 52, borderRadius: '50%', objectFit: 'cover', display: 'block' }} />
                        ) : (
                            <span className="co-avatar" style={{ width: 52, height: 52, fontSize: 19 }}>{(data.full_name || '?')[0].toUpperCase()}</span>
                        )}
                    </div>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <strong style={{ fontSize: 16 }}>{data.full_name || 'Add your name'}</strong>
                        <p className="muted" style={{ margin: '2px 0 0', fontSize: 13 }}>
                            {[data.email, data.phone, data.location].filter(Boolean).join(' · ') || 'Add your contact details'}
                        </p>
                    </div>
                </div>
                {(data.preferred_role || data.preferred_sites.length > 0) && (
                    <p className="hint" style={{ margin: '12px 0 0' }}>
                        {data.preferred_role && <>Looking for <strong>{data.preferred_role}</strong></>}
                        {data.preferred_role && data.preferred_sites.length > 0 && ' · '}
                        {data.preferred_sites.length > 0 && `${data.preferred_sites.length} job site${data.preferred_sites.length > 1 ? 's' : ''} selected`}
                    </p>
                )}
                {data.skills && <p className="hint" style={{ margin: '6px 0 0' }}>Skills: {truncate(data.skills, 90)}</p>}
                {data.bio && <p className="muted" style={{ margin: '6px 0 0', fontSize: 13 }}>{truncate(data.bio, 140)}</p>}
                {(data.linkedin_url || data.portfolio_url) && (
                    <div style={{ display: 'flex', gap: 16, marginTop: 10 }}>
                        {data.linkedin_url && <a href={data.linkedin_url} target="_blank" rel="noopener" className="btn-link" style={{ fontSize: 13 }}>LinkedIn ↗</a>}
                        {data.portfolio_url && <a href={data.portfolio_url} target="_blank" rel="noopener" className="btn-link" style={{ fontSize: 13 }}>Portfolio ↗</a>}
                    </div>
                )}
            </div>

            {/* Resumes */}
            <div className="card">
                <div className="card-head-row">
                    <h2>Resumes</h2>
                    <button type="button" className="btn btn-primary btn-sm" onClick={() => setModal('resume')}>+ Add Resume</button>
                </div>
                <p className="hint">Upload multiple resumes (PDF, DOC/X, max 10MB). Select which one to use when applying.</p>

                {resumes.length > 0 ? (
                    <div className="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th style={{ width: 100 }}>Status</th>
                                    <th style={{ width: 150, textAlign: 'right' }}>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {resumes.map(r => (
                                    <tr key={r.id}>
                                        <td>{r.name}</td>
                                        <td>
                                            {r.is_default ? <span className="badge sent">Default</span> : null}
                                        </td>
                                        <td style={{ textAlign: 'right' }}>
                                            {!r.is_default && (
                                                <Link href={`/resumes/${r.id}/default`} method="post" as="button" className="btn-link" style={{ fontSize: 13, marginRight: 12 }}>Make Default</Link>
                                            )}
                                            <Link href={`/resumes/${r.id}`} method="delete" as="button" className="btn-link" style={{ fontSize: 13, color: 'var(--red)' }}>Delete</Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <p className="muted" style={{ fontSize: 13 }}>No resumes uploaded yet.</p>
                )}
            </div>

            <div className="card">
                <h2>Cover Letter</h2>
                <p className="hint">PDF, DOC or DOCX, up to 10 MB. Attached to applications when specified.</p>
                <div>
                    <label>Cover letter file</label>
                    <input type="file" accept=".pdf,.doc,.docx" onChange={(e) => setData('cover_letter', e.target.files[0])} />
                    {profile.cover_letter_name && <p className="muted" style={{ fontSize: 12, margin: '6px 0 0' }}>Current: {profile.cover_letter_name}</p>}
                </div>
            </div>

            {/* Email Sending — summary */}
            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.mail} /></span>
                    <div style={{ flex: 1, minWidth: 0 }}>
                        <div className="card-head-row">
                            <h2 style={{ margin: 0 }}>Email Sending</h2>
                            <button type="button" className="btn btn-ghost btn-sm" onClick={() => setModal('email')}>
                                {profile.mail_connected && !data.mail_disconnect ? 'Manage' : 'Connect Gmail'}
                            </button>
                        </div>
                        <p className="hint" style={{ margin: '3px 0 0' }}>
                            Applications send through YOUR OWN Gmail — never a shared account.
                        </p>
                    </div>
                </div>

                {profile.mail_connected && !data.mail_disconnect ? (
                    <div className="mail-status mail-status-connected">
                        <ChipIcon icon={Icons.check} />
                        Connected as <strong>{profile.mail_username}</strong>
                    </div>
                ) : data.mail_disconnect ? (
                    <div className="mail-status mail-status-pending">
                        Will disconnect <strong>{profile.mail_username}</strong> when you save.
                    </div>
                ) : (
                    <div className="mail-status mail-status-off">
                        <ChipIcon icon={Icons.alert} />
                        Not connected — applications can't be emailed until you connect an account.
                    </div>
                )}
            </div>

            {/* Default email template — summary */}
            <div className="card">
                <div className="card-head-row">
                    <h2>Default email template</h2>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => setModal('template')}>Edit Template</button>
                </div>
                <p className="hint" style={{ margin: '0 0 4px' }}>Subject: <strong>{data.email_subject || '—'}</strong></p>
                <p className="muted" style={{ fontSize: 13, margin: 0 }}>{truncate(data.email_body, 160) || 'No body set.'}</p>
                <p className="hint" style={{ margin: '10px 0 0' }}>You can also create <Link href="/templates">multiple templates</Link>.</p>
            </div>

            {/* Automation & Notifications — summary */}
            <div className="card">
                <div className="card-head-row">
                    <h2>Automation & Notifications</h2>
                    <button type="button" className="btn btn-ghost btn-sm" onClick={() => setModal('automation')}>Edit Settings</button>
                </div>
                <p className="hint">Sending schedule, rate limits, follow-ups, and webhook notifications.</p>
                <div>
                    <div className="list-row"><span className="lead">Sending window</span><span>{sendingWindowText}</span></div>
                    <div className="list-row"><span className="lead">Rate limit</span><span>{rateLimitText}</span></div>
                    <div className="list-row"><span className="lead">Follow-up emails</span><span>{followupText}</span></div>
                    <div className="list-row"><span className="lead">Webhook</span><span>{webhookText}</span></div>
                </div>
            </div>

            <button type="submit" className="btn btn-primary" disabled={processing}>
                {processing ? 'Saving…' : 'Save profile & settings'}
            </button>

            {modal === 'details' && (
                <DetailsModal data={data} setData={setData} jobSites={jobSites} photoPreview={photoPreview}
                    onPhotoChange={onPhotoChange} removePhoto={removePhoto} toggleSite={toggleSite} onClose={closeModal} />
            )}
            {modal === 'resume' && (
                <ResumeModal resumeData={resumeData} setResumeData={setResumeData} uploadResume={uploadResume}
                    processingResume={processingResume} parseResume={parseResume} parsing={parsing}
                    parseStatus={parseStatus} onClose={closeModal} />
            )}
            {modal === 'email' && (
                <EmailModal data={data} setData={setData} profile={profile} onClose={closeModal} />
            )}
            {modal === 'template' && (
                <TemplateModal data={data} setData={setData} onClose={closeModal} />
            )}
            {modal === 'automation' && (
                <AutomationModal data={data} setData={setData} onClose={closeModal} />
            )}
        </form>
    );
}
