<?php
include 'db.php';

if (isset($_POST['save'])) {
    $conn->query("
    INSERT INTO production_reports (company_id,report_date,production_qty,remarks)
    VALUES ('$_POST[company]','$_POST[date]','$_POST[qty]','$_POST[remarks]')
    ");
    header("Location: report_view.php?id=".$conn->insert_id);
}
$c=$conn->query("SELECT id,name FROM companies WHERE status='active'");
?>

<form method="POST">
<select name="company">
<?php while($r=$c->fetch_assoc()){ ?>
<option value="<?= $r['id'] ?>"><?= $r['name'] ?></option>
<?php } ?>
</select>

<input type="date" name="date" required>
<input type="number" name="qty" placeholder="Production Qty">
<textarea name="remarks"></textarea>
<button name="save">Save Report</button>
</form>
