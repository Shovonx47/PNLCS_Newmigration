@extends('client.layouts.twentyone')
@section('title', __('client.downloads.title'))
@section('page_heading', __('client.downloads.page_title'))
@section('page_subheading', __('client.downloads.page_subtitle'))

@section('content')

@if($categories->isEmpty())
    <div class="alert alert-info text-center" role="alert">{{ __('client.downloads.no_available') }}</div>
@else
    @foreach($categories as $category)
        @if($category->downloads->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h3 class="card-title">
                    <i class="fal fa-folder-open fa-fw"></i>
                    {{ $category->name }}
                </h3>
                <div class="table-responsive">
                    <table class="table table-list">
                        <thead>
                            <tr>
                                <th>{{ __('client.downloads.file_name') }}</th>
                                <th>{{ __('common.table.description') }}</th>
                                <th>{{ __('client.downloads.download_count') }}</th>
                                <th>{{ __('client.downloads.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($category->downloads as $download)
                            <tr>
                                <td><strong>{{ $download->title }}</strong></td>
                                <td class="small text-muted">{{ $download->description ?? '-' }}</td>
                                <td class="small text-muted">{{ number_format($download->download_count ?? 0) }}</td>
                                <td>
                                    @if($download->location)
                                    <a href="{{ route('client.downloads.download', $download) }}" class="btn btn-default btn-xs">
                                        <i class="fas fa-download fa-fw"></i> {{ __('common.actions.download') }}
                                    </a>
                                    @else
                                    <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    @endforeach
@endif

@endsection
