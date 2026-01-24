# S-Files - Universal File Manager for Laravel

A modern, universal file manager for Laravel with a beautiful interface, drag & drop support, file preview, and many other features.

> **Note:** For Russian documentation, see [README.ru.md](README.ru.md)

## Features

- 🎨 Modern interface built with Alpine.js and Tailwind CSS
- 📁 File and folder management
- 📤 File upload via drag & drop
- 👁️ Preview for images, PDFs, and documents
- 🔒 Optional authentication (can be disabled)
- 🚀 Rate limiting to prevent abuse
- 🔐 Secure path and file validation
- 📦 Support for various storage disks
- 🎯 Fully customizable

## Requirements

- PHP >= 8.2
- Laravel >= 10.0 or >= 11.0 or >= 12.0
- Node.js >= 18.0 and npm (for building assets)
- Vite (for building frontend resources)

## Installation

### Via Composer

```bash
composer require s-webs/s-files
```

### Publish Configuration

```bash
php artisan vendor:publish --tag=sfiles-config
```

### Publish Views (Optional, for customization)

```bash
php artisan vendor:publish --tag=sfiles-views
```

### Publish Assets (Optional, for customization)

```bash
php artisan vendor:publish --tag=sfiles-assets
```

### Install npm Dependencies

The package requires the following npm dependencies:
- `alpinejs` (^3.15.4) - for interface reactivity
- `dropzone` (^6.0.0-beta.2) - for drag & drop file uploads
- `compressorjs` (^1.2.1) - for image compression before upload

**Option 1: Install in root project (recommended)**

If you're using the package in your project, install dependencies in the root `package.json`:

```bash
npm install alpinejs@^3.15.4 dropzone@^6.0.0-beta.2 compressorjs@^1.2.1
```

**Option 2: Install in package (for development)**

If you're developing the package locally:

```bash
cd vendor/s-webs/s-files
npm install
```

### Configure Vite

The package **CSS is loaded automatically** (standalone, no Vite). You only need to add the **JS** entry to `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // S-Files: add only the JS (CSS is standalone)
                'vendor/s-webs/s-files/resources/js/filemanager.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        // Ensure Vite can resolve relative imports in vendor packages
        preserveSymlinks: false,
    },
});
```

**If you develop the package locally** (e.g. in `packages/s-webs/s-files`), use:
`packages/s-webs/s-files/resources/js/filemanager.js`

### Build Assets

After configuring Vite, build the assets:

```bash
# For development (with hot reload)
npm run dev

# For production
npm run build
```

**Important:** Make sure the Vite dev server is running (`npm run dev`) during development, or build the assets (`npm run build`) for production.

## Configuration

### 1. Configuration File

Open the `config/sfiles.php` file and configure the parameters:

```php
// Storage disk for files
'disk' => env('SFILES_DISK', 'uploads'),

// Public directory prefix
'public_dir' => env('SFILES_PUBLIC_DIR', 'uploads'),

// Route prefix
'routes' => [
    'prefix' => env('SFILES_ROUTE_PREFIX', 's-files'),
    'middleware' => ['web'],
],

// Authentication (optional)
'auth' => [
    'enabled' => env('SFILES_AUTH_ENABLED', false), // false = no authentication
    'middleware' => env('SFILES_AUTH_MIDDLEWARE', 'auth'),
],
```

### 2. Storage Configuration

Make sure the files disk is configured in `config/filesystems.php`:

```php
'disks' => [
    'uploads' => [
        'driver' => 'local',
        'root' => storage_path('app/uploads'),
        'url' => env('APP_URL').'/uploads',
        'visibility' => 'public',
    ],
],
```

And create a symbolic link:

```bash
php artisan storage:link
```

### 3. Environment Variables (.env)

```env
# Storage disk
SFILES_DISK=uploads

# Public directory
SFILES_PUBLIC_DIR=uploads

# Route prefix
SFILES_ROUTE_PREFIX=s-files

# Authentication (true/false)
SFILES_AUTH_ENABLED=false

# Middleware for authentication (if enabled = true)
SFILES_AUTH_MIDDLEWARE=auth

# Maximum file size in KB (default 10MB)
SFILES_MAX_SIZE=10240

# Rate limiting
SFILES_RATE_LIMIT_UPLOAD=100
SFILES_RATE_LIMIT_DELETE=30
SFILES_RATE_LIMIT_GENERAL=60
```

## Usage

### Basic Usage

After installation, the file manager will be available at:

```
http://your-app.test/s-files
```

Or at your configured prefix.

### With Authentication

If you want to protect the file manager with authentication:

1. Set `SFILES_AUTH_ENABLED=true` in `.env`
2. Configure `SFILES_AUTH_MIDDLEWARE` (e.g., `auth` for standard Laravel authentication)

### Without Authentication

By default, authentication is disabled (`SFILES_AUTH_ENABLED=false`). The file manager will be accessible to everyone.

**⚠️ Warning:** Use without authentication only in secure environments or protect routes at the web server level.

### MoonShine Integration

If you're using MoonShine and want to integrate the file manager:

1. Create a custom middleware for MoonShine authentication
2. Set `SFILES_AUTH_ENABLED=true`
3. Configure `SFILES_AUTH_MIDDLEWARE` to your custom middleware

Example middleware:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use MoonShine\Laravel\MoonShineAuth;

class MoonShineFileManagerAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!MoonShineAuth::getGuard()->check()) {
            abort(401);
        }
        
        return $next($request);
    }
}
```

Then in `config/sfiles.php`:

```php
'auth' => [
    'enabled' => true,
    'middleware' => \App\Http\Middleware\MoonShineFileManagerAuth::class,
],
```

## API Endpoints

All endpoints are available through the prefix specified in the configuration:

- `GET /s-files` - File manager main page
- `GET /s-files/files?path=` - Get list of files and folders
- `POST /s-files/upload` - Upload a file
- `POST /s-files/create-folder` - Create a folder
- `POST /s-files/delete` - Delete a file
- `POST /s-files/delete-folder` - Delete a folder
- `POST /s-files/rename` - Rename a file/folder
- `GET /s-files/download-folder?path=` - Download folder as ZIP
- `POST /s-files/download-files` - Download selected files as ZIP
- `GET /s-files/tinymce` - TinyMCE file picker interface

## TinyMCE Integration

S-Files includes built-in integration with TinyMCE editor. You can configure it **directly in your config file** without using Blade templates.

### Quick Start

1. Include the integration scripts in your HTML (in correct order):

```html
<!-- 1. TinyMCE -->
<script src="https://cdn.tiny.cloud/1/YOUR_API_KEY/tinymce/6/tinymce.min.js"></script>

<!-- 2. S-Files integration -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-integration.js"></script>

<!-- 3. Auto-integration (processes config) -->
<script src="{{ route('sfiles.index') }}/assets/js/tinymce-auto-integration.js"></script>
```

2. Configure TinyMCE in `config/tinymce.php`:

```php
return [
    // ... other config ...
    
    'sfiles' => [
        'base_url' => env('SFILES_ROUTE_PREFIX', '/s-files'),
        'width' => 900,
        'height' => 600,
    ],
    
    'callbacks' => [
        'file_picker_callback' => 'sfiles', // Auto-integration
    ],
];
```

3. Initialize TinyMCE:

```javascript
const config = @json(config('tinymce'));
tinymce.init(config);
```

The `tinymce-auto-integration.js` script will automatically process the configuration and connect the integration.

**For detailed documentation, see:**
- [TINYMCE.md](TINYMCE.md) - Full integration guide
- [TINYMCE-CONFIG.md](TINYMCE-CONFIG.md) - Configuration-only integration guide
- [examples/tinymce-config.php](examples/tinymce-config.php) - Example config file

## Security

The package includes multiple security measures:

- ✅ Protection against path traversal attacks
- ✅ MIME type and file extension validation
- ✅ File signature verification (for Office documents)
- ✅ Rate limiting to prevent abuse
- ✅ File name sanitization
- ✅ Path depth limitation
- ✅ Blocking dangerous file extensions

## Customization

### Views

After publishing views (`php artisan vendor:publish --tag=sfiles-views`), you can customize them in `resources/views/vendor/sfiles/`.

### Assets

After publishing assets (`php artisan vendor:publish --tag=sfiles-assets`), you can customize CSS and JavaScript in `resources/css/vendor/sfiles/` and `resources/js/vendor/sfiles/`.

**Important:** After publishing assets, make sure:
1. npm dependencies are installed (`npm install alpinejs@^3.15.4 dropzone@^6.0.0-beta.2 compressorjs@^1.2.1`)
2. Files are added to `vite.config.js`:
   ```js
   input: [
       // ... other files
       'resources/css/vendor/sfiles/filemanager.css',
       'resources/js/vendor/sfiles/filemanager.js',
   ]
   ```
3. Build is executed (`npm run build`) or dev server is running (`npm run dev`)

### Configuration

All parameters can be configured in `config/sfiles.php`:

- Allowed MIME types
- Allowed extensions
- Blocked extensions
- Maximum file size
- Rate limiting settings
- Cache settings
- Logging settings

## Troubleshooting

### Security Advisories Error During Installation

If you encounter an error like this during installation:

```
Your requirements could not be resolved to an installable set of packages.
... these were not loaded, because they are affected by security advisories.
```

This error occurs when your project has dependencies with known security vulnerabilities. The `s-webs/s-files` package itself doesn't have security issues, but Composer blocks installation if any dependency in your project has security advisories.

**Solution 1: Update Dependencies (Recommended)**

Update your project dependencies to versions without security advisories:

```bash
# Update all dependencies
composer update

# Or update specific packages
composer update moonshine/moonshine symfony/http-foundation
```

**Solution 2: Temporarily Ignore Security Advisories (Not Recommended for Production)**

If you cannot update dependencies immediately, you can temporarily ignore specific advisories by adding them to your `composer.json`:

```json
{
    "config": {
        "audit": {
            "ignore": [
                "PKSA-hjzy-c19f-31tw",
                "PKSA-n4nk-wgpq-4ghg",
                "PKSA-f5fc-g28z-r13n",
                "PKSA-7389-zs25-skf1",
                "PKSA-365x-2zjk-pt47"
            ]
        }
    }
}
```

**⚠️ Warning:** Only use Solution 2 temporarily and update your dependencies as soon as possible. Ignoring security advisories leaves your application vulnerable.

**Solution 3: Disable Security Audit (Not Recommended)**

You can disable security audit entirely by adding to `composer.json`:

```json
{
    "config": {
        "audit": {
            "block-insecure": false
        }
    }
}
```

**⚠️ Warning:** This completely disables security checks. Use only for development and update dependencies immediately.

## License

MIT License

## Support

If you have questions or issues, please create an issue in the package repository.

## Author

S-WEBS
