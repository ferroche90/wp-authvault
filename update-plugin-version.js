/**
 * Syncs the plugin version from package.json into authvault.php.
 * Run before building so the zip ships with the version from package.json.
 *
 * Usage: node update-plugin-version.js
 *        npm run update-version
 */

const fs = require('fs');
const path = require('path');

// Resolve from this script's directory (plugin root) so paths work regardless of cwd
const PLUGIN_ROOT = path.resolve(__dirname);
const PACKAGE_JSON_PATH = path.resolve(PLUGIN_ROOT, 'package.json');
const PLUGIN_FILE_PATH = path.resolve(PLUGIN_ROOT, 'authvault.php');

if (!fs.existsSync(PACKAGE_JSON_PATH)) {
  console.error('package.json not found.');
  process.exit(1);
}

if (!fs.existsSync(PLUGIN_FILE_PATH)) {
  console.error('authvault.php not found.');
  process.exit(1);
}

const pkg = JSON.parse(fs.readFileSync(PACKAGE_JSON_PATH, 'utf8'));
const newVersion = pkg.version;

if (!newVersion || typeof newVersion !== 'string') {
  console.error('package.json must have a "version" field.');
  process.exit(1);
}

// Loose semver: digits and dots, optional prerelease (e.g. 1.0.0 or 1.0.0-beta.1)
const VERSION_PATTERN = '[0-9]+\\.[0-9]+\\.[0-9]+(?:[-a-zA-Z0-9.]*)?';

console.log('Updating plugin to version: ' + newVersion);

let content = fs.readFileSync(PLUGIN_FILE_PATH, 'utf8');

// WordPress plugin header: * Version: x.x.x
content = content.replace(
  new RegExp('(\\* Version:\\s+)' + VERSION_PATTERN, 'g'),
  '$1' + newVersion
);

// AUTHVAULT_VERSION constant
content = content.replace(
  new RegExp("(define\\s*\\(\\s*'AUTHVAULT_VERSION',\\s*')" + VERSION_PATTERN + "('\\s*\\)\\s*;)", 'g'),
  '$1' + newVersion + '$2'
);

fs.writeFileSync(PLUGIN_FILE_PATH, content);

console.log('Plugin file updated successfully.');
