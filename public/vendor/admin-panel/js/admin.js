(function () {
    var adminTranslations = window.AdminPanelTranslations || {};
    var commandPalette = null;
    var confirmState = null;
    var notificationTimer = null;
    var notificationVisibilityReady = false;
    var selectedIdsMap = {};

    function adminT(key, fallback, replace) {
        var message = adminTranslations[key];

        if (typeof message !== 'string') {
            message = fallback || key;
        }

        if (replace) {
            Object.keys(replace).forEach(function (token) {
                message = message.replace(':' + token, String(replace[token]));
            });
        }

        return message;
    }

    function loadScript(src, done) {
        var existing = document.querySelector('script[src="' + src + '"]');

        if (existing) {
            if (done) existing.addEventListener('load', done, { once: true });
            return;
        }

        var script = document.createElement('script');
        script.src = src;
        script.defer = true;
        if (done) script.addEventListener('load', done, { once: true });
        document.head.appendChild(script);
    }

    function bootIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function createElement(html) {
        var template = document.createElement('template');
        template.innerHTML = html.trim();

        return template.content.firstElementChild;
    }

    function ensureToastRegion() {
        var region = document.querySelector('[data-admin-toast-region]');

        if (!region) {
            region = createElement('<div class="admin-toast-region" data-admin-toast-region aria-live="polite" aria-atomic="true"></div>');
            document.body.appendChild(region);
        }

        return region;
    }

    function toast(message, type, title) {
        var region = ensureToastRegion();
        var variant = type || 'info';
        var toastElement = createElement(
            '<div class="admin-toast admin-toast-' + variant + '">' +
                '<i data-lucide="' + (variant === 'success' ? 'check-circle' : variant === 'danger' ? 'alert-triangle' : 'info') + '" width="18" height="18"></i>' +
                '<div class="admin-toast-content">' +
                    (title ? '<strong class="admin-toast-title"></strong>' : '') +
                    '<span class="admin-toast-message"></span>' +
                '</div>' +
            '</div>'
        );

        if (title) {
            toastElement.querySelector('.admin-toast-title').textContent = title;
        }

        toastElement.querySelector('.admin-toast-message').textContent = message;
        region.appendChild(toastElement);
        bootIcons();

        window.setTimeout(function () {
            toastElement.style.transition = 'opacity 180ms ease, transform 180ms ease';
            toastElement.style.opacity = '0';
            toastElement.style.transform = 'translateY(-4px)';
            window.setTimeout(function () {
                toastElement.remove();
            }, 220);
        }, 5000);
    }

    function ensureConfirm() {
        var element = document.querySelector('[data-admin-confirm-modal]');

        if (!element) {
            element = createElement(
                '<div class="admin-confirm" data-admin-confirm-modal hidden>' +
                    '<div class="admin-confirm-backdrop" data-admin-confirm-cancel></div>' +
                    '<section class="admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="admin-confirm-title">' +
                        '<h2 class="admin-confirm-title" id="admin-confirm-title">' + adminT('confirm_title', 'Confirm action') + '</h2>' +
                        '<p class="admin-confirm-message" data-admin-confirm-message></p>' +
                        '<div class="admin-confirm-actions">' +
                            '<button type="button" class="btn btn-ghost" data-admin-confirm-cancel>' + adminT('cancel_label', 'Cancel') + '</button>' +
                            '<button type="button" class="btn btn-danger" data-admin-confirm-ok>' + adminT('confirm_label', 'Confirm') + '</button>' +
                        '</div>' +
                    '</section>' +
                '</div>'
            );
            document.body.appendChild(element);

            element.querySelectorAll('[data-admin-confirm-cancel]').forEach(function (button) {
                button.addEventListener('click', function () {
                    resolveConfirm(false);
                });
            });

            element.querySelector('[data-admin-confirm-ok]').addEventListener('click', function () {
                resolveConfirm(true);
            });
        }

        return element;
    }

    function resolveConfirm(value) {
        if (!confirmState) {
            return;
        }

        confirmState.element.hidden = true;
        confirmState.resolve(value);
        confirmState = null;
    }

    function confirm(message, options) {
        var element = ensureConfirm();
        var opts = options || {};

        element.querySelector('[data-admin-confirm-message]').textContent = message || adminT('confirm_message', 'Are you sure?');
        element.querySelector('.admin-confirm-title').textContent = opts.title || adminT('confirm_title', 'Confirm action');
        element.querySelector('[data-admin-confirm-ok]').textContent = opts.confirmLabel || adminT('confirm_label', 'Confirm');
        element.querySelector('[data-admin-confirm-cancel]').textContent = opts.cancelLabel || adminT('cancel_label', 'Cancel');
        element.hidden = false;
        bootIcons();

        return new Promise(function (resolve) {
            confirmState = { element: element, resolve: resolve };
            element.querySelector('[data-admin-confirm-ok]').focus();
        });
    }

    function initializeFormStates() {
        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.adminFormStateReady === '1') {
                return;
            }

            form.dataset.adminFormStateReady = '1';

            form.addEventListener('submit', function (event) {
                if (event.defaultPrevented) {
                    return;
                }

                if (form.dataset.adminConfirmed === '1') {
                    form.classList.add('admin-is-submitting');
                    return;
                }

                var submitter = event.submitter;
                var message = submitter?.dataset?.adminConfirm || form.dataset.adminConfirm;

                if (!message && (submitter?.dataset?.crmConfirm || form.dataset.crmConfirm)) {
                    return;
                }

                if (!message) {
                    form.classList.add('admin-is-submitting');
                    return;
                }

                event.preventDefault();

                confirm(message).then(function (ok) {
                    if (!ok) {
                        return;
                    }

                    form.dataset.adminConfirmed = '1';
                    form.classList.add('admin-is-submitting');

                    if (submitter && submitter.name) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = submitter.name;
                        hidden.value = submitter.value;
                        form.appendChild(hidden);
                    }

                    form.requestSubmit();
                });
            });
        });
    }

    function initializeCustomSelects() {
        document.querySelectorAll('[data-admin-select]').forEach(function (group) {
            if (group.dataset.adminSelectReady === '1') {
                return;
            }

            var native = group.querySelector('[data-admin-select-native]');

            if (!native) {
                return;
            }

            var multiple = native.multiple;
            var placeholder = group.dataset.adminSelectPlaceholder || adminT('select_placeholder', 'Select an option');
            var searchable = group.dataset.adminSelectSearchable !== '0';
            var clearable = group.dataset.adminSelectClearable !== '0';
            var wrapper = createElement(
                '<div class="admin-select">' +
                    '<button type="button" class="admin-select-trigger" aria-haspopup="listbox" aria-expanded="false">' +
                        '<span class="admin-select-value"></span>' +
                        (clearable ? '<span class="admin-select-clear" title="' + adminT('clear', 'Clear') + '" hidden><i data-lucide="x" width="14" height="14"></i></span>' : '') +
                        '<i class="admin-select-chevron" data-lucide="chevron-down" width="16" height="16"></i>' +
                    '</button>' +
                    '<div class="admin-select-panel">' +
                        (searchable ? '<div class="admin-select-search"><input class="form-control form-control-sm" type="search" autocomplete="off" placeholder="' + adminT('search', 'Search') + '"></div>' : '') +
                        '<div class="admin-select-options" role="listbox"></div>' +
                        '<div class="admin-select-empty">' + adminT('no_options_found', 'No options found') + '</div>' +
                    '</div>' +
                '</div>'
            );
            var trigger = wrapper.querySelector('.admin-select-trigger');
            var value = wrapper.querySelector('.admin-select-value');
            var clear = wrapper.querySelector('.admin-select-clear');
            var optionsList = wrapper.querySelector('.admin-select-options');
            var search = wrapper.querySelector('.admin-select-search input');

            // Portal: detach panel to <body> so position:fixed is always relative to viewport
            // regardless of any ancestor transform/filter that would create a new containing block.
            var panel = wrapper.querySelector('.admin-select-panel');
            panel.parentNode.removeChild(panel);
            panel.style.display = 'none';
            document.body.appendChild(panel);
            wrapper._selectPanel = panel;

            function positionPanel() {
                var rect = trigger.getBoundingClientRect();
                var spaceBelow = window.innerHeight - rect.bottom;
                var panelHeight = Math.min(panel.scrollHeight || 200, 280);
                var openUpward = spaceBelow < panelHeight + 8 && rect.top > panelHeight + 8;
                if (openUpward) {
                    panel.style.top = (rect.top - panelHeight - 4) + 'px';
                } else {
                    panel.style.top = (rect.bottom + 4) + 'px';
                }
                panel.style.left = rect.left + 'px';
                panel.style.width = rect.width + 'px';
            }

            if (native.disabled) {
                wrapper.classList.add('is-disabled');
            }

            if (native.classList.contains('is-invalid')) {
                wrapper.classList.add('is-invalid');
            }

            function selectedOptions() {
                return Array.from(native.options).filter(function (option) {
                    return option.selected && option.value !== '';
                });
            }

            function close() {
                wrapper.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                panel.style.display = 'none';
            }

            function renderValue() {
                var selected = selectedOptions();
                value.innerHTML = '';

                if (selected.length === 0) {
                    var empty = document.createElement('span');
                    empty.className = 'admin-select-placeholder';
                    empty.textContent = placeholder;
                    value.appendChild(empty);
                } else if (multiple) {
                    selected.forEach(function (option) {
                        var chip = document.createElement('span');
                        chip.className = 'admin-select-chip';
                        chip.innerHTML = '<span></span>';
                        chip.querySelector('span').textContent = option.textContent.trim();
                        value.appendChild(chip);
                    });
                } else {
                    value.textContent = selected[0].textContent.trim();
                }

                if (clear) {
                    clear.hidden = selected.length === 0;
                }
            }

            function renderOptions(filter) {
                var term = (filter || '').toLowerCase();
                var visible = 0;
                optionsList.innerHTML = '';

                Array.from(native.options).forEach(function (option) {
                    if (option.value === '') {
                        return;
                    }

                    var label = option.textContent.trim();

                    if (term && !label.toLowerCase().includes(term)) {
                        return;
                    }

                    visible += 1;

                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'admin-select-option';
                    button.textContent = label;
                    button.dataset.value = option.value;

                    if (option.selected) {
                        button.classList.add('is-selected');
                    }

                    button.addEventListener('click', function () {
                        if (multiple) {
                            option.selected = !option.selected;
                        } else {
                            Array.from(native.options).forEach(function (item) {
                                item.selected = item === option;
                            });
                            close();
                        }

                        native.dispatchEvent(new Event('change', { bubbles: true }));
                        renderValue();
                        renderOptions(search?.value || '');
                    });

                    optionsList.appendChild(button);
                });

                wrapper.classList.toggle('is-empty', visible === 0);
            }

            trigger.addEventListener('click', function () {
                if (native.disabled) {
                    return;
                }

                var nextOpen = !wrapper.classList.contains('is-open');
                document.querySelectorAll('.admin-select.is-open').forEach(function (select) {
                    if (select !== wrapper) {
                        select.classList.remove('is-open');
                        select.querySelector('.admin-select-trigger')?.setAttribute('aria-expanded', 'false');
                        if (select._selectPanel) {
                            select._selectPanel.style.display = 'none';
                        }
                    }
                });
                wrapper.classList.toggle('is-open', nextOpen);
                trigger.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
                panel.style.display = nextOpen ? 'block' : 'none';

                if (nextOpen) {
                    positionPanel();
                    search?.focus();
                }
            });

            clear?.addEventListener('click', function (event) {
                event.stopPropagation();
                Array.from(native.options).forEach(function (option) {
                    option.selected = false;
                });
                native.dispatchEvent(new Event('change', { bubbles: true }));
                renderValue();
                renderOptions(search?.value || '');
            });

            search?.addEventListener('input', function () {
                renderOptions(search.value);
            });

            document.addEventListener('click', function (event) {
                if (!wrapper.contains(event.target) && !panel.contains(event.target)) {
                    close();
                }
            });

            window.addEventListener('scroll', function () {
                if (wrapper.classList.contains('is-open')) {
                    positionPanel();
                }
            }, { passive: true, capture: true });

            window.addEventListener('resize', function () {
                if (wrapper.classList.contains('is-open')) {
                    positionPanel();
                }
            }, { passive: true });

            native.classList.add('admin-select-native-enhanced');
            native.after(wrapper);
            group.dataset.adminSelectReady = '1';
            renderValue();
            renderOptions('');
        });

        bootIcons();
    }

    function initializeFilterShells() {
        document.querySelectorAll('[data-admin-filter-toggle]').forEach(function (toggle) {
            if (toggle.dataset.adminFilterToggleReady === '1') {
                return;
            }

            toggle.dataset.adminFilterToggleReady = '1';

            toggle.addEventListener('click', function () {
                var form = toggle.closest('.admin-filter-shell');
                var panel = form?.querySelector('[data-admin-filter-advanced]');

                if (!panel) {
                    return;
                }

                var open = !panel.classList.contains('is-open');
                panel.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        document.querySelectorAll('[data-admin-filter-saved-toggle]').forEach(function (toggle) {
            if (toggle.dataset.adminFilterSavedToggleReady === '1') {
                return;
            }

            toggle.dataset.adminFilterSavedToggleReady = '1';

            toggle.addEventListener('click', function () {
                var card = toggle.closest('.admin-filter-shell-card');
                var panel = card?.querySelector('[data-admin-filter-saved]');

                if (!panel) {
                    return;
                }

                var open = !panel.classList.contains('is-open');
                panel.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    function initializeBulkActions() {
        document.querySelectorAll('[data-admin-bulk-actions]').forEach(function (bar) {
            if (bar.dataset.adminBulkReady === '1') {
                return;
            }

            var form = document.getElementById(bar.dataset.formId);

            if (!form) {
                return;
            }

            var selector = bar.dataset.checkboxSelector || 'input[type="checkbox"]';
            var countNode = bar.querySelector('[data-admin-bulk-count]');
            var moduleEl = form.closest('[data-crm-module]');
            var exportModule = moduleEl ? moduleEl.dataset.crmModule : null;

            if (exportModule && selectedIdsMap[exportModule]) {
                form.querySelectorAll(selector).forEach(function (checkbox) {
                    if (checkbox.value && selectedIdsMap[exportModule].has(String(checkbox.value))) {
                        checkbox.checked = true;
                    }
                });
            }

            function update() {
                var checkboxes = Array.from(form.querySelectorAll(selector)).filter(function (checkbox) {
                    return !checkbox.disabled;
                });
                var count = checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                }).length;

                if (exportModule) {
                    if (!selectedIdsMap[exportModule]) {
                        selectedIdsMap[exportModule] = new Set();
                    }
                    form.querySelectorAll(selector).forEach(function (checkbox) {
                        if (!checkbox.value) {
                            return;
                        }
                        if (checkbox.checked) {
                            selectedIdsMap[exportModule].add(String(checkbox.value));
                        } else {
                            selectedIdsMap[exportModule].delete(String(checkbox.value));
                        }
                    });
                }

                var totalCount = (exportModule && selectedIdsMap[exportModule]) ? selectedIdsMap[exportModule].size : count;
                bar.hidden = totalCount === 0;

                if (countNode) {
                    countNode.textContent = String(totalCount);
                }

                form.querySelectorAll('[data-admin-bulk-toggle-all]').forEach(function (toggle) {
                    toggle.checked = checkboxes.length > 0 && count === checkboxes.length;
                    toggle.indeterminate = count > 0 && count < checkboxes.length;
                });
            }

            form.addEventListener('change', function (event) {
                if (event.target.matches(selector) || event.target.closest(selector)) {
                    update();
                }
            });

            form.querySelectorAll('[data-admin-bulk-toggle-all]').forEach(function (toggle) {
                toggle.addEventListener('change', function () {
                    form.querySelectorAll(selector).forEach(function (checkbox) {
                        if (!checkbox.disabled) {
                            checkbox.checked = toggle.checked;
                        }
                    });
                    update();
                });
            });

            bar.dataset.adminBulkReady = '1';
            update();
        });
    }

    function replaceAjaxList(current, responseText) {
        var advancedOpen = !!current.querySelector('[data-admin-filter-advanced].is-open');
        var savedOpen = !!current.querySelector('[data-admin-filter-saved].is-open');

        var parser = new DOMParser();
        var documentFragment = parser.parseFromString(responseText, 'text/html');
        var selector = '#' + current.id;
        var next = documentFragment.querySelector(selector);

        if (!next) {
            window.location.href = current.dataset.adminLastUrl || window.location.href;
            return;
        }

        current.replaceWith(next);
        if (window.AdminPanel && typeof window.AdminPanel.rehydrate === 'function') {
            window.AdminPanel.rehydrate();
        } else {
            rehydrateAdminUi();
        }

        if (advancedOpen) {
            var advPanel = next.querySelector('[data-admin-filter-advanced]');
            var advToggle = next.querySelector('[data-admin-filter-toggle]');
            if (advPanel) { advPanel.classList.add('is-open'); }
            if (advToggle) { advToggle.setAttribute('aria-expanded', 'true'); }
        }

        if (savedOpen) {
            var savedPanel = next.querySelector('[data-admin-filter-saved]');
            var savedToggle = next.querySelector('[data-admin-filter-saved-toggle]');
            if (savedPanel) { savedPanel.classList.add('is-open'); }
            if (savedToggle) { savedToggle.setAttribute('aria-expanded', 'true'); }
        }
    }

    function initializeAjaxLists() {
        document.querySelectorAll('[data-admin-ajax-list]').forEach(function (container) {
            if (!container.id) {
                return;
            }

            if (container.dataset.adminAjaxReady === '1') {
                return;
            }

            container.dataset.adminAjaxReady = '1';

            var form = container.querySelector('[data-admin-ajax-filter-form]');
            var paginationForms = container.querySelectorAll('[data-admin-ajax-pagination-form]');
            var requestController = null;
            var searchTimer = null;

            async function load(url, options) {
                try {
                    container.classList.add('is-loading');
                    container.dataset.adminLastUrl = url;

                    if (requestController) {
                        requestController.abort();
                    }

                    requestController = new AbortController();

                    var response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: requestController.signal,
                        method: options?.method || 'GET',
                    });

                    if (!response.ok) {
                        throw new Error('Ajax list request failed');
                    }

                    var html = await response.text();
                    replaceAjaxList(container, html);

                    if (options?.push !== false) {
                        window.history.pushState({ adminAjaxList: true }, '', url);
                    }
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    window.location.href = url;
                } finally {
                    container.classList.remove('is-loading');
                }
            }

            container.__adminAjaxLoad = load;

            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    var params = new URLSearchParams(new FormData(form));
                    var url = form.action + (params.toString() ? '?' + params.toString() : '');
                    load(url);
                });

                if (form.dataset.adminAutoSubmit === '1') {
                    form.querySelectorAll('select, input[type="date"], input[type="number"]').forEach(function (field) {
                        field.addEventListener('change', function () {
                            form.requestSubmit();
                        });
                    });

                    form.querySelectorAll('input[type="search"], input[type="text"]').forEach(function (field) {
                        field.addEventListener('input', function () {
                            window.clearTimeout(searchTimer);
                            searchTimer = window.setTimeout(function () {
                                form.requestSubmit();
                            }, 300);
                        });
                    });
                }
            }

            paginationForms.forEach(function (paginationForm) {
                if (paginationForm.dataset.adminAjaxPaginationReady === '1') {
                    return;
                }

                paginationForm.dataset.adminAjaxPaginationReady = '1';

                function submitPagination() {
                    var action = paginationForm.tagName === 'FORM'
                        ? paginationForm.action
                        : (paginationForm.dataset.action || window.location.href);
                    var params = new URLSearchParams();
                    paginationForm.querySelectorAll('input[name], select[name]').forEach(function (el) {
                        if (el.name && el.value !== '') {
                            params.set(el.name, el.value);
                        }
                    });
                    var url = action + (params.toString() ? '?' + params.toString() : '');
                    load(url);
                }

                if (paginationForm.tagName === 'FORM') {
                    paginationForm.addEventListener('submit', function (event) {
                        event.preventDefault();
                        submitPagination();
                    });
                }

                paginationForm.querySelectorAll('select').forEach(function (field) {
                    field.addEventListener('change', function () {
                        submitPagination();
                    });
                });
            });

            container.addEventListener('click', function (event) {
                var link = event.target.closest('a[data-admin-ajax-link], .pagination a');

                if (!link || !container.contains(link)) {
                    return;
                }

                var href = link.getAttribute('href');

                if (!href || href.startsWith('#')) {
                    return;
                }

                event.preventDefault();
                load(href);
            });
        });
    }

    function initializeAjaxTargetLinks() {
        if (document.body.dataset.adminAjaxTargetLinksReady === '1') {
            return;
        }

        document.body.dataset.adminAjaxTargetLinksReady = '1';

        document.addEventListener('click', function (event) {
            var link = event.target.closest('a[data-admin-ajax-link][data-admin-ajax-target]');

            if (!link) {
                return;
            }

            var targetId = link.dataset.adminAjaxTarget;
            var container = targetId ? document.getElementById(targetId) : null;
            var href = link.getAttribute('href');

            if (!container || typeof container.__adminAjaxLoad !== 'function' || !href || href.startsWith('#')) {
                return;
            }

            event.preventDefault();
            container.__adminAjaxLoad(href);
        });
    }

    function initializeDragToReorder(list) {
        var dragSrc = null;

        function clearDragClasses() {
            list.querySelectorAll('[draggable="true"]').forEach(function (item) {
                item.classList.remove('is-drag-over-top', 'is-drag-over-bottom');
            });
        }

        list.addEventListener('dragstart', function (e) {
            var item = e.target.closest('[draggable="true"]');
            if (!item) { return; }
            dragSrc = item;
            item.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        list.addEventListener('dragend', function () {
            if (dragSrc) { dragSrc.classList.remove('is-dragging'); }
            dragSrc = null;
            clearDragClasses();
        });

        list.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var target = e.target.closest('[draggable="true"]');
            if (!target || target === dragSrc) { clearDragClasses(); return; }
            clearDragClasses();
            var rect = target.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                target.classList.add('is-drag-over-top');
            } else {
                target.classList.add('is-drag-over-bottom');
            }
        });

        list.addEventListener('dragleave', function (e) {
            if (!list.contains(e.relatedTarget)) { clearDragClasses(); }
        });

        list.addEventListener('drop', function (e) {
            e.preventDefault();
            var target = e.target.closest('[draggable="true"]');
            if (!target || !dragSrc || target === dragSrc) { clearDragClasses(); return; }
            var rect = target.getBoundingClientRect();
            if (e.clientY < rect.top + rect.height / 2) {
                list.insertBefore(dragSrc, target);
            } else {
                list.insertBefore(dragSrc, target.nextSibling);
            }
            clearDragClasses();
        });
    }

    function initializeExportButtons() {
        document.querySelectorAll('[data-admin-export-button]').forEach(function (container) {
            if (container.dataset.adminExportReady === '1') { return; }
            container.dataset.adminExportReady = '1';

            var exportModule = container.dataset.exportModule;
            var trigger = container.querySelector('[data-export-trigger]');
            var backdrop = container.querySelector('[data-export-backdrop]');
            var form = container.querySelector('[data-export-form]');
            var columnList = container.querySelector('[data-export-column-list]');
            var selectedCountEl = container.querySelector('[data-export-selected-count]');
            var totalCountEl = container.querySelector('[data-export-total-count]');
            var scopeSelectedLabel = container.querySelector('[data-export-scope-selected]');
            var submitBtn = container.querySelector('[data-export-submit]');

            function openModal() {
                var selectedIds = (exportModule && selectedIdsMap[exportModule]) ? selectedIdsMap[exportModule] : new Set();
                var selectedCount = selectedIds.size;

                if (selectedCountEl) { selectedCountEl.textContent = String(selectedCount); }

                var totalEl = document.querySelector('[data-export-total]');
                if (totalCountEl) { totalCountEl.textContent = totalEl ? totalEl.dataset.exportTotal : '—'; }

                if (scopeSelectedLabel) { scopeSelectedLabel.hidden = selectedCount === 0; }

                var scopeRadios = container.querySelectorAll('input[type="radio"][name^="export-scope"]');
                scopeRadios.forEach(function (r) {
                    r.checked = selectedCount > 0 ? r.value === 'selected' : r.value === 'filtered';
                });

                backdrop.hidden = false;
                container.querySelector('[data-export-close]')?.focus();
            }

            function closeModal() {
                backdrop.hidden = true;
            }

            if (trigger) { trigger.addEventListener('click', openModal); }

            if (backdrop) {
                backdrop.addEventListener('click', function (e) {
                    if (e.target === backdrop) { closeModal(); }
                });
            }

            container.querySelectorAll('[data-export-close]').forEach(function (btn) {
                btn.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && backdrop && !backdrop.hidden) { closeModal(); }
            });

            container.querySelectorAll('[data-export-format]').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    container.querySelectorAll('[data-export-format]').forEach(function (t) { t.classList.remove('is-active'); });
                    tab.classList.add('is-active');
                });
            });

            var checkAllBtn = container.querySelector('[data-export-check-all]');
            var checkNoneBtn = container.querySelector('[data-export-check-none]');

            if (checkAllBtn) {
                checkAllBtn.addEventListener('click', function () {
                    if (columnList) {
                        columnList.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = true; });
                    }
                });
            }

            if (checkNoneBtn) {
                checkNoneBtn.addEventListener('click', function () {
                    if (columnList) {
                        columnList.querySelectorAll('input[type="checkbox"]').forEach(function (cb) { cb.checked = false; });
                    }
                });
            }

            if (submitBtn) {
                submitBtn.addEventListener('click', function () {
                    var activeFormatTab = container.querySelector('[data-export-format].is-active');
                    var format = activeFormatTab ? activeFormatTab.dataset.exportFormat : 'csv';

                    var columns = [];
                    if (columnList) {
                        columnList.querySelectorAll('.export-column-item').forEach(function (item) {
                            var cb = item.querySelector('input[type="checkbox"]');
                            if (cb && cb.checked && item.dataset.columnKey) {
                                columns.push(item.dataset.columnKey);
                            }
                        });
                    }

                    if (columns.length === 0) { return; }

                    var scopeRadio = container.querySelector('input[name^="export-scope"]:checked');
                    var scope = scopeRadio ? scopeRadio.value : 'filtered';

                    form.querySelectorAll('[data-dynamic]').forEach(function (el) { el.remove(); });

                    function addField(name, value) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        input.dataset.dynamic = '1';
                        form.appendChild(input);
                    }

                    addField('format', format);
                    columns.forEach(function (key) { addField('columns[]', key); });

                    if (scope === 'selected' && exportModule && selectedIdsMap[exportModule]) {
                        selectedIdsMap[exportModule].forEach(function (id) { addField('ids[]', id); });
                    } else {
                        var urlParams = new URLSearchParams(window.location.search);
                        urlParams.forEach(function (value, key) { addField(key, value); });
                    }

                    form.submit();
                    closeModal();
                });
            }

            if (columnList) { initializeDragToReorder(columnList); }
        });
    }

    function rehydrateAdminUi() {
        initializeFormStates();
        initializeCustomSelects();
        initializeFilterShells();
        initializeBulkActions();
        initializeAjaxLists();
        initializeAjaxTargetLinks();
        initializeCommandPalette();
        initializeNotifications();
        initializeExportButtons();
        bootIcons();
    }

    function initializeCommandPalette() {
        commandPalette = document.querySelector('[data-admin-command-palette]');

        if (!commandPalette) {
            return;
        }

        if (commandPalette.dataset.adminCommandReady === '1') {
            return;
        }

        commandPalette.dataset.adminCommandReady = '1';

        var input = commandPalette.querySelector('[data-admin-command-input]');
        var queryInput = commandPalette.querySelector('[data-admin-command-query]');
        var items = Array.from(commandPalette.querySelectorAll('[data-admin-command-item]'));

        function open() {
            commandPalette.hidden = false;
            input.value = '';
            filter('');
            window.setTimeout(function () {
                input.focus();
            }, 0);
        }

        function close() {
            commandPalette.hidden = true;
        }

        function filter(term) {
            var normalized = term.toLowerCase().trim();

            if (queryInput) {
                queryInput.value = term;
            }

            items.forEach(function (item) {
                item.classList.toggle('is-hidden', normalized !== '' && !item.dataset.label.includes(normalized));
            });
        }

        document.querySelectorAll('[data-admin-command-trigger]').forEach(function (trigger) {
            trigger.addEventListener('click', open);
        });

        commandPalette.querySelectorAll('[data-admin-command-close]').forEach(function (trigger) {
            trigger.addEventListener('click', close);
        });

        input.addEventListener('input', function () {
            filter(input.value);
        });

        document.addEventListener('keydown', function (event) {
            var key = event.key.toLowerCase();
            var target = event.target;
            var isTyping = target && ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName);
            var isShortcut = (event.metaKey || event.ctrlKey) && key === 'k';
            var isSlash = key === '/' && !isTyping;

            if (isShortcut || isSlash) {
                event.preventDefault();
                open();
                return;
            }

            if (key === 'escape' && commandPalette && !commandPalette.hidden) {
                close();
            }
        });
    }

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function notificationReadUrl(widget, id) {
        return (widget.dataset.readUrlPattern || '').replace('__NOTIFICATION__', id);
    }

    function setNotificationState(widget, state) {
        var list = widget.querySelector('[data-admin-notifications-list]');
        var empty = widget.querySelector('[data-admin-notifications-empty]');
        var loading = widget.querySelector('[data-admin-notifications-loading]');
        var error = widget.querySelector('[data-admin-notifications-error]');

        if (loading) {
            loading.hidden = state !== 'loading';
        }

        if (error) {
            error.hidden = state !== 'error';
        }

        if (empty) {
            empty.hidden = state !== 'empty';
        }

        if (list) {
            list.hidden = state !== 'list';
        }
    }

    function renderNotificationItem(item) {
        var href = item.url || '#';
        var time = item.relative_time || '';
        var variant = item.variant || 'info';
        var unreadClass = item.unread ? ' is-unread' : '';
        var unreadDot = item.unread ? '<span class="topbar-notif-unread-dot"></span>' : '';

        return '' +
            '<a href="' + href + '" class="topbar-notif-item is-variant-' + variant + unreadClass + '" data-admin-notification-item data-notification-id="' + item.id + '" data-notification-unread="' + (item.unread ? '1' : '0') + '">' +
                '<span class="topbar-notif-item-icon"><i data-lucide="' + (item.icon || 'bell') + '" width="16" height="16"></i></span>' +
                '<span class="topbar-notif-item-body">' +
                    '<span class="topbar-notif-item-title">' +
                        '<strong>' + unreadDot + item.title + '</strong>' +
                        '<span>' + time + '</span>' +
                    '</span>' +
                    '<span class="topbar-notif-item-text">' + item.body + '</span>' +
                '</span>' +
            '</a>';
    }

    function updateNotificationWidget(widget, payload) {
        var badge = widget.querySelector('[data-admin-notifications-badge]');
        var list = widget.querySelector('[data-admin-notifications-list]');
        var readAll = widget.querySelector('[data-admin-notifications-read-all]');
        var summary = widget.querySelector('[data-admin-notifications-summary]');
        var unreadCount = payload.unread_count || 0;
        var items = payload.items || [];

        if (badge) {
            badge.hidden = unreadCount === 0;
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
        }

        if (list) {
            list.innerHTML = items.map(renderNotificationItem).join('');
        }

        if (readAll) {
            readAll.hidden = unreadCount === 0;
        }

        if (summary) {
            if (unreadCount > 0) {
                summary.textContent = adminT('notifications_unread', ':count unread', { count: unreadCount });
            } else if (items.length > 0) {
                summary.textContent = adminT('notifications_recent_updates', 'Recent updates');
            } else {
                summary.textContent = adminT('notifications_all_caught_up', 'All caught up');
            }
        }

        setNotificationState(widget, items.length > 0 ? 'list' : 'empty');

        bootIcons();
    }

    async function loadNotifications(widget, options) {
        var endpoint = widget.dataset.endpoint;
        var requestId = String((Number(widget.dataset.notificationRequestId || '0') + 1));

        if (!endpoint) {
            return;
        }

        widget.dataset.notificationRequestId = requestId;

        try {
            if (options?.silent !== true) {
                setNotificationState(widget, 'loading');
            }

            var response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('Notification request failed');
            }

            var payload = await response.json();

            if (widget.dataset.notificationRequestId !== requestId) {
                return;
            }

            updateNotificationWidget(widget, payload);
        } catch (_error) {
            if (widget.dataset.notificationRequestId !== requestId) {
                return;
            }

            setNotificationState(widget, 'error');
        }
    }

    async function postNotificationAction(url) {
        var response = await fetch(url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({}),
        });

        if (!response.ok) {
            throw new Error('Notification action failed');
        }

        return response.json();
    }

    function initializeNotifications() {
        var widgets = Array.from(document.querySelectorAll('[data-admin-notifications]'));

        if (widgets.length === 0) {
            return;
        }

        widgets.forEach(function (widget) {
            if (widget.dataset.adminNotificationsReady === '1') {
                return;
            }

            widget.dataset.adminNotificationsReady = '1';

            var toggle = widget.querySelector('[data-admin-notifications-toggle]');
            var list = widget.querySelector('[data-admin-notifications-list]');
            var readAll = widget.querySelector('[data-admin-notifications-read-all]');

            toggle?.addEventListener('click', function () {
                window.setTimeout(function () {
                    var hasItems = list && list.children.length > 0;
                    loadNotifications(widget, { silent: hasItems });
                }, 0);
            });

            readAll?.addEventListener('click', async function () {
                try {
                    var payload = await postNotificationAction(widget.dataset.readAllUrl);
                    updateNotificationWidget(widget, payload);
                    toast(adminT('notifications_marked_read', 'Notifications marked as read.'), 'success');
                } catch (_error) {
                    toast(adminT('notifications_update_failed', 'Notifications could not be updated.'), 'danger');
                }
            });

            list?.addEventListener('click', async function (event) {
                var item = event.target.closest('[data-admin-notification-item]');

                if (!item || item.dataset.notificationUnread !== '1') {
                    return;
                }

                event.preventDefault();

                try {
                    var payload = await postNotificationAction(notificationReadUrl(widget, item.dataset.notificationId));
                    updateNotificationWidget(widget, payload);
                } catch (_error) {
                    toast(adminT('notification_mark_read_failed', 'Notification could not be marked as read.'), 'danger');
                }

                var href = item.getAttribute('href');

                if (href && href !== '#') {
                    window.location.href = href;
                }
            });

            loadNotifications(widget, { silent: true });
        });

        if (!notificationVisibilityReady) {
            notificationVisibilityReady = true;

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    document.querySelectorAll('[data-admin-notifications]').forEach(function (widget) {
                        loadNotifications(widget, { silent: true });
                    });
                }
            });
        }

        if (!notificationTimer) {
            notificationTimer = window.setInterval(function () {
                if (document.visibilityState !== 'visible') {
                    return;
                }

                document.querySelectorAll('[data-admin-notifications]').forEach(function (widget) {
                    loadNotifications(widget, { silent: true });
                });
            }, 3000);
        }
    }

    window.AdminPanel = Object.assign(window.AdminPanel || {}, {
        confirm: confirm,
        toast: toast,
        trans: adminT,
        translations: adminTranslations,
        rehydrate: rehydrateAdminUi,
        refreshIcons: bootIcons,
    });

    if (!window.Alpine) {
        loadScript('https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.13.3/dist/cdn.min.js');
        loadScript('https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js');
    }

    if (!window.lucide) {
        loadScript('https://unpkg.com/lucide@latest', bootIcons);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            rehydrateAdminUi();
            window.addEventListener('popstate', function (event) {
                if (event.state?.adminAjaxList) {
                    window.location.reload();
                }
            });
        });
    } else {
        rehydrateAdminUi();
        window.addEventListener('popstate', function (event) {
            if (event.state?.adminAjaxList) {
                window.location.reload();
            }
        });
    }
})();
