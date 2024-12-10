<?php

snippet('dreamform/form', [
    'form' => $site->find('forms/newsletter'),
    'attr' => [
        'form' => [
            'class' => 'flex flex-col items-center',
            'x-init',
            'x-target' => 'newsletter-message'
        ],
        'row' => ['class' => 'w-full mb-8 last:mb-0'],
        'field' => ['class' => 'group flex flex-col items-start'],
        'label' => ['class' => 'hidden text-sm font-medium text-slate-600 mb-1'],
        'input' => ['class' => 'w-full bg-transparent border-b-2 border-green-500 text-green-500 placeholder:text-green-100 placeholder:font-light pb-2 outline-hidden peer group-data-has-error:placeholder:text-white group-data-has-error:mb-2 shadow-xs placeholder-shown:group-data-has-error:border-rose-400'],
        'button' => ['class' => 'bg-green-500 rounded-xs text-black inline-block w-fit min-w-[50%] px-8 py-2 mt-4 hover:animate-wiggle'],
        'error' => ['class' => 'peer-not-placeholder-shown:hidden group-data-has-error:-mb-4 text-rose-400 text-sm'],
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
