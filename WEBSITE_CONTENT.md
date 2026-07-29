# BulkApply — Website Content

Every piece of public-facing copy on the marketing site, pulled from source so it can be reviewed or edited without opening the browser. Two sources feed the site:

- **Hardcoded in React** — the home page ([Welcome.jsx](resources/js/Pages/Welcome.jsx)) and the contact page ([Contact.jsx](resources/js/Pages/Contact.jsx)). Editing this copy means editing those files.
- **CMS-managed in the database** — Pricing/FAQ/Privacy/Terms/About/Features/Home/Contact all also exist as rows in the `cms_pages` table ([Page.jsx](resources/js/Pages/Page.jsx) renders them), editable from the admin CMS without a deploy. This document could not reach the live database from this environment, so those sections below show the seeded placeholder text ([CmsPageSeeder.php](database/seeders/CmsPageSeeder.php)), not necessarily what's live today — check the admin CMS for the current text.

---

## Meta / SEO

- **Title:** Bulk Job Application Automation for Kerala Tech Jobs
- **Description:** Search Technopark, Infopark, Cyberpark and the wider web in one place, send personalised applications with your resume in one click, and track every open, click and reply. Free for 7 days.
- **OG title:** BulkApply — Apply to hundreds of jobs, on autopilot
- **OG description:** Search Kerala's tech parks and the whole web, bulk-apply with a personalised email, and track your pipeline from one dashboard.

## Navigation

- Brand: **BulkApply**
- Links: Features · How it works · Pricing · FAQ
- CTAs: Log in · Start free trial

## Hero

- Eyebrow: *Built for Kerala's tech job market*
- **H1:** Apply to hundreds of jobs. Track every reply. On autopilot.
- Lead: BulkApply searches Technopark, Infopark, Cyberpark and the wider web in one place, sends a personalised application with your resume attached in one click, and tracks every open, click and reply — all from one dashboard.
- CTAs: Start your 7-day free trial · See how it works
- Trust row: No credit card required · 7-day free trial · Self-hosted · pay by UPI

## Why bulk apply (problem vs. solution)

- Kicker: *Why bulk apply*
- **H2:** Same job boards. A completely different experience.
- Sub: You don't need another tab to check — you need one place that already checked them all.

**Applying the old way**
- Ten tabs open, one job found
- The same resume for every role
- No idea if anyone ever opened it
- Hours lost copy-pasting details
- *The tabs you'd normally juggle, one by one:* Technopark, Infopark, Cyberpark, LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse, Lever

**Applying with BulkApply**
- One dashboard for every platform
- A personalised resume & message, every send
- Know the moment it's opened or clicked
- One click to apply, not one hour
- *All searched and saved from one dashboard:* same nine sources as above

## How it works

- Kicker: *How it works*
- **H2:** Three steps, not three hundred tabs
- Sub: No more copy-pasting your resume into forty different portals one at a time.

1. **Search once, everywhere** — Pull live listings straight from Technopark, Infopark and Cyberpark, or search LinkedIn, Indeed and Naukri — or paste any company's careers page.
2. **Apply in one click** — Select the roles you want. BulkApply sends a personalised email per job — your resume attached, your template's placeholders filled in automatically.
3. **Track what happens next** — See opens, clicks and Gmail replies matched straight to each application, and move every one through Applied → Replied → Interview → Offer.

## Feature showcase

- Kicker: *Your dashboard*
- **H2:** Every application, one screen

**Know exactly where your job search stands**
Total jobs, sent count, success rate and open rate at a glance — plus a day-by-day activity chart and a pipeline breakdown so you always know how many replies are sitting in Interview versus Offer.
- Success rate and open/click tracking per email sent
- This week vs. last week, so you can tell if you're slowing down
- Recent activity feed of every send, right down to the recruiter

**Search Kerala's tech parks — and everywhere else**
Technopark, Infopark and Cyberpark pull live listings directly. Everything else — LinkedIn, Indeed, Naukri, or a specific company's careers page — pulls matching results from across the web, in the same search box.
- One search box for tech parks, job boards, or a single company
- Sort by relevance, most recent, or highest salary
- Optional "find company emails" lookup for direct outreach

**Never lose track of an application again**
Import a CSV of leads or add jobs by hand, then send everything pending in one batch. Every row keeps its own status, pipeline stage and tracking — nothing falls through the cracks in a spreadsheet.
- Bulk send with a live progress bar, or send one row at a time
- Move each application through your pipeline as it progresses
- Export to CSV any time — your data is never locked in

## Secondary feature grid

- Kicker: *And everything around it*
- **H2:** The parts that make bulk applying actually work

| Feature | Copy |
|---|---|
| Resume ATS check | See exactly how your resume survives an Applicant Tracking System, with a ranked list of what to fix first before you start applying. |
| Reusable email templates | Write once, personalise every time — templates auto-fill the job title, company, recruiter and your details from placeholders. |
| Browser extension | Save a job straight from LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse or Lever to your dashboard in one click as you browse. |
| Gmail reply matching | Sync your inbox and every recruiter reply is matched automatically to the application it answers — no manual searching. |
| Two-factor authentication | Your resume, applications and email account stay protected with optional 2FA on every sign-in. |
| Self-hosted, your data | Nothing about your job search lives on someone else's growth dashboard. Billing is simple UPI — no card required. |

## Final CTA

- Kicker: *Get started*
- **H2:** Your dashboard is one sign-up away
- Copy: Create an account, add your resume, and run your first search — free for 7 days, no credit card needed. Already have one? Sign back in and pick up where you left off.
- CTAs: Start your 7-day free trial · Log in
- Note: After your trial, plans are billed simply by UPI — 1, 3 or 9 months, no auto-renewal surprises.

## Footer

- Links: Features · How it works · Pricing · FAQ · Privacy · Terms · Contact
- © {year} BulkApply. A self-hosted job application automation platform.
- Made for job seekers in Kerala and beyond.

---

## Pricing (`/p/pricing`)

Plan tiers are pulled live from the `plans` table, not hand-typed. As of the last check, the active tiers were:

| Plan | Price | Duration |
|---|---|---|
| Free *(Start here)* | Free | 7-day trial |
| 1 Month | ₹9.99 | 1 Month |
| 3 Month | ₹24.99 | 3 Months |
| 9 Month | ₹79.99 | 9 Months |

Every tier includes: *Every feature, unlimited use* · *Unlimited resumes & applications*.

Below the cards, the page renders the CMS `pricing` page body (currently seeded placeholder — check admin CMS for live text).

## FAQ (`/p/faq`), Privacy (`/p/privacy`), Terms (`/p/terms`), About (`/p/about`), Features (`/p/features`), Home CMS variant, Contact CMS variant

These are all `cms_pages` rows rendered through the same [Page.jsx](resources/js/Pages/Page.jsx) template. The seeder's fallback text for each is:

> "This is the {Title} page. Edit this content from the admin CMS section."

That's only ever used the first time a row is created — if it's been edited in the admin CMS since, this document doesn't reflect that. Pull the current text with:

```bash
php artisan tinker --execute="App\Models\CmsPage::all(['slug','title','content'])->each(fn(\$p) => print_r(\$p->toArray()));"
```

## Contact page (`/contact`)

Static React page, not CMS-managed.

- **H2:** Contact us
- Form fields: Your name · Your email · Subject (optional) · Message
- Success message (after submit): "Thanks — we've received your message and will get back to you soon."
