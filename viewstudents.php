<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['usertype'], ['Secretary', 'Treasurer', 'Auditor', 'Social Manager', 'Senator', 'Governor', 'Vice Governor'])) {
    header("Location: ../login.php");
    exit();
}

// ✅ Database Connection
$conn = new mysqli("localhost", "root", "", "csso");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Check for ID
if (!isset($_GET['students_id'])) {
    die("<script>alert('No student ID provided.'); window.location='students.php';</script>");
}

$students_id = intval($_GET['students_id']);

// ✅ Handle Update Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $conn->begin_transaction();

        // 1️⃣ Update student_profile
        $stmt = $conn->prepare("UPDATE student_profile SET 
            FirstName=?, LastName=?, MI=?, Suffix=?, Course=?, YearLevel=?, Section=?, 
            PhoneNumber=?, Gender=?, DOB=?, Age=?, Religion=?, EmailAddress=?, Street=?, 
            Barangay=?, Municipality=?, Province=?, Zipcode=? WHERE students_id=?");
        $stmt->bind_param(
            "ssssssssssssssssssi",
            $_POST['FirstName'], $_POST['LastName'], $_POST['MI'], $_POST['Suffix'],
            $_POST['Course'], $_POST['YearLevel'], $_POST['Section'], $_POST['PhoneNumber'],
            $_POST['Gender'], $_POST['DOB'], $_POST['Age'], $_POST['Religion'],
            $_POST['EmailAddress'], $_POST['Street'], $_POST['Barangay'],
            $_POST['Municipality'], $_POST['Province'], $_POST['Zipcode'], $students_id
        );
        $stmt->execute();

        // 2️⃣ Update family_background
        $stmt2 = $conn->prepare("UPDATE family_background SET 
            father_name=?, father_occupation=?, mother_name=?, mother_occupation=?, phone_number=?, siblings_count=?, 
            guardian_name=?, guardian_occupation=?, contact_number=?, street=?, barangay=?, municipality=?, province=?, zipcode=? 
            WHERE students_id=?");
        $stmt2->bind_param(
            "sssssisissssssi",
            $_POST['father_name'], $_POST['father_occupation'], $_POST['mother_name'], $_POST['mother_occupation'],
            $_POST['phone_number'], $_POST['siblings_count'], $_POST['guardian_name'], $_POST['guardian_occupation'],
            $_POST['contact_number'], $_POST['fam_street'], $_POST['fam_barangay'], $_POST['fam_municipality'],
            $_POST['fam_province'], $_POST['fam_zipcode'], $students_id
        );
        $stmt2->execute();

        // 3️⃣ Update educational_background
        $stmt3 = $conn->prepare("UPDATE educational_background SET 
            elementary=?, elem_year_grad=?, elem_received=?, 
            junior_high=?, jr_high_grad=?, jr_received=?, 
            senior_high=?, sr_high_grad=?, sr_received=? 
            WHERE students_id=?");
        $stmt3->bind_param(
            "sssssssssi",
            $_POST['elementary'], $_POST['elem_year_grad'], $_POST['elem_received'],
            $_POST['junior_high'], $_POST['jr_high_grad'], $_POST['jr_received'],
            $_POST['senior_high'], $_POST['sr_high_grad'], $_POST['sr_received'], $students_id
        );
        $stmt3->execute();

        $conn->commit();

        // ✅ Sweet Alert Success Message
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Updated Successfully!',
                    text: 'Student information has been updated.',
                    icon: 'success',
                    confirmButtonColor: '#2563eb'
                }).then(() => {
                    window.location = 'students.php';
                });
            });
        </script>";

    } catch (Exception $e) {
        $conn->rollback();
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: '❌ Update Failed!',
                    text: 'Error: " . addslashes($e->getMessage()) . "',
                    icon: 'error',
                    confirmButtonColor: '#dc2626'
                });
            });
        </script>";
    }
    exit;
}

// ✅ Fetch data from all tables
$sql = "
SELECT sp.*, fb.father_name, fb.father_occupation, fb.mother_name, fb.mother_occupation, 
       fb.phone_number AS fam_phone, fb.siblings_count, fb.guardian_name, fb.guardian_occupation, 
       fb.contact_number, fb.street AS fam_street, fb.barangay AS fam_barangay, 
       fb.municipality AS fam_municipality, fb.province AS fam_province, fb.zipcode AS fam_zipcode,
       eb.elementary, eb.elem_year_grad, eb.elem_received, eb.junior_high, eb.jr_high_grad, eb.jr_received, 
       eb.senior_high, eb.sr_high_grad, eb.sr_received
FROM student_profile sp
LEFT JOIN family_background fb ON sp.students_id = fb.students_id
LEFT JOIN educational_background eb ON sp.students_id = eb.students_id
WHERE sp.students_id = '$students_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("<script>alert('Student not found.'); window.location='students.php';</script>");
}
$data = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Student | CSSO</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { font-family:'Segoe UI',sans-serif; background:#f8fafc; margin:0; padding:0; }
.container { width:90%; max-width:900px; margin:30px auto; background:#fff; border-radius:10px; padding:25px; box-shadow:0 2px 10px rgba(0,0,0,0.1);}
h2 { color:#1e3a8a; display:flex; align-items:center; gap:10px; }
h2 i { background:#2563eb; color:#fff; padding:8px; border-radius:8px; }
section { margin-top:30px; }
section h3 { color:#334155; border-left:4px solid #2563eb; padding-left:8px; }
label { display:block; margin-top:10px; font-weight:600; color:#1e293b;}
input, select { width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; margin-top:4px; }
.buttons { display:flex; justify-content:space-between; align-items:center; margin-top:30px; }
button { border:none; padding:10px 18px; border-radius:6px; cursor:pointer; font-weight:600; }
.back-btn { background:#64748b; color:#fff; }
.back-btn:hover { background:#475569; }
.update-btn { background:#2563eb; color:#fff; }
.update-btn:hover { background:#1e40af; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fa fa-pen-to-square"></i> Edit Student Information</h2>

<form method="POST">
    <!-- STUDENT PROFILE -->
    <section>
        <h3>Student Profile</h3>
        <label>Students ID</label><input type="text" name="students_id" value="<?= htmlspecialchars($data['students_id']) ?>" readonly>
        <label>First Name</label><input type="text" name="FirstName" value="<?= htmlspecialchars($data['FirstName']) ?>" required>
        <label>Last Name</label><input type="text" name="LastName" value="<?= htmlspecialchars($data['LastName']) ?>" required>
        <label>Middle Initial</label><input type="text" name="MI" value="<?= htmlspecialchars($data['MI']) ?>">
        <label>Suffix</label><input type="text" name="Suffix" value="<?= htmlspecialchars($data['Suffix']) ?>">
        <label>Course</label>
        <select name="Course" required>
            <option value="BSIT" <?= $data['Course']=='BSIT'?'selected':'' ?>>BSIT</option>
            <option value="BSCS" <?= $data['Course']=='BSCS'?'selected':'' ?>>BSCS</option>
        </select>
        <label>Year Level</label>
        <select name="YearLevel" required>
            <option value="1stYear" <?= $data['YearLevel']=='1stYear'?'selected':'' ?>>1st Year</option>
            <option value="2ndYear" <?= $data['YearLevel']=='2ndYear'?'selected':'' ?>>2nd Year</option>
            <option value="3rdYear" <?= $data['YearLevel']=='3rdYear'?'selected':'' ?>>3rd Year</option>
            <option value="4thYear" <?= $data['YearLevel']=='4thYear'?'selected':'' ?>>4th Year</option>
        </select>
        <label>Section</label><input type="text" name="Section" value="<?= htmlspecialchars($data['Section']) ?>" required>
        <label>Phone Number</label><input type="text" name="PhoneNumber" value="<?= htmlspecialchars($data['PhoneNumber']) ?>">
        <label>Gender</label>
        <select name="Gender" required>
            <option value="Male" <?= $data['Gender']=='Male'?'selected':'' ?>>Male</option>
            <option value="Female" <?= $data['Gender']=='Female'?'selected':'' ?>>Female</option>
            <option value="Other" <?= $data['Gender']=='Other'?'selected':'' ?>>Other</option>
        </select>
        <label>Date of Birth</label><input type="date" name="DOB" value="<?= htmlspecialchars($data['DOB']) ?>">
        <label>Age</label><input type="number" name="Age" value="<?= htmlspecialchars($data['Age']) ?>">
        <label>Religion</label><input type="text" name="Religion" value="<?= htmlspecialchars($data['Religion']) ?>">
        <label>Email Address</label><input type="email" name="EmailAddress" value="<?= htmlspecialchars($data['EmailAddress']) ?>">
        <label>Street</label><input type="text" name="Street" value="<?= htmlspecialchars($data['Street']) ?>">
        <label>Barangay</label><input type="text" name="Barangay" value="<?= htmlspecialchars($data['Barangay']) ?>">
        <label>Municipality</label><input type="text" name="Municipality" value="<?= htmlspecialchars($data['Municipality']) ?>">
        <label>Province</label><input type="text" name="Province" value="<?= htmlspecialchars($data['Province']) ?>">
        <label>Zip Code</label><input type="text" name="Zipcode" value="<?= htmlspecialchars($data['Zipcode']) ?>">
    </section>

    <!-- FAMILY BACKGROUND -->
    <section>
        <h3>Family Background</h3>
        <label>Father's Name</label><input type="text" name="father_name" value="<?= htmlspecialchars($data['father_name']) ?>">
        <label>Father's Occupation</label><input type="text" name="father_occupation" value="<?= htmlspecialchars($data['father_occupation']) ?>">
        <label>Mother's Name</label><input type="text" name="mother_name" value="<?= htmlspecialchars($data['mother_name']) ?>">
        <label>Mother's Occupation</label><input type="text" name="mother_occupation" value="<?= htmlspecialchars($data['mother_occupation']) ?>">
        <label>Phone Number</label><input type="text" name="phone_number" value="<?= htmlspecialchars($data['fam_phone']) ?>">
        <label>Number of Siblings</label><input type="number" name="siblings_count" value="<?= htmlspecialchars($data['siblings_count']) ?>">
        <label>Guardian Name</label><input type="text" name="guardian_name" value="<?= htmlspecialchars($data['guardian_name']) ?>">
        <label>Guardian Occupation</label><input type="text" name="guardian_occupation" value="<?= htmlspecialchars($data['guardian_occupation']) ?>">
        <label>Guardian Contact</label><input type="text" name="contact_number" value="<?= htmlspecialchars($data['contact_number']) ?>">
        <label>Street</label><input type="text" name="fam_street" value="<?= htmlspecialchars($data['fam_street']) ?>">
        <label>Barangay</label><input type="text" name="fam_barangay" value="<?= htmlspecialchars($data['fam_barangay']) ?>">
        <label>Municipality</label><input type="text" name="fam_municipality" value="<?= htmlspecialchars($data['fam_municipality']) ?>">
        <label>Province</label><input type="text" name="fam_province" value="<?= htmlspecialchars($data['fam_province']) ?>">
        <label>Zip Code</label><input type="text" name="fam_zipcode" value="<?= htmlspecialchars($data['fam_zipcode']) ?>">
    </section>

    <!-- EDUCATIONAL BACKGROUND -->
    <section>
        <h3>Educational Background</h3>
        <label>Elementary School</label><input type="text" name="elementary" value="<?= htmlspecialchars($data['elementary']) ?>">
        <label>Year Graduated</label><input type="date" name="elem_year_grad" value="<?= htmlspecialchars($data['elem_year_grad']) ?>">
        <label>Received</label><input type="text" name="elem_received" value="<?= htmlspecialchars($data['elem_received']) ?>">
        <label>Junior High School</label><input type="text" name="junior_high" value="<?= htmlspecialchars($data['junior_high']) ?>">
        <label>Year Graduated</label><input type="date" name="jr_high_grad" value="<?= htmlspecialchars($data['jr_high_grad']) ?>">
        <label>Received</label><input type="text" name="jr_received" value="<?= htmlspecialchars($data['jr_received']) ?>">
        <label>Senior High School</label><input type="text" name="senior_high" value="<?= htmlspecialchars($data['senior_high']) ?>">
        <label>Year Graduated</label><input type="date" name="sr_high_grad" value="<?= htmlspecialchars($data['sr_high_grad']) ?>">
        <label>Received</label><input type="text" name="sr_received" value="<?= htmlspecialchars($data['sr_received']) ?>">
    </section>

    <div class="buttons">
        <button type="button" class="back-btn" onclick="window.location.href='students.php'"><i class="fa fa-arrow-left"></i> Back</button>
        <button type="submit" class="update-btn"><i class="fa fa-save"></i> Update Student</button>
    </div>
</form>
</div>
</body>
</html>
