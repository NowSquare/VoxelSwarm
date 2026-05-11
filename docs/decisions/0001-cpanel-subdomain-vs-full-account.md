# ADR-0001: cPanel subdomain vs full-account provisioning

| Field | Value |
|-------|-------|
| **Status** | Proposed |
| **Date** | 2026-05-11 |
| **Author** | VoxelSwarm core |
| **Related** | `src/Adapters/CpanelAdapter.php`, `docs/adapters/cpanel.md` |

## Context

VoxelSwarm provisions isolated website instances for end users. Each instance needs a publicly routable domain, a document root on disk, and (optionally) resource limits.

The cPanel/WHM ecosystem offers two provisioning models:

1. **Subdomain under a single account** — all instances are subdomains of one cPanel user (the operator's account). End users never touch cPanel.
2. **Full cPanel account per instance** — each instance is a separate cPanel user with its own home directory, credentials, and hosting package ("plan").

The question: which model should the cPanel adapter use?

## Current decision

**Subdomain model.** The adapter calls WHM `create_subdomain` to create `{slug}.{baseDomain}` under the operator's cPanel account, pointing the document root to the instance directory.

```php
// CpanelAdapter::createSubdomain()
$this->whmRequest('create_subdomain', [
    'domain'     => $slug,
    'rootdomain' => $this->baseDomain,
    'dir'        => $documentRoot,
]);
```

End users interact only through the VoxelSite front-end. They receive no cPanel login, no FTP credentials, no File Manager access.

## Alternative: full-account provisioning

Switch to WHM `createacct`, which creates a standalone cPanel user per instance. The API accepts a `plan` parameter to assign a predefined hosting package:

```
WHM API: createacct
  username  = {slug}
  domain    = {slug}.{baseDomain}
  plan      = {configured_package_name}
```

The package controls disk quota, bandwidth, email accounts, and — critically — which cPanel features the user can access (File Manager, FTP, SSH, backups, etc.).

Estimated changes (would need a spike to confirm the full scope):

1. **New config field.** Add `cpanel_plan` to adapter settings — touches [`DeploymentController::update()`](../../src/Controllers/DeploymentController.php) (lines 52-53 for storage), [`InstallController`](../../src/Controllers/InstallController.php) (line 239 for install flow), the [deployment view](../../views/operator/deployment.php) (cPanel config panel around line 133), and the [`CpanelAdapter` constructor](../../src/Adapters/CpanelAdapter.php) (line 18).
2. **Rewrite provisioning.** [`CpanelAdapter::createSubdomain()`](../../src/Adapters/CpanelAdapter.php) (line 26) would switch from `create_subdomain` to `createacct`. Callers in [`Provisioner::provision()`](../../src/Services/Provisioner.php) (line 67) and cleanup in [`SignupController`](../../src/Controllers/SignupController.php) (line 185) and [`InstanceController::destroy()`](../../src/Controllers/InstanceController.php) (line 178) would need to handle the new account lifecycle.
3. **Rewrite removal.** [`CpanelAdapter::removeSubdomain()`](../../src/Adapters/CpanelAdapter.php) (line 35) would call `removeacct`, which recursively deletes the system user, home directory, DNS entries, mail, and databases.
4. **Username generation.** cPanel usernames are max 16 chars, alphanumeric, system-wide unique. Current slugs may not comply — needs new validation or a mapping layer.
5. **Credential lifecycle.** `createacct` generates a cPanel login. Unclear whether to store, discard, or expose these. No current infrastructure for per-instance secrets.
6. **Pause/resume.** [`CpanelAdapter::pauseSubdomain()`](../../src/Adapters/CpanelAdapter.php) (line 42) and `resumeSubdomain()` (line 47) currently log warnings. Full-account mode could use `suspendacct` / `unsuspendacct`, but these also affect email and DNS — may be heavier than intended.

## Trade-offs

### Subdomain model (current)

| Aspect | Assessment |
|--------|------------|
| **License protection** | Strong. End users have zero filesystem access. No FTP, no File Manager, no SSH. They cannot download the VoxelSite codebase. |
| **Resource isolation** | Weak. All instances share the parent account's quota and bandwidth. One heavy user affects everyone. |
| **Per-user limits** | Not available. cPanel packages don't apply to subdomains. |
| **Provisioning complexity** | Low. Single API call, no username constraints, no credential management. |
| **Cleanup** | Simple. Delete subdomain, delete directory. No account-level side effects. |
| **Operator control** | Full. The operator is the only cPanel user. Everything runs under their account. |
| **cPanel UI exposure** | None. End users never see cPanel. |

### Full-account model (alternative)

| Aspect | Assessment |
|--------|------------|
| **License protection** | Depends entirely on the assigned plan. A misconfigured plan with File Manager or FTP enabled = users can download the entire codebase and run it without a license. |
| **Resource isolation** | Strong. Each account has its own disk quota, bandwidth cap, and inode limit via the package. |
| **Per-user limits** | Full cPanel package controls. The operator can tier features (basic plan, pro plan, etc.). |
| **Provisioning complexity** | High. Username constraints (16 char max, unique, alphanumeric). Credential lifecycle. Plan must pre-exist on the server. |
| **Cleanup** | Heavier. `removeacct` deletes the system user, DNS records, mail, databases — everything. More things to go wrong. |
| **Operator control** | Shared. Each instance is a real system user. Operators must manage cPanel feature lists and shell access. |
| **cPanel UI exposure** | High risk. Unless the plan explicitly disables it, end users get File Manager, FTP, terminal, backup tools — all of which expose the source code. |

The core tension is that these two models optimise for opposite things. The subdomain model trades resource isolation for simplicity and airtight license protection. The full-account model trades simplicity and default security for granular per-user resource controls. Neither is strictly better — it depends on what the operator values and how much they trust their own cPanel configuration discipline.

## Current reasoning

The subdomain model is the current default for four reasons. These are the arguments to challenge if you think the decision should flip.

1. **License protection is a primary project concern.** An operator selling VoxelSite instances probably does not want users extracting the codebase. The subdomain model eliminates this class of risk by design — there are no credentials to leak, no File Manager to misconfigure. The full-account model makes this security property opt-in (dependent on correct plan configuration), not default.

2. **Configuration discipline is unreliable at scale.** Even with documentation telling operators to disable FTP and File Manager, plans get misconfigured. Hosting providers reset defaults. cPanel updates re-enable features. The subdomain model doesn't have this failure mode.

3. **Resource limits can be enforced at other layers.** PHP `memory_limit`, `max_execution_time`, disk usage monitoring via cron, and VoxelSwarm's own instance limit setting handle the most common resource concerns without needing cPanel-level isolation. This is a weaker argument — application-layer limits are coarser than cPanel packages, and some resources (email accounts, MySQL databases) can't be controlled this way at all.

4. **Complexity budget.** The cPanel adapter is in testing status. Adding `createacct` support means handling username generation, credential storage, plan validation, and a fundamentally different cleanup path. This is a large surface area to get right before the adapter is even stable.

## What would change the decision

- **Operator demand for per-instance resource quotas** that can't be solved at the PHP/application layer (e.g., email accounts, dedicated MySQL limits).
- **A "managed cPanel" mode** where VoxelSwarm provisions accounts but **never exposes cPanel credentials to end users** — using the account purely for resource isolation while keeping all user interaction through VoxelSite's UI. This sidesteps the license risk but adds complexity.
- **Reseller hosting scenarios** where the operator already manages cPanel accounts and wants VoxelSwarm to integrate with their existing provisioning workflow rather than bypass it.

If any of these become concrete requirements, this ADR should be revisited. A possible middle path: offer both modes (`cpanel_mode: subdomain | account`) and let the operator choose, with subdomain as default and account mode requiring explicit plan configuration + a "I understand the risks" acknowledgment.

## Related reading

- [cPanel adapter docs](../adapters/cpanel.md)
- [WHM API: createacct](https://api.docs.cpanel.net/openapi/whm/operation/createacct/)
- [WHM API: create_subdomain](https://api.docs.cpanel.net/openapi/whm/operation/create_subdomain/)
- [Adapter interface](../../src/Adapters/ControlPanelAdapter.php)
