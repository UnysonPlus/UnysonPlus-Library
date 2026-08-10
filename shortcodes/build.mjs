#!/usr/bin/env node
/**
 * UnysonPlus Shortcodes Library — catalog builder.
 *
 * For every shortcode folder here that carries a `library.json`, this:
 *   1. builds a spec-valid `<slug>/<slug>.zip` payload with Windows bsdtar (forward-slash
 *      paths — NEVER PowerShell Compress-Archive), containing the runtime files with the
 *      slug folder at the zip root (so it extracts to .../shortcodes/shortcodes/<slug>/),
 *      excluding catalog-only files (library.json, thumb.*, the zip itself);
 *   2. computes the payload sha256 + verifies 0 backslash entries;
 *   3. assembles `catalog.json` (the gallery manifest the in-admin Shortcodes Library reads).
 *
 * Run:  node build.mjs
 */
import { execFileSync } from 'node:child_process';
import { readdirSync, readFileSync, writeFileSync, statSync, renameSync, existsSync, rmSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const DIR = dirname(fileURLToPath(import.meta.url));
const TAR = join(process.env.SystemRoot || 'C:\\Windows', 'System32', 'tar.exe');
const BASE_URL = 'https://raw.githubusercontent.com/UnysonPlus/UnysonPlus-Library/master/shortcodes/';

const isDir = (p) => { try { return statSync(p).isDirectory(); } catch { return false; } };
const sha256 = (buf) => createHash('sha256').update(buf).digest('hex');

const slugs = readdirSync(DIR).filter((s) => isDir(join(DIR, s)) && existsSync(join(DIR, s, 'library.json')));
if (!slugs.length) { console.error('No shortcode folders with library.json found.'); process.exit(1); }

const catalog = { version: 1, base_url: BASE_URL, generated: '', shortcodes: {} };

for (const slug of slugs) {
  const meta = JSON.parse(readFileSync(join(DIR, slug, 'library.json'), 'utf8'));
  const zipName = `${slug}.zip`;
  const tmpZip = join(DIR, `.${slug}.build.zip`);   // build outside the slug dir (no self-inclusion)
  const finalZip = join(DIR, slug, zipName);

  if (existsSync(tmpZip)) rmSync(tmpZip);
  // bsdtar: -a picks zip from the extension; slug folder at root, catalog-only files excluded.
  execFileSync(TAR, [
    '-a', '-c', '-f', tmpZip,
    '--exclude', `${slug}/library.json`,
    '--exclude', `${slug}/thumb.*`,
    '--exclude', `${slug}/${zipName}`,
    slug,
  ], { cwd: DIR, stdio: 'pipe' });

  // Verify spec-valid: every entry uses forward slashes (0 backslashes).
  const listing = execFileSync(TAR, ['-tf', tmpZip], { cwd: DIR, encoding: 'utf8' });
  const backslashes = listing.split(/\r?\n/).filter((l) => l.includes('\\')).length;
  if (backslashes > 0) { console.error(`FAIL: ${zipName} has ${backslashes} backslash entries (not spec-valid)`); process.exit(1); }

  if (existsSync(finalZip)) rmSync(finalZip);
  renameSync(tmpZip, finalZip);

  const bytes = readFileSync(finalZip);
  const entryCount = listing.split(/\r?\n/).filter((l) => l.trim() && !l.endsWith('/')).length;

  catalog.shortcodes[slug] = {
    slug,
    title: meta.title || slug,
    description: meta.description || '',
    category: meta.category || 'Uncategorized',
    version: meta.version || '1.0.0',
    min_core: meta.min_core || '',
    requires: Array.isArray(meta.requires) ? meta.requires : [],
    author: meta.author || '',
    thumb: `${slug}/thumb.svg`,
    payload: `${slug}/${zipName}`,
    sha256: sha256(bytes),
    bytes: bytes.length,
  };
  console.log(`  ✓ ${slug}: ${zipName} (${bytes.length} bytes, ${entryCount} files, sha256 ${catalog.shortcodes[slug].sha256.slice(0, 12)}…)`);
}

writeFileSync(join(DIR, 'catalog.json'), JSON.stringify(catalog, null, 2) + '\n');
console.log(`\nWrote catalog.json — ${Object.keys(catalog.shortcodes).length} shortcode(s).`);
