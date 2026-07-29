import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { PageHead, ChipIcon, Icons, UpiPaymentModal, formatDuration } from '../components';

function PlanCard({ plan, isCurrent, isPending, onPay, currencySymbol }) {
    return (
        <div className={`card plan-card${isCurrent ? ' plan-card-current' : ''}`}>
            {isCurrent && <span className="plan-card-badge">Current plan</span>}
            <div className="plan-card-name">{plan.name}</div>
            <div className="plan-card-price">
                {currencySymbol}{plan.price}
                <span> / {formatDuration(plan.duration_days)}</span>
            </div>

            <ul className="plan-card-features">
                <li><span className="tick"><ChipIcon icon={Icons.check} /></span> Full access to every feature</li>
                <li><span className="tick"><ChipIcon icon={Icons.check} /></span> Unlimited emails &amp; applications</li>
                <li><span className="tick"><ChipIcon icon={Icons.check} /></span> Unlimited resumes</li>
            </ul>

            {isCurrent ? (
                <button type="button" className="btn btn-ghost btn-block btn-lg" disabled>You're on this plan</button>
            ) : isPending ? (
                <button type="button" className="btn btn-ghost btn-block btn-lg" disabled>Payment pending verification</button>
            ) : (
                <button type="button" className="btn btn-primary btn-block btn-lg" onClick={() => onPay(plan)}>
                    Pay via UPI
                </button>
            )}
        </div>
    );
}

export default function Billing({ plans, currentPlanId, subscription, upiId, upiPayeeName, pendingPlanIds }) {
    const { props } = usePage();
    const trial = props.trial;
    const flash = props.flash || {};
    const currencySymbol = props.currencySymbol || '₹';
    const [payingPlan, setPayingPlan] = useState(null);
    const pending = new Set(pendingPlanIds || []);

    let statusVariant = 'pending', statusLabel = 'No active plan';
    if (currentPlanId) { statusVariant = 'sent'; statusLabel = 'Active'; }
    else if (trial && !trial.expired) { statusVariant = 'amber'; statusLabel = `Trial · ${trial.days_left}d left`; }

    return (
        <>
            <PageHead title="Billing & Plans" subtitle="See your current plan and pay for an upgrade via UPI." />

            {flash.status && (
                <div className="alert alert-success"><div className="alert-body">{flash.status}</div></div>
            )}
            {flash.error && (
                <div className="alert alert-error"><div className="alert-body">{flash.error}</div></div>
            )}

            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.tag} /></span>
                    <div>
                        <h2 style={{ margin: 0 }}>Your subscription</h2>
                    </div>
                    <span className={`badge ${statusVariant} hero-card-status`}>{statusLabel}</span>
                </div>

                {currentPlanId ? (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You're subscribed to the plan below
                        {subscription?.ends_at && <> · renews/ends on <strong>{subscription.ends_at}</strong></>}.
                    </p>
                ) : trial && !trial.expired ? (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You're on the free trial — <strong>{trial.days_left} day{trial.days_left === 1 ? '' : 's'} left</strong>.
                        Pick a plan below and pay via UPI any time.
                    </p>
                ) : (
                    <p className="hint" style={{ marginBottom: 0 }}>
                        You don't have an active plan yet. Pick one below and pay via UPI — an admin will verify and activate it for you.
                    </p>
                )}
            </div>

            <div className="plan-grid">
                {plans.map((plan) => (
                    <PlanCard
                        key={plan.id}
                        plan={plan}
                        isCurrent={plan.id === currentPlanId}
                        isPending={pending.has(plan.id)}
                        onPay={setPayingPlan}
                        currencySymbol={currencySymbol}
                    />
                ))}
            </div>

            {plans.length === 0 && (
                <div className="card empty">
                    <div className="empty-title">No plans available yet</div>
                    <div className="empty-sub">Check back soon, or contact support if you need an account upgrade.</div>
                </div>
            )}

            {payingPlan && (
                <UpiPaymentModal
                    plan={payingPlan}
                    upiId={upiId}
                    upiPayeeName={upiPayeeName}
                    currencySymbol={currencySymbol}
                    onClose={() => setPayingPlan(null)}
                />
            )}
        </>
    );
}
