<section id="contact" class="mt-20 md:mt-28 lg:mt-36 scroll-mt-42">
    <h2 class="container text-3xl md:text-4xl lg:text-5xl text-center">Reserva tu plaza completando el formulario para conseguir un <span class="font-bold">50% de descuento</span> en el período de lanzamiento del curso</h2>

    <div class="container mt-16 lg:mt-24 flex flex-col-reverse lg:grid lg:grid-cols-2 items-center gap-16 md:gap-24">
        <div class="text-center text-balance text-2xl">
            Si lo prefieres, puedes escribir un correo electrónico o llamar personalmente a Jaime Velasco:
            <div class="mt-12 flex flex-wrap gap-4 xl:gap-8 justify-center text-3xl font-bold">
                <div>
                    info@cursohipnosis.com
                </div>
                <div>
                    +34 659 053 976
                </div>
            </div>

            <div class="mt-16 md:mt-24">
                Pero si estás en tu teléfono móvil, puedes usar los siguientes botones para llamar o escribir directamente por Whatsapp:
            </div>

            <div class="mt-12 grid md:grid-cols-2 justify-center gap-4 text-white uppercase text-xl *:w-full">
                <a href="tel:+34659053976" class="flex items-center justify-center gap-4 bg-blue-950 pl-4 pr-8 py-4 rounded-lg *:[svg]:w-8 **:[path]:fill-white" aria-label="Llamar por teléfono">
                    <?= asset('icons/phone-calling.svg', true) ?>
                    Llamar por teléfono
                </a>
                <a href="https://wa.me/34659053976" class="flex items-center justify-center gap-4 bg-blue-950 pl-4 pr-8 py-4 rounded-lg *:[svg]:w-8 **:[path]:fill-white" aria-label="Escribir por Whatsapp">
                    <?= asset('icons/whatsapp.svg', true) ?>
                    Escribir por Whatsapp
                </a>
            </div>
        </div>

        <div class="w-full text-white">
            <form id="contact-form" action="submit.php" method="post" class="grid gap-6 md:gap-8 bg-blue-950 p-8 md:p-16 rounded-lg">
                <div class="field name relative">
                    <i class="absolute z-10 w-8 h-8 top-1/2 -translate-y-1/2 left-4">
                        <?= asset('icons/user.svg', true) ?>
                    </i>
                    <input class="text-xl md:text-2xl" type="text" name="name" placeholder="Nombre" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ\s]+"
                        title="Solo se permiten letras y espacios" required>
                    <div class="error-message" data-field="name"></div>
                </div>

                <div class="field email relative">
                    <i class="absolute z-10 w-8 h-8 top-1/2 -translate-y-1/2 left-4">
                        <?= asset('icons/at.svg', true) ?>
                    </i>
                    <input class="text-xl md:text-2xl" type="email" name="email"
                        placeholder="Correo Electrónico" required>
                    <div class="error-message" data-field="email"></div>
                </div>

                <div class="field phone relative">
                    <i class="absolute z-10 w-8 h-8 top-1/2 -translate-y-1/2 left-4">
                        <?= asset('icons/phone.svg', true) ?>
                    </i>
                    <input class="text-xl md:text-2xl" type="tel" name="phone" placeholder="Teléfono" pattern="\d{9}" maxlength="9" required>
                    <div class="error-message" data-field="phone"></div>
                </div>

                <div class="field website hidden">
                    <i class="absolute z-10 w-8 h-8 top-1/2 -translate-y-1/2 left-4">
                        <?= asset('icons/user.svg', true) ?>
                    </i>
                    <input class="text-xl md:text-2xl" type="text" name="website" placeholder="Website">
                </div>

                <div class="field message relative">
                    <i class="absolute z-10 w-8 h-8 top-4 left-4">
                        <?= asset('icons/question.svg', true) ?>
                    </i>
                    <textarea class="text-xl md:text-2xl" name="message"
                        placeholder="¿Por qué te interesa el curso?"></textarea>
                    <div class="error-message" data-field="message"></div>
                </div>

                <div id="response-banner" class="text-lime-400! text-center text-balance text-2xl"></div>

                <button type="submit" class="rounded-lg px-8 py-6 bg-blue-400 text-2xl md:text-3xl font-semibold drop-shadow-xl uppercase [text-shadow:1px_1px_5px_rgba(0,0,0,0.3)]">Reservar plaza</button>
            </form>
        </div>
    </div>
</section>
