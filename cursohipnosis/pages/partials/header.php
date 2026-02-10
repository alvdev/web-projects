<header class="sticky z-20 top-0 w-full border-b py-2 bg-white/80 backdrop-blur-md shadow-xl shadow-blue-950/10 transition-all duration-300">
    <div class="container flex items-center justify-between mx-auto px-4">
        <a href="<?= BASE_URL ?>" id="logo" class="w-32 transition-all" aria-label="Logo de curso de hipnosis terapeutica">
            <img src="<?= asset('images/logo.webp') ?>" srcset="<?= asset('images/logo.webp') ?> 400w" alt="" width="275" height="275">
        </a>

        <!-- Hidden checkbox for menu state -->
        <input type="checkbox" id="menu-toggle" class="hidden peer" />

        <!-- Hamburger Icon -->
        <label for="menu-toggle" class="md:hidden cursor-pointer">
            <div class="w-8 h-8 flex flex-col justify-between">
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
            </div>
        </label>

        <!-- Navigation Links -->
        <div class="absolute peer-checked:top-full peer-checked:opacity-100 peer-checked:visible md:relative md:top-0 md:opacity-100 md:visible left-0 w-full md:w-auto -top-[400px] opacity-0 invisible md:flex gap-12 bg-white md:bg-transparent transition-all duration-300 shadow-md md:shadow-none">
            <nav class="container mx-auto px-4 py-6 md:py-0 grid grid-cols-2 lg:flex lg:flex-row items-center gap-4 md:gap-x-12 uppercase text-xl font-semibold border-y md:border-y-0">
                <a href="#intro" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">1</span>
                    <span class="md:hidden">Intro</span>
                    <span class="hidden md:block">Introducción</span>
                </a>
                <a href="#testimonials" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">2</span>
                    Testimonios
                </a>
                <a href="#course" class="flex items-center gap-2 group hover:text-blue-800">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">3</span>
                    El curso
                </a>
                <a href="#contact" class="flex items-center gap-2 group hover:text-blue-800 scroll-mt-20 md:scroll-mt-16">
                    <span class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">4</span>
                    Contacto
                </a>
            </nav>
        </div>
    </div>
</header>

<script>
    (function() {
        const header = document.querySelector('header.sticky') || document.querySelector('header');
        const menuToggle = document.getElementById('menu-toggle');
        let userScrolling = false;
        let lastAnchorClick = 0; // Timestamp del último clic en anchor

        function getHeaderHeight() {
            return header ? Math.ceil(header.getBoundingClientRect().height) : 0;
        }

        function updateScrollMargins() {
            const h = getHeaderHeight();
            document.querySelectorAll('[id]').forEach(el => {
                if (el.hasAttribute('data-preserve-scroll-margin')) return;
                try {
                    const cs = window.getComputedStyle(el);
                    const current = parseFloat(cs.scrollMarginTop) || 0;
                    if (current > 0) return;
                } catch (e) {
                    const cls = (el.className || '').toString();
                    if (/\bscroll-mt-/.test(cls)) return;
                }
                el.style.scrollMarginTop = h + 'px';
            });
        }

        function scrollToHash(hash, smooth = true) {
            if (!hash) return;
            const id = hash.charAt(0) === '#' ? hash.slice(1) : hash;
            const el = document.getElementById(id);
            if (!el) return;
            let computedMargin = 0;
            try {
                computedMargin = parseFloat(getComputedStyle(el).scrollMarginTop) || 0;
            } catch (e) {}
            const offset = computedMargin > 0 ? computedMargin : getHeaderHeight();
            let top = window.pageYOffset + el.getBoundingClientRect().top - offset;
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            if (top > maxScroll) top = maxScroll;
            try {
                window.scrollTo({
                    top: top,
                    behavior: smooth ? 'smooth' : 'auto'
                });
            } catch (e) {
                window.scrollTo(0, top);
            }
        }

        // Detectar cuando el usuario está haciendo scroll manualmente
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            // Solo marcar como userScrolling si no fue un clic reciente en anchor
            if (Date.now() - lastAnchorClick > 1000) {
                userScrolling = true;
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(function() {
                    userScrolling = false;
                }, 150);
            }
        }, {
            passive: true
        });

        // touch handling: distinguish taps from swipes
        let lastTouch = 0,
            touchStartX = 0,
            touchStartY = 0,
            touchMoved = false;
        document.addEventListener('touchstart', function(e) {
            const t = e.touches && e.touches[0];
            if (t) {
                touchStartX = t.clientX;
                touchStartY = t.clientY;
                touchMoved = false;
            }
        }, {
            passive: true,
            capture: true
        });
        document.addEventListener('touchmove', function(e) {
            const t = e.touches && e.touches[0];
            if (!t) return;
            const dx = t.clientX - touchStartX;
            const dy = t.clientY - touchStartY;
            if (Math.hypot(dx, dy) > 10) touchMoved = true;
        }, {
            passive: true,
            capture: true
        });

        function handleAnchorEvent(e) {
            if (e.type === 'touchend' && touchMoved) {
                touchMoved = false;
                return;
            }
            if (e.type === 'touchend') lastTouch = Date.now();
            if (e.type === 'click' && (Date.now() - lastTouch) < 500) return;
            const a = e.target.closest && e.target.closest('a[href^="#"]');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href === '#') return;
            const url = new URL(a.href, location.href);
            if (url.pathname !== location.pathname || url.search !== location.search) return;

            // Marcar que hubo un clic en anchor
            lastAnchorClick = Date.now();
            userScrolling = false; // Resetear bandera para permitir ajustes

            if (menuToggle && menuToggle.checked) menuToggle.checked = false;
            updateScrollMargins();
        }

        document.addEventListener('click', handleAnchorEvent, {
            passive: false,
            capture: true
        });
        document.addEventListener('touchend', handleAnchorEvent, {
            passive: false,
            capture: true
        });

        (function() {
            let timeoutId = 0,
                ro = null,
                transitionListener = null;
            let alignmentComplete = false;

            function clearAll() {
                if (timeoutId) {
                    clearTimeout(timeoutId);
                    timeoutId = 0;
                }
                if (ro) {
                    try {
                        ro.disconnect();
                    } catch (e) {}
                    ro = null;
                }
                if (transitionListener && header) {
                    header.removeEventListener('transitionend', transitionListener);
                    transitionListener = null;
                }
            }

            function scheduleFinal(hash) {
                clearAll();
                alignmentComplete = false;

                let totalMs = 0;
                try {
                    if (header) {
                        const cs = getComputedStyle(header);
                        const td = cs.transitionDuration || '0s';
                        const tdelay = cs.transitionDelay || '0s';
                        const parseSecs = s => Math.max(...s.split(',').map(x => parseFloat(x) || 0));
                        totalMs = Math.round(Math.max(parseSecs(td), parseSecs(tdelay)) * 1000);
                    }
                } catch (e) {}
                const fallback = Math.max(totalMs + 80, 220);

                if (window.ResizeObserver && header) {
                    try {
                        let stableTimer = 0;
                        ro = new ResizeObserver(() => {
                            // Permitir ajustes si fue clic reciente o si aún no está completo
                            const isRecentClick = Date.now() - lastAnchorClick < 2000;
                            if (!alignmentComplete && (!userScrolling || isRecentClick)) {
                                if (stableTimer) clearTimeout(stableTimer);
                                stableTimer = setTimeout(() => {
                                    finalAlign(hash);
                                }, 120);
                            }
                        });
                        ro.observe(header);
                    } catch (e) {
                        ro = null;
                    }
                }

                if (header) {
                    transitionListener = function(ev) {
                        if (ev.target !== header || alignmentComplete) return;
                        const isRecentClick = Date.now() - lastAnchorClick < 2000;
                        if (userScrolling && !isRecentClick) return;
                        clearTimeout(timeoutId);
                        timeoutId = setTimeout(() => finalAlign(hash), 60);
                    };
                    header.addEventListener('transitionend', transitionListener);
                }

                timeoutId = setTimeout(() => finalAlign(hash), fallback);
            }

            function finalAlign(hash) {
                const isRecentClick = Date.now() - lastAnchorClick < 2000;
                if (alignmentComplete || (userScrolling && !isRecentClick)) return;

                clearAll();
                alignmentComplete = true;

                if (location.hash && (!hash || location.hash === hash)) {
                    scrollToHash(location.hash, false);
                } else if (hash) {
                    scrollToHash(hash, false);
                }
            }

            window.addEventListener('hashchange', function() {
                updateScrollMargins();
                scheduleFinal(location.hash);
            });

            window.addEventListener('load', function() {
                updateScrollMargins();
                if (location.hash) {
                    setTimeout(() => scheduleFinal(location.hash), 60);
                }
            });

            window.addEventListener('resize', function() {
                updateScrollMargins();
                // Solo reajustar si fue un clic reciente
                if (location.hash && Date.now() - lastAnchorClick < 2000) {
                    scheduleFinal(location.hash);
                }
            });
        })();

        updateScrollMargins();
    })();
</script>
