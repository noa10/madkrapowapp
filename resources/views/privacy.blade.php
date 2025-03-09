@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0">Privacy Policy</h1>
                </div>
                <div class="card-body">
                    <p class="lead">Last updated: {{ date('F d, Y') }}</p>
                    
                    <p>Mad Krapow ("we", "our", or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or make a purchase.</p>
                    
                    <h2 class="h4 mt-4">1. Information We Collect</h2>
                    <p>We may collect personal information that you voluntarily provide to us when you:</p>
                    <ul>
                        <li>Register on our website</li>
                        <li>Place an order</li>
                        <li>Subscribe to our newsletter</li>
                        <li>Contact us</li>
                        <li>Participate in promotions or surveys</li>
                    </ul>
                    
                    <p>The personal information we may collect includes:</p>
                    <ul>
                        <li>Name</li>
                        <li>Email address</li>
                        <li>Phone number</li>
                        <li>Shipping and billing address</li>
                        <li>Payment information (processed securely through our payment processors)</li>
                        <li>Order history</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">2. How We Use Your Information</h2>
                    <p>We may use the information we collect for various purposes, including to:</p>
                    <ul>
                        <li>Process and fulfill your orders</li>
                        <li>Communicate with you about your orders, products, and services</li>
                        <li>Provide customer support</li>
                        <li>Send you marketing communications (with your consent)</li>
                        <li>Improve our website and services</li>
                        <li>Comply with legal obligations</li>
                        <li>Detect and prevent fraud</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">3. Cookies and Tracking Technologies</h2>
                    <p>We use cookies and similar tracking technologies to track activity on our website and store certain information. Cookies are files with a small amount of data that may include an anonymous unique identifier. You can instruct your browser to refuse all cookies or to indicate when a cookie is being sent.</p>
                    
                    <h2 class="h4 mt-4">4. Third-Party Service Providers</h2>
                    <p>We may share your information with third-party service providers who perform services on our behalf, such as:</p>
                    <ul>
                        <li>Payment processors (Stripe and OCBC)</li>
                        <li>Shipping and delivery services (J&T)</li>
                        <li>Email marketing services</li>
                        <li>Analytics providers</li>
                    </ul>
                    <p>These third parties have access to your personal information only to perform these tasks on our behalf and are obligated not to disclose or use it for any other purpose.</p>
                    
                    <h2 class="h4 mt-4">5. Data Security</h2>
                    <p>We implement appropriate security measures to protect your personal information. However, no method of transmission over the Internet or electronic storage is 100% secure, and we cannot guarantee absolute security.</p>
                    
                    <h2 class="h4 mt-4">6. Your Rights</h2>
                    <p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
                    <ul>
                        <li>The right to access your personal information</li>
                        <li>The right to correct inaccurate information</li>
                        <li>The right to request deletion of your information</li>
                        <li>The right to object to or restrict processing of your information</li>
                        <li>The right to data portability</li>
                    </ul>
                    <p>To exercise these rights, please contact us using the information provided below.</p>
                    
                    <h2 class="h4 mt-4">7. Marketing Communications</h2>
                    <p>You can opt out of receiving marketing communications from us by clicking the "unsubscribe" link in our emails or by contacting us directly.</p>
                    
                    <h2 class="h4 mt-4">8. Children's Privacy</h2>
                    <p>Our website is not intended for children under 13 years of age. We do not knowingly collect personal information from children under 13.</p>
                    
                    <h2 class="h4 mt-4">9. Changes to This Privacy Policy</h2>
                    <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "Last updated" date.</p>
                    
                    <h2 class="h4 mt-4">10. Contact Us</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                    <p>Email: k.anwarbakar@madkrapow.com</p>
                    
                    <div class="alert alert-secondary mt-4">
                        <strong>Disclaimer:</strong> This Privacy Policy is provided for informational purposes only and does not constitute legal advice. You should consult with a legal professional to ensure that your Privacy Policy complies with all applicable laws and regulations, including GDPR, CCPA, and other privacy laws that may apply to your business.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 