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

    function reindexQuoteItems(container) {
        container.querySelectorAll('[data-crm-quote-item]').forEach((item, index) => {
            item.querySelector('[data-crm-quote-item-number]').textContent = String(index + 1);
            item.querySelector('[data-crm-quote-item-position]').value = String(index + 1);
            item.querySelectorAll('[name^="items["]').forEach((field) => {
                field.name = field.name.replace(/^items\[\d+\]/, `items[${index}]`);
            });
        });
    }

    function initializeQuoteItems() {
        const form = document.querySelector('[data-crm-quote-form]');
        const container = document.querySelector('[data-crm-quote-items]');

        if (!form || !container) {
            return;
        }

        const addButton = document.querySelector('[data-crm-add-quote-item]');
        const defaultTaxRate = container.dataset.defaultTaxRate || '20';

        addButton?.addEventListener('click', () => {
            const template = container.querySelector('[data-crm-quote-item]');
            const clone = template.cloneNode(true);

            clone.querySelectorAll('input, textarea, select').forEach((field) => {
                if (field.matches('[data-crm-quote-item-position]')) {
                    return;
                }

                if (field.name.includes('[quantity]')) {
                    field.value = '1.000';
                } else if (field.name.includes('[unit_price]') || field.name.includes('[discount_value]')) {
                    field.value = '0.00';
                } else if (field.name.includes('[tax_rate]')) {
                    field.value = defaultTaxRate;
                } else {
                    field.value = '';
                }
            });

            container.appendChild(clone);
            reindexQuoteItems(container);
        });

        container.addEventListener('click', (event) => {
            const button = event.target.closest('button');

            if (!button) {
                return;
            }

            const item = button.closest('[data-crm-quote-item]');

            if (button.matches('[data-crm-remove-quote-item]')) {
                if (container.querySelectorAll('[data-crm-quote-item]').length > 1) {
                    item.remove();
                    reindexQuoteItems(container);
                }
            }

            if (button.matches('[data-crm-quote-item-up]') && item.previousElementSibling) {
                container.insertBefore(item, item.previousElementSibling);
                reindexQuoteItems(container);
            }

            if (button.matches('[data-crm-quote-item-down]') && item.nextElementSibling) {
                container.insertBefore(item.nextElementSibling, item);
                reindexQuoteItems(container);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeDealKanban();
        initializeQuoteItems();
    });
})();
