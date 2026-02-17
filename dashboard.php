<?php
session_start();
include 'db.php';
if (!is_dir('uploads')) {
    mkdir('uploads', 0777, true);
}
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

/* ================= ADD COMPANY ================= */
if (isset($_POST['add'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $created_date = trim($_POST['created_date'] ?? date('Y-m-d'));
    $logo = "";
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Company name is required!']);
        exit;
    }
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required!']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address!']);
        exit;
    }
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required!']);
        exit;
    }
    if (!preg_match("/^[\d\s\-\+\(\)]+$/", $phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number can only contain digits, spaces, +, -, (, )']);
        exit;
    }
    if (empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Address is required!']);
        exit;
    }
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo = time() . "_" . basename($_FILES['logo']['name']);
        $upload_path = "uploads/" . $logo;
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Logo upload failed!']);
            exit;
        }
    }
    $check = $conn->prepare("SELECT id FROM companies WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => "This email is already registered!"]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO companies (name, email, phone, address, created_date, logo, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssss", $name, $email, $phone, $address, $created_date, $logo);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Company added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error occurred!']);
    }
    $stmt->close();
    $check->close();
    exit;
}

/* ================= UPDATE COMPANY ================= */
if (isset($_POST['update'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $created_date = trim($_POST['created_date'] ?? date('Y-m-d'));
    $logo = $_POST['old_logo'] ?? '';
    if (empty($name) || empty($email) || empty($phone) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required!']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address!']);
        exit;
    }
    if (!preg_match("/^[\d\s\-\+\(\)]+$/", $phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number can only contain digits, spaces, +, -, (, )']);
        exit;
    }
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo = time() . "_" . basename($_FILES['logo']['name']);
        $upload_path = "uploads/" . $logo;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
            if (!empty($_POST['old_logo'])) {
                @unlink("uploads/" . $_POST['old_logo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Logo upload failed!']);
            exit;
        }
    }
    $check = $conn->prepare("SELECT id FROM companies WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'This email is already used by another company!']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE companies SET name=?, email=?, phone=?, address=?, created_date=?, logo=? WHERE id=?");
    $stmt->bind_param("ssssssi", $name, $email, $phone, $address, $created_date, $logo, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Company updated successfully!', 'clearEdit' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Update failed!']);
    }
    $stmt->close();
    $check->close();
    exit;
}

/* ================= DELETE / BLOCK / UNBLOCK ================= */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    if ($action === 'delete') {
        $res = $conn->query("SELECT logo FROM companies WHERE id = $id");
        if ($row = $res->fetch_assoc()) {
            @unlink("uploads/" . $row['logo']);
        }
        $conn->query("DELETE FROM companies WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Company deleted permanently!']);
    } elseif ($action === 'block') {
        $conn->query("UPDATE companies SET status = 'blocked' WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Company blocked!']);
    } elseif ($action === 'unblock') {
        $conn->query("UPDATE companies SET status = 'active' WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Company unblocked!']);
    }
    exit;
}

/* ================= EDIT MODE ================= */
$editData = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

/* ================= FETCH ALL COMPANIES WITH DATE ================= */
$stmt = $conn->prepare("SELECT *, DATE(created_at) as created_date FROM companies ORDER BY id DESC");
$stmt->execute();
$companies_result = $stmt->get_result();
$total_companies = $companies_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background: #f8f9fa; }
        .card { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; color: #495057; }
        .preview-img { max-width: 200px; border-radius: 8px; margin-top: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .table img { max-width: 80px; border-radius: 6px; transition: transform 0.2s; }
        .table img:hover { transform: scale(1.05); }
        .action-btn { font-size: 0.9rem; }
        .filter-box { background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 30px; }
        mark.bg-warning { background-color: #fff3cd !important; color: #000 !important; padding: 2px 6px; border-radius: 4px; }
        @keyframes blink {
            0%, 100% { border-color: #dc3545; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25); }
            50% { border-color: #ff0000; box-shadow: 0 0 0 0.4rem rgba(255, 0, 0, 0.4); }
        }
        .error-blink { animation: blink 1s ease-in-out 2; }

        /* نیا CSS اضافہ: بٹنوں کو ایک لائن میں رکھنے اور overflow روکنے کے لیے */
        #companiesTable td:last-child {
            white-space: nowrap;          /* بٹن نیچے نہیں جائیں گے */
            min-width: 240px;             /* بٹنوں کے لیے کافی جگہ */
            padding: 8px 6px !important;
        }
        #companiesTable .action-btn {
            font-size: 0.8rem !important;
            padding: 4px 9px !important;
            margin: 2px 1px !important;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Super Admin Dashboard</h4>
                <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
            </div>
            <div class="card-body">
                <h4 class="mb-4"><?= $editData ? "Edit Company" : "Add New Company" ?></h4>
                <form id="companyForm" method="POST" enctype="multipart/form-data" class="row g-4" novalidate>
                    <?php if ($editData): ?>
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                        <input type="hidden" name="old_logo" value="<?= $editData['logo'] ?>">
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label class="form-label">Company Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($editData['name'] ?? '') ?>" placeholder="Enter company name">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editData['email'] ?? '') ?>" placeholder="Enter email address">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>" placeholder="Enter phone number">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($editData['address'] ?? '') ?>" placeholder="Enter full address">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Company Added Date <span class="text-danger">*</span></label>
                        <input type="date" name="created_date" class="form-control" value="<?= htmlspecialchars($editData['created_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Logo (Optional)</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if (!empty($editData['logo'] ?? '')): ?>
                            <img src="uploads/<?= htmlspecialchars($editData['logo']) ?>" class="preview-img d-block mt-3">
                            <small class="text-muted">Current logo (upload new to replace)</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg px-5">
                            <?= $editData ? 'Update Company' : 'Add Company' ?>
                        </button>
                    </div>
                </form>

                <hr class="my-5">

                <!-- فلٹر باکس — بٹن سمیت -->
                <div class="filter-box mb-4">
                    <h5 class="mb-3 text-primary">Search & Filter Companies</h5>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <input type="text" id="liveSearch" class="form-control" placeholder="Search by company name (live)" autocomplete="off">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="fromDate" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <input type="date" id="toDate" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="applyDateFilter" class="btn btn-primary w-100">Apply Filter</button>
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <small class="text-muted">Total: <strong id="totalCount"><?= $total_companies ?></strong></small>
                        <span class="text-info fw-bold" id="filteredCount"></span>
                    </div>
                </div>

                <h4 class="mb-4">All Companies (<span id="displayCount"><?= $total_companies ?></span>)</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="companiesTable">
                        <thead class="table-primary">
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Date Added</th>
                                <th>Status</th>
                                <th>Logo</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = $companies_result->fetch_assoc()): 
                                $createdDate = $row['created_date'] ?? date('Y-m-d');
                            ?>
                            <tr data-name="<?= strtolower(htmlspecialchars($row['name'])) ?>" data-created="<?= $createdDate ?>">
                                <td><?= $no++ ?></td>
                                <td class="company-name"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['address'] ?? '-') ?></td>
                                <td><?= isset($row['created_date']) ? htmlspecialchars($row['created_date']) : htmlspecialchars($row['created_at'] ?? '-') ?></td>
                                <td>
                                    <span class="badge <?= $row['status'] == 'blocked' ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $row['status'] == 'blocked' ? 'Blocked' : 'Active' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['logo']): ?>
                                        <a href="uploads/<?= htmlspecialchars($row['logo']) ?>" class="gallery-item">
                                            <img src="uploads/<?= htmlspecialchars($row['logo']) ?>" alt="Logo">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">No Logo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm action-btn">Edit</a>
                                    <button class="btn btn-danger btn-sm action-btn delete-btn" data-id="<?= $row['id'] ?>">Delete</button>
                                    <?php if($row['status'] == 'active'): ?>
                                        <button class="btn btn-secondary btn-sm action-btn block-btn" data-id="<?= $row['id'] ?>">Block</button>
                                    <?php else: ?>
                                        <button class="btn btn-success btn-sm action-btn unblock-btn" data-id="<?= $row['id'] ?>">Unblock</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($total_companies == 0): ?>
                            <tr id="noResultRow">
                                <td colspan="8" class="text-center text-muted py-4">No companies found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            let currentSearchValue = '';
            let currentFromDate = '';
            let currentToDate = '';

            function applyFilters() {
                let searchValue = currentSearchValue;
                let fromDate = currentFromDate;
                let toDate = currentToDate;
                let visibleRows = 0;
                let total = <?= $total_companies ?>;

                $("#companiesTable tbody tr").each(function() {
                    let row = $(this);
                    if (row.attr('id') === 'noResultRow') return;

                    let nameMatch = true;
                    let dateMatch = true;

                    if (searchValue !== '') {
                        let nameLower = row.data('name') || '';
                        nameMatch = nameLower.includes(searchValue);
                    }

                    let rowDateStr = row.data('created');
                    if (rowDateStr) {
                        let rowDate = new Date(rowDateStr);
                        if (!isNaN(rowDate.getTime())) {
                            if (fromDate !== '' && rowDate < new Date(fromDate)) {
                                dateMatch = false;
                            }
                            if (toDate !== '') {
                                let toDateEnd = new Date(toDate);
                                toDateEnd.setDate(toDateEnd.getDate() + 1);
                                if (rowDate >= toDateEnd) {
                                    dateMatch = false;
                                }
                            }
                        } else {
                            dateMatch = false;
                        }
                    } else {
                        dateMatch = false;
                    }

                    let companyNameCell = row.find('.company-name');
                    let originalName = companyNameCell.text().trim();

                    if (nameMatch && dateMatch) {
                        row.show();
                        visibleRows++;

                        companyNameCell.text(originalName);
                        if (searchValue !== '') {
                            let regex = new RegExp('(' + searchValue.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
                            let highlighted = originalName.replace(regex, '<mark class="bg-warning">$1</mark>');
                            companyNameCell.html(highlighted);
                        }
                    } else {
                        row.hide();
                    }
                });

                if (visibleRows === 0) {
                    if ($('#noResultRow').length === 0) {
                        $('#companiesTable tbody').append('<tr id="noResultRow"><td colspan="8" class="text-center text-muted py-4">No companies found.</td></tr>');
                    }
                } else {
                    $('#noResultRow').remove();
                }

                $('#displayCount').text(visibleRows);
                let info = [];
                if (searchValue) info.push('name');
                if (fromDate || toDate) info.push('date range');
                $('#filteredCount').text(visibleRows < total ? `(${visibleRows} matches${info.length ? ' - ' + info.join(' & ') : ''})` : '');
            }

            $('#liveSearch').on('keyup', function() {
                currentSearchValue = $(this).val().trim().toLowerCase();
                applyFilters();
            });

            $('#applyDateFilter').on('click', function() {
                currentFromDate = $('#fromDate').val();
                currentToDate = $('#toDate').val();
                applyFilters();
            });

            $('#companyForm').on('submit', function(e) {
                e.preventDefault();
                let hasError = false;
                $('.form-control').removeClass('error-blink');

                const name = $('input[name="name"]').val().trim();
                if (name === '') { $('input[name="name"]').addClass('error-blink').focus(); hasError = true; }

                const email = $('input[name="email"]').val().trim();
                if (email === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    $('input[name="email"]').addClass('error-blink').focus();
                    hasError = true;
                }

                const phone = $('input[name="phone"]').val().trim();
                if (phone === '' || !/^[\d\s\-\+\(\)]+$/.test(phone)) {
                    $('input[name="phone"]').addClass('error-blink').focus();
                    hasError = true;
                }

                const address = $('input[name="address"]').val().trim();
                if (address === '') { $('input[name="address"]').addClass('error-blink').focus(); hasError = true; }

                if (hasError) {
                    Toast.fire({ icon: 'error', title: 'Please fill all required fields correctly' });
                    return;
                }

                let formData = new FormData(this);
                const action = $('button[type="submit"]').text().trim().includes('Update') ? 'update' : 'add';
                formData.append(action, '1');

                $.ajax({
                    url: '',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Toast.fire({ icon: 'success', title: res.message });
                            setTimeout(() => {
                                if (res.clearEdit) {
                                    window.location.href = window.location.pathname;
                                } else {
                                    location.reload();
                                }
                            }, 1500);
                        } else {
                            Toast.fire({ icon: 'error', title: res.message });
                        }
                    },
                    error: function() {
                        Toast.fire({ icon: 'error', title: 'Server error!' });
                    }
                });
            });

            $(document).on('click', '.delete-btn, .block-btn, .unblock-btn', function() {
                let btn = $(this);
                let id = btn.data('id');
                let action = btn.hasClass('delete-btn') ? 'delete' : (btn.hasClass('block-btn') ? 'block' : 'unblock');
                let title = action === 'delete' ? 'Delete this company permanently?' :
                            (action === 'block' ? 'Block this company?' : 'Unblock this company?');

                Swal.fire({
                    title: 'Are you sure?',
                    text: title,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.get('', { action: action, id: id }, function(res) {
                            let data = JSON.parse(res);
                            if (data.success) {
                                Toast.fire({ icon: 'success', title: data.message });
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                Toast.fire({ icon: 'error', title: data.message || 'Failed!' });
                            }
                        }).fail(() => {
                            Toast.fire({ icon: 'error', title: 'Server error!' });
                        });
                    }
                });
            });

            $('.gallery-item').magnificPopup({
                type: 'image',
                gallery: { enabled: true },
                zoom: { enabled: true }
            });
        });
    </script>
</body>
</html>