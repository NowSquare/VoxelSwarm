<?php

declare(strict_types=1);

namespace Swarm\Controllers;

use Swarm\Helpers\Response;
use Swarm\Models\Instance;
use Swarm\Models\Setting;

/**
 * GalleryController — Public demo gallery.
 */
class GalleryController
{
    /**
     * GET /gallery — Show gallery demo instances.
     */
    public function index(): void
    {
        $galleryEnabled = Setting::get('gallery_enabled', 'false') === 'true';

        if (!$galleryEnabled) {
            http_response_code(404);
            echo 'Gallery is not available.';
            return;
        }

        $instances  = Instance::gallery();
        $baseDomain = Setting::get('base_domain', 'localhost');

        Response::view('gallery', [
            'instances'  => $instances,
            'baseDomain' => $baseDomain,
        ], 'public');
    }
}
