<?php
/**
 * Landing page — the SaaS homepage.
 * Self-contained document: no layout wrapper needed.
 *
 * This is the first thing anyone sees. It must be premium.
 */
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VoxelSwarm — Your website, live in 30 seconds</title>
  <meta name="description" content="Enter a name and email. Get a live website at its own subdomain in under 30 seconds. AI builds it. You own the files.">
  <link rel="stylesheet" href="/fonts/inter/inter.css">
  <link rel="stylesheet" href="/build/swarm.css">
  <script>
    (function() {
      var t = localStorage.getItem('swarm-theme');
      if (t) document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <style>
    /* ── Design tokens ── */
    :root {
      --sw-bg: #FAFAFA;
      --sw-bg-deep: #F4F4F5;
      --sw-surface: #FFFFFF;
      --sw-surface-alt: #F4F4F5;
      --sw-border: #E4E4E7;
      --sw-text: #09090B;
      --sw-text-secondary: #3F3F46;
      --sw-text-muted: #71717A;
      --sw-accent: #EA580C;
      --sw-accent-hover: #C2410C;
      --sw-accent-soft: rgba(234, 88, 12, 0.08);
      --sw-accent-glow: rgba(234, 88, 12, 0.15);
    }
    [data-theme="dark"] {
      --sw-bg: #09090B;
      --sw-bg-deep: #050506;
      --sw-surface: rgba(17, 17, 19, 0.7);
      --sw-surface-alt: #111113;
      --sw-border: rgba(39, 39, 42, 0.6);
      --sw-text: #FAFAFA;
      --sw-text-secondary: #A1A1AA;
      --sw-text-muted: #52525B;
      --sw-accent-soft: rgba(234, 88, 12, 0.1);
      --sw-accent-glow: rgba(234, 88, 12, 0.2);
    }

    /* ── Reset ── */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    a { color: var(--sw-accent); text-decoration: none; }
    a:hover { text-decoration: underline; }

    /* ── Body ── */
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--sw-bg);
      color: var(--sw-text);
      line-height: 1.6;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
      overflow-x: hidden;
    }

    /* ── Nav ── */
    .sw-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 50;
      padding: 0 32px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: transparent;
      transition: background 0.3s ease, border-color 0.3s ease, backdrop-filter 0.3s ease;
    }
    .sw-nav.scrolled {
      background: rgba(9, 9, 11, 0.8);
      backdrop-filter: blur(20px) saturate(180%);
      -webkit-backdrop-filter: blur(20px) saturate(180%);
      border-bottom: 1px solid var(--sw-border);
    }
    [data-theme="light"] .sw-nav.scrolled {
      background: rgba(250, 250, 250, 0.85);
    }
    .sw-nav-logo {
      display: flex;
      align-items: center;
      gap: 9px;
    }
    .sw-nav-logo svg {
      width: 22px;
      height: 22px;
      color: var(--sw-accent);
    }
    .sw-nav-logo span {
      font-size: 15px;
      font-weight: 700;
      letter-spacing: -0.04em;
      color: var(--sw-text);
    }
    .sw-nav-links {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .sw-nav-link {
      padding: 7px 14px;
      font-size: 13px;
      font-weight: 500;
      color: var(--sw-text-muted);
      border-radius: 8px;
      transition: color 0.15s, background 0.15s;
      text-decoration: none;
    }
    .sw-nav-link:hover {
      color: var(--sw-text);
      background: var(--sw-accent-soft);
      text-decoration: none;
    }
    .sw-nav-cta {
      padding: 7px 16px;
      font-size: 13px;
      font-weight: 600;
      color: #FFF;
      background: var(--sw-accent);
      border-radius: 8px;
      transition: background 0.15s, box-shadow 0.15s;
      text-decoration: none;
    }
    .sw-nav-cta:hover {
      background: var(--sw-accent-hover);
      box-shadow: 0 2px 12px rgba(234, 88, 12, 0.3);
      text-decoration: none;
    }

    /* ── Hero ── */
    .sw-hero {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 120px 24px 80px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .sw-hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        radial-gradient(circle at 50% 30%, var(--sw-accent-glow) 0%, transparent 60%),
        radial-gradient(circle at 20% 80%, rgba(99, 102, 241, 0.06) 0%, transparent 40%),
        radial-gradient(circle at 80% 70%, rgba(234, 88, 12, 0.05) 0%, transparent 40%);
      pointer-events: none;
    }
    /* Dot grid pattern */
    .sw-hero::after {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle, var(--sw-text-muted) 0.5px, transparent 0.5px);
      background-size: 32px 32px;
      opacity: 0.12;
      pointer-events: none;
      mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
      -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 70%);
    }

    /* ── Hero Voxel ── */
    .sw-hero-voxel {
      width: 80px;
      height: 80px;
      color: var(--sw-accent);
      margin-bottom: 40px;
      filter: drop-shadow(0 8px 32px rgba(234, 88, 12, 0.4));
      animation: heroFloat 4s ease-in-out infinite;
      position: relative;
      z-index: 1;
    }
    @keyframes heroFloat {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-12px); }
    }
    .voxel-top  { fill: currentColor; opacity: 1; }
    .voxel-left { fill: currentColor; opacity: 0.65; }
    .voxel-right{ fill: currentColor; opacity: 0.35; }

    /* ── Hero text ── */
    .sw-hero-headline {
      font-size: clamp(40px, 7vw, 72px);
      font-weight: 800;
      letter-spacing: -0.05em;
      line-height: 1.05;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
    }
    .sw-hero-headline .accent {
      background: linear-gradient(135deg, #EA580C 0%, #F97316 50%, #EA580C 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .sw-hero-sub {
      font-size: clamp(16px, 2.2vw, 19px);
      color: var(--sw-text-secondary);
      max-width: 560px;
      line-height: 1.7;
      margin-bottom: 40px;
      position: relative;
      z-index: 1;
    }
    .sw-hero-actions {
      display: flex;
      gap: 12px;
      position: relative;
      z-index: 1;
    }

    /* ── Buttons ── */
    .sw-btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      font-size: 15px;
      font-weight: 600;
      font-family: inherit;
      color: #FFF;
      background: var(--sw-accent);
      border: none;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 4px 16px rgba(234, 88, 12, 0.3);
      text-decoration: none;
    }
    .sw-btn-primary:hover {
      background: var(--sw-accent-hover);
      box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 8px 24px rgba(234, 88, 12, 0.4);
      transform: translateY(-1px);
      text-decoration: none;
    }
    .sw-btn-primary:active { transform: translateY(0); }
    /* ── Sections ── */
    .sw-section {
      padding: 100px 24px;
      max-width: 1100px;
      margin: 0 auto;
    }
    .sw-section-label {
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--sw-accent);
      margin-bottom: 12px;
      text-align: center;
    }
    .sw-section-title {
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 800;
      letter-spacing: -0.04em;
      line-height: 1.15;
      text-align: center;
      margin-bottom: 16px;
    }
    .sw-section-sub {
      font-size: 16px;
      color: var(--sw-text-secondary);
      text-align: center;
      max-width: 520px;
      margin: 0 auto 56px;
      line-height: 1.7;
    }

    /* ── Value cards ── */
    .sw-cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
    }
    .sw-card {
      background: var(--sw-surface);
      border: 1px solid var(--sw-border);
      border-radius: 16px;
      padding: 32px 28px;
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
    }
    .sw-card:hover {
      border-color: rgba(234, 88, 12, 0.3);
      transform: translateY(-2px);
      box-shadow: 0 8px 32px rgba(0,0,0,0.08), 0 0 0 1px rgba(234, 88, 12, 0.1);
    }
    [data-theme="dark"] .sw-card:hover {
      box-shadow: 0 8px 32px rgba(0,0,0,0.3), 0 0 0 1px rgba(234, 88, 12, 0.15);
    }
    .sw-card-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--sw-accent-soft);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 18px;
      color: var(--sw-accent);
    }
    .sw-card-icon svg {
      width: 20px;
      height: 20px;
    }
    .sw-card h3 {
      font-size: 17px;
      font-weight: 700;
      letter-spacing: -0.02em;
      margin-bottom: 8px;
    }
    .sw-card p {
      font-size: 14px;
      color: var(--sw-text-secondary);
      line-height: 1.65;
    }

    /* ── Steps ── */
    .sw-steps {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0;
      position: relative;
    }
    .sw-steps::before {
      content: '';
      position: absolute;
      top: 28px;
      left: 15%;
      right: 15%;
      height: 2px;
      background: linear-gradient(90deg, var(--sw-border) 0%, var(--sw-accent) 50%, var(--sw-border) 100%);
      z-index: 0;
    }
    .sw-step {
      text-align: center;
      position: relative;
      z-index: 1;
    }
    .sw-step-num {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      background: var(--sw-surface-alt);
      border: 2px solid var(--sw-border);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 18px;
      font-weight: 800;
      color: var(--sw-accent);
      transition: all 0.3s;
    }
    .sw-step:hover .sw-step-num {
      border-color: var(--sw-accent);
      background: var(--sw-accent-soft);
      transform: scale(1.08);
    }
    .sw-step h4 {
      font-size: 14px;
      font-weight: 700;
      letter-spacing: -0.01em;
      margin-bottom: 4px;
    }
    .sw-step p {
      font-size: 13px;
      color: var(--sw-text-muted);
      max-width: 180px;
      margin: 0 auto;
      line-height: 1.5;
    }

    /* ── Operator section ── */
    .sw-operator {
      background: var(--sw-surface-alt);
      border-top: 1px solid var(--sw-border);
      border-bottom: 1px solid var(--sw-border);
    }
    [data-theme="dark"] .sw-operator {
      background: rgba(17, 17, 19, 0.4);
    }
    .sw-operator-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      align-items: center;
    }
    .sw-operator-text h3 {
      font-size: clamp(24px, 3.5vw, 32px);
      font-weight: 800;
      letter-spacing: -0.04em;
      margin-bottom: 16px;
    }
    .sw-operator-text p {
      font-size: 15px;
      color: var(--sw-text-secondary);
      line-height: 1.7;
      margin-bottom: 24px;
    }
    .sw-feature-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .sw-feature-list li {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      font-weight: 500;
      color: var(--sw-text-secondary);
    }
    .sw-feature-list li svg {
      width: 16px;
      height: 16px;
      color: var(--sw-accent);
      flex-shrink: 0;
    }
    .sw-operator-visual {
      background: var(--sw-bg);
      border: 1px solid var(--sw-border);
      border-radius: 16px;
      padding: 28px;
      font-family: 'SF Mono', 'Fira Code', 'Cascadia Code', monospace;
      font-size: 13px;
      line-height: 1.8;
      color: var(--sw-text-muted);
      overflow: hidden;
    }
    .sw-operator-visual .cmd { color: var(--sw-accent); }
    .sw-operator-visual .dim { opacity: 0.5; }
    .sw-operator-visual .success { color: #16A34A; }
    .sw-operator-visual .info { color: var(--sw-text-secondary); }

    /* ── CTA ── */
    .sw-cta {
      text-align: center;
      padding: 120px 24px;
      position: relative;
    }
    .sw-cta::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle at 50% 80%, var(--sw-accent-glow) 0%, transparent 50%);
      pointer-events: none;
    }
    .sw-cta-voxel {
      width: 48px;
      height: 48px;
      color: var(--sw-accent);
      margin: 0 auto 24px;
      filter: drop-shadow(0 4px 16px rgba(234, 88, 12, 0.35));
      animation: heroFloat 4s ease-in-out infinite;
    }
    .sw-cta h2 {
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 800;
      letter-spacing: -0.04em;
      margin-bottom: 12px;
      position: relative;
    }
    .sw-cta p {
      font-size: 16px;
      color: var(--sw-text-secondary);
      margin-bottom: 32px;
      position: relative;
    }

    /* ── Footer ── */
    .sw-footer {
      padding: 24px 32px;
      text-align: center;
      border-top: 1px solid var(--sw-border);
      font-size: 12px;
      color: var(--sw-text-muted);
      letter-spacing: -0.01em;
    }
    .sw-footer a { color: var(--sw-text-muted); }
    .sw-footer a:hover { color: var(--sw-accent); }

    /* ── Theme toggle ── */
    .sw-theme-toggle {
      width: 34px;
      height: 34px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: transparent;
      border: 1px solid var(--sw-border);
      border-radius: 8px;
      cursor: pointer;
      color: var(--sw-text-muted);
      transition: all 0.15s;
    }
    .sw-theme-toggle:hover { color: var(--sw-text); border-color: var(--sw-text-muted); }

    /* ── Scroll animations ── */
    .sw-reveal {
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.6s ease, transform 0.6s ease;
    }
    .sw-reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sw-cards { grid-template-columns: 1fr; gap: 16px; }
      .sw-steps { grid-template-columns: repeat(2, 1fr); gap: 32px; }
      .sw-steps::before { display: none; }
      .sw-operator-grid { grid-template-columns: 1fr; }
      .sw-hero-actions { flex-direction: column; width: 100%; max-width: 320px; }
      .sw-hero-actions a { width: 100%; justify-content: center; }
      .sw-nav-links .sw-nav-link { display: none; }
    }
  </style>
</head>
<body>

  <!-- ── Navigation ── -->
  <nav class="sw-nav" id="nav">
    <a href="/" class="sw-nav-logo">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path class="voxel-top" d="M12 3L20 7.5L12 12L4 7.5Z" />
        <path class="voxel-left" d="M4 7.5L12 12L12 21L4 16.5Z" />
        <path class="voxel-right" d="M20 7.5L12 12L12 21L20 16.5Z" />
      </svg>
      <span>VoxelSwarm</span>
    </a>
    <div class="sw-nav-links">
      <a href="/operator/login" class="sw-nav-link">Operator</a>
      <button class="sw-theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
        <svg id="theme-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
        </svg>
      </button>
      <?php if ($signupsEnabled): ?>
        <a href="/signup" class="sw-nav-cta">Get Started</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- ── Hero ── -->
  <section class="sw-hero">
    <svg class="sw-hero-voxel" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path class="voxel-top" d="M12 3L20 7.5L12 12L4 7.5Z" />
      <path class="voxel-left" d="M4 7.5L12 12L12 21L4 16.5Z" />
      <path class="voxel-right" d="M20 7.5L12 12L12 21L20 16.5Z" />
    </svg>

    <h1 class="sw-hero-headline">
      Name. Email.<br><span class="accent">Website.</span>
    </h1>

    <p class="sw-hero-sub">
      Enter your business name. Thirty seconds later, AI builds your website
      at its own subdomain. No code. No hosting. No waiting.
    </p>

    <div class="sw-hero-actions">
      <?php if ($signupsEnabled): ?>
        <a href="/signup" class="sw-btn-primary">
          Create Your Workspace
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      <?php else: ?>
        <span class="sw-btn-primary" style="opacity:0.6;cursor:default;">Coming Soon</span>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── Value Props ── -->
  <section class="sw-section">
    <div class="sw-reveal">
      <div class="sw-section-label">Why VoxelSwarm</div>
      <h2 class="sw-section-title">Everything between "I signed up"<br>and "I have a website" — gone.</h2>
      <p class="sw-section-sub">
        VoxelSite builds the website. VoxelSwarm puts it in front of the world.
      </p>
    </div>

    <div class="sw-cards sw-reveal">
      <!-- Card 1 -->
      <div class="sw-card">
        <div class="sw-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <h3>30 seconds to live</h3>
        <p>Submit your name and email. Your subdomain is provisioned, VoxelSite is deployed, and your workspace is ready — all while you watch.</p>
      </div>
      <!-- Card 2 -->
      <div class="sw-card">
        <div class="sw-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m17.5 7.5-3 3"/><path d="M20 12h-4"/><path d="m17.5 16.5-3-3"/><path d="M12 18v4"/><path d="m6.5 16.5 3-3"/><path d="M4 12h4"/><path d="m6.5 7.5 3 3"/></svg>
        </div>
        <h3>AI builds. You direct.</h3>
        <p>Describe your business in plain language. VoxelSite generates pages, copy, and layout in real time. Change anything with a message.</p>
      </div>
      <!-- Card 3 -->
      <div class="sw-card">
        <div class="sw-card-icon">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <h3>Your files. Your rules.</h3>
        <p>Every site is standard HTML, CSS, PHP, and JavaScript. Download it. Move it to any host. No proprietary format. No lock-in, ever.</p>
      </div>
    </div>
  </section>

  <!-- ── How It Works ── -->
  <section class="sw-section">
    <div class="sw-reveal">
      <div class="sw-section-label">How it works</div>
      <h2 class="sw-section-title">Four steps. One URL.</h2>
      <p class="sw-section-sub">
        From signup to watching your website appear.
      </p>
    </div>

    <div class="sw-steps sw-reveal">
      <div class="sw-step">
        <div class="sw-step-num">1</div>
        <h4>Sign up</h4>
        <p>Enter your business name and email address.</p>
      </div>
      <div class="sw-step">
        <div class="sw-step-num">2</div>
        <h4>Get your URL</h4>
        <p>Your subdomain goes live in under 30 seconds.</p>
      </div>
      <div class="sw-step">
        <div class="sw-step-num">3</div>
        <h4>Run the wizard</h4>
        <p>Four quick steps: language, AI provider, business info, design.</p>
      </div>
      <div class="sw-step">
        <div class="sw-step-num">4</div>
        <h4>Watch it build</h4>
        <p>AI generates your pages, copy, and layout. Watch it happen live.</p>
      </div>
    </div>
  </section>

  <!-- ── For Operators ── -->
  <section class="sw-operator">
    <div class="sw-section" style="padding-top:80px;padding-bottom:80px;">
      <div class="sw-operator-grid sw-reveal">
        <div class="sw-operator-text">
          <div class="sw-section-label" style="text-align:left;">For operators</div>
          <h3>Run VoxelSite as a service</h3>
          <p>
            Install VoxelSwarm on any VPS. Provision instances for clients, students, or your agency portfolio.
            Plain PHP, SQLite, two Composer dependencies. Ships as a ZIP.
          </p>
          <ul class="sw-feature-list">
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Dashboard with instance management
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Pause, resume, and delete instances
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Nginx, Forge, cPanel, or Plesk adapters
            </li>
            <li>
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Complete instance isolation — separate DB, directory, subdomain
            </li>
          </ul>
        </div>

        <div class="sw-operator-visual">
          <div class="dim"># Provision a new instance</div>
          <div><span class="cmd">$</span> <span class="info">curl -X POST /signup</span></div>
          <div class="dim">  → name: "Sable & Lune Perfume"</div>
          <div class="dim">  → email: "hello@sable-lune.com"</div>
          <br>
          <div><span class="success">✓</span> <span class="info">Instance created: sable-lune</span></div>
          <div><span class="success">✓</span> <span class="info">Template copied (420ms)</span></div>
          <div><span class="success">✓</span> <span class="info">Subdomain configured (1.2s)</span></div>
          <div><span class="success">✓</span> <span class="info">Health check passed</span></div>
          <br>
          <div class="dim"># Live at:</div>
          <div><span class="cmd">→</span> <span class="info">https://sable-lune.voxelsite.com</span></div>
          <div class="dim" style="margin-top:8px;">Total: 4.8s</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ── CTA ── -->
  <section class="sw-cta sw-reveal">
    <svg class="sw-cta-voxel" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      <path class="voxel-top" d="M12 3L20 7.5L12 12L4 7.5Z" />
      <path class="voxel-left" d="M4 7.5L12 12L12 21L4 16.5Z" />
      <path class="voxel-right" d="M20 7.5L12 12L12 21L20 16.5Z" />
    </svg>
    <h2>Ready to build?</h2>
    <p>Your website is one prompt away.</p>
    <div style="position:relative;">
      <?php if ($signupsEnabled): ?>
        <a href="/signup" class="sw-btn-primary">
          Create Your Workspace
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
      <?php else: ?>
        <span class="sw-btn-primary" style="opacity:0.6;cursor:default;">Coming Soon</span>
      <?php endif; ?>
    </div>
  </section>

  <!-- ── Footer ── -->
  <footer class="sw-footer">
    VoxelSwarm v<?= SWARM_VERSION ?> · Plain PHP. SQLite. Your files. ·
    <a href="/operator/login">Operator Login</a>
  </footer>

  <!-- ── Scripts ── -->
  <script>
    // Nav scroll effect
    const nav = document.getElementById('nav');
    window.addEventListener('scroll', () => {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });

    // Theme toggle
    function toggleTheme() {
      const html = document.documentElement;
      const next = (html.getAttribute('data-theme') || 'dark') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('swarm-theme', next);
      updateIcon(next);
    }
    function updateIcon(theme) {
      const icon = document.getElementById('theme-icon');
      if (theme === 'dark') {
        icon.innerHTML = '<circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>';
      } else {
        icon.innerHTML = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
      }
    }
    updateIcon(document.documentElement.getAttribute('data-theme') || 'dark');

    // Scroll reveal
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.sw-reveal').forEach(el => observer.observe(el));
  </script>
</body>
</html>
