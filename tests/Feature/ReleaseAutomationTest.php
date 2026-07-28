<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

function runReleaseAutomationProcess(array $command, ?string $input = null): Process
{
    $process = new Process($command, base_path());
    $process->setTimeout(10);

    if ($input !== null) {
        $process->setInput($input);
    }

    $process->run();

    return $process;
}

test('framework bump detection consumes a Packagist payload and ignores prereleases', function () {
    $fixture = base_path('tests/Fixtures/release/packagist-evolayer-base.json');
    $contents = (string) file_get_contents($fixture);

    $upgrade = runReleaseAutomationProcess([
        'bash',
        base_path('scripts/detect-evolayer-base-update.sh'),
        '0.1.9',
    ], $contents);
    $promotion = runReleaseAutomationProcess([
        'bash',
        base_path('scripts/detect-evolayer-base-update.sh'),
        '0.2.0-rc.1',
    ], $contents);
    $noDowngrade = runReleaseAutomationProcess([
        'bash',
        base_path('scripts/detect-evolayer-base-update.sh'),
        '0.2.1-rc.1',
    ], $contents);

    expect($upgrade->isSuccessful())->toBeTrue()
        ->and($upgrade->getOutput())->toBe("needs_bump=true\nlatest=0.2.0\ncurrent=0.1.9\n")
        ->and($promotion->isSuccessful())->toBeTrue()
        ->and($promotion->getOutput())->toBe("needs_bump=true\nlatest=0.2.0\ncurrent=0.2.0-rc.1\n")
        ->and($noDowngrade->isSuccessful())->toBeTrue()
        ->and($noDowngrade->getOutput())->toBe("needs_bump=false\n")
        ->and(file_get_contents($fixture))->toBe($contents);
});

test('framework bump detection fails when Packagist has no stable release', function () {
    $process = runReleaseAutomationProcess([
        'bash',
        base_path('scripts/detect-evolayer-base-update.sh'),
        '0.1.9',
    ], json_encode([
        'packages' => [
            'xuple/evolayer-base' => [
                ['version' => 'v0.2.0-rc.1'],
                ['version' => 'dev-main'],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    expect($process->isSuccessful())->toBeFalse()
        ->and($process->getErrorOutput())->toContain('no stable 0.x EvoLayer Base version found');
});

test('the full npm audit accepts only the unexpired reviewed ESLint chain', function () {
    $fixture = base_path('tests/Fixtures/release/npm-audit-eslint-deferred.json');
    $contents = (string) file_get_contents($fixture);
    $process = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $fixture,
        '--date',
        '2026-07-26',
    ]);

    expect($process->isSuccessful())->toBeTrue()
        ->and($process->getOutput())->toContain('matches the reviewed allowlist')
        ->and(file_get_contents($fixture))->toBe($contents);
});

test('the full npm audit enforces the documented high and critical policy', function () {
    $fixture = base_path('tests/Fixtures/release/npm-audit-eslint-deferred.json');
    $report = json_decode((string) file_get_contents($fixture), true, flags: JSON_THROW_ON_ERROR);
    $report['vulnerabilities']['new-build-tool'] = [
        'severity' => 'high',
        'via' => [],
        'effects' => [],
        'fixAvailable' => false,
    ];

    $this->releaseAutomationRoot = sys_get_temp_dir().'/evolayer-release-automation-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->releaseAutomationRoot);
    $unexpectedPath = $this->releaseAutomationRoot.'/unexpected-audit.json';
    file_put_contents($unexpectedPath, json_encode($report, JSON_THROW_ON_ERROR));
    $report = json_decode((string) file_get_contents($fixture), true, flags: JSON_THROW_ON_ERROR);
    $report['vulnerabilities']['brace-expansion']['fixAvailable'] = true;
    $compatibleFixPath = $this->releaseAutomationRoot.'/compatible-fix-audit.json';
    file_put_contents($compatibleFixPath, json_encode($report, JSON_THROW_ON_ERROR));
    $report = json_decode((string) file_get_contents($fixture), true, flags: JSON_THROW_ON_ERROR);
    $report['vulnerabilities']['brace-expansion']['severity'] = 'critical';
    $criticalPath = $this->releaseAutomationRoot.'/critical-audit.json';
    file_put_contents($criticalPath, json_encode($report, JSON_THROW_ON_ERROR));
    $report = json_decode((string) file_get_contents($fixture), true, flags: JSON_THROW_ON_ERROR);
    $report['vulnerabilities']['new-low-build-tool'] = [
        'severity' => 'low',
        'via' => [],
        'effects' => [],
        'fixAvailable' => false,
    ];
    $lowPath = $this->releaseAutomationRoot.'/low-audit.json';
    file_put_contents($lowPath, json_encode($report, JSON_THROW_ON_ERROR));

    $unexpected = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $unexpectedPath,
        '--date',
        '2026-07-26',
    ]);
    $compatibleFix = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $compatibleFixPath,
        '--date',
        '2026-07-26',
    ]);
    $critical = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $criticalPath,
        '--date',
        '2026-07-26',
    ]);
    $low = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $lowPath,
        '--date',
        '2026-07-26',
    ]);
    $expired = runReleaseAutomationProcess([
        'node',
        base_path('scripts/check-npm-audit.mjs'),
        '--input',
        $fixture,
        '--date',
        '2026-09-01',
    ]);

    expect($unexpected->isSuccessful())->toBeFalse()
        ->and($unexpected->getErrorOutput())->toContain('npm-audit-unexpected-package')
        ->and($compatibleFix->isSuccessful())->toBeFalse()
        ->and($compatibleFix->getErrorOutput())->toContain('npm-audit-compatible-fix-available')
        ->and($critical->isSuccessful())->toBeFalse()
        ->and($critical->getErrorOutput())->toContain('npm-audit-severity-changed')
        ->and($low->isSuccessful())->toBeTrue()
        ->and($low->getOutput())->toContain('matches the reviewed allowlist')
        ->and($expired->isSuccessful())->toBeFalse()
        ->and($expired->getErrorOutput())->toContain('npm-audit-exception-expired');
});

afterEach(function () {
    if (isset($this->releaseAutomationRoot)) {
        File::deleteDirectory($this->releaseAutomationRoot);
    }
});
