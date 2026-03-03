<?php
/**
 * Instances list — filterable table of all instances.
 */
$pageTitle = 'Instances — VoxelSwarm';
?>

<div class="mb-8 flex items-center justify-between">
  <div>
    <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Instances</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Manage your provisioned VoxelSite workspaces.</p>
  </div>
  <button onclick="openNewInstanceModal()" class="sw-btn-primary">
    <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    New Instance
  </button>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl p-4 shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] mb-6 flex flex-wrap gap-4 items-center justify-between">
  <form method="GET" action="/operator/instances" class="flex flex-wrap gap-3 items-center flex-1">
    <div class="relative w-full max-w-xs">
      <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      </div>
      <input type="text" name="search" placeholder="Search name or email..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>"
             class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 pl-10 pr-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow">
    </div>
    
    <select name="status" class="block w-40 rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow sw-select">
      <option value="">All statuses</option>
      <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
      <option value="paused" <?= ($filters['status'] ?? '') === 'paused' ? 'selected' : '' ?>>Paused</option>
      <option value="provisioning" <?= ($filters['status'] ?? '') === 'provisioning' ? 'selected' : '' ?>>Provisioning</option>
      <option value="failed" <?= ($filters['status'] ?? '') === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
    
    <button type="submit" class="sw-btn-secondary">
      Filter
    </button>
  </form>
  
  <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
    <?= count($instances) ?> instance<?= count($instances) !== 1 ? 's' : '' ?>
  </span>
</div>

<!-- Table -->
<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
  <?php if (empty($instances)): ?>
    <div class="p-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
      No instances found matching your criteria.
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm whitespace-nowrap">
        <thead>
          <tr class="bg-zinc-50/50 dark:bg-zinc-800/20 border-b border-zinc-100 dark:border-zinc-800/80 text-zinc-500 dark:text-zinc-400">
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Identifier</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Name</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Email</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Status</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Type</th>
            <th class="px-5 py-3 font-medium uppercase tracking-wide text-[11px]">Created</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/80">
          <?php foreach ($instances as $inst): ?>
            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors cursor-pointer group" onclick="location.href='/operator/instances/<?= $inst['id'] ?>'">
              <td class="px-5 py-3 font-semibold text-orange-600 dark:text-orange-500 group-hover:text-orange-700 dark:group-hover:text-orange-400 transition-colors">
                <?= htmlspecialchars($inst['slug']) ?>
              </td>
              <td class="px-5 py-3 font-medium text-zinc-900 dark:text-white">
                <?= htmlspecialchars($inst['name']) ?>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400">
                <?= htmlspecialchars($inst['email']) ?>
              </td>
              <td class="px-5 py-3">
                <?php
                  $badgeClass = match($inst['status']) {
                    'active'       => 'bg-green-100/50 text-green-700 dark:bg-green-500/10 dark:text-green-400 ring-1 ring-inset ring-green-600/20 dark:ring-green-500/20',
                    'paused'       => 'bg-amber-100/50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-inset ring-amber-600/20 dark:ring-amber-500/20',
                    'provisioning' => 'bg-orange-100/50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 ring-1 ring-inset ring-orange-600/20 dark:ring-orange-500/20 animate-pulse',
                    'failed'       => 'bg-red-100/50 text-red-700 dark:bg-red-500/10 dark:text-red-400 ring-1 ring-inset ring-red-600/10 dark:ring-red-500/20',
                    default        => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 ring-1 ring-inset ring-zinc-500/20 dark:ring-zinc-400/20',
                  };
                ?>
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold tracking-wide <?= $badgeClass ?>">
                  <?= strtoupper($inst['status']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    <?= ucfirst($inst['type']) ?>
                </span>
              </td>
              <td class="px-5 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                <?= date('M j, Y', strtotime($inst['created_at'])) ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/new-instance-modal.php'; ?>
