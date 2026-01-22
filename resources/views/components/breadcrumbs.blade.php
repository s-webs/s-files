<div class="px-5 py-3 bg-gradient-to-r from-slate-50 to-blue-50 rounded-xl shadow-md border border-gray-200 mb-4">
    <template x-for="(part, index) in breadcrumbs" :key="index">
        <div class="inline-flex items-center">
            <a href="#"
               @click.prevent="goToBreadcrumb(index)"
               :class="index === breadcrumbs.length - 1
                   ? 'text-gray-800 font-semibold cursor-default'
                   : 'text-gray-600 hover:text-blue-600 font-medium'"
               class="transition-all duration-200 hover:scale-105 inline-flex items-center space-x-1 px-2 py-1 rounded-lg hover:bg-white/50">
                <template x-if="part === 'root'">
                    <i class="ph ph-house text-xl text-blue-600"></i>
                </template>
                <template x-if="part !== 'root'">
                    <span x-text="part"></span>
                </template>
            </a>
            <i x-show="index < breadcrumbs.length - 1"
               class="ph ph-caret-right mx-2 text-gray-400 text-sm"></i>
        </div>
    </template>
</div>
