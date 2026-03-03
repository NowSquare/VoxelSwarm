<?php
/**
 * Operator dashboard — summary cards + recent activity.
 */
$pageTitle = 'Dashboard — VoxelSwarm';
?>

<div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Dashboard</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Overview of your VoxelSwarm cluster.</p>
  </div>
  <button onclick="openNewInstanceModal()" class="sw-btn-primary">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Instance
  </button>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
  <?php
  $renderCard = function($label, $value, $valueColorClass = '') {
    return '
    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-5 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] flex flex-col justify-between">
      <div class="text-[13px] font-medium text-zinc-500 dark:text-zinc-400">'.$label.'</div>
      <div class="text-3xl font-bold tracking-tight mt-2 '.$valueColorClass.'">'.$value.'</div>
    </div>';
  };
  
  echo $renderCard('Total Instances', $counts['total'], 'text-zinc-900 dark:text-white');
  echo $renderCard('Active', $counts['active'], 'text-green-600');
  echo $renderCard('Paused', $counts['paused'], $counts['paused'] > 0 ? 'text-amber-600' : 'text-zinc-900 dark:text-white');
  echo $renderCard('Storage Used', $storageUsed, 'text-zinc-900 dark:text-white text-[24px]');
  ?>
</div>

<!-- Activity Log -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
  <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
    <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Recent Activity</h2>
  </div>
  
  <?php if (empty($recentLogs)): ?>
    <div class="p-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
      No provisioning activity yet. Create an instance to get started.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
        <thead>
          <tr class="bg-zinc-50/50 dark:bg-zinc-800/20 border-b border-zinc-100 dark:border-zinc-800/80 text-zinc-500 dark:text-zinc-400">
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Instance</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Step</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Status</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Duration</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px] text-right">Time</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
          <?php foreach ($recentLogs as $log): ?>
            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-5 py-3">
                <a href="/operator/instances/<?= $log['instance_id'] ?>" class="font-medium text-zinc-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-500 transition-colors group">
                  <?= htmlspecialchars($log['slug'] ?? '—') ?>
                  <span class="inline-block ml-1 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                </a>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($log['step']) ?></td>
              <td class="px-5 py-3">
                <?php
                  $badgeClass = match($log['status']) {
                    'completed' => 'bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20',
                    'failed'    => 'bg-red-100/50 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-inset ring-red-600/10 dark:ring-red-500/20',
                    'started'   => 'bg-orange-100/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-600/20 dark:ring-orange-500/20 animate-pulse',
                    default     => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 ring-1 ring-inset ring-zinc-500/20 dark:ring-zinc-400/20',
                  };
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide <?= $badgeClass ?>">
                  <?= strtoupper($log['status']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400 font-[tabular-nums]">
                <?= $log['duration_ms'] ? $log['duration_ms'] . 'ms' : '—' ?>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400 text-xs text-right">
                <?= date('M j, H:i', strtotime($log['created_at'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/new-instance-modal.php'; ?>
