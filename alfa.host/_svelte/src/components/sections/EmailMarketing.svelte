<script>
  import Carousel from '../EmailMarketingCarousel.svelte';
  import Beyonk from '../BeyonkCarousel.svelte';
</script>

<section class="px-8 mb-24 text-center">
  <h2 class="mb-16">
    <span class="block mb-2 text-xl font-semibold subtitle"
      >Un negocio digital está cojo sin un avanzado sistema de Email Marketing</span
    >
    El tercer engranaje para<br />un empresario digital
  </h2>
  <div class="flex flex-col mb-24 shadow-2xl md:flex-row">
    <div class="px-8 pt-12 text-white bg-blue-600 rounded-t pb-14 md:rounded-l md:rounded-r-none">
      <h3 class="mb-2 text-xl font-normal">
        Para emprendedores digitales comprometidos en realizar envíos elevados de emails
      </h3>
      <div class="flex items-center justify-center mb-8 text-3xl font-bold uppercase ">
        alfa mailer
        <span class="px-0 ml-2 text-sm font-medium leading-4 text-black bg-white rounded ">uno</span
        >
      </div>
      <div class="p-4 text-6xl font-bold border-2 rounded-t">
        $20.95<span class="text-lg font-light">/mes</span>
      </div>
      <div class="p-1 mb-8 text-sm font-medium text-black bg-yellow-400 rounded-b ">
        ¡50-75% por debajo del precio de mercado!
      </div>
      <div class="mb-8 text-3xl">
        5000 suscriptores<br />Envíos ilimitados
      </div>
      <div class="mb-12 text-sm text-blue-100">* Incluido en el plan de hosting Alfa UNO</div>
      <button
        class="block w-3/4 p-4 m-auto font-semibold text-black uppercase bg-white border-2 border-blue-400 rounded shadow-md hover:shadow-inner"
      >
        Contratar
      </button>
    </div>
    <div class="px-8 pt-12 text-white bg-black pb-14">
      <h3 class="mb-2 text-xl font-normal">
        Para emprendedores con un negocio digital en constante crecimiento de suscriptores
      </h3>
      <div class="flex items-center justify-center mb-8 text-3xl font-bold uppercase ">
        alfa mailer
        <span class="px-0 ml-2 text-sm font-medium leading-4 text-black bg-white rounded ">dos</span
        >
      </div>
      <div class="p-4 text-6xl font-bold border-2 rounded-t">
        $10.95<span class="text-lg font-light">/mes</span>
      </div>
      <div class="p-1 mb-8 text-sm font-medium text-black bg-yellow-400 rounded-b ">
        ¡50-75% por debajo del precio de mercado!
      </div>
      <div class="mb-8 text-3xl">
        1000 suscriptores<br />Envíos ilimitados
      </div>
      <div class="mb-12 text-sm text-blue-100">* Incluido en el plan de hosting Alfa DOS</div>
      <button
        class="block w-3/4 p-4 m-auto font-semibold text-black uppercase bg-white border-2 border-gray-500 rounded shadow-md hover:shadow-inner"
      >
        Contratar
      </button>
    </div>
    <div class="px-8 pt-12 text-white bg-gray-500 rounded-b pb-14 md:rounded-r md:rounded-l-none">
      <h3 class="mb-2 text-xl font-normal">
        Para emprendedores que aún están empezando a crear su lista de email marketing
      </h3>
      <div class="flex items-center justify-center mb-8 text-3xl font-bold uppercase ">
        alfa mailer
        <span class="px-0 ml-2 text-sm font-medium leading-4 text-black bg-white rounded "
          >tres</span
        >
      </div>
      <div class="p-4 text-6xl font-bold border-2 rounded-t">
        $5.95<span class="text-lg font-light">/mes</span>
      </div>
      <div class="p-1 mb-8 text-sm font-medium text-black bg-yellow-400 rounded-b ">
        ¡50-75% por debajo del precio de mercado!
      </div>
      <div class="mb-8 text-3xl">
        500 suscriptores<br />Envíos ilimitados
      </div>
      <div class="mb-12 text-sm text-blue-100">* Incluido en el plan de hosting Alfa TRES</div>
      <button
        class="block w-3/4 p-4 m-auto font-semibold text-black uppercase bg-white border-2 border-gray-400 rounded shadow-md hover:shadow-inner"
      >
        Contratar
      </button>
    </div>
  </div>

  <div class="grid gap-16 mb-16 text-left md:grid-cols-2">
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Constructor de emails adaptativos</h3>
        <p>
          Solo arrastrando y soltando elementos puedes crear emails y promociones que se adaptan a
          cualquier dispositivo móvil, tablet o PC. Fácil.
        </p>
      </div>
    </div>
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Autoresponders inteligentes</h3>
        <p>
          El visitante se suscribe a tu lista de contactos y comienza a recibir automáticamente
          mensajes, promociones y artículos de tu blog. Efectivo.
        </p>
      </div>
    </div>
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Gráficas profesionales de seguimiento</h3>
        <p>
          Sabrás quién ha abierto tus emails, desde qué país, en qué enlaces han hecho clic,
          porcentaje de aperturas, cancelaciones... Indispensable.
        </p>
      </div>
    </div>
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Plantillas de emails prediseñadas</h3>
        <p>
          Tienes cientos de emails con imágenes y estilos prediseñados para que solamente tengas que
          completarlos con tu texto y enviarlos. Rápido.
        </p>
      </div>
    </div>
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Importación de listas de contactos</h3>
        <p>
          Puedes importar tus contactos e incluirlos para recibir los emails de tu negocio
          cumpliendo con la Ley CAN-SPAM. No empieces desde cero.
        </p>
      </div>
    </div>
    <div class="flex items-start gap-3 md:gap-4">
      <div class="w-32 md:w-20 h-20 mt-0.5 text-sky-500">
        <svg
          data-icon="check-circle"
          role="img"
          xmlns="http://www.w3.org/2000/svg"
          viewbox="0 0 512 512"
          data-fa-i2svg=""
        >
          <path
            fill="currentColor"
            d="M256 8C119.033 8 8 119.033 8 256s111.033 248 248 248 248-111.033 248-248S392.967 8 256 8zm0 48c110.532 0 200 89.451 200 200 0 110.532-89.451 200-200 200-110.532 0-200-89.451-200-200 0-110.532 89.451-200 200-200m140.204 130.267l-22.536-22.718c-4.667-4.705-12.265-4.736-16.97-.068L215.346 303.697l-59.792-60.277c-4.667-4.705-12.265-4.736-16.97-.069l-22.719 22.536c-4.705 4.667-4.736 12.265-.068 16.971l90.781 91.516c4.667 4.705 12.265 4.736 16.97.068l172.589-171.204c4.704-4.668 4.734-12.266.067-16.971z"
          />
        </svg>
      </div>
      <div>
        <h3 class="mb-2 font-semibold">Segmentación avanzada de suscriptores</h3>
        <p>
          Un suscriptor no abre uno de tus emails. Se añade automáticamente a un segmento para
          volver a enviarle el email. Todo automatizado.
        </p>
      </div>
    </div>
  </div>
</section>

<div class="w-full mb-24">
  <Carousel />
</div>
