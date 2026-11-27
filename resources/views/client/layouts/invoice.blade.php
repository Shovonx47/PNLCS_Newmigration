@php
    // The invoice document, exactly as WHMCS Twenty-One prints it: a plain
    // page with the invoice-container grid and the invoice stylesheet, no
    // site header, no navbar, no breadcrumb. viewinvoice.tpl is a complete
    // document of its own in the theme, and so is this.
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ company_name() }} - @yield('title')</title>
    <link href="{{ asset('assets/fonts/css/open-sans-family.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/twenty-one/css/all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/twenty-one/css/theme.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/fontawesome-all.min.css') }}" rel="stylesheet">
    <link href="{{ asset('templates/twenty-one/css/invoice.min.css') }}" rel="stylesheet">
    @if(!empty($customFavicon))
    <link rel="icon" href="{{ $customFavicon }}" type="image/png">
    @endif
    @stack('styles')
</head>
<body>

<div class="container-fluid invoice-container">
    @if(session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info" role="alert">{{ session('info') }}</div>
    @endif
    @if(session('warning'))
        <div class="alert alert-warning" role="alert">{{ session('warning') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach($errors->all() as $validationError)
                <div>{{ $validationError }}</div>
            @endforeach
        </div>
    @endif

    @yield('content')
</div>

@yield('scripts')
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
