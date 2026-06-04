/**
 * Admin Listing Component
 *
 * Server-side powered listing system with pagination, sorting, filtering, and search.
 */
class AdminListing {
    constructor(element, options = {}) {
        this.element = element;
        this.options = options;
        this.translations = window.AdminPanelTranslations || {};

        // Configuration
        this.endpoint = element.dataset.endpoint;
        this.perPage = parseInt(element.dataset.perPage) || 15;
        this.columns = options.columns || [];

        // State
        this.state = {
            page: 1,
            search: '',
            sort: null,
            direction: null,
            filters: {}
        };

        // DOM elements
        this.tbody = element.querySelector('[data-listing-body]');
        this.pagination = element.querySelector('[data-listing-pagination]');
        this.searchInput = element.querySelector('[data-listing-search]');
        this.filterInputs = element.querySelectorAll('[data-listing-filter]');
        this.exportBtn = element.querySelector('[data-listing-export]');
        this.sortHeaders = element.querySelectorAll('[data-sort-key]');

        this.init();
    }

    trans(key, fallback, replace = {}) {
        let message = this.translations[key];

        if (typeof message !== 'string') {
            message = fallback || key;
        }

        Object.keys(replace).forEach(token => {
            message = message.replace(`:${token}`, String(replace[token]));
        });

        return message;
    }

    init() {
        this.bindEvents();
        this.loadData();
    }

    bindEvents() {
        // Search with debounce
        if (this.searchInput) {
            let debounceTimer;
            this.searchInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    this.state.search = e.target.value;
                    this.state.page = 1;
                    this.loadData();
                }, 500);
            });
        }

        // Filters (instant)
        this.filterInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                this.state.filters[e.target.name] = e.target.value;
                this.state.page = 1;
                this.loadData();
            });
        });

        // Sorting
        this.sortHeaders.forEach(header => {
            header.addEventListener('click', () => {
                const key = header.dataset.sortKey;

                if (this.state.sort === key) {
                    // Cycle: asc -> desc -> null
                    if (this.state.direction === 'asc') {
                        this.state.direction = 'desc';
                    } else if (this.state.direction === 'desc') {
                        this.state.sort = null;
                        this.state.direction = null;
                    }
                } else {
                    this.state.sort = key;
                    this.state.direction = 'asc';
                }

                this.updateSortIndicators();
                this.loadData();
            });
        });

        // Export
        if (this.exportBtn) {
            this.exportBtn.addEventListener('click', () => {
                this.exportCSV();
            });
        }
    }

    async loadData() {
        try {
            this.showLoading();

            const params = new URLSearchParams({
                page: this.state.page,
                per_page: this.perPage,
            });

            if (this.state.search) {
                params.append('search', this.state.search);
            }

            if (this.state.sort && this.state.direction) {
                params.append('sort', this.state.sort);
                params.append('direction', this.state.direction);
            }

            Object.keys(this.state.filters).forEach(key => {
                if (this.state.filters[key]) {
                    params.append(key, this.state.filters[key]);
                }
            });

            const response = await fetch(`${this.endpoint}?${params}`);

            if (!response.ok) {
                throw new Error('Failed to load data');
            }

            const data = await response.json();

            this.renderRows(data.data);
            this.renderPagination(data.meta);

        } catch (error) {
            console.error('Listing error:', error);
            this.showError();
        }
    }

    renderRows(items) {
        if (!items || items.length === 0) {
            this.showEmpty();
            return;
        }

        this.tbody.innerHTML = items.map(item => this.renderRow(item)).join('');

        // Re-initialize Lucide icons
        if (window.lucide) {
            lucide.createIcons();
        }
    }

    renderRow(item) {
        const cells = this.columns.map(column => {
            const value = item[column.key];

            // If column key ends with _html, render as HTML
            if (column.key.endsWith('_html')) {
                return `<td>${value || ''}</td>`;
            }

            // Default: render plain text (escaped)
            return `<td>${this.escapeHtml(value) || ''}</td>`;
        });

        return `<tr>${cells.join('')}</tr>`;
    }

    renderPagination(meta) {
        if (!meta || meta.last_page <= 1) {
            this.pagination.innerHTML = '';
            return;
        }

        const { current_page, last_page, total } = meta;

        let html = '<div class="pagination">';

        // Previous button
        if (current_page > 1) {
            html += `<a href="#" data-page="${current_page - 1}">&laquo;</a>`;
        } else {
            html += `<span class="disabled">&laquo;</span>`;
        }

        // Page numbers
        const range = this.getPageRange(current_page, last_page);
        range.forEach(page => {
            if (page === '...') {
                html += `<span class="disabled">...</span>`;
            } else if (page === current_page) {
                html += `<span class="active">${page}</span>`;
            } else {
                html += `<a href="#" data-page="${page}">${page}</a>`;
            }
        });

        // Next button
        if (current_page < last_page) {
            html += `<a href="#" data-page="${current_page + 1}">&raquo;</a>`;
        } else {
            html += `<span class="disabled">&raquo;</span>`;
        }

        html += '</div>';

        this.pagination.innerHTML = html;

        // Bind pagination click events
        this.pagination.querySelectorAll('a[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.state.page = parseInt(link.dataset.page);
                this.loadData();
            });
        });
    }

    getPageRange(current, last) {
        const delta = 2;
        const range = [];
        const rangeWithDots = [];
        let l;

        for (let i = 1; i <= last; i++) {
            if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
                range.push(i);
            }
        }

        range.forEach(i => {
            if (l) {
                if (i - l === 2) {
                    rangeWithDots.push(l + 1);
                } else if (i - l !== 1) {
                    rangeWithDots.push('...');
                }
            }
            rangeWithDots.push(i);
            l = i;
        });

        return rangeWithDots;
    }

    updateSortIndicators() {
        this.sortHeaders.forEach(header => {
            const indicator = header.querySelector('.sort-indicator');
            if (!indicator) return;

            const key = header.dataset.sortKey;

            if (this.state.sort === key) {
                if (this.state.direction === 'asc') {
                    indicator.innerHTML = ' ↑';
                } else if (this.state.direction === 'desc') {
                    indicator.innerHTML = ' ↓';
                }
            } else {
                indicator.innerHTML = '';
            }
        });
    }

    async exportCSV() {
        try {
            const params = new URLSearchParams({
                export: 'csv',
            });

            if (this.state.search) {
                params.append('search', this.state.search);
            }

            if (this.state.sort && this.state.direction) {
                params.append('sort', this.state.sort);
                params.append('direction', this.state.direction);
            }

            Object.keys(this.state.filters).forEach(key => {
                if (this.state.filters[key]) {
                    params.append(key, this.state.filters[key]);
                }
            });

            window.location.href = `${this.endpoint}?${params}`;

        } catch (error) {
            console.error('Export error:', error);
            alert(this.trans('failed_to_export_data', 'Failed to export data'));
        }
    }

    showLoading() {
        this.tbody.innerHTML = `
            <tr>
                <td colspan="${this.columns.length}" class="table-empty">
                    <div class="spinner"></div>
                </td>
            </tr>
        `;
    }

    showEmpty() {
        const icon = this.options.emptyIcon || 'inbox';
        const message = this.options.emptyMessage || this.trans('no_data_found', 'No data found');

        this.tbody.innerHTML = `
            <tr>
                <td colspan="${this.columns.length}" class="table-empty">
                    <div class="d-flex flex-col items-center gap-2">
                        <i data-lucide="${icon}" width="48" height="48" style="opacity: 0.3;"></i>
                        <p class="mb-0">${message}</p>
                    </div>
                </td>
            </tr>
        `;

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    showError() {
        this.tbody.innerHTML = `
            <tr>
                <td colspan="${this.columns.length}" class="table-empty">
                    <div class="d-flex flex-col items-center gap-2">
                        <i data-lucide="alert-circle" width="48" height="48" style="opacity: 0.3; color: var(--danger);"></i>
                        <p class="mb-0">${this.trans('failed_to_load_data', 'Failed to load data')}</p>
                    </div>
                </td>
            </tr>
        `;

        if (window.lucide) {
            lucide.createIcons();
        }
    }

    escapeHtml(text) {
        if (typeof text !== 'string') return text;
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }
}

// Export to global scope
window.AdminListing = AdminListing;
