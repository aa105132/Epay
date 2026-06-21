(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var rules = [
    ['.blue-hero-copy, .nav-visual-copy, .hero-copy, .page-hero', 'motion-from-far-left'],
    ['.blue-hero-art, .nav-visual-art, .ledger-card', 'motion-from-far-right'],
    ['.blue-capability-row article:nth-child(odd), .blue-adv-grid article:nth-child(odd), .plain-feature-line:nth-child(odd), .scenario-strip div:nth-child(odd)', 'motion-from-left'],
    ['.blue-capability-row article:nth-child(even), .blue-adv-grid article:nth-child(even), .plain-feature-line:nth-child(even), .scenario-strip div:nth-child(even)', 'motion-from-right'],
    ['.blue-notice, .blue-section-head, .section-heading, .thin-process-section > div:first-child, .about-statement > div:first-child', 'motion-from-up'],
    ['.blue-feature-copy, .workflow-list > div, .thin-process-list li, .solution-copy-list p, .about-principles p', 'motion-from-left'],
    ['.blue-feature-art, .method-card, .blue-bottom-strip > div, .contact-minimal, .cta-strip', 'motion-fade-pop'],
    ['.footer-callout, .footer-grid > *', 'motion-from-up']
  ];

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  function isInViewport(el) {
    var rect = el.getBoundingClientRect();
    var height = window.innerHeight || document.documentElement.clientHeight;
    return rect.top < height * 0.92 && rect.bottom > height * 0.04;
  }

  ready(function () {
    var elements = [];

    rules.forEach(function (rule) {
      var selector = rule[0];
      var direction = rule[1];
      var group = Array.prototype.slice.call(document.querySelectorAll(selector));

      group.forEach(function (el, index) {
        if (!el.classList.contains('motion-reveal')) {
          el.classList.add('motion-reveal');
          elements.push(el);
        }
        el.classList.add(direction);
        el.style.setProperty('--reveal-delay', Math.min(index % 7, 6) * 58 + 'ms');
      });
    });

    if (!elements.length) return;

    if (reduceMotion || !('IntersectionObserver' in window)) {
      elements.forEach(function (el) { el.classList.add('is-visible'); });
      document.body.classList.add('motion-disabled');
      return;
    }

    elements.forEach(function (el) {
      if (isInViewport(el)) el.classList.add('is-visible');
    });

    document.body.classList.add('motion-ready');

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && entry.intersectionRatio > 0.08) {
          entry.target.classList.add('is-visible');
        } else if (!entry.isIntersecting) {
          entry.target.classList.remove('is-visible');
        }
      });
    }, {
      threshold: [0, 0.08, 0.18],
      rootMargin: '0px 0px -6% 0px'
    });

    elements.forEach(function (el) { observer.observe(el); });
  });
})();
