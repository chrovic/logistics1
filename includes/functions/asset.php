<?php
// Logistic1/includes/functions/asset.php
require_once __DIR__ . '/../config/db.php';

// --- Asset CRUD Functions ---
function getAllAssets() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT * FROM assets ORDER BY asset_name ASC");
    $assets = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    return $assets;
}

function createAsset($name, $type, $purchase_date, $status, $image_path = null) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO assets (asset_name, asset_type, purchase_date, status, image_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $type, $purchase_date, $status, $image_path);
    $success = $stmt->execute();
    if ($success) {
        $asset_id = $conn->insert_id;
        $hist_stmt = $conn->prepare("INSERT INTO maintenance_history (asset_id, status, notes) VALUES (?, ?, 'Initial registration.')");
        $hist_stmt->bind_param("is", $asset_id, $status);
        $hist_stmt->execute();
        $hist_stmt->close();
    }
    $stmt->close();
    $conn->close();
    return $success;
}

function updateAsset($id, $name, $type, $purchase_date, $status, $image_path = null) {
    $conn = getDbConnection();
    
    // If image_path is provided, update it; otherwise, keep the existing image
    if ($image_path !== null) {
        $stmt = $conn->prepare("UPDATE assets SET asset_name = ?, asset_type = ?, purchase_date = ?, status = ?, image_path = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $type, $purchase_date, $status, $image_path, $id);
    } else {
        $stmt = $conn->prepare("UPDATE assets SET asset_name = ?, asset_type = ?, purchase_date = ?, status = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $type, $purchase_date, $status, $id);
    }
    
    $success = $stmt->execute();
    if ($success) {
        $hist_stmt = $conn->prepare("INSERT INTO maintenance_history (asset_id, status, notes) VALUES (?, ?, 'Status updated.')");
        $hist_stmt->bind_param("is", $id, $status);
        $hist_stmt->execute();
        $hist_stmt->close();
    }
    $stmt->close();
    $conn->close();
    return $success;
}

function deleteAsset($id) {
    $conn = getDbConnection();
    
    // Get the image path before deleting the asset
    $stmt_select = $conn->prepare("SELECT image_path FROM assets WHERE id = ?");
    $stmt_select->bind_param("i", $id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $asset = $result->fetch_assoc();
    $stmt_select->close();
    
    // Delete the asset record
    $stmt = $conn->prepare("DELETE FROM assets WHERE id = ?");
    $stmt->bind_param("i", $id);
    $success = $stmt->execute();
    
    // If deletion was successful and there was an image, delete the file
    if ($success && $asset && !empty($asset['image_path'])) {
        $file_path = __DIR__ . '/../../' . $asset['image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt->close();
    $conn->close();
    return $success;
}

// --- Image Upload Helper Functions ---
function handleAssetImageUpload($existing_image_path = null) {
    if (!isset($_FILES['asset_image']) || $_FILES['asset_image']['error'] === UPLOAD_ERR_NO_FILE) {
        return $existing_image_path; // No new file uploaded, keep existing
    }
    
    $file = $_FILES['asset_image'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Validate file size (5MB limit)
    if ($file['size'] > 5 * 1024 * 1024) {
        return false;
    }
    
    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($file_info, $file['tmp_name']);
    finfo_close($file_info);
    
    if (!in_array($mime_type, $allowed_types)) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'asset_' . uniqid() . '.' . $extension;
    $upload_dir = __DIR__ . '/../../assets/images/uploads/assets/';
    $upload_path = $upload_dir . $filename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old image if it exists and we're updating
        if ($existing_image_path && !empty($existing_image_path)) {
            $old_file_path = __DIR__ . '/../../' . $existing_image_path;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        
        return 'assets/images/uploads/assets/' . $filename;
    }
    
    return false;
}

// --- Maintenance Schedule Functions ---
function getMaintenanceSchedules() {
    $conn = getDbConnection();
    $sql = "SELECT ms.id, a.asset_name, ms.asset_id, ms.task_description, ms.scheduled_date, ms.status, ms.notes
            FROM maintenance_schedules ms
            JOIN assets a ON ms.asset_id = a.id
            ORDER BY ms.scheduled_date ASC";
    $result = $conn->query($sql);
    $schedules = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
    return $schedules;
}

function createMaintenanceSchedule($asset_id, $description, $scheduled_date, $notes = null) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO maintenance_schedules (asset_id, task_description, scheduled_date, notes) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $asset_id, $description, $scheduled_date, $notes);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

function updateMaintenanceStatus($schedule_id, $status) {
    $conn = getDbConnection();
    $completed_date = ($status === 'Completed') ? date('Y-m-d') : null;

    $asset_id_stmt = $conn->prepare("SELECT asset_id FROM maintenance_schedules WHERE id = ?");
    $asset_id_stmt->bind_param("i", $schedule_id);
    $asset_id_stmt->execute();
    $asset_id_result = $asset_id_stmt->get_result();
    $asset_id = $asset_id_result->fetch_assoc()['asset_id'];
    $asset_id_stmt->close();

    $stmt = $conn->prepare("UPDATE maintenance_schedules SET status = ?, completed_date = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $completed_date, $schedule_id);
    $success = $stmt->execute();

    if ($success && $status === 'Completed' && $asset_id) {
        $hist_stmt = $conn->prepare("INSERT INTO maintenance_history (asset_id, status, notes, `timestamp`) VALUES (?, 'Operational', 'Maintenance task completed.', NOW())");
        $hist_stmt->bind_param("i", $asset_id);
        $hist_stmt->execute();
        $hist_stmt->close();
        
        $cache_stmt = $conn->prepare("DELETE FROM asset_forecast_cache WHERE asset_id = ?");
        $cache_stmt->bind_param("i", $asset_id);
        $cache_stmt->execute();
        $cache_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    return $success;
}


// --- Predictive Automation Functions ---
function isMaintenanceScheduled($asset_id, $predicted_date_str) {
    if ($predicted_date_str === 'N/A' || $predicted_date_str === 'Error') return true;
    $conn = getDbConnection();
    
    $recent_past_date = date('Y-m-d', strtotime('-14 days'));
    $predicted_future_date = date('Y-m-d', strtotime('+14 days', strtotime($predicted_date_str)));

    $stmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM maintenance_schedules
         WHERE asset_id = ?
         AND (
             (status = 'Scheduled' AND scheduled_date <= ?) OR
             (status = 'Completed' AND completed_date >= ?)
         )"
    );
    $stmt->bind_param("iss", $asset_id, $predicted_future_date, $recent_past_date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['count'] > 0;
}


function automateMaintenanceSchedules() {
    $assets = getAllAssets();
    $forecasts = getPredictiveMaintenanceForecasts($assets);
    foreach ($assets as $asset) {
        $asset_id = $asset['id'];
        if (isset($forecasts[$asset_id])) {
            $forecast = $forecasts[$asset_id];
            $risk = strip_tags($forecast['risk']);
            $predicted_date = $forecast['next_maintenance'];
            if (in_array($risk, ['High', 'Medium'])) {
                if (!isMaintenanceScheduled($asset_id, $predicted_date)) {
                    $description = "AI Recommended: Proactive check-up.";
                    $notes = "Automated based on {$risk} risk prediction.";
                    
                    // **THE FIX IS HERE**: Swapped the arguments to the correct order.
                    // createMaintenanceSchedule(asset_id, description, scheduled_date, notes)
                    createMaintenanceSchedule($asset_id, $description, date('Y-m-d', strtotime($predicted_date)), $notes);
                }
            }
        }
    }
}

// --- Predictive Maintenance Functions ---
function getAssetHistory($asset_id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT status, notes, `timestamp` FROM maintenance_history WHERE asset_id = ? ORDER BY `timestamp` ASC");
    $stmt->bind_param("i", $asset_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    $conn->close();
    return $history;
}

function getAllUsageLogsGroupedByAsset() {
    $conn = getDbConnection();
    $sql = "SELECT a.id as asset_id, a.asset_name, u.log_date, u.metric_name, u.metric_value
            FROM assets a
            JOIN asset_usage_logs u ON a.id = u.asset_id
            ORDER BY a.asset_name ASC, u.log_date DESC";
    $result = $conn->query($sql);
    $logsByAsset = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logsByAsset[$row['asset_id']]['asset_name'] = $row['asset_name'];
            $logsByAsset[$row['asset_id']]['logs'][] = $row;
        }
    }
    $conn->close();
    return $logsByAsset;
}

function getPredictiveMaintenanceForecasts(array $assets): array {
    $conn = getDbConnection();
    $finalForecasts = [];
    $assetsToFetchFromApi = [];
    $cacheExpiryHours = 24;

    foreach ($assets as $asset) {
        $stmt = $conn->prepare("SELECT risk, next_maintenance FROM asset_forecast_cache WHERE asset_id = ? AND cached_at > NOW() - INTERVAL ? HOUR");
        $stmt->bind_param("ii", $asset['id'], $cacheExpiryHours);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $cachedData = $result->fetch_assoc();
            $risk = $cachedData['risk'];
            $next_maintenance = $cachedData['next_maintenance'];
            
            // Apply styling to cached risk data
            $risk_html = $risk;
            if ($risk === 'Low') {
                $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-gray-100 text-gray-700"><span class="inline-block w-1.5 h-1.5 bg-gray-500 rounded-full mr-1"></span>Low</span>';
            } elseif ($risk === 'Medium') {
                $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-blue-100 text-blue-700"><span class="inline-block w-1.5 h-1.5 bg-blue-500 rounded-full mr-1"></span>Medium</span>';
            } elseif ($risk === 'High') {
                $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-red-100 text-red-700"><span class="inline-block w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></span>High</span>';
            } elseif ($risk === 'No Data') {
                $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-gray-100 text-gray-500"><span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mr-1"></span>No Data</span>';
            }
            
            $finalForecasts[$asset['id']] = ['risk' => $risk_html, 'next_maintenance' => $next_maintenance];
        } else {
            $assetsToFetchFromApi[] = $asset;
        }
        $stmt->close();
    }

    if (!empty($assetsToFetchFromApi)) {
        $apiForecasts = fetchForecastsFromGeminiForAssets($assetsToFetchFromApi);
        foreach ($apiForecasts as $assetId => $forecastData) {
            $finalForecasts[$assetId] = $forecastData;
            $risk = strip_tags($forecastData['risk']);
            $next_maintenance = strip_tags($forecastData['next_maintenance']);
            $stmt = $conn->prepare("INSERT INTO asset_forecast_cache (asset_id, risk, next_maintenance, cached_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE risk = VALUES(risk), next_maintenance = VALUES(next_maintenance), cached_at = NOW()");
            $stmt->bind_param("iss", $assetId, $risk, $next_maintenance);
            $stmt->execute();
            $stmt->close();
        }
    }

    $conn->close();
    return $finalForecasts;
}

function fetchForecastsFromGeminiForAssets(array $assets): array {
    $apiKey = 'AIzaSyCdaU_w5RrRdOfsNnxHaM7dvMGrFA34J7o';
    $geminiApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $apiKey;
    $forecasts = [];
    $assetsWithHistory = [];

    $allUsageLogs = getAllUsageLogsGroupedByAsset();

    foreach ($assets as $asset) {
        $history = getAssetHistory($asset['id']);
        $usage_logs = $allUsageLogs[$asset['id']]['logs'] ?? [];
        if (count($history) < 2 && count($usage_logs) < 2) {
            $no_data_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-gray-100 text-gray-500"><span class="inline-block w-1.5 h-1.5 bg-gray-400 rounded-full mr-1"></span>No Data</span>';
            $forecasts[$asset['id']] = ['risk' => $no_data_html, 'next_maintenance' => 'N/A'];
        } else {
            $assetsWithHistory[$asset['id']] = ['name' => $asset['asset_name'], 'type' => $asset['asset_type'], 'history' => $history, 'usage' => $usage_logs];
        }
    }

    if (empty($assetsWithHistory)) return $forecasts;

    $batchPrompt = "As a predictive maintenance analyst for a logistics company, analyze the following assets based on their maintenance history and usage logs. For each asset, provide your output as a JSON object with two keys: 'risk' (a one-word risk level: 'Low', 'Medium', or 'High') and 'next_maintenance' (the predicted next service date in 'M d, Y' format). Today's date is " . date('M d, Y') . ". Return a single minified JSON array containing one object for each asset.\n\n";
    foreach ($assetsWithHistory as $id => $assetData) {
        $batchPrompt .= "Asset ID: {$id}\nAsset Name: {$assetData['name']} ({$assetData['type']})\nMaintenance History:\n";
        foreach ($assetData['history'] as $record) {
            $date = date('Y-m-d', strtotime($record['timestamp']));
            $notes = $record['notes'] ? " ({$record['notes']})" : '';
            $batchPrompt .= "- Date: {$date}, Status: {$record['status']}{$notes}\n";
        }
        if (!empty($assetData['usage'])) {
            $batchPrompt .= "Usage Logs:\n";
            foreach ($assetData['usage'] as $log) {
                $batchPrompt .= "- Date: {$log['log_date']}, {$log['metric_name']}: {$log['metric_value']}\n";
            }
        }
        $batchPrompt .= "\n";
    }

    $data = ["contents" => [["parts" => [["text" => $batchPrompt]]]], "generationConfig" => ["responseMimeType" => "application/json", "temperature" => 0.3]];
    $payload = json_encode($data);

    $ch = curl_init($geminiApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code == 200) {
        $result = json_decode($response, true);
        $json_string = $result['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
        $batch_analysis = json_decode($json_string, true);
        if (is_array($batch_analysis)) {
            foreach ($batch_analysis as $index => $analysis_data) {
                if (isset(array_keys($assetsWithHistory)[$index])) {
                    $assetId = array_keys($assetsWithHistory)[$index];
                    $risk = htmlspecialchars($analysis_data['risk'] ?? 'Error');
                    $next_maintenance = htmlspecialchars($analysis_data['next_maintenance'] ?? 'N/A');
                    
                    // Style the risk as a pill with dot like the Status column
                    $risk_html = $risk;
                    if ($risk === 'Low') {
                        $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-gray-100 text-gray-700"><span class="inline-block w-1.5 h-1.5 bg-gray-500 rounded-full mr-1"></span>Low</span>';
                    } elseif ($risk === 'Medium') {
                        $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-blue-100 text-blue-700"><span class="inline-block w-1.5 h-1.5 bg-blue-500 rounded-full mr-1"></span>Medium</span>';
                    } elseif ($risk === 'High') {
                        $risk_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-red-100 text-red-700"><span class="inline-block w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></span>High</span>';
                    }
                    
                    $forecasts[$assetId] = ['risk' => $risk_html, 'next_maintenance' => $next_maintenance];
                }
            }
        }
    } else {
        foreach ($assetsWithHistory as $id => $asset) {
            $error_detail = !empty($curl_error) ? $curl_error : "HTTP Code: {$http_code}";
            $error_html = '<span class="px-1 py-0.5 sm:px-2 sm:py-1 font-semibold leading-tight text-xs rounded-full whitespace-nowrap inline-flex items-center bg-red-100 text-red-700" title="' . htmlspecialchars($error_detail) . '"><span class="inline-block w-1.5 h-1.5 bg-red-500 rounded-full mr-1"></span>API Error</span>';
            $forecasts[$id] = ['risk' => $error_html, 'next_maintenance' => 'Error'];
        }
    }
    return $forecasts;
}

/**
 * Gets count of operational assets.
 * @return int The number of operational assets.
 */
function getOperationalAssetsCount() {
    $conn = getDbConnection();
    $result = $conn->query("SELECT COUNT(*) as count FROM assets WHERE status = 'Operational'");
    $count = 0;
    
    if ($result) {
        $row = $result->fetch_assoc();
        $count = (int)$row['count'];
    }
    
    $conn->close();
    return $count;
}

/**
 * Gets the delivery truck asset for dashboard display.
 * @return array|null The delivery truck asset data.
 */
function getDeliveryTruckAsset() {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM assets WHERE asset_name LIKE '%Delivery Truck%' LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $asset = null;
    if ($result && $result->num_rows > 0) {
        $asset = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $asset;
}

/**
 * Gets the percentage change in operational assets compared to previous month.
 * @return array Contains percentage and whether it's positive/negative.
 */
function getOperationalAssetsChange() {
    $conn = getDbConnection();
    
    // Get current operational assets count
    $currentCount = getOperationalAssetsCount();
    
    // Get operational assets count from 30 days ago based on maintenance_history
    $prevResult = $conn->query("
        SELECT COUNT(DISTINCT mh.asset_id) as count
        FROM maintenance_history mh
        WHERE mh.status = 'Operational'
        AND mh.timestamp <= DATE_SUB(NOW(), INTERVAL 30 DAY)
        AND mh.asset_id IN (SELECT id FROM assets)
    ");
    $prevCount = $prevResult ? $prevResult->fetch_assoc()['count'] : 0;
    
    $conn->close();
    
    // Calculate percentage change
    if ($prevCount == 0) {
        return ['percentage' => $currentCount > 0 ? 100 : 0, 'is_positive' => $currentCount > 0];
    }
    
    $change = (($currentCount - $prevCount) / $prevCount) * 100;
    return [
        'percentage' => abs(round($change, 1)), 
        'is_positive' => $change >= 0
    ];
}
?>