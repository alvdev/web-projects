<?php

snippet('dreamform/form', [
    'form' => $site->find('forms/contact'),
    'attr' => [
        'form' => [
            'class' => 'flex flex-col items-center',
            'x-init',
            'x-target' => 'contact-message'
        ],
        'row' => [
            'class' => 'w-full mb-8 last:mb-0 flex gap-8'
        ],
        'field' => [
            'class' => 'group flex flex-col items-start'
        ],
        'label' => [
            'class' => 'text-sm mb-2 block first-letter:uppercase'
        ],
        'input' => [
            'class' => 'w-full bg-white border-3 border-emerald-200 rounded-lg p-4 focus:border-emerald-400 outline-hidden transition-all placeholder:font-light group-data-has-error:placeholder:text-rose-400 group-data-has-error:mb-2 placeholder-shown:group-data-has-error:border-rose-400 after:content-[xxx]'
        ],
        'button' => [
            'class' => 'btn-primary group flex items-center justify-center flex-wrap  w-fit min-w-[50%] mt-4 hover:animate-wiggle after:content-[attr(after)] after:ml-2 after:mt-1 group-hover:after:animate-ping',
            'after' => '🡭',
        ],
        'error' => [
            'class' => 'peer-not-placeholder-shown:hidden group-data-has-error:-mb-4 text-rose-400 text-sm'
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
            'class' => 'text-green-500 border-2 border-green-500 px-4 py-8 text-center text-sm'
        ]
    ]
]);
