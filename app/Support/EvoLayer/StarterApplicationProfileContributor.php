<?php

namespace App\Support\EvoLayer;

use Xuple\EvoLayer\Base\Contracts\ProfileTransitionContributor;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\FilePrecondition;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionContext;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionManager;
use Xuple\EvoLayer\Base\Support\ProfileTransitions\ProfileTransitionPlan;

final readonly class StarterApplicationProfileContributor implements ProfileTransitionContributor
{
    private const PROFILE_START = '<!-- evolayer-application-profile:start -->';

    private const PROFILE_END = '<!-- evolayer-application-profile:end -->';

    public function __construct(private StarterProfilePaths $paths) {}

    public function id(): string
    {
        return 'starter.application-host';
    }

    public function apiVersion(): int
    {
        return ProfileTransitionManager::CONTRIBUTOR_API_VERSION;
    }

    public function provides(): array
    {
        return [
            'starter.registration',
            'starter.seeding',
            'starter.guidance',
            'starter.frontend',
            'starter.page-surfaces',
        ];
    }

    public function requires(): array
    {
        return ['profile.committed-intent'];
    }

    public function priority(): int
    {
        return 300;
    }

    public function contribute(ProfileTransitionContext $context, ProfileTransitionPlan $plan): void
    {
        $application = $context->profile === 'application';

        if ($application && ! $this->isGeneratedApplication($plan)) {
            return;
        }

        $this->contributeRegistrationPage($application, $plan);

        if ($this->hasGeneratedIdentity()) {
            $this->contributeGuidance($application, $plan);
        }
    }

    private function contributeRegistrationPage(bool $application, ProfileTransitionPlan $plan): void
    {
        $target = $this->paths->path('resources/js/pages/auth/register.tsx');
        $template = $this->paths->registrationTemplate();
        $precondition = FilePrecondition::capture($target);
        $templateContents = is_file($template) ? file_get_contents($template) : false;

        if (! is_string($templateContents)) {
            $plan->conflict('Starter registration template is missing.');

            return;
        }

        if ($application) {
            if (! $precondition->exists) {
                return;
            }

            if ($precondition->sha256 !== hash('sha256', $templateContents)) {
                $plan->conflict('Starter registration source is modified or host-owned; reconcile it explicitly before disabling registration.');

                return;
            }

            $plan->delete($target, $precondition);

            return;
        }

        if (! $precondition->exists) {
            $plan->replace($target, $templateContents, $precondition);
        }
    }

    private function contributeGuidance(bool $application, ProfileTransitionPlan $plan): void
    {
        foreach (['README.md', 'AGENTS.md', 'CLAUDE.md'] as $relative) {
            $path = $this->paths->path($relative);
            $precondition = FilePrecondition::capture($path);

            if (! $precondition->exists) {
                $plan->conflict("Generated application guidance [{$relative}] is missing.");

                continue;
            }

            $contents = file_get_contents($path);

            if (! is_string($contents)) {
                $plan->conflict("Generated application guidance [{$relative}] is unreadable.");

                continue;
            }

            $replacement = $this->replaceKnownGuidanceBlock(
                $contents,
                $application ? $this->applicationGuidance() : $this->demoGuidance(),
            );

            if ($replacement === null) {
                $plan->conflict("Generated application guidance [{$relative}] has a modified profile block.");

                continue;
            }

            $plan->replace($path, $replacement, $precondition);
        }
    }

    private function replaceKnownGuidanceBlock(string $contents, string $target): ?string
    {
        $pattern = '/'.preg_quote(self::PROFILE_START, '/').'.*?'.preg_quote(self::PROFILE_END, '/').'/s';

        if (preg_match($pattern, $contents, $matches) === 1) {
            if (! in_array($matches[0], [$this->demoGuidance(), $this->applicationGuidance()], true)) {
                return null;
            }

            return (string) preg_replace($pattern, $target, $contents, 1);
        }

        $legacyCredential = 'Log in with '.chr(96).'test@example.com'.chr(96).' / '.chr(96).'password'.chr(96).' to explore the seeded surfaces. (Cloned a fresh copy with no .env? Run '.chr(96).'composer setup'.chr(96).' first.)';

        if (str_contains($contents, $legacyCredential)) {
            return str_replace($legacyCredential, $target, $contents);
        }

        return null;
    }

    public static function hasApplicationGuidance(string $contents): bool
    {
        $pattern = '/'.preg_quote(self::PROFILE_START, '/').'(.*?)'.preg_quote(self::PROFILE_END, '/').'/s';

        if (preg_match($pattern, $contents, $matches) !== 1) {
            return false;
        }

        return trim($matches[0]) === trim((new self(new StarterProfilePaths))->applicationGuidance());
    }

    private function isGeneratedApplication(ProfileTransitionPlan $plan): bool
    {
        $metadata = $this->projectMetadata();
        $generated = is_array($metadata)
            && (($metadata['kind'] ?? null) === 'generated-application'
                || ($metadata['mode'] ?? null) === 'application');

        if (! $generated) {
            $plan->conflict('The official application profile may only be applied to a generated Starter application.');
        }

        return $generated;
    }

    private function hasGeneratedIdentity(): bool
    {
        $metadata = $this->projectMetadata();

        return is_array($metadata)
            && (($metadata['kind'] ?? null) === 'generated-application'
                || ($metadata['mode'] ?? null) === 'application');
    }

    /** @return array<string, mixed>|null */
    private function projectMetadata(): ?array
    {
        $path = $this->paths->path('.evolayer/project.json');
        $metadata = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        return is_array($metadata) ? $metadata : null;
    }

    private function demoGuidance(): string
    {
        return <<<'MD'
<!-- evolayer-application-profile:start -->
> **Operational profile: demo.** Public registration and bundled examples are enabled for local exploration. The seeded test@example.com / password account is demonstration-only. Apply the application profile before treating this repository as an application deployment.
<!-- evolayer-application-profile:end -->
MD;
    }

    private function applicationGuidance(): string
    {
        return <<<'MD'
<!-- evolayer-application-profile:start -->
> **Operational profile: application.** Public registration, known demo credentials, and bundled example surfaces are disabled. Regenerate Wayfinder and ontology contracts, run the Starter verification gates, and deliberately update the exact Base pin when adopting a reviewed release.
<!-- evolayer-application-profile:end -->
MD;
    }
}
