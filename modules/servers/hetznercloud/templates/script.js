// Global variables
let currentChart = null;
let currentMetricsType = null;
let serviceId = null;

// Initialize default time range
function initializeDefaultTimeRange() {
    // Set default to last 24 hours
    window.currentTimeRange = 24;
    const endDate = new Date();
    const startDate = new Date(endDate.getTime() - (24 * 60 * 60 * 1000));
}

// Start metrics refresh interval based on time range
function startMetricsRefreshInterval() {
    // Clear any existing interval
    if (window.metricsRefreshInterval) {
        clearInterval(window.metricsRefreshInterval);
    }
    
    // Set refresh interval based on time range
    const timeRange = window.currentTimeRange || 24;
    let refreshInterval;
    
    if (timeRange <= 1) {
        // 1 hour or less: refresh every minute
        refreshInterval = 60000;
    } else if (timeRange <= 24) {
        // 1-24 hours: refresh every 2 minutes
        refreshInterval = 120000;
    } else if (timeRange <= 168) {
        // 1-7 days: refresh every 5 minutes
        refreshInterval = 300000;
    } else {
        // 30 days: refresh every 10 minutes
        refreshInterval = 600000;
    }
    
    window.metricsRefreshInterval = setInterval(() => {
        const currentType = window.currentMetricType || 'cpu';
        loadMetrics(currentType);
    }, refreshInterval);
}

// Set time range for metrics
function setTimeRange(range) {

    
    const endDate = new Date();
    let startDate = new Date();
    
    switch(range) {
        case 1:
            startDate = new Date(endDate.getTime() - (60 * 60 * 1000));
            break;
        case 12:
            startDate = new Date(endDate.getTime() - (12 * 60 * 60 * 1000));
            break;
        case 24:
            startDate = new Date(endDate.getTime() - (24 * 60 * 60 * 1000));
            break;
        case 168:
            startDate = new Date(endDate.getTime() - (7 * 24 * 60 * 60 * 1000));
            break;
        case 720:
            startDate = new Date(endDate.getTime() - (30 * 24 * 60 * 60 * 1000));
            break;
        default:
            startDate = new Date(endDate.getTime() - (24 * 60 * 60 * 1000));
    }
    
    // Store the selected time range globally
    window.currentTimeRange = range;
    
    // Reset retry flag for new time range
    window.retryAttempted = false;
    
    // Update the time range display
    updateTimeRangeDisplay(range);
    
    // Update button states
    updateTimeRangeButtonStates(range);
    
    // Restart the refresh interval with new timing
    startMetricsRefreshInterval();
    
    // Reload metrics with new time range
    const currentMetricType = window.currentMetricType || 'cpu';
    if (currentMetricType) {
        loadMetrics(currentMetricType);
    }
}

// Get current selected metric type
function getCurrentMetricType() {
    const buttons = document.querySelectorAll('.metric-btn');
    for (let button of buttons) {
        if (button.classList.contains('active') || button.style.backgroundColor === 'rgb(41, 128, 185)') {
            return button.textContent.toLowerCase().replace(' ', '').replace('i/o', '');
        }
    }
    return 'cpu'; // default
}



// Get formatted date parameters for API
function getDateParameters() {
    const timeRange = window.currentTimeRange || 24;
    const now = new Date();
    const startTime = new Date(now.getTime() - (timeRange * 60 * 60 * 1000));
    return {
        start: Math.floor(startTime.getTime() / 1000),
        end: Math.floor(now.getTime() / 1000)
    };
}



// Console function
function openConsole() {
    const consoleUrl = document.getElementById('consoleLink').getAttribute('data-url');
    if (consoleUrl && consoleUrl !== '') {
        console.log('Opening console:', consoleUrl);
        window.open(consoleUrl, "HetznerConsole", "width=1000,height=700,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=yes");
    } else {
        alert("Console link is not available. Please check server status.");
    }
}

// Copy to clipboard function
function copyToClipboard(elementId, label) {
    const element = document.getElementById(elementId);
    let textToCopy = '';
    
    if (element.tagName === 'INPUT') {
        textToCopy = element.value;
    } else {
        textToCopy = element.textContent;
    }
    
    if (textToCopy && textToCopy !== 'Click to set password') {
        navigator.clipboard.writeText(textToCopy).then(function() {
            // Show success feedback
            const originalText = element.textContent || element.value;
            if (element.tagName === 'INPUT') {
                element.value = 'Copied!';
                setTimeout(() => { element.value = originalText; }, 1000);
            } else {
                element.textContent = 'Copied!';
                setTimeout(() => { element.textContent = originalText; }, 1000);
            }
        }).catch(function(err) {
            console.error('Could not copy text: ', err);
            alert('Failed to copy ' + label + ' to clipboard');
        });
    } else {
        alert('No ' + label + ' to copy');
    }
}

// Power actions
function powerAction(action) {
    if (confirm('Are you sure you want to ' + action.toLowerCase() + ' the server?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = document.getElementById('serviceForm').getAttribute('action');
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = action;
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
        
        // Update status immediately and then every 2 seconds for faster feedback
        setTimeout(updateServerStatus, 1000);
        setTimeout(updateServerStatus, 3000);
        setTimeout(updateServerStatus, 5000);
        setTimeout(updateServerStatus, 8000);
    }
}

// Reset password
function resetPassword() {
    if (confirm('Are you sure you want to reset the root password? This will generate a new password.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = document.getElementById('serviceForm').getAttribute('action');
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = 'ResetPassword';
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Open rebuild modal
function openRebuildModal() {
    openModal('rebuildModal');
    
    // Add form submission handler
    const rebuildForm = document.getElementById('rebuildForm');
    if (rebuildForm) {
        rebuildForm.onsubmit = function(e) {
            const newImage = document.getElementById('newImage').value;
            console.log('Rebuild form submitted with image:', newImage);
            
                    if (!newImage) {
            e.preventDefault();
            alert('Please select a new operating system');
            return false;
        }
        
        // After rebuild, start monitoring status more frequently
        setTimeout(() => {
            updateServerStatus();
            // Update status every 5 seconds for rebuild operations
            const rebuildStatusInterval = setInterval(() => {
                updateServerStatus();
            }, 5000);
            
            // Stop monitoring after 5 minutes
            setTimeout(() => {
                clearInterval(rebuildStatusInterval);
            }, 300000);
        }, 2000);
        
        return true;
        };
    }
}

// Open ISO management modal
function openISOModal() {
    openModal('isoModal');
    loadAvailableISOs();
}

// Load available ISOs
function loadAvailableISOs() {
    if (!serviceId) {
        console.error('Service ID not available for loadAvailableISOs');
        return;
    }
    
    const loading = document.getElementById('isoLoading');
    const isoList = document.getElementById('isoList');
    const isoError = document.getElementById('isoError');
    const isoGrid = document.getElementById('isoGrid');
    
    if (loading) loading.style.display = 'block';
    if (isoList) isoList.style.display = 'none';
    if (isoError) isoError.style.display = 'none';
    
    const url = 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=isos';
    
    console.log('Loading ISOs from:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('ISO response:', data);
            if (loading) loading.style.display = 'none';
            
            if (data.success && data.isos && Array.isArray(data.isos)) {
                if (isoList) isoList.style.display = 'block';
                displayISOs(data.isos);
            } else {
                if (isoError) {
                    isoError.textContent = 'Failed to load ISOs: ' + (data.message || 'No ISOs available');
                    isoError.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error loading ISOs:', error);
            if (loading) loading.style.display = 'none';
            if (isoError) {
                isoError.textContent = 'Error loading ISOs: ' + error.message;
                isoError.style.display = 'block';
            }
        });
}

// Refresh ISO cache
function refreshISOCache() {
    if (!serviceId) {
        console.error('Service ID not available for refreshISOCache');
        return;
    }
    
    const loading = document.getElementById('isoLoading');
    const isoList = document.getElementById('isoList');
    const isoError = document.getElementById('isoError');
    const isoGrid = document.getElementById('isoGrid');
    
    if (loading) loading.style.display = 'block';
    if (isoList) isoList.style.display = 'none';
    if (isoError) isoError.style.display = 'none';
    
    const url = 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=refresh_isos';
    
    console.log('Refreshing ISO cache from:', url);
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            console.log('ISO refresh response:', data);
            if (loading) loading.style.display = 'none';
            
            if (data.success && data.isos && Array.isArray(data.isos)) {
                if (isoList) isoList.style.display = 'block';
                displayISOs(data.isos);
                
                // Show success message
                showSuccessMessage('ISO cache refreshed successfully!');
            } else {
                if (isoError) {
                    isoError.textContent = 'Failed to refresh ISOs: ' + (data.message || 'Unknown error');
                    isoError.style.display = 'block';
                }
            }
        })
        .catch(error => {
            console.error('Error refreshing ISOs:', error);
            if (loading) loading.style.display = 'none';
            if (isoError) {
                isoError.textContent = 'Error refreshing ISOs: ' + error.message;
                isoError.style.display = 'block';
            }
        });
}

// Display ISOs in the grid
function displayISOs(isos) {
    const isoGrid = document.getElementById('isoGrid');
    if (!isoGrid) return;
    
    if (!Array.isArray(isos) || isos.length === 0) {
        isoGrid.innerHTML = '<div class="text-muted" style="grid-column: 1 / -1; text-align: center; padding: 40px;">No ISOs available</div>';
        return;
    }
    
    isoGrid.innerHTML = '';
    
    isos.forEach((iso, index) => {
        const isoItem = document.createElement('div');
        isoItem.className = 'iso-item';
        
        const architecture = iso.architecture || 'Unknown';
        const typeClass = iso.type === 'public' ? 'public' : 'private';
        
        // Use description if available, otherwise truncate the filename
        const displayName = iso.description || iso.name;
        const fullName = iso.name;
        
        isoItem.innerHTML = `
            <div class="iso-header">
                <div>
                    <div class="iso-name" title="${fullName}">${displayName}</div>
                    <div class="iso-filename" title="${fullName}">${fullName}</div>
                </div>
            </div>
            <div class="iso-meta">
                <span class="iso-badge ${typeClass}">${iso.type}</span>
                <span class="iso-badge architecture">${architecture}</span>
            </div>
            <button class="attach-iso-btn" onclick="attachISO('${fullName.replace(/'/g, "\\'")}')">
                <i class="fas fa-link"></i> Attach ISO
            </button>
        `;
        
        isoGrid.appendChild(isoItem);
    });
    
    // Set up search and filter functionality
    setupISOFilters(isos);
}

// Setup ISO search and filter functionality
function setupISOFilters(allISOs) {
    const searchInput = document.getElementById('isoSearch');
    const architectureSelect = document.getElementById('isoArchitecture');
    
    if (!searchInput || !architectureSelect) return;
    
    const filterISOs = () => {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedArchitecture = architectureSelect.value;
        
        const filteredISOs = allISOs.filter(iso => {
            const matchesSearch = iso.name.toLowerCase().includes(searchTerm) || 
                                (iso.description && iso.description.toLowerCase().includes(searchTerm));
            const matchesArchitecture = !selectedArchitecture || iso.architecture === selectedArchitecture;
            
            return matchesSearch && matchesArchitecture;
        });
        
        displayISOs(filteredISOs);
    };
    
    searchInput.addEventListener('input', filterISOs);
    architectureSelect.addEventListener('change', filterISOs);
}

// Attach ISO to server
function attachISO(isoName) {
    if (!serviceId) {
        alert('Service ID not available');
        return;
    }
    
    if (!isoName || isoName.trim() === '') {
        alert('ISO name is required');
        return;
    }
    
    const trimmedIsoName = isoName.trim();
    
    if (confirm(`Are you sure you want to attach the ISO "${trimmedIsoName}" to your server? This will detach any currently attached ISO.`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'clientarea.php?action=productdetails&id=' + serviceId;
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = 'AttachISO';
        
        const isoNameInput = document.createElement('input');
        isoNameInput.type = 'hidden';
        isoNameInput.name = 'iso_name';
        isoNameInput.value = trimmedIsoName;
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        form.appendChild(isoNameInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Unmount ISO from server
function unmountISO() {
    if (!serviceId) {
        alert('Service ID not available');
        return;
    }
    
    if (confirm('Are you sure you want to unmount the currently attached ISO? This will remove the ISO from your server.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'clientarea.php?action=productdetails&id=' + serviceId;
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = 'UnmountISO';
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
        
        // Update status after ISO unmount
        setTimeout(updateServerStatus, 2000);
        setTimeout(updateServerStatus, 5000);
    }
}

// Reset password function
function resetPassword() {
    if (confirm('Are you sure you want to reset the server password? This will generate a new password.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'clientarea.php?action=productdetails&id=' + serviceId;
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = 'ResetPassword';
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
        
        // Update status after password reset
        setTimeout(updateServerStatus, 2000);
        setTimeout(updateServerStatus, 5000);
    }
}

// Power actions
function powerAction(action) {
    if (confirm('Are you sure you want to ' + action.toLowerCase() + ' the server?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'clientarea.php?action=productdetails&id=' + serviceId;
        
        const modopInput = document.createElement('input');
        modopInput.type = 'hidden';
        modopInput.name = 'modop';
        modopInput.value = 'custom';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'a';
        actionInput.value = action;
        
        form.appendChild(modopInput);
        form.appendChild(actionInput);
        document.body.appendChild(form);
        form.submit();
    }
}

// Console functions
function openConsole() {
    const consoleLink = document.getElementById('consoleLink');
    if (consoleLink) {
        const url = consoleLink.getAttribute('data-url');
        if (url) {
            window.open(url, '_blank', 'width=1200,height=800');
        } else {
            alert('Console link not available');
        }
    }
}

// Copy to clipboard function
function copyToClipboard(elementId, label) {
    const element = document.getElementById(elementId);
    if (element) {
        let textToCopy = element.value || element.textContent;
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCopySuccess(label);
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = textToCopy;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showCopySuccess(label);
        }
    }
}

// Show copy success message
function showCopySuccess(label) {
    // Create a temporary success message
    const successMsg = document.createElement('div');
    successMsg.textContent = label + ' copied to clipboard!';
    successMsg.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        z-index: 10000;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(successMsg);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (successMsg.parentNode) {
            successMsg.parentNode.removeChild(successMsg);
        }
    }, 3000);
}

// Show success message
function showSuccessMessage(message) {
    const successMsg = document.createElement('div');
    successMsg.textContent = message;
    successMsg.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 20px;
        border-radius: 4px;
        z-index: 10000;
        font-size: 14px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    `;
    
    document.body.appendChild(successMsg);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (successMsg.parentNode) {
            successMsg.parentNode.removeChild(successMsg);
        }
    }, 3000);
}

// Load metrics for a specific type
function loadMetrics(metricType) {

    
    // Store the current metric type globally
    window.currentMetricType = metricType;
    
    // Update button states
    updateMetricButtonStates(metricType);
    
    // Clear any existing chart
    if (window.currentChart) {
        window.currentChart.destroy();
        window.currentChart = null;
    }
    
    const container = document.getElementById('metrics-chart-container');
    
    if (!container) {
        console.error('Metrics chart container not found');
        return;
    }
    
    // Get time range from global variable or default to 24 hours
    const timeRange = window.currentTimeRange || 24;
    const endDate = new Date();
    const startDate = new Date(endDate.getTime() - (timeRange * 60 * 60 * 1000));
    
    // Convert dates to timestamps
    const startTimestamp = Math.floor(startDate.getTime() / 1000);
    const endTimestamp = Math.floor(endDate.getTime() / 1000);
    
    // Show loading state
    const metricsLoading = document.getElementById('metricsLoading');
    const metricsError = document.getElementById('metricsError');
    if (metricsLoading) metricsLoading.style.display = 'block';
    if (metricsError) metricsError.style.display = 'none';
    if (container) container.style.display = 'none';
    
    // Add loading indicator to chart container
    if (container) {
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><br>Loading metrics...</div>';
    }
    
    fetch(`clientarea.php?action=productdetails&id=${serviceId}&ajax=metrics&start=${startTimestamp}&end=${endTimestamp}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {

            
            // Validate response format
            if (!data || typeof data !== 'object') {
                throw new Error('Invalid response format');
            }
            
            if (data.success && data.metrics) {
                // Validate metrics data structure
                if (validateMetricsData(data.metrics, metricType)) {
                    displayMetrics(metricType, data.metrics);
                } else {
                    // Try to retry once for longer time ranges
                    if (window.currentTimeRange > 24 && !window.retryAttempted) {

                        window.retryAttempted = true;
                        setTimeout(() => {
                            loadMetrics(metricType);
                        }, 2000);
                        return;
                    }
                    throw new Error('Invalid metrics data structure');
                }
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error loading metrics:', error);
            
            // Show error message
            const errorMessage = error.message || 'Unknown error occurred';
            
            // Provide more helpful error messages for specific cases
            if (errorMessage.includes('Invalid metrics data structure')) {
                const timeRange = window.currentTimeRange || 24;
                if (timeRange > 24) {
                    showMetricsError(`No data available for ${timeRange > 168 ? '30 days' : '1 week'} time range. Try a shorter time range.`);
                } else {
                    showMetricsError('Error loading metrics: ' + errorMessage);
                }
            } else {
                showMetricsError('Error loading metrics: ' + errorMessage);
            }
            
            // Log additional error details for debugging
            if (error.response) {
                console.error('Response status:', error.response.status);
                console.error('Response headers:', error.response.headers);
            }
            
            // Retry logic for network errors
            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                console.log('Network error detected, will retry in 30 seconds...');
                setTimeout(() => {
                    console.log('Retrying metrics request...');
                    loadMetrics(metricType);
                }, 30000);
            }
        });
}

// Update metric button states
function updateMetricButtonStates(activeType) {
    const buttons = document.querySelectorAll('.metric-btn');
    buttons.forEach(btn => {
        btn.classList.remove('active');
        if (btn.textContent.toLowerCase().includes(activeType)) {
            btn.classList.add('active');
        }
    });
}

// Update time range display
function updateTimeRangeDisplay(range) {
    const display = document.getElementById('currentTimeRangeDisplay');
    if (display) {
        let text = '';
        switch(range) {
            case 1:
                text = '1 Hour';
                break;
            case 12:
                text = '12 Hours';
                break;
            case 24:
                text = '24 Hours';
                break;
            case 168:
                text = '1 Week';
                break;
            case 720:
                text = '30 Days';
                break;
            default:
                text = `${range} Hours`;
        }
        display.textContent = text;

    }
}

// Update time range button states
function updateTimeRangeButtonStates(activeRange) {
    const buttons = document.querySelectorAll('.time-presets .btn');
    buttons.forEach(btn => {
        // Skip the refresh button
        if (btn.textContent.includes('Refresh')) {
            return;
        }
        
        btn.classList.remove('active');
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-secondary');
    });
    
    // Find the button that matches the active range
    const activeButton = Array.from(buttons).find(btn => {
        const text = btn.textContent.toLowerCase();
        if (activeRange === 1 && text.includes('1 hour')) return true;
        if (activeRange === 12 && text.includes('12 hours')) return true;
        if (activeRange === 24 && text.includes('24 hours')) return true;
        if (activeRange === 168 && text.includes('1 week')) return true;
        if (activeRange === 720 && text.includes('30 days')) return true;
        return false;
    });
    
    if (activeButton) {
        activeButton.classList.remove('btn-outline-secondary');
        activeButton.classList.add('btn-primary');
        activeButton.classList.add('active');
    }
    

}

// Display metrics chart
function displayMetrics(metricType, metricsData) {
    const container = document.getElementById('metrics-chart-container');
    if (!container) {
        return;
    }
    
    // Clear the container
    container.innerHTML = '';
    
    // Create new canvas
    const canvas = document.createElement('canvas');
    canvas.id = 'metrics-chart';
    canvas.width = container.offsetWidth;
    canvas.height = 400;
    container.appendChild(canvas);
    
    // Show container and hide error messages
    container.style.display = 'block';
    const metricsError = document.getElementById('metricsError');
    const metricsLoading = document.getElementById('metricsLoading');
    
    if (metricsError) metricsError.style.display = 'none';
    if (metricsLoading) metricsLoading.style.display = 'none';
    
    // Check if we have data for the selected metric type
    if (!metricsData || !metricsData[metricType]) {
        // Check if this is a longer time range that might not have data
        const timeRange = window.currentTimeRange || 24;
        if (timeRange > 24) {
            const timeRangeText = timeRange === 168 ? '1 week' : '30 days';
            showMetricsError(`No data available for ${timeRangeText} time range. The server may not have metrics data for this period. Try a shorter time range.`);
        } else {
            // Show a more helpful error message
            const availableTypes = Object.keys(metricsData || {}).filter(key => key !== 'start' && key !== 'end' && key !== 'step');
            if (availableTypes.length > 0) {
                showMetricsError(`No data available for ${metricType}. Available types: ${availableTypes.join(', ')}`);
            } else {
                showMetricsError(`No metrics data available for ${metricType}`);
            }
        }
        return;
    }
    
    const chartData = prepareChartData(metricType, metricsData[metricType]);
    
    if (!chartData || !chartData.datasets || chartData.datasets.length === 0) {
        
        
        // Try to provide more specific error information
        if (chartData && chartData.datasets && chartData.datasets.length === 0) {
            showMetricsError(`No time series data found for ${metricType}. The metric type exists but has no values.`);
        } else if (!chartData) {
            showMetricsError(`Failed to prepare chart data for ${metricType}. Data processing error.`);
        } else {
            showMetricsError(`No chart data available for ${metricType}`);
        }
        return;
    }
    

    
    // Create the chart
    const ctx = canvas.getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: getMetricTitle(metricType),
                    font: {
                        size: 16,
                        weight: 'bold'
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: getTimeUnit(),
                        displayFormats: getTimeDisplayFormats()
                    },
                    title: {
                        display: true,
                        text: 'Time'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: getMetricUnit(metricType)
                    },
                    beginAtZero: true,
                    // Set appropriate scales for different metric types
                    ...(metricType === 'cpu' && {
                        suggestedMin: 0,
                        suggestedMax: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }),
                    ...(metricType === 'disk' && {
                        ticks: {
                            callback: function(value) {
                                // For disk, we have mixed units, so just show the value
                                return value;
                            }
                        }
                    }),
                    ...(metricType === 'network' && {
                        ticks: {
                            callback: function(value) {
                                // For network, we have mixed units, so just show the value
                                return value;
                            }
                        }
                    })
                }
            }
        }
    });
    
    // Store the chart reference globally for potential updates
    window.currentChart = chart;
    
    // Add last updated timestamp below the chart
    const lastUpdated = document.createElement('div');
    lastUpdated.className = 'chart-last-updated';
    lastUpdated.style.cssText = 'text-align: center; margin-top: 10px; color: #6c757d; font-size: 12px;';
    lastUpdated.innerHTML = `Last updated: ${new Date().toLocaleTimeString()}`;
    container.appendChild(lastUpdated);
}

function prepareChartData(metricType, metricsData) {
    // Check if we have the metrics data structure
    if (!metricsData || !metricsData.metrics || !metricsData.metrics.time_series) {
        return { labels: [], datasets: [] };
    }
    
    const timeSeries = metricsData.metrics.time_series;
    const datasets = [];
    
    // Handle different metric types
    switch (metricType) {
        case 'cpu':
            if (timeSeries.cpu && timeSeries.cpu.values) {
                const data = timeSeries.cpu.values.map(point => ({
                    x: new Date(point[0] * 1000), // Convert Unix timestamp to Date
                    y: parseFloat(point[1]) // API already returns percentage
                }));
                
                datasets.push({
                    label: 'CPU Usage (%)',
                    data: data,
                    borderColor: 'rgb(54, 162, 235)',
                    backgroundColor: 'rgba(54, 162, 235, 0.1)',
                    tension: 0.1,
                    fill: false
                });
            }
            break;
            
        case 'disk':
            // Handle disk metrics (multiple sub-metrics)
            Object.keys(timeSeries).forEach(key => {
                if (key.startsWith('disk') && timeSeries[key].values && timeSeries[key].values.length > 0) {
                    const data = timeSeries[key].values.map(point => ({
                        x: new Date(point[0] * 1000),
                        y: parseFloat(point[1])
                    }));
                    
                    const colors = {
                        'disk.0.iops.read': 'rgb(255, 99, 132)',
                        'disk.0.iops.write': 'rgb(75, 192, 192)',
                        'disk.0.bandwidth.read': 'rgb(255, 159, 64)',
                        'disk.0.bandwidth.write': 'rgb(153, 102, 255)'
                    };
                    
                    // Create proper labels with units
                    let label = '';
                    if (key === 'disk.0.iops.read') label = 'Read IOPS (ops/s)';
                    else if (key === 'disk.0.iops.write') label = 'Write IOPS (ops/s)';
                    else if (key === 'disk.0.bandwidth.read') label = 'Read Bandwidth (B/s)';
                    else if (key === 'disk.0.bandwidth.write') label = 'Write Bandwidth (B/s)';
                    else label = key.replace('disk.0.', '').replace('.', ' ').toUpperCase();
                    
                    datasets.push({
                        label: label,
                        data: data,
                        borderColor: colors[key] || 'rgb(201, 203, 207)',
                        backgroundColor: colors[key] ? colors[key].replace('rgb', 'rgba').replace(')', ', 0.1)') : 'rgba(201, 203, 207, 0.1)',
                        tension: 0.1,
                        fill: false
                    });
                }
            });
            break;
            
        case 'network':
            // Handle network metrics (multiple sub-metrics)
            Object.keys(timeSeries).forEach(key => {
                if (key.startsWith('network') && timeSeries[key].values && timeSeries[key].values.length > 0) {
                    const data = timeSeries[key].values.map(point => ({
                        x: new Date(point[0] * 1000),
                        y: parseFloat(point[1])
                    }));
                    
                    const colors = {
                        'network.0.bandwidth.in': 'rgb(75, 192, 192)',
                        'network.0.bandwidth.out': 'rgb(255, 99, 132)',
                        'network.0.pps.in': 'rgb(54, 162, 235)',
                        'network.0.pps.out': 'rgb(255, 205, 86)'
                    };
                    
                    // Create proper labels with units
                    let label = '';
                    if (key === 'network.0.bandwidth.in') label = 'Incoming Bandwidth (B/s)';
                    else if (key === 'network.0.bandwidth.out') label = 'Outgoing Bandwidth (B/s)';
                    else if (key === 'network.0.pps.in') label = 'Incoming Packets (packets/s)';
                    else if (key === 'network.0.pps.out') label = 'Outgoing Packets (packets/s)';
                    else label = key.replace('network.0.', '').replace('.', ' ', 'g').toUpperCase();
                    
                    datasets.push({
                        label: label,
                        data: data,
                        borderColor: colors[key] || 'rgb(201, 203, 207)',
                        backgroundColor: colors[key] ? colors[key].replace('rgb', 'rgba').replace(')', ', 0.1)') : 'rgba(201, 203, 207, 0.1)',
                        tension: 0.1,
                        fill: false
                    });
                }
            });
            break;
            
        default:
            console.error('Unknown metric type:', metricType);
            return { labels: [], datasets: [] };
    }
    
    
    
    if (datasets.length === 0) {
        console.error('No datasets created for', metricType);
        return { labels: [], datasets: [] };
    }
    
    return { datasets: datasets };
}

// Get metric title for display
function getMetricTitle(metricType) {
    const timeRange = window.currentTimeRange || 24;
    const timeRangeText = timeRange === 1 ? '1 Hour' : 
                          timeRange === 12 ? '12 Hours' : 
                          timeRange === 24 ? '24 Hours' : 
                          timeRange === 168 ? '1 Week' : 
                          timeRange === 720 ? '30 Days' : 
                          `${timeRange} Hours`;
    
    const titles = {
        'cpu': `CPU Usage Over Time (${timeRangeText})`,
        'disk': `Disk I/O Over Time (${timeRangeText})`,
        'network': `Network Usage Over Time (${timeRangeText})`
    };
    return titles[metricType] || `${metricType.toUpperCase()} Over Time (${timeRangeText})`;
}

// Get metric unit for Y-axis
function getMetricUnit(metricType) {
    const units = {
        'cpu': 'CPU Usage (%)',
        'disk': 'Disk I/O (Mixed Units)',
        'network': 'Network Usage (Mixed Units)'
    };
    return units[metricType] || metricType.toUpperCase();
}

// Get appropriate time unit based on selected time range
function getTimeUnit() {
    const timeRange = window.currentTimeRange || 24;
    
    if (timeRange <= 1) {
        return 'minute';
    } else if (timeRange <= 24) {
        return 'hour';
    } else if (timeRange <= 168) {
        return 'day';
    } else {
        return 'day';
    }
}

// Get time display formats based on selected time range
function getTimeDisplayFormats() {
    const timeRange = window.currentTimeRange || 24;
    
    if (timeRange <= 1) {
        return {
            minute: 'HH:mm',
            hour: 'HH:mm'
        };
    } else if (timeRange <= 24) {
        return {
            hour: 'HH:mm',
            day: 'MMM dd'
        };
    } else if (timeRange <= 168) {
        return {
            day: 'MMM dd',
            week: 'MMM dd'
        };
    } else {
        return {
            day: 'MMM dd',
            month: 'MMM yyyy'
        };
    }
}

// Validate metrics data structure
function validateMetricsData(metricsData, metricType) {
    if (!metricsData || typeof metricsData !== 'object') {
        return false;
    }
    
    // Check if the metric type exists in the data
    if (!metricsData[metricType]) {
        return false;
    }
    
    const metricData = metricsData[metricType];
    
    // Check if it has the required structure
    if (!metricData.metrics || !metricData.metrics.time_series) {
        return false;
    }
    
    const timeSeries = metricData.metrics.time_series;
    
    // Check if there are any values
    const hasValues = Object.keys(timeSeries).some(key => {
        const hasData = timeSeries[key].values && Array.isArray(timeSeries[key].values) && timeSeries[key].values.length > 0;
        return hasData;
    });
    
    if (!hasValues) {
        return false;
    }
    
    return true;
}

// Show metrics error
function showMetricsError(message) {
    const container = document.getElementById('metrics-chart-container');
    const metricsError = document.getElementById('metricsError');
    
    if (container) container.style.display = 'none';
    if (metricsError) {
        metricsError.textContent = message;
        metricsError.style.display = 'block';
    }
}

// Update chart with current date range
function updateChart() {
    const currentType = window.currentMetricType || 'cpu';
    loadMetrics(currentType);
}

// Manual refresh function for metrics chart
function refreshMetricsChart() {
    const currentType = window.currentMetricType || 'cpu';

    
    // Show loading indicator
    const container = document.getElementById('metrics-chart-container');
    if (container) {
        container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i><br>Refreshing metrics...</div>';
    }
    
    // Reload metrics
    loadMetrics(currentType);
}



// Refresh usage data
function refreshUsage() {
    if (!serviceId) {
        console.error('Service ID not available for refreshUsage');
        return;
    }
    
    // Use the current time range or default to 1 hour for usage data
    const timeRange = window.currentTimeRange || 1;
    const startTime = Math.floor((Date.now() - (timeRange * 60 * 60 * 1000)) / 1000);
    const endTime = Math.floor(Date.now() / 1000);
    

    
    // Show loading state
    document.getElementById('cpu-usage').textContent = 'Loading...';
    document.getElementById('disk-usage').textContent = 'Loading...';
    document.getElementById('network-usage').textContent = 'Loading...';
    
    fetch(`clientarea.php?action=productdetails&id=${serviceId}&ajax=metrics&start=${startTime}&end=${endTime}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.metrics) {
                updateUsageDisplay(data.metrics);
            } else {
                showUsageError('Failed to load usage data');
            }
        })
        .catch(error => {
            showUsageError('Error loading usage data: ' + error.message);
        });
}

// Show usage error
function showUsageError(message) {
    document.getElementById('cpu-usage').textContent = 'Error';
    document.getElementById('disk-usage').textContent = 'Error';
    document.getElementById('network-usage').textContent = 'Error';
    console.error('Usage error:', message);
}

// Update usage display with real data
function updateUsageDisplay(metricsData) {
    if (!metricsData) {
        showUsageError('No metrics data available');
        return;
    }
    
    try {
        // Calculate CPU usage (average of CPU values)
        let cpuUsage = 0;
        if (metricsData.cpu && metricsData.cpu.metrics && metricsData.cpu.metrics.time_series && metricsData.cpu.metrics.time_series.cpu) {
            const cpuValues = metricsData.cpu.metrics.time_series.cpu.values;
            if (cpuValues && cpuValues.length > 0) {
                const sum = cpuValues.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                cpuUsage = (sum / cpuValues.length).toFixed(1);
            }
        }
        
        // Calculate Disk I/O (separate read/write IOPS and bandwidth)
        let diskReadIOPS = 0;
        let diskWriteIOPS = 0;
        let diskReadBandwidth = 0;
        let diskWriteBandwidth = 0;
        
        if (metricsData.disk && metricsData.disk.metrics && metricsData.disk.metrics.time_series) {
            const timeSeries = metricsData.disk.metrics.time_series;
            
            // Calculate Read IOPS
            if (timeSeries['disk.0.iops.read'] && timeSeries['disk.0.iops.read'].values) {
                const values = timeSeries['disk.0.iops.read'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    diskReadIOPS = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Write IOPS
            if (timeSeries['disk.0.iops.write'] && timeSeries['disk.0.iops.write'].values) {
                const values = timeSeries['disk.0.iops.write'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    diskWriteIOPS = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Read Bandwidth
            if (timeSeries['disk.0.bandwidth.read'] && timeSeries['disk.0.bandwidth.read'].values) {
                const values = timeSeries['disk.0.bandwidth.read'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    diskReadBandwidth = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Write Bandwidth
            if (timeSeries['disk.0.bandwidth.write'] && timeSeries['disk.0.bandwidth.write'].values) {
                const values = timeSeries['disk.0.bandwidth.write'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    diskWriteBandwidth = (sum / values.length).toFixed(2);
                }
            }
        }
        
        // Calculate Network usage (separate bandwidth and packets)
        let networkInBandwidth = 0;
        let networkOutBandwidth = 0;
        let networkInPackets = 0;
        let networkOutPackets = 0;
        
        if (metricsData.network && metricsData.network.metrics && metricsData.network.metrics.time_series) {
            const timeSeries = metricsData.network.metrics.time_series;
            
            // Calculate Incoming Bandwidth
            if (timeSeries['network.0.bandwidth.in'] && timeSeries['network.0.bandwidth.in'].values) {
                const values = timeSeries['network.0.bandwidth.in'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    networkInBandwidth = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Outgoing Bandwidth
            if (timeSeries['network.0.bandwidth.out'] && timeSeries['network.0.bandwidth.out'].values) {
                const values = timeSeries['network.0.bandwidth.out'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    networkOutBandwidth = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Incoming Packets
            if (timeSeries['network.0.pps.in'] && timeSeries['network.0.pps.in'].values) {
                const values = timeSeries['network.0.pps.in'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    networkInPackets = (sum / values.length).toFixed(2);
                }
            }
            
            // Calculate Outgoing Packets
            if (timeSeries['network.0.pps.out'] && timeSeries['network.0.pps.out'].values) {
                const values = timeSeries['network.0.pps.out'].values;
                if (values && values.length > 0) {
                    const sum = values.reduce((acc, point) => acc + parseFloat(point[1]), 0);
                    networkOutPackets = (sum / values.length).toFixed(2);
                }
            }
        }
        
        // Update the display with proper units
        document.getElementById('cpu-usage').textContent = cpuUsage + '%';
        
        // Show disk metrics with proper units (compact format)
        const diskText = `R:${diskReadIOPS} W:${diskWriteIOPS}`;
        document.getElementById('disk-usage').textContent = diskText;
        
        // Show network metrics with proper units (compact format)
        const networkText = `In:${networkInBandwidth} Out:${networkOutBandwidth}`;
        document.getElementById('network-usage').textContent = networkText;
        
        // Add detailed tooltip information
        const diskCard = document.querySelector('[data-tooltip*="Read/Write operations"]');
        const networkCard = document.querySelector('[data-tooltip*="Incoming/Outgoing bandwidth"]');
        
        if (diskCard) {
            diskCard.setAttribute('data-tooltip', 
                `Read: ${diskReadIOPS} ops/s | Write: ${diskWriteIOPS} ops/s\n` +
                `Read BW: ${diskReadBandwidth} B/s | Write BW: ${diskWriteBandwidth} B/s`
            );
        }
        
        if (networkCard) {
            networkCard.setAttribute('data-tooltip', 
                `In: ${networkInBandwidth} B/s | Out: ${networkOutBandwidth} B/s\n` +
                `In Pkts: ${networkInPackets} pkt/s | Out Pkts: ${networkOutPackets} pkt/s`
            );
        }
        
        // Update timestamp
        const now = new Date();
        document.getElementById('last-update').textContent = now.toLocaleTimeString();
        
    } catch (error) {
        console.error('Error updating usage display:', error);
        showUsageError('Error processing metrics data');
    }
}

// Update server status
function updateServerStatus() {
    if (!serviceId) {
        return;
    }
    
    const url = 'clientarea.php?action=productdetails&id=' + serviceId + '&ajax=status';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const statusSpan = document.querySelector('.status-indicator span');
                const statusDot = document.querySelector('.status-dot');
                
                if (statusSpan) {
                    statusSpan.textContent = data.statusMessage;
                    // Remove any existing status classes
                    statusSpan.className = 'status-text';
                }
                
                if (statusDot) {
                    statusDot.style.backgroundColor = data.statusColor;
                    
                    // Add heartbeat effect for online status
                    if (data.statusMessage.toLowerCase().includes('online') || data.statusMessage.toLowerCase().includes('running')) {
                        statusDot.classList.add('status-online');
                        statusDot.classList.remove('status-starting', 'status-offline', 'status-error');
                    } else if (data.statusMessage.toLowerCase().includes('starting')) {
                        statusDot.classList.add('status-starting');
                        statusDot.classList.remove('status-online', 'status-offline', 'status-error');
                    } else if (data.statusMessage.toLowerCase().includes('offline') || data.statusMessage.toLowerCase().includes('stopped')) {
                        statusDot.classList.add('status-offline');
                        statusDot.classList.remove('status-online', 'status-starting', 'status-error');
                    } else {
                        statusDot.classList.add('status-error');
                        statusDot.classList.remove('status-online', 'status-starting', 'status-offline');
                    }
                }
            }
        })
        .catch(error => {
            // Silently handle errors to avoid console spam
        });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait a bit more to ensure all elements are fully rendered
    setTimeout(function() {
        initializeComponents();
    }, 100);
    
    // Add click outside modal functionality
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
});

function initializeComponents() {
    // Get service ID from the hidden element
    const serviceIdElement = document.getElementById('serviceId');
    if (!serviceIdElement) {
        return;
    }
    
    serviceId = serviceIdElement.getAttribute('data-id');
    if (!serviceId) {
        return;
    }
    
    // Set up form action URLs
    const baseUrl = 'clientarea.php?action=productdetails&id=' + serviceId;
    
    const serviceForm = document.getElementById('serviceForm');
    const metricsUrl = document.getElementById('metricsUrl');
    const statusUrl = document.getElementById('statusUrl');
    
    if (serviceForm) serviceForm.setAttribute('action', baseUrl);
    if (metricsUrl) metricsUrl.setAttribute('data-url', baseUrl + '&ajax=metrics');
    if (statusUrl) statusUrl.setAttribute('data-url', baseUrl + '&ajax=status');
    
    // Initialize components
    initializeDefaultTimeRange();
    
    // Update time range button states
    updateTimeRangeButtonStates(window.currentTimeRange);
    
    // Update time range display
    updateTimeRangeDisplay(window.currentTimeRange);
    
    // Load initial metrics (CPU) after a short delay to ensure DOM is ready
    if (serviceId) {
        setTimeout(() => {
            loadMetrics('cpu');
        }, 500);
    }
    
    // Load initial usage data immediately
    refreshUsage();
    
    // Load initial server status
    updateServerStatus();
    
    // Start usage refresh interval
    setInterval(refreshUsage, 30000); // Refresh every 30 seconds
    
    // Start server status refresh interval (every 10 seconds)
    setInterval(updateServerStatus, 10000);
    
    // Start metrics chart refresh interval (every 5 minutes for longer ranges, every minute for shorter ranges)
    startMetricsRefreshInterval();
    
    // Components initialized successfully
}

// Toggle password visibility
function togglePassword() {
    const passwordField = document.getElementById('password-field');
    const passwordIcon = document.getElementById('password-icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        passwordIcon.className = 'fas fa-eye-slash';
        passwordIcon.title = 'Hide Password';
    } else {
        passwordField.type = 'password';
        passwordIcon.className = 'fas fa-eye';
        passwordIcon.title = 'Show Password';
    }
}

// Copy to clipboard function
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Use modern clipboard API
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess();
        }).catch(err => {
            console.error('Failed to copy: ', err);
            fallbackCopyTextToClipboard(text);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyTextToClipboard(text);
    }
}

// Fallback copy function for older browsers
function fallbackCopyTextToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showCopySuccess();
    } catch (err) {
        console.error('Fallback copy failed: ', err);
        alert('Failed to copy to clipboard');
    }
    
    document.body.removeChild(textArea);
}

// Show copy success message
function showCopySuccess() {
    // Create a temporary success message
    const successMsg = document.createElement('div');
    successMsg.className = 'copy-success-msg';
    successMsg.innerHTML = '<i class="fas fa-check"></i> Copied!';
    successMsg.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #28a745;
        color: white;
        padding: 10px 16px;
        border-radius: 6px;
        font-size: 14px;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(successMsg);
    
    // Remove after 2 seconds
    setTimeout(() => {
        successMsg.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            if (successMsg.parentNode) {
                successMsg.parentNode.removeChild(successMsg);
            }
        }, 300);
    }, 2000);
}
