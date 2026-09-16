#!/usr/bin/env bash
#
# SessionStart hook for Claude Code on the web.
#
# There is nothing to "install" for this project -- it is a plain PHP/MariaDB
# application with no package manifest. What this hook does instead is work out
# which syntax check is actually trustworthy in the current container and say
# so, because the answer differs between a cloud session and a laptop.
set -uo pipefail

# Only meaningful in the remote container; a local checkout has the real stack.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
    exit 0
fi

cd "${CLAUDE_PROJECT_DIR:-$(dirname "${BASH_SOURCE[0]}")/../..}" || exit 0

php_version() {
    "$1" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null
}

# Prefer a 7.x interpreter if one ever becomes available in the image.
OE_PHP=""
for candidate in php7.4 php7.3 php7.2 php; do
    command -v "$candidate" >/dev/null 2>&1 || continue
    version="$(php_version "$candidate")"
    case "$version" in
        7.*) OE_PHP="$(command -v "$candidate")"; break ;;
        *)   [ -z "$OE_PHP" ] && OE_PHP="$(command -v "$candidate")" ;;
    esac
done

if [ -z "$OE_PHP" ]; then
    echo "open-enventory: no php interpreter in this container; bin/lint-php.sh will not run."
    exit 0
fi

version="$(php_version "$OE_PHP")"
if [ -n "${CLAUDE_ENV_FILE:-}" ]; then
    echo "export OE_PHP=\"$OE_PHP\"" >> "$CLAUDE_ENV_FILE"
fi

echo "open-enventory: php $version at $OE_PHP"
case "$version" in
    7.*)
        echo "open-enventory: syntax check is authoritative -- run 'bin/lint-php.sh'."
        ;;
    *)
        cat <<'EOF'
open-enventory: this codebase targets PHP 7.4 (see INSTALL/INSTALL.html) and the
  container has PHP 8, which cannot parse its PHP 5/7-era syntax. Run
  'bin/lint-php.sh' -- it diffs against bin/php8-lint-baseline.txt, which is down
  to a single file, so essentially the whole tree is syntax-checked for real.
  Confirm anything important with the authoritative 7.4 check locally:
      docker compose run --rm --no-deps web bin/lint-php.sh
  There is no test suite in this repo; behaviour must be checked by running the
  app against a database (see docs/DEVELOPMENT.md).
EOF
        ;;
esac

exit 0
