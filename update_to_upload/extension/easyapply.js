// LinkedIn Easy Apply auto-fill assist.
//
// Fills what it can confidently map from the user's BulkApply profile, then
// STOPS before the final "Submit application" button — the user reviews and
// submits themselves. This is deliberate: LinkedIn's terms prohibit
// automated interaction with the platform, and jobs often carry custom
// screening questions that a script has no business guessing answers to.
// Automating file uploads (a fresh resume) isn't possible from a content
// script at all — browsers don't allow scripts to set a file input's value
// for security reasons — so an existing resume can only be *selected* from a
// list LinkedIn already shows, never uploaded fresh.

function setNativeValue(el, value) {
    const proto = Object.getPrototypeOf(el);
    const setter = Object.getOwnPropertyDescriptor(proto, 'value')?.set;
    if (setter) {
        setter.call(el, value);
    } else {
        el.value = value;
    }
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function labelTextFor(input) {
    if (input.id) {
        const label = document.querySelector(`label[for="${CSS.escape(input.id)}"]`);
        if (label) return label.textContent.trim().toLowerCase();
    }
    const wrappingLabel = input.closest('label');
    if (wrappingLabel) return wrappingLabel.textContent.trim().toLowerCase();
    return (input.getAttribute('aria-label') || input.placeholder || '').toLowerCase();
}

// Keyword -> profile field, checked in order (first match wins).
const FIELD_MATCHERS = [
    { keywords: ['first name'], field: 'firstName' },
    { keywords: ['last name'], field: 'lastName' },
    { keywords: ['full name', 'your name'], field: 'fullName' },
    { keywords: ['email'], field: 'email' },
    { keywords: ['phone', 'mobile'], field: 'phone' },
];

function fillTextFields(modal, profile) {
    const inputs = modal.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input:not([type])');
    let filled = 0;
    let unmapped = 0;

    inputs.forEach((input) => {
        if (input.value) return; // don't clobber anything already filled
        const label = labelTextFor(input);
        const match = FIELD_MATCHERS.find((m) => m.keywords.some((k) => label.includes(k)));

        if (!match) {
            if (input.required) unmapped++;
            return;
        }

        let value = profile[match.field];
        if (!value && match.field === 'firstName') value = (profile.fullName || '').split(' ')[0];
        if (!value && match.field === 'lastName') value = (profile.fullName || '').split(' ').slice(1).join(' ');
        if (!value) {
            if (input.required) unmapped++;
            return;
        }

        setNativeValue(input, value);
        filled++;
    });

    return { filled, unmapped };
}

function selectExistingResume(modal, profile) {
    if (!profile.resumeName) return false;
    const options = modal.querySelectorAll('input[type="radio"]');
    for (const opt of options) {
        const label = labelTextFor(opt) || text(opt.closest('li'));
        if (label && label.toLowerCase().includes(profile.resumeName.toLowerCase())) {
            opt.click();
            return true;
        }
    }
    return false;
}

function text(el) {
    return el ? el.textContent.trim() : '';
}

function findButtonByText(modal, patterns) {
    const buttons = Array.from(modal.querySelectorAll('button'));
    return buttons.find((b) => patterns.some((p) => b.textContent.trim().toLowerCase().includes(p)));
}

function waitForModalChange(modal, timeoutMs = 4000) {
    return new Promise((resolve) => {
        const observer = new MutationObserver(() => {
            observer.disconnect();
            resolve(true);
        });
        observer.observe(modal, { childList: true, subtree: true });
        setTimeout(() => {
            observer.disconnect();
            resolve(false);
        }, timeoutMs);
    });
}

async function runEasyApplyAssist(profile) {
    const openBtn = Array.from(document.querySelectorAll('button')).find((b) =>
        (b.getAttribute('aria-label') || '').toLowerCase().includes('easy apply')
    );
    if (!openBtn) {
        return { status: 'failed', error: 'No Easy Apply button found on this page.' };
    }
    openBtn.click();

    // Wait for the application modal (a dialog, per accessibility roles — far
    // more stable across LinkedIn redesigns than any of its nested classes).
    let modal = null;
    for (let i = 0; i < 10 && !modal; i++) {
        await new Promise((r) => setTimeout(r, 400));
        modal = document.querySelector('div[role="dialog"]');
    }
    if (!modal) {
        return { status: 'failed', error: 'Easy Apply dialog did not open.' };
    }

    let totalFilled = 0;
    let totalUnmapped = 0;

    for (let step = 0; step < 15; step++) {
        const { filled, unmapped } = fillTextFields(modal, profile);
        totalFilled += filled;
        totalUnmapped += unmapped;
        selectExistingResume(modal, profile);

        const submitBtn = findButtonByText(modal, ['submit application', 'submit']);
        if (submitBtn) {
            // Stop here on purpose — see file header. The user reviews and
            // submits themselves.
            break;
        }

        if (totalUnmapped > 0) {
            // A required field on this step needs human input; advancing
            // would either fail validation or skip past it unfilled.
            break;
        }

        const nextBtn = findButtonByText(modal, ['next', 'continue', 'review']);
        if (!nextBtn || nextBtn.disabled) break;

        nextBtn.click();
        await waitForModalChange(modal);
    }

    return {
        status: 'filled',
        filled: totalFilled,
        unmapped: totalUnmapped,
    };
}

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'startEasyApply') {
        runEasyApplyAssist(request.profile)
            .then((result) => sendResponse(result))
            .catch((err) => sendResponse({ status: 'failed', error: err.message }));
        return true; // keep the message channel open for the async response
    }
});
