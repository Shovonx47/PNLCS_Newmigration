@extends('admin.layouts.app')
@section('title', 'Quotes #' . $quote->id)
@section('content')
    @include('admin.quotes._form')
@endsection
