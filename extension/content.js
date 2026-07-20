chrome.runtime.onMessage.addListener((request, sender, sendResponse) => {
    if (request.action === "scrape") {
        const url = window.location.href;
        let data = {
            company: "",
            title: "",
            location: ""
        };

        if (url.includes("linkedin.com")) {
            // LinkedIn Jobs
            // Selectors can change frequently, these are common classes for LinkedIn job pages
            const titleEl = document.querySelector('.job-details-jobs-unified-top-card__job-title h1') 
                         || document.querySelector('.top-card-layout__title');
            
            const companyEl = document.querySelector('.job-details-jobs-unified-top-card__company-name a') 
                           || document.querySelector('.topcard__org-name-link');
            
            const locationEl = document.querySelector('.job-details-jobs-unified-top-card__primary-description span:first-child') 
                            || document.querySelector('.topcard__flavor--bullet');

            data.title = titleEl ? titleEl.innerText.trim() : "";
            data.company = companyEl ? companyEl.innerText.trim() : "";
            data.location = locationEl ? locationEl.innerText.trim() : "";

        } else if (url.includes("indeed.com")) {
            // Indeed Jobs
            const titleEl = document.querySelector('.jobsearch-JobInfoHeader-title span');
            const companyEl = document.querySelector('div[data-company-name="true"] a')
                           || document.querySelector('.jobsearch-CompanyInfoContainer a');
            const locationEl = document.querySelector('div[data-testid="inlineHeader-companyLocation"]');

            data.title = titleEl ? titleEl.innerText.trim() : "";
            data.company = companyEl ? companyEl.innerText.trim() : "";
            data.location = locationEl ? locationEl.innerText.trim() : "";
        }

        sendResponse(data);
    }
});
