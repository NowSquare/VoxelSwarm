<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Middleware\Csrf;
use Swarm\Models\Setting;
use Swarm\Adapters\AdapterFactory;
use Swarm\Services\Mailer;

/**
 * DeploymentController — System-level deployment configuration.
 *
 * Handles adapter config, base domain, instance limits,
 * public site toggles, and email/notification settings.
 */
class DeploymentController
{
    /**
     * GET /operator/deployment — Show the deployment config page.
     */
    public function index(): void
    {
        Response::view('operator/deployment', [
            'settings'  => Setting::all(),
            'csrfField' => Csrf::field(),
            'flash'     => Response::flash('flash'),
        ], 'operator');
    }

    /**
     * PUT /operator/deployment — Save deployment settings.
     */
    public function update(): void
    {
        Csrf::validate();

        $fields = [
            'base_domain', 'max_instances', 'public_site_enabled',
            'signups_enabled', 'control_panel_adapter', 'mail_driver',
        ];

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                Setting::set($field, $_POST[$field]);
            }
        }

        // Handle adapter config (JSON with sensitive fields)
        if (isset($_POST['adapter_config']) && is_array($_POST['adapter_config'])) {
            Setting::setJson('adapter_config', $_POST['adapter_config']);
        }

        // Handle mail config (JSON with sensitive fields)
        if (isset($_POST['mail_config']) && is_array($_POST['mail_config'])) {
            Setting::setJson('mail_config', $_POST['mail_config']);
        }

        // Handle toggle fields (checkboxes — unchecked = not in POST)
        foreach (['public_site_enabled', 'signups_enabled'] as $toggle) {
            if (!isset($_POST[$toggle])) {
                Setting::set($toggle, 'false');
            }
        }

        \Swarm\Logger::info('swarm', 'Deployment settings updated', [
            'fields' => array_keys(array_filter($_POST)),
        ]);

        Response::back(['flash' => 'Deployment settings saved.']);
    }

    /**
     * POST /operator/deployment/adapter/test — Test the adapter connection.
     *
     * Accepts the adapter type and config from the form so the user can
     * test unsaved changes before committing them.
     */
    public function testAdapter(): void
    {
        Csrf::validate();

        try {
            $adapterName = $_POST['adapter'] ?? Setting::get('control_panel_adapter', 'local');
            $config      = isset($_POST['config']) && is_array($_POST['config'])
                ? $_POST['config']
                : Setting::getJson('adapter_config', []);

            $adapter = AdapterFactory::createFrom($adapterName, $config);
            $result  = $adapter->verify();

            Response::json($result);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('adapter', 'Adapter test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /operator/deployment/mail/test — Send a test email.
     */
    public function testMail(): void
    {
        Csrf::validate();

        $to = Setting::get('operator_email');
        if (!$to) {
            Response::json(['ok' => false, 'message' => 'No operator email configured. Set it in Account settings.'], 422);
        }

        try {
            $ok = Mailer::sendTest($to);

            Response::json([
                'ok'      => $ok,
                'message' => $ok ? 'Test email sent to ' . $to . '.' : 'Failed to send test email. Check logs.',
            ]);
        } catch (\Throwable $e) {
            \Swarm\Logger::error('mail', 'Test email failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Response::json(['ok' => false, 'message' => 'Mail error: ' . $e->getMessage()], 500);
        }
    }
}
