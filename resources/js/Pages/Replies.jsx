import React, { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { PageHead, Stat, Icons, Spinner, EmptyState, ChipIcon } from '../components';

/** Deep-link into the exact message in Gmail, using the RFC822 Message-ID search. */
function gmailLink(messageId, mailFrom) {
    if (!messageId) return null;
    const id = messageId.replace(/^<|>$/g, '');
    const account = mailFrom ? `?authuser=${encodeURIComponent(mailFrom)}` : '';
    return `https://mail.google.com/mail/u/0/${account}#search/rfc822msgid:${encodeURIComponent(id)}`;
}

function ReplyItem({ reply, onRead, mailFrom }) {
    const link = gmailLink(reply.message_id, mailFrom);

    return (
        <div className={`reply-item${reply.is_read ? '' : ' unread'}`}
            onClick={() => !reply.is_read && onRead(reply.id)}
            role={reply.is_read ? undefined : 'button'}>
            <span className="co-avatar">{(reply.company || reply.from_name || reply.from_email)[0].toUpperCase()}</span>

            <div className="reply-body">
                <div className="reply-top">
                    <span className="reply-from">{reply.company || reply.from_name || reply.from_email}</span>
                    {!reply.is_read && <span className="reply-new">NEW</span>}
                    {reply.job_title && <span className="muted reply-role">· {reply.job_title}</span>}
                    <span className="reply-time" title={reply.received_on}>{reply.received_at}</span>
                </div>

                <div className="reply-subject">{reply.subject || '(no subject)'}</div>
                {reply.snippet && <div className="reply-snippet">{reply.snippet}</div>}

                <div className="reply-meta">
                    <span className="muted">{reply.from_email}</span>
                    {reply.job_id
                        ? <Link href={`/jobs?search=${encodeURIComponent(reply.company || '')}`} onClick={(e) => e.stopPropagation()}>
                            View application
                          </Link>
                        : <span className="muted" title="No sent application matches this sender's address">Not matched to an application</span>}
                    {link && (
                        <a href={link} target="_blank" rel="noopener noreferrer" onClick={(e) => e.stopPropagation()}>
                            Open in Gmail ↗
                        </a>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function Replies({ replies, filters, gmailConnected, counts }) {
    const { props } = usePage();
    const flash = props.flash || {};
    const [syncing, setSyncing] = useState(false);
    const [search, setSearch] = useState(filters.search || '');
    const [readIds, setReadIds] = useState(() => new Set());

    const sync = () => {
        setSyncing(true);
        router.post('/gmail/sync', {}, { preserveScroll: true, onFinish: () => setSyncing(false) });
    };

    const markRead = (id) => {
        setReadIds((prev) => new Set(prev).add(id));
        router.post(`/gmail/replies/${id}/read`, {}, { preserveScroll: true, preserveState: true });
    };

    const markAllRead = () => router.post('/gmail/replies/mark-all-read', {}, { preserveScroll: true });

    const applyFilters = (next) => router.get('/replies', { ...filters, ...next }, { preserveState: true, preserveScroll: true });

    // Reflect the click immediately without waiting for a round trip.
    const list = replies.map((r) => (readIds.has(r.id) ? { ...r, is_read: true } : r));

    return (
        <>
            <PageHead title="Company Replies"
                subtitle="Every email recruiters sent back, matched to the application it answers." />

            {flash.gmail_status && <div className="alert alert-success"><div className="alert-body">{flash.gmail_status}</div></div>}
            {flash.gmail_error && <div className="alert alert-error"><div className="alert-body">{flash.gmail_error}</div></div>}

            {!gmailConnected ? (
                <div className="card">
                    <EmptyState icon="mail" title="Gmail isn't connected">
                        Add your Gmail address and App Password in <Link href="/profile">Settings → Email Sending</Link> to
                        pull recruiter replies in here.
                    </EmptyState>
                </div>
            ) : (
                <>
                    <div className="stats">
                        <Stat label="Replies" value={counts.total} accent="primary" icon={Icons.chat} />
                        <Stat label="Unread" value={counts.unread} accent="amber" icon={Icons.bell} />
                        <Stat label="Matched to an application" value={counts.matched} accent="green" icon={Icons.check} />
                    </div>

                    <div className="card">
                        <div className="toolbar">
                            <input type="text" placeholder="Search sender, subject or text…" style={{ flex: 1, minWidth: 200 }}
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                onKeyDown={(e) => e.key === 'Enter' && applyFilters({ search })} />
                            <button className={`btn btn-sm ${filters.unread ? 'btn-primary' : 'btn-ghost'}`}
                                onClick={() => applyFilters({ unread: !filters.unread })}>
                                Unread only
                            </button>
                            <div className="spacer" />
                            {counts.unread > 0 && (
                                <button className="btn-link" onClick={markAllRead}>Mark all read</button>
                            )}
                            <button className="btn btn-primary btn-sm" onClick={sync} disabled={syncing}>
                                {syncing ? <><Spinner /> Syncing…</> : <><ChipIcon icon={Icons.mail} /> Sync Gmail</>}
                            </button>
                        </div>

                        {list.length === 0 ? (
                            <EmptyState icon="chat" title={filters.search || filters.unread ? 'Nothing matches that filter' : 'No replies yet'}>
                                {filters.search || filters.unread
                                    ? 'Clear the filters to see every reply.'
                                    : <>Replies are pulled from the inbox of the Gmail account you send from. Hit <strong>Sync Gmail</strong> after a recruiter answers.</>}
                            </EmptyState>
                        ) : (
                            <div className="reply-list">
                                {list.map((r) => (
                                    <ReplyItem key={r.id} reply={r} onRead={markRead} mailFrom={props.mailFrom} />
                                ))}
                            </div>
                        )}
                    </div>
                </>
            )}
        </>
    );
}
