# BulkApply — User Manual

> Complete guide for **Users** and **Administrators**

---

## Table of Contents

1. [Overview](#1-overview)
2. [Getting Started](#2-getting-started)
3. [User Guide](#3-user-guide)
   - 3.1 [Dashboard](#31-dashboard)
   - 3.2 [Profile Setup](#32-profile-setup)
   - 3.3 [Resumes & Cover Letters](#33-resumes--cover-letters)
   - 3.4 [Email Configuration](#34-email-configuration)
   - 3.5 [Sending Schedule & Rate Limits](#35-sending-schedule--rate-limits)
   - 3.6 [Job Applications](#36-job-applications)
   - 3.7 [Bulk Send](#37-bulk-send)
   - 3.8 [Pipeline Tracking](#38-pipeline-tracking)
   - 3.9 [Email Templates](#39-email-templates)
   - 3.10 [Find Jobs (Job Search)](#310-find-jobs-job-search)
   - 3.11 [Company Replies (Inbox)](#311-company-replies-inbox)
   - 3.12 [Resume ATS Checker](#312-resume-ats-checker)
   - 3.13 [Company Insights](#313-company-insights)
   - 3.14 [Saved Search Alerts](#314-saved-search-alerts)
   - 3.15 [Billing & Plans](#315-billing--plans)
   - 3.16 [Support Tickets](#316-support-tickets)
   - 3.17 [Browser Extension](#317-browser-extension)
   - 3.18 [Account Settings](#318-account-settings)
   - 3.19 [Notifications](#319-notifications)
4. [Admin Guide](#4-admin-guide)
   - 4.1 [Admin Dashboard](#41-admin-dashboard)
   - 4.2 [User Management](#42-user-management)
   - 4.3 [Subscriptions](#43-subscriptions)
   - 4.4 [Plans](#44-plans)
   - 4.5 [Free Access](#45-free-access)
   - 4.6 [Payment Requests](#46-payment-requests)
   - 4.7 [Job Applications (Admin View)](#47-job-applications-admin-view)
   - 4.8 [Resumes (Admin View)](#48-resumes-admin-view)
   - 4.9 [Job Sources](#49-job-sources)
   - 4.10 [Queue Monitor](#410-queue-monitor)
   - 4.11 [Analytics & Reports](#411-analytics--reports)
   - 4.12 [Support Management](#412-support-management)
   - 4.13 [CMS Pages](#413-cms-pages)
   - 4.14 [API Configuration](#414-api-configuration)
   - 4.15 [Webhooks](#415-webhooks)
   - 4.16 [Feature Flags](#416-feature-flags)
   - 4.17 [Site Settings](#417-site-settings)
   - 4.18 [Security](#418-security)
   - 4.19 [Audit Logs](#419-audit-logs)
   - 4.20 [Backup](#420-backup)
   - 4.21 [Storage](#421-storage)
   - 4.22 [Monitoring & Logs](#422-monitoring--logs)
   - 4.23 [Database Tools](#423-database-tools)
   - 4.24 [Notifications (Admin)](#424-notifications-admin)
   - 4.25 [Browser Extension (Admin)](#425-browser-extension-admin)
5. [Email Template Placeholders](#5-email-template-placeholders)
6. [CSV Import Format](#6-csv-import-format)
7. [Frequently Asked Questions](#7-frequently-asked-questions)

---

## 1. Overview

**BulkApply** is a job application automation platform. It lets job seekers:

- Build a list of target companies and recruiters
- Send personalised application emails in bulk — automatically
- Track every email (opens, clicks, follow-ups)
- Search live job boards and one-click apply
- Monitor all recruiter replies in a single inbox
- Manage the full hiring pipeline from "Applied" to "Offer"

Administrators get a separate control panel to manage users, plans, payments, job sources, system health, and more.

---

## 2. Getting Started

### 2.1 Registration

1. Visit the home page and click **Get Started** or **Register**.
2. Fill in your **name**, **email address**, and **password**.
3. Verify your email if required (check your inbox for a verification link).
4. After login you land on your **Dashboard**.

> New accounts receive a free **trial period**. You can use all features during the trial. When it expires you need an active plan to continue.

### 2.2 Login

- Go to `/login`, enter your email and password, and click **Sign In**.
- If **Two-Factor Authentication (2FA)** is enabled on your account, you will be asked for a one-time code from your authenticator app after entering your password.

### 2.3 Quick-Start Checklist

Before sending your first application:

| Step | Where |
|------|-------|
| 1. Fill in your personal info | Profile page |
| 2. Upload your resume | Profile → Resumes section |
| 3. Upload your cover letter (or paste text) | Profile → Cover Letters section |
| 4. Connect your email sender (Gmail/SMTP) | Profile → Email Sender section |
| 5. Write your application email subject & body | Profile → Application Email section |
| 6. Add jobs (manually, CSV import, or Find Jobs) | Jobs page |
| 7. Click **Send All** | Jobs page |

---

## 3. User Guide

### 3.1 Dashboard

The dashboard is your activity hub. It shows:

| Widget | Description |
|--------|-------------|
| **Total / Pending / Sent / Failed** | Live counts of all your job applications by status |
| **Sent Rate** | Percentage of applications successfully delivered |
| **This Week vs Last Week** | Applications added this week compared to last week |
| **30-Day Activity Chart** | Daily bar chart of total added vs. sent applications |
| **Top Companies** | The 5 companies you have applied to most |
| **Recent Activity** | Last 10 sent or failed applications with timestamps |
| **Email Tracking** | How many emails were opened and clicked (if tracking is enabled) |
| **Pipeline Breakdown** | Count of applications in each pipeline stage |

The dashboard is cached per user for 60 seconds — data refreshes automatically on next load.

---

### 3.2 Profile Setup

**Navigate to:** Sidebar → Profile

Your profile stores the personal information that is used in email templates and job matching.

| Field | Description |
|-------|-------------|
| Full Name | Used as `{your_name}` in emails |
| Email | Contact email used as `{your_email}` |
| Phone | Used as `{your_phone}` |
| Country / State / District | Composed into your `{your_location}` |
| Preferred Role | Pre-fills the job search field; also used for ATS analysis |
| Skills | Comma-separated list — matched against job descriptions in search results |
| Bio | Short professional summary (for your reference) |
| LinkedIn URL | Your LinkedIn profile link |
| Portfolio URL | Your portfolio or personal website |
| Profile Photo | Optional avatar shown in the UI |

Click **Save Profile** after making changes.

---

### 3.3 Resumes & Cover Letters

BulkApply supports a **library** of resumes and cover letters so you can tailor applications by role.

#### Resumes

- **Upload** a resume (PDF, DOCX, or DOC formats supported, up to the configured size limit).
- You can upload **multiple** resumes.
- Mark one as **Default** — this is automatically attached to new applications.
- The **Auto-parse** button (if enabled) reads the file and fills in your name, email, phone, and skills automatically.
- Delete resumes you no longer need.

#### Cover Letters

- **Upload** a cover letter file, OR paste the cover letter text directly.
- Multiple cover letters are supported.
- Mark one as **Default**.
- The default cover letter is attached to all new applications automatically.

> **Important:** You must have at least one resume **and** one cover letter before you can send applications.

---

### 3.4 Email Configuration

**Navigate to:** Profile → Email Sender section

BulkApply sends applications from **your own email address** using SMTP/IMAP credentials. This ensures emails appear personal and pass spam filters.

| Field | Description |
|-------|-------------|
| Email (Username) | Your Gmail or other SMTP email address |
| Password / App Password | Your email password or an app-specific password |
| From Name | The name recipients see (e.g. "Jane Doe") |

> **Gmail users:** Enable 2-Step Verification in Google Account, then create an **App Password** (Google Account → Security → App Passwords). Use that 16-character code as your password here. Never use your main Gmail password.

To disconnect your email, click **Disconnect Email**.

The email password is stored **encrypted** and is never transmitted back to the browser.

#### Application Email (Subject & Body)

Write the default subject and body that will be used for all applications. Use **placeholders** (see [Section 5](#5-email-template-placeholders)) to personalise each email automatically.

Example subject: `Application for {job_title} at {company}`

---

### 3.5 Sending Schedule & Rate Limits

Control exactly when and how fast BulkApply sends emails.

| Setting | Description |
|---------|-------------|
| Send Start Hour | Earliest hour to send (0–23, 24-hour clock) |
| Send End Hour | Latest hour to send |
| Weekdays Only | If checked, no emails are sent on Saturday or Sunday |
| Max Emails per Hour | Rate cap. Set to `0` for no limit |
| Follow-up Days | Number of days after sending before an automatic follow-up is sent. Set to `0` to disable |

**Sending window example:** Start 9, End 17, Weekdays Only = emails are sent only Mon–Fri between 9 AM and 5 PM.

---

### 3.6 Job Applications

**Navigate to:** Sidebar → Jobs

This is the main board showing all your application records.

#### Adding Jobs

**Manually:**
1. Click **Add Job**.
2. Fill in Company (required), Job Title, Recruiter Name, Recruiter Email (required), Job URL, Location, Notes.
3. Click **Save**.

**Import from CSV:**
1. Click **Import CSV**.
2. Download the **CSV Template** to see the required format.
3. Fill in your data and upload the file.
4. The system automatically skips rows with missing company/email and detects duplicates (same email + company).

See [Section 6](#6-csv-import-format) for the full import format.

**From Job Search:**
1. Use the [Find Jobs](#310-find-jobs-job-search) page to search live listings.
2. Select jobs and click **Auto Apply** to import them directly.

#### Managing Jobs

| Action | How |
|--------|-----|
| Search | Type in the search bar — filters by company, title, recruiter name/email, location |
| Filter by status | Click Pending / Sent / Failed pill buttons |
| Filter by pipeline | Use the pipeline dropdown |
| Sort | Click column headers (Company, Job Title, Status, Date) |
| Preview email | Click the eye icon next to a job to preview the rendered email before sending |
| Send one job | Click the send icon on any pending/failed row |
| Delete | Click the trash icon |
| Export to CSV | Click **Export CSV** — downloads all applications with tracking data |
| Clear all | Click **Clear All** (permanent, no undo) |

#### Application Statuses

| Status | Meaning |
|--------|---------|
| **Pending** | Added, not yet sent |
| **Queued** | In the background queue, being sent now |
| **Sent** | Email delivered successfully |
| **Failed** | Send attempt failed (see error message for details) |

Hover over a **Failed** row to see the error reason. Fix the issue (wrong email, credential problem) then retry.

---

### 3.7 Bulk Send

1. Make sure your profile has a resume, cover letter, and connected email.
2. Click **Send All** on the Jobs page.
3. Optionally select an **Email Template** from the dropdown before sending.
4. All **Pending** and **Failed** jobs are queued and sent in the background.
5. A progress bar appears at the top showing total / processed / failed counts.
6. Click **Cancel Send** to stop remaining queued emails. Jobs already dispatched to a worker finish normally; the rest revert to Pending.

> **Plan limits:** If your plan has a monthly email limit, the batch is automatically capped. You will see a message if some jobs were skipped because the quota was reached.

---

### 3.8 Pipeline Tracking

After an application is sent, drag it through pipeline stages to track your progress:

| Stage | Meaning |
|-------|---------|
| **Applied** | Application sent |
| **Replied** | Recruiter has responded |
| **Interview** | Interview scheduled |
| **Rejected** | Position rejected |
| **Offer** | Offer received |

Update the pipeline status by using the pipeline dropdown on each row in the Jobs table or by dragging the card on the Kanban view.

---

### 3.9 Email Templates

**Navigate to:** Sidebar → Templates

Create reusable email templates for different job types or industries.

- **Create** a template with a Name, Subject, and Body.
- Mark one as **Default** — it will be pre-selected on the bulk send dialog.
- Use any of the [template placeholders](#5-email-template-placeholders) in both subject and body.
- **Edit** or **Delete** templates at any time.

When sending, you can select any template from the dropdown. If none is selected, your Profile's default subject and body is used.

---

### 3.10 Find Jobs (Job Search)

**Navigate to:** Sidebar → Find Jobs

> This feature must be enabled by the administrator.

Search live job listings from multiple sources and apply in one click.

#### Searching

Fill in any combination of:

| Field | Description |
|-------|-------------|
| Role / Keywords | Job title or keywords (e.g. "React Developer") |
| Location | City, state, or country |
| Company | Filter results to a specific company name |
| Preferred Sites | Pre-selected from your profile (Indeed, LinkedIn, Glassdoor, etc.) |
| Sort By | Relevance, Date, or Salary |
| Full-time only | Filter to full-time positions |
| Find Contacts | Attempt to find recruiter email addresses |

Results show job title, company, location, source, and **skill match badges** — skills in the job description that match your profile's skill list are highlighted.

#### Skill Matching

If you have added skills in your Profile, each job result shows:
- **Matched skills** — skills from your profile that appear in the job description (green badges)
- **Other skills** — skills mentioned in the job but not in your profile (grey badges)

#### Sourcing

Jobs are pulled from:
- **Adzuna** — aggregated web job listings
- **Infopark** — Kerala IT park listings
- **Technopark** — Kerala IT park listings
- **Cyberpark** — Kerala IT park listings
- **KINFRA Hi-Tech Park Kozhikode**
- **Smart City Kozhikode**
- **Malabar Business Center**
- **Chrome Extension** — LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse, Lever (if extension installed)

Which sources are active is controlled by the admin via [Feature Flags](#416-feature-flags).

#### Auto Apply

1. Tick the checkboxes next to the jobs you want to apply for.
2. Optionally choose a specific resume from the dropdown.
3. Click **Auto Apply**.
4. Jobs with a recruiter email are queued for immediate email delivery.
5. Jobs without an email (portal-only) are added as Pending with the apply link — you visit them manually.
6. You are redirected to the Jobs page with a summary message.

---

### 3.11 Company Replies (Inbox)

**Navigate to:** Sidebar → Replies

BulkApply syncs your email inbox via IMAP to show recruiter replies alongside the original application.

#### Connecting

Your Gmail (or other) credentials entered in the Profile's Email Sender section are used for IMAP sync automatically.

#### Using the Inbox

- Replies are displayed newest-first.
- Each reply shows sender name, email, subject, snippet, and when it arrived.
- Replies are matched to their original application — company name and job title appear on the card.
- Click **Sync Now** to pull new replies immediately (limited to 6 syncs per minute).
- Use the **Search** box to search by subject, sender name, or email.
- Toggle **Unread only** to show just unread replies.
- Click a reply to mark it as read.
- Click **Mark All Read** to clear the unread badge in one action.

#### Counts shown

| Counter | Meaning |
|---------|---------|
| Total | All synced replies |
| Unread | Replies you haven't read yet |
| Matched | Replies successfully linked to an application |

---

### 3.12 Resume ATS Checker

**Navigate to:** Sidebar → ATS Check

> This feature must be enabled by the administrator.

Automatically analyse your uploaded resume for ATS (Applicant Tracking System) friendliness.

- The tool reads your currently active resume.
- It checks formatting, keywords, section headings, file compatibility, and more.
- A report is displayed showing your score and suggestions for improvement.
- The analysis uses your **Preferred Role** from your profile to compare relevant keywords.

> If you have not uploaded a resume yet, the page will prompt you to do so on the Profile page first.

---

### 3.13 Company Insights

**Navigate to:** Any job application → Company Insights link

> This feature must be enabled by the administrator.

For any company, view:
- Employee LinkedIn profiles (who works there, their roles)
- Culture links — Glassdoor reviews, Crunchbase profiles, and similar reference pages

This helps you tailor your application and prepare for interviews.

---

### 3.14 Saved Search Alerts

On the **Find Jobs** page, after performing a search:

1. Click **Save This Search**.
2. Give it a name.
3. Toggle the alert **Active**.
4. The system periodically checks for new job listings matching this search.
5. When new jobs are found, you receive a **notification** in the bell dropdown.

Manage saved searches from the Find Jobs sidebar:
- **Toggle** alerts on/off without deleting the search.
- **Delete** a saved search to stop it permanently.

---

### 3.15 Billing & Plans

**Navigate to:** Sidebar → Billing

View available subscription plans and your current plan status.

#### Submitting a Payment

1. Choose a plan and click **Subscribe**.
2. Make a **UPI payment** to the UPI ID shown on the page.
3. Enter your **Transaction Reference** number.
4. Optionally upload a **screenshot** of the payment confirmation (JPG, PNG, or PDF, max 5 MB).
5. Click **Submit**.
6. An admin reviews the payment. Once approved, your plan is activated automatically.
7. You receive an email notification when approved or rejected.

> You can only have one pending payment request per plan at a time.

#### Subscription Status

| Status | Meaning |
|--------|---------|
| Active | Plan is running; you have full access |
| Expired | Plan has ended; some features may be restricted |
| Trial | Free trial period is active |

---

### 3.16 Support Tickets

**Navigate to:** Sidebar → Support or Contact page

#### Submitting a Ticket

1. Go to the **Contact** page (visible to guests and logged-in users).
2. Fill in your name, email, subject, and message.
3. Optionally attach a file.
4. Submit — a ticket is created and the admin is notified.

#### Managing Your Tickets

1. Go to **Sidebar → Support → My Tickets**.
2. See a list of all your submitted tickets with their status (Open / In Progress / Resolved / Closed).
3. Click a ticket to view the full conversation thread.
4. Add a **reply** to respond to an admin message.
5. Download any attachments shared by the admin.

---

### 3.17 Browser Extension

**Navigate to:** Sidebar → Extension

BulkApply has a Chrome extension that lets you capture job listings directly from:
- LinkedIn
- Indeed
- Naukri
- Glassdoor
- Greenhouse
- Lever

#### Installing

The Extension page shows download / installation instructions. Follow the steps to load the extension in Chrome.

#### Using the Extension

1. Browse to a job listing on any supported site.
2. Click the BulkApply extension icon.
3. The job details (company, title, recruiter info, apply link) are captured.
4. The job is added to your BulkApply job list automatically.

---

### 3.18 Account Settings

**Navigate to:** Top-right avatar → Account Settings

| Setting | Description |
|---------|-------------|
| Name | Update your display name |
| Email | Change your login email |
| Password | Change your account password (requires current password) |

---

### 3.19 Notifications

The **bell icon** in the top navigation bar shows recent notifications:

- Plan activated / rejected
- New jobs found by saved search alerts
- Account status changes
- Admin messages

Click a notification to mark it as read or navigate to the relevant page. Click **Mark All Read** to clear all at once.

---

## 4. Admin Guide

Access the admin panel at `/admin`. You must have the **admin** role to enter.

---

### 4.1 Admin Dashboard

A high-level view of the platform:

| Card | Metric |
|------|--------|
| Total Users | All registered accounts |
| Active Users | Accounts not deactivated |
| New Today | Accounts registered today |
| Total Applications | All job applications across all users |
| Pending / Queued / Sent / Failed | Application status breakdown |
| Email Success Rate | % of applications successfully sent |
| Queue (Active / Failed) | Background jobs waiting or failed |
| Total Resumes | Uploaded resume files |

---

### 4.2 User Management

**Navigate to:** Admin → Users

#### User List

- Search users by name or email.
- See their plan, trial end date, application count, and account status.
- **Export CSV** to download the full user list.

#### User Detail Page

Click any user to see their full profile:

| Action | Description |
|--------|-------------|
| **Toggle Active** | Enable or disable login access for the user |
| **Verify Email** | Manually mark the user's email as verified |
| **Reset Password** | Generate a new temporary password and send it to the user |
| **Change Role** | Promote to admin or demote to regular user |
| **Login As (Impersonate)** | Log in as the user to debug issues. Click **Return to Admin** in the top bar to exit impersonation |
| **Update Trial End Date** | Extend or shorten the trial period |
| **Assign Subscription** | Manually assign a plan to the user |
| **Cancel Subscription** | Remove the user's active plan |
| **Delete User** | Permanently delete the account and all associated data |

---

### 4.3 Subscriptions

**Navigate to:** Admin → Subscriptions

View all active, expired, and cancelled subscriptions across all users. Shows plan name, user, start/end dates, and status.

---

### 4.4 Plans

**Navigate to:** Admin → Plans

Manage the subscription plans available to users.

| Field | Description |
|-------|-------------|
| Name | Plan display name (e.g. "Pro Monthly") |
| Price | Plan price in your currency |
| Duration Days | How many days the plan lasts |
| Email Limit | Max emails per billing period (`null` = unlimited) |
| Description | Features/notes shown on the Billing page |
| Active | Whether the plan is visible to users |

Actions: **Create**, **Edit**, **Toggle Active**, **Delete**.

> Deleting a plan with active subscribers is blocked to protect existing subscriptions.

---

### 4.5 Free Access

**Navigate to:** Admin → Free Access

Grant specific users unlimited free access without a payment.

1. Search for a user by name or email.
2. Select them and set an optional expiry date.
3. Click **Grant Free Access**.

To revoke, click the **Revoke** button next to the user.

---

### 4.6 Payment Requests

**Navigate to:** Admin → Payment Requests

When a user submits a UPI payment, it appears here.

| Column | Description |
|--------|-------------|
| User | Who submitted the request |
| Plan | Which plan they paid for |
| Amount | Amount they paid |
| Transaction Ref | The UPI reference number they entered |
| Screenshot | Download the payment screenshot to verify |
| Status | Pending / Approved / Rejected |

Actions:
- **Approve** — activates the plan for the user and sends a confirmation notification.
- **Reject** — sends a rejection notification to the user explaining the issue.

---

### 4.7 Job Applications (Admin View)

**Navigate to:** Admin → Applications

View **all** job applications across all users.

- Filter and search across the entire dataset.
- **Export CSV** for reporting.
- **Retry** a failed application (re-queues it for sending).
- **Delete** an application record.

---

### 4.8 Resumes (Admin View)

**Navigate to:** Admin → Resumes

View all uploaded resumes across all users.

- See filename, uploader, file size, and upload date.
- **Download** any resume.
- **Delete** a resume (removes the file from storage and the database record).

---

### 4.9 Job Sources

**Navigate to:** Admin → Job Sources

Control which external job sources are active for the Find Jobs search.

| Source | Type |
|--------|------|
| Adzuna | Aggregated web job listings |
| Infopark | Kerala IT park |
| Technopark | Kerala IT park |
| Cyberpark | Kerala IT park |
| KINFRA Hi-Tech Park Kozhikode | Kerala industrial park |
| Smart City Kozhikode | Smart city tech park |
| Malabar Business Center | Regional business center |
| LinkedIn (extension) | Via Chrome extension |
| Indeed (extension) | Via Chrome extension |
| Naukri (extension) | Via Chrome extension |
| Glassdoor (extension) | Via Chrome extension |
| Greenhouse (extension) | Via Chrome extension |
| Lever (extension) | Via Chrome extension |

- **Toggle** any source on or off.
- **Reorder** sources by dragging — this controls the order results appear in the UI.

---

### 4.10 Queue Monitor

**Navigate to:** Admin → Queue

Monitor background job processing (the engine that sends emails).

| Tab | Description |
|-----|-------------|
| Active Batches | In-progress bulk send batches — shows total, processed, failed, and cancelled counts |
| Failed Jobs | Jobs that threw unhandled exceptions |

Actions:
- **Cancel Batch** — stops a running bulk send.
- **Delete Failed Job** — removes a failed job record after investigation.

---

### 4.11 Analytics & Reports

#### Analytics

**Navigate to:** Admin → Analytics

Platform-wide charts:
- New user signups over time
- Applications sent per day
- Email open and click rates

#### Reports

**Navigate to:** Admin → Reports

Pre-built exportable reports:

| Report | Contents |
|--------|----------|
| User Report | All users with plan, subscription, and activity data |
| Application Report | All applications with status and tracking data |
| Revenue Report | Payment requests by status and amount |

Click **Export** on any report to download as CSV.

---

### 4.12 Support Management

**Navigate to:** Admin → Support

View all user support tickets:

- See ticket subject, user, status, and created date.
- Click a ticket to open the conversation thread.
- **Reply** to the user directly from the admin panel.
- **Update Status** — move the ticket through: Open → In Progress → Resolved → Closed.
- **Download Attachments** that users uploaded.

---

### 4.13 CMS Pages

**Navigate to:** Admin → CMS

Manage public static pages accessible at `/p/{slug}`.

Use cases: Terms of Service, Privacy Policy, FAQ, About page, etc.

| Field | Description |
|-------|-------------|
| Title | Page title shown in the browser tab and heading |
| Slug | URL path (e.g. `terms` → `/p/terms`) |
| Content | HTML content (rich text editor) |
| Published | Whether the page is publicly accessible |

Actions: **Create**, **Edit**, **Delete**.

---

### 4.14 API Configuration

**Navigate to:** Admin → API

View and update API credentials used by the platform.

Currently managed configs:
- **Adzuna API** — App ID and API key for the Adzuna job search integration

Update credentials by clicking the edit icon next to each config key.

---

### 4.15 Webhooks

**Navigate to:** Admin → Webhooks

When users configure a Webhook URL in their profile, BulkApply fires a POST request to that URL on key events (e.g. email sent, email opened).

The Webhooks page shows:
- All recent webhook delivery attempts (URL, event, HTTP status, timestamp)
- **Retry** any failed delivery

---

### 4.16 Feature Flags

**Navigate to:** Admin → Features

Toggle individual platform features and job sources on or off without deploying code.

#### Features

| Flag | Controls |
|------|---------|
| Resume ATS Checker | The `/resume-check` page |
| Job Search page | The `/search` (Find Jobs) page |
| Company Insights | Company insights endpoint |
| Chrome Extension API | The API used by the browser extension |
| Resume auto-parse on upload | Auto-fill profile from resume on Profile page |
| Email open/click tracking | Tracking pixel and click redirect |
| Follow-up emails | Automatic follow-up job |
| Webhook notifications | Outbound webhook calls |
| Saved search alerts | Saved search background checker |
| Automatic Gmail sync | Background IMAP sync job |

#### Job Sources (also shown here)

Each external job source (Adzuna, Infopark, etc.) also appears as a toggleable flag.

Toggle any flag ON or OFF — changes take effect immediately.

---

### 4.17 Site Settings

**Navigate to:** Admin → Settings

Edit platform-wide configuration values. Settings are grouped by category (e.g. Mail, Upload, Branding).

Common settings include:
- **Allowed upload file types and max size** for resumes/cover letters
- **UPI payment ID and payee name** shown on the Billing page
- **Site name and branding** values
- **Mail server defaults**

Each setting shows its label, current value, and a type-appropriate input (text, number, or toggle).

---

### 4.18 Security

**Navigate to:** Admin → Security

#### Two-Factor Authentication (Admin 2FA)

Enable 2FA for the admin account:

1. Click **Enable Two-Factor Auth**.
2. Scan the QR code with Google Authenticator or any TOTP app.
3. Enter the code shown in the app to **Confirm** and activate 2FA.
4. Click **Disable** to turn it off (requires confirmation).

#### IP Rules

Restrict admin panel access by IP address:

| Rule Type | Effect |
|-----------|--------|
| **Allowlist** | Only listed IPs can access the admin panel |
| **Blocklist** | Listed IPs are denied access everywhere |

Add an IP rule by entering the IP address (IPv4 or CIDR range), selecting the type, and clicking **Add Rule**. Delete rules you no longer need.

> **Warning:** Adding an allowlist rule without including your own IP will lock you out. Test carefully.

---

### 4.19 Audit Logs

**Navigate to:** Admin → Audit Logs

A chronological log of all significant administrative actions:
- Who performed the action
- What was changed (model type, ID, before/after values)
- When it happened
- IP address of the actor

Actions logged include: settings changes, user role changes, plan changes, payment approvals/rejections, and more.

---

### 4.20 Backup

**Navigate to:** Admin → Backup

#### Running a Backup

Click **Run Backup Now** to create an immediate database snapshot. The backup runs in the background and appears in the list when complete.

#### Managing Backups

| Column | Description |
|--------|-------------|
| Filename | Timestamped archive file |
| Size | File size |
| Created | When the backup was taken |

Actions:
- **Download** — download the backup archive to your local machine.
- **Delete** — permanently remove a backup file.

Backups are also configurable to run on a schedule via the server's cron (set up by the system administrator).

---

### 4.21 Storage

**Navigate to:** Admin → Storage

View disk usage statistics:
- Total disk space, used space, and free space
- Breakdown by storage category (documents, avatars, payment screenshots, backups, cache)

Click **Clean Cache** to clear cached view data and free disk space. This does not affect user files.

---

### 4.22 Monitoring & Logs

#### Monitoring

**Navigate to:** Admin → Monitoring

Real-time system health indicators:
- Server uptime
- PHP version
- Queue worker status
- Recent job failure rate
- Memory and CPU indicators

#### Logs

**Navigate to:** Admin → Logs

View the Laravel application log file:
- Shows recent log entries (errors, warnings, info messages)
- Useful for debugging issues reported by users

---

### 4.23 Database Tools

**Navigate to:** Admin → Database Tools

Run safe Artisan maintenance commands from the UI:

| Tool | Description |
|------|-------------|
| Run Migrations | Apply any pending database migrations |
| Clear Cache | Flush the application cache |
| Clear Config | Rebuild the configuration cache |
| Clear Views | Rebuild the compiled view cache |
| Optimize | Run `php artisan optimize` |

> These commands are safe to run on a live system. They do not delete user data.

---

### 4.24 Notifications (Admin)

**Navigate to:** Admin → Notifications

System-generated alerts for admins:
- New payment requests submitted by users
- Anomalies detected by monitoring

Mark individual notifications or all as read.

---

### 4.25 Browser Extension (Admin)

**Navigate to:** Admin → Extension

View statistics about browser extension usage:
- Number of jobs captured via the extension per source (LinkedIn, Indeed, etc.)
- Active extension users

This page also shows configuration settings for the Extension API.

---

## 5. Email Template Placeholders

Use these tags in your email subject and body. They are replaced with real values at send time.

| Placeholder | Replaced With |
|-------------|--------------|
| `{job_title}` | The job title (falls back to "the role" if blank) |
| `{company}` | Company name |
| `{recruiter_name}` | Recruiter name (falls back to "Hiring Manager" if blank) |
| `{location}` | Job location |
| `{job_url}` | URL of the job listing |
| `{your_name}` | Your full name (from Profile) |
| `{your_location}` | Your location (from Profile) |
| `{your_email}` | Your email address (from Profile) |
| `{your_phone}` | Your phone number (from Profile) |

**Example:**

```
Subject: Application for {job_title} at {company}

Dear {recruiter_name},

I am writing to apply for the {job_title} position at {company}.
Please find my resume and cover letter attached.

I look forward to hearing from you.

Best regards,
{your_name}
{your_phone} | {your_email}
```

---

## 6. CSV Import Format

Download the template from **Jobs → Import CSV → Download Template** to get a pre-formatted file.

#### Required Columns

| Column | Required | Notes |
|--------|----------|-------|
| `company` | **Yes** | Company name |
| `recruiter_email` | **Yes** | Must be a valid email address |
| `job_title` | No | Role/position title |
| `recruiter_name` | No | Contact person's name |
| `job_url` | No | Link to the job listing |
| `location` | No | City or region |
| `notes` | No | Any notes you want to attach |

#### Column Name Aliases

The importer is flexible — these alternative column names are accepted:

| Alias | Maps To |
|-------|---------|
| `company_name`, `organization`, `employer` | `company` |
| `title`, `role`, `position` | `job_title` |
| `recruiter`, `contact`, `contact_name`, `name` | `recruiter_name` |
| `email`, `recruiter_mail`, `mail`, `contact_email` | `recruiter_email` |
| `url`, `link`, `job_link` | `job_url` |
| `city`, `place` | `location` |
| `note`, `comments`, `remark` | `notes` |

#### Import Behaviour

- Rows missing `company` or a valid `recruiter_email` are **skipped**.
- Duplicate entries (same email + same company already in your list) are **skipped**.
- A summary is shown after import: imported count, skipped count, duplicate count.
- Maximum file size: 5 MB.

---

## 7. Frequently Asked Questions

**Q: My emails are going to spam. What can I do?**
A: Use a Gmail App Password and ensure your profile's "From Name" matches your real name. Avoid spam trigger words in your subject line. Warm up your sending by starting with small batches.

**Q: Can I send to many companies at once?**
A: Yes. Add all your target companies to the Jobs list, then click **Send All**. The system queues them and sends in the background respecting your rate limits and sending window.

**Q: What happens if a send fails?**
A: The application is marked **Failed** with an error message. Fix the issue (usually a wrong email address or expired mail credentials), then retry it individually or include it in the next bulk send.

**Q: Can I use multiple resumes?**
A: Yes. Upload as many resumes as you like. The **default** resume is attached automatically to new applications. When using Auto Apply from job search, you can choose which resume to use.

**Q: How do I know if a recruiter opened my email?**
A: If the admin has enabled Email Tracking, your Dashboard shows **Opened** and **Clicked** counts, and the Jobs table shows timestamps per application. Note: email clients that block tracking pixels won't register an open.

**Q: How do follow-up emails work?**
A: Set a number of days in your Profile's **Follow-up Days** field. After that many days, if the application has not received a reply, a follow-up email is automatically sent. Set to `0` to disable follow-ups.

**Q: My trial expired. What do I do?**
A: Go to **Billing**, choose a plan, make a UPI payment, and submit the payment request. An admin will activate your plan, typically within a few hours.

**Q: Can the admin see my emails or resume content?**
A: Admins can see the metadata of your applications (company, status, dates) and can download uploaded resume files. The content of sent emails is not stored on the server.

**Q: How do I stop a bulk send in progress?**
A: Click **Cancel Send** on the Jobs page. Jobs already picked up by the background worker finish normally; all remaining queued jobs are cancelled and reset to Pending.

---

*BulkApply — Automate your job search, land more interviews.*
