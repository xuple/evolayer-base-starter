import type { EvoNavItem } from '@/types/evodevops';
import { useEvoProps } from '@/hooks/use-evo-props';

export function useExampleNavItems<T extends EvoNavItem = EvoNavItem>(
    items: T[],
): T[] {
    const examples = useEvoProps().base?.examples;

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
