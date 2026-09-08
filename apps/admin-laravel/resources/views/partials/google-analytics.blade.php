{{-- Google Analytics 4 (gtag.js) — public marketing site. --}}
@php($gaId = trim((string) config('services.google_analytics_id', '')))
@if($gaId !== '')
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($gaId) }}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', @json($gaId));
</script>
@endif
