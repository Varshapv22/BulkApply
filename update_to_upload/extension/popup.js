// The URL where your BulkApply instance is running
const API_URL = 'http://127.0.0.1:8080';

document.addEventListener('DOMContentLoaded', () => {
    const loginView = document.getElementById('loginView');
    const mainView = document.getElementById('mainView');
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const saveBtn = document.getElementById('saveBtn');
    const scrapeBtn = document.getElementById('scrapeBtn');
    const easyApplyBtn = document.getElementById('easyApplyBtn');

    let scraped = { description: '', source: 'Web', hasEasyApply: false };
    let savedJobId = null;

    // Check auth status
    chrome.storage.local.get(['token'], (result) => {
        if (result.token) {
            showMainView();
        } else {
            showLoginView();
        }
    });

    // Login
    loginBtn.addEventListener('click', async () => {
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('loginError');
        
        loginBtn.disabled = true;
        loginBtn.textContent = 'Logging in...';
        errorDiv.textContent = '';

        try {
            const res = await fetch(`${API_URL}/api/extension/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await res.json();

            if (res.ok && data.token) {
                chrome.storage.local.set({ token: data.token }, () => {
                    showMainView();
                });
            } else {
                errorDiv.textContent = data.message || 'Invalid credentials.';
            }
        } catch (e) {
            errorDiv.textContent = 'Failed to connect to BulkApply server.';
        } finally {
            loginBtn.disabled = false;
            loginBtn.textContent = 'Login';
        }
    });

    // Logout
    logoutBtn.addEventListener('click', () => {
        chrome.storage.local.remove(['token'], () => {
            showLoginView();
        });
    });

    const SUPPORTED_HOSTS = ['linkedin.com', 'indeed.com', 'naukri.com', 'glassdoor.', 'greenhouse.io', 'lever.co'];
    const isSupportedUrl = (url) => SUPPORTED_HOSTS.some((h) => url.includes(h));

    // Scrape Page
    scrapeBtn.addEventListener('click', () => {
        savedJobId = null;
        document.getElementById('easyApplyStatus').textContent = '';
        chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
            if (isSupportedUrl(tabs[0].url)) {
                chrome.tabs.sendMessage(tabs[0].id, { action: "scrape" }, (response) => {
                    if (chrome.runtime.lastError) {
                        console.error(chrome.runtime.lastError);
                        return;
                    }
                    if (response) {
                        document.getElementById('jobCompany').value = response.company || '';
                        document.getElementById('jobTitle').value = response.title || '';
                        document.getElementById('jobLocation').value = response.location || '';
                        scraped.description = response.description || '';
                        scraped.source = response.source || 'Web';
                        scraped.hasEasyApply = !!response.hasEasyApply;
                    }
                    easyApplyBtn.classList.toggle('hidden', !scraped.hasEasyApply);
                });
            } else {
                document.getElementById('saveStatus').style.color = '#ef4444';
                document.getElementById('saveStatus').textContent = 'Please visit a supported job page (LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse, Lever).';
            }
        });
    });

    // Save to Dashboard
    saveBtn.addEventListener('click', async () => {
        const company = document.getElementById('jobCompany').value;
        const title = document.getElementById('jobTitle').value;
        const location = document.getElementById('jobLocation').value;
        const statusDiv = document.getElementById('saveStatus');

        if (!company) {
            statusDiv.style.color = '#ef4444';
            statusDiv.textContent = 'Company name is required.';
            return;
        }

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        statusDiv.textContent = '';

        chrome.storage.local.get(['token'], async (result) => {
            chrome.tabs.query({ active: true, currentWindow: true }, async (tabs) => {
                const url = tabs[0].url;

                try {
                    const res = await fetch(`${API_URL}/api/extension/jobs`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${result.token}`
                        },
                        body: JSON.stringify({
                            company: company,
                            job_title: title,
                            location: location,
                            job_url: url,
                            source: scraped.source,
                            description: scraped.description || null,
                            apply_type: scraped.hasEasyApply ? 'easy_apply' : 'link',
                        })
                    });

                    const data = await res.json();

                    if (res.ok) {
                        statusDiv.style.color = '#10b981';
                        statusDiv.textContent = data.message || 'Job saved!';
                        savedJobId = data.job ? data.job.id : null;
                    } else if (res.status === 401) {
                        chrome.storage.local.remove(['token']);
                        showLoginView();
                    } else {
                        statusDiv.style.color = '#ef4444';
                        statusDiv.textContent = data.message || 'Failed to save.';
                    }
                } catch (e) {
                    statusDiv.style.color = '#ef4444';
                    statusDiv.textContent = 'Failed to connect to server.';
                } finally {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save to Dashboard';
                }
            });
        });
    });

    // Auto-fill Easy Apply
    easyApplyBtn.addEventListener('click', async () => {
        const statusDiv = document.getElementById('easyApplyStatus');
        easyApplyBtn.disabled = true;
        easyApplyBtn.textContent = 'Filling…';
        statusDiv.textContent = '';

        try {
            const { token } = await chrome.storage.local.get(['token']);
            const profileRes = await fetch(`${API_URL}/api/extension/profile`, {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            });
            if (!profileRes.ok) throw new Error('Could not load your profile.');
            const profile = await profileRes.json();

            const tabs = await chrome.tabs.query({ active: true, currentWindow: true });
            const result = await chrome.tabs.sendMessage(tabs[0].id, { action: 'startEasyApply', profile });

            if (result.status === 'failed') {
                statusDiv.style.color = '#ef4444';
                statusDiv.textContent = result.error || 'Could not auto-fill this application.';
            } else {
                statusDiv.style.color = '#10b981';
                statusDiv.textContent = result.unmapped > 0
                    ? `Filled ${result.filled} field(s) — ${result.unmapped} need your input. Review and submit in LinkedIn.`
                    : `Filled ${result.filled} field(s). Review and submit in LinkedIn.`;
            }

            if (savedJobId) {
                chrome.runtime.sendMessage({
                    action: 'reportAutoApplyStatus',
                    jobApplicationId: savedJobId,
                    status: result.status,
                    error: result.error || null,
                });
            }
        } catch (e) {
            statusDiv.style.color = '#ef4444';
            statusDiv.textContent = e.message || 'Something went wrong.';
        } finally {
            easyApplyBtn.disabled = false;
            easyApplyBtn.textContent = 'Auto-fill Easy Apply';
        }
    });

    function showLoginView() {
        loginView.classList.remove('hidden');
        mainView.classList.add('hidden');
    }

    function showMainView() {
        loginView.classList.add('hidden');
        mainView.classList.remove('hidden');
        document.getElementById('scrapeBtn').click();
    }
});
