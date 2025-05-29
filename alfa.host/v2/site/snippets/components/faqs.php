<?php
$title = $title ?? 'Frequently Asked Questions';
$description = $description ?? 'Here are some of the most common questions we receive. If you have a question that is not answered here, please feel free to reach out to us.';
$columns = $columns ?? 2;

require_once 'faqsContent.php';
$faqs = $faqs ?? [];
$oddFaqs = [];
$evenFaqs = [];
foreach ($faqs as $index => $faq) {
    if ($index % 2 === 0) {
        $oddFaqs[] = $faq;
    } else {
        $evenFaqs[] = $faq;
    }
}
?>

<section class="mt-32">
    <div class="container">
        <hgroup class="mb-16 text-center uppercase">
            <h2 class="mt-5">
                <?= $title ?>
            </h2>

            <?php if ($description): ?>
                <p class="mt-4 text-lg text-neutral-600">
                    <?= $description ?>
                </p>
            <?php endif; ?>
        </hgroup>

        <?php if ($columns === 1): ?>
            <div
                x-data="{ activeAccordion: '', setActiveAccordion(id) { this.activeAccordion = (this.activeAccordion == id) ? '' : id } }"
                class="relative max-w-xl mx-auto">
                <div class="relative w-full">
                    <?php foreach ($faqs as $faq): ?>
                        <div
                            x-data="{ id: $id('accordion') }"
                            :class="{ 'text-neutral-900': activeAccordion==id, 'text-neutral-600 hover:text-neutral-900': activeAccordion!=id }"
                            class="hover:*:[button]:cursor-pointer">
                            <button
                                @click="setActiveAccordion(id)"
                                class="flex items-center justify-between w-full py-4 text-left select-none">
                                <span><?= $faq['question'] ?></span>
                                <div>
                                    <svg
                                        class="w-5 h-5 duration-300 ease-out"
                                        :class="{ '-rotate-[45deg]': activeAccordion==id }"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"></path>
                                    </svg>
                                </div>
                            </button>
                            <div x-show="activeAccordion==id" x-collapse x-cloak>
                                <div class="pb-4 opacity-70">
                                    <?= $faq['answer'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- end faqs -->
                </div>
            </div>
        <?php endif; ?>

        <?php if ($columns === 2): ?>
            <div
                x-data="{ activeAccordion: '', setActiveAccordion(id) { this.activeAccordion = (this.activeAccordion == id) ? '' : id } }"
                class="relative grid mx-auto lg:grid-cols-2 lg:gap-16">
                <div class="relative w-full">
                    <?php foreach ($oddFaqs as $faq): ?>
                        <div
                            x-data="{ id: $id('accordion') }"
                            :class="{ 'text-neutral-900': activeAccordion==id, '': activeAccordion!=id }"
                            class="relative not-first:mt-4 md:not-first:mt-6 lg:not-first:mt-8 text-black text-pretty hover:*:[button]:cursor-pointer after:absolute after:content-[''] after:w-full after:h-full after:left-1.5 after:-bottom-1.5 after:-z-50 after:bg-black transition-all">
                            <button
                                @click="setActiveAccordion(id)"
                                class="relative bg-slate-100 rounded flex items-center justify-between w-full py-4 px-6 text-left group">
                                <span><?= $faq['question'] ?></span>
                                <div>
                                    <svg
                                        class="w-5 h-5 duration-300 ease-out stroke-white bg-blue-700 rounded-full group-hover:ring-2 ring-blue-700"
                                        :class="{'-rotate-[45deg]': activeAccordion==id}"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"></path>
                                    </svg>
                                </div>
                            </button>
                            <div class="pb-1" x-show="activeAccordion==id" x-collapse x-cloak>
                                <div class="relative pl-4 pr-6 pt-6 pb-5">
                                    <?= $faq['answer'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- end oddFaqs -->
                </div>

                <div class="relative w-full">
                    <?php foreach ($evenFaqs as $faq): ?>
                        <div
                            x-data="{ id: $id('accordion') }"
                            :class="{ 'text-neutral-900': activeAccordion==id, '': activeAccordion!=id }"
                            class="relative mt-6 md:not-first:mt-6 lg:mt-0 lg:not-first:mt-8 text-black text-pretty hover:*:[button]:cursor-pointer after:absolute after:content-[''] after:w-full after:h-full after:left-1.5 after:-bottom-1.5 after:-z-50 after:bg-black transition-all">
                            <button
                                @click="setActiveAccordion(id)"
                                class="relative bg-slate-100 rounded flex items-center justify-between w-full py-4 px-6 text-left group">
                                <span><?= $faq['question'] ?></span>
                                <div>
                                    <svg
                                        class="w-5 h-5 duration-300 ease-out stroke-white bg-blue-700 rounded-full group-hover:ring-2 ring-blue-700"
                                        :class="{'-rotate-[45deg]': activeAccordion==id}"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6"></path>
                                    </svg>
                                </div>
                            </button>
                            <div class="pb-1" x-show="activeAccordion==id" x-collapse x-cloak>
                                <div class="relative pl-6 pr-6 pt-6 pb-5">
                                    <?= $faq['answer'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <!-- end evenFaqs -->
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
