<?php
/**
 * Templates page — VoxelSite ZIP management and version control.
 */
$pageTitle = 'Templates — VoxelSwarm';
?>

<div class="mb-8">
  <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Templates</h1>
  <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Manage VoxelSite versions. Upload ZIPs to <code class="text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">template/voxelsite/</code> via FTP or SSH, then process them here.</p>
</div>

<?php if (!empty($flash)): ?>
  <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= $flash['type'] === 'success'
    ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/20'
    : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20' ?>">
    <div class="flex gap-3 items-start">
      <?php if ($flash['type'] === 'success'): ?>
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      <?php else: ?>
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      <?php endif; ?>
      <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
  </div>
<?php endif; ?>

<!-- Prepared Versions -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden mb-6">
  <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20 flex items-center justify-between">
    <div>
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Prepared Versions</h2>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Extracted and ready for provisioning. The active version is used for new instances.</p>
    </div>
  </div>

  <?php if (empty($versions)): ?>
    <div class="p-8 text-center">
      <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
        <svg class="w-6 h-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
      </div>
      <p class="text-sm text-zinc-500 dark:text-zinc-400">No versions prepared yet.</p>
      <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Upload a VoxelSite ZIP and process it below.</p>
    </div>
  <?php else: ?>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
      <?php foreach ($versions as $v): ?>
        <div class="px-6 py-4 flex items-center justify-between gap-4">
          <div class="flex items-center gap-4 min-w-0">
            <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 <?= $v['active']
              ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 dark:text-orange-400'
              : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-400' ?>">
              <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">v<?= htmlspecialchars($v['version']) ?></span>
                <?php if ($v['active']): ?>
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-orange-100 dark:bg-orange-500/15 text-orange-700 dark:text-orange-400">Active</span>
                <?php endif; ?>
              </div>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                <?= htmlspecialchars($v['directory']) ?> · <?= \Swarm\Services\TemplateManager::formatSize($v['size']) ?>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <?php if (!$v['active']): ?>
              <form method="POST" action="/operator/templates/activate" class="inline">
                <?= $csrfField ?>
                <input type="hidden" name="version" value="<?= htmlspecialchars($v['directory']) ?>">
                <button type="submit" class="sw-btn-secondary px-3 py-1.5 text-xs">Activate</button>
              </form>
              <form method="POST" action="/operator/templates/delete-version" class="inline" id="delete-version-<?= htmlspecialchars($v['directory']) ?>">
                <?= $csrfField ?>
                <input type="hidden" name="version" value="<?= htmlspecialchars($v['directory']) ?>">
                <button type="button" class="sw-btn-danger px-3 py-1.5 text-xs" onclick="swConfirm({ title: 'Delete version?', message: 'Version <?= htmlspecialchars($v['directory']) ?> will be permanently removed. This cannot be undone.', confirmLabel: 'Delete Version', danger: true }).then(() => this.closest('form').submit()).catch(() => {})">Delete</button>
              </form>
            <?php else: ?>
              <span class="text-xs text-zinc-400 dark:text-zinc-500 italic">In use</span>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Available ZIPs -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
  <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
    <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Available ZIPs</h2>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">VoxelSite ZIP files found in <code class="text-[10px] bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">template/voxelsite/</code>. Process a ZIP to extract and prepare it as a template version.</p>
  </div>

  <?php if (empty($zips)): ?>
    <div class="p-8 text-center">
      <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
        <svg class="w-6 h-6 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
      </div>
      <p class="text-sm text-zinc-500 dark:text-zinc-400">No ZIP files found.</p>
      <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Upload a VoxelSite ZIP to <code class="text-[10px] bg-zinc-100 dark:bg-zinc-800 px-1 py-0.5 rounded">template/voxelsite/</code> via FTP or SSH.</p>
    </div>
  <?php else: ?>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
      <?php foreach ($zips as $zip): ?>
        <div class="px-6 py-4 flex items-center justify-between gap-4">
          <div class="flex items-center gap-4 min-w-0">
            <div class="w-10 h-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center flex-shrink-0 text-zinc-400">
              <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            </div>
            <div class="min-w-0">
              <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($zip['filename']) ?></p>
              <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                <?= \Swarm\Services\TemplateManager::formatSize($zip['size']) ?> · <?= htmlspecialchars($zip['modified']) ?>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            <form method="POST" action="/operator/templates/process" class="inline">
              <?= $csrfField ?>
              <input type="hidden" name="filename" value="<?= htmlspecialchars($zip['filename']) ?>">
              <button type="submit" class="sw-btn-primary px-3 py-1.5 text-xs">Process</button>
            </form>
            <form method="POST" action="/operator/templates/delete-zip" class="inline">
              <?= $csrfField ?>
              <input type="hidden" name="filename" value="<?= htmlspecialchars($zip['filename']) ?>">
              <button type="button" class="sw-btn-danger px-3 py-1.5 text-xs" onclick="swConfirm({ title: 'Delete ZIP file?', message: '<?= htmlspecialchars($zip['filename']) ?> will be permanently removed. This cannot be undone.', confirmLabel: 'Delete ZIP', danger: true }).then(() => this.closest('form').submit()).catch(() => {})">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
