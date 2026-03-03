<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Services\TemplateManager;

/**
 * TemplateController — Operator template management.
 *
 * Lists ZIPs, processes them into versioned templates,
 * manages version activation, and handles cleanup.
 */
class TemplateController
{
    /**
     * GET /operator/templates — List ZIPs and prepared versions.
     */
    public static function index(): void
    {
        $manager  = new TemplateManager();
        $zips     = $manager->listZips();
        $versions = $manager->listVersions();
        $active   = $manager->getActiveVersion();
        $flash    = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        Response::view('operator/templates', [
            'zips'      => $zips,
            'versions'  => $versions,
            'active'    => $active,
            'flash'     => $flash,
            'csrfField' => \Swarm\Middleware\Csrf::field(),
        ], 'operator');
    }

    /**
     * POST /operator/templates/process — Process a ZIP file.
     */
    public static function process(): void
    {
        $filename = $_POST['filename'] ?? '';

        if (empty($filename)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'No filename specified.'];
            Response::redirect('/operator/templates');
            return;
        }

        $manager = new TemplateManager();
        $result  = $manager->processZip($filename);

        $_SESSION['flash'] = [
            'type'    => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ];

        Response::redirect('/operator/templates');
    }

    /**
     * POST /operator/templates/activate — Set a version as active.
     */
    public static function activate(): void
    {
        $version = $_POST['version'] ?? '';

        if (empty($version)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'No version specified.'];
            Response::redirect('/operator/templates');
            return;
        }

        $manager = new TemplateManager();
        $result  = $manager->activateVersion($version);

        $_SESSION['flash'] = [
            'type'    => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ];

        Response::redirect('/operator/templates');
    }

    /**
     * DELETE /operator/templates/zip — Delete a ZIP file.
     */
    public static function deleteZip(): void
    {
        $filename = $_POST['filename'] ?? '';

        if (empty($filename)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'No filename specified.'];
            Response::redirect('/operator/templates');
            return;
        }

        $manager = new TemplateManager();
        $result  = $manager->deleteZip($filename);

        $_SESSION['flash'] = [
            'type'    => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ];

        Response::redirect('/operator/templates');
    }

    /**
     * DELETE /operator/templates/version — Delete a prepared version.
     */
    public static function deleteVersion(): void
    {
        $version = $_POST['version'] ?? '';

        if (empty($version)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'No version specified.'];
            Response::redirect('/operator/templates');
            return;
        }

        $manager = new TemplateManager();
        $result  = $manager->deleteVersion($version);

        $_SESSION['flash'] = [
            'type'    => $result['ok'] ? 'success' : 'error',
            'message' => $result['message'],
        ];

        Response::redirect('/operator/templates');
    }
}
