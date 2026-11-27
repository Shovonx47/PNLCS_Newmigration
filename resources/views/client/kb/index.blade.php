@extends('client.layouts.twentyone')
@section('title', __('client.kb.title'))

@section('page_heading', __('client.kb.page_title'))
@section('page_subheading', __('client.kb.page_subtitle'))

@section('content')

<form role="form" method="GET" action="{{ route('client.kb.index') }}" class="mb-4">
    <div class="input-group input-group-lg kb-search">
        <input type="text" id="inputKnowledgebaseSearch" name="q" value="{{ $searchQuery ?? '' }}" class="form-control font-weight-light" placeholder="{{ __('client.kb.search_placeholder') }}">
        <div class="input-group-append">
            <button type="submit" id="btnKnowledgebaseSearch" class="btn btn-primary">
                {{ __('common.actions.search') }}
            </button>
        </div>
    </div>
</form>

@if(isset($searchQuery) && $searchQuery)
<p class="text-muted">{{ __('client.kb.results_for') }} <strong>{{ $searchQuery }}</strong></p>
@endif

@if($categories->isEmpty())
    <div class="alert alert-info text-center" role="alert">
        {{ ($searchQuery ?? '') !== '' ? __('client.kb.no_results') : __('client.kb.no_articles_available') }}
    </div>
@else
    <div class="row">
        @foreach($categories as $cat)
            <div class="col-xl-6">
                <div class="card kb-category mb-4">
                    <div class="card-body">
                        <span class="h5 m-0">
                            <span class="badge badge-info float-right">{{ $cat->articles->count() }}</span>
                            <i class="fal fa-folder fa-fw"></i>
                            {{ $cat->name }}
                        </span>
                        @if($cat->description)
                            <p class="m-0 text-muted"><small>{{ $cat->description }}</small></p>
                        @endif
                        @if($cat->articles->isNotEmpty())
                            <ul class="list-unstyled mt-3 mb-0">
                                @foreach($cat->articles->sortBy('sort_order') as $article)
                                <li>
                                    <a href="{{ route('client.kb.show', $article) }}" class="d-block py-1">
                                        <i class="fal fa-file-alt fa-fw text-muted"></i>
                                        {{ $article->title }}
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mt-2 mb-0"><small>{{ __('client.kb.no_articles') }}</small></p>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@endsection
