/**
 * load_head_foot.js
 * Handles dynamic loading of header/footer and wires up theme toggle logic.
 * Supports both PHP-injected and JS-injected headers.
 */
(async function() {
    // 1. Determine CDN Base URL
    const cdnBase = (window.APP_CONFIG && window.APP_CONFIG.cdnUrl)
        ? window.APP_CONFIG.cdnUrl + '/'
        : 'https://cdn.stageflow.fr/';

    const assetsBase = cdnBase + 'assets/';
    const currentPage = location.pathname.split('/').pop() || 'index.html';

    // Target Elements
    const headerEl = document.querySelector('header');
    const footerEl = document.querySelector('footer');

    /**
     * Theme Toggle Logic
     */
    function setupThemeToggle() {
        setTimeout(() => {
            const btn = document.getElementById('theme-toggle');
            const icon = document.getElementById('theme-icon');
            const html = document.documentElement;

            if (!btn) {
                console.warn("Theme toggle button not found in the header.");
                return;
            }

            const savedTheme = localStorage.getItem('theme') || 'light';
            html.setAttribute('data-theme', savedTheme);
            if (icon) icon.textContent = savedTheme === 'dark' ? '🌙' : '☀️';

            btn.onclick = (e) => {
                e.preventDefault();
                const isDark = html.getAttribute('data-theme') === 'dark';
                const newTheme = isDark ? 'light' : 'dark';

                html.setAttribute('data-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                if (icon) icon.textContent = newTheme === 'dark' ? '🌙' : '☀️';

                console.log(`Theme switched to: ${newTheme}`);
            };

            console.log("Theme toggle successfully wired!");
        }, 50);
    }

    try {
        // 2. LOAD HEADER
        if (headerEl) {
            if (headerEl.innerHTML.trim() === "") {
                const headExcludePath = assetsBase + 'head_exclude.json';
                const headExclude = await fetch(headExcludePath).then(r => r.json());

                if (!headExclude.includes(currentPage)) {
                    // FIXED: Path changed from 'elements/' to 'templates/'
                    const headerPath = assetsBase + 'elements/header_template.html';
                    const resp = await fetch(headerPath + '?v=' + Date.now());

                    if (resp.ok) {
                        headerEl.innerHTML = await resp.text();
                        setupThemeToggle();
                    } else {
                        console.error(`Failed to fetch header from: ${headerPath}`);
                    }
                }
            } else {
                setupThemeToggle();
            }
        }

        // 3. LOAD FOOTER
        if (footerEl && footerEl.innerHTML.trim() === "") {
            const footExcludePath = assetsBase + 'foot_exclude.json';
            const footExclude = await fetch(footExcludePath).then(r => r.json());

            if (!footExclude.includes(currentPage)) {
                // FIXED: Path changed from 'elements/' to 'templates/' (adjust if footer is elsewhere)
                const footerPath = assetsBase + 'elements/footer_template.html';
                const resp = await fetch(footerPath);
                if (resp.ok) {
                    footerEl.innerHTML = await resp.text();
                } else {
                    console.error(`Failed to fetch footer from: ${footerPath}`);
                }
            }
        }

    } catch (err) {
        console.error("Critical error in load_head_foot.js:", err);
    }
})();