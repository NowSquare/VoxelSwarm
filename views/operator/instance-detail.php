<?php
/**
 * Instance detail — single instance view with info cards, actions, notes, and provision logs.
 *
 * Design: follows VoxelSwarm-04-design-doc.md strictly.
 * Copy: follows VoxelSwarm-05-tone-of-voice.md.
 */
$pageTitle = htmlspecialchars($instance['name']) . ' — VoxelSwarm';
$isActive = $instance['status'] === 'active';
$isPaused = $instance['status'] === 'paused';
$isFailed = $instance['status'] === 'failed';

$badgeClass = match($instance['status']) {
  'active'       => 'bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20',
  'paused'       => 'bg-amber-100/50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/20',
  'provisioning' => 'bg-orange-100/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-600/20 dark:ring-orange-500/20 animate-pulse',
  'failed'       => 'bg-red-100/50 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-inset ring-red-600/10 dark:ring-red-500/20',
  default        => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 ring-1 ring-inset ring-zinc-500/20 dark:ring-zinc-400/20',
};

$statusDotClass = match($instance['status']) {
  'active'       => 'bg-green-500',
  'paused'       => 'bg-amber-500',
  'provisioning' => 'bg-orange-500 animate-pulse',
  'failed'       => 'bg-red-500',
  default        => 'bg-zinc-400',
};

$liveUrl = "https://{$instance['subdomain']}";

$cardClass   = "bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden";
$headerClass = "px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20";
?>

<!-- Breadcrumb -->
<div class="mb-4">
  <a href="/operator/instances" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
    <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
    Instances
  </a>
</div>

<!-- Page Header -->
<div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
  <div>
    <div class="flex items-center gap-3 mb-2">
      <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white"><?= htmlspecialchars($instance['name']) ?></h1>
      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide <?= $badgeClass ?>">
        <span class="w-1.5 h-1.5 rounded-full <?= $statusDotClass ?>"></span>
        <?= strtoupper($instance['status']) ?>
      </span>
    </div>
    <div class="flex items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
      <span class="font-mono text-xs"><?= htmlspecialchars($instance['slug']) ?></span>
      <?php if ($isActive): ?>
        <span class="text-zinc-300 dark:text-zinc-600">·</span>
        <a href="<?= $liveUrl ?>" target="_blank" class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400 transition-colors group font-medium">
          <?= htmlspecialchars($instance['subdomain']) ?>
          <svg class="w-3 h-3 opacity-50 group-hover:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Actions -->
  <div class="flex flex-wrap items-center gap-2">
    <?php if ($isActive): ?>
      <button onclick="instanceAction('pause')" class="sw-btn-secondary inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="10" x2="10" y1="15" y2="9"/><line x1="14" x2="14" y1="15" y2="9"/></svg>
        Pause
      </button>
    <?php elseif ($isPaused): ?>
      <button onclick="instanceAction('resume')" class="sw-btn-primary inline-flex items-center gap-1.5">
        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
        Resume
      </button>
    <?php endif; ?>
    <button onclick="swConfirm({ title: 'Delete instance?', message: '<?= htmlspecialchars($instance['name']) ?> and all its files will be permanently removed.', confirmLabel: 'Delete Instance', danger: true }).then(() => instanceAction('delete')).catch(() => {})" class="sw-btn-danger inline-flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      Delete
    </button>
  </div>
</div>

<!-- Instance Info -->
<div class="<?= $cardClass ?> mb-6">
  <div class="<?= $headerClass ?>">
    <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Instance Details</h2>
  </div>
  <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
    <?php
    $details = [
      [
        'label' => 'Identifier',
        'value' => $instance['slug'],
        'mono'  => true,
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9h16"/><path d="M4 15h16"/><path d="M10 3 8 21"/><path d="M14 3l-2 18"/></svg>',
      ],
      [
        'label' => 'URL',
        'value' => $instance['subdomain'],
        'link'  => $isActive ? $liveUrl : null,
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
      ],
      [
        'label' => 'Email',
        'value' => $instance['email'] ?: '—',
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>',
      ],
      [
        'label' => 'Type',
        'value' => ucfirst($instance['type']),
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="m2 17 10 5 10-5"/><path d="m2 12 10 5 10-5"/></svg>',
      ],
      [
        'label' => 'Created',
        'value' => date('M j, Y \a\t H:i', strtotime($instance['created_at'])),
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
      ],
      [
        'label' => 'Provisioned',
        'value' => $instance['provisioned_at'] ? date('M j, Y \a\t H:i', strtotime($instance['provisioned_at'])) : '—',
        'icon'  => '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>',
      ],
    ];

    foreach ($details as $d): ?>
      <div class="px-5 py-3.5 flex items-center gap-4">
        <div class="w-8 h-8 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center flex-shrink-0 text-zinc-400 dark:text-zinc-500">
          <?= $d['icon'] ?>
        </div>
        <div class="min-w-0 flex-1 flex items-center justify-between gap-4">
          <span class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400"><?= $d['label'] ?></span>
          <?php if (!empty($d['link'])): ?>
            <a href="<?= $d['link'] ?>" target="_blank" class="text-sm font-medium text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400 transition-colors truncate"><?= htmlspecialchars($d['value']) ?></a>
          <?php elseif (!empty($d['mono'])): ?>
            <span class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($d['value']) ?></span>
          <?php else: ?>
            <span class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($d['value']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Custom Domain -->
<?php if ($isActive || $isPaused): ?>
<div class="<?= $cardClass ?> mb-6" id="domain-section">
  <div class="<?= $headerClass ?> flex items-center justify-between">
    <div>
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Custom Domain</h2>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Attach a fully qualified domain to this instance.</p>
    </div>
    <?php if (!empty($instance['custom_domain'])): ?>
      <div class="flex items-center gap-2">
        <?php if ($instance['domain_verified_at']): ?>
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5"></span>
            VERIFIED
          </span>
        <?php endif; ?>
        <?php if ($instance['domain_ssl_at']): ?>
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20">
            <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            SSL
          </span>
        <?php else: ?>
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide bg-amber-100/50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/20">
            <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            SSL PENDING
          </span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="p-5">
    <?php if (empty($instance['custom_domain'])): ?>
      <!-- ── No domain set ── -->
      <div class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
        <div class="flex-1 w-full sm:w-auto">
          <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Domain</label>
          <input id="domain-input" type="text" placeholder="example.com"
                 class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow">
        </div>
        <button id="btn-add-domain" onclick="addDomain(false)" class="sw-btn-primary inline-flex items-center gap-1.5 px-4 py-2.5 whitespace-nowrap">
          <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
          Verify &amp; Add
        </button>
      </div>
      <?php if (!empty($serverIp)): ?>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-3">
          <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
          Point your domain's A record to <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded font-mono"><?= htmlspecialchars($serverIp) ?></code>, then enter it here.
        </p>
      <?php else: ?>
        <p class="text-xs text-amber-600 dark:text-amber-400 mt-3">
          <svg class="w-3.5 h-3.5 inline-block mr-1 -mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
          Set your server IP in <a href="/operator/deployment" class="underline hover:text-amber-700 dark:hover:text-amber-300">Deployment settings</a> first.
        </p>
      <?php endif; ?>

      <!-- DNS pending feedback area -->
      <div id="domain-dns-pending" class="hidden mt-4 p-4 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-sm">
        <p class="font-medium text-amber-800 dark:text-amber-300 mb-2">DNS not pointing to this server yet.</p>
        <div class="font-mono text-xs bg-white/50 dark:bg-zinc-950/50 rounded-lg p-3 mb-3 text-amber-900 dark:text-amber-200">
          <span id="dns-record-info"></span>
        </div>
        <div class="flex items-center gap-3">
          <button onclick="addDomain(false)" class="sw-btn-secondary text-xs px-3 py-1.5">Re-check DNS</button>
          <button onclick="addDomain(true)" class="text-xs text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 underline transition-colors">Force add (skip verification)</button>
        </div>
      </div>
      <div id="domain-error" class="hidden mt-4 p-4 rounded-xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-sm text-red-600 dark:text-red-400"></div>

    <?php else: ?>
      <!-- ── Domain is set ── -->
      <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-500/10 flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <div class="min-w-0">
            <a href="https://<?= htmlspecialchars($instance['custom_domain']) ?>" target="_blank"
               class="text-sm font-semibold text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400 transition-colors inline-flex items-center gap-1">
              <?= htmlspecialchars($instance['custom_domain']) ?>
              <svg class="w-3 h-3 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
            </a>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
              Subdomain: <?= htmlspecialchars($instance['subdomain']) ?>
            </p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
          <?php if (!$instance['domain_ssl_at']): ?>
            <button onclick="recheckSsl()" class="sw-btn-secondary text-xs px-3 py-1.5 inline-flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/></svg>
              Re-check SSL
            </button>
          <?php endif; ?>
          <button onclick="swConfirm({ title: 'Remove custom domain?', message: '<?= htmlspecialchars($instance['custom_domain']) ?> will be disconnected. The subdomain continues to serve this instance.', confirmLabel: 'Remove Domain', danger: true }).then(() => removeDomainAction()).catch(() => {})"
                  class="sw-btn-danger text-xs px-3 py-1.5 inline-flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            Remove
          </button>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- Operator Notes -->
<div class="<?= $cardClass ?> mb-6">
  <div class="<?= $headerClass ?>">
    <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Notes</h2>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Private notes visible only to operators.</p>
  </div>
  <div class="p-5">
    <textarea id="instance-notes" rows="3" placeholder="Add notes about this instance..."
              class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow resize-y"><?= htmlspecialchars($instance['notes'] ?? '') ?></textarea>
    <div class="mt-3 flex justify-end">
      <button id="btn-save-notes" onclick="saveNotes()" class="sw-btn-secondary text-xs px-4 py-1.5">
        Save Notes
      </button>
    </div>
  </div>
</div>

<!-- Provision Log -->
<div class="<?= $cardClass ?>">
  <div class="<?= $headerClass ?>">
    <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Provision Log</h2>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Step-by-step deployment history.</p>
  </div>

  <?php if (empty($logs)): ?>
    <div class="p-8 text-center">
      <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
        <svg class="w-6 h-6 text-zinc-400 dark:text-zinc-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
      </div>
      <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">No log entries yet</p>
      <p class="text-xs text-zinc-400 dark:text-zinc-500">Deployment steps will appear here during provisioning.</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm" style="white-space: nowrap;">
        <thead>
          <tr class="bg-zinc-50/50 dark:bg-zinc-800/20 border-b border-zinc-100 dark:border-zinc-800/80 text-zinc-500 dark:text-zinc-400">
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Step</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Status</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Duration</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Error</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px] text-right">Time</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
          <?php foreach ($logs as $log): ?>
            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-5 py-3 font-medium text-zinc-900 dark:text-white">
                <?= htmlspecialchars($log['step']) ?>
              </td>
              <td class="px-5 py-3">
                <?php
                  $logBadge = match($log['status']) {
                    'completed' => 'bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20',
                    'failed'    => 'bg-red-100/50 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-inset ring-red-600/10 dark:ring-red-500/20',
                    'started'   => 'bg-orange-100/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-600/20 dark:ring-orange-500/20 animate-pulse',
                    default     => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 ring-1 ring-inset ring-zinc-500/20 dark:ring-zinc-400/20',
                  };
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide <?= $logBadge ?>">
                  <?= strtoupper($log['status']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400" style="font-variant-numeric: tabular-nums;">
                <?= $log['duration_ms'] ? $log['duration_ms'] . 'ms' : '—' ?>
              </td>
              <td class="px-5 py-3 text-red-600 dark:text-red-400" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                <?= htmlspecialchars($log['error'] ?? '') ?>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400 text-xs text-right">
                <?= date('H:i:s', strtotime($log['created_at'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>
  const instanceId = <?= $instance['id'] ?>;
  const csrf = '<?= \Swarm\Middleware\Csrf::token() ?>';

  function instanceAction(action) {
    const url = action === 'delete'
      ? '/operator/instances/' + instanceId
      : '/operator/instances/' + instanceId + '/' + action;

    const body = action === 'delete'
      ? '_token=' + encodeURIComponent(csrf) + '&_method=DELETE'
      : '_token=' + encodeURIComponent(csrf);

    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        if (action === 'delete') location.href = '/operator/instances';
        else location.reload();
      } else {
        showToast(data.error || 'Action failed.', 'error');
      }
    })
    .catch(() => showToast('Request failed — check your connection.', 'error'));
  }

  function saveNotes() {
    const notes = document.getElementById('instance-notes').value;
    const btn = document.getElementById('btn-save-notes');
    const original = btn.textContent;
    btn.textContent = 'Saving…';
    btn.disabled = true;

    fetch('/operator/instances/' + instanceId, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf) + '&_method=PATCH&notes=' + encodeURIComponent(notes)
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showToast('Notes saved.', 'success');
        btn.textContent = original;
        btn.disabled = false;
      } else {
        btn.textContent = original;
        btn.disabled = false;
        showToast(data.error || 'Failed to save.', 'error');
      }
    })
    .catch(() => {
      btn.textContent = original;
      btn.disabled = false;
      showToast('Request failed — check your connection.', 'error');
    });
  }

  /* ── Custom Domain ─────────────────────────────────────────── */

  function addDomain(force) {
    const input = document.getElementById('domain-input');
    if (!input) return;

    const domain = input.value.trim();
    if (!domain) {
      showToast('Enter a domain.', 'error');
      return;
    }

    const btn = document.getElementById('btn-add-domain');
    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="flex items-center gap-2"><svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Verifying…</span>';

    let body = '_token=' + encodeURIComponent(csrf) + '&domain=' + encodeURIComponent(domain);
    if (force) body += '&force=1';

    // Hide previous feedback
    const dnsPending = document.getElementById('domain-dns-pending');
    const domainError = document.getElementById('domain-error');
    if (dnsPending) dnsPending.classList.add('hidden');
    if (domainError) domainError.classList.add('hidden');

    fetch('/operator/instances/' + instanceId + '/domain', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = originalHtml;

      if (data.ok) {
        showToast('Domain added' + (data.ssl_active ? ' with SSL.' : '. SSL pending.'), 'success');
        setTimeout(() => location.reload(), 800);
      } else if (data.dns_pending) {
        if (dnsPending) {
          dnsPending.classList.remove('hidden');
          const info = document.getElementById('dns-record-info');
          if (info) info.textContent = data.record_type + ' ' + data.record_name + ' → ' + data.record_value;
        }
      } else {
        if (domainError) {
          domainError.textContent = data.error || 'Failed to add domain.';
          domainError.classList.remove('hidden');
        }
        showToast(data.error || 'Failed to add domain.', 'error');
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
      showToast('Request failed — check your connection.', 'error');
    });
  }

  function removeDomainAction() {
    fetch('/operator/instances/' + instanceId + '/domain', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf) + '&_method=DELETE'
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        showToast('Domain removed.', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast(data.error || 'Failed to remove domain.', 'error');
      }
    })
    .catch(() => showToast('Request failed.', 'error'));
  }

  function recheckSsl() {
    fetch('/operator/instances/' + instanceId + '/domain/recheck', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf)
    })
    .then(r => r.json())
    .then(data => {
      if (data.ssl_active) {
        showToast('SSL is active.', 'success');
        setTimeout(() => location.reload(), 800);
      } else {
        showToast('SSL not ready yet. Try again later.', 'info');
      }
    })
    .catch(() => showToast('Request failed.', 'error'));
  }
</script>
