import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { PasswordInput } from '../../components';

function Tick() {
    return (
        <span className="tick">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round"><polyline points="20 6 9 17 4 12" /></svg>
        </span>
    );
}

export default function Login({ errors = {} }) {
    const { data, setData, post, processing } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post('/login');
    };

    return (
        <div className="auth-wrap">
            <Head title="Login" />

            <div className="auth-hero">
                <div className="brand"><span className="dot">B</span> BulkApply</div>
                <div className="hero-body">
                    <h2>Apply to hundreds of jobs, on autopilot.</h2>
                    <p className="lead">Search across Kerala tech parks and the whole web, personalise every email, and track opens &amp; replies — all from one dashboard.</p>
                    <div className="feat"><Tick /> Aggregated search + dedicated park boards</div>
                    <div className="feat"><Tick /> One-click bulk applications with your resume</div>
                    <div className="feat"><Tick /> Open, click &amp; pipeline tracking</div>
                </div>
            </div>

            <div className="auth-form-side">
                <div className="auth-card">
                    <div className="auth-mobile-brand"><span className="dot">B</span> BulkApply</div>
                    <h1 className="title">Welcome back</h1>
                    <p className="sub">Sign in to continue to your dashboard.</p>

                    {errors.email && <div className="alert alert-error">{errors.email}</div>}
                    <form onSubmit={submit}>
                        <label htmlFor="email">Email</label>
                        <input id="email" type="email" autoFocus value={data.email}
                            onChange={(e) => setData('email', e.target.value)} placeholder="you@example.com" required />

                        <label htmlFor="password">Password</label>
                        <PasswordInput id="password" value={data.password}
                            onChange={(e) => setData('password', e.target.value)} placeholder="••••••••" required />

                        <label className="inline" style={{ margin: '16px 0' }}>
                            <input type="checkbox" checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)} />
                            Remember me
                        </label>

                        <button type="submit" className="btn btn-primary btn-block" disabled={processing}>
                            {processing ? 'Signing in…' : 'Sign in'}
                        </button>
                    </form>

                    <p style={{ textAlign: 'center', margin: '20px 0 0', fontSize: 14 }} className="muted">
                        Don't have an account? <Link href="/register">Create one</Link>
                    </p>

                    <p style={{ textAlign: 'center', margin: '14px 0 0', fontSize: 12 }} className="muted">
                        <Link href="/p/pricing">Pricing</Link> · <Link href="/p/faq">FAQ</Link> · <Link href="/p/privacy">Privacy</Link> · <Link href="/p/terms">Terms</Link> · <Link href="/p/contact">Contact</Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
