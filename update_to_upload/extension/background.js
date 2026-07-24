// MV3 service worker. Its one job: relay the Easy Apply assist's result to
// the BulkApply backend. This runs here rather than in easyapply.js because
// that content script executes in LinkedIn's page context — a fetch() from
// there to the BulkApply API would be subject to that page's CORS policy,
// while the service worker is an extension-privileged context and isn't.

const API_URL = 'http://127.0.0.1:8080';

chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === 'reportAutoApplyStatus') {
        chrome.storage.local.get(['token'], async ({ token }) => {
            if (!token) {
                sendResponse({ ok: false, error: 'Not logged in.' });
                return;
            }
            try {
                const res = await fetch(`${API_URL}/api/extension/jobs/${request.jobApplicationId}/auto-apply-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        Authorization: `Bearer ${token}`,
                    },
                    body: JSON.stringify({
                        status: request.status,
                        error: request.error || null,
                    }),
                });
                sendResponse({ ok: res.ok });
            } catch (e) {
                sendResponse({ ok: false, error: e.message });
            }
        });
        return true; // keep the message channel open for the async response
    }
});
