<?php
// add_option_code.php
include "includes/header.php";
include "config.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get selected syllabus from query
// grab the PK from the URL
$syllabus_id = isset($_GET['syllabus_id']) 
                ? intval($_GET['syllabus_id']) 
                : 0;
if( $syllabus_id <= 0 ) {
  die("Invalid syllabus");
}

// now fetch both the code and the name
$stmt = $conn->prepare(
  "SELECT syllabus_code, name 
     FROM syllabus 
    WHERE id = ?"
);
$stmt->bind_param("i", $syllabus_id);
$stmt->execute();
$stmt->bind_result($selectedSyllabusCode, $selectedSyllabusName);
if( ! $stmt->fetch() ) {
  die("Syllabus not found");
}
$stmt->close();


// =======================
// 1) Handle deletion
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_option'])) {
    $optionId = intval($_POST['option_id']);
    $delStmt = $conn->prepare("DELETE FROM option_code WHERE id = ?");
    $delStmt->bind_param("i", $optionId);
    if ($delStmt->execute()) {
        echo "<div class='alert alert-success'>Option code deleted successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error deleting option code: " . htmlspecialchars($delStmt->error) . "</div>";
    }
    $delStmt->close();
}

// =======================
// 2) Handle update
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_option'])) {
    $optionId   = intval($_POST['option_id']);
    $newCode    = trim($_POST['code']);
    $newFees    = floatval($_POST['fees']);
    $updateStmt = $conn->prepare("UPDATE option_code SET code = ?, fees = ? WHERE id = ?");
    $updateStmt->bind_param("sdi", $newCode, $newFees, $optionId);
    if ($updateStmt->execute()) {
        echo "<div class='alert alert-success'>Option code updated successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error updating option code: " . htmlspecialchars($updateStmt->error) . "</div>";
    }
    $updateStmt->close();
}

// =======================
// 3) Handle adding a new option code
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_option_code'])) {
  $code = trim($_POST['code']);
  $fees = floatval($_POST['fees']);

  // — check duplicate against syllabus_id instead of syllabus_code
  $check = $conn->prepare("
    SELECT id 
      FROM option_code 
     WHERE syllabus_id = ? 
       AND code = ?
  ");
  $check->bind_param("is", $syllabus_id, $code);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
      echo "<div class='alert alert-danger'>This option code already exists for this syllabus.</div>";
  } else {
      // — include syllabus_code in the INSERT
      $insert = $conn->prepare("
        INSERT INTO option_code 
          (syllabus_id, syllabus_code, code, fees) 
        VALUES (?, ?, ?, ?)
      ");
      $insert->bind_param(
        "issd",
        $syllabus_id,
        $selectedSyllabusCode, // fetched at top of script
        $code,
        $fees
      );
      if ($insert->execute()) {
          echo "<div class='alert alert-success'>Option code added successfully!</div>";
      } else {
          echo "<div class='alert alert-danger'>Error: " 
             . htmlspecialchars($insert->error) 
             . "</div>";
      }
      $insert->close();
  }
  $check->close();
}


// =======================
// 4) Handle adding an offer
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    $offer_code  = trim($_POST['offer_code']);
    $description = trim($_POST['offer_description']);

    $stmt = $conn->prepare("INSERT INTO offers (offer_code, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $offer_code, $description);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Offer added successfully!</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($stmt->error) . "</div>";
    }
    $stmt->close();
}

// =======================
// 5) Fetch syllabus list
// =======================
$syllabusList = $conn->query("SELECT * FROM syllabus");

// Fetch selected syllabus name
$syllabus_id = isset($_GET['syllabus_id']) ? intval($_GET['syllabus_id']) : 0;
if( $syllabus_id <= 0 ) {
  die("Invalid syllabus");
}

// fetch both code & name
$stmt = $conn->prepare(
  "SELECT syllabus_code, name 
     FROM syllabus 
    WHERE id = ?"
);
$stmt->bind_param("i", $syllabus_id);
$stmt->execute();
$stmt->bind_result($selectedSyllabusCode, $selectedSyllabusName);
if( ! $stmt->fetch() ) {
  die("Syllabus not found");
}
$stmt->close();

// =======================
// 6) Fetch an option to edit (if requested)
// =======================
$editOption = null;
if (isset($_GET['edit_id'])) {
    $editId   = intval($_GET['edit_id']);
    $editStmt = $conn->prepare("SELECT id, code, fees FROM option_code WHERE id = ? AND syllabus_code = ?");
    $editStmt->bind_param("is", $editId, $selectedSyllabusCode);
    $editStmt->execute();
    $res = $editStmt->get_result();
    if ($res && $res->num_rows === 1) {
        $editOption = $res->fetch_assoc();
    }
    $editStmt->close();
}

// =======================
// 7) Fetch existing option codes
// =======================
$optionCodes = [];
if (!empty($selectedSyllabusCode)) {
  $optQ = $conn->prepare(
    "SELECT * 
       FROM option_code 
      WHERE syllabus_id = ?"
  );
  $optQ->bind_param("i", $syllabus_id);
  
    $optQ->execute();
    $optionCodes = $optQ->get_result();
    $optQ->close();
}
?>

<h4>Add Option Code (Package) for <strong><?= htmlspecialchars($selectedSyllabusCode) ?> – <?= htmlspecialchars($selectedSyllabusName) ?></strong></h4>

<?php if ($editOption): ?>
  <hr>
  <h4>Edit Option Code</h4>
  <form method="POST">
    <input type="hidden" name="update_option" value="1">
    <input type="hidden" name="option_id"    value="<?= $editOption['id'] ?>">

    <div class="mb-3">
      <label>Option Code</label>
      <input type="text" name="code" class="form-control" required value="<?= htmlspecialchars($editOption['code']) ?>">
    </div>

    <div class="mb-3">
      <label>Fees</label>
      <input type="number" step="0.01" name="fees" class="form-control" required value="<?= htmlspecialchars($editOption['fees']) ?>">
    </div>

    <button type="submit" class="btn btn-success">Update Option Code</button>
    <a href="add_option_code.php?syllabus_id=<?= $syllabus_id ?>"class="btn btn-secondary">Cancel</a>
  </form>
  <hr>
<?php endif; ?>

<form method="POST">
  <input type="hidden" name="add_option_code" value="1">
  <input type="hidden" name="syllabus_code"   value="<?= htmlspecialchars($selectedSyllabusCode) ?>">

  <div class="mb-3">
    <label>Option Code</label>
    <input type="text" name="code" class="form-control" required>
  </div>
  <div class="mb-3">
    <label>Fees</label>
    <input type="number" step="0.01" name="fees" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary">Add Option Code</button>
</form>

<?php if ($optionCodes && $optionCodes->num_rows > 0): ?>
  <hr>
  <h4>Existing Options for <?= htmlspecialchars($selectedSyllabusCode) ?></h4>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Option Code</th>
        <th>Fees (RM)</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($opt = $optionCodes->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($opt['code']) ?></td>
          <td><?= number_format($opt['fees'], 2) ?></td>
          <td>
           <!-- Manage Offers button -->
            <a href="offer.php?option_code_id=<?= $opt['id'] ?>&syllabus_id=<?= $syllabus_id ?>"
              class="btn btn-sm btn-outline-primary me-1">Manage Offers</a>

          <!-- Edit button -->
            <a href="add_option_code.php?syllabus_id=<?= $syllabus_id ?>&edit_id=<?= $opt['id'] ?>"
            class="btn btn-sm btn-warning">Edit</a>

            <!-- Delete form/button -->
            <form method="POST" style="display:inline-block;" onsubmit="return confirm('Delete this option code?');">
              <input type="hidden" name="delete_option" value="1">
              <input type="hidden" name="option_id"    value="<?= $opt['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
<?php else: ?>
  <p class="text-muted">No option codes yet for this syllabus.</p>
<?php endif; ?>
<a href="add_subject.php" class="btn btn-primary mt-3">← Back to List Syllabus</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
