<?php
/**
 * Instance detail — single instance view with actions and provision logs.
 */
$pageTitle = htmlspecialchars($instance['name']) . ' — VoxelSwarm';
$isActive = $instance['status'] === 'active';
$isPaused = $instance['status'] === 'paused';
$badgeClass = match($instance['status']) {
  'active'       => 'bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20',
  'paused'       => 'bg-amber-100/50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/20',
  'provisioning' => 'bg-orange-100/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-600/20 dark:ring-orange-500/20 animate-pulse',
  'failed'       => 'bg-red-100/50 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-inset ring-red-600/10 dark:ring-red-500/20',
  default        => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 ring-1 ring-inset ring-zinc-500/20 dark:ring-zinc-400/20',
};
$liveUrl = "https://{$instance['subdomain']}";
?>

<div class="mb-4">
  <a href="/operator/instances" class="inline-flex items-center gap-1 text-sm font-medium text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
    Back to Instances
  </a>
</div>

<div class="mb-8 flex flex-col sm:flex-row sm:items-start justify-between gap-4">
  <div>
    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white mb-2"><?= htmlspecialchars($instance['name']) ?></h1>
    <div class="flex items-center gap-3">
      <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide <?= $badgeClass ?>">
        <?= strtoupper($instance['status']) ?>
      </span>
      <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
        <?= ucfirst($instance['type']) ?>
      </span>
      <?php if ($isActive): ?>
        <a href="<?= $liveUrl ?>" target="_blank" class="inline-flex items-center gap-1 text-sm font-medium text-orange-600 dark:text-orange-500 hover:text-orange-700 dark:hover:text-orange-400 transition-colors group">
          <?= htmlspecialchars($instance['subdomain']) ?>
          <svg class="w-3.5 h-3.5 opacity-50 group-hover:opacity-100 transition-opacity" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="7" y1="17" x2="17" y2="7"/><polyline points="7 7 17 7 17 17"/></svg>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="flex flex-wrap items-center gap-2">
    <?php if ($isActive): ?>
      <button onclick="instanceAction('pause')" class="sw-btn-secondary">
        Pause
      </button>
      <button onclick="instanceAction('gallery')" class="sw-btn-secondary">
        Mark Gallery
      </button>
    <?php elseif ($isPaused): ?>
      <button onclick="instanceAction('resume')" class="sw-btn-primary">
        Resume
      </button>
    <?php endif; ?>
    <button onclick="swConfirm({ title: 'Delete instance?', message: 'This permanently removes <?= htmlspecialchars($instance['name']) ?> and all its files. This cannot be undone.', confirmLabel: 'Delete Instance', danger: true }).then(() => instanceAction('delete')).catch(() => {})" class="sw-btn-danger">
      Delete
    </button>
  </div>
</div>

<!-- Details grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-5 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]">
    <div class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400 mb-1">Subdomain</div>
    <div class="text-base font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($instance['subdomain']) ?></div>
  </div>
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-5 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]">
    <div class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400 mb-1">Email</div>
    <div class="text-base font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($instance['email']) ?></div>
  </div>
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-5 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]">
    <div class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400 mb-1">Created</div>
    <div class="text-base font-semibold text-zinc-900 dark:text-white"><?= date('M j, Y, H:i', strtotime($instance['created_at'])) ?></div>
  </div>
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-5 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)]">
    <div class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400 mb-1">Provisioned</div>
    <div class="text-base font-semibold text-zinc-900 dark:text-white"><?= $instance['provisioned_at'] ? date('M j, Y, H:i', strtotime($instance['provisioned_at'])) : '—' ?></div>
  </div>
</div>

<!-- Notes -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden mb-8">
  <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
    <h2 class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-white">Operator Notes</h2>
  </div>
  <div class="p-5">
    <textarea id="instance-notes" rows="3" placeholder="Add private notes about this instance..." 
              class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-zinc-950 px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow resize-y"><?= htmlspecialchars($instance['notes'] ?? '') ?></textarea>
    <div class="mt-3 text-right">
      <button id="btn-save-notes" onclick="saveNotes()" class="sw-btn-secondary">
        Save Notes
      </button>
    </div>
  </div>
</div>

<!-- Provision Logs -->
<h2 class="text-lg font-bold tracking-tight text-zinc-900 dark:text-white mb-4">Provision Logs</h2>
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
  <?php if (empty($logs)): ?>
    <div class="p-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
      No logs yet.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
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
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400 font-[tabular-nums]">
                <?= $log['duration_ms'] ? $log['duration_ms'] . 'ms' : '—' ?>
              </td>
              <td class="px-5 py-3 text-red-600 dark:text-red-400 max-w-[200px] truncate">
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
    let method = 'POST';
    let url = '/operator/instances/' + instanceId + '/' + action;

    if (action === 'delete') {
      method = 'POST';
      url = '/operator/instances/' + instanceId;
    }

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
        showToast(data.error || 'Operation failed', 'error');
      }
    })
    .catch(() => showToast('Request failed', 'error'));
  }

  function saveNotes() {
    const notes = document.getElementById('instance-notes').value;
    const btn = document.getElementById('btn-save-notes');
    const originalText = btn.textContent;
    btn.textContent = 'Saving...';
    btn.disabled = true;

    fetch('/operator/instances/' + instanceId, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf) + '&_method=PATCH&notes=' + encodeURIComponent(notes)
    })
    .then(r => r.json())
    .then(data => {
      if (data.ok) {
        btn.textContent = 'Saved ✓';
        btn.classList.add('bg-green-100', 'text-green-700', 'dark:bg-green-500/20', 'dark:text-green-400');
        setTimeout(() => {
            btn.textContent = 'Save Notes';
            btn.classList.remove('bg-green-100', 'text-green-700', 'dark:bg-green-500/20', 'dark:text-green-400');
            btn.disabled = false;
        }, 2000);
      } else {
        btn.textContent = originalText;
        btn.disabled = false;
        showToast(data.error || 'Failed to save notes', 'error');
      }
    })
    .catch(() => {
        btn.textContent = originalText;
        btn.disabled = false;
        showToast('Request failed', 'error');
    });
  }
</script>
