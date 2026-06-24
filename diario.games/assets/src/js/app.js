import '../css/app.css'

(function () {
    var lightbox = document.getElementById('lightbox');
    if (!lightbox) return;

    var img = lightbox.querySelector('img');
    var counter = lightbox.querySelector('[data-lightbox-counter]');
    var closeBtn = lightbox.querySelector('[data-lightbox-close]');
    var prevBtn = lightbox.querySelector('[data-lightbox-prev]');
    var nextBtn = lightbox.querySelector('[data-lightbox-next]');
    var items = document.querySelectorAll('[data-lightbox]');
    var current = 0;

    var transitioning = false;

    function loadSrc(src, alt) {
        img.classList.remove('loaded');

        setTimeout(function () {
            var newImg = new Image();
            newImg.onload = function () {
                img.src = src;
                img.alt = alt || '';
                img.classList.add('loaded');
                transitioning = false;
            };
            newImg.onerror = function () {
                img.src = src;
                img.alt = alt || '';
                img.classList.add('loaded');
                transitioning = false;
            };
            newImg.src = src;
        }, 300);
    }

    function open(i) {
        var el = items[i];
        if (!el) return;
        current = i;

        if (!lightbox.classList.contains('active')) {
            img.src = el.dataset.lightbox;
            img.alt = el.dataset.lightboxAlt || '';
            img.classList.add('loaded');
            lightbox.classList.add('active');
        } else {
            if (transitioning) return;
            transitioning = true;
            loadSrc(el.dataset.lightbox, el.dataset.lightboxAlt);
        }

        if (counter) counter.textContent = (current + 1) + ' / ' + items.length;
        document.body.style.overflow = 'hidden';
    }

    function close() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    function prev() {
        open((current - 1 + items.length) % items.length);
    }

    function next() {
        open((current + 1) % items.length);
    }

    items.forEach(function (el, i) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            open(i);
        });
    });

    closeBtn.addEventListener('click', close);
    prevBtn.addEventListener('click', prev);
    nextBtn.addEventListener('click', next);

    lightbox.addEventListener('click', function (e) {
        if (e.target === lightbox) close();
    });

    document.addEventListener('keydown', function (e) {
        if (!lightbox.classList.contains('active')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
    });
})();
