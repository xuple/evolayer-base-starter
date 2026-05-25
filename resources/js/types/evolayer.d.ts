/**
 * Published by `xuple/evolayer-base` — self-contained types for the EvoLayer
 * frontend layer. Designed to be importable from the package's published
 * pages, hooks, and config files without depending on the host's existing
 * shared-props or NavItem definitions.
 *
 * Shared-prop shape:
 *   usePage().props.evolayer.base.{examples, features}
 *
 * The `evolayer.base.*` sub-tree reserves room for sibling packages
 * (`evolayer.commerce.*`, `evolayer.saas.*`, `evolayer.rls.*`) to coexist
 * under the same top-level `evolayer` namespace without collision.
 *
 * Host integration:
 *   - To use EvoLayer nav items inside your existing sidebar, either
 *     widen your own `NavItem` type with the fields below, or render the
 *     EvoLayer items separately via `useExampleNavItems()`.
 *   - To get autocomplete on `usePage().props.evolayer.base`, augment your
 *     `InertiaConfig.sharedPageProps` declaration to include
 *     `evolayer: { base: EvoLayerBaseSharedProps }`. The package does not augment
 *     Inertia automatically (would conflict with the host's own
 *     declaration).
 */

import type { InertiaLinkProps } from '@inertiajs/react';
import type { ComponentType } from 'react';

export interface EvoLayerExamples {
    thread_studio: boolean;
    prd_studio: boolean;
    admin_inbox: boolean;
    contact_ai: boolean;
    voice_input: boolean;
    ai_text_field: boolean;
    marketing_pages: boolean;
}

export interface EvoLayerFeatures {
    contact_attachments: boolean;
}

export interface EvoLayerBaseSharedProps {
    examples: EvoLayerExamples;
    features: EvoLayerFeatures;
}

/**
 * The full `evolayer` shared-props tree. Variants extend with their own
 * sub-namespace (e.g. `evolayer.commerce`, `evolayer.saas`, `evolayer.rls`).
 */
export interface EvoLayerSharedProps {
    base: EvoLayerBaseSharedProps;
}

/**
 * Self-contained NavItem shape used by the EvoLayer package. The host's
 * own `NavItem` can be a superset — pass package items through
 * `useExampleNavItems()` to consume.
 */
export interface EvoLayerNavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: ComponentType<{ className?: string }> | null;
    isActive?: boolean;
    isAccent?: boolean;
    description?: string;
    exampleKey?: keyof EvoLayerExamples;
}
