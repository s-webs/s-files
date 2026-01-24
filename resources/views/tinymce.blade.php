<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('sfiles::sfiles.select_file') }}</title>

    <!-- Иконки Phosphor -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/regular/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.1/src/fill/style.css">

    {{-- Tailwind + S-Files styles --}}
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ rtrim(route('sfiles.index'), '/') }}/assets/css/filemanager.standalone.css">
</head>
<body class="overflow-hidden h-screen w-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">

{{-- Alpine-компонент для TinyMCE интеграции --}}
<div
    x-data="tinymceFilePicker()"
    x-init="init()"
    class="h-full relative"
    @click="closeContextMenus()"
    @keydown.escape.window="closeAllPopups()"
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

    {{-- Индикатор загрузки --}}
    <div x-show="loading"
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
                <span class="text-lg font-semibold text-gray-700" x-text="t('loading_files')"></span>
            </div>
        </div>
    </div>

    {{-- Base URL and translations for JavaScript --}}
    <script>
        window.sfilesConfig = {
            baseUrl: '{{ route('sfiles.index') }}',
            filesUrl: '{{ route('sfiles.index') }}/files',
        };
        window.sfilesTranslations = @json($translations ?? ['en' => [], 'ru' => []]);
        window.sfilesDefaultLocale = '{{ config("sfiles.locale", "en") }}';
    </script>

    <div class="flex h-screen">
        {{-- Левое меню директорий (упрощенная версия для TinyMCE) --}}
        @include('sfiles::components.sidebar-tinymce')

        {{-- Правая область: breadcrumbs, файлы --}}
        <div class="p-6 flex-1 h-screen overflow-hidden flex flex-col relative bg-white/80 backdrop-blur-sm rounded-l-3xl shadow-xl border-l border-gray-200">
            {{-- Заголовок с кнопкой выбора --}}
            <div class="flex justify-between items-center mb-4">
                <div class="flex-1 min-w-0">@include('sfiles::components.breadcrumbs')</div>
                <div class="flex items-center gap-3">
                    <button
                        @click="selectFile()"
                        :disabled="!selectedFile"
                        :class="selectedFile ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700' : 'bg-gray-300 cursor-not-allowed'"
                        class="px-6 py-2 rounded-xl text-white font-semibold shadow-lg transition-all duration-200 flex items-center space-x-2">
                        <i class="ph ph-check"></i>
                        <span x-text="t('select')"></span>
                    </button>
                    <button
                        @click="closePicker()"
                        class="px-6 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold transition-all duration-200 flex items-center space-x-2">
                        <i class="ph ph-x"></i>
                        <span x-text="t('cancel')"></span>
                    </button>
                </div>
            </div>

            {{-- Список файлов --}}
            <div class="flex-1 overflow-auto">
                <div x-show="loading" class="flex items-center justify-center h-full">
                    <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600"></div>
                </div>

                <div x-show="!loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 p-2">
                    <template x-for="file in files" :key="file.opPath">
                        <div
                            @click="selectedFile = file"
                            :class="selectedFile && selectedFile.opPath === file.opPath ? 'ring-4 ring-blue-500 bg-blue-50' : 'hover:bg-gray-50'"
                            class="border-2 border-gray-200 rounded-xl p-3 cursor-pointer transition-all duration-200 hover:shadow-lg">
                            <template x-if="isImage(file)">
                                <div class="w-full h-32 mb-2 rounded-lg overflow-hidden bg-gradient-to-br from-gray-100 to-gray-200">
                                    <img :src="fileHref(file)"
                                         class="w-full h-full object-cover"
                                         alt="Thumbnail">
                                </div>
                            </template>
                            <template x-if="!isImage(file)">
                                <div class="w-full h-32 mb-2 rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 flex items-center justify-center">
                                    <i :class="getFileIcon(file) + ' text-4xl'"></i>
                                </div>
                            </template>
                            <div class="text-xs font-semibold text-gray-700 truncate mb-1" x-text="file.name"></div>
                            <div class="text-xs text-gray-500" x-text="formatFileSize(file.size)"></div>
                        </div>
                    </template>

                    <template x-if="files.length === 0 && !loading">
                        <div class="col-span-full text-center py-16">
                            <div class="flex flex-col items-center">
                                <i class="ph ph-file-x text-6xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg font-medium" x-text="t('no_files')"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- JS для TinyMCE интеграции --}}
@php
    $packageJs = base_path('packages/s-webs/s-files/resources/js/tinymce.js');
    $viteJs = file_exists($packageJs)
        ? 'packages/s-webs/s-files/resources/js/tinymce.js'
        : 'vendor/s-webs/s-files/resources/js/tinymce.js';
@endphp
@vite([$viteJs])
</body>
</html>
