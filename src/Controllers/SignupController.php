<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Helpers\Validator;
use Swarm\Middleware\Csrf;
use Swarm\Models\Instance;
use Swarm\Models\Setting;
use Swarm\Services\Provisioner;
use Swarm\Services\SubdomainGenerator;

/**
 * SignupController — Public signup form + provisioning trigger.
 */
class SignupController
{
    /**
     * GET / — Show the signup form.
     */
    public function index(): void
    {
        // Public site must be enabled AND signups must be enabled
        $publicSiteEnabled = Setting::get('public_site_enabled', 'false') === 'true';
        if (!$publicSiteEnabled) {
            Response::redirect('/operator/login');
            return;
        }
        $signupsEnabled = Setting::get('signups_enabled', 'false') === 'true';

        Response::view('signup', [
            'signupsEnabled' => $signupsEnabled,
            'csrfField'      => Csrf::field(),
            'errors'         => Response::flash('errors', []),
            'old'            => Response::flash('old', []),
        ], 'public');
    }

    /**
     * POST /signup — Create a new instance and start provisioning.
     */
    public function store(): void
    {
        Csrf::validate();

        // Check signups enabled
        if (Setting::get('signups_enabled', 'false') !== 'true') {
            Response::json(['error' => 'Signups are currently disabled.'], 403);
        }

        // Validate
        $errors = Validator::validate($_POST, [
            'name'  => 'required|string|min:2|max:80',
            'email' => 'required|email',
        ]);

        if (!empty($errors)) {
            Response::back(['errors' => $errors, 'old' => $_POST]);
        }

        $name  = trim($_POST['name']);
        $email = trim(strtolower($_POST['email']));

        // Check duplicate email
        if (Instance::findByEmail($email)) {
            Response::back([
                'errors' => ['email' => 'This email already has a workspace.'],
                'old'    => $_POST,
            ]);
        }

        // Check max instances
        $maxInstances = (int) Setting::get('max_instances', '100');
        $counts = Instance::countByStatus();
        if ($counts['total'] >= $maxInstances) {
            Response::back([
                'errors' => ['name' => 'We\'ve reached capacity. Please try again later.'],
                'old'    => $_POST,
            ]);
        }

        // Generate slug
        $slug = SubdomainGenerator::generate($name);

        // Create instance record
        $instanceId = Instance::create([
            'slug'   => $slug,
            'name'   => $name,
            'email'  => $email,
            'status' => 'queued',
            'type'   => 'tenant',
        ]);

        // Send redirect immediately, then provision in background
        $statusUrl = "/status/{$instanceId}";

        // Flush the redirect response to the browser
        header("Location: {$statusUrl}");
        http_response_code(302);

        // Close the connection — browser navigates to status page
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } else {
            // Fallback: ensure PHP continues after browser disconnect
            ignore_user_abort(true);
            header('Content-Length: 0');
            header('Connection: close');
            flush();
            if (function_exists('ob_end_flush')) {
                @ob_end_flush();
            }
            flush();
        }

        // Provision in background
        Provisioner::run($instanceId);
    }
}
