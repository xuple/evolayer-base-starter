import { readFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import process from 'node:process';

const expiry = '2026-08-31';
const allowedPackages = new Set([
    '@eslint/config-array',
    '@eslint/eslintrc',
    'brace-expansion',
    'eslint',
    'eslint-plugin-import',
    'eslint-plugin-react',
    'minimatch',
]);
const allowedAdvisories = new Set([
    'https://github.com/advisories/GHSA-mh99-v99m-4gvg',
]);

const option = (name) => {
    const index = process.argv.indexOf(name);

    return index === -1 ? undefined : process.argv[index + 1];
};

const fail = (code, message) => {
    process.stderr.write(`${code}: ${message}\n`);
    process.exit(1);
};

const inputPath = option('--input');
const reviewDate = option('--date') ?? new Date().toISOString().slice(0, 10);
let reportJson;

if (inputPath !== undefined) {
    try {
        reportJson = readFileSync(inputPath, 'utf8');
    } catch {
        fail('npm-audit-input-unreadable', 'The audit report could not be read.');
    }
} else {
    const audit = spawnSync('npm', ['audit', '--json'], {
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    });

    if (audit.error || audit.stdout.trim() === '') {
        fail('npm-audit-unavailable', 'npm audit did not return a JSON report.');
    }

    reportJson = audit.stdout;
}

let report;

try {
    report = JSON.parse(reportJson);
} catch {
    fail('npm-audit-json-invalid', 'The audit report is not valid JSON.');
}

const vulnerabilities = report.vulnerabilities ?? {};
const packageNames = Object.entries(vulnerabilities)
    .filter(([, vulnerability]) => ['high', 'critical'].includes(vulnerability.severity))
    .map(([packageName]) => packageName);

if (packageNames.length === 0) {
    process.stdout.write('Full npm audit has no high-severity findings.\n');
    process.exit(0);
}

if (!/^\d{4}-\d{2}-\d{2}$/.test(reviewDate) || reviewDate > expiry) {
    fail('npm-audit-exception-expired', `The ESLint audit exception expired on ${expiry}.`);
}

for (const packageName of packageNames) {
    const vulnerability = vulnerabilities[packageName];

    if (!allowedPackages.has(packageName)) {
        fail('npm-audit-unexpected-package', 'A high-severity advisory falls outside the reviewed ESLint exception.');
    }

    if (vulnerability.severity !== 'high') {
        fail('npm-audit-severity-changed', 'The reviewed ESLint advisory severity changed.');
    }

    if (vulnerability.fixAvailable === true
        || (typeof vulnerability.fixAvailable === 'object'
            && vulnerability.fixAvailable?.isSemVerMajor === false)) {
        fail('npm-audit-compatible-fix-available', 'A compatible fix is now available for the deferred ESLint chain.');
    }

    for (const cause of vulnerability.via ?? []) {
        if (typeof cause === 'string') {
            if (!allowedPackages.has(cause)) {
                fail('npm-audit-unexpected-cause', 'The deferred ESLint chain gained an unreviewed dependency cause.');
            }

            continue;
        }

        if (!allowedAdvisories.has(cause.url)) {
            fail('npm-audit-unexpected-advisory', 'The deferred ESLint chain gained an unreviewed advisory.');
        }
    }

    for (const effect of vulnerability.effects ?? []) {
        if (!allowedPackages.has(effect)) {
            fail('npm-audit-unexpected-effect', 'The deferred ESLint advisory now affects an unreviewed package.');
        }
    }
}

process.stdout.write(
    `Deferred ESLint npm advisory matches the reviewed allowlist (expires ${expiry}).\n`,
);
