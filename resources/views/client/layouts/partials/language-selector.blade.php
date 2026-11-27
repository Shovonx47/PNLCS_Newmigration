@if(isset($activeLanguages) && $activeLanguages->count() > 1)
@php($currentFlag = $activeLanguages->firstWhere('code', $currentLocale)?->flag_code)
<div class="dropdown d-inline-block">
    <button class="btn btn-sm btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @if($currentFlag)
            <x-language-flag :code="$currentFlag" size="16x12" />
        @endif
        {{ $currentLocaleName ?? 'English' }}
    </button>
    <div class="dropdown-menu dropdown-menu-right">
        @foreach($activeLanguages as $lang)
        <a href="{{ \App\Support\LocaleUrl::switchTo($lang->code) }}"
           class="dropdown-item px-2 py-0{{ $lang->code === $currentLocale ? ' active' : '' }}">
            <x-language-flag :code="$lang->flag_code" size="16x12" />
            {{ $lang->native_name }}
        </a>
        @endforeach
    </div>
</div>
@endif
