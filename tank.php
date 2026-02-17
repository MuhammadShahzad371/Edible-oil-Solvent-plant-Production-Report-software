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
    'lcNo'        => '4',
    'defOilRec'   => '44',
    'defMealRec'  => '54.5',
    'companyLogo' => '' 
];

// Load constants
 $constants = $defaults;
if (file_exists($constantsFile)) {
    $jsonContent = file_get_contents($constantsFile);
    if (!empty($jsonContent)) {
        $loaded = json_decode($jsonContent, true);
        if (is_array($loaded)) {
            $constants = array_merge($defaults, $loaded);
        }
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
        'lcNo'        => trim($_POST['lcNo'] ?? $constants['lcNo']),
        'defOilRec'   => trim($_POST['defOilRec'] ?? $constants['defOilRec']),
        'defMealRec'  => trim($_POST['defMealRec'] ?? $constants['defMealRec']),
        'companyLogo' => trim($constants['companyLogo'] ?? '') 
    ];

    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileExt = pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . rand(1000, 9999) . '.' . $fileExt;
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetPath)) {
            $constants['companyLogo'] = $targetPath;
        }
    }

    file_put_contents($constantsFile, json_encode($constants, JSON_PRETTY_PRINT));
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

 $generateReport = isset($_POST['generate_report']);
 $date = $_POST['date'] ?? date('Y-m-d');

// --- 1. TANK CALCULATIONS (Stock & Issued) ---
 $totalStockOil = 0;
 $totalIssuedOil = 0;

if ($generateReport) {
    if (!empty($_POST['sf'])) {
        for ($i = 0; $i < count($_POST['sf']); $i++) {
            $f = (float)($_POST['sf'][$i] ?? 0);
            $i_val = (float)($_POST['si'][$i] ?? 0);
            $s = (float)($_POST['ss'][$i] ?? 0);
            $p = (float)($_POST['sp'][$i] ?? 0);
            $inch = ($f * 12) + $i_val + ($s / 8);
            $qty = $inch * $p;
            $totalStockOil += $qty;
        }
    }

    if (!empty($_POST['if'])) {
        for ($i = 0; $i < count($_POST['if']); $i++) {
            $f = (float)($_POST['if'][$i] ?? 0);
            $i_val = (float)($_POST['ii'][$i] ?? 0);
            $s = (float)($_POST['is'][$i] ?? 0);
            $p = (float)($_POST['ip'][$i] ?? 0);
            $inch = ($f * 12) + $i_val + ($s / 8);
            $qty = $inch * $p;
            $totalIssuedOil += $qty;
        }
    }
}

// --- 2. FINAL CALCULATIONS ---
 $prevBalOil = floatval($_POST['prev_bal_oil'] ?? 0);
 $userOilRec = floatval($_POST['user_oil_rec'] ?? $constants['defOilRec']);
 $userMealRec = floatval($_POST['user_meal_rec'] ?? $constants['defMealRec']);

 $crudeOilProduction = ($totalStockOil + $totalIssuedOil) - $prevBalOil;
 $productionFactor = 0;
if ($userOilRec > 0) {
    $productionFactor = ($crudeOilProduction / $userOilRec) * 100;
}
 $mealProduction = ($productionFactor * $userMealRec) / 100;

// --- 3. MAIN TABLE LOGIC ---
 $todayCrushing = 0;
 $seedTypeLower = strtolower($constants['seedType']);
 $totalSeedIssue = 0; 
 $formRows = ['desc' => [], 'receipt' => [], 'issue' => [], 'sale' => [], 'remarks' => []];

if ($generateReport && !empty($_POST['desc'])) {
    $formRows['desc'] = $_POST['desc'];
    $formRows['receipt'] = $_POST['receipt'];
    $formRows['issue'] = $_POST['issue'];
    $formRows['sale'] = $_POST['sale'];
    $formRows['remarks'] = $_POST['remarks'];

    function injectValue(&$rows, $searchKey, $value, $targetCol) {
        $foundIndex = -1;
        foreach ($rows['desc'] as $i => $d) {
            if (stripos(trim($d), $searchKey) !== false) { $foundIndex = $i; break; }
        }
        if ($foundIndex !== -1) {
            if ($targetCol === 'issue') $rows['issue'][$foundIndex] = $value;
            if ($targetCol === 'receipt') $rows['receipt'][$foundIndex] = $value;
        } else {
            $rows['desc'][] = $searchKey; $rows['receipt'][] = 0; $rows['issue'][] = 0; $rows['sale'][] = 0; $rows['remarks'][] = '';
            if ($targetCol === 'issue') $rows['issue'][count($rows['issue'])-1] = $value;
            if ($targetCol === 'receipt') $rows['receipt'][count($rows['receipt'])-1] = $value;
        }
    }
    injectValue($formRows, 'Seed', round($productionFactor, 2), 'issue');
    injectValue($formRows, 'Crude Oil', round($crudeOilProduction, 2), 'receipt');
    injectValue($formRows, 'Meal', round($mealProduction, 2), 'receipt');

    foreach ($formRows['desc'] as $i => $desc) {
        $descTrim = trim($desc);
        if ($descTrim === '') continue;
        $issue = floatval($formRows['issue'][$i] ?? 0);
        $receipt = floatval($formRows['receipt'][$i] ?? 0);
        if (strtolower($descTrim) === $seedTypeLower || stripos($descTrim, 'seed') !== false) {
            $totalSeedIssue += $issue; $todayCrushing += $issue; 
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
        .container { max-width: 1400px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .report-header { text-align: center; margin-bottom: 20px; line-height: 1.3; }
        .report-header h2 { font-size: 26px; margin: 4px 0; font-weight: bold; }
        .report-header h3 { font-size: 22px; margin: 4px 0; }
        .company-logo { max-height: 80px; display: block; margin: 0 auto 10px auto; }
        .info-section { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 40px 0 30px 0; font-size: 18px; line-height: 2.1; }
        .info-left { text-align: left; padding-left: 30px; }
        .info-right { text-align: right; padding-right: 30px; }
        .info-item { margin: 8px 0; }
        .info-item strong { font-weight: bold; margin-right: 12px; min-width: 135px; display: inline-block; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 20px; }
        label { font-weight: bold; display: block; margin-bottom: 5px; font-size: 14px; }
        input[type=text], input[type=date], input[type=number] { width: 100%; padding: 8px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        
        .tank-section { background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 13px; }
        .general-table th, .general-table td { border: 1px solid #000; padding: 6px; text-align: center; }
        .general-table th { background: #f0f0f0; font-weight: bold; }
        #dynamicTable input { width: 100px; padding: 5px; border: 1px solid #ddd; }
        #dynamicTable input[name="desc[]"] { width: 160px; }
        #dynamicTable input[name="remarks[]"] { width: 150px; }
        .tank-name-input { width: 80px; text-align: center; }
        .btn { padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 14px; margin: 5px; }
        .btn-green { background: #4CAF50; color: white; }
        .btn-blue { background: #2196F3; color: white; }
        .btn-red { background: #f44336; color: white; }
        .btn-small { padding: 4px 8px; font-size: 12px; }
        #report { margin-top: 40px; padding: 30px; border: 2px solid #000; background: #fff; border-radius: 8px; }
        .calc-box { background: #e8f5e9; border: 2px solid #4CAF50; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .calc-row { display: flex; justify-content: space-between; margin: 5px 0; font-size: 16px; font-weight: bold; }
        .signature { margin-top: 50px; display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; }
        .signature div { width: 30%; text-align: center; }

        /* --- ISSUED OIL STYLES --- */
        .issued-table-container { width: 100%; margin: 20px auto; border: 3px solid #000; }
        table#issuedTable { width: 100%; border-collapse: collapse; background: #fff; }
        table#issuedTable th, table#issuedTable td { border: 3px solid #000; padding: 15px; text-align: center; vertical-align: middle; font-size: 16px; }
        table#issuedTable th { font-weight: bold; background-color: #f0f0f0; }
        table#issuedTable .tank-name { font-weight: bold; }
        table#issuedTable .small-cell { width: 60px; }
        table#issuedTable .action-btn { color: red; font-size: 24px; cursor: pointer; font-weight: bold; border: none; background: none; padding: 0; line-height: 1; }
        table#issuedTable .action-btn:hover { color: darkred; }
        
        .live-calc-highlight { background-color: #fffacd !important; font-weight: bold; color: #d32f2f; }
        
        /* Readonly input styling for Initial Dil */
        input[readonly] { background-color: #e9ecef; cursor: not-allowed; }
        
        table#issuedTable input { width: 100%; box-sizing: border-box; border: 1px solid #ccc; padding: 5px; text-align: center; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="text-align:center;">Solvent Extraction Plant - Daily Production Report</h1>
        <form method="POST" id="mainForm" enctype="multipart/form-data">
            <div class="form-grid">
                <div><label>Company Name</label><input type="text" name="companyName" value="<?= htmlspecialchars($constants['companyName']) ?>"></div>
                <div><label>Plant Name</label><input type="text" name="plantName" value="<?= htmlspecialchars($constants['plantName']) ?>"></div>
                <div><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($constants['address']) ?>"></div>
                <div><label>Seed Type</label><input type="text" name="seedType" value="<?= htmlspecialchars($constants['seedType']) ?>"></div>
                <div><label>Origin</label><input type="text" name="origin" value="<?= htmlspecialchars($constants['origin']) ?>"></div>
                <div><label>LC#</label><input type="text" name="lcNo" value="<?= htmlspecialchars($constants['lcNo']) ?>"></div>
                <div><label>Date (Daily)</label><input type="date" name="date" value="<?= $generateReport ? htmlspecialchars($date) : date('Y-m-d') ?>"></div>
                <div><label>Company Logo (Optional)</label><input type="file" name="company_logo" accept="image/*">
                    <?php if(!empty($constants['companyLogo'])): ?>
                        <div style="margin-top:5px;"><small>Current: <img src="<?= htmlspecialchars($constants['companyLogo']) ?>" style="height:30px; vertical-align:middle;"></small></div>
                    <?php endif; ?>
                </div>
            </div>
            <button type="submit" name="save_constants" class="btn btn-blue">💾 Save Constants</button>
            <hr style="margin: 20px 0;">

            <div class="tank-section">
                <h3>Oil Production Calculations</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                    <div><label>Previous Balance Oil (Kgs)</label><input type="number" step="any" name="prev_bal_oil" value="<?= $generateReport ? htmlspecialchars($_POST['prev_bal_oil']) : '0' ?>"></div>
                    <div><label>Oil Recovery % (User Input)</label><input type="number" step="any" name="user_oil_rec" value="<?= $generateReport ? htmlspecialchars($_POST['user_oil_rec']) : $constants['defOilRec'] ?>"></div>
                    <div><label>Meal Recovery % (User Input)</label><input type="number" step="any" name="user_meal_rec" value="<?= $generateReport ? htmlspecialchars($_POST['user_meal_rec']) : $constants['defMealRec'] ?>"></div>
                </div>
                
                <!-- Stock Oil Tank -->
                <h4>Stock Oil Tanks (Current)</h4>
                <table id="stockTable" class="general-table">
                    <thead><tr><th>Tank Name</th><th>Feet</th><th>Inch</th><th>Suter</th><th>Kg/Inch</th><th>Qty</th><th>Act</th></tr></thead>
                    <tbody>
                        <?php if($generateReport && !empty($_POST['sf'])): ?>
                            <?php for($i=0; $i<count($_POST['sf']); $i++): ?>
                            <tr>
                                <td><input type="text" name="tank_name_s[]" class="tank-name-input" value="<?= $_POST['tank_name_s'][$i] ?? '' ?>"></td>
                                <td><input class="ssf" name="sf[]" value="<?= $_POST['sf'][$i] ?>" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="ssi" name="si[]" value="<?= $_POST['si'][$i] ?>" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="sss" name="ss[]" value="<?= $_POST['ss'][$i] ?>" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="ssp" name="sp[]" value="<?= $_POST['sp'][$i] ?>" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td class="ssq total" id="sq_<?=$i?>">0</td>
                                <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">X</button></td>
                            </tr>
                            <?php endfor; ?>
                        <?php else: ?>
                            <tr>
                                <td><input type="text" name="tank_name_s[]" class="tank-name-input" placeholder="Tank #"></td>
                                <td><input class="ssf" name="sf[]" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="ssi" name="si[]" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="sss" name="ss[]" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td><input class="ssp" name="sp[]" value="10" oninput="calc(this.parentNode.parentNode,'s')"></td>
                                <td class="ssq total">0</td>
                                <td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">X</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-blue btn-small" onclick="addTankRow('stockTable','s')">+ Add Stock Tank</button>
                <div style="text-align:right; font-weight:bold;">Total Stock Oil: <span id="sTotal">0</span> Kg</div>
                <br>

                <!-- Issued Oil Tank -->
                <h4>Issued Oil Tanks</h4>
                <div class="issued-table-container">
                    <table id="issuedTable">
                        <thead><tr><th colspan="2">Tank Name</th><th>Feet</th><th>Inch</th><th>Sooter</th><th>per/inch</th><th>Qty</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php 
                            $labels_i = $_POST['tank_label_i'] ?? [];
                            if($generateReport && !empty($_POST['if'])): ?>
                                <?php for($i=0; $i<count($_POST['if']); $i++): 
                                    $isInitial = (trim($_POST['tank_name_i'][$i] ?? '') === 'Initial Dil');
                                ?>
                                <tr>
                                    <td style="width: 25%;">
                                        <input type="text" name="tank_name_i[]" value="<?= $_POST['tank_name_i'][$i] ?? '' ?>" class="tank-name" <?= $isInitial ? 'readonly' : '' ?>>
                                    </td>
                                    <td class="small-cell">
                                        <input type="text" name="tank_label_i[]" value="<?= $labels_i[$i] ?? '' ?>" placeholder="1st" <?= $isInitial ? 'readonly' : '' ?>>
                                    </td>
                                    <td><input class="isf" name="if[]" value="<?= $_POST['if'][$i] ?>" oninput="calc(this.parentNode.parentNode,'i')" <?= $isInitial ? 'readonly' : '' ?>></td>
                                    <td><input class="isi" name="ii[]" value="<?= $_POST['ii'][$i] ?>" oninput="calc(this.parentNode.parentNode,'i')" <?= $isInitial ? 'readonly' : '' ?>></td>
                                    <td><input class="iss" name="is[]" value="<?= $_POST['is'][$i] ?>" oninput="calc(this.parentNode.parentNode,'i')" <?= $isInitial ? 'readonly' : '' ?>></td>
                                    <td><input class="isp" name="ip[]" value="<?= $_POST['ip'][$i] ?>" oninput="calc(this.parentNode.parentNode,'i')" <?= $isInitial ? 'readonly' : '' ?>></td>
                                    <td class="isq total" id="iq_<?=$i?>"><?= $_POST['tank_name_i'][$i] === 'Initial Dil' ? '' : '0' ?></td>
                                    <td><button type="button" class="action-btn" onclick="this.closest('tr').remove()">✕</button></td>
                                </tr>
                                <?php endfor; ?>
                            <?php else: ?>
                                <!-- 1st Dip -->
                                <tr>
                                    <td style="width: 25%;"><input type="text" name="tank_name_i[]" value="Tank # 7" class="tank-name"></td>
                                    <td class="small-cell"><input type="text" name="tank_label_i[]" value="1st"></td>
                                    <td><input class="isf" name="if[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="isi" name="ii[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="iss" name="is[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="isp" name="ip[]" value="10" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td class="isq total">0</td>
                                    <td><button type="button" class="action-btn" onclick="this.closest('tr').remove()">✕</button></td>
                                </tr>
                                <!-- 2nd Dip -->
                                <tr>
                                    <td><input type="text" name="tank_name_i[]" value="Tank # 7" class="tank-name"></td>
                                    <td class="small-cell"><input type="text" name="tank_label_i[]" value="2nd"></td>
                                    <td><input class="isf" name="if[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="isi" name="ii[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="iss" name="is[]" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td><input class="isp" name="ip[]" value="10" oninput="calc(this.parentNode.parentNode,'i')"></td>
                                    <td class="isq total">0</td>
                                    <td><button type="button" class="action-btn" onclick="this.closest('tr').remove()">✕</button></td>
                                </tr>
                                <!-- Initial Dil Row (Readonly Inputs) -->
                                <tr>
                                    <td colspan="2" class="tank-name">
                                        <input type="text" name="tank_name_i[]" value="Initial Dil" class="tank-name" readonly style="font-weight:bold; background:#eee;">
                                    </td>
                                    <td><input class="isf" name="if[]" readonly></td>
                                    <td><input class="isi" name="ii[]" readonly></td>
                                    <td><input class="iss" name="is[]" readonly></td>
                                    <td><input class="isp" name="ip[]" value="10" readonly></td>
                                    <td class="isq total" style="background:#fffacd; font-weight:bold;">0</td>
                                    <td><button type="button" class="action-btn" onclick="this.closest('tr').remove()">✕</button></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-blue btn-small" onclick="addIssuedTankRow()">+ Add Issued Tank</button>
                <div style="text-align:right; font-weight:bold; margin-top:10px;">Total Issued Oil: <span id="iTotal">0</span> Kg</div>

                <?php if ($generateReport): ?>
                <div class="calc-box" style="margin-top:20px;">
                    <h4 style="margin-top:0; text-align:center; color:#2e7d32;">Final Calculation Summary</h4>
                    <div class="calc-row"><span>Previous Balance Oil:</span> <span><?= number_format($prevBalOil, 2) ?> Kg</span></div>
                    <div class="calc-row"><span>Total Stock Oil (Tanks):</span> <span><?= number_format($totalStockOil, 2) ?> Kg</span></div>
                    <div class="calc-row"><span>Total Issued Oil (Tanks):</span> <span><?= number_format($totalIssuedOil, 2) ?> Kg</span></div>
                    <div class="calc-row" style="border-top:1px solid #ccc; padding-top:5px;"><span>Crude Oil Production (Calc):</span> <span><?= number_format($crudeOilProduction, 2) ?> Kg</span></div>
                    <div class="calc-row"><span>Production Factor (Seed):</span> <span><?= number_format($productionFactor, 2) ?></span></div>
                    <div class="calc-row" style="color:#d32f2f;"><span>Meal Production (Calc):</span> <span><?= number_format($mealProduction, 2) ?> Kg</span></div>
                </div>
                <?php endif; ?>
            </div>
            <hr>
            <h2>Data Entry (Receipt | Issue | Sale)</h2>
            <table id="dynamicTable" class="general-table">
                <thead><tr><th>Description</th><th>Receipt (Kgs)</th><th>Issue (Kgs)</th><th>Sale (Kgs)</th><th>Remarks</th><th>Action</th></tr></thead>
                <tbody id="entryTableBody">
                    <?php if ($generateReport && !empty($formRows['desc'])): ?>
                        <?php foreach ($formRows['desc'] as $i => $desc): if (trim($desc) === '') continue; ?>
                        <tr>
                            <td><input type="text" name="desc[]" value="<?= htmlspecialchars($desc) ?>"></td>
                            <td><input type="number" name="receipt[]" value="<?= htmlspecialchars($formRows['receipt'][$i] ?? '0') ?>"></td>
                            <td><input type="number" name="issue[]" value="<?= htmlspecialchars($formRows['issue'][$i] ?? '0') ?>"></td>
                            <td><input type="number" name="sale[]" value="<?= htmlspecialchars($formRows['sale'][$i] ?? '0') ?>"></td>
                            <td><input type="text" name="remarks[]" value="<?= htmlspecialchars($formRows['remarks'][$i] ?? '') ?>"></td>
                            <td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td><input type="text" name="desc[]" placeholder="e.g. Soy Bean Seed"></td><td><input type="number" name="receipt[]" placeholder="0"></td><td><input type="number" name="issue[]" placeholder="0"></td><td><input type="number" name="sale[]" placeholder="0"></td><td><input type="text" name="remarks[]" placeholder=""></td><td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td></tr>
                        <tr><td><input type="text" name="desc[]" placeholder="e.g. Crude Oil"></td><td><input type="number" name="receipt[]" placeholder="0"></td><td><input type="number" name="issue[]" placeholder="0"></td><td><input type="number" name="sale[]" placeholder="0"></td><td><input type="text" name="remarks[]" placeholder=""></td><td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td></tr>
                        <tr><td><input type="text" name="desc[]" placeholder="e.g. Meal"></td><td><input type="number" name="receipt[]" placeholder="0"></td><td><input type="number" name="issue[]" placeholder="0"></td><td><input type="number" name="sale[]" placeholder="0"></td><td><input type="text" name="remarks[]" placeholder=""></td><td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="btn btn-blue" onclick="addRow()">+ Add Item</button>
            <br><br>
            <button type="submit" name="generate_report" class="btn btn-green">📄 Generate Final Report</button>
        </form>

        <?php if ($generateReport): ?>
        <div id="report">
            <div class="report-header">
                <?php if (!empty($constants['companyLogo']) && file_exists($constants['companyLogo'])): ?>
                    <img src="<?= htmlspecialchars($constants['companyLogo']) ?>" class="company-logo" alt="Company Logo">
                <?php endif; ?>
                <h2><?= htmlspecialchars($constants['companyName']) ?></h2>
                <h3><?= htmlspecialchars($constants['plantName']) ?></h3>
                <p><?= htmlspecialchars($constants['address']) ?></p>
                <h3>Production Report</h3>
            </div>
            <div class="info-section">
                <div class="info-left">
                    <div class="info-item"><strong>Seed Type:</strong> <?= htmlspecialchars($constants['seedType']) ?></div>
                    <div class="info-item"><strong>Today Crushing:</strong> <?= number_format(round($todayCrushing), 0) ?> Kg</div>
                </div>
                <div class="info-right">
                    <div class="info-item"><strong>Origin:</strong> <?= htmlspecialchars($constants['origin']) ?></div>
                    <div class="info-item"><strong>LC# :</strong> <?= htmlspecialchars($constants['lcNo']) ?></div>
                    <div class="info-item"><strong>Date:</strong> <?= date('j/m/Y', strtotime($date)) ?></div>
                </div>
            </div>
            <table class="general-table">
                <thead><tr><th>Description</th><th>Receipt Kgs</th><th>Total Kgs</th><th>Issue Kgs</th><th>Sale Kgs</th><th>Closing Bal Kgs</th><th>T. Receive Kgs</th><th>T. Issue Kgs</th><th>T. Sale Kgs</th><th>Recovery %age</th><th>Remarks</th></tr></thead>
                <tbody>
                    <?php 
                    $hasData = false;
                    foreach ($formRows['desc'] as $i => $desc): 
                        $desc = trim($desc); if ($desc === '') continue; $hasData = true;
                        $receipt = floatval($formRows['receipt'][$i] ?? 0); $issue = floatval($formRows['issue'][$i] ?? 0); $sale = floatval($formRows['sale'][$i] ?? 0); $remarks = htmlspecialchars($formRows['remarks'][$i] ?? '');
                        $total = $receipt; $closing = $total - $issue - $sale;
                        $recoveryDisplay = '-'; $descLower = strtolower($desc);
                        if (stripos($descLower, 'oil') !== false || stripos($descLower, 'meal') !== false) {
                            if ($totalSeedIssue > 0) { $percent = ($receipt / $totalSeedIssue) * 100; $recoveryDisplay = number_format($percent, 2) . ' %'; } else { $recoveryDisplay = '0.00 %'; }
                        }
                    ?>
                    <tr><td><?= htmlspecialchars($desc) ?></td><td><?= number_format($receipt) ?></td><td><?= number_format($total) ?></td><td><?= number_format($issue) ?></td><td><?= number_format($sale) ?></td><td><?= number_format($closing) ?></td><td><?= number_format($receipt) ?></td><td><?= number_format($issue) ?></td><td><?= number_format($sale) ?></td><td><strong><?= $recoveryDisplay ?></strong></td><td><?= $remarks ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$hasData): ?><tr><td colspan="11" style="text-align:center; color:red;">No items added yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
            <div class="signature"><div>Production Manager<br><br></div><div>General Manager<br><br></div></div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const STORAGE_KEY = 'production_report_rows';
        const isGenerated = <?= $generateReport ? 'true' : 'false' ?>;

        function saveDynamicTable() {
            if (isGenerated) return;
            const rows = [];
            document.querySelectorAll('#entryTableBody tr').forEach(tr => {
                const inputs = tr.querySelectorAll('input');
                rows.push({ desc: inputs[0].value, receipt: inputs[1].value, issue: inputs[2].value, sale: inputs[3].value, remarks: inputs[4].value });
            });
            localStorage.setItem(STORAGE_KEY, JSON.stringify(rows));
        }

        function loadDynamicTable() {
            if (isGenerated) { saveDynamicTable(); return; }
            const storedData = localStorage.getItem(STORAGE_KEY);
            if (storedData) {
                const data = JSON.parse(storedData);
                if (data && data.length > 0) {
                    const tbody = document.getElementById('entryTableBody'); tbody.innerHTML = '';
                    data.forEach(item => {
                        const row = document.createElement('tr');
                        row.innerHTML = `<td><input type="text" name="desc[]" value="${item.desc}"></td><td><input type="number" name="receipt[]" value="${item.receipt}"></td><td><input type="number" name="issue[]" value="${item.issue}"></td><td><input type="number" name="sale[]" value="${item.sale}"></td><td><input type="text" name="remarks[]" value="${item.remarks}"></td><td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td>`;
                        tbody.appendChild(row);
                    });
                }
            }
        }

        function addRow() {
            const tbody = document.querySelector('#entryTableBody');
            const row = document.createElement('tr');
            row.innerHTML = `<td><input type="text" name="desc[]" placeholder="Item name"></td><td><input type="number" name="receipt[]" value="0"></td><td><input type="number" name="issue[]" value="0"></td><td><input type="number" name="sale[]" value="0"></td><td><input type="text" name="remarks[]" placeholder=""></td><td><button type="button" class="btn-red btn-small" onclick="deleteRow(this)">Delete</button></td>`;
            tbody.appendChild(row); saveDynamicTable();
        }

        function deleteRow(btn) { btn.closest('tr').remove(); saveDynamicTable(); }
        document.getElementById('dynamicTable').addEventListener('input', function() { saveDynamicTable(); });

        // --- TANK CALCULATION ---
        function calc(row, cls) {
            let f = row.querySelector('.' + cls + 'sf').value || 0;
            let i = row.querySelector('.' + cls + 'si').value || 0;
            let s = row.querySelector('.' + cls + 'ss').value || 0;
            let p = row.querySelector('.' + cls + 'sp').value || 0;
            let inch = (f * 12) + Number(i) + (s / 8);
            let q = (inch * p).toFixed(2);
            row.querySelector('.' + cls + 'sq').innerText = q;
            sum(cls);
            if (cls === 'i') { calculateIssuedDipDifference(); }
        }

        function sum(cls) {
            let t = 0;
            document.querySelectorAll('.' + cls + 'sq').forEach(e => { t += Number(e.innerText || 0); });
            document.getElementById(cls + 'Total').innerText = t.toFixed(2);
        }

        // --- SMART INITIAL DIP CALCULATOR ---
        function calculateIssuedDipDifference() {
            let allRows = Array.from(document.querySelectorAll('#issuedTable tbody tr'));
            let totalInitialDipOil = 0;
            let processedIndices = new Set();

            // Helper to get row quantity
            const getRowQty = (r) => {
                let rf = r.querySelector('.isf').value || 0;
                let ri = r.querySelector('.isi').value || 0;
                let rs = r.querySelector('.iss').value || 0;
                let rp = r.querySelector('.isp').value || 0;
                return ((rf * 12) + Number(ri) + (rs / 8)) * rp;
            };

            // Loop through all rows to find 1st Dips
            allRows.forEach((row, index) => {
                if (processedIndices.has(index)) return;

                let nameInput = row.querySelector('input[name="tank_name_i[]"]');
                let labelInput = row.querySelector('input[name="tank_label_i[]"]');
                
                // Skip if it's the Initial Dil row itself or inputs are missing
                if (!nameInput || !labelInput || nameInput.value.trim() === 'Initial Dil') return;

                let tankName = nameInput.value.trim();
                let label = labelInput.value.toLowerCase().trim();

                // If we found a '1st' dip, let's look for its '2nd' partner
                if (label === '1st') {
                    let partnerIndex = -1;
                    // Scan forward (or anywhere) for the matching 2nd dip
                    for (let j = 0; j < allRows.length; j++) {
                        if (index === j || processedIndices.has(j)) continue;
                        let pName = allRows[j].querySelector('input[name="tank_name_i[]"]');
                        let pLabel = allRows[j].querySelector('input[name="tank_label_i[]"]');
                        if (pName && pLabel && pName.value.trim() === tankName && pLabel.value.toLowerCase().trim() === '2nd') {
                            partnerIndex = j;
                            break;
                        }
                    }

                    if (partnerIndex !== -1) {
                        // Mark both as processed so we don't count them again
                        processedIndices.add(index);
                        processedIndices.add(partnerIndex);

                        let qty1 = getRowQty(allRows[index]);
                        let qty2 = getRowQty(allRows[partnerIndex]);
                        
                        // Difference = 1st - 2nd (Consumption)
                        totalInitialDipOil += (qty1 - qty2);
                    }
                }
            });

            // Now update the "Initial Dil" row
            let initialRow = null;
            allRows.forEach(r => {
                let nameInput = r.querySelector('input[name="tank_name_i[]"]');
                if (nameInput && nameInput.value.trim() === 'Initial Dil') {
                    initialRow = r;
                }
            });

            if (initialRow) {
                let qtyCell = initialRow.querySelector('.isq');
                if (qtyCell) {
                    // Update the display
                    qtyCell.innerText = totalInitialDipOil.toFixed(2);
                    qtyCell.classList.add('live-calc-highlight');
                    
                    // CRITICAL: Re-calculate the total sum so the footer updates
                    sum('i');
                }
            }
        }

        function addTankRow(tblId, cls) {
            let t = document.getElementById(tblId);
            let r = t.insertRow();
            r.innerHTML = `<td><input type="text" name="tank_name_${cls}[]" class="tank-name-input" placeholder="Name"></td><td><input class="${cls}sf" name="${cls}f[]" oninput="calc(this.parentNode.parentNode,'${cls}')"></td><td><input class="${cls}si" name="${cls}i[]" oninput="calc(this.parentNode.parentNode,'${cls}')"></td><td><input class="${cls}ss" name="${cls}s[]" oninput="calc(this.parentNode.parentNode,'${cls}')"></td><td><input class="${cls}sp" name="${cls}p[]" value="10" oninput="calc(this.parentNode.parentNode,'${cls}')"></td><td class="${cls}sq total">0</td><td><button type="button" class="btn-red btn-small" onclick="this.closest('tr').remove()">X</button></td>`;
        }

        function addIssuedTankRow() {
            let t = document.getElementById('issuedTable');
            let r = t.insertRow();
            r.innerHTML = `<td style="width: 25%;"><input type="text" name="tank_name_i[]" placeholder="Tank Name" class="tank-name"></td><td class="small-cell"><input type="text" name="tank_label_i[]" placeholder="1st"></td><td><input class="isf" name="if[]" oninput="calc(this.parentNode.parentNode,'i')"></td><td><input class="isi" name="ii[]" oninput="calc(this.parentNode.parentNode,'i')"></td><td><input class="iss" name="is[]" oninput="calc(this.parentNode.parentNode,'i')"></td><td><input class="isp" name="ip[]" value="10" oninput="calc(this.parentNode.parentNode,'i')"></td><td class="isq total">0</td><td><button type="button" class="action-btn" onclick="this.closest('tr').remove()">✕</button></td>`;
        }
        
        window.onload = function() {
            loadDynamicTable();
            document.querySelectorAll('#stockTable tbody tr').forEach(r => calc(r, 's'));
            document.querySelectorAll('#issuedTable tbody tr').forEach(r => calc(r, 'i'));
        };
    </script>
</body>
</html>