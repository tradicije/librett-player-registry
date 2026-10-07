# Architecture

[Srpski](../sr/ARCHITECTURE.md)

Module interface ownership and the acyclic dependency graph are specified in [module contracts](MODULE_CONTRACTS.md); transactions and migrations in [storage](STORAGE_AND_MIGRATIONS.md). These refine the conceptual module list below.

## Non-monolithic design

One installable plugin does not mean one application-wide class or an inseparable implementation. Build independently organized modules with explicit public interfaces, isolated responsibilities and acyclic dependencies. A thin composition root wires them together; WordPress hooks call adapters, which call application use cases.

Initial conceptual modules:

| Module | Responsibility |
| --- | --- |
| RegistryIdentity | Identity, roles, accepted authority metadata |
| Players | Profiles, validation, revisions and publication choices |
| Clubs | Club identities, names/aliases and memberships |
| Publication | Public projection, portable snapshots and accepted change feed |
| Replication | Validated pull, cursors, staging and atomic application |
| Proposals | Replica requests, moderation and outcomes |
| Recovery | Trust transitions and offline-authorized handover |
| Media | Portable media descriptors and bounded transport |

Within each module, separate domain, application ports/use cases, and infrastructure adapters. Small shared value types are allowed; avoid a shared bucket containing unrelated services. Modules cannot reach into another module's internal tables/classes.

## Dependency direction

WordPress UI/REST/storage/media adapters → application ports/use cases → domain rules. Dependency injection occurs in the composition root. Domain rules do not know $wpdb, WP_Post, WP_User, request objects, local filesystem or HTTP transports.

Repository transactions, clock, UUID generation, signature verification, permissions and media access are ports. Keep authorization decisions explicit at use-case boundaries, with the adapter providing authenticated actor context. Use-case validation remains active independently of WordPress forms.

## Storage and hosting

WordPress custom tables use the local table prefix; it is not part of logical identity or export fields. Persistence maps domain values to database rows. Database engines are not automatically interchangeable: a future server requires a compatible repository/transaction/media adapter and migration strategy.

Start with one registry per installation. WordPress multisite isolation and multiple registries per installation are open deployment decisions, not implied support. Activation creates schema and setup state only. Deactivation retains data. Destructive uninstall needs an explicit policy and authorization.

Public profiles use routes/templates over registry repositories; CPTs are not the source of truth. WordPress Media Library attachment IDs are internal adapter references, not portable public IDs.

## Language and standards

Development target is modern supported PHP (PHP 8.5 at design date), with declared compatibility checked before implementation. Use strict types, typed properties/parameters/returns, enums for finite states, validated value objects, immutable DTOs where appropriate, Composer PSR-4 and PHP-FIG PER Coding Style 3.1. Choose features for correctness, not merely because they are new.

WordPress adapters also follow its security/API conventions. Configure separate lint rules if WordPress formatting and core PER style differ; do not contaminate domain types with platform wrappers. Pin maintained tooling once selected. Static analysis and dependency-boundary checks belong in planned CI.

A rich front end, if needed, should use strict TypeScript and separated views/state/API modules. Front-end framework and bundler are not selected yet. Do not add a SPA or distributed microservices solely to claim modularity.

## Planned directory shape

The first slice implements RegistryIdentity under `src/RegistryIdentity/{Domain,Application,Infrastructure}` and shared WordPress composition/migration/UI adapters under `src/Infrastructure/WordPress`. Future module directories are added with their functionality. See implemented scope for the actual boundary.

## References

[PHP support](https://www.php.net/supported-versions.php), [PSR-4](https://www.php-fig.org/psr/psr-4/), [PER Coding Style](https://www.php-fig.org/per/coding-style/), [WordPress custom REST routes](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/).
