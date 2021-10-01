<script>
  import { onMount } from 'svelte';
  import Glide from '@glidejs/glide';

  onMount(() => {
    new Glide('.glide', {
      type: 'carousel',
      animationDuration: 1000,
      perView: 1,
      gap: 16,
      peek: 250,
      700: {
        peek: 20
      }
    }).mount();
  });
</script>

<div class="glide relative">
  <div data-glide-el="track" class="glide__track">
    <ul class="glide__slides flex">
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/1.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/2.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/3.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/4.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/5.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/6.png" alt="" class="" />
        <div class="overlay" />
      </li>
      <li class="glide__slide h-full">
        <img src="/images/email-marketing/7.png" alt="" class="" />
        <div class="overlay" />
      </li>
    </ul>
  </div>
  <div class="glide__arrows" data-glide-el="controls">
    <button
      class="glide__arrow glide__arrow--left absolute z-10 bg-black bg-opacity-70 hover:bg-opacity-80 top-0 h-full text-white px-10 text-6xl"
      data-glide-dir="<">&#10092;</button
    >

    <button
      class="glide__arrow glide__arrow--right absolute z-10 bg-black bg-opacity-70 hover:bg-opacity-80 top-0 right-0 h-full text-white px-10 text-6xl"
      data-glide-dir=">">&#10093;</button
    >
  </div>
</div>
