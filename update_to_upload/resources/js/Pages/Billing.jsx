import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { PageHead, ChipIcon, Icons } from '../components';

function Feature({ children }) {
    return <li><span className="tick">✓</span> {children}</li>;
}

function PlanCard({ plan, isCurrent, onRequest, requesting }) {
    return (
        <div className={`card plan-card${isCurrent ? ' plan-card-current' : ''}`}>
            {isCurrent && <span className="plan-card-badge">Current plan</span>}
            <div className="plan-card-name">{plan.name}</div>
            <div className="plan-card-price">
                ${plan.price}
                <span>/{plan.billing_interval === 'monthly' ? 'mo' : 'yr'}</span>
            </div>

            <ul className="plan-card-features">
                <Feature>{plan.email_limit ? `${plan.email_limit} emails` : 'Unlimited emails'}</Feature>
                <Feature>{plan.resume_limit ? `${plan.resume_limit} resumes` : 'Unlimited resumes'}</Feature>
                <Feature>{plan.daily_application_limit ? `${plan.daily_application_limit} applications/day` : 'Unlimited applications/day'}</Feature>
                {plan.chrome_extension_access && <Feature>Chrome extension</Feature>}
                {plan.ats_checker_access && <Feature>ATS resume checker</Feature>}
                {plan.api_access && <Feature>API access</Feature>}
            </ul>

            {isCurrent ? (
                <button type="button" className="btn btn-ghost btn-block" disabled>You're on this plan</button>
            ) : (
                <button
                    type="button"
                    className="btn btn-primary btn-block"
                    disabled={requesting}
                    onClick={() => onRequest(plan)}
                >
                    {requesting ? 'Sending request…' : 'Request this plan'}
                </button>
            )}
        </div>
    );
}

export default function Billing({ plans, currentPlanId, subscription }) {
    const { props } = usePage();
    const trial = props.trial;
    const flash = props.flash || {};
    const [requestingId, setRequestingId] = useState(null);

    const requestUpgrade = (plan) => {
        setRequestingId(plan.id);
        router.post('/billing/request-upgrade', { plan_id: plan.id }, {
            preserveScroll: true,
            onFinish: () => setRequestingId(null),
        });
    };

    return (
        <>
            <PageHead title="Billing & Plans" subtitle="See your current plan and request an upgrade." />

            {flash.status && (
                <div className="alert alert-success"><div className="alert-body">{flash.status}</div></div>
            )}

            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.tag} /></span>
                    <div>
                        <h2 style={{ margin: 0 }}>Your subscription</h2>
                    </div>
                </div>

                {currentPlanId ? (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You're subscribed to the plan below
                        {subscription?.ends_at && <> · renews/ends on <strong>{subscription.ends_at}</strong></>}.
                    </p>
                ) : trial && !trial.expired ? (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You're on the free trial — <strong>{trial.days_left} day{trial.days_left === 1 ? '' : 's'} left</strong>.
                        Pick a plan below and request an upgrade any time.
                    </p>
                ) : (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You don't have an active plan yet. Pick one below and request an upgrade — an admin will activate it for you.
                    </p>
                )}
            </div>

            <div className="plan-grid">
                {plans.map((plan) => (
                    <PlanCard
                        key={plan.id}
                        plan={plan}
                        isCurrent={plan.id === currentPlanId}
                        requesting={requestingId === plan.id}
                        onRequest={requestUpgrade}
                    />
                ))}
            </div>

            {plans.length === 0 && (
                <div className="card empty">
                    <div className="empty-title">No plans available yet</div>
                    <div className="empty-sub">Check back soon, or contact support if you need an account upgrade.</div>
                </div>
            )}
        </>
    );
}
