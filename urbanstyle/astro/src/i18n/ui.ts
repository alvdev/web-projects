import { getCollection } from "astro:content";

export const languages = {
    es: "Español",
    en: "English",
    it: "Italiano",
    fr: "Français",
    pt: "Português",
} as const;

export type Locale = keyof typeof languages;

export const defaultLocale: Locale = "es";

export const coverageSlugs: Record<Locale, string> = {
    es: "pegada-carteles",
    en: "poster-pasting",
    it: "affissione-manifesti",
    fr: "collage-affiches",
    pt: "colagem-cartazes",
};

export const ogLocales: Record<Locale, string> = {
    es: "es_ES",
    en: "en_US",
    it: "it_IT",
    fr: "fr_FR",
    pt: "pt_PT",
};

export const siteDefaults: Record<Locale, { title: string; description: string }> = {
    es: {
        title: "Agencia de publicidad exterior | Urban Style Publicity",
        description:
            "Agencia de publicidad exterior con presencia en Madrid, Barcelona y principales ciudades. Especialistas en pegar el 90% de carteles que ves a diario.",
    },
    en: {
        title: "Outdoor advertising agency | Urban Style Publicity",
        description:
            "Outdoor advertising agency with a presence in Madrid, Barcelona and major cities. Specialists in pasting 90% of the posters you see every day.",
    },
    it: {
        title: "Agenzia di pubblicità esterna | Urban Style Publicity",
        description:
            "Agenzia di pubblicità esterna con presenza a Madrid, Barcellona e nelle principali città. Specialisti nell'affissione del 90% dei manifesti che vedi ogni giorno.",
    },
    fr: {
        title: "Agence de publicité extérieure | Urban Style Publicity",
        description:
            "Agence de publicité extérieure présente à Madrid, Barcelone et dans les principales villes. Spécialistes du collage de 90 % des affiches que vous voyez chaque jour.",
    },
    pt: {
        title: "Agência de publicidade exterior | Urban Style Publicity",
        description:
            "Agência de publicidade exterior com presença em Madrid, Barcelona e nas principais cidades. Especialistas em colar 90% dos cartazes que vê todos os dias.",
    },
};

export const ui = {
    es: {
        "nav.home": "Portada",
        "nav.postersTop": "Pegada de",
        "nav.postersBottom": "carteles",
        "nav.servicesTop": "Servicios",
        "nav.servicesBottom": "publicitarios",
        "nav.jobs": "Trabajos",
        "nav.agency": "Agencia",
        "nav.blog": "Blog",
        "nav.contact": "Contacto",
        "nav.openMenu": "Abrir menú de navegación",
        "nav.closeMenu": "Cerrar menú de navegación",

        "footer.sloganHome": "Somos Urban Style Publicity",
        "footer.slogan": "Tu agencia de publicidad exterior",
        "footer.services": "Servicios publicitarios",
        "footer.latest": "Últimos artículos",
        "footer.social": "Redes sociales",
        "footer.phoneTitle": "Llamar a Urban Style Publicity",
        "footer.igTitle": "Pegadas de carteles en Instagram",
        "footer.fbTitle": "Pegadas de carteles en Facebook",
        "footer.twTitle": "Pegadas de carteles en Twitter",
        "footer.liTitle": "Pegadas de carteles en LinkedIn",
        "footer.ytTitle": "Pegada de carteles en Youtube",
        "footer.footerAlt": "Ilustración de una ciudad con fondo negro que complementa al pie de página",

        "form.name": "Dinos tu nombre",
        "form.nameTitle": "El nombre sólo puede tener letras y espacios",
        "form.email": "Introduce tu email",
        "form.emailTitle": "El email debe tener un formato correcto (ejemplo: usuario@dominio.com)",
        "form.phone": "Tu teléfono si quieres que te llamemos",
        "form.phoneTitle": "El teléfono debe tener al menos 9 dígitos",
        "form.message": "Escribe tu consulta",
        "form.messageTitle": "La consulta no puede estar vacía",
        "form.required": "* Necesario",
        "form.website": "Website",
        "form.submit": "Enviar Consulta",
        "form.reset": "Reiniciar formulario",
        "form.errRequired": "Este campo es requerido",
        "form.errConnection": "Error de conexión",

        "dossier.alt": "dossier para pegadas de carteles",
        "dossier.download": "Descargar dossier",
        "posterPasting.title": "Conoce nuestro servicio de",
        "posterPasting.link": "pegada de carteles",
        "posterPasting.alt": "pegada de carteles",

        "tabs.title": "Servicios de cartelería <br />complementarios",

        "videoWithDesc.items": [
            "Impresión en pequeño y gran formato",
            "Diseño y maquetación",
            "Pegada de carteles",
            "Arte urbano",
            "Carteles en locales",
            "Instalación de lonas y vinilos",
        ],

        "desc3.cta": "Pide más información sobre nuestra pegada de carteles",
        "desc3.whatsapp": "Escribir por Whatsapp",
        "desc3.call": "Llamar por teléfono",

        "blog.readMore": "Seguir leyendo",
        "blog.related": "Servicios relacionados",
        "blog.posterPastingIn": "Pegada de carteles en",

        "city.workMostlyIn": "Trabajamos principalmente en",
        "city.campaignTypes": "Tipos de campañas de cartelería en",
        "city.campaignCultural": "Campañas culturales",
        "city.campaignEvents": "Promoción de eventos",
        "city.campaignLocal": "Acciones de marketing local",
        "city.campaignLaunch": "Lanzamientos de marca",
        "city.faqTitle": "Preguntas frecuentes sobre la pegada de carteles en",
        "city.budgetTitle": "Presupuesto para hacer una pegada de carteles en",
        "city.budgetText": "Solicita un presupuesto personalizado para tu campaña en",
        "city.budgetTextEnd": "completando el formulario o llamándonos al",
        "city.seoTitle": "Pegada de carteles en",
        "city.seoDesc": "Servicio profesional de pegada de carteles en",
        "city.coverAlt": "Fotografía panorámica de la ciudad de",

        "services.view": "Ver servicio",

        "coverage.headerTitle": "Cobertura de pegada de carteles",
        "coverage.desc1Title": "Gestión unificada para <mark class='under'>campañas nacionales</mark>",
        "coverage.desc1Description":
            "Olvídate de coordinar con 50 proveedores locales. En <strong>Urban Style Publicity</strong> centralizamos toda tu campaña de pegada de carteles en España con un único intermediario e informes unificados.",
        "coverage.logosTitle": "Llevamos tu campañas a cada rincón de España",
        "coverage.selectProvince": "Selecciona tu Provincia",
        "coverage.presenceText":
            "Estamos presentes en todo el territorio nacional. Despliega el listado para ver las localidades activas.",
        "coverage.capital": "Capital",
        "coverage.localityOne": "LOCALIDAD",
        "coverage.localityMany": "LOCALIDADES",
        "coverage.nationalPresence": "Presencia Nacional",
        "coverage.cityMissing": "¿Tu ciudad no aparece?",
        "coverage.cityMissingDesc":
            "Cubrimos todas las ciudades de España. Si tu municipio no está en el listado, no te preocupes:",
        "coverage.cityMissingEm": "llegamos a cualquier rincón",
        "coverage.cta": "Pedir información",
    },
    en: {
        "nav.home": "Home",
        "nav.postersTop": "Poster",
        "nav.postersBottom": "pasting",
        "nav.servicesTop": "Advertising",
        "nav.servicesBottom": "services",
        "nav.jobs": "Work",
        "nav.agency": "Agency",
        "nav.blog": "Blog",
        "nav.contact": "Contact",
        "nav.openMenu": "Open navigation menu",
        "nav.closeMenu": "Close navigation menu",

        "footer.sloganHome": "We are Urban Style Publicity",
        "footer.slogan": "Your outdoor advertising agency",
        "footer.services": "Advertising services",
        "footer.latest": "Latest articles",
        "footer.social": "Social media",
        "footer.phoneTitle": "Call Urban Style Publicity",
        "footer.igTitle": "Poster pasting on Instagram",
        "footer.fbTitle": "Poster pasting on Facebook",
        "footer.twTitle": "Poster pasting on Twitter",
        "footer.liTitle": "Poster pasting on LinkedIn",
        "footer.ytTitle": "Poster pasting on Youtube",
        "footer.footerAlt": "Illustration of a city on a black background that complements the footer",

        "form.name": "Tell us your name",
        "form.nameTitle": "The name can only contain letters and spaces",
        "form.email": "Enter your email",
        "form.emailTitle": "The email must be valid (e.g. user@domain.com)",
        "form.phone": "Your phone number if you want us to call you",
        "form.phoneTitle": "The phone number must have at least 9 digits",
        "form.message": "Write your enquiry",
        "form.messageTitle": "The enquiry cannot be empty",
        "form.required": "* Required",
        "form.website": "Website",
        "form.submit": "Send Enquiry",
        "form.reset": "Reset form",
        "form.errRequired": "This field is required",
        "form.errConnection": "Connection error",

        "dossier.alt": "dossier for poster pasting",
        "dossier.download": "Download dossier",
        "posterPasting.title": "Check out our",
        "posterPasting.link": "poster pasting",
        "posterPasting.alt": "poster pasting",

        "tabs.title": "Complementary <br />poster services",

        "videoWithDesc.items": [
            "Small and large format printing",
            "Design and layout",
            "Poster pasting",
            "Street art",
            "Posters in venues",
            "Billboard and vinyl installation",
        ],

        "desc3.cta": "Ask for more info about our poster pasting",
        "desc3.whatsapp": "Write on Whatsapp",
        "desc3.call": "Call us",

        "blog.readMore": "Read more",
        "blog.related": "Related services",
        "blog.posterPastingIn": "Poster pasting in",

        "city.workMostlyIn": "We mainly work in",
        "city.campaignTypes": "Types of poster campaigns in",
        "city.campaignCultural": "Cultural campaigns",
        "city.campaignEvents": "Event promotion",
        "city.campaignLocal": "Local marketing actions",
        "city.campaignLaunch": "Brand launches",
        "city.faqTitle": "Frequently asked questions about poster pasting in",
        "city.budgetTitle": "Budget for a poster pasting in",
        "city.budgetText": "Request a personalised quote for your campaign in",
        "city.budgetTextEnd": "by filling in the form or calling us at",
        "city.seoTitle": "Poster pasting in",
        "city.seoDesc": "Professional poster pasting service in",
        "city.coverAlt": "Panoramic photograph of the city of",

        "services.view": "View service",

        "coverage.headerTitle": "Poster pasting coverage",
        "coverage.desc1Title": "One-stop management for <mark class='under'>national campaigns</mark>",
        "coverage.desc1Description":
            "Forget coordinating with 50 local providers. At <strong>Urban Style Publicity</strong> we centralise your entire poster pasting campaign across Spain with a single intermediary and unified reports.",
        "coverage.logosTitle": "We take your campaigns to every corner of Spain",
        "coverage.selectProvince": "Select your Province",
        "coverage.presenceText":
            "We are present throughout the national territory. Expand the list to see the active locations.",
        "coverage.capital": "Capital",
        "coverage.localityOne": "LOCALITY",
        "coverage.localityMany": "LOCALITIES",
        "coverage.nationalPresence": "National Presence",
        "coverage.cityMissing": "Can't find your city?",
        "coverage.cityMissingDesc":
            "We cover all cities in Spain. If your town is not on the list, don't worry:",
        "coverage.cityMissingEm": "we reach any corner",
        "coverage.cta": "Request information",
    },
    it: {
        "nav.home": "Home",
        "nav.postersTop": "Affissione",
        "nav.postersBottom": "manifesti",
        "nav.servicesTop": "Servizi",
        "nav.servicesBottom": "pubblicitari",
        "nav.jobs": "Lavori",
        "nav.agency": "Agenzia",
        "nav.blog": "Blog",
        "nav.contact": "Contatti",
        "nav.openMenu": "Apri il menu di navigazione",
        "nav.closeMenu": "Chiudi il menu di navigazione",

        "footer.sloganHome": "Siamo Urban Style Publicity",
        "footer.slogan": "La tua agenzia di pubblicità esterna",
        "footer.services": "Servizi pubblicitari",
        "footer.latest": "Ultimi articoli",
        "footer.social": "Social media",
        "footer.phoneTitle": "Chiama Urban Style Publicity",
        "footer.igTitle": "Affissione manifesti su Instagram",
        "footer.fbTitle": "Affissione manifesti su Facebook",
        "footer.twTitle": "Affissione manifesti su Twitter",
        "footer.liTitle": "Affissione manifesti su LinkedIn",
        "footer.ytTitle": "Affissione manifesti su Youtube",
        "footer.footerAlt": "Illustrazione di una città su sfondo nero che completa il piè di pagina",

        "form.name": "Dicci il tuo nome",
        "form.nameTitle": "Il nome può contenere solo lettere e spazi",
        "form.email": "Inserisci la tua email",
        "form.emailTitle": "L'email deve avere un formato valido (es. utente@dominio.com)",
        "form.phone": "Il tuo telefono se vuoi che ti chiamiamo",
        "form.phoneTitle": "Il telefono deve avere almeno 9 cifre",
        "form.message": "Scrivi la tua richiesta",
        "form.messageTitle": "La richiesta non può essere vuota",
        "form.required": "* Obbligatorio",
        "form.website": "Sito web",
        "form.submit": "Invia Richiesta",
        "form.reset": "Azzera modulo",
        "form.errRequired": "Questo campo è obbligatorio",
        "form.errConnection": "Errore di connessione",

        "dossier.alt": "dossier per affissione manifesti",
        "dossier.download": "Scarica dossier",
        "posterPasting.title": "Scopri il nostro servizio di",
        "posterPasting.link": "affissione manifesti",
        "posterPasting.alt": "affissione manifesti",

        "tabs.title": "Servizi complementari <br />di cartellonistica",

        "videoWithDesc.items": [
            "Stampa piccolo e grande formato",
            "Design e impaginazione",
            "Affissione manifesti",
            "Arte urbana",
            "Manifesti nei locali",
            "Installazione di teloni e vinili",
        ],

        "desc3.cta": "Chiedi più informazioni sulla nostra affissione manifesti",
        "desc3.whatsapp": "Scrivi su Whatsapp",
        "desc3.call": "Chiama",

        "blog.readMore": "Leggi di più",
        "blog.related": "Servizi correlati",
        "blog.posterPastingIn": "Affissione manifesti a",

        "city.workMostlyIn": "Lavoriamo principalmente in",
        "city.campaignTypes": "Tipi di campagne di affissione a",
        "city.campaignCultural": "Campagne culturali",
        "city.campaignEvents": "Promozione di eventi",
        "city.campaignLocal": "Azioni di marketing locale",
        "city.campaignLaunch": "Lanci di marca",
        "city.faqTitle": "Domande frequenti sull'affissione manifesti a",
        "city.budgetTitle": "Preventivo per un'affissione manifesti a",
        "city.budgetText": "Richiedi un preventivo personalizzato per la tua campagna a",
        "city.budgetTextEnd": "compilando il modulo o chiamandoci al",
        "city.seoTitle": "Affissione manifesti a",
        "city.seoDesc": "Servizio professionale di affissione manifesti a",
        "city.coverAlt": "Foto panoramica della città di",

        "services.view": "Vedi servizio",

        "coverage.headerTitle": "Copertura di affissione manifesti",
        "coverage.desc1Title": "Gestione unificata per <mark class='under'>campagne nazionali</mark>",
        "coverage.desc1Description":
            "Dimentica di coordinarti con 50 fornitori locali. In <strong>Urban Style Publicity</strong> centralizziamo tutta la tua campagna di affissione manifesti in Spagna con un unico intermediario e report unificati.",
        "coverage.logosTitle": "Portiamo le tue campagne in ogni angolo della Spagna",
        "coverage.selectProvince": "Seleziona la tua provincia",
        "coverage.presenceText":
            "Siamo presenti in tutto il territorio nazionale. Apri l'elenco per vedere le località attive.",
        "coverage.capital": "Capoluogo",
        "coverage.localityOne": "LOCALITÀ",
        "coverage.localityMany": "LOCALITÀ",
        "coverage.nationalPresence": "Presenza Nazionale",
        "coverage.cityMissing": "La tua città non compare?",
        "coverage.cityMissingDesc":
            "Copriamo tutte le città della Spagna. Se il tuo comune non è nell'elenco, non preoccuparti:",
        "coverage.cityMissingEm": "arriviamo in ogni angolo",
        "coverage.cta": "Richiedi informazioni",
    },
    fr: {
        "nav.home": "Accueil",
        "nav.postersTop": "Collage",
        "nav.postersBottom": "d'affiches",
        "nav.servicesTop": "Services",
        "nav.servicesBottom": "publicitaires",
        "nav.jobs": "Travaux",
        "nav.agency": "Agence",
        "nav.blog": "Blog",
        "nav.contact": "Contact",
        "nav.openMenu": "Ouvrir le menu de navigation",
        "nav.closeMenu": "Fermer le menu de navigation",

        "footer.sloganHome": "Nous sommes Urban Style Publicity",
        "footer.slogan": "Votre agence de publicité extérieure",
        "footer.services": "Services publicitaires",
        "footer.latest": "Derniers articles",
        "footer.social": "Réseaux sociaux",
        "footer.phoneTitle": "Appeler Urban Style Publicity",
        "footer.igTitle": "Collage d'affiches sur Instagram",
        "footer.fbTitle": "Collage d'affiches sur Facebook",
        "footer.twTitle": "Collage d'affiches sur Twitter",
        "footer.liTitle": "Collage d'affiches sur LinkedIn",
        "footer.ytTitle": "Collage d'affiches sur Youtube",
        "footer.footerAlt": "Illustration d'une ville sur fond noir complétant le pied de page",

        "form.name": "Dites-nous votre nom",
        "form.nameTitle": "Le nom ne peut contenir que des lettres et des espaces",
        "form.email": "Saisissez votre email",
        "form.emailTitle": "L'email doit avoir un format valide (ex. utilisateur@domaine.com)",
        "form.phone": "Votre téléphone si vous souhaitez que nous vous appelions",
        "form.phoneTitle": "Le téléphone doit comporter au moins 9 chiffres",
        "form.message": "Écrivez votre demande",
        "form.messageTitle": "La demande ne peut pas être vide",
        "form.required": "* Requis",
        "form.website": "Site web",
        "form.submit": "Envoyer la demande",
        "form.reset": "Réinitialiser le formulaire",
        "form.errRequired": "Ce champ est obligatoire",
        "form.errConnection": "Erreur de connexion",

        "dossier.alt": "dossier pour collage d'affiches",
        "dossier.download": "Télécharger le dossier",
        "posterPasting.title": "Découvrez notre service de",
        "posterPasting.link": "collage d'affiches",
        "posterPasting.alt": "collage d'affiches",

        "tabs.title": "Services complémentaires <br />d'affichage",

        "videoWithDesc.items": [
            "Impression petit et grand format",
            "Design et mise en page",
            "Collage d'affiches",
            "Art urbain",
            "Affiches en locaux",
            "Installation de bâches et vinyle",
        ],

        "desc3.cta": "Demandez plus d'informations sur notre collage d'affiches",
        "desc3.whatsapp": "Écrire sur Whatsapp",
        "desc3.call": "Appeler",

        "blog.readMore": "Lire la suite",
        "blog.related": "Services liés",
        "blog.posterPastingIn": "Collage d'affiches à",

        "city.workMostlyIn": "Nous travaillons principalement dans",
        "city.campaignTypes": "Types de campagnes d'affichage à",
        "city.campaignCultural": "Campagnes culturelles",
        "city.campaignEvents": "Promotion d'événements",
        "city.campaignLocal": "Actions de marketing local",
        "city.campaignLaunch": "Lancements de marque",
        "city.faqTitle": "Questions fréquentes sur le collage d'affiches à",
        "city.budgetTitle": "Devis pour un collage d'affiches à",
        "city.budgetText": "Demandez un devis personnalisé pour votre campagne à",
        "city.budgetTextEnd": "en remplissant le formulaire ou en nous appelant au",
        "city.seoTitle": "Collage d'affiches à",
        "city.seoDesc": "Service professionnel de collage d'affiches à",
        "city.coverAlt": "Photographie panoramique de la ville de",

        "services.view": "Voir le service",

        "coverage.headerTitle": "Couverture de collage d'affiches",
        "coverage.desc1Title": "Gestion unifiée pour <mark class='under'>campagnes nationales</mark>",
        "coverage.desc1Description":
            "Oubliez la coordination avec 50 prestataires locaux. Chez <strong>Urban Style Publicity</strong>, nous centralisons toute votre campagne de collage d'affiches en Espagne avec un seul intermédiaire et des rapports unifiés.",
        "coverage.logosTitle": "Nous portons vos campagnes dans chaque recoin d'Espagne",
        "coverage.selectProvince": "Sélectionnez votre province",
        "coverage.presenceText":
            "Nous sommes présents sur tout le territoire national. Déployez la liste pour voir les localités actives.",
        "coverage.capital": "Chef-lieu",
        "coverage.localityOne": "LOCALITÉ",
        "coverage.localityMany": "LOCALITÉS",
        "coverage.nationalPresence": "Présence Nationale",
        "coverage.cityMissing": "Votre ville n'apparaît pas ?",
        "coverage.cityMissingDesc":
            "Nous couvrons toutes les villes d'Espagne. Si votre commune n'est pas dans la liste, ne vous inquiétez pas :",
        "coverage.cityMissingEm": "nous atteignons chaque recoin",
        "coverage.cta": "Demander des informations",
    },
    pt: {
        "nav.home": "Início",
        "nav.postersTop": "Colagem",
        "nav.postersBottom": "de cartazes",
        "nav.servicesTop": "Serviços",
        "nav.servicesBottom": "publicitários",
        "nav.jobs": "Trabalhos",
        "nav.agency": "Agência",
        "nav.blog": "Blog",
        "nav.contact": "Contacto",
        "nav.openMenu": "Abrir menu de navegação",
        "nav.closeMenu": "Fechar menu de navegação",

        "footer.sloganHome": "Somos a Urban Style Publicity",
        "footer.slogan": "A sua agência de publicidade exterior",
        "footer.services": "Serviços publicitários",
        "footer.latest": "Últimos artigos",
        "footer.social": "Redes sociais",
        "footer.phoneTitle": "Ligar para a Urban Style Publicity",
        "footer.igTitle": "Colagem de cartazes no Instagram",
        "footer.fbTitle": "Colagem de cartazes no Facebook",
        "footer.twTitle": "Colagem de cartazes no Twitter",
        "footer.liTitle": "Colagem de cartazes no LinkedIn",
        "footer.ytTitle": "Colagem de cartazes no Youtube",
        "footer.footerAlt": "Ilustração de uma cidade sobre fundo preto que complementa o rodapé",

        "form.name": "Diga-nos o seu nome",
        "form.nameTitle": "O nome só pode conter letras e espaços",
        "form.email": "Introduza o seu email",
        "form.emailTitle": "O email deve ter um formato válido (ex.: utilizador@dominio.com)",
        "form.phone": "O seu telefone se quiser que lhe liguemos",
        "form.phoneTitle": "O telefone deve ter pelo menos 9 dígitos",
        "form.message": "Escreva a sua consulta",
        "form.messageTitle": "A consulta não pode estar vazia",
        "form.required": "* Necessário",
        "form.website": "Site",
        "form.submit": "Enviar Consulta",
        "form.reset": "Repor formulário",
        "form.errRequired": "Este campo é obrigatório",
        "form.errConnection": "Erro de ligação",

        "dossier.alt": "dossier para colagem de cartazes",
        "dossier.download": "Descarregar dossier",
        "posterPasting.title": "Conheça o nosso serviço de",
        "posterPasting.link": "colagem de cartazes",
        "posterPasting.alt": "colagem de cartazes",

        "tabs.title": "Serviços complementares <br />de cartazaria",

        "videoWithDesc.items": [
            "Impressão em pequeno e grande formato",
            "Design e paginação",
            "Colagem de cartazes",
            "Arte urbana",
            "Cartazes em espaços",
            "Instalação de lonas e vinis",
        ],

        "desc3.cta": "Peça mais informações sobre a nossa colagem de cartazes",
        "desc3.whatsapp": "Escrever no Whatsapp",
        "desc3.call": "Ligar",

        "blog.readMore": "Continuar a ler",
        "blog.related": "Serviços relacionados",
        "blog.posterPastingIn": "Colagem de cartazes em",

        "city.workMostlyIn": "Trabalhamos principalmente em",
        "city.campaignTypes": "Tipos de campanhas de cartazes em",
        "city.campaignCultural": "Campanhas culturais",
        "city.campaignEvents": "Promoção de eventos",
        "city.campaignLocal": "Ações de marketing local",
        "city.campaignLaunch": "Lançamentos de marca",
        "city.faqTitle": "Perguntas frequentes sobre colagem de cartazes em",
        "city.budgetTitle": "Orçamento para colagem de cartazes em",
        "city.budgetText": "Solicite um orçamento personalizado para a sua campanha em",
        "city.budgetTextEnd": "preenchendo o formulário ou ligando-nos para",
        "city.seoTitle": "Colagem de cartazes em",
        "city.seoDesc": "Serviço profissional de colagem de cartazes em",
        "city.coverAlt": "Fotografia panorâmica da cidade de",

        "services.view": "Ver serviço",

        "coverage.headerTitle": "Cobertura de colagem de cartazes",
        "coverage.desc1Title": "Gestão unificada para <mark class='under'>campanhas nacionais</mark>",
        "coverage.desc1Description":
            "Esqueça a coordenação com 50 fornecedores locais. Na <strong>Urban Style Publicity</strong> centralizamos toda a sua campanha de colagem de cartazes em Espanha com um único intermediário e relatórios unificados.",
        "coverage.logosTitle": "Levamos as suas campanhas a todos os cantos de Espanha",
        "coverage.selectProvince": "Selecione a sua província",
        "coverage.presenceText":
            "Estamos presentes em todo o território nacional. Expanda a lista para ver as localidades ativas.",
        "coverage.capital": "Capital",
        "coverage.localityOne": "LOCALIDADE",
        "coverage.localityMany": "LOCALIDADES",
        "coverage.nationalPresence": "Presença Nacional",
        "coverage.cityMissing": "A sua cidade não aparece?",
        "coverage.cityMissingDesc":
            "Cobrimos todas as cidades de Espanha. Se o seu município não estiver na lista, não se preocupe:",
        "coverage.cityMissingEm": "chegamos a qualquer canto",
        "coverage.cta": "Pedir informações",
    },
} as const;

export type UiKey = keyof (typeof ui)["es"];

export function useTranslations(locale: Locale) {
    return function t(key: UiKey): string {
        const dict = ui[locale] ?? ui[defaultLocale];
        const value = dict[key];
        return typeof value === "string" ? value : "";
    };
}

export function getLocaleFromUrl(pathname: string): Locale {
    const match = pathname.match(/^\/(en|it|fr|pt)(?:\/|$)/);
    return match ? (match[1] as Locale) : defaultLocale;
}

export const localePrefixes = ["en", "it", "fr", "pt"] as const;

export function entryMatchesLocale(entry: { id: string }, locale: Locale): boolean {
    if (locale === defaultLocale) return !localePrefixes.some(prefix => entry.id.startsWith(`${prefix}/`));
    return entry.id.startsWith(`${locale}/`);
}

export async function getLocalizedCollection(
    name: string,
    locale: Locale,
    filter?: (entry: any) => boolean,
): Promise<any[]> {
    const all = (await getCollection(name as any)) as any[];
    const get = (loc: Locale) =>
        all.filter(e => entryMatchesLocale(e, loc) && (!filter || filter(e)));
    const localized = get(locale);
    if (localized.length > 0) return localized;
    return get(defaultLocale);
}