@if(filled($data['title'] ?? null))
    <h2>{{ $data['title'] }}</h2>
@endif

@if(filled($data['content'] ?? null))
    <div class="dco-intro">{!! cms()->convertToHtml($data['content']) !!}</div>
@endif

@foreach($groups as $category => $cookies)
    <h3>{{ \Dashed\DashedCookies\Classes\CookieSettings::text($category.'_label', $siteId) }}</h3>
    <p class="dco-desc">{{ \Dashed\DashedCookies\Classes\CookieSettings::text($category.'_description', $siteId) }}</p>
    <div class="dco-table-wrap">
        <table>
            <thead>
                <tr>
                    <th scope="col">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('name') }}</th>
                    <th scope="col">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('provider') }}</th>
                    <th scope="col">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('purpose') }}</th>
                    <th scope="col">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('duration') }}</th>
                    <th scope="col">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('type') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cookies as $cookie)
                    <tr>
                        <td><code>{{ $cookie->name }}</code></td>
                        <td>
                            @if($cookie->privacy_url)
                                <a href="{{ $cookie->privacy_url }}" target="_blank" rel="noopener nofollow">{{ $cookie->provider }}</a>
                            @else
                                {{ $cookie->provider }}
                            @endif
                        </td>
                        <td>{{ $cookie->purpose }}</td>
                        <td>{{ $cookie->duration }}</td>
                        <td>{{ $typeLabels[$cookie->type] ?? $cookie->type }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endforeach

@if($data['show_settings_button'] ?? true)
    <button type="button" class="dco-button" data-cookie-settings>{{ \Dashed\DashedCookies\Classes\CookieSettings::text('customize', $siteId) }}</button>
@endif

@if($updated = \Dashed\DashedCookies\Classes\CookieOverview::lastUpdated($siteId))
    <p class="dco-updated">{{ \Dashed\DashedCookies\Classes\CookieOverview::label('updated') }}: {{ $updated->isoFormat('LL') }}</p>
@endif
