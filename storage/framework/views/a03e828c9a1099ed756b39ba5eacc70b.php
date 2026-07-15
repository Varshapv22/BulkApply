<?php $__env->startSection('title', 'Profile & Template'); ?>

<?php $__env->startSection('content'); ?>
    <h1 style="font-size:22px;margin:0 0 4px;">Profile &amp; Email Template</h1>
    <p class="muted" style="margin:0 0 20px;">Your resume, cover letter and the email that gets sent to every recruiter.</p>

    <form method="POST" action="<?php echo e(route('profile.update')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="card">
            <h2>Your details</h2>
            <p class="hint">Used to fill <code>{your_name}</code> in the template and as the reply-to address.</p>
            <div class="row">
                <div>
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo e(old('full_name', $profile->full_name)); ?>">
                </div>
                <div>
                    <label for="email">Your email (reply-to)</label>
                    <input type="email" id="email" name="email" value="<?php echo e(old('email', $profile->email)); ?>">
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo e(old('phone', $profile->phone)); ?>">
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Documents</h2>
            <p class="hint">PDF, DOC or DOCX, up to 10 MB each. These are attached to every application email.</p>
            <div class="row">
                <div>
                    <label for="resume">Resume</label>
                    <input type="file" id="resume" name="resume" accept=".pdf,.doc,.docx">
                    <?php if($profile->resume_name): ?>
                        <p class="muted" style="font-size:12px;margin:6px 0 0;">Current: <?php echo e($profile->resume_name); ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="cover_letter">Cover letter</label>
                    <input type="file" id="cover_letter" name="cover_letter" accept=".pdf,.doc,.docx">
                    <?php if($profile->cover_letter_name): ?>
                        <p class="muted" style="font-size:12px;margin:6px 0 0;">Current: <?php echo e($profile->cover_letter_name); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Email template</h2>
            <p class="hint">
                Placeholders get replaced per job:
                <code>{job_title}</code> <code>{company}</code> <code>{recruiter_name}</code>
                <code>{location}</code> <code>{job_url}</code> <code>{your_name}</code>
            </p>
            <label for="email_subject">Subject</label>
            <input type="text" id="email_subject" name="email_subject"
                   value="<?php echo e(old('email_subject', $profile->email_subject ?: 'Application for {job_title} at {company}')); ?>">

            <label for="email_body">Body</label>
            <textarea id="email_body" name="email_body" rows="10"><?php echo e(old('email_body', $profile->email_body ?: $defaultBody ?? '')); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Save profile</button>
    </form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/profile.blade.php ENDPATH**/ ?>