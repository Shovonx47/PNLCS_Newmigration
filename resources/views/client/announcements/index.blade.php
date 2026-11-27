@extends('client.layouts.twentyone')
@section('title', __('client.announcements.title'))

@section('page_heading', __('client.announcements.page_title'))
@section('page_subheading', __('client.announcements.page_subtitle'))

@section('content')

@if(count($categories ?? []) > 1)
<div class="btn-group btn-group-sm mb-3" role="group" aria-label="{{ __('admin.announcements.category') }}">
    <a href="{{ route('client.announcements.index') }}" class="btn {{ $category ? 'btn-default' : 'btn-primary' }}">{{ __('client.announcements.all_categories') }}</a>
    @foreach($categories as $c)
        <a href="{{ route('client.announcements.index', ['category' => $c]) }}" class="btn {{ $category === $c ? 'btn-primary' : 'btn-default' }}">{{ $c }}</a>
    @endforeach
</div>
@endif

<div class="card">
    <div class="card-body">
        <h3 class="card-title">{{ __('client.announcements.title') }}</h3>

        <div class="announcements">
            @forelse($announcements as $announcement)
                <div class="announcement">
                    <h1>
                        <a href="{{ route('client.announcements.show', $announcement) }}">{{ $announcement->title }}</a>
                    </h1>

                    <ul class="list-inline">
                        <li class="list-inline-item text-muted pr-3">
                            <i class="far fa-calendar-alt fa-fw"></i>
                            {{ $announcement->created_at->format('jS F Y') }}
                        </li>
                        @if($announcement->category)
                        <li class="list-inline-item text-muted pr-3">
                            <i class="far fa-folder fa-fw"></i>
                            {{ $announcement->category }}
                        </li>
                        @endif
                    </ul>

                    <article>
                        @if(strlen(strip_tags((string) $announcement->announcement)) < 350)
                            {!! nl2br(e($announcement->announcement)) !!}
                        @else
                            {{ \Illuminate\Support\Str::limit(strip_tags((string) $announcement->announcement), 350) }}
                        @endif
                    </article>

                    <a href="{{ route('client.announcements.show', $announcement) }}" class="btn btn-default btn-sm">
                        {{ __('client.announcements.read_more') }}
                        <i class="far fa-arrow-right"></i>
                    </a>
                </div>
            @empty
                <div class="alert alert-info text-center" role="alert">{{ __('client.announcements.no_announcements') }}</div>
            @endforelse
        </div>
    </div>
</div>

@if($announcements instanceof \Illuminate\Pagination\LengthAwarePaginator && $announcements->hasPages())
    <div class="mt-3">{{ $announcements->links() }}</div>
@endif

<p class="text-right">
    <a href="{{ route('client.announcements.rss') }}" class="btn btn-default btn-sm"><i class="fas fa-rss fa-fw"></i> {{ __('client.announcements.rss_feed') }}</a>
</p>

@endsection
