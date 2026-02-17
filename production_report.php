

Ji, maine opening balance column ko input fields se hat kar code update kar diya hai. Ab input form mein sirf **Description, Receipt, Issue, Sale,** aur **Remarks** fields rahenge.

Iske sath hi, maine calculation logic bhi update kar diya hai taake bina opening balance ke report sahi generate ho.

```php
<?php
// production_report.php

$constantsFile = 'constants.json';

// Default constants
$defaults = [
    'companyName' => 'Salva Feed Mills (Pvt) Ltd',
    'plantName'   => 'Solvent Extraction Plant',
    'address'     => '10km Faisalabad Road Okara, Pakistan',
    'seedType'    => 'Soy Bean Seed',
    'origin'      => 'South Africa',
    'lcNo'        => '4'
];

// Load constants
$constants = $defaults;
if (file_exists($constantsFile)) {
    $loaded = json_decode(file_get_contents($constantsFile), true);
    if (is_array($loaded)) {
        $constants = array_merge($defaults, $loaded);
    }
}

// Save constants if requested
if (isset($_POST['save_constants'])) {
    $constants = [
        'companyName' => trim($_POST['companyName'] ?? $constants['companyName']),
        'plantName'   => trim($_POST['plantName'] ?? $constants['plantName']),
        'address'     => trim($_POST['address'] ?? $constants['address']),
        'seedType'    => trim($_POST['seedType'] ?? $constants['seedType']),
        'origin'      => trim($_POST['origin'] ?? $constants['origin']),
        'lcNo'        => trim($_POST['lcNo'] ?? $constants['lcNo'])
    ];
    file_put_contents($constantsFile, json_encode($constants, JSON_PRETTY_PRINT));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Generate report
$generateReport = isset($_POST['generate_report']);
$date = $_POST['date'] ?? date('Y-m-d');

// Variables for calculations
$todayCrushing = 0;
$seedTypeLower = strtolower($constants['seedType']);

// Specific variable for Recovery Denominator (Total Seed Issue)
$totalSeedIssue = 0; 

if ($generateReport && !empty($_POST['desc'])) {
    
    // Loop 1: Calculate Total Seed Issue (Denominator for Recovery)
    foreach ($_POST['desc'] as $i => $desc) {
        $descTrim = trim($desc);
        if ($descTrim === '') continue;

        $issue = floatval($_POST['issue'][$i] ?? 0);
        $receipt = floatval($_POST['receipt'][$i] ?? 0);

        // Identify Seed Row to get Total Issue
        if (strtolower($descTrim) === $seedTypeLower || stripos($descTrim, 'seed') !== false) {
            $totalSeedIssue += $issue; 
            // Today Crushing (Usually Receipt in Daily Report)
            $todayCrushing += $receipt; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        
        .report-header { 
            text-align: center; 
            margin-bottom: 20px; 
            line-height: 1.3; 
        }
        .report-header h2 { font-size: 26px; margin: 4px 0; font-weight: bold; }
        .report-header h3 { font-size: 22px; margin: 4px 0; }
        .report-header p { font-size: 18px; margin: 6px 0; }

        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 40px 0 30px 0;
            font-size: 18px;
            line-height: 2.1;
        }
        .info-left { text-align: left; padding-left: 30px; }
        .info-right { text-align: right; padding-right: 30px; }
        .info-item { margin: 8px 0; }
        .info-item strong { font-weight: bold; margin-right: 12px; min-width: 135px; display: inline-block; }

        .form-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 10px; 
            margin-bottom: 20px; 
        }
        label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 14px; }
        input[type=text], input[type=date], input[type=number] { 
            width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; 
        }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 14px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: center; }
        th { background: #f0f0f0; font-weight: bold; }
        
        /* Input table adjustments */
        #dynamicTable input { width: 100px; padding: 5px; border: 1px solid #ddd; }
        #dynamicTable input[name="desc[]"] { width: 160px; }
        #dynamicTable input[name="remarks[]"] { width: 150px; }

        .btn { padding: 12px 25px; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; margin: 10px 5px; }
        .btn-green { background: #4CAF50; color: white; }
        .btn-blue { background: #2196F3; color: white; }
        .btn-red { background: #f44336; color: white; }
        .btn-small { padding: 6px 12px; font-size: 13px; }

        #report { margin-top: 40px; padding: 30px; border: 2px solid #000; background: #fff; border-radius: 8px; }

        .signature { 
            margin-top: 50px; 
            display: flex; 
            justify-content: space-between; 
            font-size: 18px; 
            font-weight: bold;
        }
        .signature div {
            width: 30%;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align:center;">Solvent Extraction Plant - Daily Production Report (Input)</h1>

        <form method="POST">
            <div class="form-grid">
                <div><label>Company Name</label><input type="text" name="companyName" value="<?= htmlspecialchars($constants['companyName']) ?>"></div>
                <div><label>Plant Name</label><input type="text" name="plantName" value="<?= htmlspecialchars($constants['plantName']) ?>"></div>
                <div><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($constants['address']) ?>"></div>
                <div><label>Seed Type</label><input type="text" name="seedType" value="<?= htmlspecialchars($constants['seedType']) ?>"></div>
                <div><label>Origin</label><input type="text" name="origin" value="<?= htmlspecialchars($constants['origin']) ?>"></div>
                <div><label>LC#</label><input type="text" name="lcNo" value="<?= htmlspecialchars($constants['lcNo']) ?>"></div>
                <div><label>Date (Daily)</label><input type="date" name="date" value="<?= $generateReport ? htmlspecialchars($date) : date('Y-m-d') ?>"></div>
            </div>

            <button type="submit" name="save_constants" class="btn btn-blue">💾 Save Constant Values</button>
            <p><small>↳ Company name, address, plant wagera kal bhi same rahenge</small></p>

            <h2>Data Entry (Receipt | Issue | Sale)</h2>
            
            <div style="overflow-x: auto;">
            <table id="dynamicTable">
                <thead>
                    <tr>
                        <th style="width: 200px;">Description</th>
                        <th>Receipt (Kgs)</th>
                        <th>Issue (Kgs)</th>
                        <th>Sale (Kgs)</th>
                        <th style="width: 150px;">Remarks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($generateReport && !empty($_POST['desc'])): ?>
                        <?php foreach ($_POST['desc'] as $i => $desc): 
                            if (trim($desc) === '') continue;
                        ?>
                        <tr>
                            <td><input type="text" name="desc[]" value="<?= htmlspecialchars($desc) ?>"></td>
                            <!-- Opening Bal Removed -->
                            <td><input type="number" name="receipt[]" value="<?= htmlspecialchars($_POST['receipt'][$i] ?? '0') ?>"></td>
                            <td><input type="number" name="issue[]" value="<?= htmlspecialchars($_POST['issue'][$i] ?? '0') ?>"></td>
                            <td><input type="number" name="sale[]" value="<?= htmlspecialchars($_POST['sale'][$i] ?? '0') ?>"></td>
                            <td><input type="text" name="remarks[]" value="<?= htmlspecialchars($_POST['remarks'][$i] ?? '') ?>"></td>
                            <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">Delete</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Default Rows for New Entry -->
                        <tr>
                            <td><input type="text" name="desc[]" placeholder="e.g. Soy Bean Seed"></td>
                            <td><input type="number" name="receipt[]" placeholder="0"></td>
                            <td><input type="number" name="issue[]" placeholder="0"></td>
                            <td><input type="number" name="sale[]" placeholder="0"></td>
                            <td><input type="text" name="remarks[]" placeholder=""></td>
                            <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">Delete</button></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="desc[]" placeholder="e.g. Oil"></td>
                            <td><input type="number" name="receipt[]" placeholder="0"></td>
                            <td><input type="number" name="issue[]" placeholder="0"></td>
                            <td><input type="number" name="sale[]" placeholder="0"></td>
                            <td><input type="text" name="remarks[]" placeholder=""></td>
                            <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">Delete</button></td>
                        </tr>
                        <tr>
                            <td><input type="text" name="desc[]" placeholder="e.g. Meal"></td>
                            <td><input type="number" name="receipt[]" placeholder="0"></td>
                            <td><input type="number" name="issue[]" placeholder="0"></td>
                            <td><input type="number" name="sale[]" placeholder="0"></td>
                            <td><input type="text" name="remarks[]" placeholder=""></td>
                            <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">Delete</button></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>

            <button type="button" class="btn btn-blue" onclick="addRow()">+ Add New Item</button>
            <br><br>
            <button type="submit" name="generate_report" class="btn btn-green">📄 Generate Report (Update)</button>
        </form>

        <?php if ($generateReport && !empty($_POST['desc'])): ?>
        <div id="report">
            <div class="report-header">
                <h2><?= htmlspecialchars($constants['companyName']) ?></h2>
                <h3><?= htmlspecialchars($constants['plantName']) ?></h3>
                <p><?= htmlspecialchars($constants['address']) ?></p>
                <h3>Production Report</h3>
            </div>

            <div class="info-section">
                <div class="info-left">
                    <div class="info-item"><strong>Seed Type:</strong> <?= htmlspecialchars($constants['seedType']) ?></div>
                    <div class="info-item"><strong>Today Crushing:</strong> <?= number_format($todayCrushing) ?> Kg</div>
                </div>
                <div class="info-right">
                    <div class="info-item"><strong>Origin:</strong> <?= htmlspecialchars($constants['origin']) ?></div>
                    <div class="info-item"><strong>LC# :</strong> <?= htmlspecialchars($constants['lcNo']) ?></div>
                    <div class="info-item"><strong>Date:</strong> <?= date('j/m/Y', strtotime($date)) ?></div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <!-- Opening Bal Column Removed from Report -->
                        <th>Receipt Kgs</th>
                        <th>Total Kgs</th>
                        <th>Issue Kgs</th>
                        <th>Sale Kgs</th>
                        <th>Closing Bal Kgs</th>
                        <th>T. Receive Kgs</th>
                        <th>T. Issue Kgs</th>
                        <th>T. Sale Kgs</th>
                        <th>Recovery %age</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $hasData = false;
                    foreach ($_POST['desc'] as $i => $desc): 
                        $desc = trim($desc);
                        if ($desc === '') continue;
                        $hasData = true;

                        $opening = 0; // Fixed to 0
                        $receipt = floatval($_POST['receipt'][$i] ?? 0);
                        $issue   = floatval($_POST['issue'][$i] ?? 0);
                        $sale    = floatval($_POST['sale'][$i] ?? 0);
                        $remarks = htmlspecialchars($_POST['remarks'][$i] ?? '');
                        
                        // Calculations
                        $total   = $opening + $receipt; 
                        $closing = $total - $issue - $sale;
                        
                        // Recovery Calculation Logic
                        $recoveryDisplay = '-';
                        $descLower = strtolower($desc);
                        
                        // Apply formula only for Oil and Meal rows
                        if (stripos($descLower, 'oil') !== false || stripos($descLower, 'meal') !== false) {
                            if ($totalSeedIssue > 0) {
                                // Formula: (T. Receive Kgs of Item / T. Issue Kgs of Seed) * 100
                                $percent = ($receipt / $totalSeedIssue) * 100;
                                $recoveryDisplay = number_format($percent, 2) . ' %';
                            } else {
                                $recoveryDisplay = '0.00 %';
                            }
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($desc) ?></td>
                        <td><?= number_format($receipt) ?></td>
                        <td><?= number_format($total) ?></td>
                        <td><?= number_format($issue) ?></td>
                        <td><?= number_format($sale) ?></td>
                        <td><?= number_format($closing) ?></td>
                        <td><?= number_format($receipt) ?></td>
                        <td><?= number_format($issue) ?></td>
                        <td><?= number_format($sale) ?></td>
                        <td><strong><?= $recoveryDisplay ?></strong></td>
                        <td><?= $remarks ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (!$hasData): ?>
                    <tr><td colspan="11" style="text-align:center; color:red;">No items added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Signature Section -->
            <div class="signature">
                <div>
                    Production Manager
                    <div class="sig-line"></div>
                </div>
                <div>
                    General Manager
                    <div class="sig-line"></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function addRow() {
            const tbody = document.querySelector('#dynamicTable tbody');
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><input type="text" name="desc[]" placeholder="Item name"></td>
                <td><input type="number" name="receipt[]" value="0"></td>
                <td><input type="number" name="issue[]" value="0"></td>
                <td><input type="number" name="sale[]" value="0"></td>
                <td><input type="text" name="remarks[]" placeholder=""></td>
                <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">Delete</button></td>
            `;
            tbody.appendChild(row);
        }
    </script>
</body>
</html>
```