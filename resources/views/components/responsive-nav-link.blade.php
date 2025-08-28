@props(['active'])

@php
    $classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-brown-500 text-start text-base font-medium text-brown-800 bg-brown-50 focus:outline-none focus:text-brown-900 focus:bg-brown-100 focus:border-brown-700 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-brown-600 hover:text-brown-800 hover:bg-brown-50 hover:border-brown-300 focus:outline-none focus:text-brown-800 focus:bg-brown-50 focus:border-brown-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
