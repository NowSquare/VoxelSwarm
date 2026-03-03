<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Models\Instance;
use Swarm\Models\Setting;

/**
 * LandingController — Public homepage.
 *
 * By default, redirects to the operator login. When the operator
 * enables the public site via Settings, renders the landing page.
 */
class LandingController
{
    /**
     * GET / — Redirect to operator login, or show the landing page.
     */
    public function index(): void
    {
        // Public site disabled by default — redirect to operator panel
        $publicSiteEnabled = Setting::get('public_site_enabled', 'false') === 'true';

        if (!$publicSiteEnabled) {
            Response::redirect('/operator/login');
            return;
        }

        $signupsEnabled = Setting::get('signups_enabled', 'false') === 'true';
        $counts = Instance::countByStatus();

        // No layout — the landing page is a self-contained document
        Response::view('landing', [
            'signupsEnabled' => $signupsEnabled,
            'totalInstances' => $counts['total'],
            'activeInstances' => $counts['active'],
        ]);
    }
}
