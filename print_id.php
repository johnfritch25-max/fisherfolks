<?php
require_once 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid user ID');
}

$stmt = $pdo->prepare("
    SELECT u.*, f.*, b.barangay_name, b.municipality, b.province,
           ic.id_number, ic.photo_path, ic.signature_path, ic.issue_date, ic.expiry_date, ic.status as id_status
    FROM fisherfolk f
    LEFT JOIN users u ON f.user_id = u.user_id
    LEFT JOIN barangay b ON f.barangay_id = b.barangay_id
    LEFT JOIN id_cards ic ON f.fisherfolk_id = ic.fisherfolk_id
    WHERE f.fisherfolk_id = ? OR u.user_id = ?
    LIMIT 1
");
$stmt->execute([$_GET['id'], $_GET['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('User not found');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fisherfolk ID Card - Print</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="printStyles.css?v=<?php echo time(); ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .print-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }

        .no-print {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .no-print button {
            padding: 10px 20px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #2176c7;
            color: white;
            font-weight: bold;
        }

        .no-print button:hover {
            background: #1a5fa0;
        }

        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                gap: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print">
            <button onclick="window.print()">Print ID Card</button>
            <button onclick="window.close()">Close</button>
        </div>

        <div class="id-card-print-wrap">
            <div class="id-card-realistic">
                <div class="id-card-header-redesign">
                    <div>REPUBLIC OF THE PHILIPPINES</div>
                    <div>PROVINCE OF MINDORO</div>
                    <div>MUNICIPALITY OF BONGABONG</div>
                </div>

                <div class="id-card-body-redesign">
                    <div class="id-card-fields-redesign">
                        <div class="id-line-redesign">
                            <div class="id-label-redesign">LAST NAME</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php echo htmlspecialchars($user['last_name'] ?? ''); ?></span>
                            </div>
                        </div>

                        <div class="id-line-redesign">
                            <div class="id-label-redesign">FIRST NAME</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php echo htmlspecialchars($user['first_name'] ?? ''); ?></span>
                            </div>
                        </div>

                        <div class="id-line-redesign">
                            <div class="id-label-redesign">MIDDLE NAME</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php echo htmlspecialchars($user['middle_name'] ?? ''); ?></span>
                            </div>
                        </div>

                        <div class="id-line-redesign">
                            <div class="id-label-redesign">BARANGAY</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php 
                                    $barangay = $user['barangay_name'] ?? '';
                                    if ($user['province'] ?? false) {
                                        $barangay .= ', ' . $user['province'];
                                    }
                                    echo htmlspecialchars($barangay);
                                ?></span>
                            </div>
                        </div>

                        <div class="id-line-redesign">
                            <div class="id-label-redesign">BIRTHDAY</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php 
                                    if ($user['birthdate'] ?? false) {
                                        echo htmlspecialchars(date('m/d/Y', strtotime($user['birthdate'])));
                                    }
                                ?></span>
                            </div>
                        </div>

                        <div class="id-line-redesign">
                            <div class="id-label-redesign">FISHR NUMBER</div>
                            <div class="id-value-redesign">
                                <span class="id-value-text"><?php echo htmlspecialchars($user['fishr_number'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="id-card-photo-signature">
                        <div class="id-photo-redesign">
                            <?php 
                            if ($user['photo_path'] ?? false) {
                                if (file_exists($user['photo_path'])) {
                                    echo '<img src="' . htmlspecialchars($user['photo_path']) . '" class="id-photo-image-redesign" alt="Photo" />';
                                } else {
                                    echo 'Photo';
                                }
                            } else {
                                echo 'Photo';
                            }
                            ?>
                        </div>
                        <div class="id-signature-redesign">
                            <div class="id-signature-image-slot-redesign">
                                <?php 
                                if ($user['signature_path'] ?? false) {
                                    if (file_exists($user['signature_path'])) {
                                        echo '<img src="' . htmlspecialchars($user['signature_path']) . '" class="id-signature-image-redesign" alt="Signature" />';
                                    }
                                }
                                ?>
                            </div>
                            <div class="id-signature-line-redesign" aria-hidden="true"></div>
                            <div class="id-signature-label-redesign" style="color: #111111 !important; -webkit-text-fill-color: #111111 !important;">SIGNATURE</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>