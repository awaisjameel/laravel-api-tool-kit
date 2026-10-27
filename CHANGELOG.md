# Changelog

## 4.0.0 - 2026-10-27

- Merge upstream master through `79793b2`, preserving fork constant-enum Eloquent casts, naming conventions and unguarded model scaffolding. Merge commit: `d9f559a687803ae8b6cd473043f631459f6d8d4d`.
- Support maintained PHP 8.2-8.5 / Laravel 12-13, current compatible Guzzle and PHPUnit majors. Replace Duster with Pint/Larastan, convert all test/data-provider annotations to attributes, and add PHPStan level 8 analysis of concrete trait consumers.
- Fix prepared Laravel exception rendering, database status/redaction and HTTP header preservation. Hide both database and HTTP 5xx details in production even when debug is accidentally enabled; retain local debug details.
- Validate filter DTO boundaries, restrict custom filter dispatch to documented hooks, guard page sizes and preserve pagination query strings.
- Make generation preserve existing source unless forced, use atomic writes and attempt every planned restoration after a failure, reporting any unrestored paths. Replace managed route blocks, reuse create migrations, and keep fork route/table naming consistent with generated tests.
- Fix parameterized column PHP, enum trailing-character corruption, nullable foreign-key ordering and nullable update validation; normalize common type aliases and reject malformed schema fragments.
- Generate working validated controllers with/without FormRequests, directly include response behavior, accept immutable resource dates, and bind factories/relations to configured model namespaces. Restoration now requires POST.
- Preserve native integer/string enum helper types and legacy scalar enum casts without coercion.
- Isolate media disk selections, inspect decoded raster MIME labels, generate correct image extensions and surface storage failures.
- Honor external-service form requests and reject redirects. Use current HTTP-client authorization hooks.
- Fix permission generation's missing template, CSV duplication and restore permission; make ordering transactional and cache invalidation include deletion.
- Preserve unrelated AI workflows/knowledge and instruction text; write quoted YAML descriptions and literal marker replacements. Ship all optional rule/workflow resources and current tool metadata.
- Add generator, migration, media, enum, HTTP, exception, command and installer regressions; verify real no-dev archives, generated CRUD/factories and fresh cached routes on Laravel 12/13.
- Restore PHPUnit discovery for three existing regression methods that lacked test attributes.

See MIGRATION.md for breaking changes. Earlier upstream releases remain documented in upstream Git history.
