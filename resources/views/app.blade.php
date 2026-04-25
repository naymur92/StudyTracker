<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Meta Tags: Charset & Viewport -->
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <!-- SEO Meta Tags -->
    <title>StudyTracker - Learn Smart, Revise Systematically</title>
    <meta name="description"
        content="StudyTracker is a spaced repetition study management app. Track topics, manage revision schedules, and monitor your learning progress with intelligent study reminders based on the Ebbinghaus forgetting curve." />
    <meta name="keywords"
        content="study app, spaced repetition, learning management, revision planner, study tracker, progress monitoring, learning tools, education, flashcards, study schedule" />
    <meta name="author" content="StudyTracker" />
    <meta name="theme-color" content="#0ea5e9" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="{{ url('/') }}" />

    <!-- Security -->
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <!-- Favicon & Icons -->
    <!-- Standard favicon -->
    <link rel="icon" type="image/png" href="/favicon.png" sizes="32x32" />
    <link rel="icon" type="image/x-icon" href="/favicon.ico" />

    <!-- Apple Touch Icon for iOS PWA -->
    <link rel="apple-touch-icon" href="/icon_180x180.png" sizes="180x180" />

    <!-- Android PWA Icons -->
    <link rel="icon" type="image/png" href="/icon_192x192.png" sizes="192x192" />
    <link rel="icon" type="image/png" href="/icon_512x512.png" sizes="512x512" />

    <!-- SVG favicon (uncomment when available) -->
    {{-- <link rel="icon" type="image/svg+xml" href="/favicon.svg" /> --}}

    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:title" content="StudyTracker - Learn Smart, Revise Systematically" />
    <meta property="og:description"
        content="Powerful spaced repetition study management platform. Track topics, schedule revisions, log practice sessions, and monitor your learning progress." />
    <meta property="og:image" content="/logo.png" />
    <meta property="og:image:width" content="1200" />
    <meta property="og:image:height" content="630" />
    <meta property="og:site_name" content="StudyTracker" />
    <meta property="og:locale" content="en_US" />

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:url" content="{{ url('/') }}" />
    <meta name="twitter:title" content="StudyTracker - Learn Smart, Revise Systematically" />
    <meta name="twitter:description" content="Powerful spaced repetition study management platform for effective learning and revision." />
    <meta name="twitter:image" content="/logo.png" />

    <!-- PWA Web App Manifest -->
    <link rel="manifest" href="/manifest.json" />

    <!-- Color Scheme -->
    <meta name="color-scheme" content="light dark" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div id="app"></div>
</body>

</html>
