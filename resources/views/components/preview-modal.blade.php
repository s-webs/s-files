<div x-show="previewModal.show"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-[100]"
     style="display: none;">
    <div class="bg-white/95 backdrop-blur-md w-[90vw] max-w-6xl p-8 rounded-2xl shadow-2xl overflow-auto relative border border-gray-200">
        <button @click="previewModal.show = false"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 hover:bg-gray-100 w-12 h-12 bg-white/80 backdrop-blur-sm text-xl rounded-full shadow-lg transition-all duration-200 flex items-center justify-center">
            <i class="ph ph-x"></i>
        </button>

        {{-- Предпросмотр изображений --}}
        <template x-if="previewModal.type === 'image'">
            <div class="mt-4">
                <img :src="previewModal.url"
                     class="max-w-full max-h-[80vh] mx-auto rounded-xl shadow-2xl"
                     alt="Preview">
            </div>
        </template>

        {{-- Предпросмотр PDF --}}
        <template x-if="previewModal.type === 'pdf'">
            <div class="mt-4 rounded-xl overflow-hidden shadow-xl">
                <embed :src="previewModal.url"
                       type="application/pdf"
                       width="100%"
                       height="600px"
                       class="rounded-xl">
            </div>
        </template>

        {{-- Предпросмотр Word-документа через Google Docs Viewer --}}
        <template x-if="previewModal.type === 'word'">
            <div class="w-full h-[80vh] mt-4">
                <div class="rounded-xl overflow-hidden shadow-xl border-2 border-gray-200">
                    <iframe :src="`https://docs.google.com/gview?url=${encodeURIComponent(previewModal.url)}&embedded=true`"
                            class="w-full h-full" frameborder="0"></iframe>
                </div>
                <div class="mt-4 text-center">
                    <a :href="previewModal.url" 
                       download 
                       class="inline-flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                        <i class="ph ph-download text-lg"></i>
                        <span>Скачать оригинал</span>
                    </a>
                </div>
            </div>
        </template>

        {{-- Если предпросмотр невозможен --}}
        <template x-if="previewModal.type === 'other'">
            <div class="text-center p-12">
                <div class="mb-6">
                    <i class="ph ph-file text-7xl text-gray-300"></i>
                </div>
                <p class="text-xl font-semibold text-gray-700 mb-2">Предпросмотр недоступен</p>
                <p class="text-gray-500 mb-6">Этот тип файла не поддерживает предпросмотр</p>
                <a :href="previewModal.url"
                   download
                   class="inline-flex items-center space-x-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <i class="ph ph-download text-lg"></i>
                    <span>Скачать файл</span>
                </a>
            </div>
        </template>
    </div>
</div>
