# Bulk Apply

A small Laravel + Docker app for bulk-applying to jobs. Upload your **resume** and
**cover letter** once, import a **list of jobs with recruiter emails**, and send a
personalized application email (with both documents attached) to every recruiter in
one click.

## Stack

| Service   | What it is                              | URL / Port |
|-----------|-----------------------------------------|------------|
| nginx     | Web server                              | http://localhost:8080 |
| app       | PHP 8.2 FPM (Laravel 12)                | internal :9000 |
| queue     | `php artisan queue:work` — sends email  | (background) |
| mysql     | Database                                | localhost:3308 |
| mailpit   | Fake inbox — catches all outgoing mail  | http://localhost:8025 |

## Getting started

```bash
cp .env.example .env          # already done; edit if needed
docker compose up -d --build  # build + start everything
docker compose exec app php artisan key:generate   # if APP_KEY is empty
docker compose exec app php artisan migrate --force
```

Then open **http://localhost:8080**.

## How to use it

1. **Profile & Template** tab
   - Upload your resume and cover letter (PDF / DOC / DOCX).
   - Fill in your name/email and edit the email template. Placeholders get replaced
     per job: `{job_title}`, `{company}`, `{recruiter_name}`, `{location}`,
     `{job_url}`, `{your_name}`.
2. **Jobs** tab
   - Import a CSV (download the template for the exact columns), or add jobs one by
     one. Required per row: **company** and a valid **recruiter_email**.
   - Click **Send pending application(s)**. Emails are queued and sent in the
     background. Each row shows `pending → queued → sent` (or `failed` with the error).
3. Check **http://localhost:8025** (Mailpit) to see exactly what was sent — nothing
   reaches real recruiters while Mailpit is configured.

### CSV columns

`company, job_title, recruiter_name, recruiter_email, job_url, location, notes`

Headers are matched case-insensitively and accept common aliases (e.g. `Email`,
`Recruiter Mail`, `Title`, `Role`).

## Sending for real

When you're ready to send actual emails, edit the `MAIL_*` block in `.env` to point at
your SMTP provider (Gmail, Mailgun, SES, etc.), then restart:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-user
MAIL_PASSWORD=your-pass
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="you@yourdomain.com"
MAIL_FROM_NAME="Your Name"
```

```bash
docker compose restart app queue
```

> Tip: for Gmail use an **App Password**, not your normal password. Be mindful of your
> provider's daily sending limits when bulk-sending.

## Handy commands

```bash
docker compose logs -f queue          # watch emails being sent
docker compose exec app php artisan migrate:fresh   # reset the database
docker compose down                   # stop (add -v to also wipe the DB volume)
```
# BulkApply
