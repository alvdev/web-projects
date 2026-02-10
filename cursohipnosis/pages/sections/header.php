<header
        class="sticky z-20 top-0 w-full border-b py-2 bg-white/80 backdrop-blur-md shadow-xl shadow-blue-950/10 transition-all duration-300">
    <div class="container flex items-center justify-between mx-auto px-4">
        <a href="<?= $BASE_URL ?>"
           id="logo"
           class="w-32 transition-all"
           aria-label="Logo de curso de hipnosis terapeutica">
            <img src="<?= asset('images/logo.webp') ?>"
                 srcset="<?= asset('images/logo.webp') ?> 400w"
                 alt=""
                 width="275"
                 height="275">
        </a>

        <!-- Hidden checkbox for menu state -->
        <input type="checkbox"
               id="menu-toggle"
               class="hidden peer" />

        <!-- Hamburger Icon -->
        <label for="menu-toggle"
               class="md:hidden cursor-pointer">
            <div class="w-8 h-8 flex flex-col justify-between">
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
                <span class="h-1 w-full bg-black rounded transition-all origin-left"></span>
            </div>
        </label>

        <!-- Navigation Links -->
        <div
             class="absolute peer-checked:top-full peer-checked:opacity-100 peer-checked:visible md:relative md:top-0 md:opacity-100 md:visible left-0 w-full md:w-auto -top-[400px] opacity-0 invisible md:flex gap-12 bg-white md:bg-transparent transition-all duration-300 shadow-md md:shadow-none">
            <nav
                 class="container mx-auto px-4 py-6 md:py-0 grid grid-cols-2 lg:flex lg:flex-row items-center gap-4 md:gap-x-12 uppercase text-xl font-semibold border-y md:border-y-0">
                <a href="#intro"
                   class="flex items-center gap-2 group hover:text-blue-800">
                    <span
                          class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">1</span>
                    <span class="md:hidden">Intro</span>
                    <span class="hidden md:block">Introducción</span>
                </a>
                <a href="#testimonials"
                   class="flex items-center gap-2 group hover:text-blue-800">
                    <span
                          class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">2</span>
                    Testimonios
                </a>
                <a href="#course"
                   class="flex items-center gap-2 group hover:text-blue-800">
                    <span
                          class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">3</span>
                    El curso
                </a>
                <a href="#contact"
                   class="flex items-center gap-2 group hover:text-blue-800 scroll-mt-20 md:scroll-mt-16">
                    <span
                          class="text-white bg-blue-950 rounded-full w-8 h-8 flex justify-center items-center leading-px group-hover:bg-blue-800">4</span>
                    Contacto
                </a>
            </nav>
        </div>
    </div>
</header>
<script>
(function() {
    // Robust anchor scroll handler to compensate for sticky header height.
    // Intercepts same-page anchor clicks, updates the URL with pushState
    // and scrolls programmatically so the target sits below the header.
    const header = document.querySelector('header.sticky') || document.querySelector('header');
    const menuToggle = document.getElementById('menu-toggle');

    function getHeaderHeight() {
        return header ? Math.ceil(header.getBoundingClientRect().height) : 0;
    }

    function scrollToHash(hash, smooth = true) {
        if (!hash) return;
        const id = hash.charAt(0) === '#' ? hash.slice(1) : hash;
        const el = document.getElementById(id);
        if (!el) return;
        const offset = getHeaderHeight();
        const top = window.pageYOffset + el.getBoundingClientRect().top - offset;
        try {
            window.scrollTo({
                top: top,
                behavior: smooth ? 'smooth' : 'auto'
            });
        } catch (e) {
            window.scrollTo(0, top);
        }
    }
    // Intercept clicks on same-page anchors
    document.addEventListener('click', function(e) {
        const a = e.target.closest && e.target.closest('a[href^="#"]');
        if (!a) return;
        // Only handle same-document anchors
        const href = a.getAttribute('href');
        if (!href || href === '#') return;
        // If the link includes a path, ensure it's the same path (relative links may not)
        // We only handle pure fragment links or links whose pathname matches current pathname
        const url = new URL(a.href, location.href);
        if (url.pathname !== location.pathname || url.search !== location.search) return;
        e.preventDefault();
        // Close mobile menu if open
        if (menuToggle && menuToggle.checked) {
            menuToggle.checked = false;
        }
        // Update URL without causing the browser to perform native fragment scroll
        history.pushState(null, '', url.hash);
        // Scroll to element accounting for header height
        // Use a tiny timeout to let any layout changes (menu closing) settle
        setTimeout(function() {
            scrollToHash(url.hash, true);
        }, 50);
    }, {
        passive: false
    });
    // Handle back/forward navigation
    window.addEventListener('popstate', function() {
        // On popstate, location.hash is already updated
        setTimeout(function() {
            scrollToHash(location.hash, false);
        }, 10);
    });
    // On initial load with a hash, scroll to it after a short delay (to allow layout/fonts/images)
    window.addEventListener('load', function() {
        if (location.hash) {
            setTimeout(function() {
                scrollToHash(location.hash, false);
            }, 50);
        }
    });
    // Recalculate if the header changes size (resize)
    window.addEventListener('resize', function() {
        // If currently have a hash, re-position without smooth animation
        if (location.hash) scrollToHash(location.hash, false);
    });
})();
</script>
