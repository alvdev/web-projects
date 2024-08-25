<section id="contact" class="bg-emerald-100 bg-opacity-90 backdrop-blur-lg py-28 border-t-2 border-white">
    <div class="container flex gap-24">
        <div class="w-2/5">
            <hgroup class="font-bold leading-none flex flex-wrap flex-col gap-y-2 mb-10">
                <p class="text-emerald-800 text-xl">
                    Contacto
                </p>
                <h2 class="text-black-800 text-7xl">
                    Siempre contesto
                </h2>
            </hgroup>

            <div class="flex flex-wrap flex-col gap-7">
                <div class="flex flex-wrap gap-4 pb-6 border-b border-gray-300">
                    <svg class="w-10 h-10 fill-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="currentColor">
                        <path
                              d="M3 3H21C21.5523 3 22 3.44772 22 4V20C22 20.5523 21.5523 21 21 21H3C2.44772 21 2 20.5523 2 20V4C2 3.44772 2.44772 3 3 3ZM20 7.23792L12.0718 14.338L4 7.21594V19H20V7.23792ZM4.51146 5L12.0619 11.662L19.501 5H4.51146Z">
                        </path>
                    </svg>
                    <div class="flex flex-wrap flex-col flex-1">
                        <p class="leading-none">
                            Puedes escribirme a
                        </p>
                        <p class="text-xl font-bold text-black-800 leading-7">
                            soy@alv.dev
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 pb-6 border-b border-gray-300">
                    <svg class="w-10 h-10 fill-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="currentColor">
                        <path
                              d="M10 3H14C18.4183 3 22 6.58172 22 11C22 15.4183 18.4183 19 14 19V22.5C9 20.5 2 17.5 2 11C2 6.58172 5.58172 3 10 3ZM12 17H14C17.3137 17 20 14.3137 20 11C20 7.68629 17.3137 5 14 5H10C6.68629 5 4 7.68629 4 11C4 14.61 6.46208 16.9656 12 19.4798V17Z">
                        </path>
                    </svg>
                    <div class="flex flex-wrap flex-col flex-1">
                        <p class="leading-none">
                            Chatear conmigo en
                        </p>
                        <a class="link-green-xl" href="https://t.me/alvdev" target="_blank">
                            t.me/alvdev
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4">
                    <svg class="w-10 h-10 fill-gray-700" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                         fill="currentColor">
                        <path
                              d="M2 22C2 17.5817 5.58172 14 10 14C14.4183 14 18 17.5817 18 22H16C16 18.6863 13.3137 16 10 16C6.68629 16 4 18.6863 4 22H2ZM10 13C6.685 13 4 10.315 4 7C4 3.685 6.685 1 10 1C13.315 1 16 3.685 16 7C16 10.315 13.315 13 10 13ZM10 11C12.21 11 14 9.21 14 7C14 4.79 12.21 3 10 3C7.79 3 6 4.79 6 7C6 9.21 7.79 11 10 11ZM18.2837 14.7028C21.0644 15.9561 23 18.752 23 22H21C21 19.564 19.5483 17.4671 17.4628 16.5271L18.2837 14.7028ZM17.5962 3.41321C19.5944 4.23703 21 6.20361 21 8.5C21 11.3702 18.8042 13.7252 16 13.9776V11.9646C17.6967 11.7222 19 10.264 19 8.5C19 7.11935 18.2016 5.92603 17.041 5.35635L17.5962 3.41321Z">
                        </path>
                    </svg>
                    <div class="flex flex-wrap flex-col flex-1">
                        <p class="leading-none">
                            O seguirme en
                        </p>
                        <a class="link-blue-xl" href="https://www.linkedin.com/in/alvdev" target="_blank">
                            linkedin.com/in/alvdev
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-3/5">
            <div id="contact-message" x-merge.transition>
                <?php snippet('forms/contact') ?>
            </div>
        </div>
    </div>
</section>
