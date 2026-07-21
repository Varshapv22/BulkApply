import React from 'react';
import { PageHead, ChipIcon, Icons } from '../components';

export default function Extension() {
    return (
        <>
            <PageHead title="Browser Extension" subtitle="Save jobs directly from LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse and Lever to your dashboard." />
            
            <div className="card">
                <h2>Download & Install</h2>
                <p>Because this extension is not yet published to the Chrome Web Store, you need to load it manually. It only takes 30 seconds.</p>
                
                <div style={{ margin: '20px 0' }}>
                    <a href="/downloads/bulkapply-extension.zip" download className="btn btn-primary btn-lg">
                        <ChipIcon icon={Icons.upload} /> Download Extension (.zip)
                    </a>
                </div>

                <h3>Installation Steps</h3>
                <ol style={{ lineHeight: '1.6' }}>
                    <li><strong>Download</strong> the `.zip` file using the button above and <strong>extract</strong> it to a folder on your computer.</li>
                    <li>Open your Chrome or Edge browser and go to <code>chrome://extensions/</code> in the address bar.</li>
                    <li>Turn on <strong>Developer mode</strong> in the top right corner.</li>
                    <li>Click the <strong>Load unpacked</strong> button in the top left.</li>
                    <li>Select the folder you extracted in step 1.</li>
                </ol>

                <div className="alert alert-info" style={{ marginTop: 20 }}>
                    <div className="alert-body">
                        <strong>Tip:</strong> Pin the extension to your toolbar for easy access! Click the puzzle icon 🧩 in Chrome, then click the pin 📌 next to BulkApply.
                    </div>
                </div>
            </div>

            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.sparkle} /></span>
                    <div>
                        <h2 style={{ margin: 0 }}>How to use the Extension</h2>
                    </div>
                </div>
                
                <ol style={{ lineHeight: '1.6' }}>
                    <li>Click the BulkApply icon in your browser toolbar.</li>
                    <li>Log in using your BulkApply dashboard <strong>Email and Password</strong>.</li>
                    <li>Visit any job posting on <strong>LinkedIn, Indeed, Naukri, Glassdoor, Greenhouse,</strong> or <strong>Lever</strong>.</li>
                    <li>Click the extension icon again — it will automatically scrape the Company, Job Title, and Location!</li>
                    <li>Click <strong>Save to Dashboard</strong>. The job is now instantly in your BulkApply applications list.</li>
                </ol>
            </div>

            <div className="card hero-card">
                <div className="hero-card-head">
                    <span className="hero-card-ico"><ChipIcon icon={Icons.send} /></span>
                    <div>
                        <h2 style={{ margin: 0 }}>LinkedIn Easy Apply assist</h2>
                    </div>
                </div>

                <p>On a LinkedIn job with an Easy Apply button, the extension shows an <strong>Auto-fill Easy Apply</strong> option after you save the job. It opens the application and fills in what it can confidently match from your profile — name, email, phone, and an existing resume if LinkedIn offers a choice.</p>

                <div className="alert alert-warn">
                    <div className="alert-body">
                        <strong>It stops before submitting.</strong> LinkedIn's terms don't allow automated interaction with the platform, and some jobs ask custom screening questions a script has no business guessing answers to — so the assist fills what it's confident about, flags anything it couldn't, and leaves the final <strong>Submit</strong> click to you. It also can't attach a brand-new resume file (browsers don't allow that from an extension) — only select one you've already uploaded, if LinkedIn shows that option.
                    </div>
                </div>
            </div>
        </>
    );
}
