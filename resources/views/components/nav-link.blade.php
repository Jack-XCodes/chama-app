@props(['active'])

@php
    $classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-brown-500 text-sm font-medium leading-5 text-brown-800 focus:outline-none focus:border-brown-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-brown-600 hover:text-brown-800 hover:border-brown-300 focus:outline-none focus:text-brown-800 focus:border-brown-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
