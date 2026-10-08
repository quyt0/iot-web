<?php
    if (!defined('IN_APP')) {
        header('Location: index.php');
        exit;
    }
?>

<ul class="dashboard-data-wrap row">
    <li class="col-3">
        <div class="dashboard-data-item">
            <span class="dashboard-data-item-avt">T</span>
            <div class="dashboard-val-wrap">
                <h4 class="dashboard-data-item-title">Nhiệt độ</h4>
                <span class="dashboard-data-item-val" data-sensor="temperature">-- &deg;C</span>
            </div>
        </div>
    </li>
    <li class="col-3">
        <div class="dashboard-data-item">
            <span class="dashboard-data-item-avt">H</span>
            <div class="dashboard-val-wrap">
                <h4 class="dashboard-data-item-title">Độ ẩm</h4>
                <span class="dashboard-data-item-val" data-sensor="humidity">-- %</span>
            </div>
        </div>
    </li>
    <li class="col-3">
        <div class="dashboard-data-item">
            <span class="dashboard-data-item-avt">L</span>
            <div class="dashboard-val-wrap">
                <h4 class="dashboard-data-item-title">Ánh sáng</h4>
                <span class="dashboard-data-item-val" data-sensor="light">-- %</span>
            </div>
        </div>
    </li>
    <li class="col-3">
        <div class="dashboard-data-item">
            <span class="dashboard-data-item-avt">E</span>
            <div class="dashboard-val-wrap">
                <h4 class="dashboard-data-item-title">ESP32</h4>
                <span class="dashboard-data-item-val" data-system="esp32_status">ĐANG TẢI...</span>
            </div>
        </div>
    </li>
</ul>
<div class="dashboard-below-wrap row">
    <div class="col-8">
        <div class="dashboard-chart">
            <h3 class="dashboard-controls-heading">Biểu đồ dữ liệu cảm biến</h3>
            <div style="position: relative; height: 350px; width: 100%;">
                <canvas id="iot-chart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="dashboard-controls">
            <h3 class="dashboard-controls-heading">Điều khiển thiết bị</h3>
            <ol class="control-devices">
                <?php
                    require_once __DIR__ . '/lib/dashboard.php';

                    $pdo = db();
                    $controlDevices = dashboard_devices(new DeviceDAO($pdo), new ActionDAO($pdo));
                    $offlineLabel = 'Không thể kết nối';
                    $stateColors = ['ON' => 'green', 'OFF' => 'red', 'LOADING' => 'orange', $offlineLabel => 'red'];

                    foreach ($controlDevices as $dev):
                        $pendingRequest = ($dev['last_action']['status'] ?? '') === 'LOADING' ? $dev['last_action']['request_id'] : '';
                        $label = $pendingRequest !== '' ? 'LOADING' : ($dev['is_online'] ? $dev['current_state'] : $offlineLabel);
                        $controllable = $pendingRequest === '' && $dev['is_online'];
                    ?>
                    <li class="control-device-item"
                        data-device-code="<?= htmlspecialchars($dev['device_code']) ?>"
                        data-state="<?= htmlspecialchars($dev['current_state']) ?>"
                        data-online="<?= $dev['is_online'] ? '1' : '0' ?>"
                        data-pending-request="<?= htmlspecialchars($pendingRequest) ?>">
                        <div class="device-content">
                            <?= htmlspecialchars($dev['device_name']) ?>

                            <span class="device-state-text" style="font-weight: bold; margin-left: auto; margin-right: 12px; color: <?= $stateColors[$label] ?? 'gray' ?>;">
                                <?= htmlspecialchars($label) ?>
                            </span>

                            <label class="control-device-switch">
                                <input type="checkbox" class="device-switch-input"
                                    <?= $dev['is_online'] && $dev['current_state'] === 'ON' ? 'checked' : '' ?>
                                    <?= $controllable ? '' : 'disabled' ?>
                                    title="<?= $dev['is_online'] ? '' : 'Không thể kết nối thiết bị' ?>">
                                <span class="control-device-slider"></span>
                            </label>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</div>

<style>
    .dashboard-data-wrap {
        padding: 24px 0 0 0;
        list-style: none;
    }

    .dashboard-data-item {
        background-color: white;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
    }

    .dashboard-val-wrap {
        display: flex;
        flex-direction: column;
    }

    .dashboard-data-item-avt {
        color: var(--white-text-color);
        background-color: var(--primary-color);
        border-radius: 100%;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dashboard-data-item-title {
        margin: 0;
    }

    .dashboard-chart {
        background-color: white;
        border-radius: 8px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
        padding: 12px;
    }

    .dashboard-controls-heading {
        margin: 0;
    }
    .dashboard-controls {
        background-color: white;
        border-radius: 8px;
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
        padding: 12px;
    }

    .control-device-item {
        margin-bottom: 16px;
    }

    .device-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px
    }

    .control-device-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        flex-shrink: 0;
    }

    .control-device-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .control-device-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 34px;
    }

    .control-device-slider::before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
        border-radius: 50%;
    }

    .control-device-switch input:checked + .control-device-slider {
        background-color: var(--primary-color);
    }

    .control-device-switch input:checked + .control-device-slider::before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    .control-device-switch input:focus + .control-device-slider {
        box-shadow: 0 0 1px var(--primary-color);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const MAX_POINTS = <?= CHART_POINTS ?>;
        const STATE_COLORS = { ON: 'green', OFF: 'red', LOADING: 'orange' };
        const OFFLINE_LABEL = 'Không thể kết nối';
        const SENSOR_UNITS = { temperature: '&deg;C', humidity: '%', light: '%' };

        const devices = {};
        const finishedRequests = new Map();

        document.querySelectorAll('.control-device-item').forEach(item => {
            const code = item.dataset.deviceCode;
            const input = item.querySelector('.device-switch-input');
            devices[code] = {
                input,
                stateText: item.querySelector('.device-state-text'),
                state: item.dataset.state,
                online: item.dataset.online === '1',
                waiting: item.dataset.pendingRequest || null,
            };
            input.addEventListener('change', () => sendAction(code, input.checked ? 'ON' : 'OFF'));
        });

        function renderDevice(code) {
            const dev = devices[code];
            const offline = !dev.online && !dev.waiting;
            const label = dev.waiting ? 'LOADING' : (offline ? OFFLINE_LABEL : dev.state);
            dev.stateText.innerText = label;
            dev.stateText.style.color = offline ? 'red' : (STATE_COLORS[label] || 'gray');
            dev.input.checked = !offline && dev.state === 'ON';
            dev.input.disabled = offline || !!dev.waiting;
            dev.input.title = offline ? 'Không thể kết nối thiết bị' : '';
        }

        function notifyFailure(action) {
            if (action.status === 'TIMEOUT') {
                alert('ESP32 không phản hồi trong thời gian chờ, vui lòng thử lại.');
            } else if (action.status === 'FAILED') {
                alert(action.error_message || 'Gửi lệnh điều khiển thất bại, vui lòng thử lại.');
            }
        }

        function sendAction(code, action) {
            const dev = devices[code];
            dev.waiting = 'sending';
            renderDevice(code);

            IoT.api(`api/devices/actions.php?device=${encodeURIComponent(code)}`, { method: 'POST', body: { action } })
                .then(res => {
                    const requestId = res.data.requestId;
                    const finished = finishedRequests.get(requestId);
                    dev.waiting = finished ? null : requestId;
                    if (finished) notifyFailure(finished);
                    renderDevice(code);
                })
                .catch(err => {
                    dev.waiting = null;
                    renderDevice(code);
                    alert(err.message);
                });
        }

        function applyActionStatus(action) {
            const dev = devices[action.device_code];
            if (!dev || !action.request_id) return;

            if (action.status === 'LOADING') {
                if (!dev.waiting) {
                    dev.waiting = action.request_id;
                    renderDevice(action.device_code);
                }
                return;
            }

            if (finishedRequests.has(action.request_id)) return;
            finishedRequests.set(action.request_id, action);

            if (action.status === 'ON' || action.status === 'OFF') dev.state = action.status;
            if (dev.waiting === action.request_id) {
                dev.waiting = null;
                notifyFailure(action);
            }
            renderDevice(action.device_code);
        }

        function applyDevices(list) {
            list.forEach(d => {
                const dev = devices[d.device_code];
                if (!dev) return;
                if (d.last_action) applyActionStatus({ ...d.last_action, device_code: d.device_code });
                dev.state = d.current_state;
                dev.online = d.is_online;
                renderDevice(d.device_code);
            });
        }

        let latestValues = {};
        let esp32Online = false;

        function renderSensors(values) {
            if (values) latestValues = values;
            Object.keys(SENSOR_UNITS).forEach(key => {
                const el = document.querySelector(`[data-sensor="${key}"]`);
                if (!el) return;
                const value = latestValues[key];
                const unit = SENSOR_UNITS[key];
                if (value === null || value === undefined) el.innerHTML = 'Chưa có dữ liệu';
                else if (!esp32Online) el.innerHTML = `-- ${unit}`;
                else el.innerHTML = `${value} ${unit}`;
                el.title = latestValues.updated_at ? `Cập nhật: ${latestValues.updated_at}` : '';
            });
        }

        function renderEsp32(esp32) {
            esp32Online = esp32.status === 'ONLINE';
            const el = document.querySelector('[data-system="esp32_status"]');
            if (el) {
                el.innerText = esp32.status;
                el.style.color = esp32Online ? 'green' : 'red';
                el.title = esp32.last_seen_at ? `Lần cuối: ${esp32.last_seen_at}` : 'Chưa nhận bản tin nào từ ESP32';
            }
            renderSensors();
        }

        const chart = new Chart(document.getElementById('iot-chart').getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Nhiệt độ (°C)', data: [], borderColor: '#b11e3b', backgroundColor: 'rgba(177, 30, 59, 0.1)', borderWidth: 2, tension: 0.4, fill: true },
                    { label: 'Độ ẩm (%)', data: [], borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, 0.1)', borderWidth: 2, tension: 0.4, fill: true },
                    { label: 'Ánh sáng (%)', data: [], borderColor: '#ffc107', backgroundColor: 'rgba(255, 193, 7, 0.1)', borderWidth: 2, tension: 0.4, fill: true }
                ]
            },
            options: {
                devicePixelRatio: 2, responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true, pointStyle: 'line', borderWidth: 3 } } },
                scales: { y: { beginAtZero: true, max: 100 } }
            }
        });
        const SERIES = ['temperature', 'humidity', 'light'];

        let chartDrawn = false;

        function drawChart(labels, series) {
            chart.data.labels = labels;
            SERIES.forEach((key, i) => { chart.data.datasets[i].data = series[key]; });
            chart.update(chartDrawn ? 'none' : undefined);
            chartDrawn = true;
        }

        function setChart(data) {
            drawChart([...data.labels], Object.fromEntries(SERIES.map(key => [key, [...data[key]]])));
        }

        function pushChartPoint(values) {
            const label = values.updated_at ? values.updated_at.slice(11) : null;
            const labels = chart.data.labels;
            if (!label || labels.includes(label)) return;

            drawChart(
                [...labels, label].slice(-MAX_POINTS),
                Object.fromEntries(SERIES.map((key, i) => [key, [...chart.data.datasets[i].data, values[key]].slice(-MAX_POINTS)]))
            );
        }

        function refreshSummary() {
            return IoT.api('api/dashboard/summary.php')
                .then(res => {
                    const { summary, chart: chartData } = res.data;
                    renderSensors(summary);
                    renderEsp32(summary.esp32);
                    applyDevices(summary.devices);
                    setChart(chartData);
                })
                .catch(err => console.error('Không thể cập nhật Dashboard:', err.message));
        }

        refreshSummary();
        IoT.realtime({
            onSync: refreshSummary,
            events: {
                telemetry: values => {
                    renderSensors(values);
                    pushChartPoint(values);
                },
                device_status: data => {
                    renderEsp32(data.esp32);
                    applyDevices(data.devices);
                },
                action_status: applyActionStatus,
            },
        });
    });
</script>