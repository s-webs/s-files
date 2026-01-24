<div data-rename-modal
     data-modal-backdrop
     class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[120]"
     style="display: none;">
    <div class="bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl w-[90vw] max-w-[520px] border border-gray-200">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2" data-rename-title>
                    {{-- Заполняется через JavaScript --}}
                </h2>
                <p class="text-sm text-gray-500 mt-2">
                    <span>{{ __('sfiles::sfiles.current_name') }}</span>: <span class="font-semibold text-gray-700" data-rename-current></span>
                </p>
            </div>

            <button data-modal-close
                    class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full p-2 transition-all">
                <i class="ph ph-x text-xl"></i>
            </button>
        </div>

        <div class="mt-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('sfiles::sfiles.new_name') }}</label>
            <input type="text"
                   data-rename-input
                   class="w-full border-2 border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white shadow-sm"
                   autofocus>
            <p class="text-xs text-gray-500 mt-3" data-rename-hint style="display: none;">
                <i class="ph ph-info mr-1"></i>
                {{ __('sfiles::sfiles.rename_ext_hint') }}
            </p>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button data-rename-cancel
                    class="px-6 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition-all duration-200">
                {{ __('sfiles::sfiles.cancel') }}
            </button>
            <button data-rename-submit
                    class="px-6 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold shadow-lg hover:shadow-xl transition-all duration-200 transform hover:scale-105">
                {{ __('sfiles::sfiles.rename_btn') }}
            </button>
        </div>
    </div>
</div>
