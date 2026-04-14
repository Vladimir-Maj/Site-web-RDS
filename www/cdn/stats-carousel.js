window.StageFlowStatsCarousel = (function () {
    function init() {
        document.querySelectorAll('[data-carousel]').forEach(setupCarousel);
    }

    function setupCarousel(root) {
        const track = root.querySelector('[data-carousel-track]');
        if (!track) {
            return;
        }

        const slides = Array.from(track.querySelectorAll('.stats-slide'));
        const prev = root.querySelector('[data-carousel-prev]');
        const next = root.querySelector('[data-carousel-next]');
        const dotsWrap = root.querySelector('[data-carousel-dots]');

        if (slides.length === 0) {
            return;
        }

        let index = slides.findIndex((slide) => slide.classList.contains('is-active'));
        index = index >= 0 ? index : 0;

        const dots = [];

        if (dotsWrap) {
            dotsWrap.innerHTML = '';

            slides.forEach((_, i) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'stats-dot';
                button.setAttribute('aria-label', `Aller � la carte ${i + 1}`);
                button.addEventListener('click', () => update(i));
                dotsWrap.appendChild(button);
                dots.push(button);
            });
        }

        function update(newIndex) {
            index = (newIndex + slides.length) % slides.length;

            slides.forEach((slide, i) => {
                const isActive = i === index;
                slide.classList.toggle('is-active', isActive);
                slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
            });

            dots.forEach((dot, i) => {
                const isActive = i === index;
                dot.classList.toggle('is-active', isActive);
                dot.setAttribute('aria-current', isActive ? 'true' : 'false');
            });
        }

        prev?.addEventListener('click', () => update(index - 1));
        next?.addEventListener('click', () => update(index + 1));

        root.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                update(index - 1);
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                update(index + 1);
            }
        });

        update(index);
    }

    return {
        init,
    };
})();
