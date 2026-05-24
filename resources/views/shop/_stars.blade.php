@props(['rating' => 0, 'size' => '1rem'])
@php
    $r = (float) $rating;
    $full = (int) floor($r);
    $half = ($r - $full) >= 0.5;
@endphp
<span aria-label="{{ number_format($r, 1) }} av 5" style="display: inline-flex; gap: 1px; color: #f59e0b; font-size: {{ $size }}; line-height: 1;">
    @for ($i = 1; $i <= 5; $i++)
        @if ($i <= $full)
            <span>★</span>
        @elseif ($i === $full + 1 && $half)
            <span style="position: relative; display: inline-block;">
                <span style="color: #e2e8f0;">★</span>
                <span style="position: absolute; left: 0; top: 0; width: 50%; overflow: hidden;">★</span>
            </span>
        @else
            <span style="color: #e2e8f0;">★</span>
        @endif
    @endfor
</span>
