import React from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function TwoFactorChallenge({ errors = {} }) {
    const { data, setData, post, processing } = useForm({ code: '' });

    const submit = (e) => {
        e.preventDefault();
        post('/2fa-challenge');
    };

    return (
        <div className="auth-wrap">
            <Head title="Two-Factor Authentication" />

            <div className="auth-hero">
                <div className="brand"><span className="dot">B</span> BulkApply</div>
                <div className="hero-body">
                    <h2>Almost there.</h2>
                    <p className="lead">Enter the code from your authenticator app to finish signing in.</p>
                </div>
            </div>

            <div className="auth-form-side">
                <div className="auth-card">
                    <div className="auth-mobile-brand"><span className="dot">B</span> BulkApply</div>
                    <h1 className="title">Two-factor authentication</h1>
                    <p className="sub">Enter your 6-digit code, or a recovery code.</p>

                    {errors.code && <div className="alert alert-error">{errors.code}</div>}
                    <form onSubmit={submit}>
                        <label htmlFor="code">Authentication code</label>
                        <input id="code" type="text" autoFocus value={data.code}
                            onChange={(e) => setData('code', e.target.value)} placeholder="123456" required />

                        <button type="submit" className="btn btn-primary btn-block" style={{ marginTop: 16 }} disabled={processing}>
                            {processing ? 'Verifying…' : 'Verify'}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    );
}

TwoFactorChallenge.layout = (page) => page;
