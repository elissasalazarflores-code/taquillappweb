(function () {
  'use strict';

  var doc = document.documentElement;
  doc.classList.add('js');
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Header state ---------- */
  var header = document.getElementById('header');
  function onScroll() {
    header.classList.toggle('is-scrolled', window.scrollY > 40);
  }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------- Mobile menu ---------- */
  var burger = document.getElementById('burger');
  var nav = document.getElementById('nav');
  function setMenu(open) {
    nav.classList.toggle('is-open', open);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  burger.addEventListener('click', function () {
    setMenu(burger.getAttribute('aria-expanded') !== 'true');
  });
  nav.addEventListener('click', function (e) {
    if (e.target.closest('a')) setMenu(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && nav.classList.contains('is-open')) setMenu(false);
  });

  /* ---------- Active nav link ---------- */
  var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]:not(.btn)'));
  var sections = links.map(function (a) { return document.querySelector(a.getAttribute('href')); });
  if ('IntersectionObserver' in window) {
    var navIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        links.forEach(function (a) {
          a.classList.toggle('is-active', a.getAttribute('href') === '#' + entry.target.id);
        });
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(function (s) { if (s) navIO.observe(s); });
  }

  /* ---------- Count-up ---------- */
  function countUp(el) {
    var target = parseFloat(el.getAttribute('data-count'));
    var prefix = el.getAttribute('data-prefix') || '';
    var suffix = el.getAttribute('data-suffix') || '';
    if (reduceMotion || isNaN(target)) return;
    var start = null;
    var duration = 1800;
    function frame(t) {
      if (!start) start = t;
      var p = Math.min((t - start) / duration, 1);
      var eased = 1 - Math.pow(1 - p, 4);
      el.textContent = prefix + Math.round(target * eased) + suffix;
      if (p < 1) requestAnimationFrame(frame);
    }
    el.textContent = prefix + '0' + suffix;
    requestAnimationFrame(frame);
  }

  /* ---------- Reveal on scroll ---------- */
  var reveals = document.querySelectorAll('.reveal');
  // stagger siblings
  Array.prototype.forEach.call(reveals, function (el) {
    var parent = el.parentElement;
    var siblings = Array.prototype.filter.call(parent.children, function (c) { return c.classList.contains('reveal'); });
    var i = siblings.indexOf(el);
    if (i > 0) el.style.setProperty('--d', Math.min(i * 0.09, 0.5) + 's');
  });

  if ('IntersectionObserver' in window && !reduceMotion) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-in');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    Array.prototype.forEach.call(reveals, function (el) { io.observe(el); });

    var countIO = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        countUp(entry.target);
        countIO.unobserve(entry.target);
      });
    }, { threshold: 0.6 });
    Array.prototype.forEach.call(document.querySelectorAll('[data-count]'), function (el) { countIO.observe(el); });

    var crew = document.querySelector('.crew');
    if (crew) {
      new IntersectionObserver(function (entries, obs) {
        if (entries[0].isIntersecting) { crew.classList.add('is-in'); obs.disconnect(); }
      }, { threshold: 0.4 }).observe(crew);
    }
  } else {
    Array.prototype.forEach.call(reveals, function (el) { el.classList.add('is-in'); });
    var crewEl = document.querySelector('.crew');
    if (crewEl) crewEl.classList.add('is-in');
  }

  /* ---------- Timeline ---------- */
  var tl = document.getElementById('timeline');
  var bar = document.getElementById('tl-progress');
  if (tl) {
    var step = function () {
      var card = tl.querySelector('.tl');
      return card ? card.getBoundingClientRect().width + 18 : 300;
    };
    document.querySelectorAll('.tl-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        tl.scrollBy({ left: step() * Number(btn.getAttribute('data-dir')), behavior: reduceMotion ? 'auto' : 'smooth' });
      });
    });
    var updateBar = function () {
      var max = tl.scrollWidth - tl.clientWidth;
      var visible = tl.clientWidth / tl.scrollWidth;
      bar.style.width = (visible * 100) + '%';
      bar.style.left = (max > 0 ? (tl.scrollLeft / max) * (1 - visible) * 100 : 0) + '%';
    };
    tl.addEventListener('scroll', updateBar, { passive: true });
    window.addEventListener('resize', updateBar);
    updateBar();
  }

  /* ---------- Hero video: pause when off-screen / reduced motion ---------- */
  var video = document.querySelector('.hero__video');
  if (video) {
    if (reduceMotion) {
      video.removeAttribute('autoplay');
      video.pause();
    } else if ('IntersectionObserver' in window) {
      new IntersectionObserver(function (entries) {
        var p = entries[0].isIntersecting ? video.play() : video.pause();
        if (p && p.catch) p.catch(function () {});
      }).observe(video);
    }
  }

  /* ---------- Contact form (mailto) ---------- */
  var form = document.getElementById('contact-form');
  var note = document.getElementById('form-note');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var ok = true;
      ['name', 'email', 'message'].forEach(function (n) {
        var input = form.elements[n];
        var valid = input.value.trim() !== '' && (n !== 'email' || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim()));
        input.closest('.field').classList.toggle('is-invalid', !valid);
        if (!valid) ok = false;
      });
      if (!ok) {
        note.textContent = 'Please complete your name, a valid email and a short description of your project.';
        return;
      }
      var f = form.elements;
      var subject = 'Project inquiry · ' + f.profile.value + (f.company.value.trim() ? ' · ' + f.company.value.trim() : '');
      var body = [
        'Name: ' + f.name.value.trim(),
        'Organization: ' + (f.company.value.trim() || '—'),
        'Email: ' + f.email.value.trim(),
        'Phone: ' + (f.phone.value.trim() || '—'),
        'Profile: ' + f.profile.value,
        '',
        f.message.value.trim()
      ].join('\n');
      window.location.href = 'mailto:contact@bkm.com.mx?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(body);
      note.textContent = 'Thank you. Your email app should open with the message ready to send.';
    });
  }

  var year = document.getElementById('year');
  if (year) year.textContent = new Date().getFullYear();
})();
