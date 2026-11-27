@extends('admin.layouts.app')
@section('title', 'Domain WHOIS Lookup')
@section('content')

<h1>{{ 'Domain WHOIS Lookup' }}</h1>

@if(isset($errors) && $errors->any())
<div class="errorbox" style="max-width:740px;margin:0 auto 14px;">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('admin.whois.lookup') }}" style="max-width:740px;margin:18px auto 26px;">
    @csrf
    <div style="display:flex;align-items:stretch;width:100%;">
        <input type="text"
               name="domain"
               value="{{ $domain ?? '' }}"
               placeholder="domaintolookup.com"
               required
               autofocus
               style="flex:1;min-width:0;height:46px;padding:10px 16px;font-size:17px;color:#555;background:#fff;border:1px solid #ccc;border-right:0;border-radius:4px 0 0 4px;box-shadow:inset 0 1px 1px rgba(0,0,0,0.075);outline:none;">
        <button type="submit"
                style="height:46px;padding:0 20px;font-size:17px;font-weight:400;color:#fff;background-color:#337ab7;border:1px solid #2e6da4;border-radius:0 4px 4px 0;cursor:pointer;white-space:nowrap;">
            {{ 'Lookup Domain' }}
        </button>
    </div>
</form>

@if($result)
    <div style="max-width:860px;margin:0 auto;">
        @if(!empty($result['error']))
            <div class="errorbox" style="text-align:center;">
                {{ $result['raw'] }}
            </div>
        @elseif(!empty($result['available']))
            <div class="successbox" style="text-align:center;font-size:14px;">
                {{ 'The domain' }} <strong>{{ $result['domain'] }}</strong> {{ 'is available for registration.' }}
            </div>
        @else
            <div class="infobox" style="text-align:center;font-size:14px;">
                {{ 'The domain' }} <strong>{{ $result['domain'] }}</strong> {{ 'is registered.' }}
                @if(!empty($result['server']))
                    <span style="color:#666;font-size:12px;margin-left:6px;">({{ $result['server'] }})</span>
                @endif
            </div>
        @endif

        @if(!empty($result['parsed']))
            <table class="form" width="100%" border="0" cellspacing="2" cellpadding="3" style="margin-bottom:14px;">
                @foreach($result['parsed'] as $label => $value)
                    <tr>
                        <td width="22%" class="fieldlabel">{{ $label }}</td>
                        <td class="fieldarea" style="font-family:monospace;font-size:12.5px;">
                            @if(is_array($value))
                                {{ implode(', ', $value) }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif

        <div class="panel" style="margin-top:12px;">
            <div class="panel-heading" style="display:flex;align-items:center;justify-content:space-between;">
                <span>{{ 'WHOIS Output' }}</span>
                @if(!empty($result['server']))
                    <span style="font-size:12px;color:#666;font-weight:400;font-family:monospace;">{{ $result['server'] }}</span>
                @endif
            </div>
            <div class="panel-body" style="padding:0;">
                <pre style="margin:0;padding:16px;font-family:'Courier New',monospace;font-size:12px;line-height:1.55;color:#222;background:#fafafa;white-space:pre-wrap;word-break:break-word;max-height:540px;overflow-y:auto;">{{ $result['raw'] }}</pre>
            </div>
        </div>
    </div>
@endif

@endsection
