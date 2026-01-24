<div data-preview-modal
     class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center z-[100]"
     style="display: none;">
    <div class="bg-white/95 backdrop-blur-md w-[90vw] max-w-6xl p-8 rounded-2xl shadow-2xl overflow-auto relative border border-gray-200">
        <button data-modal-close
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 hover:bg-gray-100 w-12 h-12 bg-white/80 backdrop-blur-sm text-xl rounded-full shadow-lg transition-all duration-200 flex items-center justify-center">
            <i class="ph ph-x"></i>
        </button>

        <div data-modal-content class="mt-4">
            {{-- Контент будет вставлен через JavaScript --}}
        </div>
    </div>
</div>
