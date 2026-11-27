@extends('admin.layouts.app')
@section('title', 'Integration Code')
@section('content')

<h1>{{ 'Integration Code' }}</h1>

<div class="infobox">
    {{ 'Copy and paste the HTML snippets below into any external website to link directly into your client portal, domain checker, and product checkout.' }}
</div>

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 6px;">{{ 'Clients Login' }}</h2>
<textarea rows="6" readonly class="form-control" style="font-family:monospace;font-size:12px;margin-bottom:16px;" onclick="this.select()"><form method="post" action="{{ $baseUrl }}/client/login">
    Email Address: <input type="email" name="email" size="25" required>
    Password: <input type="password" name="password" size="20" required>
    <input type="submit" value="Login">
</form></textarea>

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 6px;">{{ 'Domain Availability Lookup' }}</h2>
<textarea rows="5" readonly class="form-control" style="font-family:monospace;font-size:12px;margin-bottom:16px;" onclick="this.select()"><form method="get" action="{{ $baseUrl }}/domains/search">
    Domain: <input type="text" name="domain" size="30" placeholder="example.com" required>
    <input type="submit" value="Search Domain">
</form></textarea>

<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 6px;">{{ 'User Registration Link' }}</h2>
<textarea rows="2" readonly class="form-control" style="font-family:monospace;font-size:12px;margin-bottom:16px;" onclick="this.select()"><a href="{{ $baseUrl }}/client/register">Click here to register an account</a></textarea>

@if($products->isNotEmpty())
<h2 style="font-size:15px;font-weight:600;color:#1a4d80;margin:14px 0 6px;">{{ 'Direct Product Order Links' }}</h2>
<div class="tablebg">
    <table class="datatable" width="100%" border="0" cellspacing="1" cellpadding="3">
        <thead>
            <tr>
                <th class="text-left">{{ 'Product' }}</th>
                <th class="text-left">{{ 'Direct Order URL' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $prod)
                <tr>
                    <td><strong>{{ ($prod->group?->name ? $prod->group->name . ' - ' : '') . $prod->name }}</strong></td>
                    <td>
                        <input type="text" readonly value="{{ $baseUrl . '/client/store/configure/' . $prod->id }}" class="form-control" style="font-family:monospace;font-size:12px;" onclick="this.select()">
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
