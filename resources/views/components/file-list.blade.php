<div data-file-list class="overflow-auto flex-1 my-8 pb-8 relative">
    {{-- Индикатор загрузки --}}
    <div data-file-list-loading
         class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"
         style="display: none;">
        <div class="flex flex-col items-center space-y-4">
            <div class="relative">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 border-t-blue-600"></div>
                <div class="absolute inset-0 animate-ping rounded-full h-16 w-16 border-2 border-blue-400 opacity-20"></div>
            </div>
            <p class="text-lg font-semibold text-gray-700">{{ __('sfiles::sfiles.loading_files') }}</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="mb-6">
        <div class="relative">
            <i class="ph ph-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
            <input
                type="text"
                data-search
                placeholder="{{ __('sfiles::sfiles.search_placeholder') }}"
                class="w-full border-2 border-gray-200 rounded-xl px-12 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
            >
        </div>
    </div>

    {{-- Переключатель режима отображения --}}
    <div class="flex items-center justify-end mb-4 space-x-2 bg-white/50 backdrop-blur-sm p-2 rounded-xl">
        <button
            data-view-toggle="grid"
            class="px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg">
            <i class="ph ph-grid text-lg"></i>
            <span>{{ __('sfiles::sfiles.view_grid') }}</span>
        </button>
        <button
            data-view-toggle="list"
            class="px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium bg-white text-gray-600 hover:bg-gray-100">
            <i class="ph ph-list text-lg"></i>
            <span>{{ __('sfiles::sfiles.view_list') }}</span>
        </button>
    </div>

    {{-- Режим "Плитка" --}}
    <ul data-file-list-grid class="w-full flex flex-wrap gap-4 p-2 overflow-auto">
        {{-- Заполняется через JavaScript --}}
    </ul>

    {{-- List view --}}
    <ul data-file-list-list class="w-full space-y-2 overflow-auto" style="display: none;">
        {{-- Заполняется через JavaScript --}}
    </ul>

    {{-- Pagination --}}
    <div data-pagination
         class="mt-6 flex items-center justify-center space-x-2 bg-white/50 backdrop-blur-sm p-4 rounded-xl"
         style="display: none;">
        {{-- Заполняется через JavaScript --}}
    </div>
</div>
