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

    function updateKanbanAggregates(stages) {
        if (!Array.isArray(stages)) {
            return;
        }

        stages.forEach(function (stage) {
            const col = document.querySelector('[data-crm-kanban-column="' + stage.id + '"]');

            if (!col) {
                return;
            }

            const countEl = col.querySelector('[data-crm-stage-count]');
            const valueEl = col.querySelector('[data-crm-stage-value]');

            if (countEl) {
                countEl.textContent = stage.count_label;
            }

            if (valueEl) {
                valueEl.textContent = stage.value_label;
            }
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

            const data = await response.json();
            updateKanbanAggregates(data.stages || []);
            window.AdminPanel?.toast('Deal moved.', 'success');
        } catch (error) {
            restoreCard(card, sourceList, sourceIndex);
            window.AdminPanel?.toast('Deal could not be moved. Please refresh and try again.', 'danger');
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

    function initializeGlobalSearchShortcut() {
        document.addEventListener('keydown', (event) => {
            const isSearchShortcut = (event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k';

            if (!isSearchShortcut || document.querySelector('[data-admin-command-palette]')) {
                return;
            }

            const input = document.querySelector('[data-crm-global-search]');

            if (!input) {
                return;
            }

            event.preventDefault();
            input.focus();
            input.select();
        });
    }

    function initializeFormStates() {
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.crmConfirmed === '1') {
                    form.classList.add('crm-is-submitting');
                    return;
                }

                const submitter = event.submitter;
                const message = submitter?.dataset?.crmConfirm || form.dataset.crmConfirm;

                if (message && window.AdminPanel?.confirm) {
                    event.preventDefault();

                    window.AdminPanel.confirm(message).then((ok) => {
                        if (!ok) {
                            return;
                        }

                        form.dataset.crmConfirmed = '1';
                        form.classList.add('crm-is-submitting');
                        form.requestSubmit(submitter || undefined);
                    });

                    return;
                }

                if (message && !window.confirm(message)) {
                    event.preventDefault();
                    return;
                }

                form.classList.add('crm-is-submitting');
            });
        });

        const invalid = document.querySelector('.is-invalid, [aria-invalid="true"]');
        invalid?.focus?.();
    }

    function initializeToasts() {
        document.querySelectorAll('[data-crm-toast]').forEach((toast) => {
            window.setTimeout(() => {
                toast.style.transition = 'opacity 180ms ease';
                toast.style.opacity = '0';
            }, 5000);
        });
    }

    async function reloadRegion(region) {
        const url = region.dataset.crmRegionUrl || window.location.href;

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const html = await response.text();
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const next = doc.getElementById(region.id);

            if (next) {
                region.replaceWith(next);
                window.AdminPanel?.rehydrate?.();
            }
        } catch (_e) {
            // silently fail — region updates on next full page load
        }
    }

    function initializeAjaxForms() {
        document.querySelectorAll('[data-crm-ajax-form]').forEach((form) => {
            if (form.dataset.crmAjaxFormReady === '1') {
                return;
            }

            form.dataset.crmAjaxFormReady = '1';

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const button = event.submitter;
                form.classList.add('crm-is-submitting');

                if (button) {
                    button.disabled = true;
                }

                try {
                    const formData = new FormData(form);

                    if (button && button.name) {
                        formData.set(button.name, button.value || '1');
                    }

                    const response = await fetch(form.action, {
                        method: (form.getAttribute('method') || 'POST').toUpperCase(),
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        window.AdminPanel?.toast(data.message || 'Request failed.', 'danger');
                        return;
                    }

                    window.AdminPanel?.toast(data.message || 'Done.', 'success');

                    const aiContent = data.draft || data.summary;

                    if (aiContent) {
                        const container = document.querySelector('[data-crm-ai-result]');

                        if (container) {
                            const labelEl = container.querySelector('[data-crm-ai-label]');
                            const contentEl = container.querySelector('[data-crm-ai-content]');

                            if (labelEl) {
                                labelEl.textContent = form.dataset.crmAiLabel || 'AI Result';
                            }

                            if (contentEl) {
                                contentEl.textContent = aiContent;
                            }

                            container.hidden = false;
                            container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }

                    const regionId = form.dataset.crmReloadRegion;

                    if (regionId) {
                        const region = document.getElementById(regionId);

                        if (region) {
                            await reloadRegion(region);
                        }
                    }

                    if (data.redirect) {
                        window.location.href = data.redirect;
                        return;
                    }

                    if (form.dataset.crmResetOnSuccess !== undefined) {
                        form.reset();
                        window.AdminPanel?.rehydrate?.();
                    }
                } catch (_error) {
                    window.AdminPanel?.toast('Request failed. Please try again.', 'danger');
                } finally {
                    form.classList.remove('crm-is-submitting');

                    if (button) {
                        button.disabled = false;
                    }
                }
            });
        });
    }

    function initializeImportPreviewForms() {
        document.querySelectorAll('[data-crm-import-form]').forEach((form) => {
            if (form.dataset.crmImportFormReady === '1') {
                return;
            }

            form.dataset.crmImportFormReady = '1';

            form.addEventListener('submit', async (event) => {
                const submitter = event.submitter;
                const previewUrl = form.dataset.crmImportPreviewUrl;

                if (!previewUrl || (submitter && submitter.getAttribute('formaction'))) {
                    return;
                }

                event.preventDefault();

                const previewContainer = document.querySelector('[data-crm-import-preview]');
                form.classList.add('crm-is-submitting');

                if (submitter) {
                    submitter.disabled = true;
                }

                if (previewContainer) {
                    previewContainer.classList.add('is-loading');
                }

                try {
                    const formData = new FormData(form);
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: formData,
                    });

                    if (!response.ok) {
                        throw new Error('Preview failed.');
                    }

                    const html = await response.text();

                    if (previewContainer) {
                        previewContainer.innerHTML = html;
                        previewContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        window.AdminPanel?.rehydrate?.();
                    }
                } catch (_e) {
                    window.AdminPanel?.toast('Preview failed. Please try again.', 'danger');
                } finally {
                    form.classList.remove('crm-is-submitting');

                    if (submitter) {
                        submitter.disabled = false;
                    }

                    if (previewContainer) {
                        previewContainer.classList.remove('is-loading');
                    }
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initializeGlobalSearchShortcut();
        initializeFormStates();
        initializeToasts();
        initializeDealKanban();
        initializeQuoteItems();
        initializeAjaxForms();
        initializeImportPreviewForms();
    });
})();
