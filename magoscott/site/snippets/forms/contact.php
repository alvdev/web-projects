<div id="contact-message" x-merge.transition>
    <?php
    /**
     * @var \Kirby\Cms\Page $page
     * @var \Kirby\Cms\Site $site
     * @var \Kirby\Cms\App $kirby
     */

    snippet('dreamform/form', [
        'form' => $site->find('forms/contact'),
        'attr' => [
            'form' => [
                'class' => 'mt-16',
                'x-init',
                'x-target' => 'contact-message'
            ],
            'row' => [
                'class' => ''
            ],
            'column' => [
                'class' => 'group flex flex-col gap-8 has-[textarea]:relative has-[textarea]:mt-8 has-[textarea]:flex has-[textarea]:flex-row has-[textarea]:items-center'
            ],
            'field' => [
                'class' => 'relative flex flex-col *:[span]:absolute *:[span]:left-6 has-[textarea]:border-4 has-[textarea]:border-blue-500 has-[button]:absolute has-[button]:right-0'
            ],
            'label' => [
                'class' => 'hidden'
            ],
            'input' => [
                'class' => 'w-full text-2xl text-black bg-violet-300 rounded-4xl px-6 pt-4 pb-5.5 resize-none focus:ring-4 focus:ring-violet-900 outline-hidden transition-all placeholder:font-light group-data-has-error:placeholder:text-rose-400 group-data-has-error:mb-2 placeholder-shown:group-data-has-error:border-rose-400 after:content-[xxx]'
            ],
            'textarea' => [
                'row' => [
                    'class' => '',
                ],
                'field' => [
                    'class' => 'w-full flex flex-col mr-16 *:[textarea]:pr-28 *:[textarea]:h-32 *:[span]:absolute *:[span]:left-6',
                ],
            ],
            'button' => [
                'class' => 'w-min bg-violet-700 ring-4 ring-violet-400 uppercase font-semibold text-xl aspect-square rounded-full p-4 hover:bg-violet-800 hover:animate-wiggle after:content-[attr(after)] after:ml-2 after:mt-1 transition-all active:scale-90',
            ],
            'error' => [
                'class' => 'peer-not-placeholder-shown:hidden group-data-has-error:relative text-white text-sm text-shadow-md bg-red-700 px-2 leading-6 rounded-full -top-2.5'
            ],
            'radio' => [
                'label' => ['class' => 'text-sm font-medium text-slate-600 mb-2'],
                'input' => ['class' => 'w-4 rounded-full mr-2 shadow-xs border border-gray-200 '],
                'error' => ['class' => 'group-has-checked:hidden text-red-500 text-sm'],
                'row' => ['class' => 'flex items-center mb-1 text-sm'],
            ],
            'checkbox' =>  [
                'label' => ['class' => 'text-sm font-medium text-slate-600 mb-2'],
                'input' => ['class' => 'w-4 mr-2 shadow-xs border border-gray-200 rounded-xs'],
                'error' => ['class' => 'group-has-checked:hidden text-red-500 text-sm'],
                'row' => ['class' => 'flex items-center mb-1 text-sm'],
            ],
            'success' => [
                'class' => 'text-fuchsia-400 mt-16 border-3 border-dotted border-fuchsia-400 px-8 py-5 *:text-3xl *:font-semibold *:text-balance rounded-xl'
            ]
        ]
    ]);
    ?>
</div>
