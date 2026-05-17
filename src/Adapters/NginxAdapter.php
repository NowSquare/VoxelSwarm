<?php

declare(strict_types=1);

namespace Swarm\Adapters;

use Swarm\Logger;
use Swarm\Models\Setting;
use Symfony\Component\Process\Process;

/**
 * NginxAdapter — Direct server block management for Nginx.
 *
 * Writes per-instance conf files to the Nginx conf directory,
 * tests config, and reloads. Requires filesystem permissions.
 */
class NginxAdapter implements ControlPanelAdapter
{
    private string $confDir;
    private string $reloadCmd;
    private string $sslCertPath;
    private string $sslKeyPath;
    private string $baseDomain;

    public function __construct(array $config)
    {
        $this->confDir     = $config['conf_dir']      ?? '/etc/nginx/conf.d';
        $this->reloadCmd   = $config['reload_cmd']     ?? 'nginx -t && systemctl reload nginx';
        $this->sslCertPath = $config['ssl_cert_path']  ?? '';
        $this->sslKeyPath  = $config['ssl_key_path']   ?? '';
        $this->baseDomain  = Setting::get('base_domain', 'localhost');
    }

    public function createSubdomain(string $slug, string $documentRoot): void
    {
        $serverName = "{$slug}.{$this->baseDomain}";
        $confPath   = $this->confDir . "/{$slug}.conf";

        $conf = $this->buildServerBlock($serverName, $documentRoot);

        file_put_contents($confPath, $conf);
        Logger::info('adapter', 'Nginx conf written', ['slug' => $slug, 'path' => $confPath]);

        $this->reloadNginx();
    }

    public function removeSubdomain(string $slug): void
    {
        $confPath = $this->confDir . "/{$slug}.conf";

        if (file_exists($confPath)) {
            unlink($confPath);
            Logger::info('adapter', 'Nginx conf removed', ['slug' => $slug]);
            $this->reloadNginx();
        }
    }

    public function pauseSubdomain(string $slug): void
    {
        $serverName = "{$slug}.{$this->baseDomain}";
        $confPath   = $this->confDir . "/{$slug}.conf";

        // Replace with 503 response
        $conf = $this->buildPausedBlock($serverName);
        file_put_contents($confPath, $conf);

        Logger::info('adapter', 'Nginx conf paused', ['slug' => $slug]);
        $this->reloadNginx();
    }

    public function resumeSubdomain(string $slug): void
    {
        // Re-read instance document_root from DB and rebuild
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if ($instance && $instance['document_root']) {
            $this->createSubdomain($slug, $instance['document_root']);
        }
    }

    public function verify(): array
    {
        // Check conf directory exists and is writable
        if (!is_dir($this->confDir)) {
            return ['ok' => false, 'message' => "Nginx conf directory not found: {$this->confDir}"];
        }

        if (!is_writable($this->confDir)) {
            return ['ok' => false, 'message' => "Nginx conf directory not writable: {$this->confDir}"];
        }

        // Check SSL files exist (if configured)
        if ($this->sslCertPath && !file_exists($this->sslCertPath)) {
            return ['ok' => false, 'message' => "SSL certificate not found: {$this->sslCertPath}"];
        }

        if ($this->sslKeyPath && !file_exists($this->sslKeyPath)) {
            return ['ok' => false, 'message' => "SSL key not found: {$this->sslKeyPath}"];
        }

        // Test nginx -t
        $process = Process::fromShellCommandline('nginx -t 2>&1');
        $process->run();

        if (!$process->isSuccessful()) {
            return ['ok' => false, 'message' => 'Nginx config test failed: ' . $process->getOutput()];
        }

        return ['ok' => true, 'message' => 'Nginx adapter verified. Conf dir writable, config valid.'];
    }

    public function addDomain(string $slug, string $domain): void
    {
        $instance = \Swarm\Models\Instance::findBySlug($slug);
        if (!$instance || !$instance['document_root']) {
            throw new \RuntimeException("Cannot add domain: instance '{$slug}' has no document root");
        }

        $confPath = $this->confDir . "/domain-{$slug}.conf";
        $conf = $this->buildServerBlock($domain, $instance['document_root']);

        file_put_contents($confPath, $conf);
        Logger::info('adapter', 'Nginx domain conf written', ['slug' => $slug, 'domain' => $domain, 'path' => $confPath]);

        $this->reloadNginx();

        // Attempt per-domain SSL via Certbot (best-effort)
        $this->certbotDomain($domain, $instance['document_root'], $slug);
    }

    public function removeDomain(string $slug, string $domain): void
    {
        $confPath = $this->confDir . "/domain-{$slug}.conf";

        if (file_exists($confPath)) {
            unlink($confPath);
            Logger::info('adapter', 'Nginx domain conf removed', ['slug' => $slug, 'domain' => $domain]);
            $this->reloadNginx();
        }
    }

    private function buildServerBlock(string $serverName, string $documentRoot): string
    {
        $ssl = '';
        if ($this->sslCertPath && $this->sslKeyPath) {
            $ssl = <<<NGINX
    listen 443 ssl http2;
    ssl_certificate     {$this->sslCertPath};
    ssl_certificate_key {$this->sslKeyPath};
NGINX;
        }

        return <<<NGINX
server {
    listen 80;
    {$ssl}
    server_name {$serverName};
    root {$documentRoot};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~* \.(db|sqlite|sql|sh|env|bak|log)$ {
        deny all;
    }
}
NGINX;
    }

    private function buildPausedBlock(string $serverName): string
    {
        $ssl = '';
        if ($this->sslCertPath && $this->sslKeyPath) {
            $ssl = <<<NGINX
    listen 443 ssl http2;
    ssl_certificate     {$this->sslCertPath};
    ssl_certificate_key {$this->sslKeyPath};
NGINX;
        }

        return <<<NGINX
server {
    listen 80;
    {$ssl}
    server_name {$serverName};

    location / {
        return 503;
    }

    error_page 503 @maintenance;
    location @maintenance {
        default_type text/html;
        return 503 '<html><body style="font-family:Inter,system-ui,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#09090B;color:#FAFAFA;"><div style="text-align:center"><h1 style="font-size:32px;font-weight:700;margin:0;">Under Maintenance</h1><p style="color:#71717A;margin-top:8px;">This site is temporarily paused.</p></div></body></html>';
    }
}
NGINX;
    }

    private function reloadNginx(): void
    {
        $process = Process::fromShellCommandline($this->reloadCmd);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            Logger::error('adapter', 'Nginx reload failed', [
                'output' => $process->getOutput(),
                'error'  => $process->getErrorOutput(),
            ]);
            throw new \RuntimeException('Nginx reload failed: ' . $process->getErrorOutput());
        }

        Logger::info('adapter', 'Nginx reloaded successfully');
    }

    /**
     * Attempt per-domain SSL via Certbot webroot challenge.
     * On success, rewrites the domain's server block with the Let's Encrypt
     * cert paths and reloads Nginx so the certificate is actually served.
     */
    private function certbotDomain(string $domain, string $documentRoot, string $slug): void
    {
        $cmd = sprintf(
            'certbot certonly --webroot -w %s -d %s --non-interactive --agree-tos --register-unsafely-without-email 2>&1',
            escapeshellarg($documentRoot),
            escapeshellarg($domain)
        );

        $process = Process::fromShellCommandline($cmd);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            Logger::warning('adapter', 'Certbot SSL failed for custom domain (non-fatal)', [
                'domain' => $domain,
                'output' => $process->getOutput(),
                'error'  => $process->getErrorOutput(),
            ]);
            return;
        }

        Logger::info('adapter', 'Certbot SSL provisioned for custom domain', ['domain' => $domain]);

        // Wire the Let's Encrypt cert into the domain server block
        $certPath = "/etc/letsencrypt/live/{$domain}/fullchain.pem";
        $keyPath  = "/etc/letsencrypt/live/{$domain}/privkey.pem";

        if (file_exists($certPath) && file_exists($keyPath)) {
            $confPath = $this->confDir . "/domain-{$slug}.conf";
            $conf = $this->buildDomainServerBlock($domain, $documentRoot, $certPath, $keyPath);
            file_put_contents($confPath, $conf);

            Logger::info('adapter', 'Nginx domain conf updated with SSL cert', [
                'domain' => $domain,
                'cert'   => $certPath,
            ]);

            $this->reloadNginx();
        }
    }

    /**
     * Build a server block for a custom domain with per-domain SSL paths.
     * Separate from buildServerBlock() which uses the adapter's static wildcard cert.
     */
    private function buildDomainServerBlock(
        string $serverName,
        string $documentRoot,
        string $sslCertPath,
        string $sslKeyPath
    ): string {
        return <<<NGINX
server {
    listen 80;
    server_name {$serverName};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    ssl_certificate     {$sslCertPath};
    ssl_certificate_key {$sslKeyPath};
    server_name {$serverName};
    root {$documentRoot};
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }

    location ~* \.(db|sqlite|sql|sh|env|bak|log)$ {
        deny all;
    }
}
NGINX;
    }
}
