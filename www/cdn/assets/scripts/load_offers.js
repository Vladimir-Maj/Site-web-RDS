(async function() {
    const offerListEl = document.querySelector('.offer-list');

    // 1. Define the CDN Base
    const cdnBase = 'http://cdn.localhost:8080/';
    const assetsBase = cdnBase + 'assets/';

    // Map state -> tag class
    const stateTagMap = {
        open: 'tag-green',
        pending: 'tag-amber',
        draft: 'tag-slate'
    };

    try {
        // 2. Load the card template using the absolute CDN path
        const templateHtml = await fetch(assetsBase + 'elements/card_template.html').then(r => r.text());

        // 3. Update JSON paths to use absolute CDN URLs
        const jsonFiles = [
            assetsBase + 'offers/offer1.json',
            assetsBase + 'offers/offer2.json',
            assetsBase + 'offers/offer3.json'
        ];

        const dataList = await Promise.all(
            jsonFiles.map(path => fetch(path).then(r => r.json()))
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

            // Check if element exists before appending
            if (offerListEl) {
                offerListEl.appendChild(cardEl);
            }
        });
    } catch (err) {
        console.error("Error loading offer data from CDN:", err);
    }
})();