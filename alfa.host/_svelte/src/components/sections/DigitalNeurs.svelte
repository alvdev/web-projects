<script>
  import { onMount } from 'svelte';
  import Glide from '@glidejs/glide';

  onMount(() => {
    new Glide('.glide-neurs', {
      type: 'carousel',
      animationDuration: 1000,
      perView: 2,
      autoplay: 4000,
      breakpoints: {
        700: {
          perView: 1
        }
      }
    }).mount();
  });
</script>

<section class="px-8 mb-24 text-center overflow-hidden">
  <h3 class="text-4xl mb-16">Empresarios digitales</h3>

  <div class="glide-neurs">
    <div data-glide-el="track" class="glide__track">
      <div class="glide__slides flex gap-24 items-start">
        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-mark-zuckerberg.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>
            Si realizas primero las cosas que son más simples, después podrás realmente hacer
            grandes progresos.
          </p>
          <address>
            <b>Mark Zuckerberg</b>, creador de Facebook
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-steve-jobs.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>
            Estoy convencido de lo que separa a los emprendedores exitosos de los no exitosos es la
            perseverancia.
          </p>
          <address>
            <b>Steve Jobs</b>, fundador de Apple
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-larry-page.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>
            No necesitas ser empresario y tener una empresa de 100 personas para desarrollar una
            idea en Internet.
          </p>
          <address>
            <b>Larry Page</b>, cofundador de Google
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-sean-parker.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>
            Sufro de la ilusión de que cada producto que está en mi imaginación no sólo es posible
            sino que siempre está en la antesala de convertirse en realidad.
          </p>
          <address>
            <b>Sean Parker</b>, confundador de Napster
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-bill-gates.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>Si naciste pobre no es tu culpa. Pero si mueres pobre, eso sí es tu culpa.</p>
          <address>
            <b>Bill Gates</b>, fundador de Microsoft
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-jeff-bezos.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>
            Uno de los enormes errores que comete la gente es tratar de forzarse un interés. No se
            eligen tus pasiones; tus pasiones te eligen a ti.
          </p>
          <address>
            <b>Jeff Bezos</b>, fundador de Amazon
          </address>
        </article>

        <article class="glide__slide flex justify-center flex-col gap-6">
          <div class="flex justify-center items-center relative">
            <img
              class="rounded-full z-10 border-2 border-blue-300"
              src="images/digital-neurs/empresario-digital-matt-mullenweg.jpg"
              alt=""
            />
            <div class="absolute w-full flex justify-center flex-col gap-1 items-center">
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
              <span class="bg-blue-300 block w-2/3 h-pxx rounded" />
              <span class="bg-blue-300 block w-3/5 h-pxx rounded" />
            </div>
          </div>
          <p>No tengo grandes ideas. En ocasiones tengo pequeñas ideas que parecen funcionar.</p>
          <address>
            <b>Matt Mullenweg</b>, creador de Wordpress
          </address>
        </article>
      </div>
    </div>
    <div class="glide__bullets mt-12" data-glide-el="controls[nav]">
      <button class="w-2 h-2 bg-black" data-glide-dir="=0" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=1" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=2" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=3" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=4" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=5" />
      <button class="w-2 h-2 bg-black" data-glide-dir="=6" />
    </div>
  </div>
</section>
