<section id="home-header" class="bg-linear-to-tl from-red-600 via-indigo-950 via-50% to-black">
    <div class="container pt-64 flex items-center gap-32 *:w-1/2">
        <div>
            <hgroup>
                <h1 class="text-7xl font-bold leading-none text-balance">Ofrece una experiencia única</h1>
                <p class="mt-8 text-4xl text-pretty">Puedes tener al Mago Scott en tu pub, discoteca o fiesta privada para que sientas la magia y el humor en directo.</p>
            </hgroup>

            <form action="" class="mt-16 flex flex-col gap-8 **:[input,textarea]:text-2xl **:[input,textarea]:text-black **:[input,textarea]:bg-violet-300 **:[input,textarea]:rounded-4xl **:[input,textarea]:px-6 **:[input,textarea]:py-4 **:[input,textarea]:pb-5.5">
                <input type="text" placeholder="Introduce tu nombre">
                <input type="email" placeholder="y un email al que poder responderte">
                <input type="tel" placeholder="o tu teléfono si prefieres una llamada">

                <div class="relative flex items-center">
                    <textarea name="" id="" class="w-full mr-12 h-32 resize-none" placeholder="Escribe tu pregunta o consulta aquí"></textarea>
                    <button type="submit" class="absolute right-0 bg-violet-700 ring-4 ring-violet-400 uppercase font-semibold text-xl aspect-square rounded-full p-4">Enviar<br> consulta</button>
                </div>
            </form>
        </div>

        <div>
            <?= asset('assets/images/scott.png') ?>
        </div>
    </div>
</section>
