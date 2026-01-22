<div class="flex items-center gap-1 shrink-0 bg-white/80 backdrop-blur-sm border border-gray-200 rounded-lg px-1 py-1 shadow-sm">
    <button @click="setLocale('en')"
            :class="locale === 'en' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100'"
            class="px-3 py-1.5 rounded-md text-sm font-semibold transition-all duration-200">
        EN
    </button>
    <span class="text-gray-300">|</span>
    <button @click="setLocale('ru')"
            :class="locale === 'ru' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow' : 'text-gray-600 hover:bg-gray-100'"
            class="px-3 py-1.5 rounded-md text-sm font-semibold transition-all duration-200">
        RU
    </button>
</div>
