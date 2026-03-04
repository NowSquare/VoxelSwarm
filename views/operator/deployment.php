<?php
/**
 * Deployment page — adapter, domain, instance limits, public site, notifications.
 *
 * This is the system-level configuration: how instances are created,
 * what visitors see, and how the system sends emails.
 */
$pageTitle = 'Deployment — VoxelSwarm';
$s  = $settings;
$ac = \Swarm\Models\Setting::getJson('adapter_config', []);
$mc = \Swarm\Models\Setting::getJson('mail_config', []);

function sv(array $arr, string $key): string {
    return htmlspecialchars($arr[$key] ?? '');
}

$adapter = $s['control_panel_adapter'] ?? 'local';
$usesDomains = in_array($adapter, ['nginx', 'forge', 'cpanel', 'plesk']);

$inputClass = "block w-full rounded-lg border border-zinc-300 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-950 px-3 py-2 text-sm text-zinc-900 dark:text-white placeholder-zinc-400 focus:border-orange-500 focus:outline-none focus:ring-1 focus:ring-orange-500 transition-shadow";
$labelClass = "block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5";
$hintClass  = "text-xs text-zinc-500 dark:text-zinc-500 mt-1.5";
$cardClass  = "bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800/80 rounded-xl shadow-sm dark:shadow-[inset_0_1px_0_rgba(255,255,255,0.05)] overflow-hidden";
$headerClass = "px-6 py-4 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-800/20";
?>

<div class="mb-8">
  <h1 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-white">Deployment</h1>
  <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">How instances are provisioned, what visitors see, and how the system reaches you.</p>
</div>

<?php if (!empty($flash)): ?>
  <div class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 text-green-700 dark:text-green-400 text-sm font-medium">
    <?= htmlspecialchars($flash) ?>
  </div>
<?php endif; ?>

<form method="POST" action="/operator/deployment" class="space-y-6">
  <?= $csrfField ?>
  <input type="hidden" name="_method" value="PUT">

  <!-- ═══════════════════════════════════════════════════════════
       SECTION 1: ADAPTER & INFRASTRUCTURE
       ═══════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Adapter</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">How VoxelSwarm creates instance directories and routes traffic.</p>
      </div>
      <button type="button" id="btn-test-adapter" onclick="testAdapter()"
              class="sw-btn-secondary px-3 py-1.5 text-xs">
        Test Connection
      </button>
    </div>

    <div class="p-6 space-y-6">
      <!-- Adapter selection -->
      <div>
        <label class="<?= $labelClass ?>" for="control_panel_adapter">Control Panel</label>
        <select class="<?= $inputClass ?> sw-select max-w-md" id="control_panel_adapter" name="control_panel_adapter" onchange="onAdapterChange()">
          <option value="local" <?= $adapter === 'local' ? 'selected' : '' ?>>Filesystem (Local)</option>
          <option value="nginx" <?= $adapter === 'nginx' ? 'selected' : '' ?>>Nginx (Direct Config)</option>
          <option value="forge" <?= $adapter === 'forge' ? 'selected' : '' ?>>Laravel Forge</option>
          <option value="cpanel" <?= $adapter === 'cpanel' ? 'selected' : '' ?>>cPanel / WHM</option>
          <option value="plesk" <?= $adapter === 'plesk' ? 'selected' : '' ?>>Plesk</option>
        </select>
      </div>

      <!-- Adapter-specific config panels -->
      <div id="adapter-local" class="adapter-fields hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 space-y-4">
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg> Instances Path</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[instances_root]"
                   placeholder="<?= SWARM_STORAGE ?>/instances" value="<?= sv($ac, 'instances_root') ?>">
            <p class="<?= $hintClass ?>">Where instance directories are created. Leave empty for the default.</p>
          </div>
        </div>
      </div>

      <div id="adapter-nginx" class="adapter-fields hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg> Conf Directory</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[conf_dir]" placeholder="/etc/nginx/conf.d" value="<?= sv($ac, 'conf_dir') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 7-7 7 7"/><path d="M12 19V5"/></svg> Reload Command</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[reload_cmd]" placeholder="nginx -t && systemctl reload nginx" value="<?= sv($ac, 'reload_cmd') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> SSL Certificate</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[ssl_cert_path]" placeholder="/etc/ssl/certs/wildcard.pem" value="<?= sv($ac, 'ssl_cert_path') ?>">
            <p class="<?= $hintClass ?>">Wildcard cert for *.yourdomain.com</p>
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> SSL Key</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[ssl_key_path]" placeholder="/etc/ssl/private/wildcard.key" value="<?= sv($ac, 'ssl_key_path') ?>">
          </div>
        </div>
      </div>

      <div id="adapter-forge" class="adapter-fields hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> API Token</span>
            </label>
            <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_token]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_token') ?>">
            <p class="<?= $hintClass ?>">forge.laravel.com → Account Settings</p>
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg> Server ID</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[server_id]" placeholder="123456" value="<?= sv($ac, 'server_id') ?>">
            <p class="<?= $hintClass ?>">In your Forge server URL</p>
          </div>
        </div>
      </div>

      <div id="adapter-cpanel" class="adapter-fields hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg> WHM Hostname</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[hostname]" placeholder="https://your-server.com:2087" value="<?= sv($ac, 'hostname') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> API Token</span>
            </label>
            <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_token]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_token') ?>">
          </div>
        </div>
      </div>

      <div id="adapter-plesk" class="adapter-fields hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg> Hostname</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" name="adapter_config[hostname]" placeholder="https://your-server.com:8443" value="<?= sv($ac, 'hostname') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21 2-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0 3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg> API Key</span>
            </label>
            <input class="<?= $inputClass ?>" type="password" name="adapter_config[api_key]" placeholder="••••••••••••••••" value="<?= sv($ac, 'api_key') ?>">
          </div>
        </div>
      </div>

      <div id="adapter-test-result" class="hidden p-4 rounded-xl text-sm font-medium border"></div>

      <!-- Base domain — only shown for domain-based adapters -->
      <div id="domain-fields" class="<?= $usesDomains ? '' : 'hidden' ?> border-t border-zinc-100 dark:border-zinc-800/80 pt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="<?= $labelClass ?>" for="base_domain">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg> Base Domain</span>
            </label>
            <input class="<?= $inputClass ?>" type="text" id="base_domain" name="base_domain"
                   value="<?= htmlspecialchars($s['base_domain'] ?? '') ?>" placeholder="voxelsite.com">
            <p class="<?= $hintClass ?>">Instances are created as subdomains, e.g. <code class="text-xs bg-zinc-100 dark:bg-zinc-900 px-1 py-0.5 rounded">demo.<?= htmlspecialchars($s['base_domain'] ?? 'yourdomain.com') ?></code></p>
          </div>
          <div>
            <label class="<?= $labelClass ?>" for="max_instances">
              <span class="inline-flex items-center gap-1.5"><svg class="w-3.5 h-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> Instance Limit</span>
            </label>
            <input class="<?= $inputClass ?>" type="number" id="max_instances" name="max_instances"
                   value="<?= htmlspecialchars($s['max_instances'] ?? '100') ?>" min="1">
            <p class="<?= $hintClass ?>">Signups are blocked at this limit. ~32 MB per instance.</p>
          </div>
        </div>
      </div>

      <!-- Instance limit for non-domain adapters -->
      <div id="local-limit-field" class="<?= $usesDomains ? 'hidden' : '' ?> border-t border-zinc-100 dark:border-zinc-800/80 pt-6">
        <div class="max-w-md">
          <label class="<?= $labelClass ?>" for="max_instances_local">Instance Limit</label>
          <input class="<?= $inputClass ?>" type="number" id="max_instances_local" name="max_instances"
                 value="<?= htmlspecialchars($s['max_instances'] ?? '100') ?>" min="1">
          <p class="<?= $hintClass ?>">New signups are blocked once this limit is reached. Each instance uses ~32 MB.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       SECTION 2: PUBLIC SITE
       ═══════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?>">
      <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Public Site</h2>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">What visitors see at your root URL.</p>
    </div>
    <div class="p-6 space-y-4">
      <label class="flex items-start gap-3 cursor-pointer group">
        <input type="checkbox" name="public_site_enabled" value="true" class="sw-checkbox mt-0.5"
               <?= ($s['public_site_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>
               onchange="toggleSignupsVisibility()">
        <div>
          <span class="text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors font-medium">Show landing page</span>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Display a marketing page at the root URL. When off, visitors go straight to operator login.</p>
        </div>
      </label>
      <div id="signups-toggle" class="ml-8 <?= ($s['public_site_enabled'] ?? 'false') !== 'true' ? 'opacity-40 pointer-events-none' : '' ?> transition-opacity duration-200">
        <label class="flex items-start gap-3 cursor-pointer group">
          <input type="checkbox" name="signups_enabled" value="true" class="sw-checkbox mt-0.5"
                 <?= ($s['signups_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
          <div>
            <span class="text-sm text-zinc-700 dark:text-zinc-300 group-hover:text-zinc-900 dark:group-hover:text-white transition-colors font-medium">Accept signups</span>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Let visitors create their own workspace. When off, the landing page shows a "coming soon" message.</p>
          </div>
        </label>
      </div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       SECTION 3: NOTIFICATIONS
       ═══════════════════════════════════════════════════════════ -->
  <div class="<?= $cardClass ?>">
    <div class="<?= $headerClass ?> flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">Notifications</h2>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Welcome emails and failure alerts.</p>
      </div>
      <button type="button" onclick="testMail()"
              class="sw-btn-secondary px-3 py-1.5 text-xs">
        Send Test Email
      </button>
    </div>
    <div class="p-6 space-y-6">
      <div class="max-w-md">
        <label class="<?= $labelClass ?>" for="mail_driver">Email Driver</label>
        <select class="<?= $inputClass ?> sw-select" id="mail_driver" name="mail_driver" onchange="toggleMailFields()">
          <option value="log"  <?= ($s['mail_driver'] ?? '') === 'log' ? 'selected' : '' ?>>Log to file (development)</option>
          <option value="smtp" <?= ($s['mail_driver'] ?? '') === 'smtp' ? 'selected' : '' ?>>SMTP</option>
          <option value="null" <?= ($s['mail_driver'] ?? '') === 'null' ? 'selected' : '' ?>>Disabled</option>
        </select>
      </div>

      <div id="mail-smtp-fields" class="hidden">
        <div class="bg-zinc-50 dark:bg-zinc-950 p-5 rounded-xl border border-zinc-200 dark:border-zinc-800/50 grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="<?= $labelClass ?>">SMTP Host</label>
            <input class="<?= $inputClass ?>" type="text" name="mail_config[host]" placeholder="smtp.gmail.com" value="<?= sv($mc, 'host') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">Port</label>
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
            <input class="<?= $inputClass ?>" type="email" name="mail_config[from_address]" placeholder="noreply@yourdomain.com" value="<?= sv($mc, 'from_address') ?>">
          </div>
          <div>
            <label class="<?= $labelClass ?>">From Name</label>
            <input class="<?= $inputClass ?>" type="text" name="mail_config[from_name]" placeholder="VoxelSwarm" value="<?= sv($mc, 'from_name') ?>">
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="pt-2 pb-12 flex justify-end">
    <button type="submit" class="sw-btn-primary px-6 py-2.5">
      Save Deployment Settings
    </button>
  </div>
</form>

<script>
  const csrf = '<?= \Swarm\Middleware\Csrf::token() ?>';

  /* ── Adapter switching ─────────────────────────────────────── */
  function onAdapterChange() {
    const val = document.getElementById('control_panel_adapter').value;
    const usesDomains = ['nginx', 'forge', 'cpanel', 'plesk'].includes(val);

    document.querySelectorAll('.adapter-fields').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById('adapter-' + val);
    if (target) target.classList.remove('hidden');

    document.getElementById('domain-fields').classList.toggle('hidden', !usesDomains);
    document.getElementById('local-limit-field').classList.toggle('hidden', usesDomains);

    const domainLimit = document.getElementById('max_instances');
    const localLimit = document.getElementById('max_instances_local');
    if (usesDomains) {
      domainLimit.value = localLimit.value || domainLimit.value;
      localLimit.disabled = true;
      domainLimit.disabled = false;
    } else {
      localLimit.value = domainLimit.value || localLimit.value;
      domainLimit.disabled = true;
      localLimit.disabled = false;
    }
  }
  onAdapterChange();

  /* ── Public site toggles ───────────────────────────────────── */
  function toggleSignupsVisibility() {
    const publicSite = document.querySelector('input[name="public_site_enabled"]');
    const signupsToggle = document.getElementById('signups-toggle');
    if (publicSite.checked) {
      signupsToggle.classList.remove('opacity-40', 'pointer-events-none');
    } else {
      signupsToggle.classList.add('opacity-40', 'pointer-events-none');
    }
  }

  /* ── Mail driver switching ─────────────────────────────────── */
  function toggleMailFields() {
    const val = document.getElementById('mail_driver').value;
    document.getElementById('mail-smtp-fields').classList.toggle('hidden', val !== 'smtp');
  }
  toggleMailFields();

  /* ── Test adapter connection ───────────────────────────────── */
  function testAdapter() {
    const btn = document.getElementById('btn-test-adapter');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="flex items-center gap-2"><svg class="animate-spin h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Testing…</span>';

    // Serialize current form state so we test the selected adapter, not the saved one
    const adapterName = document.getElementById('control_panel_adapter').value;
    let body = '_token=' + encodeURIComponent(csrf) + '&adapter=' + encodeURIComponent(adapterName);

    // Collect all adapter_config inputs from the currently visible panel
    const activePanel = document.getElementById('adapter-' + adapterName);
    if (activePanel) {
      activePanel.querySelectorAll('input[name^="adapter_config"]').forEach(input => {
        const key = input.name.replace('adapter_config[', 'config[');
        body += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(input.value);
      });
    }

    fetch('/operator/deployment/adapter/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
    .then(r => r.json())
    .then(data => {
      const el = document.getElementById('adapter-test-result');
      el.classList.remove('hidden');
      el.className = 'p-4 rounded-xl text-sm font-medium border ' + (data.ok
        ? 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/20'
        : 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/20');

      const icon = data.ok
        ? '<svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
        : '<svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

      el.innerHTML = `<div class="flex gap-3"><div class="mt-0.5">${icon}</div><div>${data.message}</div></div>`;
      btn.disabled = false;
      btn.innerHTML = originalText;
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = originalText; });
  }

  /* ── Test email ────────────────────────────────────────────── */
  function testMail() {
    fetch('/operator/deployment/mail/test', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: '_token=' + encodeURIComponent(csrf)
    })
    .then(r => r.json())
    .then(data => showToast(data.message, data.ok ? 'success' : 'error'))
    .catch(() => showToast('Request failed', 'error'));
  }
</script>
