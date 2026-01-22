<div x-show="fileContextMenu.show"
     x-cloak
     x-ref="fileCtxMenu"
     @click.stop
     class="fixed z-[200] bg-white/95 backdrop-blur-md shadow-2xl rounded-xl p-2 border border-gray-200 min-w-[180px]"
     :style="`left:${fileContextMenu.x}px; top:${fileContextMenu.y}px;`"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    {{-- Кнопка закрыть (крестик) в правом верхнем углу --}}
    <button @click="fileContextMenu.show = false"
            class="w-full text-gray-400 hover:text-gray-600 hover:bg-gray-50 text-end rounded-lg p-2 transition-all mb-1">
        <i class="ph ph-x text-lg"></i>
    </button>

    <button @click="previewFile(fileContextMenu.file)"
            class="flex items-center w-full px-4 py-2.5 hover:bg-blue-50 rounded-lg transition-colors text-gray-700 hover:text-blue-600">
        <i class="ph ph-eye mr-3 text-lg"></i>
        <span class="font-medium">Просмотр</span>
    </button>
    <button @click="copyFileLink(); fileContextMenu.show = false"
            class="flex items-center w-full px-4 py-2.5 hover:bg-green-50 rounded-lg transition-colors text-gray-700 hover:text-green-600">
        <i class="ph ph-copy mr-3 text-lg"></i>
        <span class="font-medium">Копировать ссылку</span>
    </button>
    <button @click="openRenameForFile(fileContextMenu.file); fileContextMenu.show = false"
            class="flex items-center w-full px-4 py-2.5 hover:bg-amber-50 rounded-lg transition-colors text-gray-700 hover:text-amber-600">
        <i class="ph ph-pencil-simple mr-3 text-lg"></i>
        <span class="font-medium">Переименовать</span>
    </button>
    <div class="border-t border-gray-200 my-1"></div>
    <button @click="deleteFile(fileContextMenu.file); fileContextMenu.show = false"
            class="flex items-center w-full px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
        <i class="ph ph-trash mr-3 text-lg"></i>
        <span class="font-medium">Удалить</span>
    </button>
</div>
