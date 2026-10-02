import React from 'react';
import { Toaster } from 'sonner';
import { PackageOpen } from 'lucide-react';
import useDocumentTheme from '../utils/useDocumentTheme';

export function WorkspaceToaster() {
    const theme = useDocumentTheme();
    return <Toaster position="top-right" richColors closeButton theme={theme}
        toastOptions={{ className: 'qpos-flash-toast' }} />;
}

export function EmptyState({ title, description }) {
    return <div className="qpos-state" role="status">
        <span className="qpos-state-icon"><PackageOpen size={24} aria-hidden="true" /></span>
        <h3>{title}</h3><p>{description}</p>
    </div>;
}

export function Field({ label, children, ...props }) {
    return <label className="qpos-field" {...props}><span>{label}</span>{children}</label>;
}

// React Select keeps keyboard search while consuming the shared design tokens.
export const selectStyles = {
    control: (base, state) => ({ ...base, minHeight: 44, borderRadius: 10,
        borderColor: state.isFocused ? 'var(--qpos-brand)' : 'var(--qpos-control-border)',
        backgroundColor: 'var(--qpos-surface)', boxShadow: state.isFocused ? '0 0 0 3px var(--qpos-brand-soft)' : 'none',
        ':hover': { borderColor: 'var(--qpos-brand)' } }),
    menu: base => ({ ...base, backgroundColor: 'var(--qpos-surface)', border: '1px solid var(--qpos-border)', zIndex: 25 }),
    option: (base, state) => ({ ...base, minHeight: 44, color: state.isSelected ? '#fff' : 'var(--qpos-text)',
        backgroundColor: state.isSelected ? 'var(--qpos-brand)' : state.isFocused ? 'var(--qpos-brand-soft)' : 'var(--qpos-surface)',
        ':active': { backgroundColor: 'var(--qpos-brand-soft)', color: 'var(--qpos-text)' } }),
    singleValue: base => ({ ...base, color: 'var(--qpos-text)' }),
    input: base => ({ ...base, color: 'var(--qpos-text)' }),
    placeholder: base => ({ ...base, color: 'var(--qpos-muted)' }),
};
