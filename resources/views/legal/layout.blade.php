{{--
     LEGAL & PUBLIC GUIDES — shared WHMCS Twenty-One chrome.
     Renders inside client.layouts.twentyone so Terms, Privacy, About, SSL and
     Mail Setup share the exact Twenty-One header, navbar, breadcrumb and footer.
--}}
@extends('client.layouts.twentyone')
@section('title', trim($__env->yieldContent('legal-title')) ?: company_name())
@section('no_page_header', '1')
@section('full_width', '1')

@push('styles')
    <meta name="robots" content="index, follow">
    @if(!\App\Support\LocaleUrl::enabled())
    <link rel="canonical" href="{{ url()->current() }}">
    @endif
    <style>
        .legal-wrap { padding: 8px 0 40px; }
        .legal-head { padding: 10px 0 22px; border-bottom: 1px solid #dee2e6; margin-bottom: 28px; }
        .legal-head h1 { font-size: 28px; line-height: 1.25; font-weight: 400; color: #333; margin: 0 0 8px; }
        .legal-head p { margin: 0; font-size: 14.5px; color: #6c757d; max-width: 72ch; }
        .legal-crumb { font-size: 13px; color: #6c757d; margin-bottom: 12px; }
        .legal-crumb a { color: #6c757d; text-decoration: none; }
        .legal-crumb a:hover { text-decoration: underline; }
        .legal-grid { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 36px; align-items: start; }
        .legal-side { position: sticky; top: 24px; background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 14px; }
        .legal-side-title { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6c757d; margin: 0 0 10px; }
        .legal-side a { display: block; padding: 6px 10px; border-radius: 4px; font-size: 13px; line-height: 1.4; color: #333; text-decoration: none; margin-bottom: 2px; }
        .legal-side a:hover { background: #f4f6f8; color: #1a4d80; }
        .legal-side a.is-active { background: #e8f0fb; color: #1a4d80; font-weight: 600; }
        .legal-body { background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 26px 30px; font-size: 14.5px; line-height: 1.72; color: #333; }
        .legal-body h2 { font-size: 19px; font-weight: 600; margin: 32px 0 10px; padding-top: 4px; color: #1a4d80; scroll-margin-top: 80px; }
        .legal-body h2:first-child { margin-top: 0; }
        .legal-body h3 { font-size: 16px; font-weight: 600; margin: 22px 0 8px; }
        .legal-body p { margin: 0 0 14px; }
        .legal-body ul, .legal-body ol { margin: 0 0 16px; padding-left: 22px; }
        .legal-body li { margin-bottom: 6px; }
        .legal-body a { color: #1a4d80; }
        .legal-body table { width: 100%; border-collapse: collapse; margin: 8px 0 20px; font-size: 13.5px; display: block; overflow-x: auto; }
        .legal-body th, .legal-body td { border: 1px solid #dee2e6; padding: 8px 12px; text-align: left; vertical-align: top; }
        .legal-body th { background: #f8f9fa; font-weight: 600; }
        .legal-note { border: 1px solid #dee2e6; border-left: 3px solid #1a4d80; background: #f8f9fa; border-radius: 4px; padding: 12px 16px; margin: 18px 0; font-size: 14px; }
        .legal-note p:last-child { margin-bottom: 0; }
        .legal-meta { font-size: 12.5px; color: #6c757d; margin-top: 32px; padding-top: 14px; border-top: 1px solid #dee2e6; }
        .legal-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(268px, 1fr)); gap: 14px; }
        .legal-card { display: block; background: #fff; border: 1px solid #dcdcdc; border-radius: 4px; padding: 18px 20px; text-decoration: none !important; color: #333; transition: border-color .15s, box-shadow .15s; }
        .legal-card:hover { border-color: #1a4d80; box-shadow: 0 3px 10px rgba(26,77,128,.08); }
        .legal-card i { font-size: 19px; color: #1a4d80; }
        .legal-card b { display: block; font-size: 15px; font-weight: 600; margin: 8px 0 4px; color: #1a4d80; }
        .legal-card span { font-size: 13px; color: #6c757d; line-height: 1.5; }
        .legal-tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 2px 7px; border-radius: 4px; background: #e8f0fb; color: #1a4d80; margin-left: 6px; }
        @media (max-width: 900px) {
            .legal-grid { grid-template-columns: minmax(0, 1fr); gap: 20px; }
            .legal-side { position: static; }
            .legal-body { padding: 18px 20px; }
        }
        @media print {
            #header, #footer, .master-breadcrumb, .legal-side, .legal-crumb { display: none !important; }
            .legal-grid { grid-template-columns: 1fr; }
            .legal-body { border: 0; padding: 0; max-width: none; font-size: 11pt; }
        }
    </style>
@endpush

@section('content')
    <div class="legal-wrap">
        @yield('legal-content')
    </div>
@endsection
