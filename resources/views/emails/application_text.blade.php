{{-- resources/views/emails/application_text.blade.php (plain-text alternative; improves deliverability) --}}
{!! trim(html_entity_decode(strip_tags(preg_replace(['#<br\s*/?>#i', '#</p>#i'], ["\n", "\n\n"], $bodyHtml)))) !!}

{{ $seekerName }}
@if(!empty($seekerEmail)){{ $seekerEmail }}
@endif

--
Sent by {{ $seekerName }} via {{ config('app.name') }}{{ $countryName ? ' · ' . $countryName : '' }}
Hiring? To post a job on {{ config('app.name') }}, email us at {{ config('mail.from.address') }}