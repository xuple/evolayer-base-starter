<?php

/**
 * The public starter ships AGENTS.md and CLAUDE.md as maintainer guidance.
 * scripts/evolayer-finalize-install.php and the application-profile commands
 * prepend generated-application blocks to those files in applications created
 * from the starter, and record that fact in .evolayer/project.json. The
 * starter repository itself has no project.json, so its guides must carry
 * neither block.
 */
it('ships the agent guides without generated-application blocks', function (): void {
    if (is_file(base_path('.evolayer/project.json'))) {
        $this->markTestSkipped('Generated application: the install finalizer and profile commands own these blocks.');
    }

    foreach (['AGENTS.md', 'CLAUDE.md'] as $document) {
        $content = (string) file_get_contents(base_path($document));

        expect($content)
            ->not->toContain('<!-- evolayer-application-profile:start -->', "{$document} carries the generated-application profile block.")
            ->not->toContain('<!-- evolayer-generated-app-identity:start -->', "{$document} carries the generated-application identity block.");
    }
});
