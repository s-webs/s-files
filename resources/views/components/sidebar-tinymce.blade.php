<div class="bg-gradient-to-b from-slate-800 via-slate-700 to-slate-800 w-1/3 xl:w-1/5 h-full overflow-y-auto shadow-2xl border-r border-slate-600">
    <div class="sticky top-0 z-10 bg-gradient-to-r from-slate-700 to-slate-800 border-b border-slate-600">
        <button @click="goUp()"
                :disabled="loading"
                :class="loading ? 'opacity-50 cursor-wait' : 'hover:bg-slate-600/50'"
                class="p-4 w-full text-start font-semibold flex items-center justify-between text-white transition-all duration-200 group relative">
            <span class="flex items-center space-x-2">
                <i class="ph ph-arrow-elbow-up-left text-xl group-hover:translate-x-[-4px] transition-transform"></i>
                <span x-text="t('back')"></span>
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

    <div class="relative">
        <ul>
            <template x-for="dir in directories" :key="dir">
                <li>
                    <button
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
    </div>
</div>
