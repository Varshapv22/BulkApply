@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Jobs to apply to</h1>
    <p class="muted" style="margin:0 0 20px;">Import your list, then send your resume + cover letter to every recruiter in one click.</p>

    @unless ($profile->hasDocuments())
        <div class="alert banner-warn">
            You haven't uploaded your resume and cover letter yet.
            <a href="{{ route('profile.edit') }}">Go to Profile &amp; Template</a> to add them before sending.
        </div>
    @endunless

    <div class="stats">
        <div class="stat"><div class="num">{{ $counts['total'] }}</div><div class="lbl">Total</div></div>
        <div class="stat"><div class="num">{{ $counts['pending'] }}</div><div class="lbl">To send</div></div>
        <div class="stat"><div class="num">{{ $counts['sent'] }}</div><div class="lbl">Sent</div></div>
        <div class="stat"><div class="num">{{ $counts['failed'] }}</div><div class="lbl">Failed</div></div>
    </div>

    <div class="row">
        <div class="card">
            <h2>Import from CSV</h2>
            <p class="hint">Columns: company, job_title, recruiter_name, recruiter_email, job_url, location, notes.</p>
            <form method="POST" action="{{ route('jobs.import') }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="csv" accept=".csv,.txt" required>
                <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                    <button type="submit" class="btn btn-primary">Import</button>
                    <a class="btn-link" href="{{ route('jobs.template') }}">Download template</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Add one manually</h2>
            <p class="hint">For a quick single entry.</p>
            <form method="POST" action="{{ route('jobs.store') }}">
                @csrf
                <div class="row">
                    <div><input type="text" name="company" placeholder="Company *" required></div>
                    <div><input type="text" name="job_title" placeholder="Job title"></div>
                </div>
                <div class="row">
                    <div><input type="text" name="recruiter_name" placeholder="Recruiter name"></div>
                    <div><input type="email" name="recruiter_email" placeholder="Recruiter email *" required></div>
                </div>
                <button type="submit" class="btn btn-ghost" style="margin-top:12px;">Add job</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="toolbar">
            <form method="POST" action="{{ route('jobs.send') }}"
                  onsubmit="return confirm('Queue and email {{ $counts['pending'] }} application(s) now?');">
                @csrf
                <button type="submit" class="btn btn-primary" {{ $counts['pending'] === 0 ? 'disabled' : '' }}>
                    Send {{ $counts['pending'] }} pending application(s)
                </button>
            </form>
            <div class="spacer"></div>
            @if ($counts['total'] > 0)
                <form method="POST" action="{{ route('jobs.clear') }}" onsubmit="return confirm('Delete ALL jobs? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn-link" style="color:var(--red);">Clear all</button>
                </form>
            @endif
        </div>

        @if ($jobs->isEmpty())
            <div class="empty">No jobs yet. Import a CSV or add one above to get started.</div>
        @else
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Company / Role</th>
                        <th>Recruiter</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($jobs as $job)
                        <tr>
                            <td>
                                <strong>{{ $job->company }}</strong><br>
                                <span class="muted">{{ $job->job_title ?: '—' }}</span>
                                @if ($job->job_url)
                                    · <a href="{{ $job->job_url }}" target="_blank" rel="noopener">link</a>
                                @endif
                            </td>
                            <td>
                                {{ $job->recruiter_name ?: '—' }}<br>
                                <span class="muted">{{ $job->recruiter_email }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $job->status }}">{{ $job->status }}</span>
                                @if ($job->status === 'failed' && $job->error)
                                    <br><span class="muted" style="font-size:11px;" title="{{ $job->error }}">{{ \Illuminate\Support\Str::limit($job->error, 40) }}</span>
                                @endif
                            </td>
                            <td class="muted">{{ $job->sent_at ? $job->sent_at->diffForHumans() : '—' }}</td>
                            <td style="white-space:nowrap;text-align:right;">
                                @if ($job->status !== 'sent')
                                    <form method="POST" action="{{ route('jobs.sendOne', $job) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm">Send</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('jobs.destroy', $job) }}" style="display:inline;"
                                      onsubmit="return confirm('Delete this job?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">✕</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
