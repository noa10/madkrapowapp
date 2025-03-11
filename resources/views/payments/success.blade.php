@extends('layouts.app')

@section('title', 'Payment Successful')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Payment Successful</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                        </div>
                        <h3>Thank You for Your Order!</h3>
                        <p class="lead">Your payment has been processed successfully.</p>
                    </div>

                    <div class="mb-4">
                        <h5>Order Details</h5>
                        <table class="table">
                            <tr>
                                <th>Order Number:</th>
                                <td>{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <th>Date:</th>
                                <td>{{ $order->order_date->format('d M Y, h:i A') }}</td>
                            </tr>
                            <tr>
                                <th>Total Amount:</th>
                                <td>RM {{ number_format($order->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <th>Payment Method:</th>
                                <td>
                                @if($order->payment && $order->payment->payment_method == 'billplz')
                                Billplz
                                @else
                                {{ ucfirst($order->payment->payment_method ?? 'Unknown') }}
                                @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-4">
                        <h5>What's Next?</h5>
                        <ul>
                            <li>We're preparing your order for shipping.</li>
                            <li>You'll receive a confirmation email shortly.</li>
                            <li>You can track your order status from your account dashboard.</li>
                        </ul>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('orders.show', $order->order_id) }}" class="btn btn-primary">View Order Details</a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
