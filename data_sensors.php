<?php
    if (!defined('IN_APP')) {
        header('Location: index.php');
        exit;
    }
?>

<div class="dsr-content-wrap">
    <div class="dsr-fitler-wrap">
        <div class="row">
            <div class="col-10">
                <div class="dsr-filter-list">
                    <div class="dsr-filter-item-wrap">
                        <span class="dsr-filter-item-title">Loại cảm biến</span>
                        <select class="dsr-fitler-dropdown dsr-filter-data" name="dsr-drp-type">
                            <option value="">Tất cả</option>
                        </select>
                    </div>
                    <div class="dsr-filter-item-wrap">
                        <span class="dsr-filter-item-title">Từ khoá</span>
                        <input type="text" name="dsr-inp-name" class="dsr-filter-data">
                    </div>
                    <div class="dsr-filter-item-wrap">
                        <span class="dsr-filter-item-title">Thời gian</span>
                        <input type="text" name="dsr-inp-time" class="dsr-filter-data">
                    </div>
                </div>
            </div>
            <div class="col-2">
                <div class="dsr-btn-search-wrap">
                    <button class="btn dsr-btn-search">Tìm kiếm</button>
                </div>
            </div>
        </div>
    </div>
    <div class="dsr-table-wrap">
        <table class="dsr-main-table">
            <tr>
                <th>ID</th>
                <th>Tên cảm biến</th>
                <th>Giá trị</th>
                <th>Đơn vị</th>
                <th>Thời gian</th>
            </tr>
        </table>
        <nav class="dsr-pagination">
            <span class="dsr-pagination-info">Đang tải dữ liệu...</span>
            <ul class="dsr-pagination-controls">
                <li class="dsr-pagination-page-item"></li>
            </ul>
        </nav>
    </div>
</div>

<style>
    .dsr-content-wrap {
        padding-top: 24px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .dsr-fitler-wrap {
        background-color: white;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }

    .dsr-filter-list {
        display: flex;
        gap: 24px;
    }

    .dsr-filter-item-wrap {
        flex: 1;
    }

    .dsr-filter-item-title {
        display: block;
    }

    .dsr-filter-data {
        width: 100%;
        outline: none;
        border: 1px solid var(--primary-color);
        border-radius: 4px;
        height: 36px;

    }

    .dsr-btn-search-wrap {
        height: 100%;
        width: 100%;
        display: flex;
        align-items: flex-end;
    }

    .dsr-btn-search {
        height: 36px;
        border-radius: 4px;
        width: 100%;
    }
    .dsr-table-wrap {
        flex: 1;
    }
    .dsr-main-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        margin-bottom: 16px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }

    .dsr-main-table th {
        background-color: var(--primary-color);
        color: var(--white-text-color);
        padding: 8px;
    }

    .dsr-main-table td {
        padding: 8px 4px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .dsr-main-table tr:nth-child(odd){
        background-color: var(--border-color);
    }

    .dsr-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .dsr-pagination-info {
        color: var(--sub-text-color);
    }

    .dsr-pagination-controls {
        margin: 0;
        list-style: none;
    }

    .dsr-pagination-page-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background-color: white;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
    }

    .dsr-pagination-page-btn.active {
        background-color: var(--primary-color);
        color: var(--white-text-color)
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const PAGE_SIZE = 10;
        const root = document.querySelector('.dsr-content-wrap');
        const typeSelect = root.querySelector('[name="dsr-drp-type"]');
        const keywordInput = root.querySelector('[name="dsr-inp-name"]');
        const timeInput = root.querySelector('[name="dsr-inp-time"]');
        const table = root.querySelector('.dsr-main-table');
        const nav = root.querySelector('.dsr-pagination');

        let filters = { sensor_type: '', keyword: '', time: '' };

        IoT.api('api/sensors.php')
            .then(res => res.data.forEach(s => typeSelect.add(new Option(s.sensor_name, s.sensor_type))))
            .catch(err => console.error('Không thể tải danh sách cảm biến:', err.message));

        let currentPage = 1;

        function load(page, silent = false) {
            const params = new URLSearchParams({ page, size: PAGE_SIZE });
            Object.entries(filters).forEach(([key, value]) => { if (value) params.set(key, value); });

            IoT.api(`api/data-sensors.php?${params}`)
                .then(res => {
                    currentPage = res.data.page;
                    const rows = res.data.items.map(i => [i.id, i.sensor_name, i.value, i.unit, i.recorded_at]);
                    IoT.renderTableRows(table, rows, 5, 'Không tìm thấy dữ liệu');
                    IoT.renderPagination(nav, 'dsr', res.data, p => load(p));
                })
                .catch(err => {
                    const message = `Không thể tải dữ liệu cảm biến: ${err.message}`;
                    if (silent) console.error(message); else alert(message);
                });
        }

        function refreshLatest() {
            if (currentPage === 1) load(1, true);
        }

        function search() {
            const time = timeInput.value.trim();
            if (time && !IoT.isValidTimeFilter(time)) {
                alert('Thời gian phải theo định dạng YYYY-MM-DD hh:mm:ss');
                return;
            }
            filters = { sensor_type: typeSelect.value, keyword: keywordInput.value.trim(), time };
            load(1);
        }

        IoT.bindSearch(root.querySelector('.dsr-btn-search'), [keywordInput, timeInput], search);
        load(1);
        IoT.realtime({ onSync: refreshLatest, events: { telemetry: refreshLatest } });
    });
</script>
