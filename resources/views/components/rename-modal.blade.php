<div x-show="renameModal.show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[120]"
     @click.self="closeRenameModal()"
     style="display: none;">
    <div class="bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl w-[90vw] max-w-[520px] border border-gray-200">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2"
                    x-text="renameModal.type === 'file' ? 'Переименовать файл' : 'Переименовать папку'">
                </h2>
                <p class="text-sm text-gray-500 mt-2">
                    Текущее имя: <span class="font-semibold text-gray-700" x-text="renameModal.displayName"></span>
                </p>
            </div>

            <button class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all"
                    @click="closeRenameModal()">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Новое имя</label>
            <input type="text"
                   class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                   x-model="renameModal.newName"
                   @keydown.enter.prevent="submitRename()"
                   autofocus>
            <p class="text-xs text-gray-500 mt-3">
                <i class="ph ph-info mr-1"></i>
                Для файла: если не указать расширение — сохранится старое.
            </p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button class="px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition-all duration-200"
                    @click="closeRenameModal()">
                Отмена
            </button>
            <button class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105"
                    @click="submitRename()">
                Переименовать
            </button>
        </div>
    </div>
</div>
