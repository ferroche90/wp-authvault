/**
 * Build script: create a distributable zip of the WP AuthVault plugin.
 * Run after `npm run minify` so the zip includes minified assets only.
 * Only vendor/autoload.php and vendor/composer/ are included (no dev deps).
 *
 * Before archiving, the Composer autoloader is regenerated with --no-dev
 * so it contains no references to dev packages (phpunit, mockery, etc.).
 * After the zip is written the autoloader is restored for development.
 *
 * Usage: node build.js
 * Output: wp-authvault.zip (plugin root)
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const archiver = require('archiver');

const PLUGIN_SLUG = 'wp-authvault';
const outputPath = path.join(__dirname, PLUGIN_SLUG + '.zip');
const output = fs.createWriteStream(outputPath);
const archive = archiver('zip', {
  zlib: { level: 9 }
});

output.on('close', function () {
  console.log(archive.pointer() + ' total bytes');
  console.log('Plugin archive created successfully: ' + PLUGIN_SLUG + '.zip');

  // Restore full autoloader (with dev) after the zip is written
  try {
    execSync('composer dump-autoload --optimize --quiet', {
      cwd: __dirname,
      stdio: 'inherit'
    });
    console.log('Composer autoloader restored (dev).');
  } catch (_) {
    console.warn('Warning: could not restore dev autoloader. Run: composer dump-autoload');
  }
});

archive.on('error', function (err) {
  throw err;
});

archive.pipe(output);

// Non-minified assets: only .min.css and .min.js are included in the build
const IGNORE_SOURCE_ASSETS = [
  'assets/css/authvault-public.css',
  'assets/css/authvault-admin.css',
  'assets/css/authvault-elementor.css',
  'assets/js/authvault-public.js',
  'assets/js/authvault-admin.js'
];

const IGNORE_DIRS = ['node_modules', '.git', '.cursor', '.vscode', 'vendor', 'coverage', 'tests'];
const IGNORE_FILES = [
  'build.js',
  'update-plugin-version.js',
  'build-admin-css.js',
  'package.json',
  'package-lock.json',
  'composer.json',
  'composer.lock',
  '.gitignore',
  '.editorconfig',
  'phpunit.xml.dist',
  '.phpunit.result.cache',
  ...IGNORE_SOURCE_ASSETS
];

function shouldIgnore(relPath) {
  const normalized = path.normalize(relPath).replace(/\\/g, '/');
  if (IGNORE_FILES.includes(normalized)) return true;
  if (normalized.endsWith('.zip') || normalized.endsWith('.log')) return true;
  const top = normalized.split('/')[0];
  if (IGNORE_DIRS.includes(top)) return true;
  return false;
}

function walkDir(relDir, list) {
  const fullDir = path.join(__dirname, relDir);
  const entries = fs.readdirSync(fullDir, { withFileTypes: true });
  for (const e of entries) {
    const rel = relDir ? relDir + '/' + e.name : e.name;
    const relSlash = rel.replace(/\\/g, '/');
    if (e.isDirectory()) {
      if (shouldIgnore(relSlash + '/')) continue;
      walkDir(rel, list);
    } else {
      if (shouldIgnore(relSlash)) continue;
      list.push(rel);
    }
  }
}

const relFiles = [];
walkDir('', relFiles);

for (const rel of relFiles) {
  const relSlash = rel.replace(/\\/g, '/');
  archive.file(path.join(__dirname, rel), { name: PLUGIN_SLUG + '/' + relSlash });
}

// Regenerate Composer autoloader without dev dependencies
console.log('Regenerating Composer autoloader (--no-dev) …');
try {
  execSync('composer dump-autoload --no-dev --optimize --quiet', {
    cwd: __dirname,
    stdio: 'inherit'
  });
} catch (err) {
  console.error('composer dump-autoload --no-dev failed. Is Composer installed?');
  process.exit(1);
}

// Include only the Composer autoloader (no dev dependencies)
const vendorDir = path.join(__dirname, 'vendor');
if (fs.existsSync(path.join(vendorDir, 'autoload.php'))) {
  archive.file(path.join(vendorDir, 'autoload.php'), { name: PLUGIN_SLUG + '/vendor/autoload.php' });
}
const composerDir = path.join(vendorDir, 'composer');
if (fs.existsSync(composerDir)) {
  archive.directory(composerDir, PLUGIN_SLUG + '/vendor/composer');
}

archive.finalize();
