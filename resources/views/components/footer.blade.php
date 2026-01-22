<div class="fixed bottom-0 left-[33.333333%] xl:left-[20%] right-0 bg-gradient-to-r from-slate-800 to-slate-700 border-t border-slate-600 shadow-2xl z-40">
    <div class="flex items-center justify-between px-6 py-4">
        <div>
            <button @click="passFiles()"
                    class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-500 hover:to-emerald-500 px-6 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 flex items-center space-x-2">
                <i class="ph ph-paper-plane-tilt text-lg"></i>
                <span>Передать выбранные файлы</span>
            </button>
        </div>
        <div class="flex items-center space-x-4 text-sm">
            <div class="bg-slate-600/50 backdrop-blur-sm px-4 py-2 rounded-lg border border-slate-500">
                <span class="text-slate-300">Выбрано:</span>
                <span class="text-white font-bold ml-2" x-text="selectedFilesCount"></span>
            </div>
            <div class="bg-slate-600/50 backdrop-blur-sm px-4 py-2 rounded-lg border border-slate-500">
                <span class="text-slate-300">Размер:</span>
                <span class="text-white font-bold ml-2" x-text="formatFileSize(selectedFilesSize)"></span>
            </div>
            <div class="bg-slate-600/50 backdrop-blur-sm px-4 py-2 rounded-lg border border-slate-500">
                <span class="text-slate-300">Всего:</span>
                <span class="text-white font-bold ml-2" x-text="totalFilesCount"></span>
            </div>
            <div class="bg-slate-600/50 backdrop-blur-sm px-4 py-2 rounded-lg border border-slate-500">
                <span class="text-slate-300">Общий размер:</span>
                <span class="text-white font-bold ml-2" x-text="formatFileSize(totalFilesSize)"></span>
            </div>
        </div>
    </div>
</div>
