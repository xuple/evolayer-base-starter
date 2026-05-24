import { useEffect, useState } from 'react';

type TypewriterState = {
    source: string | null;
    displayed: string;
};

export function useTypewriter(text: string | null, speedMs = 5): string {
    const [state, setState] = useState<TypewriterState>({
        source: null,
        displayed: '',
    });

    useEffect(() => {
        if (!text) {
            return;
        }

        let i = 0;

        const id = setInterval(() => {
            i++;
            setState({
                source: text,
                displayed: text.slice(0, i),
            });

            if (i >= text.length) {
                clearInterval(id);
            }
        }, speedMs);

        return () => clearInterval(id);
    }, [text, speedMs]);

    return text === state.source ? state.displayed : '';
}
