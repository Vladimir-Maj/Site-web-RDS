(async function() {
    const offerListEl = document.querySelector('.offer-list');

    // 1. Define the CDN Base
    // Use the global config if available, otherwise default to HTTPS for security
    const cdnBase = (window.APP_CONFIG && window.APP_CONFIG.cdnUrl)
        ? window.APP_CONFIG.cdnUrl + '/'
        : 'https://cdn.stageflow.fr/';

    const assetsBase = cdnBase + 'assets/';

    // Map state -> tag class
    const stateTagMap = {
        open: 'tag-green',
        pending: 'tag-amber',
        draft: 'tag-slate'
    };

    try {
        // 2. Load the card template using the secure CDN path
        // Added a timestamp to the URL to prevent caching issues during development
        const templateHtml = await fetch(assetsBase + 'elements/card_template.html?v=' + Date.now())
            .then(r => r.text());

        // 3. Update JSON paths to use absolute CDN URLs
        const jsonFiles = [
            assetsBase + 'offers/offer1.json',
            assetsBase + 'offers/offer2.json',
            assetsBase + 'offers/offer3.json'
        ];

        const dataList = await Promise.all(
            jsonFiles.map(path => fetch(path).then(r => {
                if (!r.ok) throw new Error(`Failed to load ${path}`);
                return r.json();
            }))
        );

        const offers = dataList.flat();

        offers.forEach(offer => {
            let cardHtml = templateHtml;

            // Replace placeholders
            cardHtml = cardHtml
                .replace('{{STAGE_NAME}}', offer.company_name)
                .replace('{{STAGE_POSITION}}', offer.location)
                .replace('{{STAGE_COMPANY}}', offer.company_name)
                .replace('{{STAGE_DATE}}', offer.date)
                .replace('{{STAGE_DESC}}', offer.desc)
                .replace('{{STAGE_STATUS}}', offer.state.toUpperCase())
                .replace('{{STAGE_TAG_CLASS}}', stateTagMap[offer.state] || 'tag-slate');

            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = cardHtml;
            const cardEl = tempDiv.firstElementChild;

            // Check if container exists before appending
            if (offerListEl) {
                offerListEl.appendChild(cardEl);
            }
        });
    } catch (err) {
        // Improved error logging to help identify if it's a CORS or 404 issue
        console.error("Error loading offer data from CDN:", err);
    }
})();