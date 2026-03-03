<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Middleware\Csrf;
use Swarm\Models\Setting;
use Swarm\Adapters\AdapterFactory;
use Swarm\Services\Mailer;

/**
 * SettingsController — Operator settings management.
 */
class SettingsController
{
    /**
     * GET /operator/settings — Show the settings page.
     */
    public function index(): void
    {
        Response::view('operator/settings', [
            'settings'  => Setting::all(),
            'csrfField' => Csrf::field(),
            'flash'     => Response::flash('flash'),
        ], 'operator');
    }

    /**
     * PUT /operator/settings — Save settings.
     */
    public function update(): void
    {
        Csrf::validate();

        $fields = [
            'base_domain', 'max_instances', 'public_site_enabled',
            'signups_enabled', 'gallery_enabled', 'operator_email',
            'control_panel_adapter', 'mail_driver',
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

        // Handle password change
        if (!empty($_POST['new_password'])) {
            $current = $_POST['current_password'] ?? '';
            $hash    = Setting::get('operator_password_hash', '');

            if (!password_verify($current, $hash)) {
                Response::back(['flash' => 'Current password is incorrect.']);
            }

            Setting::set('operator_password_hash', password_hash($_POST['new_password'], PASSWORD_BCRYPT));
        }

        // Handle toggle fields (checkboxes)
        foreach (['public_site_enabled', 'signups_enabled', 'gallery_enabled'] as $toggle) {
            if (!isset($_POST[$toggle])) {
                Setting::set($toggle, 'false');
            }
        }

        \Swarm\Logger::info('swarm', 'Settings updated', [
            'fields' => array_keys(array_filter($_POST)),
        ]);

        Response::back(['flash' => 'Settings saved.']);
    }

    /**
     * POST /operator/settings/adapter/test — Test the adapter connection.
     */
    public function testAdapter(): void
    {
        Csrf::validate();

        try {
            $adapter = AdapterFactory::create();
            $result  = $adapter->verify();

            Response::json($result);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /operator/settings/mail/test — Send a test email.
     */
    public function testMail(): void
    {
        Csrf::validate();

        $to = Setting::get('operator_email');
        if (!$to) {
            Response::json(['ok' => false, 'message' => 'No operator email configured.'], 422);
        }

        $ok = Mailer::sendTest($to);

        Response::json([
            'ok'      => $ok,
            'message' => $ok ? 'Test email sent.' : 'Failed to send test email. Check logs.',
        ]);
    }
}
