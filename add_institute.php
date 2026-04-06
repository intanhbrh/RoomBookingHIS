<?php
include "includes/header.php";
include "config.php";

$success = false;
$message = '';

// ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_institute'])) {
    $name = trim($_POST['name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("INSERT INTO institutes (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $success = true;
            $message = "Exam Board added successfully!";
        }
    }
}

// EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_institute'])) {
    $id = intval($_POST['edit_id']);
    $name = trim($_POST['edit_name']);
    if (!empty($name)) {
        $stmt = $conn->prepare("UPDATE institutes SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if ($stmt->execute()) {
            $success = true;
            $message = "Exam Board updated successfully!";
        }
    }
}

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_institute'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM institutes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = true;
        $message = "Exam Board deleted successfully!";
    }
}

// Fetch all institutes
$institutes = $conn->query("SELECT id, name FROM institutes ORDER BY name ASC");
?>

<div class="row justify-content-center">
  <div class="col-md-8 col-lg-6">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
          <i class="bi bi-building-add me-2"></i> Add New Exam Board
        </h5>
      </div>
      <div class="card-body">
        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <form method="POST">
          <input type="hidden" name="add_institute" value="1">
          <div class="mb-3">
            <label class="form-label">Exam Board Name</label>
            <input type="text" class="form-control" name="name" placeholder="Enter Exam Board Name" required>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add Exam Board
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<hr>
<h5 class="mt-4"><i class="bi bi-list-ul me-2"></i> Existing Exam Boards</h5>

<?php if ($institutes->num_rows > 0): ?>
  <div class="table-responsive mt-3">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th scope="col">#</th>
          <th scope="col">Exam Board Name</th>
          <th scope="col">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $counter = 1;
        $modalHTML = ""; // store modals separately
        while ($row = $institutes->fetch_assoc()):
        ?>
          <tr>
            <td><?= $counter++ ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td>
            <div class="d-flex align-items-center">
  <!-- Edit -->
  <button type="button" class="btn btn-warning btn-sm me-2"
          data-bs-toggle="modal" data-bs-target="#editModal<?= $row['id'] ?>">
    Edit
  </button>

  <!-- Delete -->
  <form method="POST" onsubmit="return confirm('Are you sure you want to delete this exam board?');" class="d-inline">
    <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
    <button type="submit  " name="delete_institute" class="btn btn-danger btn-sm">Delete</button>
  </form>
</div>
  
            </td>
          </tr>

          <?php
          // Build modal HTML outside of <tr> to avoid nesting issues
          $modalHTML .= '
          <div class="modal fade" id="editModal' . $row['id'] . '" tabindex="-1" aria-labelledby="editModalLabel' . $row['id'] . '" aria-hidden="true">
            <div class="modal-dialog">
              <form method="POST" class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="editModalLabel' . $row['id'] . '">Edit Exam Board</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="edit_id" value="' . $row['id'] . '">
                  <div class="mb-3">
                    <label class="form-label">Exam Board Name</label>
                    <input type="text" class="form-control" name="edit_name" value="' . htmlspecialchars($row['name']) . '" required>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="edit_institute" class="btn btn-primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>';
          ?>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Output all modals after the table -->
  <?= $modalHTML ?>
<?php else: ?>
  <p class="text-muted mt-3">No exam boards added yet.</p>
<?php endif; ?>

<!-- Bootstrap JS (required for modal) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
