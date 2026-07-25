@extends('layouts.cashier')

@section('title', 'Kasir')

@section('content')
    <div
        class="cashier-root"
        data-cashier-root
        data-products-url="{{ route('cashier.products.index') }}"
        data-checkout-url="{{ route('cashier.checkout.store') }}"
        data-cashier-url="{{ route('cashier.index') }}"
        data-branch-id="{{ $branch?->id }}"
        data-branch-name="{{ $branch?->name }}"
        data-can-switch-branch="{{ $canSwitchBranch ? '1' : '0' }}"
        data-user-key="{{ auth()->id() }}"
        data-maximum-discount="{{ $maximumDiscount ?? '0.00' }}"
        data-discount-restricted="{{ $discountRestricted ? '1' : '0' }}"
        data-placeholder-url="{{ asset('assets/images/placeholders/product.svg') }}"
    >
        @include('pages.cashier.sections.simulation-alert')
        @include('pages.cashier.sections.branch-selector')
        @include('pages.cashier.sections.mobile-tabs')

        <div class="cashier-workspace">
            @include('pages.cashier.sections.product-panel')
            @include('pages.cashier.sections.cart-panel')
        </div>

        @include('pages.cashier.sections.mobile-cart-bar')
        @include('pages.cashier.sections.payment-preview-modal')
        @include('pages.cashier.sections.branch-change-modal')
        @include('pages.cashier.sections.clear-cart-modal')
        @include('pages.cashier.sections.product-card-template')
        @include('pages.cashier.sections.cart-item-template')
    </div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/js/pages/cashier/index.js') }}"></script>
@endpush
