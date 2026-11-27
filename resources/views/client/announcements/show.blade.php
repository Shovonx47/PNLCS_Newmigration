@extends('client.layouts.twentyone')
@section('title', $announcement->title)
@section('page_heading', __('client.announcements.title'))
@section('meta_description'){{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $announcement->announcement)), 300) }}@endsection
@section('og_type', 'article')

@section('content')

<div class="card">
    <div class="card-body extra-padding">
        <h1>{{ $announcement->title }}</h1>

        <ul class="list-inline">
            <li class="list-inline-item text-muted pr-3">
                <i class="far fa-calendar-alt fa-fw"></i>
                {{ $announcement->created_at->format('l, jS F, Y') }}
            </li>
            <li class="list-inline-item text-muted pr-3">
                <i class="far fa-clock fa-fw"></i>
                {{ $announcement->created_at->format('H:i') }}
            </li>
            @if($announcement->category)
            <li class="list-inline-item text-muted pr-3">
                <i class="far fa-folder fa-fw"></i>
                {{ $announcement->category }}
            </li>
            @endif
        </ul>

        <div class="py-5">
            {!! nl2br(e($announcement->announcement)) !!}
        </div>
    </div>
</div>

<a href="{{ route('client.announcements.index') }}" class="btn btn-default px-4">
    {{ __('client.announcements.all_announcements') }}
</a>

@endsection
