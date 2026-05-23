<?php
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'PUT':
        handlePut();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleGet() {
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_records':
            getRecords();
            break;
        case 'get_archived_records':
            getArchivedRecords();
            break;
        case 'get_posts':
            getPosts();
            break;
        case 'get_theme_settings':
            getThemeSettings();
            break;
        case 'get_activity_logs':
            getActivityLogs();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

function handlePost() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) $data = $_POST;

    if (!$data || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data or missing action']);
        return;
    }

    $action = $data['action'];

    switch ($action) {
        case 'add_record':
            addRecord($data);
            break;
        case 'update_record':
            updateRecord($data);
            break;
        case 'update_status':
            updateRecordStatus($data);
            break;
        case 'delete_record':
            deleteRecord($data);
            break;
        case 'archive_record':
            archiveRecord($data);
            break;
        case 'restore_record':
            restoreRecord($data);
            break;
        case 'add_request':
            addRequest($data);
            break;
        case 'add_announcement':
            addAnnouncement($data);
            break;
        case 'update_request':
            updateRequest($data);
            break;
        case 'create_post':
            createPost($data);
            break;
        case 'delete_post':
            deletePost($data);
            break;
        case 'update_theme_settings':
            updateThemeSettings($data);
            break;
        case 'log_activity':
            logActivity($data);
            break;
        case 'mark_announcements_read':
            markAnnouncementsRead();
            break;
        case 'mark_requests_read':
            markRequestsRead();
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}
function addRecord($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        return;
    }

    $username = trim((string)($data['username'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $firstName = trim((string)($data['firstName'] ?? ''));
    $lastName = trim((string)($data['lastName'] ?? ''));

    if ($username === '' || $password === '' || $firstName === '' || $lastName === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        return;
    }

    $middleName = trim((string)($data['middleName'] ?? ''));
    $birthDate = trim((string)($data['birthDate'] ?? ''));
    $gender = trim((string)($data['gender'] ?? ''));
    $barangayId = (int)($data['barangayId'] ?? 0);
    $barangayName = sanitize($data['barangay'] ?? '');
    $livelihood = sanitize($data['livelihood'] ?? '');
    $fishingType = sanitize($data['fishingType'] ?? $livelihood); // Fallback to livelihood if fishingType is missing
    $contact = sanitize($data['contact'] ?? '');
    $status = sanitize($data['status'] ?? 'Pending');

    // If barangayId is 0 but name is provided, look up the ID
    if ($barangayId === 0 && !empty($barangayName)) {
        $stmt = $pdo->prepare("SELECT barangay_id FROM barangay WHERE barangay_name = ?");
        $stmt->execute([$barangayName]);
        $row = $stmt->fetch();
        if ($row) {
            $barangayId = (int)$row['barangay_id'];
        }
    }
    $fishrNumber = trim((string)($data['fishrNumber'] ?? ''));
    $rsbsaNumber = trim((string)($data['rsbsaNumber'] ?? ''));
    $emergencyName = trim((string)($data['emergencyName'] ?? ''));
    $emergencyRelation = trim((string)($data['emergencyRelation'] ?? ''));
    $emergencyAddress = trim((string)($data['emergencyAddress'] ?? ''));
    $emergencyContact = trim((string)($data['emergencyContact'] ?? ''));
    $photoData = (string)($data['photoData'] ?? '');

    try {
        ensureFisherfolkRegistrationColumns();
        ensureIdCardSignatureColumn();

        // username uniqueness
        $check = $pdo->prepare('SELECT user_id FROM users WHERE username = ? LIMIT 1');
        $check->execute([$username]);
        if ($check->fetch()) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Username already exists']);
            return;
        }

        $pdo->beginTransaction();

        // resolve or create barangay
        $barangayId = null;
        if ($barangayName !== '') {
            $stmtBarangay = $pdo->prepare('SELECT barangay_id FROM barangay WHERE barangay_name = ? LIMIT 1');
            $stmtBarangay->execute([$barangayName]);
            $rowBarangay = $stmtBarangay->fetch(PDO::FETCH_ASSOC);
            if ($rowBarangay) {
                $barangayId = (int)$rowBarangay['barangay_id'];
            } else {
                $insertBarangay = $pdo->prepare('INSERT INTO barangay (barangay_name, municipality, province) VALUES (?, ?, ?)');
                $insertBarangay->execute([$barangayName, 'Bongabong', 'Oriental Mindoro']);
                $barangayId = (int)$pdo->lastInsertId();
            }
        }

        // create user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $insertUser = $pdo->prepare("INSERT INTO users (username, password, role, email) VALUES (?, ?, 'fisherfolk', ?)");
        $insertUser->execute([$username, $hashedPassword, $username . '@fisherfolk.local']);
        $userId = (int)$pdo->lastInsertId();

        // create fisherfolk profile
        $insertFisherfolk = $pdo->prepare('INSERT INTO fisherfolk (user_id, first_name, last_name, middle_name, birthdate, gender, contact_number, address, barangay_id, fishing_type, livelihood, fishr_number, rsbsa_number, emergency_name, emergency_relation, emergency_address, emergency_contact, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $insertFisherfolk->execute([
            $userId,
            $firstName,
            $lastName,
            $middleName,
            $birthDate !== '' ? $birthDate : null,
            $gender !== '' ? $gender : null,
            $contact,
            null, // address
            $barangayId,
            $fishingType,
            $livelihood,
            $fishrNumber,
            $rsbsaNumber,
            $emergencyName,
            $emergencyRelation,
            $emergencyAddress,
            $emergencyContact,
            $status !== '' ? $status : 'Pending'
        ]);
        $fisherfolkId = (int)$pdo->lastInsertId();

        // optional photo upload from data URL
        $photoPath = '';
        if ($photoData !== '' && preg_match('/^data:image\/(jpeg|jpg|png|gif);base64,/', $photoData, $m)) {
            $extension = strtolower($m[1]);
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
            $raw = substr($photoData, strpos($photoData, ',') + 1);
            $binary = base64_decode($raw, true);
            if ($binary !== false) {
                $uploadDir = __DIR__ . '/photos';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = sprintf('fisherfolk_%d_%d.%s', $fisherfolkId, time(), $extension);
                $absolute = $uploadDir . '/' . $filename;
                if (file_put_contents($absolute, $binary) !== false) {
                    $photoPath = 'photos/' . $filename;
                }
            }
        }

        // optional signature upload from data URL
        $signaturePath = '';
        $signatureData = (string)($data['signatureData'] ?? '');
        if (!empty($signatureData) && preg_match('/^data:image\/(\w+);base64,/', $signatureData, $type)) {
            $signatureData = substr($signatureData, strpos($signatureData, ',') + 1);
            $type = strtolower($type[1]);
            if (in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                $signatureData = base64_decode($signatureData);
                if ($signatureData !== false) {
                    $uploadDirSig = __DIR__ . '/photos';
                    if (!is_dir($uploadDirSig)) mkdir($uploadDirSig, 0755, true);
                    $sigFilename = sprintf('fisherfolk_%d_sig_%d.%s', $fisherfolkId, time(), $type === 'jpeg' ? 'jpg' : $type);
                    $sigAbsolute = $uploadDirSig . '/' . $sigFilename;
                    if (file_put_contents($sigAbsolute, $signatureData) !== false) {
                        $signaturePath = 'photos/' . $sigFilename;
                    }
                }
            }
        }

        // create id card row
        $idNumber = 'FF-' . date('Y') . '-' . str_pad((string)$fisherfolkId, 4, '0', STR_PAD_LEFT);
        $insertCard = $pdo->prepare("INSERT INTO id_cards (fisherfolk_id, id_number, photo_path, signature_path, issue_date, expiry_date, status) VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 YEAR), 'Active')");
        $insertCard->execute([$fisherfolkId, $idNumber, $photoPath, $signaturePath]);

        $pdo->commit();

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'registered_fisherfolk', 'fisherfolk', $fisherfolkId, null, $firstName . ' ' . $lastName);

        $expiryDate = (new DateTime('+3 years'))->format('m/Y');
        echo json_encode([
            'success' => true,
            'record' => [
                'id' => (string)$fisherfolkId,
                'username' => $username,
                'firstName' => $firstName,
                'middleName' => $middleName,
                'lastName' => $lastName,
                'barangay' => $barangayName,
                'livelihood' => $livelihood,
                'contact' => $contact,
                'status' => $status !== '' ? $status : 'Pending',
                'validity' => $expiryDate,
                'gender' => $gender !== '' ? $gender : 'Not specified',
                'birthDate' => $birthDate,
                'fishrNumber' => $fishrNumber,
                'rsbsaNumber' => $rsbsaNumber,
                'emergencyName' => $emergencyName,
                'emergencyRelation' => $emergencyRelation,
                'emergencyAddress' => $emergencyAddress,
                'emergencyContact' => $emergencyContact,
                'photo' => $photoPath,
                'photoData' => '',
                'signatureData' => $signaturePath,
                'idNumber' => $idNumber
            ]
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function ensureRequestColumns() {
    global $pdo;
    try {
        $pdo->exec("ALTER TABLE requests ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");
    } catch (Exception $e) {}
}

function getRequests() {
    global $pdo;
    ensureRequestColumns();
    try {
        $stmt = $pdo->query("SELECT r.*, f.first_name, f.last_name FROM requests r LEFT JOIN fisherfolk f ON r.fisherfolk_id = f.fisherfolk_id ORDER BY r.date_submitted DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

function updateRecord($data) {
    global $pdo;

    $recordId = $data['id'] ?? $data['recordId'] ?? null;
    
    if (!$recordId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing record ID']);
        return;
    }

    // Extract numeric ID from FF-YYYY-NNNN format if needed
    $fisherfolkId = $recordId;
    if (strpos($recordId, 'FF-') === 0) {
        // Extract the numeric part
        $parts = explode('-', $recordId);
        $fisherfolkId = isset($parts[2]) ? (int)$parts[2] : (int)$recordId;
    }

    try {
        ensureFisherfolkRegistrationColumns();
        ensureIdCardSignatureColumn();

        // Get the current record to get user_id
        $stmt = $pdo->prepare('SELECT user_id FROM fisherfolk WHERE fisherfolk_id = ? LIMIT 1');
        $stmt->execute([$fisherfolkId]);
        $currentRecord = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$currentRecord) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Record not found']);
            return;
        }

        $userId = $currentRecord['user_id'];

        // Ownership check: non-admins may only update their own fisherfolk record
        if (!isAdmin()) {
            $sessionUserId = $_SESSION['user_id'] ?? null;
            if (empty($sessionUserId) || (int)$sessionUserId !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Forbidden: cannot modify other users']);
                return;
            }
        }

        // Build update query for fisherfolk table
        $updateFields = [];
        $params = [':fisherfolk_id' => $fisherfolkId];
        
        $fieldMapping = [
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'middleName' => 'middle_name',
            'birthDate' => 'birthdate',
            'gender' => 'gender',
            'contact' => 'contact_number',
            'barangay' => 'barangay_name',
            'livelihood' => 'livelihood',
            'fishrNumber' => 'fishr_number',
            'rsbsaNumber' => 'rsbsa_number',
            'emergencyName' => 'emergency_name',
            'emergencyRelation' => 'emergency_relation',
            'emergencyAddress' => 'emergency_address',
            'emergencyContact' => 'emergency_contact',
            'idDesign' => 'id_design'
        ];

        // If data is wrapped in 'fields' (from updateRecordFields), unwrap it
        $sourceData = isset($data['fields']) ? array_merge($data, $data['fields']) : $data;

        foreach ($fieldMapping as $apiField => $dbField) {
            if (isset($sourceData[$apiField])) {
                $val = $sourceData[$apiField];
                // Convert idDesign object to JSON string for database
                if ($apiField === 'idDesign' && is_array($val)) {
                    $val = json_encode($val);
                }
                $updateFields[] = "$dbField = :$apiField";
                $params[":$apiField"] = $val;
            }
        }

        // Handle photo data
        if (isset($sourceData['photoData']) && !empty($sourceData['photoData'])) {
            $updateFields[] = "photo_data = :photoData";
            $params[':photoData'] = $sourceData['photoData'];
        }

        if (empty($updateFields)) {
            // Check if we still have card updates even if no fisherfolk fields changed
            if (empty($sourceData['photoData']) && empty($sourceData['signatureData'])) {
                echo json_encode(['success' => true, 'message' => 'No fields to update']);
                return;
            }
        } else {
            ensureIdDesignColumn();
            $sql = "UPDATE fisherfolk SET " . implode(', ', $updateFields) . " WHERE fisherfolk_id = :fisherfolk_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // Update id_cards table if photo or signature changed
        if (!empty($sourceData['photoData']) || !empty($sourceData['signatureData'])) {
            $cardUpdates = [];
            $cardParams = [':id' => $fisherfolkId];
            
            if (!empty($sourceData['photoData'])) {
                $photoPath = saveBase64Image($sourceData['photoData'], 'photos', 'photo_' . $fisherfolkId);
                $cardUpdates[] = "photo_path = :photoPath";
                $cardParams[':photoPath'] = $photoPath;
            }
            if (!empty($sourceData['signatureData'])) {
                // If it's base64, save it. If it's already a path, use it.
                if (strpos($sourceData['signatureData'], 'data:image') === 0) {
                    $sigPath = saveBase64Image($sourceData['signatureData'], 'photos', 'sig_' . $fisherfolkId);
                } else {
                    $sigPath = $sourceData['signatureData'];
                }
                $cardUpdates[] = "signature_path = :sigPath";
                $cardParams[':sigPath'] = $sigPath;
            }

            if (!empty($cardUpdates)) {
                $cardSql = "UPDATE id_cards SET " . implode(', ', $cardUpdates) . " WHERE fisherfolk_id = :id";
                $cardStmt = $pdo->prepare($cardSql);
                $cardStmt->execute($cardParams);
            }
        }

            // If username or password were included, update the linked users table
            if (isset($sourceData['username']) || isset($sourceData['password'])) {
                try {
                    $newUsername = isset($sourceData['username']) ? trim((string)$sourceData['username']) : null;
                    $newPassword = isset($sourceData['password']) ? (string)$sourceData['password'] : null;

                    // Check username uniqueness if provided
                    if ($newUsername !== null && $newUsername !== '') {
                        $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id <> ? LIMIT 1");
                        $chk->execute([$newUsername, $userId]);
                        if ($chk->fetch()) {
                            http_response_code(400);
                            echo json_encode(['success' => false, 'error' => 'Username already exists']);
                            return;
                        }
                    }

                    $userUpdates = [];
                    $userParams = [];
                    if ($newUsername !== null && $newUsername !== '') {
                        $userUpdates[] = "username = :username";
                        $userUpdates[] = "email = :email";
                        $userParams[':username'] = $newUsername;
                        $userParams[':email'] = $newUsername . '@fisherfolk.local';
                    }
                    if ($newPassword !== null && $newPassword !== '') {
                        $userUpdates[] = "password = :password";
                        $userParams[':password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                    }

                    if (!empty($userUpdates)) {
                        $userParams[':user_id'] = $userId;
                        $sql = "UPDATE users SET " . implode(', ', $userUpdates) . " WHERE user_id = :user_id";
                        $ustmt = $pdo->prepare($sql);
                        $ustmt->execute($userParams);
                        // Log the activity for user credential update
                        logActivityEntry($_SESSION['user_id'], 'updated_user_credentials', 'users', $userId, null, 'Updated username/password for linked fisherfolk');
                    }
                } catch (Exception $e) {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'error' => 'Failed updating user credentials: ' . $e->getMessage()]);
                    return;
                }
            }

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'updated_fisherfolk_profile', 'fisherfolk', $fisherfolkId, null, 'Updated record details');

        echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function ensureIdDesignColumn() {
    global $pdo;

    try {
        $pdo->exec("ALTER TABLE fisherfolk ADD COLUMN IF NOT EXISTS id_design LONGTEXT NULL");
    } catch (Exception $e) {
        // Ignore schema errors here; updateRecord can still succeed for non-design fields.
    }
}

function ensureFisherfolkRegistrationColumns() {
    global $pdo;

    $columns = [
        "birthdate DATE NULL",
        "fishr_number VARCHAR(50) NULL",
        "rsbsa_number VARCHAR(50) NULL",
        "emergency_name VARCHAR(100) NULL",
        "emergency_relation VARCHAR(50) NULL",
        "emergency_address TEXT NULL",
        "emergency_contact VARCHAR(20) NULL",
        "gender VARCHAR(20) DEFAULT 'Not specified'",
        "id_design LONGTEXT NULL",
        "photo_data LONGTEXT NULL"
    ];

    foreach ($columns as $definition) {
        try {
            $columnName = strtok($definition, ' ');
            $check = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fisherfolk' AND COLUMN_NAME = ? LIMIT 1");
            $check->execute([$columnName]);
            if (!$check->fetchColumn()) {
                $pdo->exec("ALTER TABLE fisherfolk ADD COLUMN {$definition}");
            }
        } catch (Exception $e) {
            // ignore if the column already exists or the server cannot alter the table in-place
        }
    }
}

function ensureIdCardSignatureColumn() {
    global $pdo;

    try {
        $check = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'id_cards' AND COLUMN_NAME = 'signature_path' LIMIT 1");
        $check->execute();
        if (!$check->fetchColumn()) {
            $pdo->exec("ALTER TABLE id_cards ADD COLUMN signature_path VARCHAR(255) NULL");
        }
    } catch (Exception $e) {
        // ignore if the column already exists or the server cannot alter the table in-place
    }
}

function ensureFisherfolkArchiveColumns() {
    global $pdo;

    $columns = [
        'archived_at DATETIME NULL',
        'archived_by INT NULL',
        'archive_reason VARCHAR(255) NULL'
    ];

    foreach ($columns as $definition) {
        try {
            $columnName = strtok($definition, ' ');
            $check = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'fisherfolk' AND COLUMN_NAME = ? LIMIT 1");
            $check->execute([$columnName]);
            if (!$check->fetchColumn()) {
                $pdo->exec("ALTER TABLE fisherfolk ADD COLUMN {$definition}");
            }
        } catch (Exception $e) {
            // ignore if the server cannot alter the table in-place
        }
    }
}

function updateRecordStatus($data) {
    global $pdo;

    $recordId = $data['recordId'] ?? null;
    $status = $data['status'] ?? null;

    if (!$recordId || !$status) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing recordId or status']);
        return;
    }

    try {
        $stmt = $pdo->prepare("UPDATE fisherfolk SET status = :status WHERE fisherfolk_id = :fisherfolk_id");
        $stmt->execute([
            ':status' => $status,
            ':fisherfolk_id' => $recordId
        ]);

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'updated_status', 'fisherfolk', $recordId, null, $status);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function deleteRecord($data) {
    archiveRecord($data);
}

function archiveRecord($data) {
    global $pdo;

    $recordId = $data['recordId'] ?? null;
    $reason = trim((string)($data['reason'] ?? 'Archived from admin dashboard'));

    if (!$recordId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing recordId']);
        return;
    }

    try {
        ensureFisherfolkArchiveColumns();

        $stmt = $pdo->prepare("SELECT fisherfolk_id, user_id FROM fisherfolk WHERE fisherfolk_id = :fisherfolk_id LIMIT 1");
        $stmt->execute([':fisherfolk_id' => $recordId]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$record) {
            http_response_code(404);
            echo json_encode(['error' => 'Record not found']);
            return;
        }

        if (!isAdmin()) {
            $userId = $_SESSION['user_id'];
            if ((int)$record['user_id'] !== (int)$userId) {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden']);
                return;
            }
        }

        $stmt = $pdo->prepare("UPDATE fisherfolk SET archived_at = NOW(), archived_by = :archived_by, archive_reason = :archive_reason WHERE fisherfolk_id = :fisherfolk_id");
        $stmt->execute([
            ':archived_by' => $_SESSION['user_id'] ?? null,
            ':archive_reason' => $reason,
            ':fisherfolk_id' => $recordId
        ]);

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'archived_record', 'fisherfolk', $recordId, null, $reason);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function restoreRecord($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $recordId = $data['recordId'] ?? null;

    if (!$recordId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing recordId']);
        return;
    }

    try {
        ensureFisherfolkArchiveColumns();

        $stmt = $pdo->prepare("UPDATE fisherfolk SET archived_at = NULL, archived_by = NULL, archive_reason = NULL WHERE fisherfolk_id = :fisherfolk_id");
        $stmt->execute([':fisherfolk_id' => $recordId]);

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'restored_record', 'fisherfolk', $recordId, null, 'Record restored from archive');

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function addRequest($data) {
    global $pdo;

    $fisherfolkId = $data['fisherfolkId'] ?? null;
    $requestType = $data['requestType'] ?? null;
    $description = $data['description'] ?? '';

    if (!$fisherfolkId || !$requestType) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing fisherfolkId or requestType']);
        return;
    }

    try {
        $subject = sanitize($data['subject'] ?? 'General Request');
        $boatName = sanitize($data['boatName'] ?? null);
        $boatType = sanitize($data['boatType'] ?? null);
        $boatColor = sanitize($data['boatColor'] ?? null);
        $boatSize = sanitize($data['boatSize'] ?? null);
        $incidentDate = sanitize($data['incidentDate'] ?? null);
        $damageCost = $data['damageCost'] ?? null;
        $adminNotes = sanitize($data['adminNotes'] ?? null);

        $stmt = $pdo->prepare("INSERT INTO requests (fisherfolk_id, request_type, subject, description, boat_name, boat_type, boat_color, boat_size, incident_date, estimated_damage, admin_notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->execute([
            $fisherfolkId,
            $requestType,
            $subject,
            $description,
            $boatName,
            $boatType,
            $boatColor,
            $boatSize,
            $incidentDate !== '' ? $incidentDate : null,
            $damageCost !== '' ? $damageCost : null,
            $adminNotes,
        ]);

        echo json_encode(['success' => true, 'requestId' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function addAnnouncement($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $title = $data['title'] ?? null;
    $message = $data['message'] ?? null;

    if (!$title || !$message) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing title or message']);
        return;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, message, created_by) VALUES (:title, :message, :created_by)");
        $stmt->execute([
            ':title' => $title,
            ':message' => $message,
            ':created_by' => $_SESSION['user_id']
        ]);
        $annId = $pdo->lastInsertId();

        // Log the activity
        logActivityEntry($_SESSION['user_id'], 'posted_announcement', 'announcements', $annId, null, $title);

        echo json_encode(['success' => true, 'announcementId' => $annId]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateRequest($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    $requestId = $data['requestId'] ?? null;
    $fields = $data['fields'] ?? [];

    if (!$requestId || empty($fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing requestId or fields']);
        return;
    }

    try {
        // Build dynamic update query
        $setParts = [];
        $params = [':request_id' => $requestId];

        $mapping = [
            'status' => 'status',
            'adminNote' => 'admin_notes',
            'subject' => 'subject',
            'description' => 'description'
        ];

        foreach ($fields as $field => $value) {
            if (isset($mapping[$field])) {
                $column = $mapping[$field];
                $setParts[] = "$column = :$field";
                $params[":$field"] = $value;
            }
        }

        if (!empty($setParts)) {
            $query = "UPDATE requests SET " . implode(', ', $setParts) . " WHERE request_id = :request_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        }

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePut() {
    // Handle PUT requests if needed
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}

function handleDelete() {
    // Handle DELETE requests if needed
    http_response_code(501);
    echo json_encode(['error' => 'Not implemented']);
}

function getRecords() {
    global $pdo;

    try {
        ensureFisherfolkArchiveColumns();
        $stmt = $pdo->query("\n            SELECT f.*, u.username, b.barangay_name, b.municipality, b.province,\n                   ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status\n            FROM fisherfolk f\n            LEFT JOIN users u ON f.user_id = u.user_id\n            LEFT JOIN barangay b ON f.barangay_id = b.barangay_id\n            LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id\n            WHERE f.archived_at IS NULL\n            ORDER BY f.created_at DESC\n        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $defaultIdDesign = [
            'themeKey' => 'coastal',
            'title' => 'Municipal Fisherfolk ID',
            'subtitle' => 'Official identity card for registered fisherfolk',
            'badge' => 'Verified Fisherfolk',
            'accentColor' => ''
        ];

        $records = array_map(function($f) use ($defaultIdDesign) {
            $decodedDesign = json_decode((string)($f['id_design'] ?? ''), true);
            $idDesign = is_array($decodedDesign) ? array_merge($defaultIdDesign, $decodedDesign) : $defaultIdDesign;

            return [
                'id' => (string)($f['fisherfolk_id'] ?? ''),
                'username' => $f['username'] ?? '',
                'firstName' => $f['first_name'] ?? '',
                'middleName' => $f['middle_name'] ?? '',
                'lastName' => $f['last_name'] ?? '',
                'barangay' => $f['barangay_name'] ?? '',
                'livelihood' => $f['livelihood'] ?? '',
                'contact' => $f['contact_number'] ?? '',
                'status' => $f['status'] ?? 'Pending',
                'validity' => !empty($f['expiry_date']) ? date('m/Y', strtotime($f['expiry_date'])) : 'N/A',
                'gender' => $f['gender'] ?? 'Not specified',
                'birthDate' => $f['birthdate'] ?? '',
                'fishrNumber' => $f['fishr_number'] ?? '',
                'rsbsaNumber' => $f['rsbsa_number'] ?? '',
                'emergencyName' => $f['emergency_name'] ?? '',
                'emergencyRelation' => $f['emergency_relation'] ?? '',
                'emergencyAddress' => $f['emergency_address'] ?? '',
                'emergencyContact' => $f['emergency_contact'] ?? '',
                'photo' => $f['photo_path'] ?? 'Photo',
                'photoData' => $f['photo_data'] ?? '',
                'signatureData' => $f['signature_path'] ?? '',
                'idNumber' => $f['id_number'] ?? '',
                'idDesign' => $idDesign
            ];
        }, $rows);

        echo json_encode(['success' => true, 'records' => $records]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error', 'detail' => $e->getMessage()]);
    }
}

function getArchivedRecords() {
    global $pdo;

    try {
        ensureFisherfolkArchiveColumns();
        $stmt = $pdo->query("\n            SELECT f.*, u.username, b.barangay_name, b.municipality, b.province,\n                   ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status\n            FROM fisherfolk f\n            LEFT JOIN users u ON f.user_id = u.user_id\n            LEFT JOIN barangay b ON f.barangay_id = b.barangay_id\n            LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id\n            WHERE f.archived_at IS NOT NULL\n            ORDER BY f.archived_at DESC, f.created_at DESC\n        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $defaultIdDesign = [
            'themeKey' => 'coastal',
            'title' => 'Municipal Fisherfolk ID',
            'subtitle' => 'Official identity card for registered fisherfolk',
            'badge' => 'Verified Fisherfolk',
            'accentColor' => ''
        ];

        $records = array_map(function($f) use ($defaultIdDesign) {
            $decodedDesign = json_decode((string)($f['id_design'] ?? ''), true);
            $idDesign = is_array($decodedDesign) ? array_merge($defaultIdDesign, $decodedDesign) : $defaultIdDesign;

            return [
                'id' => (string)($f['fisherfolk_id'] ?? ''),
                'username' => $f['username'] ?? '',
                'firstName' => $f['first_name'] ?? '',
                'middleName' => $f['middle_name'] ?? '',
                'lastName' => $f['last_name'] ?? '',
                'barangay' => $f['barangay_name'] ?? '',
                'livelihood' => $f['livelihood'] ?? '',
                'contact' => $f['contact_number'] ?? '',
                'status' => $f['status'] ?? 'Pending',
                'validity' => !empty($f['expiry_date']) ? date('m/Y', strtotime($f['expiry_date'])) : 'N/A',
                'gender' => $f['gender'] ?? 'Not specified',
                'birthDate' => $f['birthdate'] ?? '',
                'fishrNumber' => $f['fishr_number'] ?? '',
                'rsbsaNumber' => $f['rsbsa_number'] ?? '',
                'emergencyName' => $f['emergency_name'] ?? '',
                'emergencyRelation' => $f['emergency_relation'] ?? '',
                'emergencyAddress' => $f['emergency_address'] ?? '',
                'emergencyContact' => $f['emergency_contact'] ?? '',
                'photo' => $f['photo_path'] ?? 'Photo',
                'photoData' => $f['photo_data'] ?? '',
                'signatureData' => $f['signature_path'] ?? '',
                'idNumber' => $f['id_number'] ?? '',
                'idDesign' => $idDesign,
                'archivedAt' => $f['archived_at'] ?? '',
                'archivedBy' => $f['archived_by'] ?? '',
                'archiveReason' => $f['archive_reason'] ?? ''
            ];
        }, $rows);

        echo json_encode(['success' => true, 'records' => $records]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error', 'detail' => $e->getMessage()]);
    }
}

// ============= NEW FUNCTIONS FOR POSTS, THEME, AND ACTIVITY LOGS =============

function getPosts() {
    global $pdo;

    try {
        $limit = $_GET['limit'] ?? 50;
        $offset = $_GET['offset'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT 
                fp.post_id,
                fp.fisherfolk_id,
                fp.content,
                fp.created_at,
                f.first_name,
                f.last_name,
                COUNT(DISTINCT pl.like_id) as like_count,
                COUNT(DISTINCT pc.comment_id) as comment_count
            FROM fisherfolk_posts fp
            LEFT JOIN fisherfolk f ON fp.fisherfolk_id = f.fisherfolk_id
            LEFT JOIN post_likes pl ON fp.post_id = pl.post_id
            LEFT JOIN post_comments pc ON fp.post_id = pc.post_id
            GROUP BY fp.post_id
            ORDER BY fp.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $posts = array_map(function($post) {
            return [
                'post_id' => $post['post_id'],
                'fisherfolk_id' => $post['fisherfolk_id'],
                'content' => $post['content'],
                'created_at' => $post['created_at'],
                'author_name' => $post['first_name'] . ' ' . $post['last_name'],
                'like_count' => (int)$post['like_count'],
                'comment_count' => (int)$post['comment_count']
            ];
        }, $posts);

        echo json_encode(['success' => true, 'posts' => $posts]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function createPost($data) {
    global $pdo;

    $content = trim((string)($data['content'] ?? ''));

    if (empty($content)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Post content cannot be empty']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT fisherfolk_id FROM fisherfolk WHERE user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $fisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fisherfolk) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'User profile not found']);
            return;
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO fisherfolk_posts (fisherfolk_id, content, created_at)
            VALUES (?, ?, NOW())
        ");
        $insertStmt->execute([$fisherfolk['fisherfolk_id'], $content]);

        // Log activity
        logActivityEntry($_SESSION['user_id'], 'created_post', 'fisherfolk_posts', $pdo->lastInsertId(), null, $content);

        echo json_encode(['success' => true, 'message' => 'Post created successfully']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function deletePost($data) {
    global $pdo;

    $postId = (int)($data['post_id'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid post id']);
        return;
    }

    try {
        $stmt = $pdo->prepare("SELECT fisherfolk_id FROM fisherfolk WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $fisherfolk = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fisherfolk) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'User profile not found']);
            return;
        }

        $stmt = $pdo->prepare("SELECT post_id FROM fisherfolk_posts WHERE post_id = ? AND fisherfolk_id = ?");
        $stmt->execute([$postId, $fisherfolk['fisherfolk_id']]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'You can only delete your own post']);
            return;
        }

        $pdo->beginTransaction();

        $deleteStmt = $pdo->prepare("DELETE FROM fisherfolk_posts WHERE post_id = ?");
        $deleteStmt->execute([$postId]);

        logActivityEntry($_SESSION['user_id'], 'deleted_post', 'fisherfolk_posts', $postId, null, null);

        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

function getThemeSettings() {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    try {
        $stmt = $pdo->query("
            SELECT setting_name, setting_value FROM admin_settings 
            WHERE setting_name LIKE 'theme_%'
        ");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_name']] = $setting['setting_value'];
        }

        echo json_encode(['success' => true, 'settings' => $result]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function updateThemeSettings($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        return;
    }

    try {
        $pdo->beginTransaction();

        $themeFields = [
            'theme_sidebar_color',
            'theme_body_color',
            'theme_text_color',
            'theme_accent_color',
            'theme_mode',
            'system_name'
        ];

        foreach ($themeFields as $field) {
            if (isset($data[$field])) {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_settings (setting_name, setting_value, updated_by)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_by = ?
                ");
                $stmt->execute([$field, $data[$field], $_SESSION['user_id'], $data[$field], $_SESSION['user_id']]);

                // Log activity
                logActivityEntry($_SESSION['user_id'], 'updated_theme_' . $field, 'admin_settings', 0, null, $data[$field]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Theme settings updated successfully']);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
}

function getActivityLogs() {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        return;
    }

    try {
        $limit = $_GET['limit'] ?? 100;
        $offset = $_GET['offset'] ?? 0;

        $stmt = $pdo->prepare("
            SELECT 
                al.log_id,
                al.user_id,
                al.action,
                al.timestamp,
                al.table_name,
                al.record_id,
                u.username,
                u.email
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            ORDER BY al.timestamp DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'logs' => $logs]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function logActivityEntry($userId, $action, $tableName = null, $recordId = null, $oldValue = null, $newValue = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, table_name, record_id, old_value, new_value)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $action, $tableName, $recordId, $oldValue, $newValue]);
        return true;
    } catch (Exception $e) {
        // Silently fail - don't interrupt main operations
        return false;
    }
}

function logActivity($data) {
    global $pdo;

    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        return;
    }

    $action = $data['action'] ?? '';
    $tableName = $data['table_name'] ?? null;
    $recordId = $data['record_id'] ?? null;

    if (empty($action)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing action']);
        return;
    }

    try {
        logActivityEntry($_SESSION['user_id'], $action, $tableName, $recordId, null, null);
        echo json_encode(['success' => true, 'message' => 'Activity logged']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
    }
}


function markAnnouncementsRead() {
    global $pdo;
    try {
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}

function markRequestsRead() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE requests SET is_read = 1 WHERE is_read = 0 AND request_type LIKE '%Subsidy%'");
        $stmt->execute();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
}
?>
