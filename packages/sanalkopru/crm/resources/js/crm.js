(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function restoreCard(card, sourceList, sourceIndex) {
        if (!sourceList) {
            return;
        }

        const children = Array.from(sourceList.children).filter((child) => child !== card);
        sourceList.insertBefore(card, children[sourceIndex] || null);
    }

    function positionInList(card) {
        return Array.from(card.parentElement.children).indexOf(card) + 1;
    }

    async function askLostReason() {
        const dialog = document.querySelector('[data-crm-lost-dialog]');

        if (!dialog || typeof dialog.showModal !== 'function') {
            return window.prompt('Lost reason') || null;
        }

        const form = dialog.querySelector('form');
        const textarea = dialog.querySelector('[name="lost_reason"]');
        textarea.value = '';

        return new Promise((resolve) => {
            const close = (value) => {
                form.removeEventListener('submit', onSubmit);
                dialog.removeEventListener('close', onClose);
                resolve(value);
            };

            const onSubmit = (event) => {
                event.preventDefault();
                const value = textarea.value.trim();

                if (value.length === 0) {
                    textarea.focus();
                    return;
                }

                dialog.close('submit');
                close(value);
            };

            const onClose = () => close(null);

            form.addEventListener('submit', onSubmit);
            dialog.addEventListener('close', onClose, { once: true });
            dialog.showModal();
            textarea.focus();
        });
    }

    async function moveDeal(card, targetList, sourceList, sourceIndex) {
        const stageId = targetList.dataset.stageId;
        const isLostStage = targetList.dataset.stageIsLost === '1';
        const payload = {
            stage_id: Number(stageId),
            position: positionInList(card),
        };

        if (isLostStage) {
            const lostReason = await askLostReason();

            if (!lostReason) {
                restoreCard(card, sourceList, sourceIndex);
                return;
            }

            payload.lost_reason = lostReason;
        }

        card.classList.add('is-moving');

        try {
            const response = await fetch(card.dataset.moveUrl, {
                method: 'PATCH',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(`Move failed with status ${response.status}`);
            }
        } catch (error) {
            restoreCard(card, sourceList, sourceIndex);
            window.alert('Deal could not be moved. Please refresh and try again.');
        } finally {
            card.classList.remove('is-moving');
        }
    }

    function initializeDealKanban() {
        if (typeof window.Sortable === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-crm-kanban-list]').forEach((list) => {
            window.Sortable.create(list, {
                group: 'crm-deals',
                animation: 150,
                ghostClass: 'is-moving',
                onEnd(event) {
                    if (!event.item || !event.to) {
                        return;
                    }

                    moveDeal(event.item, event.to, event.from, event.oldIndex || 0);
                },
            });
        });
    }

    document.addEventListener('DOMContentLoaded', initializeDealKanban);
})();
