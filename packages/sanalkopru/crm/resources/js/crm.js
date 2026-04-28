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
            if (list.dataset.crmKanbanReady === '1') {
                return;
            }

            list.dataset.crmKanbanReady = '1';

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

    function animateDashboardBars() {
        const dashboard = document.querySelector('[data-crm-module="dashboard"]');

        if (!dashboard) {
            return;
        }

        dashboard.querySelectorAll('.crm-dashboard-bar > span, .crm-dashboard-split-bars > span').forEach((span) => {
            if (span.dataset.crmBarAnimated === '1') {
                return;
            }

            span.dataset.crmBarAnimated = '1';

            const target = span.style.width;

            span.style.transition = 'none';
            span.style.width = '0';

            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    span.style.transition = '';
                    span.style.width = target;
                });
            });
        });
    }

    function animateDashboardNumbers() {
        const dashboard = document.querySelector('[data-crm-module="dashboard"]');

        if (!dashboard) {
            return;
        }

        dashboard.querySelectorAll('[data-crm-count-up]').forEach((card) => {
            if (card.dataset.crmCountAnimated === '1') {
                return;
            }

            card.dataset.crmCountAnimated = '1';

            const target = parseInt(card.dataset.crmCountUp, 10);

            if (isNaN(target) || target === 0) {
                return;
            }

            const h3 = card.querySelector('h3');

            if (!h3) {
                return;
            }

            const originalText = h3.textContent.trim();
            const duration = Math.min(1200, 400 + target * 6);
            const startTime = performance.now();

            function easeOutCubic(t) {
                return 1 - Math.pow(1 - t, 3);
            }

            function tick(now) {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.round(easeOutCubic(progress) * target);

                h3.textContent = current.toLocaleString();

                if (progress < 1) {
                    requestAnimationFrame(tick);
                } else {
                    h3.textContent = originalText;
                }
            }

            h3.textContent = '0';
            requestAnimationFrame(tick);
        });
    }

    function animateDashboardRegion() {
        const region = document.getElementById('crm-dashboard-region');

        if (!region) {
            return;
        }

        region.classList.remove('crm-region-fade');
        void region.offsetWidth;
        region.classList.add('crm-region-fade');
    }

    function collapseCard(card) {
        const backdrop = document.querySelector('.crm-dashboard-backdrop');
        const icon = card.querySelector('[data-crm-dashboard-expand] [data-lucide]');

        card.classList.remove('is-expanded');
        backdrop?.remove();

        // Restore card to its original position in the DOM
        if (card._crmPortalAnchor) {
            card._crmPortalAnchor.replaceWith(card);
            delete card._crmPortalAnchor;
        }

        if (icon) {
            icon.setAttribute('data-lucide', 'expand');
            window.AdminPanel?.refreshIcons?.();
        }
    }

    function expandCard(card) {
        document.querySelectorAll('[data-crm-dashboard-card].is-expanded').forEach(collapseCard);

        const icon = card.querySelector('[data-crm-dashboard-expand] [data-lucide]');

        // Portal: move card to body so position:fixed is relative to viewport,
        // not a parent with CSS transform (animation creates containing block).
        const anchor = document.createElement('div');
        anchor.style.display = 'contents';
        card.replaceWith(anchor);
        card._crmPortalAnchor = anchor;
        document.body.appendChild(card);

        const backdrop = document.createElement('div');
        backdrop.className = 'crm-dashboard-backdrop';
        backdrop.addEventListener('click', () => collapseCard(card));
        document.body.appendChild(backdrop);

        card.classList.add('is-expanded');

        if (icon) {
            icon.setAttribute('data-lucide', 'shrink');
            window.AdminPanel?.refreshIcons?.();
        }
    }

    function initializeDashboardCards() {
        const dashboard = document.querySelector('[data-crm-module="dashboard"]');

        if (!dashboard) {
            return;
        }

        dashboard.querySelectorAll('[data-crm-dashboard-card]').forEach((card) => {
            if (card.dataset.crmDashboardReady === '1') {
                return;
            }

            card.dataset.crmDashboardReady = '1';

            const button = card.querySelector('[data-crm-dashboard-expand]');

            if (button) {
                button.addEventListener('click', () => {
                    card.classList.contains('is-expanded') ? collapseCard(card) : expandCard(card);
                });
            }
        });
    }

    function initializeDashboardCardPagination() {
        document.querySelectorAll('[data-crm-paginate]').forEach((panel) => {
            if (panel.dataset.crmPaginateReady === '1') {
                return;
            }

            panel.dataset.crmPaginateReady = '1';

            const pageSize = parseInt(panel.dataset.crmPageSize || '5', 10);
            const items = Array.from(
                panel.querySelectorAll(':scope > .crm-dashboard-row, :scope > .crm-list-item, :scope > .crm-timeline-item'),
            );

            if (items.length <= pageSize) {
                return;
            }

            let currentPage = 0;
            const totalPages = Math.ceil(items.length / pageSize);

            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'crm-dashboard-pager-btn';
            prevBtn.setAttribute('aria-label', 'Previous page');
            prevBtn.innerHTML = '<i data-lucide="chevron-left" width="14" height="14"></i>';

            const info = document.createElement('span');
            info.className = 'crm-dashboard-pager-info';

            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'crm-dashboard-pager-btn';
            nextBtn.setAttribute('aria-label', 'Next page');
            nextBtn.innerHTML = '<i data-lucide="chevron-right" width="14" height="14"></i>';

            const footer = document.createElement('div');
            footer.className = 'crm-dashboard-pagination';
            footer.appendChild(prevBtn);
            footer.appendChild(info);
            footer.appendChild(nextBtn);

            panel.insertAdjacentElement('afterend', footer);

            function renderPage() {
                const start = currentPage * pageSize;
                const end = start + pageSize;

                items.forEach((item, i) => {
                    if (i >= start && i < end) {
                        item.removeAttribute('hidden');
                    } else {
                        item.setAttribute('hidden', '');
                    }
                });

                info.textContent = `${currentPage + 1} / ${totalPages}`;
                prevBtn.disabled = currentPage === 0;
                nextBtn.disabled = currentPage === totalPages - 1;
                window.AdminPanel?.refreshIcons?.();

                if (!panel._minHeightLocked) {
                    requestAnimationFrame(() => {
                        const h = panel.offsetHeight;

                        if (h > 0) {
                            panel.style.minHeight = h + 'px';
                            panel._minHeightLocked = true;
                        }
                    });
                }
            }

            prevBtn.addEventListener('click', () => {
                if (currentPage > 0) {
                    currentPage--;
                    renderPage();
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentPage < totalPages - 1) {
                    currentPage++;
                    renderPage();
                }
            });

            renderPage();
        });
    }

    function extendAdminRehydrate() {
        if (!window.AdminPanel || window.AdminPanel._crmDashboardPatched === true) {
            return;
        }

        const original = window.AdminPanel.rehydrate;

        window.AdminPanel.rehydrate = function () {
            if (typeof original === 'function') {
                original();
            }

            animateDashboardRegion();
            initializeDashboardCards();
            initializeDashboardCardPagination();
            animateDashboardBars();
            animateDashboardNumbers();
            initializeDealKanban();
        };

        window.AdminPanel._crmDashboardPatched = true;
    }

    document.addEventListener('DOMContentLoaded', () => {
        extendAdminRehydrate();
        initializeGlobalSearchShortcut();
        initializeFormStates();
        initializeToasts();
        initializeDealKanban();
        initializeQuoteItems();
        initializeAjaxForms();
        initializeImportPreviewForms();
        initializeDashboardCards();
        initializeDashboardCardPagination();
        animateDashboardBars();
        animateDashboardNumbers();
    });
})();
