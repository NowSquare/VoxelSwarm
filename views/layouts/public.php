<?php
/**
 * Public layout — used by signup, status, login, and 404 pages.
 * Receives $content from Response::view()
 */
$pageTitle = $pageTitle ?? 'VoxelSwarm';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="/fonts/inter/inter.css">
  <link rel="stylesheet" href="/build/swarm.css">
  <script>
    (function() {
      var t = localStorage.getItem('swarm-theme');
      if (t) document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  <style>
    /* ── Tokens ── */
    :root {
      --sw-bg: #F4F4F5;
      --sw-surface: #FFFFFF;
      --sw-border: #E4E4E7;
      --sw-text: #09090B;
      --sw-text-secondary: #52525B;
      --sw-text-muted: #71717A;
      --sw-accent: #EA580C;
      --sw-accent-hover: #C2410C;
      --sw-accent-glow: rgba(234, 88, 12, 0.12);
      --sw-success: #16A34A;
      --sw-warning: #D97706;
      --sw-error: #DC2626;
    }
    [data-theme="dark"] {
      --sw-bg: #09090B;
      --sw-surface: #111113;
      --sw-border: #27272A;
      --sw-text: #FAFAFA;
      --sw-text-secondary: #A1A1AA;
      --sw-text-muted: #52525B;
      --sw-accent-glow: rgba(234, 88, 12, 0.15);
    }

    /* ── Reset ── */
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    a { color: var(--sw-accent); text-decoration: none; }
    a:hover { text-decoration: underline; }

    /* ── Body ── */
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background: var(--sw-bg);
      color: var(--sw-text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      line-height: 1.5;
      background-image:
        radial-gradient(circle at 15% 85%, var(--sw-accent-glow) 0%, transparent 50%),
        radial-gradient(circle at 85% 15%, rgba(99, 102, 241, 0.06) 0%, transparent 50%);
    }
    [data-theme="dark"] body {
      background-image:
        radial-gradient(circle at 15% 85%, rgba(234, 88, 12, 0.08) 0%, transparent 50%),
        radial-gradient(circle at 85% 15%, rgba(99, 102, 241, 0.06) 0%, transparent 50%);
    }

    /* ── Card ── */
    .sw-card {
      background: var(--sw-surface);
      border: 1px solid var(--sw-border);
      border-radius: 20px;
      padding: 44px 40px;
      width: 100%;
      max-width: 460px;
      box-shadow:
        0 0 0 1px rgba(0,0,0,0.04),
        0 4px 6px -2px rgba(0,0,0,0.05),
        0 12px 40px -8px rgba(0,0,0,0.08);
    }
    [data-theme="dark"] .sw-card {
      box-shadow:
        0 0 0 1px rgba(255,255,255,0.06),
        0 4px 6px -2px rgba(0,0,0,0.4),
        0 12px 40px -8px rgba(0,0,0,0.6);
    }

    /* ── Logo ── */
    .sw-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
      margin-bottom: 32px;
    }
    .sw-logo-icon {
      width: 28px;
      height: 28px;
      color: var(--sw-accent);
      filter: drop-shadow(0 4px 8px rgba(234,88,12,0.4));
    }
    .sw-logo-text {
      font-size: 17px;
      font-weight: 700;
      letter-spacing: -0.03em;
    }
    .voxel-top  { fill: currentColor; opacity: 1; }
    .voxel-left { fill: currentColor; opacity: 0.65; }
    .voxel-right{ fill: currentColor; opacity: 0.35; }

    /* ── Typography ── */
    .sw-heading {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -0.03em;
      text-align: center;
      margin-bottom: 8px;
    }
    .sw-subheading {
      font-size: 14px;
      color: var(--sw-text-muted);
      text-align: center;
      margin-bottom: 32px;
      line-height: 1.6;
    }

    /* ── Form ── */
    .sw-field { margin-bottom: 16px; }
    .sw-label {
      display: block;
      font-size: 13px;
      font-weight: 500;
      color: var(--sw-text-secondary);
      margin-bottom: 6px;
    }
    .sw-input {
      width: 100%;
      padding: 10px 14px;
      font-size: 15px;
      font-family: inherit;
      background: var(--sw-bg);
      color: var(--sw-text);
      border: 1.5px solid var(--sw-border);
      border-radius: 10px;
      outline: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .sw-input:focus {
      border-color: var(--sw-accent);
      box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
    }
    .sw-input::placeholder { color: var(--sw-text-muted); }
    .sw-error-text { font-size: 12px; color: var(--sw-error); margin-top: 5px; font-weight: 500; }

    /* ── Button ── */
    .sw-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      width: 100%;
      padding: 12px 24px;
      font-size: 15px;
      font-weight: 600;
      font-family: inherit;
      color: #FFF;
      background: var(--sw-accent);
      border: none;
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.15s ease, transform 0.1s ease, box-shadow 0.15s ease;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 4px 12px rgba(234,88,12,0.25);
      margin-top: 4px;
    }
    .sw-btn:hover {
      background: var(--sw-accent-hover);
      box-shadow: 0 1px 2px rgba(0,0,0,0.1), 0 6px 16px rgba(234,88,12,0.35);
    }
    .sw-btn:active { transform: scale(0.98); }
    .sw-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    /* ── Theme toggle ── */
    .sw-theme-toggle {
      position: fixed;
      top: 20px;
      right: 20px;
      width: 38px;
      height: 38px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: var(--sw-surface);
      border: 1px solid var(--sw-border);
      border-radius: 10px;
      cursor: pointer;
      color: var(--sw-text-muted);
      transition: all 0.15s;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .sw-theme-toggle:hover { color: var(--sw-text); }
  </style>
</head>
<body>

  <!-- Theme toggle -->
  <button class="sw-theme-toggle" onclick="toggleTheme()" title="Toggle theme" aria-label="Toggle theme">
    <svg id="theme-icon-sun" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
      <circle cx="12" cy="12" r="5"/>
      <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
      <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
      <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
      <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
    </svg>
    <svg id="theme-icon-moon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
         style="display:none;">
      <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>
  </button>

  <?= $content ?>

  <script>
    function toggleTheme() {
      const html = document.documentElement;
      const next = (html.getAttribute('data-theme') || 'dark') === 'dark' ? 'light' : 'dark';
      html.setAttribute('data-theme', next);
      localStorage.setItem('swarm-theme', next);
      updateIcon(next);
    }
    function updateIcon(theme) {
      document.getElementById('theme-icon-sun').style.display  = theme === 'dark'  ? 'block' : 'none';
      document.getElementById('theme-icon-moon').style.display = theme === 'light' ? 'block' : 'none';
    }
    updateIcon(document.documentElement.getAttribute('data-theme') || 'dark');
  </script>
</body>
</html>
