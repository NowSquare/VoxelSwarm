<?php
/**
 * Account page — operator identity and credentials.
 *
 * Design: VoxelSwarm-04-design-doc.md
 * Copy: VoxelSwarm-05-tone-of-voice.md
 */
$pageTitle = 'Account — VoxelSwarm';
$s = $settings;

$inputClass  = "block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow";
$labelClass  = "block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5";
$hintClass   = "text-xs text-zinc-500 dark:text-zinc-400 mt-1.5";
$cardClass   = "bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden";
$headerClass = "px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20";
?>

<div class="mb-8">
  <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Account</h1>
  <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Your operator identity and credentials.</p>
</div>

<?php if (!empty($flash)): ?>
  <div class="mb-6 p-4 rounded-xl text-sm font-medium border <?= str_contains($flash, 'incorrect') ? 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20' : 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/20' ?>">
    <?= htmlspecialchars($flash) ?>
  </div>
<?php endif; ?>

<form method="POST" action="/operator/account" class="space-y-6">
  <?= $csrfField ?>
  <input type="hidden" name="_method" value="PUT">

  <!-- ═══════════════════════════════════════════════════════════
       EMAIL ADDRESS
       ═══════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-500/10 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
      </div>
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Email Address</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Receives failure alerts and system notifications.</p>
      </div>
    </div>
    <div class="p-6">
      <div class="max-w-md">
        <label class="<?= $labelClass ?>" for="operator_email">
          <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> Email</span>
        </label>
        <input class="<?= $inputClass ?>" type="email" id="operator_email" name="operator_email"
               value="<?= htmlspecialchars($s['operator_email'] ?? '') ?>" placeholder="you@example.com">
        <p class="<?= $hintClass ?>">All system notifications are sent to this address.</p>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       CHANGE PASSWORD
       ═══════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-500/10 flex items-center justify-center flex-shrink-0">
        <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      </div>
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Password</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Update your operator login credentials.</p>
      </div>
    </div>
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label class="<?= $labelClass ?>">
            <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> Current Password</span>
          </label>
          <input class="<?= $inputClass ?>" type="password" name="current_password" autocomplete="current-password" placeholder="••••••••">
        </div>
        <div>
          <label class="<?= $labelClass ?>">
            <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> New Password</span>
          </label>
          <input class="<?= $inputClass ?>" type="password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Min. 8 characters">
        </div>
      </div>
      <p class="<?= $hintClass ?>">Leave both empty to keep your current password.</p>
    </div>
  </div>

  <div class="pt-2 pb-12 flex justify-end">
    <button type="submit" class="sw-btn-primary px-6 py-2.5">
      Save Account
    </button>
  </div>
</form>
