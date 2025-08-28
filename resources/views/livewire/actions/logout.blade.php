<form method="POST" action="{{ route('logout') }}" x-data>
    @csrf
    <button type="submit"
            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 focus:outline-none"
            @click.prevent="$root.submit();">
        {{ __('Log Out') }}
    </button>
</form>