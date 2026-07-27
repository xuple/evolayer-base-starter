import type { ReactNode } from 'react';
import {
    createContext,
    useCallback,
    useContext,
    useRef,
    useState,
} from 'react';

type CommandPaletteContextType = {
    isOpen: boolean;
    open: () => void;
    close: () => void;
    restoreFocus: () => void;
};

const CommandPaletteContext = createContext<
    CommandPaletteContextType | undefined
>(undefined);

export function CommandPaletteProvider({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);
    const previouslyFocusedElement = useRef<HTMLElement | null>(null);

    const open = useCallback(() => {
        previouslyFocusedElement.current =
            document.activeElement instanceof HTMLElement
                ? document.activeElement
                : null;
        setIsOpen(true);
    }, []);
    const close = useCallback(() => setIsOpen(false), []);
    const restoreFocus = useCallback(() => {
        if (previouslyFocusedElement.current?.isConnected) {
            previouslyFocusedElement.current.focus();
        }
    }, []);

    return (
        <CommandPaletteContext.Provider
            value={{ isOpen, open, close, restoreFocus }}
        >
            {children}
        </CommandPaletteContext.Provider>
    );
}

export function useCommandPalette() {
    const context = useContext(CommandPaletteContext);

    if (context === undefined) {
        throw new Error(
            'useCommandPalette must be used within a CommandPaletteProvider',
        );
    }

    return context;
}
