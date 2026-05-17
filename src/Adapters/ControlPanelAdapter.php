<?php

declare(strict_types=1);

namespace Swarm\Adapters;

/**
 * ControlPanelAdapter — Interface for subdomain management.
 *
 * All routing operations go through this interface.
 * Implementations: NginxAdapter, ForgeAdapter, CpanelAdapter, PleskAdapter.
 */
interface ControlPanelAdapter
{
    /**
     * Create routing for a new subdomain pointing to documentRoot.
     * Must be idempotent — safe to call if subdomain already exists.
     */
    public function createSubdomain(string $slug, string $documentRoot): void;

    /**
     * Remove all routing for a subdomain.
     * Must be idempotent — safe to call if subdomain doesn't exist.
     */
    public function removeSubdomain(string $slug): void;

    /**
     * Replace instance routing with a holding page response.
     */
    public function pauseSubdomain(string $slug): void;

    /**
     * Restore instance routing from paused state.
     */
    public function resumeSubdomain(string $slug): void;

    /**
     * Verify adapter can connect and has required permissions.
     * @return array{ok: bool, message: string}
     */
    public function verify(): array;

    /**
     * Add a custom domain pointing to an existing instance's document root.
     * The subdomain must already exist. SSL is provisioned by the adapter
     * if the panel supports automatic certificate issuance.
     * Must be idempotent — safe to call if domain already exists.
     */
    public function addDomain(string $slug, string $domain): void;

    /**
     * Remove a custom domain from an instance.
     * The subdomain remains active.
     * Must be idempotent — safe to call if domain doesn't exist.
     */
    public function removeDomain(string $slug, string $domain): void;
}
