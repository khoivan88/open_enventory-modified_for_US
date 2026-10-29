# Lab report items 3 + 4: sidenav collapse button, invisible "User guides" / "Logout" (2026-10-29)

Scope: reproduce, root-cause and prototype fixes for two UI bugs a lab reported on the PHP 8 / Bootstrap 5 build (`claude/eloquent-davinci-l30490`). The lab runs Ubuntu 24.04, PHP 8.4 (FPM), Apache 2 and MariaDB, with the modern UI turned on. Both features worked on `develop` (Bootstrap 4, PHP 7).

Prototype branch: `claude/scope-bs5-nav-issues` (on top of `claude/eloquent-davinci-l30490`). The diff is 4 files and about 25 lines.

## How it was tested

- **Stack:** PHP 8.3 CLI server (`php -S`, `error_reporting=E_ALL`) and MariaDB 10.11. A fresh DB `testlab` was created by logging in as root.
- **User:** `labuser` ("Write access" preset), created through `edit.php?table=person`. The modern UI is on (it is the default).
- **Browser:** headless Chromium via Playwright, at viewport widths 1600, 1280 and 1000 px. Console errors and page errors were collected from every frame.
- **Classic UI:** checked by setting `use_bootstrap4` to 0 in `global_settings`.
- **Framesets checked:** `main.php` (inventory) and `lj_main.php` (lab journal). Both load the sidenav with the same collapse toggle.

The PHP 8.3 vs 8.4 difference does not matter here: both bugs are client-side (JS and CSS only).

---

## Bug 3: the collapse button only works once

> The "collapse" button at the top left of the screen doesn't work properly. Before clicking it the sidenav is normal; after clicking it, the left section becomes tiny. But then clicking the button again does nothing. […] dragging […] clicking the collapse button still doesn't have any functionality.

### Reproduced

| Step | `<frameset id="sideframe">` cols | Topnav `»` button (`#expand-icon`) |
|---|---|---|
| Initial | `262,*` | hidden |
| Click the sidenav toggle | `35,*,0` (a 35 px strip) | hidden |
| Click the toggle again (it is still visible in the strip) | `35,*,0`, no change | hidden |
| Click it 3 more times | `35,*,0` | hidden |

The result is the same at all three widths and in the lab journal (`275,*,0` → `35,*,0`, then stuck). The classic UI is stuck the same way. No JS errors are raised: the click runs, but it does nothing.

| Before: after the 1st click | Before: after the 2nd click |
|---|---|
| ![](img/bs5nav_collapsed_before.png) | ![](img/bs5nav_second_click_before.png) |

### Root cause

The collapse toggle is in `sidenav.php:228`. Its handler is hard-coded to `switchSideframe(false)`, so it can only ever collapse.

On `develop` that was fine, because there was a separate way back:

1. The fork's `lib/sidenav.js` collapsed the frame to **0** px (`fs_obj.cols="0,*,0"`).
2. The topnav's resize listener (`topnav.php:451`, unchanged from `develop`) shows the `»` `#expand-icon` button only when `sidenav.scrollWidth == 0`.
3. That button calls `switchSideframe(true)`.

On this branch, **`lib/sidenav.js:43` collapses to 35 px again** (`fs_obj.cols="35,*,0"`). That is Felix's value. The merge of Felix OE-2022-02-20 (`1d5eb42`, step A) took Felix's `lib/sidenav.js` wholesale and dropped all of the fork's edits to it (`git diff 1d5eb42^1 1d5eb42 -- lib/sidenav.js`). As a result:

- the sidenav ends up 35 px wide, never 0, so the `»` button in the topnav never appears;
- the toggle is still visible in the 35 px strip, but it only collapses;
- Felix's own expand button in the strip (`id="expand"`, toggled by the `visibleObj("expand",…)` calls) was commented out in the fork's `sidenav.php:1295-1297`, so nothing can expand the sidenav again.

This is not a Bootstrap 4 → 5 rename problem. The `data-bs-toggle="collapse"` on `#expand-icon` has no target and plays no part in it.

### Fix (prototype)

Make the sidenav toggle an actual toggle. It is visible in the 35 px strip in every frameset, so this also covers the lab journal, the literature frameset and the classic UI. None of those have a topnav `»` button, so restoring the fork's 0 px collapse would leave them with no way to expand the sidenav.

- **`lib/sidenav.js`:**
  - New `toggleSideframe()`, which calls `switchSideframe(fs_obj.cols!=top.fs_obj_orig)`.
  - `switchSideframe()` now flips the toggle icon between `bi-toggle-on` and `bi-toggle-off`, so the button shows its state. The icon is looked up through the sidenav frame, because the topnav's `»` span also has `id="collapse-icon"`.
- **`sidenav.php:228`:** the toggle's handler changes from `switchSideframe(false)` to `toggleSideframe()`.
- **`topnav.php` resize listener:** return early when `#expand-icon` is missing.
  - The classic topnav has no `#expand-icon`. The listener already threw `Cannot read properties of null (reading 'style')` once per page load there. With a working toggle it would throw on every collapse and expand.
  - Behaviour is unchanged in the BS5 UI.

The topnav `»` path still works: when the user drags the sidenav to 0 px, `»` appears, and clicking it restores `262,*` (verified).

### Verified (after the fix)

| UI / frameset | Toggle clicked 4× | Console errors |
|---|---|---|
| BS5, `main.php`, at 1600, 1280 and 1000 px | `262,*` → `35,*,0` → `262,*` → `35,*,0` → `262,*`; icon goes off/on/off/on | 0 |
| BS5, `lj_main.php` | `275,*,0` → `35,*,0` → `275,*,0` → `35,*,0` → `275,*,0` | 0 (DYMO probe excluded) |
| BS5, sidenav dragged to 0 px | `»` visible; clicking it gives `262,*` | 0 |
| Classic, `main.php` and `lj_main.php` | same toggling | 0 (was 1 before) |

| After: collapsed (toggle shows "off") | After: 2nd click expands again |
|---|---|
| ![](img/bs5nav_collapsed_after.png) | ![](img/bs5nav_second_click_after.png) |

---

## Bug 4: "User guides" and "Logout" are white text off the black bar

> The "User guide" and "Logout" buttons at the top right of the screen are now almost invisible, since they are in white text that no longer sits on the black background.

### Reproduced

Measured in the topnav frame (the frame is 1336 px wide at a 1600 px viewport, and 1016 px wide at 1280):

| Viewport | `nav.navbar` box | `a.user_guide` x-position | Background behind the link | Link colour (`navbar-dark`) | Contrast |
|---|---|---|---|---|---|
| 1600 | 0–1336 | **1336**–1403 | page `rgb(237,240,255)` | `rgba(255,255,255,.55)` | **1.07 : 1** |
| 1280 | 0–1016 | **1016**–1083 | page `rgb(237,240,255)` | same | **1.07 : 1** |

The links start exactly where the dark bar ends. They render in white on the light page background, partly cut off on the right edge of the frame, and the frame gets a horizontal overflow (`scrollWidth` 1471 > 1336).

![before](img/bs5nav_topnav_before.png)

### Root cause

At `topnav.php:183` the left-hand menu is `<ul class="navbar-nav me-auto col-8 nav-fill w-100">`. Bootstrap 4 and Bootstrap 5 handle these classes differently:

| | `.col-8` | Result with `.w-100` (`width:100% !important`) |
|---|---|---|
| Bootstrap 4.3 | `flex: 0 0 66.67%; max-width: 66.67%` | `max-width` caps the width at 66.67%. `w-100` had no effect, and the right-hand `<ul class="ms-auto">` fit next to it. |
| Bootstrap 5.3 | `flex: 0 0 auto; width: 66.67%` (no `max-width`) | `w-100` wins, so the left `<ul>` is 100% wide and cannot shrink (`flex-shrink: 0`). The right `<ul>` is pushed outside the bar. |

Measured: the left `<ul>` is 1336 px wide (100%), and the right `<ul>` starts at x = 1336.

### Fix (prototype)

`topnav.php:183`: drop `w-100`. This restores the Bootstrap 4 layout exactly: the left menu takes 66.67%, and `ms-auto` pins "User guides" and "Logout" to the right end of the bar. The classic UI does not use this code path.

### Verified (after the fix)

| Viewport | Links' x-position | Inside the bar | Background | Contrast |
|---|---|---|---|---|
| 1600 | 1160–1336 | yes | `rgb(33,37,41)` (`bg-dark`) | **5.67 : 1** (passes WCAG AA) |
| 1280 | 840–1016 | yes | `rgb(33,37,41)` | **5.67 : 1** |

The bar is also back to one line: it was 64 px tall before the fix, because "User guides" wrapped.

![after](img/bs5nav_topnav_after.png)

At 1000 px (topnav frame 736 px, below Bootstrap's `md` breakpoint) the links are inside the burger menu, both before and after the fix. Unchanged and pre-existing, the same as on BS4: the opened burger menu is taller than the 100 px topnav frame (`main.php` `rows="100,0,*"`). "User guides" and "Logout" land at y = 160 and 192, so they can only be reached by scrolling inside the topnav frame. That is a separate, optional follow-up.

---

## Risk

- **Bug 4: very low.** One class is removed, in the BS5-only branch of `topnav.php`.
- **Bug 3: low.**
  - `toggleSideframe()` is new, and only the sidenav toggle calls it.
  - `switchSideframe()` is still called the same way by the topnav links and by `»`. The only addition is a null-guarded icon swap.
  - The guard in the topnav listener only skips code that used to throw.
  - Edge case: if the user drags the sidenav wider or narrower without using the toggle, `cols` stays at its last scripted value. One click on the toggle then goes by that value: from the 35 px strip it always expands, otherwise it collapses. That is the expected behaviour.
- **Not covered by this test:** Firefox and Safari. Frameset `cols` handling is long-standing and the same in all engines.

## Effort

The prototype is done. Merging it into the work branch is a few minutes: cherry-pick the commit and re-run `php -l`. A manual check on the lab's server needs about 5 minutes: click the toggle a few times and look at the top-right links.

## Related findings (not fixed here)

The same merge (`1d5eb42`) dropped two more fork edits from `lib/sidenav.js`. Both should be reviewed separately:

1. **Structure search no longer widens the sidenav.** The fork's `expandWidth()` / `resetWidth()` set the sidenav to 350 px when "Structure" was picked as the search criterion. That code is gone.
2. **The date presets in the sidenav search are German-formatted again.** The presets (today, yesterday, this week, this month) call `getGerDate()`, which gives `d.m.yyyy`. The fork had changed them to `getSQLDate()`, which gives `yyyy-m-d`. This was not checked here against the new `date_format` setting or the server-side date parser.
