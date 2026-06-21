(function () {
  'use strict';

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  ready(function () {
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var selectors = [
      ['.doc-visual-hero > div', 'doc-motion-left'],
      ['.doc-visual-hero > img', 'doc-motion-right'],
      ['.markdown-body > h1, .markdown-body > h2, .markdown-body > h3, .markdown-body > h4', 'doc-motion-left'],
      ['.markdown-body > p, .markdown-body > blockquote, .markdown-body > ul, .markdown-body > ol, .markdown-body > table', 'doc-motion-item']
    ];
    var items = [];

    selectors.forEach(function (rule) {
      Array.prototype.slice.call(document.querySelectorAll(rule[0])).forEach(function (el, index) {
        el.classList.add('doc-motion-item');
        if (rule[1] !== 'doc-motion-item') el.classList.add(rule[1]);
        el.style.setProperty('--doc-reveal-delay', Math.min(index % 6, 5) * 48 + 'ms');
        items.push(el);
      });
    });

    if (!items.length) return;
    if (reduce || !('IntersectionObserver' in window)) {
      items.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }

    document.body.classList.add('doc-motion-ready');
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting && entry.intersectionRatio > 0.06) {
          entry.target.classList.add('is-visible');
        }
      });
    }, { threshold: [0, .06, .16], rootMargin: '0px 0px -8% 0px' });

    items.forEach(function (el) { observer.observe(el); });
  });
})();
