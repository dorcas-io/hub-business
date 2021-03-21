@extends('layouts.tabler-errors')
@section('body')
<div class="page-content">
    <div class="container text-center">
        <div class="display-1 text-muted mb-5"><i class="si si-exclamation"></i> 403</div>
        <h1 class="h2 mb-3 text-uppercase">You are not authorized to access this content :(</h1> <!-- $alarm['title'] || '' -->
        <p class="h4 text-muted font-weight-normal mb-7">{{ $exception->getMessage() }}</p> <!-- $alarm['text'] || '' -->
        <!-- <a class="btn btn-primary" href="{{ route('dashboard') }}">
            <i class="fe fe-home mr-2"></i>Try Starting Again
        </a> -->
    </div>
</div>
@endsection
