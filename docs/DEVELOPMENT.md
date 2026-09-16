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

The baseline currently holds a single file, `lib_draw_analytics.php`. Because
`php -l` stops at the first error in a file, an error introduced *below* a
pre-existing one in a baselined file is not caught — so keep that list at one
entry rather than letting it grow. Every other file in the repository is checked
for real, even in baseline mode.

Regenerate the baseline (only from a PHP 8 interpreter) with:

```sh
bin/lint-php.sh --update-baseline
```

## Why PHP 7.4 is pinned

`INSTALL/INSTALL.html` records 7.4.15 as the last version tested against this
codebase.

Two syntax-level blockers have been removed, so 408 of the 409 tracked PHP files
now parse under both 7.4 and 8.x:

- `READONLY` used as a bare, never-defined constant (39 uses in 28 files).
  `readonly` became a keyword in PHP 8.1, so `READONLY => false` stopped
  parsing. Under PHP 7 an undefined constant evaluates to the string of its own
  name and the E_NOTICE is suppressed by `.htaccess`, so these are now written
  as the `"READONLY"` string literal — identical behaviour on 7.4, and valid on
  8.x.
- Curly-brace string offsets, `$s{$i}` (40 uses in 12 files), rewritten to
  `$s[$i]`. The two forms are the same operation on PHP 5 and 7; the curly form
  was deprecated in 7.4 and removed in 8.0.

What still blocks PHP 8 proper:

- `lib_draw_analytics.php` declares `specImage` extending GD's image type, which
  became the `final` class `GdImage` in PHP 8.0. This needs a real redesign
  (composition instead of inheritance), not a mechanical edit.
- 11 other never-defined uppercase constants are still used as bare words —
  `DEFAULTREADONLY` (144 uses), `SPLITMODE` (62), `TABLEMODE` (24), `VISIBLE`
  (16) and friends, 266 uses in total. These parse on 8.x but are a fatal
  `Error` at runtime there, since PHP 8.0 made undefined constants fatal. They
  are harmless on 7.4.

So the lint being green is not the same as PHP 8 support; it means the source
parses. Runtime compatibility is a separate, larger piece of work.
