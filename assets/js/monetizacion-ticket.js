/**
 * Panel monetización — contador, pulso y confetti ligero (gestionar ticket).
 */
(function (global) {
    'use strict';

    var DURATION_MS = 2900;
    var rafId = null;
    var confettiRafId = null;

    function prefersReducedMotion() {
        return global.matchMedia && global.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function parseValor(raw) {
        if (raw === null || raw === undefined) return NaN;
        var s = String(raw).trim();
        if (s === '' || s === '—') return NaN;
        var cleaned = s.replace(/[^\d,.-]/g, '');
        if (/^\d{1,3}(,\d{3})+(\.\d+)?$/.test(cleaned)) {
            cleaned = cleaned.replace(/,/g, '');
        } else if (cleaned.indexOf(',') !== -1 && cleaned.indexOf('.') !== -1) {
            cleaned = cleaned.replace(/\./g, '').replace(',', '.');
        } else if (cleaned.indexOf(',') !== -1) {
            cleaned = cleaned.replace(',', '.');
        }
        var n = parseFloat(cleaned);
        return isNaN(n) ? NaN : n;
    }

    function formatUsd(n) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(n);
    }

    function getPanel() {
        return document.getElementById('monetizacionPanel');
    }

    function getValorEl() {
        return document.getElementById('detMonetizacion');
    }

    function getCanvas() {
        return document.getElementById('monetizacionConfetti');
    }

    function cancelAnimation() {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
        if (confettiRafId !== null) {
            cancelAnimationFrame(confettiRafId);
            confettiRafId = null;
        }
    }

    function burstConfetti() {
        if (prefersReducedMotion()) return;

        var panel = getPanel();
        var canvas = getCanvas();
        if (!panel || !canvas) return;

        var rect = panel.getBoundingClientRect();
        var dpr = global.devicePixelRatio || 1;
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(rect.height * dpr));
        canvas.style.width = rect.width + 'px';
        canvas.style.height = rect.height + 'px';

        var ctx = canvas.getContext('2d');
        if (!ctx) return;
        ctx.scale(dpr, dpr);

        var colors = ['#10b981', '#f59e0b', '#1e88e5', '#fbbf24', '#34d399'];
        var cx = rect.width / 2;
        var cy = rect.height * 0.35;
        var particles = [];
        var count = 48;
        var i;

        for (i = 0; i < count; i++) {
            var angle = (Math.PI * 2 * i) / count + Math.random() * 0.4;
            var speed = 2 + Math.random() * 4;
            particles.push({
                x: cx,
                y: cy,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed - 2,
                size: 3 + Math.random() * 4,
                color: colors[Math.floor(Math.random() * colors.length)],
                rot: Math.random() * Math.PI,
                vr: (Math.random() - 0.5) * 0.2,
                life: 1
            });
        }

        var start = performance.now();
        var duration = 1200;

        function frame(now) {
            var t = Math.min(1, (now - start) / duration);
            ctx.clearRect(0, 0, rect.width, rect.height);

            var alive = false;
            particles.forEach(function (p) {
                if (p.life <= 0) return;
                alive = true;
                p.x += p.vx;
                p.y += p.vy;
                p.vy += 0.12;
                p.vx *= 0.98;
                p.life = 1 - t;
                p.rot += p.vr;

                ctx.save();
                ctx.globalAlpha = Math.max(0, p.life);
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
                ctx.restore();
            });

            if (alive && t < 1) {
                confettiRafId = requestAnimationFrame(frame);
            } else {
                ctx.clearRect(0, 0, rect.width, rect.height);
                confettiRafId = null;
            }
        }

        confettiRafId = requestAnimationFrame(frame);
    }

    function runCountUp(el, target) {
        var start = performance.now();

        function frame(now) {
            var t = Math.min(1, (now - start) / DURATION_MS);
            var eased = 1 - Math.pow(1 - t, 3);
            var current = Math.round(target * eased);
            el.textContent = formatUsd(current);

            if (t < 1) {
                rafId = requestAnimationFrame(frame);
            } else {
                el.textContent = formatUsd(target);
                el.classList.add('is-done', 'is-zoom-loop');
                rafId = null;
                burstConfetti();
            }
        }

        rafId = requestAnimationFrame(frame);
    }

    function reset() {
        cancelAnimation();
        var panel = getPanel();
        var el = getValorEl();
        if (el) {
            el.textContent = '—';
            el.classList.remove('is-done', 'is-zoom-loop');
        }
        if (panel) {
            panel.setAttribute('hidden', 'hidden');
        }
        var canvas = getCanvas();
        if (canvas) {
            var ctx = canvas.getContext('2d');
            if (ctx) ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }

    function play(rawValue) {
        cancelAnimation();

        var panel = getPanel();
        var el = getValorEl();
        if (!panel || !el) return;

        var target = parseValor(rawValue);
        if (isNaN(target) || target <= 0) {
            panel.setAttribute('hidden', 'hidden');
            el.textContent = '—';
            el.classList.remove('is-done', 'is-zoom-loop');
            return;
        }

        panel.removeAttribute('hidden');
        el.classList.remove('is-done', 'is-zoom-loop');

        if (prefersReducedMotion()) {
            el.textContent = formatUsd(target);
            el.classList.add('is-done');
            return;
        }

        el.textContent = formatUsd(0);
        runCountUp(el, target);
    }

    global.MonetizacionTicket = {
        play: play,
        reset: reset,
        parseValor: parseValor,
        formatUsd: formatUsd
    };
})(window);
