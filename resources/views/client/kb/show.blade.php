@extends('client.layouts.twentyone')
@section('title', $article->title)
@section('page_heading', __('client.kb.title'))
@section('meta_description'){{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $article->article)), 300) }}@endsection
@section('og_type', 'article')

@section('content')

<div class="card mb-4">
    <div class="card-body extra-padding">
        <h1>{{ $article->title }}</h1>

        <ul class="list-inline">
            @if($article->category)
            <li class="list-inline-item text-muted pr-3">
                <i class="far fa-folder fa-fw"></i>
                <a href="{{ route('client.kb.index') }}">{{ $article->category->name }}</a>
            </li>
            @endif
            <li class="list-inline-item text-muted pr-3">
                <i class="far fa-calendar-alt fa-fw"></i>
                {{ $article->updated_at->format('jS F Y') }}
            </li>
        </ul>

        <div class="py-4 kb-body">
            {!! nl2br(e($article->article)) !!}
        </div>

        <hr>

        <div class="text-center">
            <p class="mb-2"><strong>{{ __('client.kb.was_helpful') }}</strong></p>
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-default" onclick="this.parentNode.querySelectorAll('button').forEach(function(b){b.disabled=true;});this.innerHTML='&#128077; {{ __('client.kb.thanks') }}';">
                    &#128077; {{ __('client.kb.yes') }}
                </button>
                <button type="button" class="btn btn-default" onclick="this.parentNode.querySelectorAll('button').forEach(function(b){b.disabled=true;});this.innerHTML='&#128078; {{ __('client.kb.noted') }}';">
                    &#128078; {{ __('client.kb.no') }}
                </button>
            </div>
        </div>
    </div>
</div>

<a href="{{ route('client.kb.index') }}" class="btn btn-default px-4">
    <i class="fas fa-arrow-left fa-fw"></i> {{ __('client.kb.all_articles') }}
</a>

@endsection
