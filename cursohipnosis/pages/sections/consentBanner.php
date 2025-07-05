<noscript>
    <iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PZMN6DP3"
        height="0" width="0" style="display:none;visibility:hidden"></iframe>
</noscript>

<!-- Consent Banner -->
<div id="consent-banner" class="consent-banner fixed inset-0 bg-white/90 shadow-lg overflow-hidden z-50 flex items-center justify-center">
    <div class="consent-content md:w-1/2 shadow-lg border border-blue-800 rounded-lg p-16 grid gap-8 bg-white/90 [animation:_slideInUp_0.5s_ease-out]">
        <div class="consent-text">
            <p class="text-black">Para tu información:</p>
            <p class="text-xl text-gray-700">
                Esta web recopila datos exclusivamente para el análisis y seguimiento de campañas de publicidad. Para poder continuar, es necesario tu permiso para aceptar o rechazar su recopilación.
            </p>
        </div>
        <div class="consent-buttons">
            <button class="min-w-1/4 inline-flex justify-center border-1.5 px-8 py-4 rounded-lg uppercase" onclick="handleConsent('denied')">Rechazar</button>
            <button class="min-w-1/4 bg-blue-950 text-white px-8 py-4 rounded-lg uppercase flex items-center justify-center hover:bg-blue-900" onclick="handleConsent('granted')">Aceptar</button>
        </div>
    </div>
</div>

<script>
    // On DOM load: if banner will show, disable scroll
    document.addEventListener('DOMContentLoaded', () => {
        const consent = localStorage.getItem('gtm_consent');
        if (!consent) {
            // block scroll
            document.body.style.overflow = 'hidden';
        } else {
            // already decided → hide banner
            document.getElementById('consent-banner').style.display = 'none';
        }
    });

    function handleConsent(choice) {
        // Persist choice
        localStorage.setItem('gtm_consent', choice);

        // Push update to dataLayer
        window.dataLayer.push({
            event: 'consent_update',
            ad_storage: choice,
            analytics_storage: choice,
            functionality_storage: choice,
            personalization_storage: choice,
            security_storage: 'granted'
        });

        // Fire page_view if allowed
        if (choice === 'granted') {
            window.dataLayer.push({
                event: 'page_view'
            });
        }

        // Hide banner
        const banner = document.getElementById('consent-banner');
        banner.style.display = 'none';

        // Restore scroll
        document.body.style.overflow = '';
    }
</script>

<style>
    .consent-text a {
        color: #0066cc;
        text-decoration: none;
        font-weight: 500;
    }

    .consent-text a:hover {
        text-decoration: underline;
    }

    /* Buttons container */
    .consent-buttons {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    /* Button base styles */
    .btn {
        padding: 0.5rem 1.25rem;
        font-size: 0.9rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .btn:focus {
        outline: none;
    }

    /* Reject button */
    .btn–reject {
        background: #e0e0e0;
        color: #555;
    }

    .btn–reject:hover {
        background: #d5d5d5;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        transform: translateY(-1px);
    }

    /* Accept button */
    .btn–accept {
        background: #0066cc;
        color: #fff;
    }

    .btn–accept:hover {
        background: #005bb5;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
        transform: translateY(-1px);
    }

    /* Responsive adjustments */
    @media (max-width: 600px) {
        .consent-content {
            flex-direction: column;
            align-items: stretch;
        }

        .consent-buttons {
            justify-content: flex-end;
            margin-top: 1rem;
        }
    }

    /* Slide-in animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(100%);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
