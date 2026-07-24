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

export default function Register({ errors = {} }) {
    const { data, setData, post, processing } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/register');
    };

    return (
        <div className="auth-wrap">
            <Head title="Register" />

            <div className="auth-hero">
                <div className="brand"><span className="dot">B</span> BulkApply</div>
                <div className="hero-body">
                    <h2>Start your job hunt the smart way.</h2>
                    <p className="lead">Create an account and let BulkApply find, personalise, and send your applications while you focus on interviews.</p>
                    <div className="feat"><Tick /> Free to get started</div>
                    <div className="feat"><Tick /> Import jobs or auto-search in seconds</div>
                    <div className="feat"><Tick /> Your resume, your templates, your rules</div>
                </div>
            </div>

            <div className="auth-form-side">
                <div className="auth-card">
                    <div className="auth-mobile-brand"><span className="dot">B</span> BulkApply</div>
                    <h1 className="title">Create your account</h1>
                    <p className="sub">Get started in under a minute.</p>

                    {Object.values(errors).length > 0 && (
                        <div className="alert alert-error">
                            <ul style={{ margin: '0 0 0 18px' }}>
                                {Object.values(errors).map((e, i) => <li key={i}>{e}</li>)}
                            </ul>
                        </div>
                    )}
                    <form onSubmit={submit}>
                        <label htmlFor="name">Name</label>
                        <input id="name" type="text" autoFocus value={data.name}
                            onChange={(e) => setData('name', e.target.value)} placeholder="Jane Doe" required />

                        <label htmlFor="email">Email</label>
                        <input id="email" type="email" value={data.email}
                            onChange={(e) => setData('email', e.target.value)} placeholder="you@example.com" required />

                        <label htmlFor="password">Password</label>
                        <PasswordInput id="password" value={data.password}
                            onChange={(e) => setData('password', e.target.value)} placeholder="At least 8 characters" required />

                        <label htmlFor="password_confirmation">Confirm Password</label>
                        <PasswordInput id="password_confirmation" value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)} placeholder="Re-enter password" required />

                        <button type="submit" className="btn btn-primary btn-block" style={{ marginTop: 18 }} disabled={processing}>
                            {processing ? 'Creating…' : 'Create account'}
                        </button>
                    </form>

                    <p style={{ textAlign: 'center', margin: '20px 0 0', fontSize: 14 }} className="muted">
                        Already have an account? <Link href="/login">Sign in</Link>
                    </p>

                    <p style={{ textAlign: 'center', margin: '14px 0 0', fontSize: 12 }} className="muted">
                        <Link href="/p/pricing">Pricing</Link> · <Link href="/p/faq">FAQ</Link> · <Link href="/p/privacy">Privacy</Link> · <Link href="/p/terms">Terms</Link> · <Link href="/p/contact">Contact</Link>
                    </p>
                </div>
            </div>
        </div>
    );
}
