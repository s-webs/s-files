<div class="overflow-auto flex-1 my-8 pb-8 relative">
    {{-- Индикатор загрузки при переходе по директориям --}}
    <div x-show="loading"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center rounded-xl"
         style="display: none;">
        <div class="flex flex-col items-center space-y-4">
            <div class="relative">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-200 border-t-blue-600"></div>
                <div class="absolute inset-0 animate-ping rounded-full h-16 w-16 border-2 border-blue-400 opacity-20"></div>
            </div>
            <p class="text-lg font-semibold text-gray-700">Загрузка файлов...</p>
        </div>
    </div>

    {{-- Поле поиска --}}
    <div class="mb-6">
        <div class="relative">
            <i class="ph ph-magnifying-glass absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400 text-xl"></i>
            <input
                type="text"
                x-model="searchQuery"
                placeholder="Поиск по файлам..."
                class="w-full border-2 border-gray-200 rounded-xl px-12 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white/80 backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200"
            >
        </div>
    </div>

    {{-- Переключатель режима отображения --}}
    <div class="flex items-center justify-end mb-4 space-x-2 bg-white/50 backdrop-blur-sm p-2 rounded-xl">
        <button
            @click="toggleView('grid')"
            :class="viewMode === 'grid'
                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                : 'bg-white text-gray-600 hover:bg-gray-100'"
            class="px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium">
            <i class="ph ph-grid text-lg"></i>
            <span>Плитка</span>
        </button>
        <button
            @click="toggleView('list')"
            :class="viewMode === 'list'
                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                : 'bg-white text-gray-600 hover:bg-gray-100'"
            class="px-4 py-2 rounded-lg transition-all duration-200 flex items-center space-x-2 font-medium">
            <i class="ph ph-list text-lg"></i>
            <span>Список</span>
        </button>
    </div>

    {{-- Режим "Плитка" --}}
    <ul
        x-show="viewMode === 'grid' && !loading"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full flex flex-wrap gap-4 p-2 overflow-auto"
    >
        <template x-for="file in filteredFiles" :key="file.opPath">
            <li class="relative group">
                {{-- Чекбокс --}}
                <div class="absolute top-2 left-2 z-[3] opacity-0 group-hover:opacity-100 transition-opacity">
                    <input type="checkbox"
                           :value="file.opPath"
                           x-model="selectedFiles"
                           class="form-checkbox h-5 w-5 bg-white shadow-lg">
                </div>

                {{-- Превью / иконка файла --}}
                <div
                    @contextmenu.prevent="openFileContextMenu(file, $event)"
                    class="relative"
                >
                    <a :href="fileHref(file)"
                       target="_blank"
                       class="block border-2 border-gray-200 bg-white w-[140px] h-[160px] flex flex-col items-center p-3 rounded-2xl hover:shadow-2xl hover:border-blue-400 transition-all duration-300 transform hover:scale-105 hover:-translate-y-1">
                        <template x-if="isImage(file)">
                            <div class="w-full h-[100px] mb-2 rounded-xl overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                                <img :src="fileHref(file)"
                                     class="w-full h-full object-cover"
                                     alt="Thumbnail">
                            </div>
                        </template>
                        <template x-if="!isImage(file)">
                            <div class="w-full h-[100px] mb-2 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                                <i :class="getFileIcon(file) + ' text-5xl'"></i>
                            </div>
                        </template>
                        <div class="w-full text-center text-xs font-semibold text-gray-700 truncate mb-1">
                            <span x-text="file.name"></span>
                        </div>
                        <div class="w-full text-center text-xs text-gray-500">
                            <span x-text="formatFileSize(file.size)"></span>
                        </div>
                    </a>
                </div>
            </li>
        </template>

        <template x-if="filteredFiles.length === 0">
            <li class="w-full text-center py-16">
                <div class="flex flex-col items-center">
                    <i class="ph ph-file-x text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg font-medium">Файлы не найдены</p>
                </div>
            </li>
        </template>
    </ul>

    {{-- Режим "Список" --}}
    <ul
        x-show="viewMode === 'list' && !loading"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="w-full space-y-2 overflow-auto"
    >
        <template x-for="file in filteredFiles" :key="file.opPath">
            <li class="flex items-center justify-between bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl px-4 py-3 hover:bg-white hover:border-blue-400 hover:shadow-lg transition-all duration-200 group relative">
                {{-- Чекбокс --}}
                <div class="mr-4">
                    <input type="checkbox"
                           :value="file.opPath"
                           x-model="selectedFiles"
                           class="form-checkbox h-5 w-5">
                </div>

                {{-- Иконка или превью --}}
                <div class="flex items-center flex-1 cursor-pointer min-w-0"
                     @contextmenu.prevent.stop="openFileContextMenu(file, $event)">

                    <template x-if="isImage(file)">
                        <div class="w-12 h-12 rounded-lg overflow-hidden mr-4 shadow-md flex-shrink-0">
                            <img :src="fileHref(file)"
                                 class="w-full h-full object-cover"
                                 alt="Thumbnail">
                        </div>
                    </template>

                    <template x-if="!isImage(file)">
                        <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center mr-4 shadow-md flex-shrink-0">
                            <i :class="getFileIcon(file) + ' text-2xl'"></i>
                        </div>
                    </template>

                    {{-- Название и размер --}}
                    <div class="flex-1 overflow-hidden min-w-0">
                        <a :href="fileHref(file)"
                           target="_blank"
                           class="text-sm font-semibold text-gray-800 truncate hover:text-blue-600 transition-colors block"
                           x-text="file.name"></a>
                        <div class="text-xs text-gray-500 mt-1">
                            <span x-text="formatFileSize(file.size)"></span>
                        </div>
                    </div>
                </div>

                {{-- Кнопка «еще» (три точки) для быстрого доступа к контекст-меню --}}
                <div class="ml-4">
                    <button
                        @click.prevent="openFileContextMenu(file, $event)"
                        class="text-gray-400 hover:text-blue-600 hover:bg-blue-50 px-3 py-2 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                        <i class="ph ph-dots-three-outline-vertical text-xl"></i>
                    </button>
                </div>
            </li>
        </template>

        <template x-if="filteredFiles.length === 0">
            <li class="w-full text-center py-16">
                <div class="flex flex-col items-center">
                    <i class="ph ph-file-x text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg font-medium">Файлы не найдены</p>
                </div>
            </li>
        </template>
    </ul>

    {{-- Пагинация --}}
    <div x-show="pagination.enabled && pagination.totalPages > 1 && !loading"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="mt-6 flex items-center justify-center space-x-2 bg-white/50 backdrop-blur-sm p-4 rounded-xl"
         style="display: none;">
        <button @click="goToPage(pagination.currentPage - 1)"
                :disabled="pagination.currentPage === 1"
                :class="pagination.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-50 hover:text-blue-600'"
                class="px-4 py-2 border-2 border-gray-200 rounded-lg transition-all duration-200 font-medium">
            <i class="ph ph-caret-left"></i>
        </button>

        <template x-for="page in Array.from({length: Math.min(5, pagination.totalPages)}, (_, i) => {
            const start = Math.max(1, pagination.currentPage - 2);
            return start + i;
        }).filter(p => p <= pagination.totalPages)" :key="page">
            <button @click="goToPage(page)"
                    :class="page === pagination.currentPage
                        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg'
                        : 'bg-white hover:bg-blue-50 border-2 border-gray-200 hover:border-blue-400'"
                    class="px-4 py-2 rounded-lg min-w-[44px] font-semibold transition-all duration-200">
                <span x-text="page"></span>
            </button>
        </template>

        <button @click="goToPage(pagination.currentPage + 1)"
                :disabled="pagination.currentPage === pagination.totalPages"
                :class="pagination.currentPage === pagination.totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-50 hover:text-blue-600'"
                class="px-4 py-2 border-2 border-gray-200 rounded-lg transition-all duration-200 font-medium">
            <i class="ph ph-caret-right"></i>
        </button>

        <span class="text-sm text-gray-600 ml-6 font-medium">
            Страница <span x-text="pagination.currentPage" class="font-bold text-blue-600"></span> из <span x-text="pagination.totalPages" class="font-bold"></span>
            <span class="text-gray-400 mx-2">•</span>
            <span x-text="pagination.total" class="font-bold"></span> файлов
        </span>
    </div>
</div>
