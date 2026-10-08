@extends('layouts.landing')
@section('title', 'Privacy Policy — Skillspot.in')
@section('content')

<section class="gradient-hero pt-32 pb-16 px-4 text-center relative overflow-hidden">
  <div class="relative z-10 max-w-2xl mx-auto">
    <h1 class="text-4xl md:text-5xl font-black text-white mb-4">Privacy <span class="gradient-text">Policy</span></h1>
    <p class="text-blue-200">Last updated: {{ date('F d, Y') }}</p>
  </div>
</section>

<section class="py-16 bg-white">
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="prose prose-gray max-w-none space-y-8 text-gray-600 text-sm leading-relaxed">

      <div class="bg-brand-50 border border-brand-200 rounded-2xl p-5 text-brand-700 text-sm">
        Your privacy is important to us. Skillspot.in is committed to protecting your personal data and being transparent about how we use it.
      </div>

      @php $sections = [
        [
          'title' => '1. Information We Collect',
          'content' => 'When you register or use our platform, we collect:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li><strong>Account data:</strong> Name, email address, phone number, country</li>
            <li><strong>Profile data:</strong> Profile photo, bio (if provided)</li>
            <li><strong>Payment data:</strong> Transaction IDs, order IDs (we do NOT store card numbers — Razorpay handles payments)</li>
            <li><strong>Usage data:</strong> Course progress, lesson completion, quiz scores, certificates earned</li>
            <li><strong>Technical data:</strong> IP address, browser type, device info, cookies</li>
          </ul>'
        ],
        [
          'title' => '2. How We Use Your Information',
          'content' => 'We use your data to:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Create and manage your student account</li>
            <li>Provide access to courses and track your progress</li>
            <li>Process payments and issue certificates</li>
            <li>Send important notifications (course updates, receipts, OTP verification)</li>
            <li>Send promotional emails (you can opt out at any time)</li>
            <li>Improve our platform and content quality</li>
          </ul>'
        ],
        [
          'title' => '3. Payment Data & Razorpay',
          'content' => 'All payments are processed securely through <strong>Razorpay</strong>. Skillspot.in does not store your card numbers, CVV, or bank credentials. We only store transaction IDs and order references for record keeping. Razorpay\'s privacy policy applies to all payment processing. <a href="https://razorpay.com/privacy/" target="_blank" class="text-brand-600 hover:underline">View Razorpay Privacy Policy →</a>'
        ],
        [
          'title' => '4. SMS & Email Communications',
          'content' => 'With your consent, we may send:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li><strong>OTP messages</strong> via SMS (Fast2SMS) for account verification</li>
            <li><strong>Transactional emails</strong> for receipts, certificates, and account updates</li>
            <li><strong>Promotional messages</strong> about new courses and offers</li>
          </ul>
          You can unsubscribe from promotional communications at any time via your account settings or by emailing info@Skillspot.in'
        ],
        [
          'title' => '5. Data Sharing',
          'content' => 'We do NOT sell your personal data. We only share data with:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li><strong>Razorpay</strong> — for payment processing</li>
            <li><strong>Fast2SMS</strong> — for OTP delivery</li>
            <li><strong>Cloudflare R2</strong> — for storing course files and assets</li>
            <li><strong>Legal authorities</strong> — only when required by law</li>
          </ul>'
        ],
        [
          'title' => '6. Cookies',
          'content' => 'We use cookies to:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Keep you logged in (session cookies)</li>
            <li>Remember your preferences</li>
            <li>Analyse platform usage (analytics)</li>
          </ul>
          You can disable cookies in your browser settings, but some features may not work correctly.'
        ],
        [
          'title' => '7. Data Security',
          'content' => 'We implement industry-standard security measures including:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>HTTPS/SSL encryption for all data in transit</li>
            <li>Bcrypt hashing for passwords</li>
            <li>Secure session management</li>
            <li>Regular security audits</li>
          </ul>'
        ],
        [
          'title' => '8. Your Rights',
          'content' => 'You have the right to:
          <ul class="list-disc list-inside space-y-1 mt-2">
            <li>Access your personal data</li>
            <li>Correct inaccurate data</li>
            <li>Request deletion of your account and data</li>
            <li>Opt out of promotional communications</li>
            <li>Data portability (export your data)</li>
          </ul>
          To exercise these rights, email us at <a href="mailto:info@Skillspot.in" class="text-brand-600 hover:underline">info@Skillspot.in</a>'
        ],
        [
          'title' => '9. Data Retention',
          'content' => 'We retain your data for as long as your account is active. If you request deletion, we will remove your personal data within 30 days, except where retention is required by law (e.g., financial records for 7 years).'
        ],
        [
          'title' => '10. Children\'s Privacy',
          'content' => 'Our platform is not intended for children under 13 years of age. We do not knowingly collect personal data from children. If you believe a child has registered, please contact us immediately.'
        ],
        [
          'title' => '11. Changes to This Policy',
          'content' => 'We may update this Privacy Policy from time to time. We will notify registered users via email of any significant changes. Continued use of the platform after changes constitutes acceptance.'
        ],
        [
          'title' => '12. Contact Us',
          'content' => 'For privacy-related questions or concerns:
          <ul class="list-none space-y-1 mt-2">
            <li>📧 Email: <a href="mailto:info@Skillspot.in" class="text-brand-600 hover:underline">info@Skillspot.in</a></li>
            <li>🌐 Website: <a href="https://Skillspot.in" class="text-brand-600 hover:underline">Skillspot.in</a></li>
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
