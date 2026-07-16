@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 style="font-size:22px;margin:0 0 4px;">Dashboard</h1>
    <p class="muted" style="margin:0 0 20px;">Overview of your job application activity.</p>

    {{-- Status cards --}}
    <div class="stats">
        <div class="stat">
            <div class="num">{{ $counts['total'] }}</div>
            <div class="lbl">Total Jobs</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--green);">{{ $counts['sent'] }}</div>
            <div class="lbl">Sent</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--amber);">{{ $counts['pending'] + $counts['queued'] }}</div>
            <div class="lbl">Pending</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--red);">{{ $counts['failed'] }}</div>
            <div class="lbl">Failed</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--primary);">{{ $sentRate }}%</div>
            <div class="lbl">Success Rate</div>
        </div>
    </div>

    {{-- Email tracking stats --}}
    <div class="stats">
        <div class="stat">
            <div class="num" style="color:var(--blue);">{{ $tracking['opened'] }}</div>
            <div class="lbl">Opened</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--blue);">{{ $tracking['clicked'] }}</div>
            <div class="lbl">Clicked</div>
        </div>
        <div class="stat">
            <div class="num" style="color:var(--blue);">{{ $tracking['open_rate'] }}%</div>
            <div class="lbl">Open Rate</div>
        </div>
    </div>

    {{-- Weekly comparison + Pipeline + Top Companies --}}
    <div class="row">
        <div class="card">
            <h2>This Week</h2>
            <p class="hint">Applications added since {{ now()->startOfWeek()->format('M j') }}</p>
            <div class="num" style="font-size:32px;font-weight:700;">{{ $thisWeek }}</div>
            @php
                $diff = $thisWeek - $lastWeek;
            @endphp
            @if ($diff > 0)
                <span style="color:var(--green);font-size:13px;font-weight:600;">+{{ $diff }} vs last week</span>
            @elseif ($diff < 0)
                <span style="color:var(--red);font-size:13px;font-weight:600;">{{ $diff }} vs last week</span>
            @else
                <span class="muted" style="font-size:13px;">Same as last week</span>
            @endif
        </div>

        <div class="card">
            <h2>Pipeline</h2>
            <p class="hint">Application stages breakdown</p>
            @php
                $pipelineColors = ['applied' => '--primary', 'replied' => '--blue', 'interview' => '--amber', 'rejected' => '--red', 'offer' => '--green'];
            @endphp
            @foreach (\App\Models\JobApplication::PIPELINE_STATUSES as $key => $label)
                <div style="display:flex;align-items:center;gap:10px;padding:5px 0;border-bottom:1px solid var(--border);">
                    <span style="flex:1;font-size:14px;">{{ $label }}</span>
                    <span style="font-weight:700;color:var({{ $pipelineColors[$key] ?? '--text' }});">{{ $pipelineStats[$key] ?? 0 }}</span>
                </div>
            @endforeach
        </div>

        <div class="card">
            <h2>Top Companies</h2>
            <p class="hint">Most applications by company</p>
            @if ($topCompanies->isEmpty())
                <div class="muted" style="padding:10px 0;">No data yet.</div>
            @else
                @foreach ($topCompanies as $company)
                    <div style="display:flex;align-items:center;gap:10px;padding:6px 0;border-bottom:1px solid var(--border);">
                        <span style="flex:1;font-size:14px;font-weight:500;">{{ $company->company }}</span>
                        <span class="badge sent" style="min-width:28px;text-align:center;">{{ $company->count }}</span>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    {{-- Activity chart --}}
    <div class="card">
        <h2>Activity (Last 30 Days)</h2>
        <p class="hint">Applications added per day. <span style="color:var(--primary);">Blue</span> = total, <span style="color:var(--green);">Green</span> = sent.</p>

        @php
            $maxVal = max($chartData->max('total'), 1);
        @endphp

        <div style="display:flex;align-items:flex-end;gap:3px;height:160px;padding-top:10px;">
            @foreach ($chartData as $day)
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%;position:relative;"
                     title="{{ $day['label'] }}: {{ $day['total'] }} added, {{ $day['sent'] }} sent">
                    @if ($day['total'] > 0)
                        <div style="width:100%;max-width:20px;border-radius:3px 3px 0 0;background:var(--primary);opacity:0.3;height:{{ ($day['total'] / $maxVal) * 100 }}%;min-height:4px;position:relative;">
                            @if ($day['sent'] > 0)
                                <div style="position:absolute;bottom:0;left:0;right:0;border-radius:0 0 0 0;background:var(--green);opacity:1;height:{{ ($day['sent'] / $day['total']) * 100 }}%;min-height:3px;border-radius:0 0 3px 3px;"></div>
                            @endif
                        </div>
                    @else
                        <div style="width:100%;max-width:20px;height:2px;background:var(--border);border-radius:1px;"></div>
                    @endif
                </div>
            @endforeach
        </div>
        <div style="display:flex;gap:3px;margin-top:6px;">
            @foreach ($chartData as $i => $day)
                @if ($i % 5 === 0 || $i === count($chartData) - 1)
                    <div style="flex:1;text-align:center;font-size:10px;color:var(--muted);">{{ $day['label'] }}</div>
                @else
                    <div style="flex:1;"></div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Recent activity --}}
    <div class="card">
        <h2>Recent Activity</h2>
        <p class="hint">Last 10 sent or failed applications.</p>

        @if ($recentActivity->isEmpty())
            <div class="empty">No sent or failed applications yet.</div>
        @else
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Company / Role</th>
                        <th>Recruiter</th>
                        <th>Status</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentActivity as $job)
                        <tr>
                            <td>
                                <strong>{{ $job->company }}</strong><br>
                                <span class="muted">{{ $job->job_title ?: '-' }}</span>
                            </td>
                            <td>
                                {{ $job->recruiter_name ?: '-' }}<br>
                                <span class="muted">{{ $job->recruiter_email }}</span>
                            </td>
                            <td><span class="badge {{ $job->status }}">{{ $job->status }}</span></td>
                            <td class="muted">{{ $job->updated_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endif
    </div>
@endsection
