// Each adapter matches a job-board hostname and knows how to pull
// company/title/location/description out of that site's DOM. Class names on
// these sites are frequently hashed/regenerated on deploy (CSS modules), so
// selectors below intentionally use [class*="stablePrefix"] partial matches
// rather than full class names wherever the site does that, and fall back to
// document.title parsing for fields (like company) that some ATSs don't
// expose as a discrete DOM node on the job page at all.

function text(el) {
    return el ? el.textContent.trim() : '';
}

function firstMatch(selectors) {
    for (const sel of selectors) {
        const el = document.querySelector(sel);
        if (el) return el;
    }
    return null;
}

const ADAPTERS = {
    linkedin: {
        matches: (url) => url.includes('linkedin.com'),
        source: 'LinkedIn',
        scrape() {
            const titleEl = firstMatch([
                '.job-details-jobs-unified-top-card__job-title h1',
                '.top-card-layout__title',
            ]);
            const companyEl = firstMatch([
                '.job-details-jobs-unified-top-card__company-name a',
                '.topcard__org-name-link',
            ]);
            const locationEl = firstMatch([
                '.job-details-jobs-unified-top-card__primary-description span:first-child',
                '.topcard__flavor--bullet',
            ]);
            const hasEasyApply = Array.from(document.querySelectorAll('button')).some((b) =>
                (b.getAttribute('aria-label') || '').toLowerCase().includes('easy apply')
            );
            return {
                title: text(titleEl),
                company: text(companyEl),
                location: text(locationEl),
                description: '',
                hasEasyApply,
            };
        },
    },

    indeed: {
        matches: (url) => url.includes('indeed.com'),
        source: 'Indeed',
        scrape() {
            const titleEl = document.querySelector('.jobsearch-JobInfoHeader-title span');
            const companyEl = firstMatch([
                'div[data-company-name="true"] a',
                '.jobsearch-CompanyInfoContainer a',
            ]);
            const locationEl = document.querySelector('div[data-testid="inlineHeader-companyLocation"]');
            return {
                title: text(titleEl),
                company: text(companyEl),
                location: text(locationEl),
                description: '',
            };
        },
    },

    naukri: {
        matches: (url) => url.includes('naukri.com'),
        source: 'Naukri',
        scrape() {
            const titleEl = document.querySelector('h1[class*="jd-header-title"]');
            const companyEl = document.querySelector('[class*="jd-header-comp-name"] a');
            const locationEl = firstMatch([
                '[class*="jhc__loc"] a',
                '[class*="jhc__location"]',
            ]);
            const descEl = document.querySelector('[class*="job-desc" i]');
            return {
                title: text(titleEl),
                company: text(companyEl),
                location: text(locationEl),
                description: text(descEl),
            };
        },
    },

    glassdoor: {
        // Glassdoor redirects by region (glassdoor.com -> glassdoor.co.in, etc.)
        matches: (url) => url.includes('glassdoor.'),
        source: 'Glassdoor',
        scrape() {
            const titleEl = document.querySelector('[class*="JobDetails"] h1');
            const companyEl = document.querySelector('[class*="EmployerProfile_employerInfo"]');
            // Glassdoor concatenates "CompanyName4.0" (name + rating) with no separator.
            const companyRaw = text(companyEl);
            const company = companyRaw.replace(/[\d.]+$/, '').trim();
            const locationEl = firstMatch([
                '[class*="SalaryEstimate_location"]',
                '[class*="JobDetails_locationAndPay"]',
            ]);
            return {
                title: text(titleEl),
                company,
                location: text(locationEl),
                description: '',
            };
        },
    },

    greenhouse: {
        // Covers both the legacy boards.greenhouse.io and current job-boards.greenhouse.io.
        matches: (url) => url.includes('greenhouse.io'),
        source: 'Greenhouse',
        scrape() {
            const titleEl = document.querySelector('.job__title h1');
            const locationEl = document.querySelector('.job__location');
            // Company isn't a discrete DOM node on the job page; the tab title
            // reliably follows "Job Application for {title} at {company}".
            const titleMatch = document.title.match(/ at ([^|]+)$/);
            return {
                title: text(titleEl),
                company: titleMatch ? titleMatch[1].trim() : '',
                location: text(locationEl),
                description: '',
            };
        },
    },

    lever: {
        matches: (url) => url.includes('lever.co'),
        source: 'Lever',
        scrape() {
            const titleEl = document.querySelector('.posting-headline h2');
            const locationEl = document.querySelector('.posting-categories .location');
            // Company isn't a discrete DOM node either; the tab title follows
            // "{Company} - {Job Title}".
            const company = document.title.split(' - ')[0].trim();
            return {
                title: text(titleEl),
                company,
                location: text(locationEl),
                description: '',
            };
        },
    },
};

function findAdapter(url) {
    return Object.values(ADAPTERS).find((adapter) => adapter.matches(url));
}

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'scrape') {
        const url = window.location.href;
        const adapter = findAdapter(url);
        const data = adapter
            ? { ...adapter.scrape(), source: adapter.source }
            : { company: '', title: '', location: '', description: '', source: 'Web' };
        sendResponse(data);
    }
});
