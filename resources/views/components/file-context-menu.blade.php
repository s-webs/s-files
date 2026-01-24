<div data-file-context-menu
     class="fixed z-[200] bg-white/95 backdrop-blur-md shadow-2xl rounded-xl p-2 border border-gray-200 min-w-[180px]"
     style="display: none;">
    <button class="w-full text-gray-400 hover:text-gray-600 hover:bg-gray-50 text-end rounded-lg p-2 transition-all mb-1">
        <i class="ph ph-x text-lg"></i>
    </button>

    <button data-context-preview
            class="flex items-center w-full px-4 py-2.5 hover:bg-blue-50 rounded-lg transition-colors text-gray-700 hover:text-blue-600">
        <i class="ph ph-eye mr-3 text-lg"></i>
        <span class="font-medium">{{ __('sfiles::sfiles.preview') }}</span>
    </button>
    <button data-context-open-tab
            class="flex items-center w-full px-4 py-2.5 hover:bg-indigo-50 rounded-lg transition-colors text-gray-700 hover:text-indigo-600">
        <i class="ph ph-arrow-square-out mr-3 text-lg"></i>
        <span class="font-medium">{{ __('sfiles::sfiles.open_in_new_tab') }}</span>
    </button>
    <button data-context-copy-link
            class="flex items-center w-full px-4 py-2.5 hover:bg-green-50 rounded-lg transition-colors text-gray-700 hover:text-green-600">
        <i class="ph ph-copy mr-3 text-lg"></i>
        <span class="font-medium">{{ __('sfiles::sfiles.copy_link') }}</span>
    </button>
    <button data-context-rename
            class="flex items-center w-full px-4 py-2.5 hover:bg-amber-50 rounded-lg transition-colors text-gray-700 hover:text-amber-600">
        <i class="ph ph-pencil-simple mr-3 text-lg"></i>
        <span class="font-medium">{{ __('sfiles::sfiles.rename') }}</span>
    </button>
    <div class="border-t border-gray-200 my-1"></div>
    <button data-context-delete
            class="flex items-center w-full px-4 py-2.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
        <i class="ph ph-trash mr-3 text-lg"></i>
        <span class="font-medium">{{ __('sfiles::sfiles.delete') }}</span>
    </button>
</div>
