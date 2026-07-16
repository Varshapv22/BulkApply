@extends('layouts.app')

@section('title', 'Find Jobs')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Find Jobs</h1>
    <p class="muted" style="margin:0 0 20px;">Search for job vacancies across multiple sites. Select matching jobs and auto-apply in one click.</p>

    @unless ($profile->hasDocuments())
        <div class="alert banner-warn">
            Upload your resume and cover letter on <a href="{{ route('profile.edit') }}">Profile & Settings</a> before applying.
        </div>
    @endunless

    {{-- Search form --}}
    <div class="card">
        <h2>Search Criteria</h2>
        <p class="hint">Enter the role you're looking for, your location, and which job sites to search.</p>
        <form method="POST" action="{{ route('search.search') }}">
            @csrf
            <div class="row">
                <div style="flex:2;">
                    <label for="role">Job Role / Title *</label>
                    <input type="text" id="role" name="role" required
                           value="{{ old('role', $profile->preferred_role) }}"
                           placeholder="e.g. Software Engineer, Data Analyst, Product Manager">
                </div>
                <div>
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location"
                           value="{{ old('location', $profile->location) }}"
                           placeholder="e.g. New York, Remote, London">
                </div>
            </div>

            <label style="margin-top:12px;">Job Sites</label>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                @foreach (\App\Models\Profile::JOB_SITES as $key => $name)
                    <label style="font-weight:400;display:flex;align-items:center;gap:6px;font-size:14px;">
                        <input type="checkbox" name="sites[]" value="{{ $key }}" style="width:auto;"
                               {{ in_array($key, old('sites', $profile->preferred_sites ?? [])) ? 'checked' : '' }}>
                        {{ $name }}
                    </label>
                @endforeach
            </div>
            <p class="hint" style="margin-top:6px;">Leave all unchecked to search all sites.</p>

            <button type="submit" class="btn btn-primary" style="margin-top:16px;">Search Jobs</button>
        </form>
    </div>

    {{-- Error --}}
    @if ($error)
        <div class="alert alert-error">{{ $error }}</div>
    @endif

    {{-- Results --}}
    @if ($searched && empty($error))
        <div class="card">
            <div class="toolbar">
                <h2 style="margin:0;">{{ count($results) }} Job(s) Found</h2>
                <div class="spacer"></div>
                @if (count($results) > 0)
                    <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAll()">Select All</button>
                @endif
            </div>

            @if (empty($results))
                <div class="empty">No jobs found matching your criteria. Try broadening your search.</div>
            @else
                <form method="POST" action="{{ route('search.autoApply') }}" id="applyForm">
                    @csrf

                    <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:30px;"><input type="checkbox" id="selectAll" onchange="toggleAll()" style="width:auto;"></th>
                                <th>Job</th>
                                <th>Company</th>
                                <th>Location</th>
                                <th>Source</th>
                                <th>Apply Via</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $i => $job)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="job-check" name="jobs[{{ $i }}][company]"
                                               value="{{ $job['company'] }}" style="width:auto;"
                                               onchange="syncFields(this, {{ $i }})">
                                        {{-- Hidden fields, enabled when checked --}}
                                        <input type="hidden" name="jobs[{{ $i }}][job_title]" value="{{ $job['job_title'] }}" disabled class="job-field-{{ $i }}">
                                        <input type="hidden" name="jobs[{{ $i }}][location]" value="{{ $job['location'] }}" disabled class="job-field-{{ $i }}">
                                        <input type="hidden" name="jobs[{{ $i }}][recruiter_email]" value="{{ $job['recruiter_email'] }}" disabled class="job-field-{{ $i }}">
                                        <input type="hidden" name="jobs[{{ $i }}][job_url]" value="{{ $job['job_url'] }}" disabled class="job-field-{{ $i }}">
                                        <input type="hidden" name="jobs[{{ $i }}][apply_url]" value="{{ $job['apply_url'] }}" disabled class="job-field-{{ $i }}">
                                        <input type="hidden" name="jobs[{{ $i }}][source]" value="{{ $job['source'] }}" disabled class="job-field-{{ $i }}">
                                    </td>
                                    <td>
                                        <strong>{{ $job['job_title'] }}</strong>
                                        @if ($job['description'])
                                            <br><span class="muted" style="font-size:12px;">{{ $job['description'] }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($job['employer_logo'])
                                            <img src="{{ $job['employer_logo'] }}" alt="" style="width:20px;height:20px;border-radius:4px;vertical-align:middle;margin-right:4px;">
                                        @endif
                                        {{ $job['company'] }}
                                    </td>
                                    <td>{{ $job['location'] ?: '—' }}</td>
                                    <td><span class="badge queued">{{ $job['source'] }}</span></td>
                                    <td>
                                        @if ($job['apply_type'] === 'email')
                                            <span class="badge sent" title="{{ $job['recruiter_email'] }}">Email</span>
                                        @else
                                            <a href="{{ $job['apply_url'] }}" target="_blank" rel="noopener" class="badge pending" style="text-decoration:none;">
                                                Portal
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    <div style="margin-top:16px;display:flex;gap:12px;align-items:center;">
                        <button type="submit" class="btn btn-primary" id="applyBtn" disabled>
                            Apply to Selected Jobs
                        </button>
                        <span class="muted" style="font-size:13px;" id="selectedCount">0 selected</span>
                        <span class="muted" style="font-size:12px;">
                            (Email jobs will be sent automatically. Portal jobs will be added to your list with apply links.)
                        </span>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <script>
        function syncFields(checkbox, index) {
            var fields = document.querySelectorAll('.job-field-' + index);
            fields.forEach(function(f) { f.disabled = !checkbox.checked; });
            updateCount();
        }

        function toggleAll() {
            var selectAll = document.getElementById('selectAll');
            var checkboxes = document.querySelectorAll('.job-check');
            // If called from button, toggle based on current state
            if (!event || event.target.id !== 'selectAll') {
                var anyUnchecked = Array.from(checkboxes).some(function(c) { return !c.checked; });
                selectAll.checked = anyUnchecked;
            }
            checkboxes.forEach(function(cb, i) {
                cb.checked = selectAll.checked;
                syncFields(cb, i);
            });
        }

        function updateCount() {
            var checked = document.querySelectorAll('.job-check:checked').length;
            document.getElementById('selectedCount').textContent = checked + ' selected';
            document.getElementById('applyBtn').disabled = checked === 0;
        }
    </script>
@endsection
