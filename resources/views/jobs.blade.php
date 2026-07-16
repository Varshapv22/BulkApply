@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Applications</h1>
    <p class="muted" style="margin:0 0 20px;">Track all your job applications. <a href="{{ route('search.index') }}">Find Jobs</a> to auto-search and apply, or import a CSV / add manually below.</p>

    @unless ($profile->hasDocuments())
        <div class="alert banner-warn">
            Upload your resume and cover letter on <a href="{{ route('profile.edit') }}">Settings</a> before applying.
        </div>
    @endunless

    <div class="stats">
        <div class="stat"><div class="num">{{ $counts['total'] }}</div><div class="lbl">Total</div></div>
        <div class="stat"><div class="num">{{ $counts['pending'] }}</div><div class="lbl">To send</div></div>
        <div class="stat"><div class="num">{{ $counts['sent'] }}</div><div class="lbl">Sent</div></div>
        <div class="stat"><div class="num">{{ $counts['failed'] }}</div><div class="lbl">Failed</div></div>
    </div>

    <details class="card" style="cursor:default;">
        <summary style="cursor:pointer;">Import CSV or Add Manually</summary>
        <div class="row" style="margin-top:16px;">
            <div>
                <h2 style="font-size:15px;">Import from CSV</h2>
                <form method="POST" action="{{ route('jobs.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="csv" accept=".csv,.txt" required>
                    <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                        <button type="submit" class="btn btn-primary btn-sm">Import</button>
                        <a class="btn-link" href="{{ route('jobs.template') }}">Download template</a>
                    </div>
                </form>
            </div>
            <div>
                <h2 style="font-size:15px;">Add one manually</h2>
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
                    <button type="submit" class="btn btn-ghost btn-sm" style="margin-top:12px;">Add job</button>
                </form>
            </div>
        </div>
    </details>

    {{-- Search & Filters --}}
    <div class="card" style="padding:14px 20px;">
        <form method="GET" action="{{ route('jobs.index') }}" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search company, title, recruiter..."
                   style="flex:1;min-width:200px;">
            <select name="status" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="">All statuses</option>
                <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="queued" {{ ($filters['status'] ?? '') === 'queued' ? 'selected' : '' }}>Queued</option>
                <option value="sent" {{ ($filters['status'] ?? '') === 'sent' ? 'selected' : '' }}>Sent</option>
                <option value="failed" {{ ($filters['status'] ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
            </select>
            <select name="pipeline" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="">All stages</option>
                @foreach (\App\Models\JobApplication::PIPELINE_STATUSES as $key => $label)
                    <option value="{{ $key }}" {{ ($filters['pipeline'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="sort" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="created_at" {{ ($filters['sort'] ?? '') === 'created_at' ? 'selected' : '' }}>Newest first</option>
                <option value="company" {{ ($filters['sort'] ?? '') === 'company' ? 'selected' : '' }}>Company</option>
                <option value="status" {{ ($filters['sort'] ?? '') === 'status' ? 'selected' : '' }}>Status</option>
                <option value="sent_at" {{ ($filters['sort'] ?? '') === 'sent_at' ? 'selected' : '' }}>Sent date</option>
            </select>
            <button type="submit" class="btn btn-ghost">Filter</button>
            @if (($filters['search'] ?? '') || ($filters['status'] ?? '') || ($filters['pipeline'] ?? ''))
                <a href="{{ route('jobs.index') }}" class="btn-link" style="color:var(--red);">Clear</a>
            @endif
        </form>
    </div>

    <div class="card">
        <div class="toolbar">
            <form method="POST" action="{{ route('jobs.send') }}"
                  onsubmit="return confirm('Queue and email {{ $counts['pending'] }} application(s) now?');"
                  style="display:flex;gap:10px;align-items:center;">
                @csrf
                @if ($templates->isNotEmpty())
                    <select name="email_template_id" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:13px;background:var(--input-bg);color:var(--text);">
                        <option value="">Use profile template</option>
                        @foreach ($templates as $tpl)
                            <option value="{{ $tpl->id }}">{{ $tpl->name }}{{ $tpl->is_default ? ' (default)' : '' }}</option>
                        @endforeach
                    </select>
                @endif
                <button type="submit" class="btn btn-primary" {{ $counts['pending'] === 0 ? 'disabled' : '' }}>
                    Send {{ $counts['pending'] }} pending
                </button>
            </form>
            <div class="spacer"></div>
            <a href="{{ route('jobs.export') }}" class="btn btn-ghost btn-sm">Export CSV</a>
            @if ($counts['total'] > 0)
                <form method="POST" action="{{ route('jobs.clear') }}" onsubmit="return confirm('Delete ALL jobs? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn-link" style="color:var(--red);">Clear all</button>
                </form>
            @endif
        </div>

        @if ($jobs->isEmpty())
            <div class="empty">No applications yet. <a href="{{ route('search.index') }}">Find Jobs</a> to get started, or import a CSV above.</div>
        @else
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Company / Role</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Pipeline</th>
                        <th>Tracking</th>
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
                                @if ($job->apply_type === 'link' && $job->apply_url)
                                    · <a href="{{ $job->apply_url }}" target="_blank" rel="noopener" style="color:var(--amber);">Apply on portal</a>
                                @endif
                            </td>
                            <td>
                                @if ($job->source)
                                    <span class="badge queued">{{ $job->source }}</span>
                                @else
                                    <span class="muted">{{ $job->recruiter_name ?: '—' }}</span><br>
                                    <span class="muted" style="font-size:11px;">{{ $job->recruiter_email }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $job->status }}">{{ $job->status }}</span>
                                @if ($job->status === 'failed' && $job->error)
                                    <br><span class="muted" style="font-size:11px;" title="{{ $job->error }}">{{ \Illuminate\Support\Str::limit($job->error, 40) }}</span>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('jobs.updatePipeline', $job) }}" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="pipeline_status" onchange="this.form.submit()"
                                            style="padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:12px;background:var(--input-bg);color:var(--text);">
                                        @foreach (\App\Models\JobApplication::PIPELINE_STATUSES as $key => $label)
                                            <option value="{{ $key }}" {{ $job->pipeline_status === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td class="muted" style="font-size:12px;">
                                @if ($job->opened_at)
                                    <span title="Opened {{ $job->opened_at->toDateTimeString() }}" style="color:var(--green);">Opened</span><br>
                                @endif
                                @if ($job->clicked_at)
                                    <span title="Clicked {{ $job->clicked_at->toDateTimeString() }}" style="color:var(--blue);">Clicked</span><br>
                                @endif
                                @if ($job->followup_count > 0)
                                    <span>{{ $job->followup_count }}x follow-up</span>
                                @endif
                                @if (!$job->opened_at && !$job->clicked_at && $job->followup_count === 0)
                                    —
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
                                <button type="button" class="btn btn-ghost btn-sm" onclick="previewEmail({{ $job->id }})" title="Preview email">Preview</button>
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

    {{-- Email Preview Modal --}}
    <div id="previewModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;">
        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;padding:24px;position:relative;">
            <button onclick="closePreview()" style="position:absolute;top:12px;right:12px;background:none;border:none;font-size:18px;cursor:pointer;color:var(--muted);">✕</button>
            <h2 style="margin:0 0 4px;">Email Preview</h2>
            <p class="muted" style="margin:0 0 16px;">This is how the email will look with placeholders filled in.</p>
            <div style="margin-bottom:8px;">
                <span class="muted" style="font-size:12px;">TO:</span>
                <strong id="previewTo"></strong>
            </div>
            <div style="margin-bottom:12px;">
                <span class="muted" style="font-size:12px;">SUBJECT:</span>
                <strong id="previewSubject"></strong>
            </div>
            <div style="border-top:1px solid var(--border);padding-top:12px;font-size:14px;line-height:1.7;" id="previewBody"></div>
        </div>
    </div>

    <script>
        function previewEmail(jobId) {
            var modal = document.getElementById('previewModal');
            modal.style.display = 'flex';
            document.getElementById('previewTo').textContent = 'Loading...';
            document.getElementById('previewSubject').textContent = '';
            document.getElementById('previewBody').innerHTML = '';

            fetch('{{ route("jobs.preview") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ job_id: jobId })
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                document.getElementById('previewTo').textContent = data.to;
                document.getElementById('previewSubject').textContent = data.subject;
                document.getElementById('previewBody').innerHTML = data.body;
            })
            .catch(function() {
                document.getElementById('previewTo').textContent = 'Error loading preview';
            });
        }

        function closePreview() {
            document.getElementById('previewModal').style.display = 'none';
        }

        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) closePreview();
        });
    </script>
@endsection
