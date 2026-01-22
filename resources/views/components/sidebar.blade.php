<div class="bg-gradient-to-b from-slate-800 via-slate-700 to-slate-800 w-1/3 xl:w-1/5 h-full overflow-y-auto shadow-2xl border-r border-slate-600">
    <div class="sticky top-0 z-10 bg-gradient-to-r from-slate-700 to-slate-800 border-b border-slate-600">
        <button @click="goUp()"
                :disabled="loading"
                :class="loading ? 'opacity-50 cursor-wait' : 'hover:bg-slate-600/50'"
                class="p-4 w-full text-start font-semibold flex items-center justify-between text-white transition-all duration-200 group relative">
            <span class="flex items-center space-x-2">
                <i class="ph ph-arrow-elbow-up-left text-xl group-hover:translate-x-[-4px] transition-transform"></i>
                <span>Назад</span>
            </span>
            {{-- Мини-спиннер при загрузке --}}
            <div x-show="loading" 
                 x-cloak
                 class="absolute right-4 top-1/2 transform -translate-y-1/2"
                 style="display: none;">
                <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-300 border-t-blue-500"></div>
            </div>
        </button>
    </div>

    <div class="p-4 bg-slate-700/50 border-b border-slate-600">
        <div class="flex items-center space-x-2">
            <input type="text"
                   placeholder="Новая папка..."
                   x-model="newFolder"
                   @keydown.enter.prevent="createFolder()"
                   class="flex-1 py-2.5 px-4 rounded-lg bg-slate-600/50 border border-slate-500 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"/>
            <button @click="createFolder()"
                    class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                <i class="ph ph-plus text-lg"></i>
            </button>
        </div>
    </div>

    <div class="relative">
        <ul>
            <template x-for="dir in directories" :key="dir">
                <li>
                    <button
                        @contextmenu.prevent="openDirContextMenu(dir, $event)"
                        @click.stop="openDirectory(dir)"
                        :disabled="loading"
                        :class="loading ? 'opacity-50 cursor-wait' : 'hover:bg-slate-600/50'"
                        class="flex text-start justify-start items-center w-full py-3 px-4 text-white transition-all duration-200 group border-b border-slate-600/50 relative">
                        <i class="ph ph-folder text-xl mr-3 text-blue-400 group-hover:text-blue-300 transition-colors"></i>
                        <span class="text-sm font-medium truncate" x-text="dir.split('/').pop()"></span>
                        <i class="ph ph-caret-right ml-auto text-slate-400 group-hover:text-slate-300 opacity-0 group-hover:opacity-100 transition-all"></i>
                        {{-- Мини-спиннер при загрузке --}}
                        <div x-show="loading" 
                             x-cloak
                             class="absolute right-3 top-1/2 transform -translate-y-1/2"
                             style="display: none;">
                            <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-300 border-t-blue-500"></div>
                        </div>
                    </button>
                </li>
            </template>
        </ul>

        <div x-show="contextMenu.show"
             @click.stop
             :style="`top: ${contextMenu.y}px; left: ${contextMenu.x}px`"
             class="fixed bg-white/95 backdrop-blur-md shadow-2xl rounded-xl p-2 z-50 border border-gray-200 min-w-[180px]">
            <button @click="copyDirectoryLink(contextMenu.dir); closeContextMenus()"
                    class="flex items-center w-full px-4 py-2.5 hover:bg-blue-50 rounded-lg transition-colors text-gray-700 hover:text-blue-600">
                <i class="ph ph-copy mr-3 text-lg"></i>
                <span class="font-medium">Копировать путь</span>
            </button>
            <button @click="downloadFolder(contextMenu.dir); closeContextMenus()"
                    class="flex items-center w-full px-4 py-2.5 hover:bg-green-50 rounded-lg transition-colors text-gray-700 hover:text-green-600">
                <i class="ph ph-download-simple mr-3 text-lg"></i>
                <span class="font-medium">Скачать папку</span>
            </button>
            <button @click="openRenameForDir(contextMenu.dir); closeContextMenus()"
                    class="flex items-center w-full px-4 py-2.5 hover:bg-amber-50 rounded-lg transition-colors text-gray-700 hover:text-amber-600">
                <i class="ph ph-pencil-simple mr-3 text-lg"></i>
                <span class="font-medium">Переименовать</span>
            </button>
            <div class="border-t border-gray-200 my-1"></div>
            <button @click="deleteFolder(contextMenu.dir); closeContextMenus()"
                    class="flex items-center w-full px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <i class="ph ph-trash mr-3 text-lg"></i>
                <span class="font-medium">Удалить папку</span>
            </button>
        </div>
    </div>
</div>
