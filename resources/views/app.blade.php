<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @inject('siteSettings', 'App\Services\SiteSettings')
        @if ($googleSiteVerification = $siteSettings->get('seo_google_site_verification'))
            <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
        @endif
        @if ($bingSiteVerification = $siteSettings->get('seo_bing_site_verification'))
            <meta name="msvalidate.01" content="{{ $bingSiteVerification }}">
        @endif
        @php($googleAnalyticsId = $siteSettings->get('seo_google_analytics_id'))
        @if ($googleAnalyticsId && ! auth()->check())
            <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAnalyticsId }}"></script>
            <script>
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', @js($googleAnalyticsId));
            </script>
        @endif

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: #fefefe;
            }

            html.dark {
                background-color: #0f1226;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="16x16 32x32">
        <link rel="icon" href="/favicon.png" type="image/png" sizes="192x192">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
