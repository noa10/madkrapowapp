@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0">Terms of Service</h1>
                </div>
                <div class="card-body">
                    <h2 class="h4 mt-4">1. Acceptance of Terms</h2>
                    <p>By accessing and using the Mad Krapow website (https://madkrapowapp.com), you agree to be bound by these Terms of Service. If you do not agree with any part of these terms, you must not use this website.</p>

                    <h2 class="h4 mt-4">2. Products and Services</h2>
                    <p>Mad Krapow offers a selection of Thai food, ready-to-cook paste. Product descriptions and images are provided for informational purposes only and may not be entirely accurate. We reserve the right to modify or discontinue any product or service at any time without notice.</p>

                    <h2 class="h4 mt-4">3. Ordering and Payment</h2>
                    <ul>
                        <li><strong>Order Placement:</strong> Orders can be placed through the website. You are responsible for ensuring the accuracy of your order details, including delivery address and contact information.</li>
                        <li><strong>Payment:</strong> We accept payments through Stripe (for credit/debit cards) and OCBC (for DuitNow and direct transfer]).</li>
                        <li><strong>Payment Processing:</strong> Payment processing is handled by Stripe and OCBC. Your payment information is securely processed by these third-party providers. We do not store your full payment details.</li>
                        <li><strong>Order Confirmation:</strong> An order confirmation will be sent to your provided email address upon successful payment.</li>
                        <li><strong>Pricing:</strong> Prices are listed on the website and are subject to change without notice. Prices are inclusive of applicable taxes unless otherwise stated.</li>
                        <li><strong>Refund and Cancellation Policies:</strong>
                            <ul>
                                <li>Refund and cancellation policies are contingent upon the specific food packaging and other relevant factors. Once an order has been prepared, cancellation may not be feasible.</li>
                                <li>Please contact us immediately if you have any issues with your order.</li>
                                <li>Refunds, if applicable, will be processed through the original payment method.</li>
                            </ul>
                        </li>
                    </ul>

                    <h2 class="h4 mt-4">4. Delivery</h2>
                    <ul>
                        <li><strong>Delivery Areas:</strong> We provide delivery services to Malaysia through the reputable logistics company J&T.</li>
                        <li><strong>Delivery Times:</strong> Estimated delivery times are provided during the ordering process. We are not responsible for delays caused by factors beyond our control, such as traffic or weather conditions.</li>
                        <li><strong>Delivery Fees:</strong> Delivery fees, if applicable, will be displayed during checkout.</li>
                        <li><strong>Delivery Acceptance:</strong> You are responsible for ensuring someone is available to receive the delivery at the specified address.</li>
                    </ul>

                    <h2 class="h4 mt-4">5. User Accounts</h2>
                    <ul>
                        <li>You may create an account to enhance your ordering experience.</li>
                        <li>You are responsible for maintaining the confidentiality of your account credentials.</li>
                        <li>You agree to provide accurate and complete information when creating your account.</li>
                    </ul>

                    <h2 class="h4 mt-4">6. Intellectual Property</h2>
                    <p>All content on this website, including text, images, and logos, is the property of Mad Krapow or its licensors and is protected by copyright and other intellectual property laws.</p>

                    <h2 class="h4 mt-4">7. Limitation of Liability</h2>
                    <p>Mad Krapow shall not be liable for any direct, indirect, incidental, or consequential damages arising from your use of this website or our products and services.</p>

                    <h2 class="h4 mt-4">8. Third-Party Links</h2>
                    <p>This website may contain links to third-party websites. We are not responsible for the content or privacy practices of these websites.</p>

                    <h2 class="h4 mt-4">9. Governing Law</h2>
                    <p>These Terms of Service shall be governed by and construed in accordance with the laws of Malaysia.</p>

                    <h2 class="h4 mt-4">10. Changes to Terms of Service</h2>
                    <p>We reserve the right to modify these Terms of Service at any time. Any changes will be posted on this page.</p>

                    <h2 class="h4 mt-4">11. Contact Information</h2>
                    <p>If you have any questions about these Terms of Service, please contact us at: k.anwarbakar@madkrapow.com</p>

                    <h2 class="h4 mt-4">12. Stripe and OCBC Specific Terms</h2>
                    <ul>
                        <li>By using Stripe for payment processing, you agree to Stripe's terms of service.</li>
                        <li>By using OCBC for payment processing, you agree to OCBC's terms of service.</li>
                        <li>We are not responsible for any issues or errors related to Stripe or OCBC's payment processing.</li>
                    </ul>

                    <div class="alert alert-secondary mt-4">
                        <strong>Disclaimer:</strong> This Terms of Service document is provided for informational purposes only and does not constitute legal advice. You should consult with a legal professional to ensure that your Terms of Service comply with all applicable laws and regulations.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 