/* ─── Servicezz — main.js ───────────────────────────────────────────────── */

// ─── Nav scroll effect ──────────────────────────────────────────────────────
const nav = document.getElementById('nav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 20);
}, { passive: true });

// ─── Mobile nav ─────────────────────────────────────────────────────────────
const burger = document.getElementById('navBurger');
const navLinks = document.querySelector('.nav-links');
burger.addEventListener('click', () => {
    navLinks.classList.toggle('open');
});
document.querySelectorAll('.nav-links a').forEach(a => {
    a.addEventListener('click', () => navLinks.classList.remove('open'));
});

// ─── Language toggle NL / EN ────────────────────────────────────────────────
let currentLang = 'nl';
const langBtn = document.getElementById('langToggle');

function setLang(lang) {
    currentLang = lang;
    langBtn.textContent = lang === 'nl' ? 'EN' : 'NL';
    document.documentElement.lang = lang;

    document.querySelectorAll('[data-nl]').forEach(el => {
        const text = el.getAttribute('data-' + lang);
        if (!text) return;
        // Preserve child elements (e.g. <span> inside button)
        if (el.children.length === 0) {
            el.textContent = text;
        }
    });
}

langBtn.addEventListener('click', () => {
    setLang(currentLang === 'nl' ? 'en' : 'nl');
});

// ─── Reveal on scroll ───────────────────────────────────────────────────────
const revealEls = document.querySelectorAll('.reveal');
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
        if (entry.isIntersecting) {
            setTimeout(() => {
                entry.target.classList.add('visible');
            }, i * 80);
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.12 });

revealEls.forEach(el => revealObserver.observe(el));

// ─── Contact form ───────────────────────────────────────────────────────────
const form = document.getElementById('contactForm');
const notice = document.getElementById('formNotice');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    const btn = form.querySelector('.btn');
    const btnText = form.querySelector('.btn-text');
    const btnLoading = form.querySelector('.btn-loading');

    btn.disabled = true;
    btnText.style.display = 'none';
    btnLoading.style.display = '';

    const data = Object.fromEntries(new FormData(form));

    // mailto fallback — stuurt e-mail aan via client
    const subject = encodeURIComponent(`Servicezz licentie aanvraag — ${data.tier.toUpperCase()}`);
    const body = encodeURIComponent(
        `Naam: ${data.name}\nE-mail: ${data.email}\nTier: ${data.tier}\nWebsite: ${data.url || '—'}\n\nBericht:\n${data.message || '—'}`
    );

    // Simuleer verzending (mailto)
    setTimeout(() => {
        window.location.href = `mailto:info@servicezz.nl?subject=${subject}&body=${body}`;

        notice.className = 'form-notice success';
        notice.textContent = currentLang === 'nl'
            ? '✓ Je e-mailprogramma wordt geopend. Stuur de e-mail om je aanvraag in te dienen.'
            : '✓ Your email client is opening. Send the email to submit your request.';

        btn.disabled = false;
        btnText.style.display = '';
        btnLoading.style.display = 'none';
    }, 600);
});

// ─── Smooth number count-up ─────────────────────────────────────────────────
function countUp(el, target, duration = 1500) {
    const start = performance.now();
    const isText = isNaN(target);
    if (isText) return;

    const update = (now) => {
        const elapsed = now - start;
        const progress = Math.min(elapsed / duration, 1);
        const ease = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.round(target * ease);
        if (progress < 1) requestAnimationFrame(update);
    };
    requestAnimationFrame(update);
}

const proofNums = document.querySelectorAll('.proof-num');
const proofObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            const val = parseInt(el.textContent);
            if (!isNaN(val)) countUp(el, val);
            proofObserver.unobserve(el);
        }
    });
}, { threshold: 0.5 });

proofNums.forEach(el => proofObserver.observe(el));

// ─── Active nav link ────────────────────────────────────────────────────────
const sections = document.querySelectorAll('section[id]');
const navAnchors = document.querySelectorAll('.nav-links a[href^="#"]');

const sectionObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            navAnchors.forEach(a => {
                a.style.color = a.getAttribute('href') === '#' + entry.target.id
                    ? 'var(--text)'
                    : '';
            });
        }
    });
}, { threshold: 0.4 });

sections.forEach(s => sectionObserver.observe(s));
