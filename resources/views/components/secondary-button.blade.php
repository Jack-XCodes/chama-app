<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-brown-200 rounded-md font-semibold text-xs text-brown-700 uppercase tracking-widest shadow-sm hover:bg-brown-50 focus:outline-none focus:ring-2 focus:ring-brown-400 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
