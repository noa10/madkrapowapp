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
                    
                    <h2 class="h4 mt-4">1. Introduction</h2>
                    <p>Mad Krapow ("we," "us," or "our") is committed to protecting your privacy. This Privacy Policy outlines how we collect, use, and disclose your personal information when you visit our website (https://madkrapowapp.com) and use our services.</p>
                    
                    <h2 class="h4 mt-4">2. Information We Collect</h2>
                    <p>We collect the following types of information:</p>
                    
                    <h5 class="mt-3">Personal Information:</h5>
                    <ul>
                        <li>Name</li>
                        <li>Email address</li>
                        <li>Phone number</li>
                        <li>Delivery address</li>
                        <li>Payment information (processed by Stripe and OCBC, we do not store full payment details)</li>
                        <li>Account login details (if you create an account)</li>
                    </ul>
                    
                    <h5 class="mt-3">Usage Information:</h5>
                    <ul>
                        <li>IP address</li>
                        <li>Browser type</li>
                        <li>Pages visited</li>
                        <li>Time spent on our website</li>
                        <li>Cookies and similar technologies (see our Cookie Policy below)</li>
                    </ul>
                    
                    <h5 class="mt-3">Order Information:</h5>
                    <ul>
                        <li>Order History</li>
                        <li>Order Preferences</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">3. How We Use Your Information</h2>
                    <p>We use your information for the following purposes:</p>
                    <ul>
                        <li>To process and fulfill your orders.</li>
                        <li>To communicate with you about your orders and provide customer support.</li>
                        <li>To improve our website and services.</li>
                        <li>To send you promotional emails and updates (you can opt out at any time).</li>
                        <li>To comply with legal obligations.</li>
                        <li>To detect and prevent fraud.</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">4. Sharing Your Information</h2>
                    <p>We may share your information with:</p>
                    
                    <h5 class="mt-3">Service Providers:</h5>
                    <ul>
                        <li>Stripe and OCBC (for payment processing).</li>
                        <li>J&T (for delivery).</li>
                        <li>Email marketing providers.</li>
                        <li>Website hosting providers.</li>
                    </ul>
                    
                    <h5 class="mt-3">Legal Compliance:</h5>
                    <p>When required by law or to protect our rights.</p>
                    
                    <h5 class="mt-3">Business Transfers:</h5>
                    <p>In connection with a merger, acquisition, or sale of assets.</p>
                    
                    <h2 class="h4 mt-4">5. Data Security</h2>
                    <p>We take reasonable measures to protect your personal information from unauthorized access, use, or disclosure. However, no method of transmission over the internet or electronic storage is 100% secure.</p>
                    
                    <h2 class="h4 mt-4">6. Data Retention</h2>
                    <p>We retain your personal information for as long as necessary to fulfill the purposes outlined in this Privacy Policy, unless a longer retention period is required or permitted by law.</p>
                    
                    <h2 class="h4 mt-4">7. Your Rights</h2>
                    <p>You have the following rights regarding your personal information:</p>
                    <ul>
                        <li><strong>Access:</strong> You can request access to the personal information we hold about you.</li>
                        <li><strong>Correction:</strong> You can request that we correct any inaccurate or incomplete information.</li>
                        <li><strong>Deletion:</strong> You can request that we delete your personal information (subject to legal limitations).</li>
                        <li><strong>Opt-Out:</strong> You can opt out of receiving promotional emails at any time.</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">8. Cookie Policy</h2>
                    <p>We use cookies and similar technologies to enhance your browsing experience. Cookies are small files that are stored on your device. You can control cookies through your browser settings.</p>
                    
                    <p>We use cookies for:</p>
                    <ul>
                        <li>Website functionality.</li>
                        <li>Analytics.</li>
                        <li>Personalization.</li>
                    </ul>
                    
                    <h2 class="h4 mt-4">9. Third-Party Links</h2>
                    <p>Our website may contain links to third-party websites. We are not responsible for the privacy practices of these websites.</p>
                    
                    <h2 class="h4 mt-4">10. Changes to This Privacy Policy</h2>
                    <p>We may update this Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page.</p>
                    
                    <h2 class="h4 mt-4">11. Contact Information</h2>
                    <p>If you have any questions about this Privacy Policy, please contact us at:</p>
                    <p>k.anwarbakar@madkrapow.com</p>
                    
                    <h2 class="h4 mt-4">12. Governing Law</h2>
                    <p>This Privacy Policy shall be governed by and construed in accordance with the laws of Malaysia.</p>
                    
                    <div class="alert alert-secondary mt-4">
                        <strong>Disclaimer:</strong> This Privacy Policy is provided for informational purposes only and does not constitute legal advice. You should consult with a legal professional to ensure that your Privacy Policy complies with all applicable laws and regulations.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 