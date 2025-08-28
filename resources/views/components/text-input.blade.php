@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-brown-300 focus:border-brown-500 focus:ring-brown-500 rounded-md shadow-sm']) }}>
