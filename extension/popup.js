// The URL where your BulkApply instance is running
const API_URL = 'http://127.0.0.1:8080';

document.addEventListener('DOMContentLoaded', () => {
    const loginView = document.getElementById('loginView');
    const mainView = document.getElementById('mainView');
    const loginBtn = document.getElementById('loginBtn');
    const logoutBtn = document.getElementById('logoutBtn');
    const saveBtn = document.getElementById('saveBtn');
    const scrapeBtn = document.getElementById('scrapeBtn');

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

    // Scrape Page
    scrapeBtn.addEventListener('click', () => {
        chrome.tabs.query({ active: true, currentWindow: true }, (tabs) => {
            if (tabs[0].url.includes('linkedin.com') || tabs[0].url.includes('indeed.com')) {
                chrome.tabs.sendMessage(tabs[0].id, { action: "scrape" }, (response) => {
                    if (chrome.runtime.lastError) {
                        console.error(chrome.runtime.lastError);
                        return;
                    }
                    if (response) {
                        document.getElementById('jobCompany').value = response.company || '';
                        document.getElementById('jobTitle').value = response.title || '';
                        document.getElementById('jobLocation').value = response.location || '';
                    }
                });
            } else {
                document.getElementById('saveStatus').style.color = '#ef4444';
                document.getElementById('saveStatus').textContent = 'Please visit a LinkedIn or Indeed job page.';
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
                const source = url.includes('linkedin') ? 'LinkedIn' : url.includes('indeed') ? 'Indeed' : 'Web';

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
                            source: source
                        })
                    });

                    const data = await res.json();
                    
                    if (res.ok) {
                        statusDiv.style.color = '#10b981';
                        statusDiv.textContent = data.message || 'Job saved!';
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
