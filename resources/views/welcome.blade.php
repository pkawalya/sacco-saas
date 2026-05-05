<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Banking Software for SACCOs & Microfinance</title>
    <meta name="description"
        content="Enterprise-grade core banking software purpose-built for Savings & Credit Cooperatives and Microfinance institutions. Member management, loans, savings, GL, compliance — one platform.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --white: #ffffff;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --blue-900: #1e3a5f;
            --green-500: #22c55e;
            --emerald-50: #ecfdf5;
            --violet-500: #8b5cf6;
            --navy: #0f172a;
            --max-w: 1200px;
        }

        html {
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--gray-800);
            background: var(--white);
            line-height: 1.6;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        img {
            display: block;
            max-width: 100%;
            height: auto;
        }

        .container {
            max-width: var(--max-w);
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* === NAV === */
        .nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--gray-200);
            height: 72px;
        }

        .nav .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 800;
            font-size: 1.125rem;
            color: var(--navy);
            letter-spacing: -0.02em;
        }

        .nav-brand .mark {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 900;
            font-size: 1rem;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 2.25rem;
        }

        .nav-menu a {
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--gray-600);
            transition: color 0.2s;
        }

        .nav-menu a:hover {
            color: var(--gray-900);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-sm {
            padding: 0.5rem 1.125rem;
        }

        .btn-md {
            padding: 0.625rem 1.5rem;
        }

        .btn-lg {
            padding: 0.875rem 2rem;
            font-size: 1rem;
            border-radius: 10px;
        }

        .btn-primary {
            background: var(--blue-600);
            color: white;
        }

        .btn-primary:hover {
            background: var(--blue-700);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-outline {
            background: transparent;
            color: var(--gray-700);
            border: 1.5px solid var(--gray-300);
        }

        .btn-outline:hover {
            border-color: var(--gray-400);
            background: var(--gray-50);
        }

        .btn-white {
            background: white;
            color: var(--blue-700);
            border: 1.5px solid rgba(255, 255, 255, 0.3);
        }

        .btn-white:hover {
            background: var(--blue-50);
        }

        .btn-ghost {
            background: transparent;
            color: white;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.4);
        }

        /* === HERO === */
        .hero {
            background: linear-gradient(135deg, var(--navy) 0%, #1a2744 50%, #1e3a5f 100%);
            color: white;
            padding: 10rem 0 6rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -200px;
            right: -200px;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.15), transparent 70%);
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -300px;
            left: -100px;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.08), transparent 70%);
        }

        .hero .container {
            position: relative;
            z-index: 1;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--blue-100);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1.5rem;
        }

        .hero-eyebrow .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green-500);
            animation: blink 2s infinite;
        }

        @keyframes blink {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }

        .hero h1 {
            font-size: clamp(2.25rem, 4.5vw, 3.5rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 1.5rem;
        }

        .hero-sub {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
            margin-bottom: 2.5rem;
            max-width: 500px;
        }

        .hero-actions {
            display: flex;
            gap: 0.75rem;
        }

        .hero-visual {
            position: relative;
        }

        .hero-visual img {
            border-radius: 12px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        /* === STATS === */
        .stats-bar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 3.5rem 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 2rem;
            text-align: center;
        }

        .stat-val {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--blue-700);
            letter-spacing: -0.02em;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        /* === SECTIONS === */
        .section {
            padding: 6rem 0;
        }

        .section-alt {
            background: var(--gray-50);
        }

        .section-dark {
            background: var(--navy);
            color: white;
        }

        .section-label {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--blue-600);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.75rem;
        }

        .section-dark .section-label {
            color: var(--blue-100);
        }

        .section-title {
            font-size: clamp(1.75rem, 3vw, 2.5rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .section-dark .section-title {
            color: white;
        }

        .section-desc {
            font-size: 1.0625rem;
            color: var(--gray-500);
            max-width: 560px;
            line-height: 1.7;
        }

        .section-dark .section-desc {
            color: rgba(255, 255, 255, 0.6);
        }

        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-header .section-desc {
            margin: 0 auto;
        }

        /* === AUDIENCE CARDS === */
        .audience-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
        }

        .audience-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 2rem 1.5rem;
            transition: all 0.3s;
            cursor: default;
        }

        .audience-card:hover {
            border-color: var(--blue-500);
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.08);
            transform: translateY(-3px);
        }

        .audience-card .emoji {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .audience-card h3 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--gray-900);
        }

        .audience-card p {
            font-size: 0.8125rem;
            color: var(--gray-500);
            line-height: 1.6;
        }

        /* === PRODUCTS (SBS-style tabs) === */
        .products-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--gray-200);
            margin-bottom: 3rem;
            overflow-x: auto;
        }

        .products-tabs button {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray-400);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s;
            margin-bottom: -2px;
        }

        .products-tabs button:hover {
            color: var(--gray-700);
        }

        .products-tabs button.active {
            color: var(--blue-600);
            border-bottom-color: var(--blue-600);
        }

        .product-panel {
            display: none;
        }

        .product-panel.active {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .product-panel h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--gray-900);
        }

        .product-panel p {
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        .product-features {
            list-style: none;
        }

        .product-features li {
            padding: 0.5rem 0;
            font-size: 0.9rem;
            color: var(--gray-600);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .product-features .check {
            color: var(--green-500);
            font-weight: 700;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .product-visual {
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 16px;
            padding: 2.5rem;
            text-align: center;
            min-height: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .product-visual .big-emoji {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .product-visual .caption {
            font-size: 0.875rem;
            color: var(--gray-400);
        }

        /* === SECURITY GRID === */
        .security-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .sec-card {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 2rem;
            transition: all 0.3s;
        }

        .sec-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.15);
        }

        .sec-card h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .sec-card p {
            font-size: 0.8125rem;
            color: rgba(255, 255, 255, 0.5);
            line-height: 1.6;
        }

        .sec-card .ico {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        /* === PRICING === */
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .price-card {
            background: var(--white);
            border: 1.5px solid var(--gray-200);
            border-radius: 16px;
            padding: 2.5rem;
            transition: all 0.3s;
            position: relative;
        }

        .price-card.featured {
            border-color: var(--blue-600);
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.1);
        }

        .price-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.06);
        }

        .price-badge {
            position: absolute;
            top: -14px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--blue-600);
            color: white;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.3rem 1.25rem;
            border-radius: 100px;
            letter-spacing: 0.04em;
        }

        .price-tier {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .price-amount {
            font-size: 3rem;
            font-weight: 800;
            color: var(--gray-900);
            margin: 0.5rem 0;
            letter-spacing: -0.03em;
        }

        .price-amount small {
            font-size: 1rem;
            font-weight: 500;
            color: var(--gray-400);
        }

        .price-desc {
            font-size: 0.875rem;
            color: var(--gray-500);
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-200);
        }

        .price-list {
            list-style: none;
            margin-bottom: 2rem;
        }

        .price-list li {
            padding: 0.4rem 0;
            font-size: 0.875rem;
            color: var(--gray-600);
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .price-list .ck {
            color: var(--green-500);
            font-weight: 600;
        }

        .price-card .btn {
            width: 100%;
        }

        .price-toggle {
            display: flex;
            gap: 0;
            background: var(--gray-100);
            border-radius: 10px;
            padding: 4px;
            width: fit-content;
            margin: 0 auto 3rem;
        }

        .price-toggle button {
            padding: 0.5rem 1.5rem;
            border: none;
            background: none;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-500);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .price-toggle button.active {
            background: white;
            color: var(--gray-900);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        /* === TESTIMONIALS === */
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }

        .test-card {
            background: var(--white);
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 2rem;
        }

        .test-card blockquote {
            font-size: 0.9375rem;
            color: var(--gray-600);
            line-height: 1.7;
            font-style: italic;
            margin-bottom: 1.25rem;
        }

        .test-card cite {
            display: flex;
            flex-direction: column;
            font-style: normal;
        }

        .test-card .name {
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--gray-900);
        }

        .test-card .role {
            font-size: 0.8125rem;
            color: var(--gray-400);
        }

        /* === ORDER FORM === */
        .order-section {
            background: var(--gray-50);
            border-top: 1px solid var(--gray-200);
        }

        .order-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5rem;
            align-items: start;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 0.375rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background: var(--white);
            border: 1.5px solid var(--gray-300);
            color: var(--gray-900);
            font-size: 0.9375rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue-500);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .alert-success {
            padding: 1rem 1.25rem;
            border-radius: 10px;
            background: var(--emerald-50);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #15803d;
            font-size: 0.9375rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .form-error {
            font-size: 0.8125rem;
            color: #ef4444;
            margin-top: 0.25rem;
        }

        /* === CTA BANNER === */
        .cta-banner {
            background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
            color: white;
            text-align: center;
            padding: 5rem 2rem;
            border-radius: 20px;
            margin: 0 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .cta-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.05);
        }

        .cta-banner h2 {
            font-size: clamp(1.5rem, 3vw, 2.25rem);
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .cta-banner p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 2rem;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
        }

        .cta-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        /* === FOOTER === */
        footer {
            background: var(--navy);
            color: rgba(255, 255, 255, 0.5);
            padding: 4rem 0 2rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand {
            font-size: 1.125rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
        }

        .footer-brand-desc {
            font-size: 0.875rem;
            line-height: 1.7;
            max-width: 280px;
        }

        .footer-col h4 {
            font-size: 0.8125rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1rem;
        }

        .footer-col a {
            display: block;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.45);
            padding: 0.25rem 0;
            transition: color 0.2s;
        }

        .footer-col a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.5rem;
            font-size: 0.8125rem;
            display: flex;
            justify-content: space-between;
        }

        /* === RESPONSIVE === */
        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-sub {
                margin: 0 auto 2.5rem;
            }

            .hero-actions {
                justify-content: center;
            }

            .hero-visual {
                max-width: 600px;
                margin: 3rem auto 0;
            }

            .audience-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .product-panel.active {
                grid-template-columns: 1fr;
            }

            .security-grid,
            .testimonials-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .pricing-grid {
                grid-template-columns: 1fr;
                max-width: 420px;
                margin: 0 auto;
            }

            .order-grid {
                grid-template-columns: 1fr;
            }

            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .hero {
                padding: 8rem 0 4rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }

            .audience-grid,
            .security-grid,
            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .nav-menu a:not(.btn) {
                display: none;
            }

            .footer-grid {
                grid-template-columns: 1fr;
            }

            .footer-bottom {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
            }
        }

        /* === ANIMATIONS === */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-d1 {
            transition-delay: 0.1s;
        }

        .reveal-d2 {
            transition-delay: 0.2s;
        }

        .reveal-d3 {
            transition-delay: 0.3s;
        }
    </style>
</head>

<body>

    <!-- ═════════ NAV ═════════ -->
    <header class="nav">
        <div class="container">
            <a href="#" class="nav-brand">
                <span class="mark">H</span>
                {{ config('app.name') }}
            </a>
            <nav class="nav-menu">
                <a href="#solutions">Solutions</a>
                <a href="#products">Products</a>
                <a href="#security">Security</a>
                <a href="#pricing">Pricing</a>
                <a href="#order" class="btn btn-outline btn-sm">Contact Us</a>
                <a href="{{ url('/admin/login') }}" class="btn btn-primary btn-sm">Sign In</a>
            </nav>
        </div>
    </header>

    <!-- ═════════ HERO ═════════ -->
    <section class="hero">
        <div class="container">
            <div class="hero-grid">
                <div class="reveal">
                    <div class="hero-eyebrow"><span class="dot"></span> Core banking for cooperatives</div>
                    <h1>We build software for leading financial cooperatives</h1>
                    <p class="hero-sub">Purpose-built core banking software that helps SACCOs, microfinance banks, and
                        credit unions modernize operations, ensure compliance, and serve members better.</p>
                    <div class="hero-actions">
                        <a href="#order" class="btn btn-primary btn-lg">Request a Demo</a>
                        <a href="#products" class="btn btn-ghost btn-lg">Explore Products</a>
                    </div>
                </div>
                <div class="hero-visual reveal reveal-d2">
                    <img src="{{ asset('images/dashboard.png') }}"
                        alt="SACCO HSMS Dashboard — Branch performance analytics" width="640" height="640">
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ STATS ═════════ -->
    <div class="stats-bar">
        <div class="container">
            <div class="stats-grid reveal">
                <div>
                    <div class="stat-val">52+</div>
                    <div class="stat-label">Data models in platform</div>
                </div>
                <div>
                    <div class="stat-val">15</div>
                    <div class="stat-label">Modular business services</div>
                </div>
                <div>
                    <div class="stat-val">253</div>
                    <div class="stat-label">Automated test assertions</div>
                </div>
                <div>
                    <div class="stat-val">99.9%</div>
                    <div class="stat-label">Uptime guarantee</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═════════ AUDIENCE ═════════ -->
    <section class="section" id="solutions">
        <div class="container">
            <div class="section-header reveal">
                <p class="section-label">Built for cooperatives</p>
                <h2 class="section-title">Solutions built for the full spectrum of cooperative finance</h2>
                <p class="section-desc">Every cooperative is different. Creating real impact starts with understanding
                    each institution, its model, and the unique needs of its members.</p>
            </div>
            <div class="audience-grid">
                <div class="audience-card reveal">
                    <div class="emoji">🏦</div>
                    <h3>Village SACCOs</h3>
                    <p>Digitize member records, savings passbooks, and basic lending. Start your digital journey with
                        minimal setup.</p>
                </div>
                <div class="audience-card reveal reveal-d1">
                    <div class="emoji">🏢</div>
                    <h3>Urban SACCOs</h3>
                    <p>Scale operations with branches, mobile money integration, credit scoring, and multi-product
                        lending.</p>
                </div>
                <div class="audience-card reveal reveal-d2">
                    <div class="emoji">🏗️</div>
                    <h3>Microfinance Banks</h3>
                    <p>Full regulatory compliance with BOU returns, Basel III CAR/LCR, IFRS 9 ECL, and CRB integration.
                    </p>
                </div>
                <div class="audience-card reveal reveal-d3">
                    <div class="emoji">🌍</div>
                    <h3>Credit Unions</h3>
                    <p>Community-focused identity with modern member-centric experience, group lending, and democratic
                        governance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ PRODUCTS (Tabbed — SBS-style) ═════════ -->
    <section class="section section-alt" id="products">
        <div class="container">
            <div class="section-header reveal">
                <p class="section-label">Platform</p>
                <h2 class="section-title">World-class products, delivered with local expertise</h2>
                <p class="section-desc">A modular platform covering the entire cooperative banking lifecycle.</p>
            </div>

            <div class="products-tabs reveal" id="productTabs">
                <button class="active" data-tab="members">Member Management</button>
                <button data-tab="savings">Savings & Deposits</button>
                <button data-tab="lending">Lending</button>
                <button data-tab="accounting">Accounting</button>
                <button data-tab="compliance">Regulatory</button>
                <button data-tab="channels">Digital Channels</button>
            </div>

            <div class="product-panel active" id="panel-members">
                <div>
                    <h3>Complete Member Lifecycle</h3>
                    <p>From intake to exit — manage every stage of a member's journey with full KYC integration, share
                        management, and automated number generation.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> Automated member numbering (branch-year-seq)</li>
                        <li><span class="check">✓</span> KYC scoring with document verification</li>
                        <li><span class="check">✓</span> Share capital management & tracking</li>
                        <li><span class="check">✓</span> Group lending & member groups</li>
                        <li><span class="check">✓</span> Lifecycle transitions with full audit trail</li>
                        <li><span class="check">✓</span> Exit workflow with obligation checks</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">👥</div>
                    <div class="caption">12,854 active members managed</div>
                </div>
            </div>
            <div class="product-panel" id="panel-savings">
                <div>
                    <h3>Flexible Savings Products</h3>
                    <p>Create unlimited savings products with tiered interest rates, auto-accrual schedules, fixed
                        deposits, and mobile money deposits.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> Multi-product savings accounts</li>
                        <li><span class="check">✓</span> Tiered interest rate configuration</li>
                        <li><span class="check">✓</span> Automated daily/monthly interest accrual</li>
                        <li><span class="check">✓</span> Fixed deposits with early withdrawal penalties</li>
                        <li><span class="check">✓</span> Double-entry transaction journaling</li>
                        <li><span class="check">✓</span> Mobile money deposit integration</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">💰</div>
                    <div class="caption">UGX 45.2B total savings managed</div>
                </div>
            </div>
            <div class="product-panel" id="panel-lending">
                <div>
                    <h3>End-to-End Loan Lifecycle</h3>
                    <p>From application to collections — streamline every step with credit scoring, collateral tracking,
                        disbursement workflows, and arrears management.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> Multi-product loan configuration</li>
                        <li><span class="check">✓</span> Built-in credit scoring engine</li>
                        <li><span class="check">✓</span> Collateral management & valuation</li>
                        <li><span class="check">✓</span> Maker-checker approval workflow</li>
                        <li><span class="check">✓</span> Automated repayment scheduling</li>
                        <li><span class="check">✓</span> Arrears tracking & collection tools</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">📋</div>
                    <div class="caption">UGX 38.7B loan portfolio</div>
                </div>
            </div>
            <div class="product-panel" id="panel-accounting">
                <div>
                    <h3>Double-Entry General Ledger</h3>
                    <p>A fully integrated general ledger with chart of accounts, journal entries, period controls, and
                        automated trial balance computation.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> Hierarchical chart of accounts</li>
                        <li><span class="check">✓</span> Multi-type journal entries</li>
                        <li><span class="check">✓</span> Enforced debit-credit balance</li>
                        <li><span class="check">✓</span> Accounting period controls</li>
                        <li><span class="check">✓</span> Automated trial balance</li>
                        <li><span class="check">✓</span> Income statement & balance sheet</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">📊</div>
                    <div class="caption">Real-time financial reporting</div>
                </div>
            </div>
            <div class="product-panel" id="panel-compliance">
                <div>
                    <h3>Regulatory Compliance Suite</h3>
                    <p>Meet Bank of Uganda requirements with built-in AML screening, CRB integration, Basel III capital
                        adequacy, and IFRS 9 expected credit loss models.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> AML/CFT screening & STR filing</li>
                        <li><span class="check">✓</span> Credit Reference Bureau integration</li>
                        <li><span class="check">✓</span> Basel III CAR & LCR computation</li>
                        <li><span class="check">✓</span> IFRS 9 ECL staging & provisioning</li>
                        <li><span class="check">✓</span> BOU regulatory returns generation</li>
                        <li><span class="check">✓</span> Full audit trail for examiners</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">🏛️</div>
                    <div class="caption">BOU compliant from day one</div>
                </div>
            </div>
            <div class="product-panel" id="panel-channels">
                <div>
                    <h3>Digital Banking Channels</h3>
                    <p>Meet members where they are with mobile money integration, USSD banking, agent networks, and
                        self-service member portals.</p>
                    <ul class="product-features">
                        <li><span class="check">✓</span> Mobile money (MTN, Airtel) integration</li>
                        <li><span class="check">✓</span> USSD self-service banking</li>
                        <li><span class="check">✓</span> Agent banking network management</li>
                        <li><span class="check">✓</span> Member self-service portal</li>
                        <li><span class="check">✓</span> SMS & email notifications</li>
                        <li><span class="check">✓</span> Offline-capable branch operations</li>
                    </ul>
                </div>
                <div class="product-visual">
                    <div class="big-emoji">📱</div>
                    <div class="caption">Mobile-first member experience</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ SECURITY ═════════ -->
    <section class="section section-dark" id="security">
        <div class="container">
            <div class="section-header reveal">
                <p class="section-label">Enterprise Security</p>
                <h2 class="section-title">Bank-grade security, built in from day one</h2>
                <p class="section-desc">15 security layers protect your members' data and your cooperative's reputation.
                </p>
            </div>
            <div class="security-grid reveal">
                <div class="sec-card">
                    <div class="ico">🔐</div>
                    <h3>Account Lockout</h3>
                    <p>Automatic lockout after failed login attempts with configurable thresholds and cooldown periods.
                    </p>
                </div>
                <div class="sec-card">
                    <div class="ico">📝</div>
                    <h3>Full Audit Trail</h3>
                    <p>Every create, update, and delete is logged with user, IP, timestamp, and old/new values.</p>
                </div>
                <div class="sec-card">
                    <div class="ico">✅</div>
                    <h3>Maker-Checker</h3>
                    <p>Dual approval workflow for critical operations — loans, transfers, and configuration changes.</p>
                </div>
                <div class="sec-card">
                    <div class="ico">🔑</div>
                    <h3>Password Policy</h3>
                    <p>90-day rotation, first-login change enforcement, and strong password validation rules.</p>
                </div>
                <div class="sec-card">
                    <div class="ico">🛡️</div>
                    <h3>Data Encryption</h3>
                    <p>AES-256 encrypted backups, sensitive field encryption at rest, and encrypted sessions.</p>
                </div>
                <div class="sec-card">
                    <div class="ico">🌐</div>
                    <h3>OWASP Headers</h3>
                    <p>CSP, HSTS, X-Frame-Options, and XSS protection headers on every response.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ TESTIMONIALS ═════════ -->
    <section class="section">
        <div class="container">
            <div class="section-header reveal">
                <p class="section-label">Trusted by cooperatives</p>
                <h2 class="section-title">Hear it from our clients</h2>
            </div>
            <div class="testimonials-grid reveal">
                <div class="test-card">
                    <blockquote>"We onboarded 2,400 members in the first month. The KYC scoring alone saved us hundreds
                        of manual verification hours."</blockquote>
                    <cite><span class="name">Sarah Namuli</span><span class="role">Operations Manager, Kampala Teachers
                            SACCO</span></cite>
                </div>
                <div class="test-card">
                    <blockquote>"The maker-checker workflow and audit trails gave us the confidence to pass our BOU
                        examination with zero findings."</blockquote>
                    <cite><span class="name">James Okello</span><span class="role">CEO, Northern Uganda Credit
                            Union</span></cite>
                </div>
                <div class="test-card">
                    <blockquote>"Moving from spreadsheets to HSMS transformed our loan processing from 5 days to under
                        24 hours. Members are thrilled."</blockquote>
                    <cite><span class="name">Grace Atim</span><span class="role">Board Chair, Gulu Farmers
                            SACCO</span></cite>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ PRICING ═════════ -->
    <section class="section section-alt" id="pricing">
        <div class="container">
            <div class="section-header reveal">
                <p class="section-label">Simple Pricing</p>
                <h2 class="section-title">Plans that grow with your cooperative</h2>
                <p class="section-desc">Start free, upgrade when you're ready. No hidden fees, no long-term contracts.
                </p>
            </div>
            <div class="price-toggle reveal" id="billingToggle">
                <button class="active" data-cycle="monthly">Monthly</button>
                <button data-cycle="annual">Annual <span
                        style="color:var(--green-500);font-size:0.75rem;margin-left:4px;">-20%</span></button>
            </div>
            <div class="pricing-grid reveal">
                <div class="price-card">
                    <p class="price-tier">Starter</p>
                    <p class="price-amount" data-monthly="49" data-annual="39">$49<small>/mo</small></p>
                    <p class="price-desc">For village SACCOs starting digital.</p>
                    <ul class="price-list">
                        <li><span class="ck">✓</span> Up to 500 members</li>
                        <li><span class="ck">✓</span> Member management</li>
                        <li><span class="ck">✓</span> Savings accounts</li>
                        <li><span class="ck">✓</span> Basic loan processing</li>
                        <li><span class="ck">✓</span> Transaction reports</li>
                        <li><span class="ck">✓</span> Email support</li>
                    </ul>
                    <a href="#order" class="btn btn-outline btn-md" onclick="selectPlan('starter')">Get Started</a>
                </div>
                <div class="price-card featured">
                    <div class="price-badge">MOST POPULAR</div>
                    <p class="price-tier">Growth</p>
                    <p class="price-amount" data-monthly="149" data-annual="119">$149<small>/mo</small></p>
                    <p class="price-desc">For established SACCOs scaling operations.</p>
                    <ul class="price-list">
                        <li><span class="ck">✓</span> Up to 5,000 members</li>
                        <li><span class="ck">✓</span> Everything in Starter</li>
                        <li><span class="ck">✓</span> General Ledger</li>
                        <li><span class="ck">✓</span> Credit scoring engine</li>
                        <li><span class="ck">✓</span> Branch operations</li>
                        <li><span class="ck">✓</span> Mobile money integration</li>
                        <li><span class="ck">✓</span> Priority support</li>
                    </ul>
                    <a href="#order" class="btn btn-primary btn-md" onclick="selectPlan('growth')">Get Started</a>
                </div>
                <div class="price-card">
                    <p class="price-tier">Enterprise</p>
                    <p class="price-amount" data-monthly="399" data-annual="319">$399<small>/mo</small></p>
                    <p class="price-desc">For MFBs and large cooperatives.</p>
                    <ul class="price-list">
                        <li><span class="ck">✓</span> Unlimited members</li>
                        <li><span class="ck">✓</span> Everything in Growth</li>
                        <li><span class="ck">✓</span> IFRS 9 ECL computation</li>
                        <li><span class="ck">✓</span> Basel III CAR/LCR</li>
                        <li><span class="ck">✓</span> CRB integration</li>
                        <li><span class="ck">✓</span> Custom domain</li>
                        <li><span class="ck">✓</span> Dedicated account manager</li>
                        <li><span class="ck">✓</span> 99.9% SLA</li>
                    </ul>
                    <a href="#order" class="btn btn-outline btn-md" onclick="selectPlan('enterprise')">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ CTA BANNER ═════════ -->
    <div class="section" style="padding-bottom:0">
        <div class="cta-banner reveal">
            <h2>Success is more than finding the right software.<br>It's finding the right partner.</h2>
            <p>Our mission is to contribute to a vibrant cooperative sector — accessible to everyone, everywhere. Let us
                help you navigate the digital transformation.</p>
            <div class="cta-actions">
                <a href="#order" class="btn btn-white btn-lg">Request a Demo</a>
                <a href="#pricing" class="btn btn-ghost btn-lg">View Pricing</a>
            </div>
        </div>
    </div>

    <!-- ═════════ ORDER / CONTACT FORM ═════════ -->
    <section class="section order-section" id="order">
        <div class="container">
            <div class="order-grid">
                <div class="reveal">
                    <p class="section-label">Get Started</p>
                    <h2 class="section-title">Help us better understand your needs</h2>
                    <p class="section-desc" style="margin-bottom:2rem;">Because in this industry, excellence lies in the
                        details. Our team will get back to you within 24 hours with a carefully tailored solution.</p>
                    <div
                        style="background:var(--white);border:1px solid var(--gray-200);border-radius:12px;padding:1.5rem;">
                        <p style="font-weight:700;margin-bottom:0.75rem;color:var(--gray-900);">What happens next?</p>
                        <div style="display:flex;flex-direction:column;gap:0.75rem;">
                            <div
                                style="display:flex;gap:0.75rem;align-items:flex-start;font-size:0.875rem;color:var(--gray-600);">
                                <span
                                    style="background:var(--blue-50);color:var(--blue-600);font-weight:700;padding:0.125rem 0.5rem;border-radius:6px;font-size:0.75rem;">1</span>
                                We receive your request and verify details
                            </div>
                            <div
                                style="display:flex;gap:0.75rem;align-items:flex-start;font-size:0.875rem;color:var(--gray-600);">
                                <span
                                    style="background:var(--blue-50);color:var(--blue-600);font-weight:700;padding:0.125rem 0.5rem;border-radius:6px;font-size:0.75rem;">2</span>
                                Your dedicated environment is provisioned
                            </div>
                            <div
                                style="display:flex;gap:0.75rem;align-items:flex-start;font-size:0.875rem;color:var(--gray-600);">
                                <span
                                    style="background:var(--blue-50);color:var(--blue-600);font-weight:700;padding:0.125rem 0.5rem;border-radius:6px;font-size:0.75rem;">3</span>
                                Login credentials sent to your email
                            </div>
                            <div
                                style="display:flex;gap:0.75rem;align-items:flex-start;font-size:0.875rem;color:var(--gray-600);">
                                <span
                                    style="background:var(--blue-50);color:var(--blue-600);font-weight:700;padding:0.125rem 0.5rem;border-radius:6px;font-size:0.75rem;">4</span>
                                Our team helps onboard your first members
                            </div>
                        </div>
                    </div>
                </div>
                <div class="reveal reveal-d2">
                    @if(session('success'))
                        <div class="alert-success">✅ {{ session('success') }}</div>
                    @endif
                    <form method="POST" action="{{ route('order.store') }}">
                        @csrf
                        <input type="hidden" name="billing_cycle" id="billingInput" value="monthly">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="organization_name">SACCO / Organization *</label>
                                <input type="text" id="organization_name" name="organization_name"
                                    value="{{ old('organization_name') }}" required
                                    placeholder="e.g. Kampala Teachers SACCO">
                                @error('organization_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="contact_person">Contact Person *</label>
                                <input type="text" id="contact_person" name="contact_person"
                                    value="{{ old('contact_person') }}" required placeholder="Full name">
                                @error('contact_person')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                    placeholder="you@sacco.co.ug">
                                @error('email')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number *</label>
                                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                                    placeholder="+256 7XX XXX XXX">
                                @error('phone')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="plan_tier">Plan *</label>
                                <select id="plan_tier" name="plan_tier" required>
                                    <option value="">Select a plan</option>
                                    <option value="starter" {{ old('plan_tier') == 'starter' ? 'selected' : '' }}>Starter —
                                        $49/mo</option>
                                    <option value="growth" {{ old('plan_tier') == 'growth' ? 'selected' : '' }}>Growth —
                                        $149/mo</option>
                                    <option value="enterprise" {{ old('plan_tier') == 'enterprise' ? 'selected' : '' }}>
                                        Enterprise — $399/mo</option>
                                </select>
                                @error('plan_tier')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-group">
                                <label for="member_count">Estimated Members</label>
                                <input type="number" id="member_count" name="member_count"
                                    value="{{ old('member_count') }}" placeholder="e.g. 500" min="1">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="message">Additional Notes</label>
                            <textarea id="message" name="message"
                                placeholder="Tell us about your specific requirements…">{{ old('message') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg" style="width:100%">Submit Request
                            →</button>
                        <p style="font-size:0.8125rem;color:var(--gray-400);text-align:center;margin-top:0.75rem;">Free
                            14-day trial · No credit card · Cancel anytime</p>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ═════════ FOOTER ═════════ -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="footer-brand">⬡ {{ config('app.name') }}</div>
                    <p class="footer-brand-desc">Enterprise-grade core banking software purpose-built for SACCOs and
                        microfinance institutions across East Africa.</p>
                </div>
                <div class="footer-col">
                    <h4>Products</h4>
                    <a href="#products">Member Management</a>
                    <a href="#products">Savings & Deposits</a>
                    <a href="#products">Lending</a>
                    <a href="#products">General Ledger</a>
                    <a href="#products">Compliance</a>
                </div>
                <div class="footer-col">
                    <h4>Company</h4>
                    <a href="#solutions">About</a>
                    <a href="#security">Security</a>
                    <a href="#pricing">Pricing</a>
                    <a href="#order">Contact</a>
                </div>
                <div class="footer-col">
                    <h4>Access</h4>
                    <a href="{{ url('/admin/login') }}">Admin Panel</a>
                    <a href="#order">Request Demo</a>
                    <a href="#order">Partner With Us</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</span>
                <span>Built with Laravel, Filament & Livewire</span>
            </div>
        </div>
    </footer>

    <script>
    // Scroll reveal
    const obs = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); } });
    }, { threshold: 0.08 });
    document.querySelectorAll('.reveal').forEach(el => obs.observe(el));

    // Product tabs
    document.querySelectorAll('#productTabs button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#productTabs button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.product-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panel-' + btn.dataset.tab).classList.add('active');
        });
    });

    // Billing toggle
    document.querySelectorAll('#billingToggle button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#billingToggle button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const cycle = btn.dataset.cycle;
            document.getElementById('billingInput').value = cycle;
            document.querySelectorAll('.price-amount').forEach(el => {
                el.innerHTML = '$' + el.dataset[cycle] + '<small>/mo</small>';
            });
        });
    });

    // Plan selector
    function selectPlan(tier) { document.getElementById('plan_tier').value = tier; }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(a => {
        a.addEventListener('click', e => {
            const t = document.querySelector(a.getAttribute('href'));
            if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
        });
    });
    </script>
</body>

</html>