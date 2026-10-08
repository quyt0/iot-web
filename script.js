const IoT = (() => {
    async function api(url, options = {}) {
        const init = { method: options.method || 'GET', headers: { Accept: 'application/json' }, credentials: 'same-origin' };
        if (options.body !== undefined) {
            init.headers['Content-Type'] = 'application/json';
            init.body = JSON.stringify(options.body);
        }

        let response;
        try {
            response = await fetch(url, init);
        } catch (e) {
            throw new Error('Không thể kết nối tới máy chủ');
        }

        if (response.status === 401) {
            window.location.href = 'login.php';
            throw new Error('Phiên đăng nhập đã hết hạn');
        }

        let body = null;
        try {
            body = await response.json();
        } catch (e) {
        }
        if (!response.ok || !body || body.status !== 'success') {
            throw new Error((body && body.message) || `Lỗi máy chủ (HTTP ${response.status})`);
        }
        return body;
    }

    function realtime({ events = {}, onSync, pollInterval = 2000 }) {
        let pollTimer = null;
        const startPolling = () => {
            if (!pollTimer) pollTimer = setInterval(onSync, pollInterval);
        };
        const stopPolling = () => {
            clearInterval(pollTimer);
            pollTimer = null;
        };

        if (!window.EventSource) {
            startPolling();
            return;
        }

        const source = new EventSource('api/events.php');
        source.onopen = () => {
            stopPolling();
            onSync();
        };
        source.onerror = () => startPolling();
        Object.entries(events).forEach(([name, handler]) => {
            source.addEventListener(name, (e) => {
                try {
                    handler(JSON.parse(e.data));
                } catch (err) {
                    console.error(`Lỗi xử lý sự kiện ${name}:`, err);
                }
            });
        });
    }

    function isValidTimeFilter(value) {
        const m = /^(\d{4})-(\d{2})-(\d{2})(?: (\d{2})(?::(\d{2})(?::(\d{2}))?)?)?$/.exec(value);
        if (!m) return false;
        const [y, mo, d] = [+m[1], +m[2], +m[3]];
        const date = new Date(y, mo - 1, d);
        if (date.getFullYear() !== y || date.getMonth() !== mo - 1 || date.getDate() !== d) return false;
        return (m[4] === undefined || +m[4] < 24) && (m[5] === undefined || +m[5] < 60) && (m[6] === undefined || +m[6] < 60);
    }

    function renderTableRows(table, rows, columnCount, emptyText) {
        const body = table.tBodies[0];
        while (body.rows.length > 1) body.deleteRow(1);

        if (!rows.length) {
            const td = body.insertRow().insertCell();
            td.colSpan = columnCount;
            td.textContent = emptyText;
            return;
        }

        rows.forEach((cells) => {
            const tr = body.insertRow();
            cells.forEach((cell) => {
                const td = tr.insertCell();
                const isObject = cell !== null && typeof cell === 'object';
                td.textContent = isObject ? cell.text : (cell ?? '');
                if (isObject && cell.title) td.title = cell.title;
            });
        });
    }

    function pageNumbers(page, lastPage) {
        const candidates = [1, lastPage, page - 1, page, page + 1, page === 1 ? 3 : 0];
        const pages = [...new Set(candidates.filter((p) => p >= 1 && p <= lastPage))].sort((a, b) => a - b);
        const result = [];
        pages.forEach((p, i) => {
            if (i > 0 && p - pages[i - 1] > 1) result.push('...');
            result.push(p);
        });
        return result;
    }

    function renderPagination(nav, prefix, { page, size, totalItems, totalPages }, onPage) {
        const from = totalItems === 0 ? 0 : (page - 1) * size + 1;
        const to = Math.min(page * size, totalItems);
        nav.querySelector(`.${prefix}-pagination-info`).textContent = `Hiển thị ${from} - ${to} trong tổng số ${totalItems} bản ghi`;

        const holder = nav.querySelector(`.${prefix}-pagination-page-item`);
        holder.innerHTML = '';
        const addButton = (label, target, { active = false, disabled = false } = {}) => {
            const button = document.createElement('button');
            button.className = `${prefix}-pagination-page-btn` + (active ? ' active' : '');
            button.textContent = label;
            button.disabled = disabled;
            if (!disabled && !active) button.addEventListener('click', () => onPage(target));
            holder.appendChild(button);
        };

        const lastPage = Math.max(totalPages, 1);
        addButton('<<', page - 1, { disabled: page <= 1 });
        pageNumbers(page, lastPage).forEach((p) => {
            if (p === '...') addButton('...', null, { disabled: true });
            else addButton(String(p), p, { active: p === page });
        });
        addButton('>>', page + 1, { disabled: page >= lastPage });
    }

    function bindSearch(button, inputs, onSearch) {
        button.addEventListener('click', onSearch);
        inputs.forEach((input) => input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') onSearch();
        }));
    }

    return { api, realtime, isValidTimeFilter, renderTableRows, renderPagination, bindSearch };
})();
