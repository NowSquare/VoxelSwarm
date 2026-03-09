<?php

declare(strict_types=1);

/**
 * VoxelSwarm — Front Controller
 *
 * All requests route through here via .htaccess (Apache) or
 * try_files (Nginx). Loads bootstrap, registers routes, dispatches.
 */

require_once __DIR__ . '/src/bootstrap.php';

use Swarm\Router;
use Swarm\Controllers\LandingController;
use Swarm\Controllers\SignupController;
use Swarm\Controllers\StatusController;
use Swarm\Controllers\AuthController;
use Swarm\Controllers\DashboardController;
use Swarm\Controllers\InstanceController;
use Swarm\Controllers\InstallController;
use Swarm\Controllers\DeploymentController;
use Swarm\Controllers\AccountController;
use Swarm\Controllers\SystemController;
use Swarm\Controllers\TemplateController;

$router = new Router();

// ── Install guard: redirect all requests to /install if not installed ──
$requestUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
$isInstallRoute = str_starts_with($requestUri, '/install');
$isAssetRoute = preg_match('/\.(css|js|png|jpg|svg|ico|woff2?)$/', $requestUri);

if (!isInstalled() && !$isInstallRoute && !$isAssetRoute) {
    header('Location: /install');
    exit;
}

// ── Install wizard (only accessible when not installed) ──
$router->get('/install',               [InstallController::class,  'index']);
$router->post('/install/check',        [InstallController::class,  'check']);
$router->post('/install/test-adapter', [InstallController::class,  'testAdapter']);
$router->post('/install/complete',     [InstallController::class,  'complete']);

// ── Public ──
$router->get('/',                        [LandingController::class,   'index']);
$router->get('/signup',                  [SignupController::class,    'index']);
$router->post('/signup',                 [SignupController::class,    'store'],  ['throttle:signup']);
$router->get('/status/{id}',             [StatusController::class,    'show']);
$router->get('/api/status/{id}',         [StatusController::class,    'json']);

// ── Operator auth ──
$router->get('/operator/login',          [AuthController::class,      'show']);
$router->post('/operator/login',         [AuthController::class,      'store'],  ['throttle:login']);
$router->post('/operator/logout',        [AuthController::class,      'destroy']);

// ── Operator dashboard (session-protected) ──
$router->group(['prefix' => '/operator', 'middleware' => ['auth']], function (Router $r) {
    $r->get('/',                              [DashboardController::class,    'index']);
    $r->get('/instances',                     [InstanceController::class,     'index']);
    $r->post('/instances',                    [InstanceController::class,     'store']);
    $r->get('/instances/{id}',                [InstanceController::class,     'show']);
    $r->patch('/instances/{id}',              [InstanceController::class,     'update']);
    $r->delete('/instances/{id}',             [InstanceController::class,     'destroy']);
    $r->post('/instances/{id}/pause',         [InstanceController::class,     'pause']);
    $r->post('/instances/{id}/resume',        [InstanceController::class,     'resume']);
    $r->get('/templates',                     [TemplateController::class,     'index']);
    $r->post('/templates/process',            [TemplateController::class,     'process']);
    $r->post('/templates/activate',           [TemplateController::class,     'activate']);
    $r->post('/templates/delete-zip',         [TemplateController::class,     'deleteZip']);
    $r->post('/templates/delete-version',     [TemplateController::class,     'deleteVersion']);
    $r->get('/deployment',                    [DeploymentController::class,   'index']);
    $r->put('/deployment',                    [DeploymentController::class,   'update']);
    $r->post('/deployment/adapter/test',      [DeploymentController::class,   'testAdapter']);
    $r->post('/deployment/mail/test',         [DeploymentController::class,   'testMail']);
    $r->get('/account',                       [AccountController::class,      'index']);
    $r->put('/account',                       [AccountController::class,      'update']);
    $r->get('/system',                        [SystemController::class,       'index']);
    $r->post('/system/git-pull',              [SystemController::class,       'gitPull']);
    $r->get('/system/logs/download',          [SystemController::class,       'downloadLog']);
    $r->post('/system/logs/delete',           [SystemController::class,       'deleteLog']);
    $r->post('/system/refresh',              [SystemController::class,       'refresh']);
    $r->post('/system/reset',                 [SystemController::class,       'reset']);
});

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
