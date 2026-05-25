import type { EvoLayerNavItem } from '@/types/evolayer';
import { useEvoLayerProps } from '@/hooks/use-evolayer-props';

export function useExampleNavItems<T extends EvoLayerNavItem = EvoLayerNavItem>(
    items: T[],
): T[] {
    const examples = useEvoLayerProps().base?.examples;

    if (!examples) {
        return items;
    }

    return items.filter((item) => {
        if (!item.exampleKey) {
            return true;
        }

        return examples[item.exampleKey] !== false;
    });
}
