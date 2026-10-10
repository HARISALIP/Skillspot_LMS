@extends('layouts.landing')
@section('title', 'Terms of Service — Skillspot.in')
@section('content')

<section class="gradient-hero pt-32 pb-16 px-4 text-center relative overflow-hidden">
  <div class="relative z-10 max-w-2xl mx-auto">
    <h1 class="text-4xl md:text-5xl font-black text-slate-900 mb-4 tracking-tight">Terms of <span class="gradient-text">Service</span></h1>
    <p class="text-slate-600 font-medium">Last updated: {{ date('F d, Y') }}</p>
  </div>
</section>

<section class="py-16 bg-white">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="space-y-8 text-gray-600 text-sm leading-relaxed">

      <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5 text-yellow-800 text-sm">
        ⚠️ Please read these Terms of Service carefully before using Skillspot.in. By registering or using our platform, you agree to these terms.
      </div>

      @php $sections = [
        [
          'title' => '1. Acceptance of Terms',
          'content' => 'By accessing or using Skillspot.in ("Platform", "we", "us"), you agree to be bound by these Terms of Service. If you do not agree, please do not use our platform.'
        ],
        [
          'title' => '2. User Accounts',
          'content' => '<ul class="list-disc list-inside space-y-1 mt-2">
            <li>You must provide accurate and complete information when registering</li>
            <li>You are responsible for maintaining the security of your account credentials</li>
            <li>You must not share your account with others</li>
            <li>You must be at least 13 years old to register</li>
            <li>We reserve the right to terminate accounts that violate these terms</li>
          </ul>'
        ],
        [
          'title' => '3. Course Access & Licenses',
          'content' => '<ul class="list-disc list-inside space-y-1 mt-2">
            <li>Upon enrolling in a course, you receive a personal, non-transferable license to access that course content</li>
            <li>Course content is for personal, non-commercial learning only</li>
            <li>You may not share, resell, or redistribute course materials</li>
            <li>Access continues as long as your account is active and in good standing</li>
          </ul>'
        ],
        [
          'title' => '4. Payments & Refunds',
          'content' => '<ul class="list-disc list-inside space-y-1 mt-2">
            <li>All prices are in Indian Rupees (INR) and exclusive of applicable GST</li>
            <li>Payments are processed securely via Razorpay</li>
            <li>We offer a <strong>7-day money-back guarantee</strong> on all purchases</li>
            <li>Refund requests must be submitted within 7 days of purchase via email to info@Skillspot.in</li>
            <li>Refunds will be processed within 5-7 business days to the original payment method</li>
            <li>Certificates already downloaded are not eligible for refund</li>
          </ul>'
        ],
        [
          'title' => '5. Intellectual Property',
          'content' => 'All course content, videos, materials, brand assets, and platform code are the intellectual property of Skillspot.in. You may not copy, reproduce, distribute, or create derivative works without written permission. Student-created projects remain the property of the student.'
        ],
        [
          'title' => '6. Prohibited Conduct',
          'content' => 'You agree NOT to:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Share login credentials or course content with third parties</li>
            <li>Use the platform for any illegal or unauthorised purpose</li>
            <li>Harass, abuse, or harm other users or instructors</li>
            <li>Post spam, malware, or harmful content</li>
            <li>Attempt to hack, scrape, or disrupt the platform</li>
            <li>Misrepresent yourself or impersonate others</li>
          </ul>'
        ],
        [
          'title' => '7. Certificates',
          'content' => 'Certificates are issued upon successful completion of a course as defined by our completion criteria. Certificates are:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Digitally verifiable via unique certificate numbers</li>
            <li>Non-transferable — issued only to the enrolled student</li>
            <li>Subject to revocation if obtained through dishonest means</li>
          </ul>'
        ],
        [
          'title' => '8. Limitation of Liability',
          'content' => 'Skillspot.in is not liable for:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Job placement or employment outcomes after course completion</li>
            <li>Technical issues caused by your internet connection or device</li>
            <li>Loss of data due to circumstances beyond our control</li>
            <li>Indirect, incidental, or consequential damages</li>
          </ul>
          Our total liability is limited to the amount you paid for the specific course or subscription in question.'
        ],
        [
          'title' => '9. Third-Party Services',
          'content' => 'Our platform integrates with third-party services including Razorpay (payments), Fast2SMS (OTP), and Cloudflare (storage). Your use of these services is subject to their respective terms and privacy policies. We are not responsible for third-party service interruptions.'
        ],
        [
          'title' => '10. Platform Availability',
          'content' => 'We strive for 99.9% uptime but cannot guarantee uninterrupted service. We reserve the right to perform maintenance that may temporarily affect access. We will notify users of scheduled maintenance in advance.'
        ],
        [
          'title' => '11. Modifications',
          'content' => 'We may update these Terms at any time. We will notify registered users via email of significant changes. Continued use of the platform after notification constitutes acceptance of updated terms.'
        ],
        [
          'title' => '12. Governing Law',
          'content' => 'These Terms are governed by the laws of India. Any disputes shall be subject to the exclusive jurisdiction of courts in Kerala, India.'
        ],
        [
          'title' => '13. Contact',
          'content' => 'For questions about these Terms:
          <ul class="list-none space-y-1 mt-2">
            <li>📧 <a href="mailto:info@Skillspot.in" class="text-brand-600 hover:underline">info@Skillspot.in</a></li>
            <li>🌐 <a href="https://Skillspot.in" class="text-brand-600 hover:underline">Skillspot.in</a></li>
          </ul>'
        ],
      ]; @endphp

      @foreach($sections as $section)
      <div>
        <h2 class="text-lg font-black text-gray-900 mb-2">{{ $section['title'] }}</h2>
        <div>{!! $section['content'] !!}</div>
      </div>
      @endforeach

    </div>
  </div>
</section>
@endsection
