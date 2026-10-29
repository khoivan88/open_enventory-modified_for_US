# Structure search finds no aryl halides after upgrading ("contains" / "is similar to")

Status: diagnosed, reproduced and fixed on a test stack (2026-10-29). Branch `claude/scope-arylhalide-substructure` (based on `claude/eloquent-davinci-l30490`).

## Symptom (as reported by the lab)

Ubuntu 24.04, PHP 8.4.27 (PHP-FPM), Apache 2, MariaDB. They upgraded an existing `develop` (PHP 7) database with root login → `update.php`.

- Structure search **contains** with an aryl halide (Ph‑F, Ph‑Cl, Ph‑Br, Ph‑I) returns nothing. **is similar to** returns nothing too.
- Other aryl compounds (Ar‑O, Ar‑N, Ar‑C, Ar‑S) and alkyl halides work.
- **is exactly** works (Ph‑Cl finds chlorobenzene).

## Root cause

The fingerprints stored in the lab's database are out of date. **There is no bug in the current search algorithm.** The upgrade path never recomputes the fingerprints, and the one link that should have led there was broken in this fork.

1. **The fingerprint code changed upstream.** Felix fixed a bug in `lib_fingerprint.php:59` `addBondToGroup()` in OE‑2022‑09‑07 (`git show d906549 -- lib_fingerprint.php`; that release also bumped `currentVersion` 0.814 → 0.815). The old code wrote the ring‑closing bond to `bondsFromNeighbours[$newIdx][$attachedToAtom]`. Neither variable exists in that function, so in the old code the bond went to `[""][""]` and was lost.
   - `addBondToGroup()` has exactly one caller: the `isPhenylHalogenide` group, `lib_fingerprint.php:1226‑1240`. That group is a benzene ring closed with `addBondToGroup($group,0,5,1.5)` at line 1234, plus a halogen `X`.
   - In the old code that ring never closed, so the group never matched. **Bit `0x2000000` of `fingerprint5` was never set for any molecule.** The new code sets it correctly for every aryl halide.
2. **The search pre‑filter needs stored ⊇ query.** "contains" and "is similar to" build the query molecule with `readMolfile(..., array("forStructureSearch"=>true))` (`lib_db_filter.php:341‑348`). They then filter candidates in SQL with `getFingerprintFilter()` / `getSimilarFilter()` (`lib_db_filter.php:1200‑1222`): `(fingerprintN & q) = q` for every non‑zero query word.
   - The query for any aryl halide now carries `0x2000000` in `fingerprint5`. Every molecule saved by the old code lacks it, so SQL drops every candidate, **including the identical molecule**.
   - "is exactly" compares `smiles_stereo`/`smiles` (`lib_db_filter.php:352‑360`) and never uses fingerprints, which is why it still works.
   - Molecules created *after* the upgrade are saved with the new code (`lib_db_manip_edit.php:1441`, `:1544`) and are found normally. Only old records are affected.
3. **Why nobody was prompted to fix it.** Felix's `update.php` ends with an optional link, "In some cases, the fingerprints … must be recalculated …". It opens the batch tool through `main.php?desired_action=fix_structures`.
   - In this fork, `main.php` has an extra frameset for `use_bootstrap4` (default **on**, `lib_constants_default_settings.php:92`). When Felix's `$frame_url` was merged in, only the non‑bootstrap frameset got it. The bootstrap frameset still hard‑coded `list.php?table=message_in`.
   - So on the lab's settings, that link opened the message inbox instead of the batch tool.

The other change Felix made in this area, the `FP3single()` symmetric‑bond condition at `lib_fingerprint.php:137`, does not matter here. For a query it can only remove bits, never add them. The comparison below shows the bond‑path words `fingerprint6..13` are identical between old and new code for all test molecules.

Confidence: **high.** The bit was identified, the failure was reproduced through the real UI with old‑code fingerprints, and the fix was confirmed through the real UI.

## Evidence

Test stack: this branch, PHP 8.3 CLI + `php -S`, MariaDB 10.11.14, PEAR from `php-pear`, headless Chromium via Playwright. The molfiles are drawn as a user would draw them (Kekulé benzene): chlorobenzene, bromobenzene, fluorobenzene, iodobenzene, 4‑chlorotoluene, anisole (control), 1‑chlorobutane (control).

The full `develop` branch does not parse on PHP 8 (`lib_form_elements.php:116`), so "old code" here means **this branch with only `lib_fingerprint.php` replaced by `develop`'s** (`git show origin/develop:lib_fingerprint.php`). That isolates exactly the code that computes the stored values.

### 1. Fingerprints, old vs new code (`docs/upgrade/arylhalide_repro/fp_repro.php`)

```
$ php fp_repro.php <oe_root>   # stored = readMolfile($mf,array()), query = readMolfile($mf,array("forStructureSearch"=>true))
words that differ between develop's and this branch's lib_fingerprint.php:
chlorobenzene   stored fp5 old=10200000 new=12200000   query fp5 old=10200000 new=12200000
bromobenzene    stored fp5 old=10200000 new=12200000   query fp5 old=10200000 new=12200000
fluorobenzene   stored fp5 old=10200000 new=12200000   query fp5 old=10200000 new=12200000
iodobenzene     stored fp5 old=10200000 new=12200000   query fp5 old=10200000 new=12200000
4-chlorotoluene stored fp5 old=10a00000 new=12a00000   query fp5 old=10a00000 new=12a00000
(anisole, 1-chlorobutane: identical; all other words identical)
```

### 2. Pre‑filter verdict, the same `(stored & q) = q` test as the SQL (`fp_compare.php`)

Stored by old code, query by new code (the lab's situation). Every aryl halide query fails on fp5 `0x2000000` against every molecule, itself included:

```
query \ stored  chlorobenzene     4-chlorotoluene   anisole   1-chlorobutane
chlorobenzene   DROP fp5:2000000  DROP fp5:2000000  DROP ...  DROP ...
anisole         DROP ...          DROP ...          pass      DROP ...
1-chlorobutane  DROP ...          DROP ...          DROP ...  pass
```

Stored and query both by new code (a fresh DB): `chlorobenzene → chlorobenzene, 4-chlorotoluene pass`. Each other aryl halide finds itself. Both old/old (develop) and new/new are consistent; only the mixture fails.

### 3. Through the application (fresh DB `testlab`, root login)

The 7 molecules were added through `editAsync.php` on a second `php -S` serving the old‑`lib_fingerprint.php` tree against the same DB. That leaves exactly the lab's stored state (`fp5 = 0x10200000` for chlorobenzene). Searches go through `list.php?table=molecule&crit0=molecule.molfile_blob&op0=su|si|se` on this branch:

```
              before recompute                     after recompute
su chlorobenzene   ->  (none)                  ->  chlorobenzene 4-chlorotoluene
su bromobenzene    ->  (none)                  ->  bromobenzene
su fluorobenzene   ->  (none)                  ->  fluorobenzene
su iodobenzene     ->  (none)                  ->  iodobenzene
su 4-chlorotoluene ->  (none)                  ->  4-chlorotoluene
su anisole         ->  anisole                 ->  anisole
su 1-chlorobutane  ->  1-chlorobutane          ->  1-chlorobutane
si (same pattern as su)
se all 7           ->  found                   ->  found
```

This matches the lab's report exactly.

### 4. End‑to‑end lab flow in Chromium (Playwright)

Setup: DB aged to `Version = 0.814` with old fingerprints. Steps:

1. Root login redirects to `update.php` ("Update from version 0.814 to version 0.817").
2. Clicking "Perform update" shows the new bold notice (below) and the batch link.
3. The link loads `root_db_man.php?desired_action=fix_structures&auto_fingerprints=true` in `mainpage`, with *molecule, reactants, reagents, products, smiles, fingerprint* and DB `testlab` pre‑selected. **Before the `main.php` fix this frame was `list.php?table=message_in`.**
4. Submitting the form recomputes the fingerprints: `fp5 = 0x12200000` and the like; version is now 0.817.

There were 0 PHP warnings (`error_reporting=E_ALL`) and 0 JS page errors.

## Fix

No change to the fingerprint algorithm: the new code is correct. The fix is to recompute the stored fingerprints once, and to make the upgrade lead people there.

OE already has the tool: **root → Batch operations** (`root_db_man.php?desired_action=fix_structures`, `root_db_man.php:526`). It recomputes `smiles`, `molecule_serialized` and `fingerprint1..15` for `molecule` (lines 741/781‑784) and for `reaction_chemical` (reactants, reagents, products; lines 869/897‑900). It reads the stored molfile and does not change the structure or any other data unless *molfile* / *image* boxes are also ticked.

Prototype changes on this branch:

| File | Change |
|---|---|
| `main.php:60` | The bootstrap frameset now uses `$frame_url` like the other frameset. This makes `main.php?desired_action=fix_structures`, the link on the last `update.php` page, open the batch tool instead of the inbox. |
| `update.php:57‑65` | After performing an update from a version `< 0.815`, prints a bold notice that aryl halide substructure/similarity search will miss existing molecules until fingerprints are recalculated, and asks the user to use the link for all databases. |
| `lib_language_en.php`, `lib_language_de.php` | New string `update_fix_structures_required`. Other languages fall back to English via `l()`. |
| `docs/upgrade/arylhalide_repro/` | `fp_repro.php` and `fp_compare.php`, the CLI scripts used above. |

`php -l` is clean on all touched files (PHP 8.3 locally; the changes are plain string/echo edits with nothing version‑specific).

I considered but did **not** implement a query‑side workaround: masking `0x2000000` out of the query via `getFingerprintFilter()`'s `$ignoreMask`. It would make old data searchable without a recompute. But it permanently weakens the pre‑filter for every aryl halide query and hides stale data instead of fixing it. The recompute is cheap enough.

## What the lab must do on their existing server

1. Deploy this fix, or just log in as root and go to the tool directly, which works without the fix: sidenav **Batch operations**, or `root_db_man.php?desired_action=fix_structures`.
2. In the form:
   - Select **every database** they search ("(select all)").
   - Tick **molecule**, **reactants**, **reagents**, **products**, **SMILES** and **fingerprint**.
   - Leave *image*, *molfile*, *read ext.* and the working‑instruction selects off.
   - Click the save icon.
3. Wait for the page to return. Afterwards, a "contains" search for chlorobenzene should find chlorobenzene and other aryl chlorides. Quick SQL check per database: `SELECT COUNT(*) FROM molecule WHERE fingerprint5 & 33554432;` (0x2000000) goes from 0 to the number of aryl halides.

The run is idempotent: running it twice, or again after an interruption, is harmless. A DB backup beforehand is still good practice, because it rewrites `smiles` and `molecule_serialized` too. Those are regenerated from the same molfile by the same code that saves new molecules.

## Runtime / risk

- **Measured:** 7 168 molecules in **33 s** through the web tool, about 4.7 ms per molecule for small molecules. CLI benchmark: about 4 ms (14 heavy atoms) to 6.5 ms (22 heavy atoms) per molecule for `readMolfile` + serialisation.
- **Estimate for drug‑like inventories,** allowing 5–15 ms per molecule including the UPDATE: **10k molecules ≈ 1–3 min, 50k ≈ 4–12 min**, plus reaction components if they use the lab journal (same cost per component). Large ring‑rich structures are slower.
- **Web timeouts:** the request produces no output until it finishes. PHP's own limit is lifted (`set_time_limit(0)` / per‑reaction `set_time_limit(180)`). But Apache's `Timeout` / `ProxyTimeout` (Ubuntu default 300 s) can make the browser show a 504 on a large DB, while PHP‑FPM keeps working (FPM's `request_terminate_timeout` defaults to 0). Check completion with the SQL above, or rerun. For more than ~30k molecules, raising `ProxyTimeout` temporarily is the simple option. If it becomes a recurring need, a small root‑only CLI wrapper around the same loop would be the next step; it was not needed here.
- **Risk of the code change:** low. One frame URL (`main.php`) and one conditional message (`update.php`). The default landing frame is unchanged (verified: `mainpage` is still `list.php?table=message_in&…&sess_proof=…`).
- **Not tested:** the reaction‑component loop with real reactions (the test DB had none). It is the same code pattern as the molecule loop and ran without errors on the empty table.

## Effort

- Lab: one root action, a few minutes per database.
- Code: done (8 lines). Merge into `claude/eloquent-davinci-l30490` when convenient. The `main.php` fix is worth having anyway, because that link was broken for every bootstrap install.
- The same issue affects any upstream install upgrading across 0.814 → 0.815. Worth mentioning to Felix, since his `update.php` presents the recalculation as optional.
