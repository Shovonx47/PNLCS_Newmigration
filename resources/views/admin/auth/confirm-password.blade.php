@extends('admin.layouts.app')
@section('title', $pageTitle ?? 'Administrators')
@section('content')

<style>
    .whmcs-confirm-heading {
        margin: 0 0 16px 0 !important;
        padding: 0 !important;
        border: 0 !important;
        font-size: 21px !important;
        font-weight: 400 !important;
        color: #333333 !important;
        line-height: 1.3 !important;
    }
    .whmcs-confirm-stage {
        background: #fafafa;
        border-top: 1px solid #f1f1f1;
        min-height: calc(100vh - 210px);
        margin: 0 -20px -20px;
        padding: 72px 16px 96px;
        display: flex;
        align-items: flex-start;
        justify-content: center;
    }
    .whmcs-confirm-card {
        width: 400px;
        max-width: 100%;
        background: #ffffff;
        border: 1px solid #f0f0f0;
        border-radius: 2px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        padding: 26px 28px 28px;
    }
    .whmcs-confirm-card h2 {
        margin: 0 0 10px 0 !important;
        padding: 0 !important;
        border: 0 !important;
        font-size: 17px !important;
        font-weight: 400 !important;
        color: #333333 !important;
        line-height: 1.35 !important;
    }
    .whmcs-confirm-card p {
        margin: 0 0 14px 0;
        font-size: 13px;
        color: #555555;
        line-height: 1.45;
    }
    .whmcs-confirm-error {
        background: #f2dede;
        border: 1px solid #ebccd1;
        color: #a94442;
        border-radius: 3px;
        padding: 8px 12px;
        font-size: 12.5px;
        margin: 0 0 12px 0;
    }
    .whmcs-confirm-label {
        display: block;
        font-size: 13px;
        font-weight: 700;
        color: #333333;
        margin: 0 0 6px 0;
    }
    .whmcs-confirm-input {
        display: block;
        width: 100%;
        height: 34px;
        padding: 6px 12px;
        font-size: 13.5px;
        line-height: 1.42857143;
        color: #555555;
        background-color: #ffffff;
        border: 1px solid #cccccc;
        border-radius: 4px;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
        transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    }
    .whmcs-confirm-input:focus {
        border-color: #66afe9;
        outline: 0;
        box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075), 0 0 8px rgba(102, 175, 233, 0.6);
    }
    .whmcs-confirm-btn {
        display: block;
        width: 100%;
        height: 36px;
        margin-top: 12px;
        padding: 6px 12px;
        font-size: 13.5px;
        font-weight: 400;
        color: #ffffff;
        background-color: #337ab7;
        border: 1px solid #2e6da4;
        border-radius: 4px;
        cursor: pointer;
        text-align: center;
        transition: background-color 0.15s ease;
    }
    .whmcs-confirm-btn:hover {
        background-color: #286090;
        border-color: #204d74;
    }
</style>

<h1 class="whmcs-confirm-heading">{{ $pageTitle ?? 'Administrators' }}</h1>

<div class="whmcs-confirm-stage">
    <div class="whmcs-confirm-card">
        <h2>{{ 'Confirm password to continue' }}</h2>
        <p>{{ 'You are entering an administrative area of WHMCS and must confirm your password to continue.' }}</p>

        @error('password')
            <div class="whmcs-confirm-error" role="alert">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('admin.confirm-password') }}">
            @csrf
            <input type="hidden" name="intended" value="{{ $intendedUrl ?? url()->current() }}">
            <label for="inputConfirmAdminPassword" class="whmcs-confirm-label">{{ 'Password' }}</label>
            <input type="password" id="inputConfirmAdminPassword" name="password" class="whmcs-confirm-input" required autofocus autocomplete="current-password">
            <button type="submit" class="whmcs-confirm-btn">{{ 'Confirm Password' }}</button>
        </form>
    </div>
</div>

@endsection
