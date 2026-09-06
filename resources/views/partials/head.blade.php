{{-- Bagian <head> yang sama untuk seluruh layout: meta, favicon, font, aset Vite. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'MAXPORT') }}</title>

<meta name="description" content="{{ $description ?? 'MAXPORT membantu eksportir pemula Indonesia memeriksa kesiapan spesifikasi produk dan menyiapkan dokumen ekspor.' }}">

{{-- Favicon & ikon aplikasi (dibuat dari public/logo/maxport-mark.svg) --}}
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="icon" type="image/svg+xml" href="{{ asset('logo/maxport-mark.svg') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="manifest" href="{{ asset('site.webmanifest') }}">
<meta name="theme-color" content="#0051D5">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
