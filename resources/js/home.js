// Homepage: reveal sections as they scroll in, and give the nav a hairline once the page moves.
// Content is fully visible without JS; the hidden start state only applies after this runs.
const root = document.documentElement;
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!reduce && 'IntersectionObserver' in window) {
    root.classList.add('reveal-ready');
    const io = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            }
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 },
    );
    document.querySelectorAll('[data-reveal]').forEach((el) => io.observe(el));
}

const nav = document.querySelector('[data-nav]');
const onScroll = () => nav?.classList.toggle('border-line', window.scrollY > 8);
addEventListener('scroll', onScroll, { passive: true });
onScroll();
