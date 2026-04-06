<?php
//add_subject.php
include "includes/header.php";
include "config.php";

// Insert new syllabus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $type = $_POST['exam_type'];
    $data_selection = $_POST['data_selection'];
    $institute_id = $_POST['institute_id'];

    $stmt = $conn->prepare("INSERT INTO syllabus (syllabus_code, name, exam_type, data_selection, institute_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("ssssi", $code, $name, $type, $data_selection, $institute_id);
    $stmt->execute();
    header("Location: add_subject.php?added=1");
    exit;
}

// Update syllabus by ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
  $id              = intval($_POST['syllabus_id']);
  $name            = $_POST['name'];
  $type            = $_POST['exam_type'];
  $data_selection  = $_POST['data_selection'];
  $institute_id    = intval($_POST['institute_id']);

  $stmt = $conn->prepare("
      UPDATE syllabus 
         SET name           = ?,
             exam_type      = ?,
             data_selection = ?,
             institute_id   = ?
       WHERE id = ?
  ");
  $stmt->bind_param("sssii",
      $name,
      $type,
      $data_selection,
      $institute_id,
      $id
  );
  $stmt->execute();
  $stmt->close();

  header("Location: add_subject.php?updated=1");
  exit;
}


// Toggle active/inactive
if (isset($_GET['toggle']) && isset($_GET['code'])) {
    $newStatus = $_GET['toggle'] === 'active' ? 'active' : 'inactive';
    $code = $_GET['code'];
    $stmt = $conn->prepare("UPDATE syllabus SET status = ? WHERE syllabus_code = ?");
    $stmt->bind_param("ss", $newStatus, $code);
    $stmt->execute();
    header("Location: add_subject.php");
    exit;
}

// Sorting
$sort = $_GET['sort'] ?? 'asc';
$nextSort = $sort === 'asc' ? 'desc' : 'asc';
$arrow = $sort === 'asc' ? '↑' : '↓';
$order = strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC';

// Search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchSQL = $search ? "AND (s.syllabus_code LIKE '%$search%' OR s.name LIKE '%$search%')" : '';

// Fetch data
$institutes = $conn->query("SELECT * FROM institutes");

$subjects = $conn->query("
    SELECT s.*, i.name as institute_name 
    FROM syllabus s 
    JOIN institutes i ON s.institute_id = i.id
    WHERE 1=1 $searchSQL
    ORDER BY s.exam_type $order, s.syllabus_code ASC
");
?>

<div class="container my-5">

  <?php if (isset($_GET['added'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      Syllabus added successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php elseif (isset($_GET['updated'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      Syllabus updated successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Add Syllabus Card -->
  <div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0">Add New Syllabus</h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <input type="hidden" name="add_subject" value="1">
        <div class="mb-3">
          <label class="form-label">Syllabus Code</label>
          <input type="text" class="form-control" name="code" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Syllabus Name</label>
          <input type="text" class="form-control" name="name" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Exam Type</label>
          <select class="form-select" name="exam_type" required>
            <option value="GCE">GCE</option>
            <option value="IGCSE/GCSE">IGCSE/GCSE</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Data Selection</label>
          <select class="form-select" name="data_selection">
            <option value="single">Single</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Exam Board</label>
          <select class="form-select" name="institute_id" required>
            <?php while ($row = $institutes->fetch_assoc()): ?>
              <option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <button class="btn btn-success" type="submit"><i class="bi bi-plus-circle me-1"></i> Add Syllabus</button>
      </form>
    </div>
  </div>

  <!-- Existing Syllabuses Card -->
  <!-- Existing Syllabuses Card -->
<div class="card shadow-sm">
  <div class="card-header bg-secondary text-white">
    <h5 class="mb-0">Existing Syllabuses</h5>
  </div>
  <div class="card-body p-3">
    <!-- Search input -->
    <div class="input-group mb-3">
      <input type="text" id="searchInput" class="form-control" placeholder="Search by code or name" value="<?= htmlspecialchars($search) ?>">
    </div>

    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>Syllabus Code</th>
          <th>Name</th>
          <th>
            <a href="?sort=<?= $nextSort ?>" class="text-decoration-none text-dark">
              Exam Type <?= $arrow ?>
            </a>
          </th>
          <th>Exam Board</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="syllabusTable">
        <?php
          $syllabusJS = [];
          while ($row = $subjects->fetch_assoc()):
            $syllabusJS[] = $row;
        ?>
        <tr>
          <td><?= $row['syllabus_code'] ?></td>
          <td><?= $row['name'] ?></td>
          <td><?= $row['exam_type'] ?></td>
          <td><?= $row['institute_name'] ?></td>
          <td>
            <span class="badge <?= $row['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>">
              <?= ucfirst($row['status']) ?>
            </span>
          </td>
          <td>
            <a href="add_option_code.php?syllabus_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary">Add Option</a>
            <?php if ($row['status'] === 'active'): ?>
              <a href="?toggle=inactive&code=<?= $row['syllabus_code'] ?>" class="btn btn-sm btn-outline-secondary">Disable</a>
            <?php else: ?>
              <a href="?toggle=active&code=<?= $row['syllabus_code'] ?>" class="btn btn-sm btn-outline-success">Enable</a>
            <?php endif; ?>
            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['syllabus_code'] ?>">Edit</button>
            <a href="delete_subject.php?syllabus=<?= $row['syllabus_code'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this syllabus?')">Delete</a>
          </td>
        </tr>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal<?= $row['syllabus_code'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST">
                <div class="modal-header">
                  <h5 class="modal-title">Edit Syllabus</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="syllabus_id" value="<?= $row['id'] ?>">
                  <input type="hidden" name="edit_subject" value="1">
                  <div class="mb-3">
                    <label class="form-label">Syllabus Name</label>
                    <input type="text" name="name" class="form-control" value="<?= $row['name'] ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Exam Type</label>
                    <select name="exam_type" class="form-select">
                      <option value="GCE" <?= $row['exam_type'] == 'GCE' ? 'selected' : '' ?>>GCE</option>
                      <option value="IGCSE/GCSE" <?= $row['exam_type'] == 'IGCSE/GCSE' ? 'selected' : '' ?>>IGCSE/GCSE</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Data Selection</label>
                    <select name="data_selection" class="form-select">
                      <option value="single" <?= $row['data_selection'] == 'single' ? 'selected' : '' ?>>Single</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Exam Board</label>
                    <select name="institute_id" class="form-select">
                      <?php
                      $inst2 = $conn->query("SELECT * FROM institutes");
                      while ($i = $inst2->fetch_assoc()):
                      ?>
                        <option value="<?= $i['id'] ?>" <?= $row['institute_id'] == $i['id'] ? 'selected' : '' ?>>
                          <?= $i['name'] ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-primary">Save Changes</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- JS for live search -->
<script>
  const searchInput = document.getElementById("searchInput");
  const syllabusTable = document.getElementById("syllabusTable");
  const allRows = Array.from(syllabusTable.querySelectorAll("tr"));

  function filterTable() {
    const term = searchInput.value.toLowerCase();
    allRows.forEach(row => {
      const code = row.children[0].textContent.toLowerCase();
      const name = row.children[1].textContent.toLowerCase();
      row.style.display = (code.includes(term) || name.includes(term)) ? "" : "none";
    });
  }

  searchInput.addEventListener("input", filterTable);
  searchInput.addEventListener("keypress", e => {
    if (e.key === "Enter") {
      e.preventDefault();
      filterTable();
    }
  });
</script>


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

