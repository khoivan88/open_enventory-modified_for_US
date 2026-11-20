# PHP2Shell (CVE-2026-109618, CVE-2026-109619): what to do for Open Enventory

*Written 2026-10-26.*

Two critical bugs in PHP itself (CVSS 9.2) let an unauthenticated attacker run code on the
server with a single HTTP POST. PHP parses the request body before any script runs, so
**no change to Open Enventory's code can block it**: every `.php` file of every PHP web
application is a way in, including OE's login page. Exploitation in the wild has been
reported since 2026-10-17.

## 1. Update PHP (the actual fix)

Fixed upstream in **8.2.35, 8.3.36, 8.4.27 and 8.5.12** (2026-10-21). PHP 8.1 and older
are out of upstream support; PHP published source backports for 7.4/8.0/8.1, and
distributions ship them in their own packages.

| Install | Update with |
|---|---|
| Ubuntu / Debian | `sudo apt update && sudo apt install --only-upgrade 'php*'` |
| RHEL / Rocky / Alma | `sudo dnf update 'php*'` |
| XAMPP (Windows / Mac) | install the XAMPP release that bundles a fixed PHP, or replace its `php` folder |
| Docker | rebuild the image from a fixed `php:` base image and redeploy |

Then **restart Apache and PHP-FPM**: running workers keep the old code until restarted.

Distribution packages often keep the old version number (e.g. Ubuntu 24.04 stays on
`8.3.6-0ubuntu0.24.04.x`), so `php -v` alone doesn't tell you. Check the package changelog
(`apt changelog php8.3 | grep -i CVE-2026-10961`) or your distribution's advisory. The web
server's PHP can differ from the command-line `php`; OE's *Credits* page (`credits.php`)
shows the version that serves pages.

**CentOS 7** (still mentioned in the old guides) is end of life and its PHP will never get
this fix. Move to a supported distribution.

## 2. Hardening while you patch

These narrow the attack surface but do **not** fully protect an unpatched PHP.

- Copy [`INSTALL/99-open_enventory-security.ini`](../INSTALL/99-open_enventory-security.ini)
  into PHP's `conf.d` directory (paths are in the file) and restart. It turns off
  `session.upload_progress` (OE does not use it), turns off `fatal_error_backtraces`
  (PHP 8.5) and hides the PHP version header. `install_open_enventory.sh` now does this
  for new installs.
- OE's `.htaccess` turns `session.upload_progress` off as well when PHP runs as an Apache
  module (`mod_php`) and `AllowOverride Options` is set. Under PHP-FPM `.htaccess` PHP
  settings do nothing; use the ini file.
- Don't set `memory_limit = -1` and don't put `RLIMIT_AS`/`RLIMIT_DATA` limits on Apache or
  PHP-FPM.
- Most OE servers only need to be reached from the lab or campus network. If yours is
  reachable from the internet, restrict it (firewall, VPN, or Apache `Require ip ...`)
  until PHP is updated.

## 3. Check whether you were hit

Look in Apache's error log (`/var/log/apache2/error.log`, `/var/log/httpd/error_log`, or
`xampp/apache/logs/error.log`) since mid-October 2026 for:

- `Allowed memory size of ... bytes exhausted` together with `Unknown on line 0`
  (memory ran out before any script started), especially a few in a row;
- `AH00052: child pid ... exit signal Segmentation fault`.

Also look for files in OE's folder or `/tmp` that you didn't put there, and for shells or
`wget`/`curl` started by the `www-data`/`apache` user. If you find signs of compromise,
treat the server as compromised: rebuild it, restore the database from a backup made
before the attack, and change the MySQL/MariaDB passwords and the OE user passwords.

## Sources

- [Datadog Security Labs: PHP2Shell](https://securitylabs.datadoghq.com/articles/php2shell-critical-vulnerabilities-in-php/)
- [Tenable FAQ](https://www.tenable.com/blog/php2shell-cve-2026-109618-cve-2026-109619-frequently-asked-questions-about-critical-php-remote)
- [Unit 42 threat brief](https://unit42.paloaltonetworks.com/cve-2026-109618-cve-2026-109619/)
