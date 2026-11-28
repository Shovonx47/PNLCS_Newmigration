@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $paginator */
    $current = $paginator->currentPage();
    $last = max(1, $paginator->lastPage());
@endphp

@if($last > 1)
<nav class="whmcs-pagination-wrap" aria-label="{{ __('Pagination Navigation') }}">
    <ul class="whmcs-pagination">
        @if($paginator->onFirstPage())
            <li class="disabled"><span>{{ __('admin.pagination.previous_page') }}</span></li>
        @else
            <li><a href="{{ $paginator->previousPageUrl() }}">{{ __('admin.pagination.previous_page') }}</a></li>
        @endif

        @php
            $pages = [];
            if ($last <= 9) {
                $pages = range(1, $last);
            } else {
                $startWindow = range(1, min(4, $last));
                $endWindow = range(max(1, $last - 2), $last);
                $midWindow = ($current > 4 && $current < $last - 2) ? range($current - 1, $current + 1) : [];
                $combined = array_values(array_unique(array_merge($startWindow, $midWindow, $endWindow)));
                sort($combined);
                $prev = null;
                foreach ($combined as $p) {
                    if ($prev !== null && $p - $prev > 1) {
                        $pages[] = '...';
                    }
                    $pages[] = $p;
                    $prev = $p;
                }
            }
        @endphp

        @foreach($pages as $page)
            @if($page === '...')
                <li class="disabled ellipsis"><span>{{ '...' }}</span></li>
            @elseif($page === $current)
                <li class="active"><span>{{ $page }}</span></li>
            @else
                <li><a href="{{ $paginator->url($page) }}">{{ $page }}</a></li>
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <li><a href="{{ $paginator->nextPageUrl() }}">{{ __('admin.pagination.next_page') }}</a></li>
        @else
            <li class="disabled"><span>{{ __('admin.pagination.next_page') }}</span></li>
        @endif
    </ul>
</nav>
@endif
