<style>
    .server-management {
        text-align: center;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .server-status {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .status-indicator {
        display: flex;
        align-items: center;
        font-size: 18px;
        font-weight: bold;
    }

    .status-indicator div {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        margin-right: 10px;
    }

    .server-ip {
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        background: #e9ecef;
        padding: 5px 10px;
        border-radius: 5px;
        transition: background 0.3s;
    }

    .server-ip:hover {
        background: #dee2e6;
    }

    .server-ip i {
        font-size: 14px;
        color: #6c757d;
    }

    .server-image {
        font-size: 16px;
        font-weight: bold;
        background: #d4edda;
        padding: 5px 10px;
        border-radius: 5px;
        color: #155724;
    }

    .console-section {
        margin-top: 20px;
    }

    .btn {
        margin: 5px;
        padding: 10px 15px;
        font-size: 16px;
        border-radius: 5px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn i {
        margin-right: 5px;
    }

    .alert {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .power-buttons {
        margin-top: 20px;
    }

    .usage-graphs {
        margin-top: 30px;
    }

    .usage-graphs canvas {
        max-width: 100%;
        margin-top: 20px;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 900px;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .modal-header h4 {
        margin: 0;
    }

    .close {
        font-size: 24px;
        cursor: pointer;
    }

    .modal-body {
        margin-top: 15px;
    }

    .console-iframe {
        width: 100%;
        height: 500px;
        border: none;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="server-management">
    <h3><i class="fa fa-server"></i> Server Management</h3>
    <p>Use the buttons below to manage your server.</p>

    <!-- Server Status & IP -->
    <div class="server-status">
        <div class="status-indicator">
            <div style="background-color: {$statusColor};"></div>
            <span>{$statusMessage}</span>
        </div>
        <!-- Clickable IP -->
        <div class="server-ip" onclick="copyToClipboard('{$ip}', this)">
            <i class="fa fa-network-wired"></i> {$ip} <i class="fa fa-copy"></i>
        </div>

        <!-- OS Image -->
        <div class="server-image">
            <i class="fa fa-hdd"></i> {$image}
        </div>
    </div>

    <!-- Console Access -->
    <div class="console-section">
        {if $consoleLink}
            <button class="btn btn-primary" onclick="openConsole()">
                <i class="fa fa-terminal"></i> {$consoleText}
            </button>
        {else}
            <button class="btn btn-secondary" disabled>
                <i class="fa fa-terminal"></i> {$consoleText}
            </button>
        {/if}

        {if $consoleError}
            <div class="alert alert-danger">
                <strong>Error:</strong> {$consoleError}
            </div>
        {/if}
    </div>

    <!-- Power Control Buttons -->
    <div class="power-buttons">
        <form method="post" action="clientarea.php?action=productdetails&id={$serviceid}">
            <input type="hidden" name="modop" value="custom" />

            <button type="submit" name="a" value="PowerOn" class="btn btn-success">
                <i class="fa fa-power-off"></i> Power On
            </button>
            <button type="submit" name="a" value="PowerOff" class="btn btn-danger">
                <i class="fa fa-times-circle"></i> Power Off
            </button>
            <button type="submit" name="a" value="Reboot" class="btn btn-warning">
                <i class="fa fa-sync"></i> Reboot
            </button>
            <button type="submit" name="a" value="Rebuild" class="btn btn-info">
                <i class="fa fa-wrench"></i> Rebuild
            </button>
            <button type="submit" name="a" value="ResetPassword" class="btn btn-secondary">
                <i class="fa fa-key"></i> Reset Password
            </button>
        </form>
    </div>

    <!-- Usage Graphs -->
    <div class="usage-graphs">
        <h4>Usage</h4>
        <canvas id="cpuChart"></canvas>
        <canvas id="bandwidthChart"></canvas>
    </div>
</div>

<!-- Console Modal -->
<div id="consoleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h4><i class="fa fa-terminal"></i> Web Console</h4>
            <span class="close" onclick="closeConsole()">&times;</span>
        </div>
        <div class="modal-body">
            <iframe id="consoleFrame" class="console-iframe"></iframe>
        </div>
    </div>
</div>

<script>
    const cpuCtx = document.getElementById('cpuChart').getContext('2d');
    const bandwidthCtx = document.getElementById('bandwidthChart').getContext('2d');
    let cpuChart = new Chart(cpuCtx, {type: 'line', data: {labels: [], datasets: [{label: 'CPU %', data: []}]}});
    let bandwidthChart = new Chart(bandwidthCtx, {type: 'line', data: {labels: [], datasets: [{label: 'Bandwidth Bps', data: []}]}});

    function fetchMetrics() {
        $.ajax({
            url: 'clientarea.php?action=productdetails&id={$serviceid}&ajax=metrics',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.metrics.cpu) {
                        cpuChart.data.labels = response.metrics.cpu.times;
                        cpuChart.data.datasets[0].data = response.metrics.cpu.values;
                        cpuChart.update();
                    }
                    if (response.metrics.network) {
                        bandwidthChart.data.labels = response.metrics.network.times;
                        bandwidthChart.data.datasets[0].data = response.metrics.network.values;
                        bandwidthChart.update();
                    }
                }
            }
        });
    }

    function openConsole() {
        var consoleUrl = "{$consoleLink}";
        if (consoleUrl) {
            window.open(consoleUrl, "HetznerConsole", "width=900,height=600,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes");
        } else {
            alert("Console link is not available.");
        }
    }

    function closeConsole() {
        document.getElementById("consoleModal").style.display = "none";
        document.getElementById("consoleFrame").src = ""; // Reset iframe
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        var modal = document.getElementById("consoleModal");
        if (event.target === modal) {
            closeConsole();
        }
    };

    function copyToClipboard(text, element) {
        navigator.clipboard.writeText(text).then(() => {
            element.innerHTML = '<i class="fa fa-network-wired"></i> ' + text + ' <i class="fa fa-check" style="color: green;"></i>';
            setTimeout(() => {
                element.innerHTML = '<i class="fa fa-network-wired"></i> ' + text + ' <i class="fa fa-copy"></i>';
            }, 1500);
        }).catch(err => {
            console.error('Failed to copy text: ', err);
        });
    }

    function updateServerStatus() {
        $.ajax({
            url: 'clientarea.php?action=productdetails&id={$serviceid}&ajax=status',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update status message
                    $('.status-indicator span').text(response.statusMessage);

                    // Update status color
                    $('.status-indicator div').css('background-color', response.statusColor);
                }
            },
            error: function() {
                console.error('Failed to fetch server status.');
            }
        });
    }

    // Refresh status every 5 seconds and metrics every minute
    setInterval(updateServerStatus, 5000);
    fetchMetrics();
    setInterval(fetchMetrics, 60000);
</script>


