<?php
/**
 * New Instance Modal — Shared partial for dashboard and instances pages.
 *
 * Expects these variables in scope:
 *   $adapter       — 'local', 'nginx', 'forge', etc.
 *   $baseDomain    — The configured base domain
 *   $instancesPath — The instances root directory
 *   $operatorEmail — Default operator email
 */
$isDomainMode = ($adapter !== 'local');
?>

<!-- New Instance Modal -->
<div id="modal-new" class="fixed inset-0 z-50 hidden items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-sm" style="transition: opacity 0.15s ease;">
  <div id="modal-new-card" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl rounded-2xl w-full max-w-lg overflow-hidden transform transition-all duration-150 dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] scale-95 opacity-0">
    <div class="px-6 py-5 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
      <div>
        <h3 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">New Instance</h3>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
          <?php if ($isDomainMode): ?>
            Deploy a new VoxelSite installation on <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($baseDomain) ?></span>
          <?php else: ?>
            Deploy a new VoxelSite installation to the filesystem
          <?php endif; ?>
        </p>
      </div>
      <button type="button" onclick="closeNewInstanceModal()" class="text-zinc-400 hover:text-zinc-500 dark:hover:text-zinc-300 transition-colors">
        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    
    <div class="px-6 py-5">
      <form id="form-new-instance" class="space-y-5">

        <!-- Identifier (required) -->
        <div>
          <label for="inst-slug" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
            <?= $isDomainMode ? 'Subdomain' : 'Folder Name' ?> <span class="text-red-500">*</span>
          </label>
          <?php if ($isDomainMode): ?>
            <div class="flex items-stretch">
              <input type="text" id="inst-slug" name="slug" placeholder="acme" required
                     class="block flex-1 rounded-l-lg border border-r-0 border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#0f0f11] px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow"
                     oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '')">
              <span class="inline-flex items-center px-3 py-2.5 rounded-r-lg border border-l-0 border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-sm text-zinc-500 dark:text-zinc-400 font-medium select-none">.<?= htmlspecialchars($baseDomain) ?></span>
            </div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1.5">
              Accessible at <span id="slug-preview" class="text-zinc-500 dark:text-zinc-400">slug</span>.<?= htmlspecialchars($baseDomain) ?>
            </p>
          <?php else: ?>
            <div class="flex items-stretch">
              <span class="inline-flex items-center px-3 py-2.5 rounded-l-lg border border-r-0 border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 text-xs text-zinc-500 dark:text-zinc-400 font-mono select-none truncate max-w-[200px]"><?= htmlspecialchars($instancesPath) ?>/</span>
              <input type="text" id="inst-slug" name="slug" placeholder="my-site" required
                     class="block flex-1 rounded-r-lg border border-l-0 border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#0f0f11] px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow"
                     oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '')">
            </div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1.5">Folder name for this instance. Lowercase letters, numbers, and hyphens only.</p>
          <?php endif; ?>
        </div>

        <!-- Name & Email (optional) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label for="inst-name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Name <span class="text-zinc-400 text-xs font-normal">(optional)</span></label>
            <input type="text" id="inst-name" name="name" placeholder="e.g. Acme Corp"
                   class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#0f0f11] px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Display name for your records.</p>
          </div>
          <div>
            <label for="inst-email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">Email <span class="text-zinc-400 text-xs font-normal">(optional)</span></label>
            <input type="email" id="inst-email" name="email" placeholder="<?= htmlspecialchars($operatorEmail) ?>"
                   class="block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-white dark:bg-[#0f0f11] px-3 py-2.5 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow">
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">Contact email for your records.</p>
          </div>
        </div>

        <!-- Error display -->
        <div id="new-instance-error" class="hidden p-3 rounded-lg text-sm font-medium bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20"></div>

        <!-- Actions -->
        <div class="pt-1 flex gap-3">
          <button type="button" onclick="closeNewInstanceModal()" class="flex-1 sw-btn-secondary">
            Cancel
          </button>
          <button type="submit" id="btn-create-instance" class="flex-1 sw-btn-primary">
            Create Instance
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Open / close modal
  function openNewInstanceModal() {
    const overlay = document.getElementById('modal-new');
    const card = document.getElementById('modal-new-card');
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    requestAnimationFrame(() => {
      card.classList.remove('scale-95', 'opacity-0');
      card.classList.add('scale-100', 'opacity-100');
    });
    document.getElementById('inst-slug').focus();
  }

  function closeNewInstanceModal() {
    const overlay = document.getElementById('modal-new');
    const card = document.getElementById('modal-new-card');
    card.classList.remove('scale-100', 'opacity-100');
    card.classList.add('scale-95', 'opacity-0');
    setTimeout(() => {
      overlay.classList.remove('flex');
      overlay.classList.add('hidden');
    }, 150);
  }

  // Click outside to close
  document.getElementById('modal-new').addEventListener('click', function(e) {
    if (e.target === this) closeNewInstanceModal();
  });

  // Escape to close
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('modal-new').classList.contains('hidden')) {
      closeNewInstanceModal();
    }
  });

  // Live preview for domain mode
  <?php if ($isDomainMode): ?>
  document.getElementById('inst-slug').addEventListener('input', function() {
    const preview = document.getElementById('slug-preview');
    if (preview) preview.textContent = this.value || 'slug';
  });
  <?php endif; ?>

  // Form submission
  document.getElementById('form-new-instance').addEventListener('submit', function(e) {
    e.preventDefault();

    const slug  = document.getElementById('inst-slug').value.trim();
    const name  = document.getElementById('inst-name').value.trim();
    const email = document.getElementById('inst-email').value.trim();
    const errorEl = document.getElementById('new-instance-error');

    if (!slug) {
      errorEl.textContent = 'An identifier is required.';
      errorEl.classList.remove('hidden');
      return;
    }

    errorEl.classList.add('hidden');
    const btn = document.getElementById('btn-create-instance');
    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Creating...';

    const csrf = '<?= \Swarm\Middleware\Csrf::token() ?>';
    let body = '_token=' + encodeURIComponent(csrf) + '&slug=' + encodeURIComponent(slug);
    if (name)  body += '&name=' + encodeURIComponent(name);
    if (email) body += '&email=' + encodeURIComponent(email);

    fetch('/operator/instances', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': csrf },
      body: body
    })
    .then(r => r.json())
    .then(data => {
      if (data.id) {
        location.href = '/operator/instances/' + data.id;
      } else {
        errorEl.textContent = data.error || 'Creation failed.';
        errorEl.classList.remove('hidden');
        btn.disabled = false;
        btn.innerHTML = originalText;
      }
    })
    .catch(() => {
      errorEl.textContent = 'Request failed. Check your connection.';
      errorEl.classList.remove('hidden');
      btn.disabled = false;
      btn.innerHTML = originalText;
    });
  });
</script>
