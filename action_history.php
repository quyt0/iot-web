<?php
    if (!defined('IN_APP')) {
        header('Location: index.php');
        exit;
    }
?>

<div class="ath-content-wrap">
    <div class="ath-fitler-wrap">
        <div class="row">
            <div class="col-10">
                <div class="ath-filter-list">
                    <div class="ath-filter-item-wrap">
                        <span class="ath-filter-item-title">Thiết bị</span>
                        <select class="ath-fitler-dropdown ath-filter-data" name="ath-drp-devices">
                            <option value="">Tất cả</option>
                            <?php foreach ((new DeviceDAO(db()))->all() as $historyDevice): ?>
                            <option value="<?= htmlspecialchars($historyDevice['device_code']) ?>"><?= htmlspecialchars($historyDevice['device_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ath-filter-item-wrap">
                        <span class="ath-filter-item-title">Trạng thái</span>
                        <select class="ath-fitler-dropdown ath-filter-data" name="ath-drp-status">
                            <option value="">Tất cả</option>
                            <option value="ON">ON</option>
                            <option value="OFF">OFF</option>
                            <option value="LOADING">LOADING</option>
                            <option value="TIMEOUT">TIMEOUT</option>
                            <option value="FAILED">FAILED</option>
                        </select>
                    </div>
                    <div class="ath-filter-item-wrap">
                        <span class="ath-filter-item-title">Hành động</span>
                        <select class="ath-fitler-dropdown ath-filter-data" name="ath-drp-actions">
                            <option value="">Tất cả</option>
                            <option value="TURN_ON">TURN ON</option>
                            <option value="TURN_OFF">TURN OFF</option>
                        </select>
                    </div>
                    <div class="ath-filter-item-wrap">
                        <span class="ath-filter-item-title">Thời gian</span>
                        <input type="text" name="ath-inp-time" class="ath-filter-data" placeholder="YYYY-MM-DD hh:mm:ss">
                    </div>
                </div>
            </div>
            <div class="col-2">
                <div class="ath-btn-search-wrap">
                    <button class="btn ath-btn-search">Tìm kiếm</button>
                </div>
            </div>
        </div>
    </div>
    <div class="ath-table-wrap">
        <table class="ath-main-table">
            <tr>
                <th>ID</th>
                <th>Thiết bị</th>
                <th>Hành động</th>
                <th>Trạng thái</th>
                <th>Time action</th>
                <th>Time status</th>
            </tr>
        </table>
        <nav class="ath-pagination">
            <span class="ath-pagination-info">Đang tải dữ liệu...</span>
            <ul class="ath-pagination-controls">
                <li class="ath-pagination-page-item"></li>
            </ul>
        </nav>
    </div>

</div>

<style>
    .ath-content-wrap {
        padding-top: 24px;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .ath-fitler-wrap {
        background-color: white;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 16px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }

    .ath-filter-list {
        display: flex;
        gap: 24px;
    }

    .ath-filter-item-wrap {
        flex: 1;
    }

    .ath-filter-item-title {
        display: block;
    }

    .ath-filter-data {
        width: 100%;
        outline: none;
        border: 1px solid var(--primary-color);
        border-radius: 4px;
        height: 36px;

    }

    .ath-btn-search-wrap {
        height: 100%;
        width: 100%;
        display: flex;
        align-items: flex-end;
    }

    .ath-btn-search {
        height: 36px;
        border-radius: 4px;
        width: 100%;
    }
    .ath-table-wrap {
        flex: 1;
    }
    .ath-main-table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        margin-bottom: 16px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }

    .ath-main-table th {
        background-color: var(--primary-color);
        color: var(--white-text-color);
        padding: 8px;
    }

    .ath-main-table td {
        padding: 8px 4px;
        text-align: center;
        border: 1px solid #ddd;
    }

    .ath-main-table tr:nth-child(odd){
        background-color: var(--border-color);
    }

    .ath-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .ath-pagination-info {
        color: var(--sub-text-color);
    }

    .ath-pagination-controls {
        margin: 0;
        list-style: none;
    }

    .ath-pagination-page-btn {
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

    .ath-pagination-page-btn.active {
        background-color: var(--primary-color);
        color: var(--white-text-color)
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const PAGE_SIZE = 10;
        const root = document.querySelector('.ath-content-wrap');
        const deviceSelect = root.querySelector('[name="ath-drp-devices"]');
        const statusSelect = root.querySelector('[name="ath-drp-status"]');
        const actionSelect = root.querySelector('[name="ath-drp-actions"]');
        const timeInput = root.querySelector('[name="ath-inp-time"]');
        const table = root.querySelector('.ath-main-table');
        const nav = root.querySelector('.ath-pagination');

        let filters = { device: '', status: '', action: '', time: '' };
        let currentPage = 1;

        function toRow(a) {
            const failed = a.status === 'FAILED' || a.status === 'TIMEOUT';
            return [
                a.id,
                a.device_name,
                a.action.replace('_', ' '),
                failed && a.error_message ? { text: a.status, title: a.error_message } : a.status,
                a.time_action,
                a.time_status || '-',
            ];
        }

        function load(page, silent = false) {
            const params = new URLSearchParams({ page, size: PAGE_SIZE });
            Object.entries(filters).forEach(([key, value]) => { if (value) params.set(key, value); });

            return IoT.api(`api/actions.php?${params}`)
                .then(res => {
                    currentPage = res.data.page;
                    IoT.renderTableRows(table, res.data.items.map(toRow), 6, 'Chưa có lịch sử thao tác');
                    IoT.renderPagination(nav, 'ath', res.data, page => load(page));
                })
                .catch(err => {
                    const message = `Không thể tải lịch sử thao tác: ${err.message}`;
                    if (silent) console.error(message); else alert(message);
                });
        }

        function search() {
            const time = timeInput.value.trim();
            if (time && !IoT.isValidTimeFilter(time)) {
                alert('Thời gian phải theo định dạng YYYY-MM-DD hh:mm:ss');
                return;
            }
            filters = { device: deviceSelect.value, status: statusSelect.value, action: actionSelect.value, time };
            load(1);
        }

        let reloadTimer = null;
        function reloadCurrentPage() {
            clearTimeout(reloadTimer);
            reloadTimer = setTimeout(() => load(currentPage, true), 300);
        }

        IoT.bindSearch(root.querySelector('.ath-btn-search'), [timeInput], search);
        load(1);
        IoT.realtime({ onSync: reloadCurrentPage, events: { action_status: reloadCurrentPage } });
    });
</script>
