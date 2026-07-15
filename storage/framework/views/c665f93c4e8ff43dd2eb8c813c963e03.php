<?php $__env->startSection('title', 'Jobs'); ?>

<?php $__env->startSection('content'); ?>
    <h1 style="font-size:22px;margin:0 0 4px;">Jobs to apply to</h1>
    <p class="muted" style="margin:0 0 20px;">Import your list, then send your resume + cover letter to every recruiter in one click.</p>

    <?php if (! ($profile->hasDocuments())): ?>
        <div class="alert banner-warn">
            You haven't uploaded your resume and cover letter yet.
            <a href="<?php echo e(route('profile.edit')); ?>">Go to Profile &amp; Template</a> to add them before sending.
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
            <p class="hint">Columns: company, job_title, recruiter_name, recruiter_email, job_url, location, notes.</p>
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

    <div class="card">
        <div class="toolbar">
            <form method="POST" action="<?php echo e(route('jobs.send')); ?>"
                  onsubmit="return confirm('Queue and email <?php echo e($counts['pending']); ?> application(s) now?');">
                <?php echo csrf_field(); ?>
                <button type="submit" class="btn btn-primary" <?php echo e($counts['pending'] === 0 ? 'disabled' : ''); ?>>
                    Send <?php echo e($counts['pending']); ?> pending application(s)
                </button>
            </form>
            <div class="spacer"></div>
            <?php if($counts['total'] > 0): ?>
                <form method="POST" action="<?php echo e(route('jobs.clear')); ?>" onsubmit="return confirm('Delete ALL jobs? This cannot be undone.');">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn-link" style="color:var(--red);">Clear all</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if($jobs->isEmpty()): ?>
            <div class="empty">No jobs yet. Import a CSV or add one above to get started.</div>
        <?php else: ?>
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
                            <td class="muted"><?php echo e($job->sent_at ? $job->sent_at->diffForHumans() : '—'); ?></td>
                            <td style="white-space:nowrap;text-align:right;">
                                <?php if($job->status !== 'sent'): ?>
                                    <form method="POST" action="<?php echo e(route('jobs.sendOne', $job)); ?>" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <button type="submit" class="btn btn-ghost btn-sm">Send</button>
                                    </form>
                                <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/jobs.blade.php ENDPATH**/ ?>