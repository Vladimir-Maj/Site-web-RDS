(async function() {
    // 1. Define where the CDN lives
    const cdnBase = 'http://cdn.localhost:8080/';
    const assetsBase = cdnBase + 'assets/';

    // Get current page filename (e.g., index.html)
    const currentPage = location.pathname.split('/').pop() || 'index.html';

    // 2. Use Absolute Paths for fetching
    const headerPath = assetsBase + 'elements/header.html';
    const footerPath = assetsBase + 'elements/footer.html';
    const headExcludePath = assetsBase + 'head_exclude.json';
    const footExcludePath = assetsBase + 'foot_exclude.json';

    try {
        // Load exclude lists from CDN
        const [headExclude, footExclude] = await Promise.all([
            fetch(headExcludePath).then(r => r.json()),
            fetch(footExcludePath).then(r => r.json())
        ]);

        // Load header if not excluded
        if (!headExclude.includes(currentPage)) {
            const headerHtml = await fetch(headerPath).then(r => r.text());
            const headerEl = document.querySelector('header');
            if (headerEl) {
                headerEl.innerHTML = headerHtml;

                // Highlight active link
                headerEl.querySelectorAll('nav a').forEach(link => {
                    if (link.getAttribute('href') === currentPage) {
                        link.classList.add('active');
                    }
                });
            }
        }

        // Load footer if not excluded
        if (!footExclude.includes(currentPage)) {
            const footerHtml = await fetch(footerPath).then(r => r.text());
            const footerEl = document.querySelector('footer');
            if (footerEl) {
                footerEl.innerHTML = footerHtml;
            }
        }
    } catch (err) {
        console.error("Failed to load CDN assets:", err);
    }
})();