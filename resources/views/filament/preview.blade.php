{{--
    Voorbeeld van de banner op het instellingenscherm. Statisch (geen
    JavaScript) en in een eigen kader, met dezelfde maten als de echte banner.
--}}
@php
    $c = $colors;
@endphp
<div style="position: relative; height: {{ $layout === 'bar' ? '240px' : '340px' }}; border-radius: 12px; overflow: hidden; background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 12px, #e5e7eb 12px, #e5e7eb 24px); font-family: inherit;">
    @if($layout === 'modal')
        <div style="position: absolute; inset: 0; background: rgba(0,0,0,.45);"></div>
    @endif
    <div style="position: absolute; background: {{ $c['background'] }}; color: {{ $c['text'] }}; box-shadow: 0 10px 40px rgba(0,0,0,.18); padding: 18px; font-size: 13px; line-height: 1.5;
        @if($layout === 'bar') left: 0; right: 0; bottom: 0; border-radius: {{ $radius }}px {{ $radius }}px 0 0;
        @elseif($layout === 'modal') top: 50%; left: 50%; transform: translate(-50%, -50%); width: 380px; max-width: calc(100% - 32px); border-radius: {{ $radius }}px;
        @else bottom: 12px; {{ $position === 'right' ? 'right' : 'left' }}: 12px; width: 340px; max-width: calc(100% - 24px); border-radius: {{ $radius }}px;
        @endif">
        <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px;">{{ $title }}</div>
        <div>{{ $text }} @if($policy)<span style="text-decoration: underline;">{{ $policy }}</span>@endif</div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px;">
            @foreach([$reject, $accept] as $label)
                <span style="flex: 1 1 auto; text-align: center; background: {{ $c['primary'] }}; color: {{ $c['primary_text'] }}; border: 2px solid {{ $c['primary'] }}; font-weight: 600; padding: 7px 12px; border-radius: {{ round($radius * .6) }}px;">{{ $label }}</span>
            @endforeach
            <span style="padding: 7px 4px; text-decoration: underline;">{{ $customize }}</span>
        </div>
    </div>
</div>
@foreach($warnings as $warning)
    <p style="margin-top: 8px; color: #b45309; font-size: 13px;">{{ $warning }}</p>
@endforeach
