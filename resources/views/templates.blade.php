@extends('layouts.app')

@section('title', 'Email Templates')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Email Templates</h1>
    <p class="muted" style="margin:0 0 20px;">Create multiple templates for different job types. The default template is used unless you choose another when sending.</p>

    <div class="card">
        <h2>Create New Template</h2>
        <p class="hint">
            Placeholders: <code>{job_title}</code> <code>{company}</code> <code>{recruiter_name}</code>
            <code>{location}</code> <code>{job_url}</code> <code>{your_name}</code>
        </p>
        <form method="POST" action="{{ route('templates.store') }}">
            @csrf
            <div class="row">
                <div>
                    <label for="name">Template name</label>
                    <input type="text" id="name" name="name" placeholder="e.g. Engineering roles" required>
                </div>
                <div>
                    <label for="subject">Email subject</label>
                    <input type="text" id="subject" name="subject" placeholder="Application for {job_title} at {company}" required>
                </div>
            </div>
            <label for="body">Email body</label>
            <textarea id="body" name="body" rows="8" required placeholder="Dear {recruiter_name}, ..."></textarea>
            <div style="margin-top:12px;display:flex;align-items:center;gap:12px;">
                <button type="submit" class="btn btn-primary">Save Template</button>
                <label style="margin:0;font-weight:400;display:flex;align-items:center;gap:6px;">
                    <input type="checkbox" name="is_default" value="1" style="width:auto;"> Set as default
                </label>
            </div>
        </form>
    </div>

    @if ($templates->isEmpty())
        <div class="card empty">No templates yet. Create one above or use the profile template.</div>
    @else
        @foreach ($templates as $template)
            <div class="card">
                <form method="POST" action="{{ route('templates.update', $template) }}">
                    @csrf
                    @method('PUT')
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <div>
                            <h2 style="display:inline;">{{ $template->name }}</h2>
                            @if ($template->is_default)
                                <span class="badge sent" style="margin-left:8px;">Default</span>
                            @endif
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-ghost btn-sm">Save</button>
                            <button type="button" class="btn-danger" style="font-size:12px;"
                                    onclick="if(confirm('Delete this template?')) this.closest('.card').querySelector('.delete-form').submit();">
                                Delete
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <div>
                            <label>Name</label>
                            <input type="text" name="name" value="{{ $template->name }}" required>
                        </div>
                        <div>
                            <label>Subject</label>
                            <input type="text" name="subject" value="{{ $template->subject }}" required>
                        </div>
                    </div>
                    <label>Body</label>
                    <textarea name="body" rows="6" required>{{ $template->body }}</textarea>
                    <label style="margin-top:8px;font-weight:400;display:flex;align-items:center;gap:6px;">
                        <input type="checkbox" name="is_default" value="1" style="width:auto;" {{ $template->is_default ? 'checked' : '' }}>
                        Default template
                    </label>
                </form>
                <form class="delete-form" method="POST" action="{{ route('templates.destroy', $template) }}" style="display:none;">
                    @csrf
                    @method('DELETE')
                </form>
            </div>
        @endforeach
    @endif
@endsection
