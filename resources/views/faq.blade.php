@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h1 class="mb-0">Frequently Asked Questions (FAQ)</h1>
                </div>
                <div class="card-body">
                    <p class="lead">Find answers to commonly asked questions about Mad Krapow's products and services.</p>
                    
                    <div class="accordion mt-4" id="faqAccordion">
                        <!-- General Questions -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingGeneral">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGeneral" aria-expanded="true" aria-controls="collapseGeneral">
                                    General Questions
                                </button>
                            </h2>
                            <div id="collapseGeneral" class="accordion-collapse collapse show" aria-labelledby="headingGeneral" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: What type of cuisine does Mad Krapow specialize in?</h5>
                                        <p>A: Mad Krapow offers a diverse selection of delectable Thai cuisine, including ready-to-cook pastes.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Where is your headquarters located?</h5>
                                        <p>A: Our headquarters are situated in Malaysia, and we provide nationwide delivery services.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How can I reach Mad Krapow?</h5>
                                        <p>A: You may contact us via email at k.anwarbakar@madkrapow.com.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ordering and Payment -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOrdering">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOrdering" aria-expanded="false" aria-controls="collapseOrdering">
                                    Ordering and Payment
                                </button>
                            </h2>
                            <div id="collapseOrdering" class="accordion-collapse collapse" aria-labelledby="headingOrdering" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: How can I place an order?</h5>
                                        <p>A: You can directly place an order through our website, madkrapowapp.com.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Which payment methods do you accept?</h5>
                                        <p>A: We accept payments via Stripe (credit/debit cards) and OCBC (DuitNow and direct transfer).</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Is my payment information secure?</h5>
                                        <p>A: Yes, your payment information is securely processed by Stripe and OCBC. We do not retain your complete payment details.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Is it possible to cancel an order?</h5>
                                        <p>A: Cancellation policies vary depending on the food packaging and preparation status. Please contact us promptly if you require order cancellation. Once food preparation commences, cancellation may not be feasible.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How can I request a refund?</h5>
                                        <p>A: If a refund is applicable, it will be processed through your original payment method. Please contact us with any concerns regarding your order.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Are your prices inclusive of taxes?</h5>
                                        <p>A: Yes, all prices displayed on our website are inclusive of applicable taxes, unless otherwise specified.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Delivery -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingDelivery">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelivery" aria-expanded="false" aria-controls="collapseDelivery">
                                    Delivery
                                </button>
                            </h2>
                            <div id="collapseDelivery" class="accordion-collapse collapse" aria-labelledby="headingDelivery" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: Where do you deliver your products?</h5>
                                        <p>A: We provide delivery services throughout Malaysia through the services of J&T Logistics.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: What are the delivery charges?</h5>
                                        <p>A: Delivery fees, if applicable, will be displayed during the checkout process.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How long does delivery take?</h5>
                                        <p>A: Estimated delivery times are provided during the ordering process. Please note that delivery times may vary due to factors beyond our control, such as traffic or weather conditions.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: What happens if I'm not home to receive my delivery?</h5>
                                        <p>A: You are responsible for ensuring someone is available to receive the delivery at the specified address. Please track your delivery via the J&T tracking number provided.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account and Website -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAccount">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAccount" aria-expanded="false" aria-controls="collapseAccount">
                                    Account and Website
                                </button>
                            </h2>
                            <div id="collapseAccount" class="accordion-collapse collapse" aria-labelledby="headingAccount" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: Do I need an account to place an order?</h5>
                                        <p>A: No, you can place an order as a guest. However, creating an account enhances your ordering experience.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How do I create an account?</h5>
                                        <p>A: You can create an account during the checkout process or by clicking on the "Account" section of our website.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How do I reset my password?</h5>
                                        <p>A: You can reset your password by clicking on the "Forgot Password" link on the login page.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Are my personal details safe?</h5>
                                        <p>A: Yes, we take your privacy seriously. Please refer to our Privacy Policy for more information.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Product Questions -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingProduct">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProduct" aria-expanded="false" aria-controls="collapseProduct">
                                    Product Questions
                                </button>
                            </h2>
                            <div id="collapseProduct" class="accordion-collapse collapse" aria-labelledby="headingProduct" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: Are your ready-to-cook pastes spicy?</h5>
                                        <p>A: Spiciness levels may vary between products. Please refer to the product descriptions for details.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How should I store your ready-to-cook pastes?</h5>
                                        <p>A: Storage instructions are provided on the product packaging.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: Are your products Halal?</h5>
                                        <p>A: Please refer to the individual product descriptions for Halal certification information.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Other Questions -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOther">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOther" aria-expanded="false" aria-controls="collapseOther">
                                    Other Questions
                                </button>
                            </h2>
                            <div id="collapseOther" class="accordion-collapse collapse" aria-labelledby="headingOther" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-4">
                                        <h5>Q: What if I have a problem with my order?</h5>
                                        <p>A: Please contact us immediately at k.anwarbakar@madkrapow.com with your order details and a description of the issue.</p>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Q: How do I give feedback?</h5>
                                        <p>A: We welcome your feedback! Please email us at k.anwarbakar@madkrapow.com.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-secondary mt-4">
                        <strong>Disclaimer:</strong> This FAQ is for informational purposes only and may be updated from time to time. Please refer to our Terms of Service and Privacy Policy for complete details.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 