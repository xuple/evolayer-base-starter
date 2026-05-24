import type { ReactNode } from 'react';
import { createContext, useContext, useState, useCallback } from 'react';

type CommandPaletteContextType = {
    isOpen: boolean;
    open: () => void;
    close: () => void;
};

const CommandPaletteContext = createContext<
    CommandPaletteContextType | undefined
>(undefined);

export function CommandPaletteProvider({ children }: { children: ReactNode }) {
    const [isOpen, setIsOpen] = useState(false);

    const open = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);

    return (
        <CommandPaletteContext.Provider value={{ isOpen, open, close }}>
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
