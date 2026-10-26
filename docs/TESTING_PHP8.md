# Trying the PHP 8 version of this fork (beta)

*Written 2026-10-26.*

> **Status: not tested on a real server yet.** This version merges Felix Rudolphi's
> releases up to 2026-07-06, ports this fork's own features to PHP 8 and moves the modern UI
> to Bootstrap 5. It has only been tested on a throw-away test setup (PHP 8.4, MariaDB 10.11,
> see [upgrade/TEST_RESULTS.md](upgrade/TEST_RESULTS.md)), never on a lab's production
> server with real data. **Try it on a copy first and keep a backup.**

## Why you need this branch to move to PHP 8

The released version of this fork (`develop` / `master`) only runs on PHP 7. On PHP 8,
at least 36 of its PHP files fail to load (e.g. `READONLY` became a reserved word in PHP 8.1), so
**updating PHP under an existing install breaks Open Enventory**. PHP 7 gets no more upstream
fixes, including for the October 2026 PHP2Shell bugs
([SECURITY_PHP2SHELL.md](SECURITY_PHP2SHELL.md)). The only exception is an OS vendor that
still ships patched PHP 7.4 packages (ask your IT).

The PHP 8 version is on the branch
[`claude/eloquent-davinci-l30490`](https://github.com/khoivan88/open_enventory-modified_for_US/tree/claude/eloquent-davinci-l30490)
until it is merged.

## Requirements

- A supported Linux with a PHP that has the PHP2Shell fixes: e.g. Ubuntu 24.04 LTS,
  Debian 12/13, or Rocky/Alma Linux 9 with a PHP 8.2+ module stream (its default PHP 8.0 is too
  old). PHP 8.2 or newer is recommended (8.1 works but is end of
  life upstream). **Not CentOS 7**: it is end of life and its PHP is 5.4.
- Apache 2.4 with `mod_php` (or PHP-FPM), MariaDB (tested with 10.11; MySQL not tested).
- PHP extensions: `mysqli`, `gd`, `mbstring`, and `php-pear` (needed by the supplier
  price/SDS lookups).

Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 libapache2-mod-php php-mysql php-gd php-mbstring php-pear mariadb-server git
```

## Safest way: upgrade a clone of your server

Open Enventory keeps each user as a MySQL/MariaDB account, so the easiest way to test with
your real data is to **clone the whole server or VM** (a snapshot in your cloud or
virtualization tool) and upgrade the clone. Keep the original running read-only, or
offline if your IT requires that, until the clone works.

On the clone:

1. **Back up** the database and the old program folder:
   ```bash
   sudo mysqldump --all-databases --routines > ~/oe-backup-$(date +%F).sql
   sudo cp -a /var/www/html/oe ~/oe-old-$(date +%F)   # adjust to your install folder
   ```
2. **Upgrade the OS / PHP** to a supported version (above). The old OE will stop working at
   this point; that is expected.
3. **Put the new code in place** (replace `/var/www/html/oe` with your folder):
   ```bash
   cd /var/www/html
   sudo mv oe oe-php7
   sudo git clone -b claude/eloquent-davinci-l30490 https://github.com/khoivan88/open_enventory-modified_for_US.git oe
   ```
   or download the branch as a ZIP from GitHub (*Code > Download ZIP*) and unzip it there.
4. **Copy your local settings** from the old folder if you changed them:
   - `lib_global_settings.php` (`db_server`, `customization`, proxy). Don't copy the whole
     file over the new one; re-apply your changed lines, since the new file has new settings.
   - your `lib_customization.<school>.php`, if you made one;
   - `chemdraw/ChemDraw-JS-License.xml`, if you use ChemDraw JS;
   - anything else you added (logos, uploaded files).
5. **PHP settings**: copy [`INSTALL/99-open_enventory-security.ini`](../INSTALL/99-open_enventory-security.ini)
   into PHP's `conf.d` folder. If PHP runs as PHP-FPM (not `mod_php`), also set
   `memory_limit = 512M`, `upload_max_filesize = 64M` and `post_max_size = 64M` there. With
   `mod_php`, `.htaccess` sets them when Apache has `AllowOverride Options`.
6. Restart: `sudo systemctl restart apache2` (and `php*-fpm` if used).
7. **Log in as `root`** with your database name. OE sends you to `update.php`: check what it
   lists, then confirm the update. It updates the database structure; in our test, user
   accounts and their passwords were kept. Do this once per database (group).
8. Go through the checklist below with the Apache error log open:
   ```bash
   sudo tail -f /var/log/apache2/error.log
   ```

**Rolling back:** restore the VM snapshot, or put the `oe-php7` folder back and reload the
SQL backup on the old PHP version. Once the database is updated, don't point the old PHP 7
version at it.

## Fresh test install (no real data)

Same requirements; then `git clone` as in step 3, open `http://<server>/oe/`, and log in
with a new database name, `root` as user and the MariaDB root password. OE creates the
database.

## What to check

- Log in as a normal user and as `root`; the old and the modern (Bootstrap 5) UI
  (*Settings > Personal settings*).
- Search by name, CAS and barcode; structure search; open, edit and save a container and a
  molecule.
- Add a chemical by CAS (supplier lookup), check that SDS and H/P statements are shown.
- Barcode terminal: borrow, return, move a container to another storage; the guest
  (*External borrow*) account.
- Import / Import and Edit / Delete multiple from an Excel file (try on a few rows first);
  barcode auto-generation; printing labels.
- Date display (*Settings > Date format*).
- Lab journal, if you use it.

## Reporting problems

Open an issue at
<https://github.com/khoivan88/open_enventory-modified_for_US/issues> with what you clicked,
what happened, a screenshot, the PHP version (`php -v`) and the lines that appeared in the
Apache error log at that moment. **Remove passwords and server addresses before posting.**
