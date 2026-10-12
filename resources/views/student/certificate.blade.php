@php
  $title = \App\Models\Setting::get('certificate_title', 'Certificate of Completion');
  $intro = \App\Models\Setting::get('certificate_intro', 'This certifies that');
  $completion = str_replace('{{course_title}}', $certificate->course?->title ?? 'this course', \App\Models\Setting::get('certificate_completion_text', 'has successfully completed {{course_title}}'));
  $signatory = \App\Models\Setting::get('certificate_signatory', 'Skillspot Academy');
  $footer = \App\Models\Setting::get('certificate_footer', 'Issued by Skillspot LMS');
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title }} — {{ $certificate->course?->title }}</title>
  <style>
    @page { size: A4 landscape; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: #f3f4f6; color: #172033; font-family: Georgia, serif; }
    .actions { display: flex; justify-content: center; gap: .75rem; padding: 1.5rem; font-family: Arial, sans-serif; }
    button, a { border: 0; border-radius: .5rem; background: #1d4ed8; color: #fff; cursor: pointer; font: 600 .875rem Arial, sans-serif; padding: .75rem 1rem; text-decoration: none; }
    .sheet { width: min(1120px, calc(100vw - 2rem)); min-height: min(790px, calc(100vh - 7rem)); margin: 0 auto 2rem; padding: 3rem; display: grid; place-items: center; background: #fffdf5; border: 18px solid #b5892c; outline: 2px solid #ead9a7; outline-offset: -30px; text-align: center; }
    .mark { color: #a16d11; font: 700 1rem Arial, sans-serif; letter-spacing: .2em; text-transform: uppercase; }
    h1 { margin: 1.5rem 0 .75rem; color: #172033; font-size: clamp(2.2rem, 5vw, 4.5rem); font-weight: 500; }
    .intro, .completion { font-size: clamp(1rem, 2vw, 1.3rem); }
    .name { margin: 1rem 0; color: #1d4ed8; font-size: clamp(2rem, 4vw, 3.4rem); font-style: italic; }
    .course { margin-bottom: 2rem; font-size: clamp(1.35rem, 3vw, 2.25rem); font-weight: 600; }
    .details { width: min(640px, 100%); display: flex; justify-content: space-between; gap: 1rem; border-top: 1px solid #d6c590; padding-top: 1.25rem; color: #5c5237; font: .78rem Arial, sans-serif; text-align: left; }
    .signature { border-top: 1px solid #8c7a45; min-width: 12rem; padding-top: .45rem; text-align: center; }
    @media print { body { background: #fff; } .actions { display: none; } .sheet { width: 100%; min-height: 100vh; margin: 0; border-width: 14px; } }
  </style>
</head>
<body>
  <div class="actions"><button onclick="window.print()">Print / Save as PDF</button><a href="{{ route('student.certs') }}">Back to certificates</a></div>
  <main class="sheet">
    <div>
      <div class="mark">Skillspot LMS</div>
      <h1>{{ $title }}</h1>
      <p class="intro">{{ $intro }}</p>
      <div class="name">{{ $certificate->user?->name }}</div>
      <p class="completion">{{ $completion }}</p>
      <div class="course">{{ $certificate->course?->title }}</div>
      <div class="details"><span>Certificate ID: {{ $certificate->certificate_number }}</span><span>Issued {{ ($certificate->issued_at ?? $certificate->created_at)?->format('d M Y') }}</span><span class="signature">{{ $signatory }}</span></div>
      <p style="margin:1.5rem 0 0;color:#786b48;font:italic .8rem Arial,sans-serif">{{ $footer }}</p>
    </div>
  </main>
</body>
</html>
