<div class="flex justify-between items-center mt-4 mb-4">
    {{-- Кнопка "Загрузить файлы" + модальное окно --}}
    <div>
        <button @click="openUploadModal()"
                class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 px-6 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 flex items-center space-x-2">
            <i class="ph ph-cloud-arrow-up text-xl"></i>
            <span x-text="t('upload_files')"></span>
        </button>
        <div x-show="uploadModal.show"
             x-cloak
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100]"
             style="display: none;">
            <div class="bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl w-11/12 max-w-2xl border border-gray-200"
                 @click.stop>
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                        <i class="ph ph-cloud-arrow-up text-blue-600"></i>
                        <span x-text="t('upload_files_modal')"></span>
                    </h2>
                    <button @click="uploadModal.show = false"
                            class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                <form action="{{ route('sfiles.index') }}/upload"
                      class="dropzone h-[300px] overflow-auto border-2 border-dashed border-gray-300 rounded-xl p-4 hover:border-blue-400 transition-colors"
                      id="uploadZoneModal">
                    <input type="hidden" name="path" :value="currentPath">
                    @csrf
                </form>
                {{-- Прогресс загрузки --}}
                <div class="mt-6" x-show="isUploading || uploadProgress > 0" x-cloak>
                    <div class="h-4 bg-gray-200 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-300 flex items-center justify-end pr-3 shadow-lg"
                             :style="`width: ${uploadProgress}%;`">
                            <span class="text-xs text-white font-bold" x-text="uploadProgress + '%'"></span>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600 mt-3 flex items-center justify-between">
                        <span class="font-medium"><span x-text="t('upload_progress')"></span>: <span x-text="uploadProgress" class="text-blue-600 font-bold"></span>%</span>
                        <span x-show="isUploading" class="flex items-center text-blue-600">
                            <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent mr-2"></div>
                            <span class="font-medium" x-text="t('uploading')"></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Выбор всех и удаление --}}
    <div class="flex items-center space-x-3">
        <label class="flex items-center bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-400 hover:to-indigo-400 disabled:opacity-50 disabled:cursor-not-allowed px-5 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 cursor-pointer">
            <input type="checkbox"
                   @change="toggleAllFiles($event.target.checked)"
                   :checked="allFilesSelected"
                   class="form-checkbox mr-3 h-5 w-5">
            <span x-text="t('select_all')"></span>
        </label>
        <button @click="downloadSelectedFiles()"
                :disabled="!selectedFiles.length"
                class="bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-500 hover:to-gray-600 disabled:opacity-50 disabled:cursor-not-allowed px-5 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 disabled:transform-none flex items-center space-x-2">
            <i class="ph ph-download text-lg"></i>
            <span x-text="t('download') + ' (' + selectedFiles.length + ')'"></span>
        </button>
        <button @click="deleteSelectedFiles()"
                :disabled="!selectedFiles.length"
                class="bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-400 hover:to-rose-500 disabled:opacity-50 disabled:cursor-not-allowed px-5 py-3 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105 disabled:transform-none flex items-center space-x-2">
            <i class="ph ph-trash text-lg"></i>
            <span x-text="t('delete') + ' (' + selectedFiles.length + ')'"></span>
        </button>
    </div>
</div>
