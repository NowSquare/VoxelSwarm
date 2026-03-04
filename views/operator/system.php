<?php
/**
 * System page — runtime info, updates, server logs, danger zone.
 *
 * Every element follows the design system in .ai/VoxelSwarm-04-design-doc.md.
 * Icon alignment uses inline-flex + items-center on button wrappers.
 * Log table uses alternating row backgrounds for premium readability.
 */
$pageTitle = 'System — VoxelSwarm';

$cardClass   = "bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden";
$headerClass = "px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20";
$labelClass  = "text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide";
$valueClass  = "text-sm font-semibold text-zinc-900 dark:text-white mt-1";
?>

<div class="mb-8">
  <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">System</h1>
  <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Runtime environment, updates, logs, and maintenance.</p>
</div>

<div class="space-y-6">

  <!-- ═════════════════════════════════════════════════════════
       SYSTEM STATUS
       ═════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?>">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">System Status</h2>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Runtime environment and resource usage.</p>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <?php
        $sysRows = [
          ['VoxelSwarm', SWARM_VERSION],
          ['PHP', PHP_VERSION],
          ['SQLite', \SQLite3::version()['versionString'] ?? '?'],
          ['Database', file_exists(SWARM_DB_PATH) ? round(filesize(SWARM_DB_PATH) / 1024, 1) . ' KB' : '—'],
        ];
        foreach ($sysRows as [$label, $value]): ?>
          <div>
            <div class="<?= $labelClass ?>"><?= $label ?></div>
            <div class="<?= $valueClass ?>"><?= htmlspecialchars((string)$value) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ═════════════════════════════════════════════════════════
       UPDATE
       ═════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Update</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Pull from Git or replace files manually.</p>
      </div>
      <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">v<?= SWARM_VERSION ?></span>
    </div>
    <div class="p-6 space-y-6">

      <!-- Git Update -->
      <div>
        <div class="flex items-center gap-2 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M13 6h3a2 2 0 0 1 2 2v7"/><path d="M6 9v12"/></svg>
          <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Git</span>
          <?php
          $isGitRepo = is_dir(SWARM_ROOT . '/.git');
          if ($isGitRepo): ?>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-50 dark:bg-green-500/10 text-green-600 dark:text-green-400">Available</span>
          <?php else: ?>
            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-zinc-100 dark:bg-zinc-800 text-zinc-500 dark:text-zinc-400">Not a git repo</span>
          <?php endif; ?>
        </div>

        <?php if ($isGitRepo): ?>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">Pull the latest changes from the remote repository. Your database and configuration are preserved.</p>
          <div class="flex items-center gap-3">
            <button type="button" id="btn-git-pull" onclick="gitPull()" class="sw-btn-secondary text-xs inline-flex items-center gap-2 px-4 py-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
              Pull Latest
            </button>
            <span id="git-pull-status" class="text-xs text-zinc-500 dark:text-zinc-400 hidden"></span>
          </div>
          <div id="git-pull-output" class="hidden mt-3 p-3 rounded-lg bg-zinc-950 dark:bg-zinc-950 border border-zinc-800 text-xs font-mono text-zinc-300" style="white-space: pre-wrap; max-height: 12rem; overflow-y: auto;"></div>
        <?php else: ?>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">Not a Git repository. Clone from <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">https://github.com/NowSquare/VoxelSwarm</code> to enable Git updates.</p>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- ═════════════════════════════════════════════════════════
       SERVER LOGS
       ═════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Server Logs</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Application and provisioning logs.</p>
      </div>
      <?php if (!empty($logFiles)): ?>
        <button type="button" onclick="deleteAllLogs()" id="btn-delete-all-logs" class="sw-btn-secondary text-xs inline-flex items-center gap-2 px-3 py-1.5">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete All
        </button>
      <?php endif; ?>
    </div>

    <?php if (empty($logFiles)): ?>
      <div class="p-8 text-center">
        <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-zinc-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">No log files yet.</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Logs appear as the system processes instances and sends emails.</p>
      </div>
    <?php else: ?>
      <!-- Log table with alternating rows -->
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead>
            <tr class="border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20 text-zinc-500 dark:text-zinc-400">
              <th class="px-6 py-3 font-medium uppercase tracking-wide text-[11px]">File</th>
              <th class="px-6 py-3 font-medium uppercase tracking-wide text-[11px] text-right">Size</th>
              <th class="px-6 py-3 font-medium uppercase tracking-wide text-[11px] text-right">Modified</th>
              <th class="px-6 py-3 font-medium uppercase tracking-wide text-[11px] text-right" style="width: 80px;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logFiles as $i => $f): ?>
              <tr class="border-b border-zinc-100 dark:border-zinc-800/80 transition-colors group <?= $i % 2 === 1 ? 'bg-zinc-50/50 dark:bg-zinc-800/20' : '' ?> hover:bg-zinc-100 dark:hover:bg-zinc-800/50" data-log="<?= htmlspecialchars($f['name']) ?>">
                <td class="px-6 py-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-zinc-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span class="text-sm font-mono font-medium text-zinc-800 dark:text-zinc-200 truncate"><?= htmlspecialchars($f['name']) ?></span>
                  </div>
                </td>
                <td class="px-6 py-3 text-right">
                  <span class="text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap" style="font-variant-numeric: tabular-nums;"><?= round($f['size'] / 1024, 1) ?> KB</span>
                </td>
                <td class="px-6 py-3 text-right">
                  <span class="text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap"><?= date('M j, H:i', $f['modified']) ?></span>
                </td>
                <td class="px-6 py-3 text-right">
                  <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a href="/operator/system/logs/download?file=<?= urlencode($f['name']) ?>" download class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 transition-colors" style="background: transparent;" onmouseover="this.style.background='var(--color-zinc-200, rgba(0,0,0,0.06))'; if(document.documentElement.classList.contains('dark')) this.style.background='rgba(255,255,255,0.08)';" onmouseout="this.style.background='transparent';" title="Download">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    </a>
                    <button type="button" onclick="deleteLog('<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>')" class="p-1.5 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <!-- ═════════════════════════════════════════════════════════
       DANGER ZONE
       ═════════════════════════════════════════════════════════ -->
  <div class="border border-red-200 dark:border-red-500/20 rounded-xl overflow-hidden bg-red-500/5 dark:bg-red-500/5">
    <div class="px-6 py-4 border-b border-red-200 dark:border-red-500/20 flex items-center gap-2.5">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-500 dark:text-red-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      <h2 class="text-sm font-semibold text-red-700 dark:text-red-400">Danger Zone</h2>
    </div>
    <div class="p-6 space-y-6">

      <!-- Refresh Installation -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Refresh Installation</p>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Deletes instances, logs, ZIPs, and templates. Account and settings are kept.</p>
        </div>
        <button type="button" onclick="refreshInstallation()" class="sw-btn-danger text-xs inline-flex items-center gap-2 px-4 py-2 flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M21 21v-5h-5"/></svg>
          Refresh
        </button>
      </div>

      <div class="border-t border-red-200 dark:border-red-500/20"></div>

      <!-- Reset Installation -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200">Reset Installation</p>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Wipes everything. Database, files, settings. The setup wizard will run on next visit.</p>
        </div>
        <button type="button" onclick="resetInstallation()" class="sw-btn-danger text-xs inline-flex items-center gap-2 px-4 py-2 flex-shrink-0">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Reset
        </button>
      </div>
    </div>
  </div>

</div>

<div class="h-12"></div>

<!-- Refresh Installation Modal (password required, account kept) -->
<div id="refresh-overlay" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-sm" style="transition: opacity 0.15s ease;">
  <div id="refresh-card" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl rounded-2xl w-full max-w-sm overflow-hidden dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] transform transition-all duration-150 scale-95 opacity-0">
    <div class="px-6 pt-6 pb-2">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M21 21v-5h-5"/></svg>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Refresh installation?</h3>
          <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1" style="line-height: 1.5;">All instances, logs, ZIPs, and processed templates will be deleted. Your account and settings are kept.</p>
          <div class="mt-4">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">Enter your password</label>
            <input type="password" id="refresh-password" class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow" autocomplete="current-password">
            <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">You'll log in with this same password after the refresh.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="px-6 pb-6 pt-4 flex gap-3">
      <button id="refresh-cancel" type="button" class="flex-1 sw-btn-secondary">Cancel</button>
      <button id="refresh-confirm" type="button" class="flex-1 sw-btn-danger">Refresh</button>
    </div>
  </div>
</div>

<!-- Reset Installation Modal (password to confirm identity, full wipe) -->
<div id="reset-overlay" class="fixed inset-0 z-[60] hidden items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-sm" style="transition: opacity 0.15s ease;">
  <div id="reset-card" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl rounded-2xl w-full max-w-sm overflow-hidden dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] transform transition-all duration-150 scale-95 opacity-0">
    <div class="px-6 pt-6 pb-2">
      <div class="flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400">
          <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        </div>
        <div class="min-w-0 flex-1">
          <h3 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Reset entire installation?</h3>
          <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1" style="line-height: 1.5;">This permanently wipes the database, all instances, settings, and files. The setup wizard will appear so you can start from scratch.</p>
          <div class="mt-4">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5 block">Confirm your password</label>
            <input type="password" id="reset-password" class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow" autocomplete="current-password">
            <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">Enter your operator password to confirm this action.</p>
          </div>
        </div>
      </div>
    </div>
    <div class="px-6 pb-6 pt-4 flex gap-3">
      <button id="reset-cancel" type="button" class="flex-1 sw-btn-secondary">Cancel</button>
      <button id="reset-confirm" type="button" class="flex-1 sw-btn-danger">Reset Everything</button>
    </div>
  </div>
</div>

<script>
  const csrf = '<?= \Swarm\Middleware\Csrf::token() ?>';

  /* ── Git pull ──────────────────────────────────────────────── */
  async function gitPull() {
    const btn = document.getElementById('btn-git-pull');
    const status = document.getElementById('git-pull-status');
    const output = document.getElementById('git-pull-output');

    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin w-3.5 h-3.5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Pulling…';

    try {
      const r = await fetch('/operator/system/git-pull', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(csrf)
      });
      const data = await r.json();

      output.classList.remove('hidden');
      output.textContent = data.output || '';

      if (data.ok) {
        status.textContent = data.message || 'Pull successful';
        status.className = 'text-xs text-green-600 dark:text-green-400';
        showToast(data.message || 'Updated successfully', 'success');
      } else {
        status.textContent = data.error || 'Pull failed';
        status.className = 'text-xs text-red-600 dark:text-red-400';
        showToast(data.error || 'Update failed', 'error');
      }
      status.classList.remove('hidden');
    } catch {
      showToast('Request failed', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg> Pull Latest';
  }

  /* ── Delete single log ─────────────────────────────────────── */
  function deleteLog(filename) {
    swConfirm({
      title: 'Delete log file?',
      message: 'Delete "' + filename + '". This cannot be undone.',
      confirmLabel: 'Delete',
      danger: true
    }).then(() => {
      fetch('/operator/system/logs/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(csrf) + '&file=' + encodeURIComponent(filename)
      })
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          const row = document.querySelector('[data-log="' + filename + '"]');
          if (row) { row.style.opacity = '0'; setTimeout(() => row.remove(), 200); }
          showToast('Log deleted.', 'success');
        } else {
          showToast(data.error || 'Failed', 'error');
        }
      });
    }).catch(() => {});
  }

  /* ── Delete all logs ───────────────────────────────────────── */
  function deleteAllLogs() {
    swConfirm({
      title: 'Delete all log files?',
      message: 'Every log file will be removed. Cannot be undone.',
      confirmLabel: 'Delete All',
      danger: true
    }).then(() => {
      fetch('/operator/system/logs/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(csrf) + '&file=*'
      })
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          showToast('All logs deleted.', 'success');
          setTimeout(() => location.reload(), 500);
        }
      });
    }).catch(() => {});
  }

  /* ── Refresh installation (password-protected modal) ───────── */
  function refreshInstallation() {
    const overlay = document.getElementById('refresh-overlay');
    const card = document.getElementById('refresh-card');
    const pwInput = document.getElementById('refresh-password');
    const confirmBtn = document.getElementById('refresh-confirm');
    const cancelBtn = document.getElementById('refresh-cancel');

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    pwInput.value = '';
    pwInput.style.borderColor = '';
    requestAnimationFrame(() => {
      card.classList.remove('scale-95', 'opacity-0');
      card.classList.add('scale-100', 'opacity-100');
      pwInput.focus();
    });

    function closeModal() {
      card.classList.remove('scale-100', 'opacity-100');
      card.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        overlay.classList.remove('flex');
        overlay.classList.add('hidden');
      }, 150);
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlay);
      document.removeEventListener('keydown', onKey);
    }

    function onConfirm() {
      const pw = pwInput.value.trim();
      if (!pw) {
        pwInput.style.borderColor = 'var(--color-red-500)';
        pwInput.focus();
        showToast('Enter your password to confirm.', 'error');
        return;
      }
      closeModal();

      fetch('/operator/system/refresh', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(csrf) + '&password=' + encodeURIComponent(pw)
      })
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          showToast('Installation refreshed. Redirecting…', 'success');
          setTimeout(() => location.href = '/operator', 1000);
        } else {
          showToast(data.error || 'Refresh failed', 'error');
        }
      })
      .catch(() => showToast('Request failed — check your connection.', 'error'));
    }

    function onCancel() { closeModal(); }
    function onOverlay(e) { if (e.target === overlay) closeModal(); }
    function onKey(e) {
      if (e.key === 'Escape') closeModal();
      if (e.key === 'Enter') onConfirm();
    }

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlay);
    document.addEventListener('keydown', onKey);
  }

  /* ── Reset installation (password-protected modal, full wipe) ── */
  function resetInstallation() {
    const overlay = document.getElementById('reset-overlay');
    const card = document.getElementById('reset-card');
    const pwInput = document.getElementById('reset-password');
    const confirmBtn = document.getElementById('reset-confirm');
    const cancelBtn = document.getElementById('reset-cancel');

    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    pwInput.value = '';
    pwInput.style.borderColor = '';
    requestAnimationFrame(() => {
      card.classList.remove('scale-95', 'opacity-0');
      card.classList.add('scale-100', 'opacity-100');
      pwInput.focus();
    });

    function closeModal() {
      card.classList.remove('scale-100', 'opacity-100');
      card.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        overlay.classList.remove('flex');
        overlay.classList.add('hidden');
      }, 150);
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn.removeEventListener('click', onCancel);
      overlay.removeEventListener('click', onOverlay);
      document.removeEventListener('keydown', onKey);
    }

    function onConfirm() {
      const pw = pwInput.value.trim();
      if (!pw) {
        pwInput.style.borderColor = 'var(--color-red-500)';
        pwInput.focus();
        showToast('Enter your password to confirm.', 'error');
        return;
      }
      closeModal();

      fetch('/operator/system/reset', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(csrf) + '&password=' + encodeURIComponent(pw)
      })
      .then(r => r.json())
      .then(data => {
        if (data.ok) {
          showToast('Installation reset. Redirecting…', 'success');
          setTimeout(() => location.href = '/install', 1000);
        } else {
          showToast(data.error || 'Reset failed', 'error');
        }
      })
      .catch(() => showToast('Request failed — check your connection.', 'error'));
    }

    function onCancel() { closeModal(); }
    function onOverlay(e) { if (e.target === overlay) closeModal(); }
    function onKey(e) {
      if (e.key === 'Escape') closeModal();
      if (e.key === 'Enter') onConfirm();
    }

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn.addEventListener('click', onCancel);
    overlay.addEventListener('click', onOverlay);
    document.addEventListener('keydown', onKey);
  }
</script>
