<?php
/**
 * Settings page — operator configuration.
 */
$pageTitle = 'Settings — VoxelSwarm';
$s  = $settings; // shorthand
$ac = \Swarm\Models\Setting::getJson('adapter_config', []);
$mc = \Swarm\Models\Setting::getJson('mail_config', []);
function sv(array $arr, string $key): string {
    return htmlspecialchars($arr[$key] ?? '');
}

$inputClass = "block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow";
$labelClass = "block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5";
?>

<div class="mb-8">
  <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Settings</h1>
  <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Configure your orchestration layer and control panel integration.</p>
</div>

<?php if (!empty($flash)): ?>
  <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 text-green-700 dark:text-green-400 text-sm font-medium">
    <?= htmlspecialchars($flash) ?>
  </div>
<?php endif; ?>

<form method="POST" action="/operator/settings" class="space-y-6">
  <?= $csrfField ?>
  <input type="hidden" name="_method" value="PUT">

  <!-- General -->
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">General Settings</h2>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="<?= $labelClass ?>" for="base_domain">Base Domain</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
          </div>
          <input class="<?= $inputClass ?> pl-10" type="text" id="base_domain" name="base_domain"
                 value="<?= htmlspecialchars($s['base_domain'] ?? '') ?>" placeholder="voxelsite.com">
        </div>
      </div>
      <div>
        <label class="<?= $labelClass ?>" for="operator_email">Operator Email</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
          </div>
          <input class="<?= $inputClass ?> pl-10" type="email" id="operator_email" name="operator_email"
                 value="<?= htmlspecialchars($s['operator_email'] ?? '') ?>">
        </div>
      </div>
      <div>
        <label class="<?= $labelClass ?>" for="max_instances">Max Instances</label>
        <div class="relative">
           <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-zinc-400">
             <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
           </div>
           <input class="<?= $inputClass ?> pl-10" type="number" id="max_instances" name="max_instances"
                  value="<?= htmlspecialchars($s['max_instances'] ?? '100') ?>" min="1">
        </div>
      </div>
      <div>
        <label class="<?= $labelClass ?>">Features</label>
        <div class="space-y-3 pt-2">
          <label class="flex items-center gap-3 cursor-pointer group">
            <input type="checkbox" name="public_site_enabled" value="true" class="sw-checkbox"
                   <?= ($s['public_site_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
            <div>
              <span class="text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">Public Site Enabled</span>
              <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-0.5">Show the marketing page and signup form at the root URL. When off, visitors are redirected to operator login.</p>
            </div>
          </label>
          <label class="flex items-center gap-3 cursor-pointer group">
            <input type="checkbox" name="signups_enabled" value="true" class="sw-checkbox"
                   <?= ($s['signups_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
            <span class="text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">Public Signups Enabled</span>
          </label>
          <label class="flex items-center gap-3 cursor-pointer group">
            <input type="checkbox" name="gallery_enabled" value="true" class="sw-checkbox"
                   <?= ($s['gallery_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
            <span class="text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors">Public Demo Gallery Enabled</span>
          </label>
        </div>
      </div>
    </div>
  </div>

  <!-- Control Panel -->
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Control Panel Integration</h2>
      <button type="button" id="btn-test-adapter" onclick="testAdapter()" 
              class="sw-btn-secondary px-3 py-1.5 text-xs">
        Test Connection
      </button>
    </div>
    
    <div class="p-6">
      <div class="max-w-md mb-6">
        <label class="<?= $labelClass ?>" for="control_panel_adapter">Adapter</label>
        <select class="<?= $inputClass ?> sw-select" id="control_panel_adapter" name="control_panel_adapter" onchange="toggleAdapterFields()">
          <option value="local" <?= ($s['control_panel_adapter'] ?? '') === 'local' ? 'selected' : '' ?>>Filesystem (Local / Custom path)</option>
          <option value="nginx" <?= ($s['control_panel_adapter'] ?? '') === 'nginx' ? 'selected' : '' ?>>Nginx (Direct Configuration)</option>
          <option value="forge" <?= ($s['control_panel_adapter'] ?? '') === 'forge' ? 'selected' : '' ?>>Laravel Forge via API</option>
          <option value="cpanel" <?= ($s['control_panel_adapter'] ?? '') === 'cpanel' ? 'selected' : '' ?>>cPanel / WHM via API</option>
          <option value="plesk" <?= ($s['control_panel_adapter'] ?? '') === 'plesk' ? 'selected' : '' ?>>Plesk via API</option>
        </select>
      </div>

      <!-- Local/Filesystem fields -->
      <div id="adapter-local" class="adapter-fields hidden grid grid-cols-1 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">Instances Root Path</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[instances_root]" placeholder="<?= SWARM_STORAGE ?>/instances" value="<?= sv($ac, 'instances_root') ?>">
          <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">Absolute path where instance directories are created. Leave empty for the default (<code class="text-xs bg-zinc-100 dark:bg-zinc-900 px-1 py-0.5 rounded">storage/instances</code>). No subdomain or web server configuration is performed — use this for testing or custom setups.</p>
        </div>
      </div>

      <!-- Nginx fields -->
      <div id="adapter-nginx" class="adapter-fields hidden grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">Nginx Conf Directory</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[conf_dir]" placeholder="/etc/nginx/conf.d" value="<?= sv($ac, 'conf_dir') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">Reload Command</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[reload_cmd]" placeholder="nginx -t && systemctl reload nginx" value="<?= sv($ac, 'reload_cmd') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">SSL Certificate Path (Wildcard)</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[ssl_cert_path]" placeholder="/etc/ssl/certs/wildcard.pem" value="<?= sv($ac, 'ssl_cert_path') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">SSL Key Path (Wildcard)</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[ssl_key_path]" placeholder="/etc/ssl/private/wildcard.key" value="<?= sv($ac, 'ssl_key_path') ?>">
        </div>
      </div>

      <!-- Forge fields -->
      <div id="adapter-forge" class="adapter-fields hidden grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">API Token</label>
          <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_token]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_token') ?>">
          <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">Create a token in Forge Account Settings</p>
        </div>
        <div>
          <label class="<?= $labelClass ?>">Server ID</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[server_id]" placeholder="123456" value="<?= sv($ac, 'server_id') ?>">
          <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">You can find this in the URL of your server dashboard</p>
        </div>
      </div>

      <!-- cPanel fields -->
      <div id="adapter-cpanel" class="adapter-fields hidden grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">WHM Hostname</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[hostname]" placeholder="https://your-server.com:2087" value="<?= sv($ac, 'hostname') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">API Token</label>
          <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_token]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_token') ?>">
        </div>
      </div>

      <!-- Plesk fields -->
      <div id="adapter-plesk" class="adapter-fields hidden grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">Plesk Hostname</label>
          <input class="<?= $inputClass ?>" type="text" name="adapter_config[hostname]" placeholder="https://your-server.com:8443" value="<?= sv($ac, 'hostname') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">API Key</label>
          <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_key]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_key') ?>">
        </div>
      </div>

      <div id="adapter-test-result" class="hidden mt-4 p-3 rounded-lg text-sm font-medium"></div>
    </div>
  </div>

  <!-- Email -->
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Email Configuration</h2>
      <button type="button" onclick="testMail()" 
              class="sw-btn-secondary px-3 py-1.5 text-xs">
        Send Test Email
      </button>
    </div>

    <div class="p-6">
      <div class="max-w-md mb-6">
        <label class="<?= $labelClass ?>" for="mail_driver">Driver</label>
        <select class="<?= $inputClass ?> sw-select" id="mail_driver" name="mail_driver" onchange="toggleMailFields()">
          <option value="log" <?= ($s['mail_driver'] ?? '') === 'log' ? 'selected' : '' ?>>Log Channel (development)</option>
          <option value="smtp" <?= ($s['mail_driver'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP (Production)</option>
          <option value="null" <?= ($s['mail_driver'] ?? '') === 'null' ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>

      <div id="mail-smtp-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50">
        <div>
          <label class="<?= $labelClass ?>">SMTP Host</label>
          <input class="<?= $inputClass ?>" type="text" name="mail_config[host]" placeholder="smtp.gmail.com" value="<?= sv($mc, 'host') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">SMTP Port</label>
          <input class="<?= $inputClass ?>" type="text" name="mail_config[port]" placeholder="587" value="<?= sv($mc, 'port') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">Username</label>
          <input class="<?= $inputClass ?>" type="text" name="mail_config[username]" value="<?= sv($mc, 'username') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">Password</label>
          <input class="<?= $inputClass ?>" type="password" name="mail_config[smtp_password]" placeholder="••••••••••••">
        </div>
        <div>
          <label class="<?= $labelClass ?>">From Address</label>
          <input class="<?= $inputClass ?>" type="email" name="mail_config[from_address]" placeholder="noreply@domain.com" value="<?= sv($mc, 'from_address') ?>">
        </div>
        <div>
          <label class="<?= $labelClass ?>">From Name</label>
          <input class="<?= $inputClass ?>" type="text" name="mail_config[from_name]" placeholder="VoxelSwarm Notification" value="<?= sv($mc, 'from_name') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- Security -->
  <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Security</h2>
    </div>
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="<?= $labelClass ?>">Current Password</label>
        <input class="<?= $inputClass ?>" type="password" name="current_password" placeholder="••••••••">
      </div>
      <div>
        <label class="<?= $labelClass ?>">New Password</label>
        <input class="<?= $inputClass ?>" type="password" name="new_password" placeholder="••••••••" minlength="8">
        <p class="text-xs text-zinc-500 dark:text-zinc-500 mt-1.5">Leave blank if you don't want to change it.</p>
      </div>
    </div>
  </div>

  <div class="pt-2 pb-12 flex justify-end">
    <button type="submit" class="sw-btn-primary px-6 py-2.5">
      Save All Settings
    </button>
  </div>
</form>

<script>
  const csrf = '<?= \Swarm\Middleware\Csrf::token() ?>';

  function toggleAdapterFields() {
    document.querySelectorAll('.adapter-fields').forEach(el => {
      el.classList.add('hidden');
      el.classList.remove('grid');
    });
    const val = document.getElementById('control_panel_adapter').value;
    const target = document.getElementById('adapter-' + val);
    if (target) {
      target.classList.remove('hidden');
      target.classList.add('grid');
    }
  }
  toggleAdapterFields();

  function toggleMailFields() {
    const val = document.getElementById('mail_driver').value;
    const el = document.getElementById('mail-smtp-fields');
    if (val === 'smtp') {
      el.classList.remove('hidden');
      el.classList.add('grid');
    } else {
      el.classList.add('hidden');
      el.classList.remove('grid');
    }
  }
  toggleMailFields();

  function testAdapter() {
    const btn = document.getElementById('btn-test-adapter');
    const originalText = btn.innerHTML;
    btn.disabled = true; 
    btn.innerHTML = '<span class="flex items-center gap-2"><svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Testing...</span>';
    
    fetch('/operator/settings/adapter/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf)
    })
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('adapter-test-result');
      el.classList.remove('hidden');
      el.className = 'mt-4 p-4 rounded-xl text-sm font-medium border ' + (data.ok ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/20' : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20');
      
      const iconSVG = data.ok ? 
          '<svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' :
          '<svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';
      
      el.innerHTML = `<div class="flex gap-3"><div class="mt-0.5">${iconSVG}</div><div>${data.message}</div></div>`;
      
      btn.disabled = false; 
      btn.innerHTML = originalText;
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = originalText; });
  }

  function testMail() {
    fetch('/operator/settings/mail/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf)
    })
    .then(r => r.json())
    .then(data => showToast(data.message, 'info'))
    .catch(() => showToast('Request failed', 'error'));
  }
</script>
