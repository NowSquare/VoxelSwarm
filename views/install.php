<?php
/**
 * VoxelSwarm — Web Installation Wizard
 *
 * Self-contained page (no layout). Multi-step form powered by Alpine.js.
 * Steps: System Check → Configuration → Adapter → Complete
 */
$pageTitle = 'Install — VoxelSwarm';
?>
<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="/fonts/inter/inter.css">
  <link rel="stylesheet" href="/build/swarm.css">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    :root { --sw-bg: #09090B; --sw-surface: #18181B; --sw-border: #27272A; --sw-text: #FAFAFA; --sw-text-secondary: #A1A1AA; --sw-accent: #F97316; --sw-accent-hover: #EA580C; }
    body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--sw-bg); color: var(--sw-text); min-height: 100vh; display: flex; flex-direction: column; }
    .install-card { background: var(--sw-surface); border: 1px solid var(--sw-border); border-radius: 16px; box-shadow: 0 0 0 1px rgba(255,255,255,0.03), 0 8px 40px rgba(0,0,0,0.4); }
    .install-input { display: block; width: 100%; border-radius: 10px; border: 1px solid var(--sw-border); background: var(--sw-bg); padding: 10px 14px; font-size: 14px; color: var(--sw-text); outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
    .install-input:focus { border-color: var(--sw-accent); box-shadow: 0 0 0 3px rgba(249,115,22,0.15); }
    .install-input::placeholder { color: #52525B; }
    .install-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23A1A1AA' viewBox='0 0 16 16'%3E%3Cpath d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
    .install-label { display: block; font-size: 13px; font-weight: 500; color: #A1A1AA; margin-bottom: 6px; }
    .install-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.15s; }
    .install-btn-primary { background: var(--sw-accent); color: #fff; }
    .install-btn-primary:hover { background: var(--sw-accent-hover); }
    .install-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
    .install-btn-secondary { background: transparent; color: var(--sw-text-secondary); border: 1px solid var(--sw-border); }
    .install-btn-secondary:hover { color: var(--sw-text); border-color: #3F3F46; }
    .check-pass { color: #22C55E; }
    .check-fail { color: #EF4444; }
    .check-warn { color: #EAB308; }
    .step-dot { width: 10px; height: 10px; border-radius: 50%; transition: all 0.3s; }
    .step-dot.active { background: var(--sw-accent); box-shadow: 0 0 12px rgba(249,115,22,0.4); }
    .step-dot.done { background: #22C55E; }
    .step-dot.pending { background: #3F3F46; }
    .install-hint { font-size: 12px; color: #52525B; margin-top: 4px; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .spinner { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.2); border-top-color: #fff; border-radius: 50%; animation: spin 0.6s linear infinite; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .step-content { animation: fadeIn 0.3s ease-out; }
  </style>
</head>
<body class="antialiased">

<div class="flex-1 flex items-center justify-center px-4 py-12" x-data="installWizard()">

  <div class="w-full max-w-xl">

    <!-- Logo / Header -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center gap-3 mb-4">
        <svg class="w-10 h-10 text-orange-500" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
          <rect x="4" y="4" width="14" height="14" rx="3" fill="currentColor" opacity="0.9"/>
          <rect x="22" y="4" width="14" height="14" rx="3" fill="currentColor" opacity="0.6"/>
          <rect x="4" y="22" width="14" height="14" rx="3" fill="currentColor" opacity="0.6"/>
          <rect x="22" y="22" width="14" height="14" rx="3" fill="currentColor" opacity="0.3"/>
        </svg>
        <span class="text-2xl font-bold tracking-tight">VoxelSwarm</span>
      </div>
      <p class="text-sm text-zinc-400">Setup wizard · <span x-text="'Step ' + step + ' of 3'"></span></p>
    </div>

    <!-- Step indicators -->
    <div class="flex items-center justify-center gap-3 mb-8">
      <template x-for="s in 3" :key="s">
        <div class="step-dot" :class="s < step ? 'done' : (s === step ? 'active' : 'pending')"></div>
      </template>
    </div>

    <!-- Step 1: System Check -->
    <div class="install-card" x-show="step === 1" x-transition>
      <div class="px-6 py-5 border-b border-zinc-800">
        <h2 class="text-lg font-semibold">System Requirements</h2>
        <p class="text-sm text-zinc-400 mt-1">Checking your server environment.</p>
      </div>

      <div class="p-6 step-content">
        <!-- Loading state -->
        <div x-show="checking" class="flex items-center justify-center py-8 gap-3">
          <div class="spinner"></div>
          <span class="text-sm text-zinc-400">Checking requirements...</span>
        </div>

        <!-- Results -->
        <div x-show="!checking && checks.length > 0" class="space-y-2.5">
          <template x-for="check in checks" :key="check.name">
            <div class="flex items-center justify-between py-2 px-3 rounded-lg" :class="check.status === 'fail' ? 'bg-red-500/5' : ''">
              <div class="flex items-center gap-3">
                <div class="w-5 text-center">
                  <span x-show="check.status === 'pass'" class="check-pass text-sm">✓</span>
                  <span x-show="check.status === 'fail'" class="check-fail text-sm">✗</span>
                  <span x-show="check.status === 'warn'" class="check-warn text-sm">!</span>
                </div>
                <span class="text-sm font-medium" x-text="check.name"></span>
              </div>
              <span class="text-xs text-zinc-500" x-text="check.detail"></span>
            </div>
          </template>
        </div>

        <!-- Error -->
        <div x-show="error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="error"></div>

        <div class="mt-6 flex justify-end">
          <button class="install-btn install-btn-primary" @click="nextStep()" :disabled="!canProceed">
            <span x-show="canProceed">Continue</span>
            <span x-show="!canProceed && !checking">Fix Issues & Retry</span>
            <span x-show="checking">Checking...</span>
            <svg x-show="canProceed" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Step 2: Configuration -->
    <div class="install-card" x-show="step === 2" x-transition>
      <div class="px-6 py-5 border-b border-zinc-800">
        <h2 class="text-lg font-semibold">Configuration</h2>
        <p class="text-sm text-zinc-400 mt-1">Set up your domain, operator account, and control panel.</p>
      </div>

      <div class="p-6 step-content space-y-6">

        <!-- Domain -->
        <div>
          <label class="install-label">Base Domain *</label>
          <input type="text" class="install-input" x-model="form.base_domain" placeholder="swarm.yourdomain.com">
          <p class="install-hint">Instances will be accessible at *.yourdomain.com</p>
        </div>

        <!-- Operator email -->
        <div>
          <label class="install-label">Operator Email *</label>
          <input type="email" class="install-input" x-model="form.operator_email" placeholder="you@example.com">
        </div>

        <!-- Password -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="install-label">Password *</label>
            <input type="password" class="install-input" x-model="form.operator_password" placeholder="Minimum 8 characters">
          </div>
          <div>
            <label class="install-label">Confirm Password *</label>
            <input type="password" class="install-input" x-model="form.operator_password_confirm" placeholder="Repeat password">
          </div>
        </div>

        <!-- Control Panel Adapter -->
        <div>
          <label class="install-label">Control Panel Adapter</label>
          <select class="install-input install-select" x-model="form.adapter">
            <option value="local">Local (Herd / Valet — development)</option>
            <option value="nginx">Nginx (direct VPS)</option>
            <option value="forge">Laravel Forge (API)</option>
            <option value="cpanel">cPanel / WHM (API)</option>
            <option value="plesk">Plesk (API)</option>
            <option value="directadmin" disabled>DirectAdmin (coming soon)</option>
            <option value="cloudpanel" disabled>CloudPanel (coming soon)</option>
            <option value="hestiacp" disabled>HestiaCP (coming soon)</option>
            <option value="cyberpanel" disabled>CyberPanel (coming soon)</option>
          </select>
        </div>

        <!-- Nginx config fields -->
        <div x-show="form.adapter === 'nginx'" x-transition class="space-y-4 p-4 rounded-xl bg-zinc-950 border border-zinc-800">
          <div>
            <label class="install-label">Nginx Conf Directory</label>
            <input type="text" class="install-input" x-model="form.adapter_config.conf_dir" placeholder="/etc/nginx/conf.d">
          </div>
          <div>
            <label class="install-label">Reload Command</label>
            <input type="text" class="install-input" x-model="form.adapter_config.reload_cmd" placeholder="nginx -t && systemctl reload nginx">
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="install-label">SSL Certificate Path</label>
              <input type="text" class="install-input" x-model="form.adapter_config.ssl_cert_path" placeholder="/etc/ssl/certs/wildcard.pem">
            </div>
            <div>
              <label class="install-label">SSL Key Path</label>
              <input type="text" class="install-input" x-model="form.adapter_config.ssl_key_path" placeholder="/etc/ssl/private/wildcard.key">
            </div>
          </div>
        </div>

        <!-- Forge config fields -->
        <div x-show="form.adapter === 'forge'" x-transition class="space-y-4 p-4 rounded-xl bg-zinc-950 border border-zinc-800">
          <div>
            <label class="install-label">Forge API Token</label>
            <input type="password" class="install-input" x-model="form.adapter_config.api_token" placeholder="Your Forge API token">
          </div>
          <div>
            <label class="install-label">Server ID</label>
            <input type="text" class="install-input" x-model="form.adapter_config.server_id" placeholder="123456">
          </div>
        </div>

        <!-- cPanel config fields -->
        <div x-show="form.adapter === 'cpanel'" x-transition class="space-y-4 p-4 rounded-xl bg-zinc-950 border border-zinc-800">
          <div>
            <label class="install-label">WHM Hostname</label>
            <input type="text" class="install-input" x-model="form.adapter_config.hostname" placeholder="https://your-server.com:2087">
          </div>
          <div>
            <label class="install-label">WHM Username</label>
            <input type="text" class="install-input" x-model="form.adapter_config.whm_username" placeholder="root">
            <p class="install-hint">The WHM user that owns the API token. Usually root.</p>
          </div>
          <div>
            <label class="install-label">WHM API Token</label>
            <input type="password" class="install-input" x-model="form.adapter_config.api_token" placeholder="Your WHM API token">
          </div>
        </div>

        <!-- Plesk config fields -->
        <div x-show="form.adapter === 'plesk'" x-transition class="space-y-4 p-4 rounded-xl bg-zinc-950 border border-zinc-800">
          <div>
            <label class="install-label">Plesk Hostname</label>
            <input type="text" class="install-input" x-model="form.adapter_config.hostname" placeholder="https://your-server.com:8443">
          </div>
          <div>
            <label class="install-label">Plesk API Key</label>
            <input type="password" class="install-input" x-model="form.adapter_config.api_key" placeholder="Your Plesk API key">
          </div>
        </div>

        <!-- Email driver -->
        <div>
          <label class="install-label">Email Driver</label>
          <select class="install-input install-select" x-model="form.mail_driver">
            <option value="log">Log (development — writes to storage/logs/)</option>
            <option value="smtp">SMTP (production)</option>
            <option value="null">Disabled</option>
          </select>
        </div>

        <!-- SMTP fields -->
        <div x-show="form.mail_driver === 'smtp'" x-transition class="space-y-4 p-4 rounded-xl bg-zinc-950 border border-zinc-800">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="install-label">SMTP Host</label>
              <input type="text" class="install-input" x-model="form.mail_config.host" placeholder="smtp.gmail.com">
            </div>
            <div>
              <label class="install-label">SMTP Port</label>
              <input type="text" class="install-input" x-model="form.mail_config.port" placeholder="587">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="install-label">Username</label>
              <input type="text" class="install-input" x-model="form.mail_config.username">
            </div>
            <div>
              <label class="install-label">Password</label>
              <input type="password" class="install-input" x-model="form.mail_config.smtp_password">
            </div>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="install-label">From Address</label>
              <input type="email" class="install-input" x-model="form.mail_config.from_address" placeholder="noreply@domain.com">
            </div>
            <div>
              <label class="install-label">From Name</label>
              <input type="text" class="install-input" x-model="form.mail_config.from_name" placeholder="VoxelSwarm">
            </div>
          </div>
        </div>

        <!-- Error -->
        <div x-show="error" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm" x-text="error"></div>

        <div class="flex justify-between pt-2">
          <button class="install-btn install-btn-secondary" @click="prevStep()">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Back
          </button>
          <button class="install-btn install-btn-primary" @click="nextStep()">
            Install VoxelSwarm
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Step 3: Installing / Complete -->
    <div class="install-card" x-show="step === 3" x-transition>
      <div class="px-6 py-5 border-b border-zinc-800">
        <h2 class="text-lg font-semibold" x-text="installing ? 'Installing...' : (installSuccess ? 'Installation Complete' : 'Installation Failed')"></h2>
      </div>

      <div class="p-6 step-content">
        <!-- Installing -->
        <div x-show="installing" class="flex flex-col items-center py-8 gap-4">
          <div class="spinner" style="width: 32px; height: 32px; border-width: 3px;"></div>
          <p class="text-sm text-zinc-400">Setting up VoxelSwarm...</p>
        </div>

        <!-- Success -->
        <div x-show="installSuccess" class="text-center py-6">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-green-500/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <h3 class="text-xl font-bold mb-2">You're all set!</h3>
          <p class="text-sm text-zinc-400 mb-6">VoxelSwarm v<?= SWARM_VERSION ?> is installed and ready.</p>

          <div class="bg-zinc-950 rounded-xl border border-zinc-800 p-4 text-left text-sm space-y-2 mb-6">
            <p class="text-zinc-400"><span class="text-zinc-500 font-medium">Next steps:</span></p>
            <p class="text-zinc-300">1. Upload a VoxelSite ZIP to <code class="text-xs bg-zinc-800 px-1.5 py-0.5 rounded">template/voxelsite/</code></p>
            <p class="text-zinc-300">2. Process it from the Templates page</p>
            <p class="text-zinc-300">3. Provision your first instance</p>
          </div>

          <a :href="redirectUrl || '/operator'" class="install-btn install-btn-primary">
            Open Operator Dashboard
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        </div>

        <!-- Failure -->
        <div x-show="!installing && !installSuccess && step === 3" class="text-center py-6">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-red-500/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <p class="text-sm text-red-400 mb-4" x-text="error"></p>
          <button class="install-btn install-btn-secondary" @click="step = 2; error = ''">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Go Back
          </button>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-xs text-zinc-600 mt-8">VoxelSwarm v<?= SWARM_VERSION ?> · MIT License · <a href="https://github.com/NowSquare/VoxelSwarm" target="_blank" class="hover:text-zinc-400 transition-colors">GitHub</a></p>

  </div>
</div>

<script>
function installWizard() {
  return {
    step: 1,
    checking: true,
    checks: [],
    canProceed: false,
    error: '',
    installing: false,
    installSuccess: false,
    redirectUrl: '',

    form: {
      base_domain: '',
      operator_email: '',
      operator_password: '',
      operator_password_confirm: '',
      adapter: 'local',
      adapter_config: {},
      mail_driver: 'log',
      mail_config: { host: '', port: '587', username: '', smtp_password: '', from_address: '', from_name: 'VoxelSwarm' },
    },

    init() {
      this.runChecks();
    },

    async runChecks() {
      this.checking = true;
      this.error = '';
      try {
        const res = await fetch('/install/check', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        const data = await res.json();
        this.checks = data.checks || [];
        this.canProceed = data.can_proceed || false;
      } catch (e) {
        this.error = 'Failed to check system requirements. Is PHP running correctly?';
      }
      this.checking = false;
    },

    async nextStep() {
      this.error = '';

      if (this.step === 1) {
        if (!this.canProceed) {
          this.runChecks();
          return;
        }
        this.step = 2;
        return;
      }

      if (this.step === 2) {
        // Validate
        if (!this.form.base_domain) { this.error = 'Base domain is required.'; return; }
        if (!this.form.operator_email) { this.error = 'Operator email is required.'; return; }
        if (!this.form.operator_password || this.form.operator_password.length < 8) { this.error = 'Password must be at least 8 characters.'; return; }
        if (this.form.operator_password !== this.form.operator_password_confirm) { this.error = 'Passwords do not match.'; return; }

        // Submit installation
        this.step = 3;
        this.installing = true;

        try {
          const res = await fetch('/install/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(this.form),
          });
          const data = await res.json();

          if (data.ok) {
            this.installSuccess = true;
            this.redirectUrl = data.redirect || '/operator';
          } else {
            this.error = data.error || 'Installation failed.';
            this.installSuccess = false;
          }
        } catch (e) {
          this.error = 'Request failed. Check your server configuration.';
          this.installSuccess = false;
        }
        this.installing = false;
      }
    },

    prevStep() {
      this.error = '';
      if (this.step > 1) this.step--;
    },
  };
}
</script>

</body>
</html>
