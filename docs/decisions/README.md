# Architecture Decision Records (ADRs)

Lightweight records of significant technical decisions in VoxelSwarm.

## Format

Each ADR is a numbered markdown file: `NNNN-title.md`. Once accepted, the number is permanent. Superseded records stay in the folder with a status change, not deleted.

**Disagreement is welcome.** Any ADR with status `Proposed` is explicitly open for challenge. If you think the reasoning is wrong, the trade-offs are misstated, or there's a better alternative, open a PR or issue. The point of writing these down is to make them arguable, not to end the conversation.

## Statuses

| Status | Meaning |
|--------|---------|
| **Proposed** | Open for discussion. Challenge via PR or issue. |
| **Accepted** | Decision is in effect. Implementation may be in progress or complete. |
| **Superseded** | Replaced by a later ADR (linked). Kept for historical context. |
| **Rejected** | Considered and declined. Reasoning preserved. |

## Lifecycle

A `Proposed` ADR stays open for 30 days. After 30 days with no substantive challenge (issue or PR arguing the other side with evidence), the author should flip it to `Accepted`. If a challenge arrives, the ADR stays `Proposed` until the discussion resolves -- either the original reasoning holds and it moves to `Accepted`, or the challenge wins and it moves to `Rejected` or `Superseded`.

Don't let ADRs drift in `Proposed` indefinitely. A decision nobody disagrees with is an accepted decision.

## Index

| # | Title | Status | Date |
|---|-------|--------|------|
| [0001](0001-cpanel-subdomain-vs-full-account.md) | cPanel subdomain vs full-account provisioning | Proposed | 2026-05-11 |
| [0002](0002-content-portability-boundary.md) | Content portability boundary | Proposed | 2026-05-11 |
