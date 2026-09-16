# Development workflow

Open Enventory is a PHP 7.4 / MariaDB application with no package manifest and
no automated test suite. Correctness is established by running the app against a
database, so the workflow below splits the work in two: draft changes wherever
is convenient, verify them somewhere that can actually run the stack.

## The two environments

| | Cloud session | Local checkout |
|---|---|---|
| Edit, search, refactor | yes | yes |
| Syntax check | baseline mode only (see below) | authoritative, via the container |
| Run the app | no | yes, `docker compose up` |
| Real inventory data | no | yes |
| Supplier scrapers (`suppliers/*.php`) | unreliable — datacenter IP behind a proxy | yes |
| Barcode terminal, balance macros, label printers | no | on a lab workstation |

Good cloud work: language files (`lib_language_*.php`), documentation, renames
across `lib_constants_*.php`, reading unfamiliar code, drafting a new supplier
scraper by following the shape of `suppliers/Merck.php`.

Verify locally before trusting: anything in `lib_db_manip*.php`, `lib_import.php`,
molecule and fingerprint handling, PDF/image generation, and any scraper output.

## Running the stack locally

```sh
docker compose up -d --build
# then open http://localhost:8080/
```

Log in with user `root` and the password in `docker-compose.yml`
(`MARIADB_ROOT_PASSWORD`, default `open_enventory_dev`). Open Enventory has no
user table of its own — **login credentials are passed straight to MariaDB**, so
application users are database users.

The working tree is bind-mounted into the container, so edits are live; no
rebuild is needed unless you change `docker/Dockerfile`.

To develop against realistic data, drop a `.sql` or `.sql.gz` dump into
`docker/initdb/` before the first `up`. It is imported when the `db_data` volume
is empty. Dumps are gitignored — do not commit production inventory data.

To start over:

```sh
docker compose down -v      # -v also drops the database volume
```

### Why the web container has no ports of its own

`lib_global_settings.php` hardcodes `define("db_server","localhost")`, and
`lib_global_funcs.php:999` calls `mysqli_connect(db_server, ...)` with that
constant. The database therefore has to answer on `localhost` *inside the web
container*. The compose file uses `network_mode: "service:db"` to put both
containers in one network namespace, which is why `8080` and `3307` are
published by the `db` service.

## Syntax checking

```sh
bin/lint-php.sh                     # all tracked .php files
bin/lint-php.sh edit.php            # specific files
```

The script runs in one of two modes depending on the interpreter it finds.

**Authoritative** — a PHP 7.x binary is available (inside the container, or
natively). Every file must parse:

```sh
docker compose run --rm --no-deps web bin/lint-php.sh
```

**Baseline** — only PHP 8 is available, which is the normal case in a cloud
session. PHP 8 removed constructs this codebase still uses, so a plain `php -l`
reports errors on correct legacy code. The script tolerates exactly the failures
recorded in `bin/php8-lint-baseline.txt` and flags anything else.

Baseline mode has one real limitation worth knowing: `php -l` stops at the first
error in a file, so for a file already in the baseline a *newly introduced* error
further down is not caught. 34 of the 41 baselined files are core application
files. Treat baseline mode as a safety net for the other ~370 files, and run the
authoritative check before merging.

Regenerate the baseline (only from a PHP 8 interpreter) with:

```sh
bin/lint-php.sh --update-baseline
```

## Why PHP 7.4 is pinned

`INSTALL/INSTALL.html` records 7.4.15 as the last version tested against this
codebase. Three things currently block PHP 8, accounting for all 41 entries in
the lint baseline:

- `READONLY` used as a bare, never-defined constant (28 files). `readonly`
  became a keyword in PHP 8.1, so `READONLY => false` no longer parses. Under
  PHP 7 an undefined constant resolves to the string `"READONLY"` and the
  E_NOTICE is suppressed by `.htaccess`, so quoting these is a
  semantics-preserving fix — see the follow-up note below.
- Curly-brace string offsets, `$s{$i}` (12 files). Removed in PHP 8.0;
  `$s[$i]` is equivalent on PHP 5 and 7.
- `lib_draw_analytics.php` declares a class extending GD's image type, which
  became the `final` class `GdImage` in PHP 8.0. This one needs real work, not
  a mechanical edit.

Separately, undefined constants became a fatal `Error` in PHP 8.0, so the
`READONLY` usages are a runtime problem on PHP 8 as well as a parse problem.

### Do not "fix" these locally

The first two items are mechanical, and rewriting them would take the baseline
from 41 files to 1. **Do not do it in this fork.** `docs/upgrade/UPGRADE_PLAN.md`
(on the `claude/charming-brahmagupta-3u32sn` branch) plans a merge of Felix's
upstream PHP 8 release, and upstream resolves both differently:

- Felix renamed the constant to **`READ_ONLY`**, not to a `"READONLY"` string
  literal. Quoting it here would leave our files keying form parameters on a
  string while merged upstream code keys them on Felix's constant. If those two
  values ever differ, read-only form fields silently become editable — a bad
  failure mode for an inventory system, and one no syntax check would catch.
- Felix has already fixed the curly-brace offsets, so they arrive with the
  merge. Rewriting them here only widens the conflict surface in the merge step
  that the plan is explicitly trying to keep small.

Both were implemented on this branch and then reverted for these reasons; see
the revert commit for the working method (token-level rewriting via
`token_get_all`, which lexes files PHP 8 cannot parse) if the same approach is
wanted later against `READ_ONLY`.

Until the merge lands, the baseline stays at 41 and baseline mode keeps its
blind spot on those files. That is the deliberate trade: correctness of the
upgrade path over a tidier lint.
