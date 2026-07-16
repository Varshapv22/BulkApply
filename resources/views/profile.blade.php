@extends('layouts.app')

@section('title', 'Profile & Settings')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Profile &amp; Settings</h1>
    <p class="muted" style="margin:0 0 20px;">Your resume, cover letter, email template, and sending settings.</p>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
        @csrf

        <div class="card">
            <h2>Your details</h2>
            <p class="hint">Used to fill <code>{your_name}</code> in the template and as the reply-to address.</p>
            <div class="row">
                <div>
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="{{ old('full_name', $profile->full_name) }}">
                </div>
                <div>
                    <label for="email">Your email (reply-to)</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $profile->email) }}">
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $profile->phone) }}">
                </div>
            </div>
            <div class="row">
                <div>
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $profile->location) }}" placeholder="e.g. New York, NY">
                </div>
                <div>
                    <label for="preferred_role">Preferred Job Role</label>
                    <input type="text" id="preferred_role" name="preferred_role" value="{{ old('preferred_role', $profile->preferred_role) }}" placeholder="e.g. Software Engineer">
                </div>
            </div>

            <label style="margin-top:12px;">Preferred Job Sites</label>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:4px;">
                @foreach (\App\Models\Profile::JOB_SITES as $key => $name)
                    <label style="font-weight:400;display:flex;align-items:center;gap:6px;font-size:14px;">
                        <input type="checkbox" name="preferred_sites[]" value="{{ $key }}" style="width:auto;"
                               {{ in_array($key, old('preferred_sites', $profile->preferred_sites ?? [])) ? 'checked' : '' }}>
                        {{ $name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="card">
            <h2>Documents</h2>
            <p class="hint">PDF, DOC or DOCX, up to 10 MB each. These are attached to every application email.</p>
            <div class="row">
                <div>
                    <label for="resume">Resume</label>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                    @if ($profile->resume_name)
                        <p class="muted" style="font-size:12px;margin:6px 0 0;">Current: {{ $profile->resume_name }}</p>
                    @endif
                </div>
                <div>
                    <label for="cover_letter">Cover letter</label>
                    <input type="file" id="cover_letter" name="cover_letter" accept=".pdf,.doc,.docx">
                    @if ($profile->cover_letter_name)
                        <p class="muted" style="font-size:12px;margin:6px 0 0;">Current: {{ $profile->cover_letter_name }}</p>
                    @endif
                </div>
            </div>
            <div style="margin-top:12px;">
                <button type="button" class="btn btn-ghost btn-sm" id="parseResumeBtn" onclick="parseResume()">
                    Auto-fill from resume
                </button>
                <span class="muted" style="font-size:12px;margin-left:8px;" id="parseStatus"></span>
            </div>
        </div>

        <div class="card">
            <h2>Default email template</h2>
            <p class="hint">
                Placeholders get replaced per job:
                <code>{job_title}</code> <code>{company}</code> <code>{recruiter_name}</code>
                <code>{location}</code> <code>{job_url}</code> <code>{your_name}</code>.
                You can also create <a href="{{ route('templates.index') }}">multiple templates</a>.
            </p>
            <label for="email_subject">Subject</label>
            <input type="text" id="email_subject" name="email_subject"
                   value="{{ old('email_subject', $profile->email_subject ?: 'Application for {job_title} at {company}') }}">

            <label for="email_body">Body</label>
            <textarea id="email_body" name="email_body" rows="10">{{ old('email_body', $profile->email_body ?: $defaultBody ?? '') }}</textarea>
        </div>

        <div class="card">
            <h2>Email Scheduling</h2>
            <p class="hint">Control when emails are sent. Leave hours empty to send anytime.</p>
            <div class="row">
                <div>
                    <label for="send_start_hour">Send window start (hour, 0-23)</label>
                    <input type="text" id="send_start_hour" name="send_start_hour"
                           value="{{ old('send_start_hour', $profile->send_start_hour) }}" placeholder="e.g. 9">
                </div>
                <div>
                    <label for="send_end_hour">Send window end (hour, 0-23)</label>
                    <input type="text" id="send_end_hour" name="send_end_hour"
                           value="{{ old('send_end_hour', $profile->send_end_hour) }}" placeholder="e.g. 17">
                </div>
            </div>
            <label style="margin-top:12px;font-weight:400;display:flex;align-items:center;gap:8px;">
                <input type="checkbox" name="send_weekdays_only" value="1" style="width:auto;"
                       {{ old('send_weekdays_only', $profile->send_weekdays_only) ? 'checked' : '' }}>
                Only send on weekdays (Mon–Fri)
            </label>
        </div>

        <div class="card">
            <h2>Rate Limiting</h2>
            <p class="hint">Limit how many emails are sent per hour to stay within provider limits. Set to 0 for unlimited.</p>
            <div class="row">
                <div>
                    <label for="max_emails_per_hour">Max emails per hour</label>
                    <input type="text" id="max_emails_per_hour" name="max_emails_per_hour"
                           value="{{ old('max_emails_per_hour', $profile->max_emails_per_hour ?: 0) }}" placeholder="0 = unlimited">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Follow-up Emails</h2>
            <p class="hint">Automatically send a follow-up email N days after the initial application if no reply. Set to 0 to disable. Requires scheduler: <code>php artisan schedule:work</code></p>
            <div class="row">
                <div>
                    <label for="followup_days">Follow up after (days)</label>
                    <input type="text" id="followup_days" name="followup_days"
                           value="{{ old('followup_days', $profile->followup_days ?: 0) }}" placeholder="0 = disabled">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Webhook / Notifications</h2>
            <p class="hint">Receive a POST request to this URL when an email is sent or fails. Payload includes event, company, email, and status.</p>
            <label for="webhook_url">Webhook URL</label>
            <input type="url" id="webhook_url" name="webhook_url"
                   value="{{ old('webhook_url', $profile->webhook_url) }}" placeholder="https://hooks.slack.com/...">
        </div>

        <button type="submit" class="btn btn-primary">Save profile &amp; settings</button>
    </form>

    <script>
        function parseResume() {
            var fileInput = document.getElementById('resume');
            if (!fileInput.files.length) {
                document.getElementById('parseStatus').textContent = 'Please select a resume file first.';
                return;
            }

            var formData = new FormData();
            formData.append('resume', fileInput.files[0]);
            formData.append('_token', '{{ csrf_token() }}');

            document.getElementById('parseStatus').textContent = 'Parsing...';
            document.getElementById('parseResumeBtn').disabled = true;

            fetch('{{ route("profile.parseResume") }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var filled = [];
                if (data.name && !document.getElementById('full_name').value) {
                    document.getElementById('full_name').value = data.name;
                    filled.push('name');
                }
                if (data.email && !document.getElementById('email').value) {
                    document.getElementById('email').value = data.email;
                    filled.push('email');
                }
                if (data.phone && !document.getElementById('phone').value) {
                    document.getElementById('phone').value = data.phone;
                    filled.push('phone');
                }
                document.getElementById('parseStatus').textContent = filled.length
                    ? 'Extracted: ' + filled.join(', ')
                    : 'Could not extract info from this file.';
                document.getElementById('parseResumeBtn').disabled = false;
            })
            .catch(function() {
                document.getElementById('parseStatus').textContent = 'Parsing failed.';
                document.getElementById('parseResumeBtn').disabled = false;
            });
        }
    </script>
@endsection
