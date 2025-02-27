@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            Madkrapow Contact Notification
        @endcomponent
    @endslot

    # New Contact Form Submission

    **Name:** {{ $name }}<br>
    **Email:** {{ $email }}<br>
    **Subject:** {{ $subject }}

    **Message:**  
    {{ $messageContent }}

    @slot('footer')
        @component('mail::footer')
            © {{ date('Y') }} Madkrapow. All rights reserved.
        @endcomponent
    @endslot
@endcomponent