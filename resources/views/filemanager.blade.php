<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Файловый менеджер S-Files</title>

    <!-- Иконки Phosphor -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

    <!-- Ваши стили -->
    @if(config('sfiles.assets.use_cdn', false))
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3/dist/tailwind.min.css">
    @else
        @php
            $publishedCss = resource_path('css/vendor/sfiles/filemanager.css');
            $packageCss = base_path('packages/s-webs/s-files/resources/css/filemanager.css');
            $usePublished = file_exists($publishedCss);
            $usePackage = file_exists($packageCss);
        @endphp

        @if($usePublished)
            @vite(['resources/css/vendor/sfiles/filemanager.css'])
        @elseif($usePackage)
            @vite(['packages/s-webs/s-files/resources/css/filemanager.css'])
        @else
            <link rel="stylesheet" href="{{ route('sfiles.index') }}/assets/css/filemanager.css">
        @endif
    @endif
</head>
<body class="overflow-hidden h-screen w-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

{{-- Alpine-компонент инициализируется здесь --}}
<div
    x-data="fileManager()"
    x-init="init()"
    class="h-full relative"
    @click="closeContextMenus()"
    @keydown.escape.window="closeAllPopups()"
    @dragover.prevent.stop="handleDragOver($event)"
    @dragenter.prevent.stop="handleDragOver($event)"
    @dragleave.prevent.stop="handleDragLeave($event)"
    @drop.prevent.stop="handleDrop($event)"
    x-ref="fmRoot"
>
    {{-- Уведомления --}}
    <div x-show="notification.show"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed top-4 right-4 z-[200] max-w-md"
         style="display: none;">
        <div class="p-4 rounded-xl shadow-2xl backdrop-blur-sm border"
             :class="{
                 'bg-gradient-to-r from-green-500 to-emerald-600 text-white border-green-400': notification.type === 'success',
                 'bg-gradient-to-r from-red-500 to-rose-600 text-white border-red-400': notification.type === 'error',
                 'bg-gradient-to-r from-yellow-500 to-amber-600 text-white border-yellow-400': notification.type === 'warning',
                 'bg-gradient-to-r from-blue-500 to-indigo-600 text-white border-blue-400': notification.type === 'info'
             }">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <i :class="{
                        'ph ph-check-circle': notification.type === 'success',
                        'ph ph-x-circle': notification.type === 'error',
                        'ph ph-warning': notification.type === 'warning',
                        'ph ph-info': notification.type === 'info'
                    }" class="text-xl"></i>
                    <p x-text="notification.message" class="font-medium"></p>
                </div>
                <button @click="notification.show = false"
                        class="ml-4 text-white/80 hover:text-white hover:bg-white/20 rounded-full p-1 transition-all">
                    <i class="ph ph-x text-lg"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Индикатор загрузки операций --}}
    <div x-show="operationLoading"
         x-cloak
         class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[150]"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         style="display: none;">
        <div class="bg-white/95 backdrop-blur-md p-8 rounded-2xl shadow-2xl border border-gray-200">
            <div class="flex flex-col items-center space-y-4">
                <div class="relative">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
                    <div class="absolute inset-0 animate-ping rounded-full h-12 w-12 border-2 border-blue-400 opacity-20"></div>
                </div>
                <span class="text-lg font-semibold text-gray-700">Выполнение операции...</span>
            </div>
        </div>
    </div>

    {{-- Drag & Drop зона --}}
    <div x-show="dragOver"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         class="fixed inset-0 bg-gradient-to-br from-blue-500/30 via-indigo-500/30 to-purple-500/30 backdrop-blur-md border-4 border-dashed border-blue-400 z-[140] flex items-center justify-center"
         style="display: none;">
        <div class="text-center transform animate-pulse">
            <div class="mb-6">
                <i class="ph ph-cloud-arrow-up text-8xl text-blue-600 drop-shadow-lg"></i>
            </div>
            <p class="text-3xl font-bold text-blue-800 drop-shadow-md">Перетащите файлы сюда</p>
            <p class="text-lg text-blue-600 mt-2">Отпустите для начала загрузки</p>
        </div>
    </div>

    {{-- Скрытая dropzone область для drag & drop (всегда доступна) --}}
    <form action="{{ route('sfiles.index') }}/upload"
          class="hidden"
          id="uploadZoneHidden">
        @csrf
    </form>

    {{-- Передаем базовый URL в JavaScript --}}
    <script>
        window.sfilesConfig = {
            baseUrl: '{{ route('sfiles.index') }}',
            uploadUrl: '{{ route('sfiles.index') }}/upload',
            filesUrl: '{{ route('sfiles.index') }}/files',
            createFolderUrl: '{{ route('sfiles.index') }}/create-folder',
            deleteUrl: '{{ route('sfiles.index') }}/delete',
            deleteFolderUrl: '{{ route('sfiles.index') }}/delete-folder',
            renameUrl: '{{ route('sfiles.index') }}/rename',
            downloadFolderUrl: '{{ route('sfiles.index') }}/download-folder',
            downloadFilesUrl: '{{ route('sfiles.index') }}/download-files',
        };
    </script>

    <div class="flex h-screen">

        {{-- Левое меню директорий --}}
        @include('sfiles::components.sidebar')

        {{-- Правая область: хлебные крошки, тулбар, file-list, и т. д. --}}
        <div class="p-6 flex-1 h-screen overflow-hidden flex flex-col relative bg-white/80 backdrop-blur-sm rounded-l-3xl shadow-xl border-l border-gray-200 pb-24">
            @include('sfiles::components.breadcrumbs')
            @include('sfiles::components.toolbar')

            {{--  ЭТОТ include ДОЛЖЕН БЫТЬ ВНУТРИ альпайновского контейнера --}}
            @include('sfiles::components.file-list')
            @include('sfiles::components.preview-modal')
            @include('sfiles::components.rename-modal')
        </div>

        {{-- Footer вынесен за пределы контейнера для фиксированного позиционирования --}}
        @include('sfiles::components.footer')
        @include('sfiles::components.file-context-menu')
    </div>
</div>

{{-- Скрипт Alpine + ваш скрипт с fileManager() --}}
@if(config('sfiles.assets.use_cdn', false))
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/dropzone@6.x.x/dist/dropzone.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.x.x/dist/compressor.min.js"></script>
@else
    @php
        $publishedJs = resource_path('js/vendor/sfiles/filemanager.js');
        $packageJs = base_path('packages/s-webs/s-files/resources/js/filemanager.js');
        $usePublished = file_exists($publishedJs);
        $usePackage = file_exists($packageJs);
    @endphp

    @if($usePublished)
        @vite(['resources/js/vendor/sfiles/filemanager.js'])
    @elseif($usePackage)
        @vite(['packages/s-webs/s-files/resources/js/filemanager.js'])
    @else
        <script type="module" src="{{ route('sfiles.index') }}/assets/js/filemanager.js"></script>
    @endif
@endif
</body>

</html>
