@extends('layouts.frontend_master')

@section('content')

<div class="breadcrumb-area">
    <div class="container">
        <div class="row align-items-center justify-content-center">
            <div class="col-12 text-center">
                <h2 class="breadcrumb-title">{{ $search !== '' ? 'Search Results' : 'Shop' }}</h2>
                <ul class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Home</a></li>
                    <li class="breadcrumb-item active">Shop</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="shop-page-wrapper pt-60px pb-100px">
    <div class="container">

        <form method="GET" action="{{ route('shop') }}" class="row g-2 mb-4">
            <div class="col-md-6">
                <input type="text" name="q" class="form-control"
                       placeholder="Search by name, SKU or description"
                       value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-control">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) $categoryId === (string) $category->id)>
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
            @if ($search !== '' || $categoryId)
                <div class="col-md-1">
                    <a href="{{ route('shop') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
            @endif
        </form>

        <p class="text-muted">
            {{ $products->total() }} {{ Str::plural('product', $products->total()) }} found
            @if ($search !== '') for "{{ $search }}" @endif
        </p>

        <div class="row">
            @forelse ($products as $product)
                <div class="col-lg-4 col-xl-3 col-md-6 col-sm-6 col-xs-6 mb-30px">
                    @include('parts.product')
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No products matched your search.
                        <a href="{{ route('shop') }}">Browse everything</a>.
                    </div>
                </div>
            @endforelse
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $products->links() }}
        </div>
    </div>
</div>

@endsection
