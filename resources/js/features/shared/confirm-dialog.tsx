import { useEffect } from 'react';

export function ConfirmDialog({
    title,
    message,
    confirmLabel,
    pending = false,
    onConfirm,
    onCancel,
}: {
    title: string;
    message: string;
    confirmLabel: string;
    pending?: boolean;
    onConfirm: () => void;
    onCancel: () => void;
}) {
    useEffect(() => {
        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape' && !pending) {
                onCancel();
            }
        }

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [onCancel, pending]);

    return (
        <div className="modal-backdrop" onClick={pending ? undefined : onCancel}>
            <div
                className="modal"
                role="dialog"
                aria-modal="true"
                aria-labelledby="confirm-title"
                onClick={(event) => event.stopPropagation()}
            >
                <h2 id="confirm-title">{title}</h2>
                <p>{message}</p>
                <div className="modal-actions">
                    <button className="btn btn-secondary" type="button" onClick={onCancel} disabled={pending}>
                        Cancel
                    </button>
                    <button className="btn btn-danger" type="button" onClick={onConfirm} disabled={pending}>
                        {pending ? 'Deleting' : confirmLabel}
                    </button>
                </div>
            </div>
        </div>
    );
}
