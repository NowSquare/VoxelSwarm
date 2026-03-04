<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Middleware\Csrf;
use Swarm\Models\Setting;

/**
 * AccountController — Operator personal settings (email + password).
 */
class AccountController
{
    /**
     * GET /operator/account — Show the account page.
     */
    public function index(): void
    {
        Response::view('operator/account', [
            'settings'  => Setting::all(),
            'csrfField' => Csrf::field(),
            'flash'     => Response::flash('flash'),
        ], 'operator');
    }

    /**
     * PUT /operator/account — Save account settings.
     */
    public function update(): void
    {
        Csrf::validate();

        // Update operator email
        if (isset($_POST['operator_email'])) {
            Setting::set('operator_email', $_POST['operator_email']);
        }

        // Handle password change
        if (!empty($_POST['new_password'])) {
            $current = $_POST['current_password'] ?? '';
            $hash    = Setting::get('operator_password_hash', '');

            if (!password_verify($current, $hash)) {
                Response::back(['flash' => 'Current password is incorrect.']);
            }

            if (strlen($_POST['new_password']) < 8) {
                Response::back(['flash' => 'New password must be at least 8 characters.']);
            }

            Setting::set('operator_password_hash', password_hash($_POST['new_password'], PASSWORD_BCRYPT));

            \Swarm\Logger::info('swarm', 'Operator password changed');
        }

        \Swarm\Logger::info('swarm', 'Account settings updated');

        Response::back(['flash' => 'Account saved.']);
    }
}
