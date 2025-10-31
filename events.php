<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "csso";
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ===== CREATE EVENT =====
if (isset($_POST['create_event'])) {
  $event_Name = $_POST['event_Name'];
  $event_Date = $_POST['event_Date'];
  $location   = $_POST['location'];

  $stmt = $conn->prepare("INSERT INTO event (event_Name, event_Date, location) VALUES (?, ?, ?)");
  $stmt->bind_param("sss", $event_Name, $event_Date, $location);
  $stmt->execute();
  $stmt->close();
  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
      icon: 'success',
      title: 'Event Added!',
      text: 'Your new event has been successfully created.',
      showConfirmButton: false,
      timer: 1500
    }).then(() => window.location.href='events.php');
  });
  </script>";
  exit();
}

// ===== UPDATE EVENT =====
if (isset($_POST['update_event'])) {
  $original_name = $_POST['original_name'];
  $event_Name = $_POST['event_Name'];
  $event_Date = $_POST['event_Date'];
  $location   = $_POST['location'];

  $stmt = $conn->prepare("UPDATE event SET event_Name=?, event_Date=?, location=? WHERE event_Name=?");
  $stmt->bind_param("ssss", $event_Name, $event_Date, $location, $original_name);
  $stmt->execute();
  $stmt->close();
  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
      icon: 'success',
      title: 'Event Updated!',
      text: 'Changes have been saved successfully.',
      showConfirmButton: false,
      timer: 1500
    }).then(() => window.location.href='events.php');
  });
  </script>";
  exit();
}

// ===== DELETE EVENT =====
if (isset($_GET['delete_name'])) {
  $event_Name = $_GET['delete_name'];
  $stmt = $conn->prepare("DELETE FROM event WHERE event_Name = ?");
  $stmt->bind_param("s", $event_Name);
  $stmt->execute();
  $stmt->close();
  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    Swal.fire({
      icon: 'success',
      title: 'Event Deleted!',
      text: 'The event has been removed successfully.',
      showConfirmButton: false,
      timer: 1500
    }).then(() => window.location.href='events.php');
  });
  </script>";
  exit();
}

// ===== SEARCH FEATURE =====
$search = "";
if (isset($_GET['search'])) {
  $search = trim($_GET['search']);
  $query = $conn->prepare("SELECT * FROM event WHERE event_Name LIKE ? OR location LIKE ?");
  $like = "%$search%";
  $query->bind_param("ss", $like, $like);
  $query->execute();
  $events = $query->get_result();
} else {
  $events = $conn->query("SELECT * FROM event");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Events List</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body {
  background-color: #f5f7fa;
  font-family: 'Segoe UI', sans-serif;
}
.container {
  margin-top: 40px;
}
h4 {
  color: #2563eb;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 8px;
}
.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 15px;
}
.search-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}
.search-wrapper {
  position: relative;
}
.search-wrapper input {
  width: 280px;
  padding-left: 35px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
.search-wrapper i {
  position: absolute;
  top: 10px;
  left: 10px;
  color: #2563eb;
}
.btn-success {
  background-color: #22c55e;
  border: none;
}
.btn-success:hover {
  background-color: #16a34a;
}
.table {
  border-radius: 8px;
  overflow: hidden;
}
.table thead th {
  background-color: #2563eb !important;
  color: white !important;
  text-align: center !important;
  vertical-align: middle !important;
}
.table tbody td {
  text-align: center;
  vertical-align: middle;
  background-color: white;
}
.action-buttons {
  display: flex;
  justify-content: center;
  gap: 10px;
}
.action-btn {
  border: none;
  color: white;
  padding: 8px 10px;
  border-radius: 6px;
  width: 35px;
  height: 35px;
  cursor: pointer;
  transition: 0.2s ease;
}
.action-btn.edit { background-color: #facc15; }
.action-btn.delete { background-color: #ef4444; }
.action-btn:hover { opacity: 0.8; }
.modal-header { background-color: #2563eb; color: white; }
</style>
</head>

<body>
<div class="container">
  <div class="header-section">
    <div>
      <h4><i class="fas fa-calendar-alt"></i> Events List</h4>
      <div class="search-controls mt-2">
        <form method="GET" class="d-flex align-items-center gap-2">
          <div class="search-wrapper">
            <i class="fas fa-search"></i>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search event..." class="form-control">
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-sync-alt"></i></button>
        </form>
      </div>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal">+ Add Event</button>
  </div>

  <div class="card shadow-sm">
    <table class="table table-bordered mb-0">
      <thead>
        <tr>
          <th>Event Name</th>
          <th>Event Date</th>
          <th>Location</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($events->num_rows > 0): ?>
          <?php while ($row = $events->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['event_Name']) ?></td>
              <td><?= htmlspecialchars($row['event_Date']) ?></td>
              <td><?= htmlspecialchars($row['location']) ?></td>
              <td>
                <div class="action-buttons">
                  <button class="action-btn edit"
                          data-name="<?= htmlspecialchars($row['event_Name']) ?>"
                          data-date="<?= htmlspecialchars($row['event_Date']) ?>"
                          data-location="<?= htmlspecialchars($row['location']) ?>">
                    <i class="fas fa-pen"></i>
                  </button>
                  <button class="action-btn delete"
                          data-name="<?= htmlspecialchars($row['event_Name']) ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="4">No events found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Add New Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Event Name</label>
            <input type="text" name="event_Name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Event Date</label>
            <input type="date" name="event_Date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="create_event" class="btn btn-primary">Create</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT EVENT MODAL -->
<div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary">  <!-- Blue -->
          <h5 class="modal-title">Edit Event</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="original_name" id="original_name">
          <div class="mb-3">
            <label class="form-label">Event Name</label>
            <input type="text" name="event_Name" id="edit_event_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Event Date</label>
            <input type="date" name="event_Date" id="edit_event_date" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Location</label>
            <input type="text" name="location" id="edit_location" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_event" class="btn btn-success">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const deleteButtons = document.querySelectorAll(".delete");
  const editButtons = document.querySelectorAll(".edit");

  // DELETE
  deleteButtons.forEach(button => {
    button.addEventListener("click", () => {
      const name = button.dataset.name;
      Swal.fire({
        title: "Delete Event?",
        text: "Are you sure you want to delete '" + name + "'?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
      }).then(result => {
        if (result.isConfirmed) {
          window.location.href = "events.php?delete_name=" + encodeURIComponent(name);
        }
      });
    });
  });

  // EDIT
  editButtons.forEach(button => {
    button.addEventListener("click", () => {
      const name = button.dataset.name;
      const date = button.dataset.date;
      const location = button.dataset.location;
      document.getElementById("edit_event_name").value = name;
      document.getElementById("edit_event_date").value = date;
      document.getElementById("edit_location").value = location;
      document.getElementById("original_name").value = name;
      new bootstrap.Modal(document.getElementById("editEventModal")).show();
    });
  });
});
</script>
</body>
</html>