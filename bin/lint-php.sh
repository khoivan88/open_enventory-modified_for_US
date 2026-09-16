#!/usr/bin/env bash
#
# Syntax-check the Open Enventory PHP sources.
#
# This codebase targets PHP 7.4 (see INSTALL/INSTALL.html). It uses constructs
# that PHP 8 removed, so a bare `php -l` on a modern interpreter reports errors
# on perfectly good legacy code. The script therefore runs in one of two modes:
#
#   authoritative  A PHP 7.x interpreter was found. Every file must parse.
#   baseline       Only PHP 8.x is available (typical in a cloud session). Files
#                  are still parsed, but the known set of legacy-syntax failures
#                  recorded in bin/php8-lint-baseline.txt is tolerated. Anything
#                  that fails and is *not* in the baseline, or fails differently
#                  than recorded, is reported as an error.
#
# Usage:
#   bin/lint-php.sh                 # check every tracked .php file
#   bin/lint-php.sh a.php b.php     # check specific files
#   bin/lint-php.sh --update-baseline
#   OE_PHP=/path/to/php bin/lint-php.sh
#
# Exit status is 0 when clean, 1 when something needs attention.

set -uo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit 1
BASELINE="bin/php8-lint-baseline.txt"

php_major_minor() {
    "$1" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null
}

# Prefer a real 7.x interpreter; fall back to whatever `php` is.
PHP_BIN=""
for candidate in ${OE_PHP:-} php7.4 php7.3 php7.2 php; do
    [ -n "$candidate" ] || continue
    command -v "$candidate" >/dev/null 2>&1 || continue
    PHP_BIN="$(command -v "$candidate")"
    case "$(php_major_minor "$PHP_BIN")" in
        7.*) break ;;
    esac
done

if [ -z "$PHP_BIN" ]; then
    echo "error: no php interpreter found." >&2
    echo "hint: run the containerised 7.4 instead:" >&2
    echo "      docker compose run --rm --no-deps web bin/lint-php.sh" >&2
    exit 1
fi

PHP_VERSION="$(php_major_minor "$PHP_BIN")"
case "$PHP_VERSION" in
    7.*) MODE="authoritative" ;;
    *)   MODE="baseline" ;;
esac

UPDATE_BASELINE=0
if [ "${1:-}" = "--update-baseline" ]; then
    UPDATE_BASELINE=1
    shift
fi

if [ "$#" -gt 0 ]; then
    FILES=("$@")
else
    mapfile -t FILES < <(git ls-files '*.php' 2>/dev/null)
    if [ "${#FILES[@]}" -eq 0 ]; then
        mapfile -t FILES < <(find . -name '*.php' -not -path './.git/*' | sort)
    fi
fi

# Reduce `php -l` output to a stable "line<TAB>message" signature so a baseline
# entry survives reformatting elsewhere in the file but not a changed error.
signature() {
    local file="$1" out
    out="$("$PHP_BIN" -l "$file" 2>&1)"
    if [ $? -eq 0 ]; then
        return 0
    fi
    printf '%s\n' "$out" \
        | grep -m1 -E '(Parse|Fatal) error' \
        | sed -E 's/^(PHP )?(Parse|Fatal) error: +//; s/ in .* on line ([0-9]+)$/\t\1/' \
        | sed -E 's/\r$//'
    return 1
}

if [ "$UPDATE_BASELINE" -eq 1 ]; then
    if [ "$MODE" = "authoritative" ]; then
        echo "refusing to write a PHP 8 baseline using PHP $PHP_VERSION." >&2
        exit 1
    fi
    {
        echo "# Files that fail 'php -l' under PHP 8 purely because of PHP 5/7-era"
        echo "# syntax this codebase still uses (curly-brace string offsets, 'readonly'"
        echo "# as a bare constant, and similar). They parse correctly under the"
        echo "# supported PHP 7.4. Format: <path>\\t<message>\\t<line>"
        echo "# Regenerate with: bin/lint-php.sh --update-baseline"
    } > "$BASELINE"
    for file in "${FILES[@]}"; do
        [ -f "$file" ] || continue
        if sig="$(signature "$file")"; then continue; fi
        printf '%s\t%s\n' "$file" "$sig" >> "$BASELINE"
    done
    echo "wrote $BASELINE ($(grep -vc '^#' "$BASELINE") entries) using PHP $PHP_VERSION"
    exit 0
fi

declare -A KNOWN=()
if [ "$MODE" = "baseline" ] && [ -f "$BASELINE" ]; then
    while IFS=$'\t' read -r path message line; do
        case "$path" in ''|'#'*) continue ;; esac
        KNOWN["$path"]="$message"$'\t'"$line"
    done < "$BASELINE"
fi

echo "lint: PHP $PHP_VERSION ($MODE mode), ${#FILES[@]} files"
[ "$MODE" = "baseline" ] && echo "lint: tolerating ${#KNOWN[@]} known legacy-syntax files; run under docker compose for an authoritative check"

fail=0
legacy=0
checked=0
for file in "${FILES[@]}"; do
    [ -f "$file" ] || continue
    checked=$((checked + 1))
    if sig="$(signature "$file")"; then
        # Parsed cleanly. If the baseline expected it to fail, it has been
        # fixed -- tell the user so the baseline can shrink.
        if [ -n "${KNOWN[$file]:-}" ]; then
            echo "  fixed    $file (no longer fails; drop it from $BASELINE)"
        fi
        continue
    fi
    expected="${KNOWN[$file]:-}"
    if [ -n "$expected" ] && [ "$expected" = "$sig" ]; then
        legacy=$((legacy + 1))
        continue
    fi
    printf '  FAIL     %s\n           %s\n' "$file" "$(printf '%s' "$sig" | tr '\t' ' ')"
    fail=$((fail + 1))
done

echo "lint: $checked checked, $fail failed, $legacy known-legacy skipped"
[ "$fail" -eq 0 ] || exit 1
exit 0
