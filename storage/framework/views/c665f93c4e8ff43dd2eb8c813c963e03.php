<?php $__env->startSection('title', 'Jobs'); ?>

<?php $__env->startSection('content'); ?>
    <h1 style="font-size:22px;margin:0 0 4px;">Jobs to apply to</h1>
    <p class="muted" style="margin:0 0 20px;">Import your list, then send your resume + cover letter to every recruiter in one click.</p>

    <?php if (! ($profile->hasDocuments())): ?>
        <div class="alert banner-warn">
            You haven't uploaded your resume and cover letter yet.
            <a href="<?php echo e(route('profile.edit')); ?>">Go to Profile &amp; Settings</a> to add them before sending.
        </div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat"><div class="num"><?php echo e($counts['total']); ?></div><div class="lbl">Total</div></div>
        <div class="stat"><div class="num"><?php echo e($counts['pending']); ?></div><div class="lbl">To send</div></div>
        <div class="stat"><div class="num"><?php echo e($counts['sent']); ?></div><div class="lbl">Sent</div></div>
        <div class="stat"><div class="num"><?php echo e($counts['failed']); ?></div><div class="lbl">Failed</div></div>
    </div>

    <div class="row">
        <div class="card">
            <h2>Import from CSV</h2>
            <p class="hint">Columns: company, job_title, recruiter_name, recruiter_email, job_url, location, notes. Duplicates (same email + company) are skipped.</p>
            <form method="POST" action="<?php echo e(route('jobs.import')); ?>" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="file" name="csv" accept=".csv,.txt" required>
                <div style="margin-top:12px;display:flex;gap:10px;align-items:center;">
                    <button type="submit" class="btn btn-primary">Import</button>
                    <a class="btn-link" href="<?php echo e(route('jobs.template')); ?>">Download template</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Add one manually</h2>
            <p class="hint">For a quick single entry.</p>
            <form method="POST" action="<?php echo e(route('jobs.store')); ?>">
                <?php echo csrf_field(); ?>
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

    
    <div class="card" style="padding:14px 20px;">
        <form method="GET" action="<?php echo e(route('jobs.index')); ?>" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="text" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" placeholder="Search company, title, recruiter..."
                   style="flex:1;min-width:200px;">
            <select name="status" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="">All statuses</option>
                <option value="pending" <?php echo e(($filters['status'] ?? '') === 'pending' ? 'selected' : ''); ?>>Pending</option>
                <option value="queued" <?php echo e(($filters['status'] ?? '') === 'queued' ? 'selected' : ''); ?>>Queued</option>
                <option value="sent" <?php echo e(($filters['status'] ?? '') === 'sent' ? 'selected' : ''); ?>>Sent</option>
                <option value="failed" <?php echo e(($filters['status'] ?? '') === 'failed' ? 'selected' : ''); ?>>Failed</option>
            </select>
            <select name="pipeline" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="">All stages</option>
                <?php $__currentLoopData = \App\Models\JobApplication::PIPELINE_STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($key); ?>" <?php echo e(($filters['pipeline'] ?? '') === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <select name="sort" style="padding:9px 11px;border:1px solid var(--border);border-radius:7px;font-size:14px;background:var(--input-bg);color:var(--text);">
                <option value="created_at" <?php echo e(($filters['sort'] ?? '') === 'created_at' ? 'selected' : ''); ?>>Newest first</option>
                <option value="company" <?php echo e(($filters['sort'] ?? '') === 'company' ? 'selected' : ''); ?>>Company</option>
                <option value="status" <?php echo e(($filters['sort'] ?? '') === 'status' ? 'selected' : ''); ?>>Status</option>
                <option value="sent_at" <?php echo e(($filters['sort'] ?? '') === 'sent_at' ? 'selected' : ''); ?>>Sent date</option>
            </select>
            <button type="submit" class="btn btn-ghost">Filter</button>
            <?php if(($filters['search'] ?? '') || ($filters['status'] ?? '') || ($filters['pipeline'] ?? '')): ?>
                <a href="<?php echo e(route('jobs.index')); ?>" class="btn-link" style="color:var(--red);">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card">
        <div class="toolbar">
            <form method="POST" action="<?php echo e(route('jobs.send')); ?>"
                  onsubmit="return confirm('Queue and email <?php echo e($counts['pending']); ?> application(s) now?');"
                  style="display:flex;gap:10px;align-items:center;">
                <?php echo csrf_field(); ?>
                <?php if($templates->isNotEmpty()): ?>
                    <select name="email_template_id" style="padding:7px 10px;border:1px solid var(--border);border-radius:7px;font-size:13px;background:var(--input-bg);color:var(--text);">
                        <option value="">Use profile template</option>
                        <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($tpl->id); ?>"><?php echo e($tpl->name); ?><?php echo e($tpl->is_default ? ' (default)' : ''); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary" <?php echo e($counts['pending'] === 0 ? 'disabled' : ''); ?>>
                    Send <?php echo e($counts['pending']); ?> pending
                </button>
            </form>
            <div class="spacer"></div>
            <a href="<?php echo e(route('jobs.export')); ?>" class="btn btn-ghost btn-sm">Export CSV</a>
            <?php if($counts['total'] > 0): ?>
                <form method="POST" action="<?php echo e(route('jobs.clear')); ?>" onsubmit="return confirm('Delete ALL jobs? This cannot be undone.');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn-link" style="color:var(--red);">Clear all</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if($jobs->isEmpty()): ?>
            <div class="empty">No jobs match your filters. Import a CSV or add one above to get started.</div>
        <?php else: ?>
            <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Company / Role</th>
                        <th>Recruiter</th>
                        <th>Status</th>
                        <th>Pipeline</th>
                        <th>Tracking</th>
                        <th>Sent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <strong><?php echo e($job->company); ?></strong><br>
                                <span class="muted"><?php echo e($job->job_title ?: '—'); ?></span>
                                <?php if($job->job_url): ?>
                                    · <a href="<?php echo e($job->job_url); ?>" target="_blank" rel="noopener">link</a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo e($job->recruiter_name ?: '—'); ?><br>
                                <span class="muted"><?php echo e($job->recruiter_email); ?></span>
                            </td>
                            <td>
                                <span class="badge <?php echo e($job->status); ?>"><?php echo e($job->status); ?></span>
                                <?php if($job->status === 'failed' && $job->error): ?>
                                    <br><span class="muted" style="font-size:11px;" title="<?php echo e($job->error); ?>"><?php echo e(\Illuminate\Support\Str::limit($job->error, 40)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo e(route('jobs.updatePipeline', $job)); ?>" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <select name="pipeline_status" onchange="this.form.submit()"
                                            style="padding:4px 6px;border:1px solid var(--border);border-radius:5px;font-size:12px;background:var(--input-bg);color:var(--text);">
                                        <?php $__currentLoopData = \App\Models\JobApplication::PIPELINE_STATUSES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($key); ?>" <?php echo e($job->pipeline_status === $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                </form>
                            </td>
                            <td class="muted" style="font-size:12px;">
                                <?php if($job->opened_at): ?>
                                    <span title="Opened <?php echo e($job->opened_at->toDateTimeString()); ?>" style="color:var(--green);">Opened</span><br>
                                <?php endif; ?>
                                <?php if($job->clicked_at): ?>
                                    <span title="Clicked <?php echo e($job->clicked_at->toDateTimeString()); ?>" style="color:var(--blue);">Clicked</span><br>
                                <?php endif; ?>
                                <?php if($job->followup_count > 0): ?>
                                    <span><?php echo e($job->followup_count); ?>x follow-up</span>
                                <?php endif; ?>
                                <?php if(!$job->opened_at && !$job->clicked_at && $job->followup_count === 0): ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?php echo e($job->sent_at ? $job->sent_at->diffForHumans() : '—'); ?></td>
                            <td style="white-space:nowrap;text-align:right;">
                                <?php if($job->status !== 'sent'): ?>
                                    <form method="POST" action="<?php echo e(route('jobs.sendOne', $job)); ?>" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">Send</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="btn btn-ghost btn-sm" onclick="previewEmail(<?php echo e($job->id); ?>)" title="Preview email">Preview</button>
                                <form method="POST" action="<?php echo e(route('jobs.destroy', $job)); ?>" style="display:inline;"
                                      onsubmit="return confirm('Delete this job?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn-danger">✕</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>

    
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

            fetch('<?php echo e(route("jobs.preview")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/jobs.blade.php ENDPATH**/ ?>