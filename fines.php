<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

$currentPage = basename($_SERVER['PHP_SELF']);


include 'connection.php';
// // Database connection
// $con = new mysqli("localhost", "username", "password", "database");
// if ($con->connect_error) {
//     die("Connection failed: " . $con->connect_error);
// }

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_fine') {
    $roll_no = $_POST['roll_no'];
    $batch = $_POST['batch'];
    $semester = $_POST['semester'];
    $fine_amount = $_POST['fine_amount'];
    $fines = [];

    if (!empty($_POST['ut_1'])) $fines['Unit Test 1'] = $_POST['ut_1_input'];
    if (!empty($_POST['ut_2'])) $fines['Unit Test 2'] = $_POST['ut_2_input'];
    if (!empty($_POST['int_1'])) $fines['Internal 1'] = $_POST['int_1_input'];
    if (!empty($_POST['int_2'])) $fines['Internal 2'] = $_POST['int_2_input'];
    if (!empty($_POST['vst'])) $fines['VST'] = $_POST['vst_input'];
    if (!empty($_POST['project'])) $fines['Project'] = $_POST['project_input'];
    if (!empty($_POST['attend'])) $fines['Attendance'] = $_POST['attend_input'];

    $fine_details = json_encode($fines);

    $sql = "INSERT INTO fines (roll_no, batch, semester, fine_amount, fine_details) VALUES (?, ?, ?, ?, ?)";
    $stmt = $con->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($con->error));
    }
    $stmt->bind_param("sssss", $roll_no, $batch, $semester, $fine_amount, $fine_details);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "success";
    } else {
        echo "error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    exit;
}

// Fetch fines data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'get_fines') {
    $sql = "SELECT * FROM fines ORDER BY fine_no DESC";
    $result = $con->query($sql);

    $fines = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Check if fine_details is not null
            if ($row['fine_details'] !== null) {
                $row['fine_details'] = json_decode($row['fine_details'], true);
            } else {
                $row['fine_details'] = [];
            }
            $fines[] = $row;
        }
    }

    echo json_encode($fines);
    exit;
}

// Handle status update with additional details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_status_with_details') {
    $fine_no = $_POST['fine_no_modal'];
    $payment_type = $_POST['payment_type'];
    $transaction_id = $_POST['transaction_id'];
    $new_status = 1; // Assuming the status is set to "Paid"

    $sql = "UPDATE fines SET fine_status = ?, fine_payment_mode = ?, fine_transaction_id = ?, fine_update_timestamp = NOW() WHERE fine_no = ?";
    $stmt = $con->prepare($sql);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars($con->error));
    }
    $stmt->bind_param("issi", $new_status, $payment_type, $transaction_id, $fine_no);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "success";
    } else {
        echo "error: " . htmlspecialchars($stmt->error);
    }

    $stmt->close();
    exit;
}

// Close connection
$con->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
    <title>Fines</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bills.css">
</head>

<body>
    <!-- ############### NAVBAR ############### -->
    <div class="side-bar">
        <div class="logo-details">
            <img src="logo.jpg" alt="">
            <span class="logo_name">Accounts Software</span>
        </div>
        <ul class="navlinks">
            <li class="<?php echo ($currentPage === 'home.php') ? 'active' : ''; ?>">
                <a href="home.php">
                    <i class='bx bx-grid-alt'></i>
                    <span class="link-name">Dashboard</span>
                </a>
            </li>

            <li class="dropdown <?php echo ($currentPage === 'batch2022.php' || $currentPage === 'batch2023.php' || $currentPage === 'batch2024.php' || $currentPage === 'payment-update.php') ? 'active' : ''; ?>">
                <a class="dropbtn"><i class='bx bxs-graduation'></i>Batch<i class='bx bx-caret-down'></i></a>
                <div class="dropdown-content">
                    <a href="batch2022.php">All Batch 2022</a>
                    <a href="batch2023.php">All Batch 2023</a>
                    <a href="batch2024.php" id="bottom-batch">All Batch 2024</a>
                </div>
            </li>

            <li class="<?php echo ($currentPage === 'bills.php') ? 'active' : ''; ?>">
                <a href="bills.php">
                    <i class='bx bx-money-withdraw'></i>
                    <span class="link-name">Bills</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'fines.php') ? 'active' : ''; ?>">
                <a href="fines.php">
                    <i class="fa-solid fa-gavel"></i>
                    <span class="link-name">Fines</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'examination-fee.php') ? 'active' : ''; ?>">
                <a href="examination-fee.php">
                    <i class="fa-regular fa-pen-to-square"></i>
                    <span class="link-name">Examination Fee</span>
                </a>
            </li>

            <li class="<?php echo ($currentPage === 'add-students.php') ? 'active' : ''; ?>">
                <a href="add-students.php">
                    <i class="fa-solid fa-user-plus"></i>
                    <span class="link-name">Add Students</span>
                </a>
            </li>

            <div class="user-info">
                <div class="profile"></div>
                <div class="user-name">
                    <div class="name">
                        <?php if (isset($_SESSION["username"])) : ?>
                            <h3><?php echo $_SESSION["username"]; ?></h3>
                        <?php endif; ?>
                    </div>
                    <div class="type">
                        <?php if (isset($_SESSION["type"])) : ?>
                            <?php if ($_SESSION["type"] == 1) : ?>
                                <h5><?php echo "Admin"; ?></h5>
                            <?php elseif ($_SESSION["type"] == 2) : ?>
                                <h5><?php echo "Accountant"; ?></h5>
                            <?php else : ?>
                                <h5>Unknown Type: <?php echo $_SESSION["type"]; ?></h5> <!-- Add debug output -->
                            <?php endif; ?>
                        <?php else : ?>
                            <h5>Type Not Set</h5> <!-- Add debug output -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <a href="logout.php"><button class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i>Logout</button></a>

        </ul>
    </div>

    <!-- ################# FINES CONTENT ###################  -->
    <div class="content">
        <div class="header">
            <h2>Fines</h2>
        </div>
        <div class="main-content">
            <form id="fine-form">
                <div class="fine-input-container">
                    <div class="fine-inputs">
                        <div class="inputBox">
                            <label for="roll_no">Enter Roll No.</label>
                            <input type="text" id="roll_no" name="roll_no" autocomplete="off" required>
                        </div>
                        <div class="inputBox">
                            <label for="batch">Enter Batch</label>
                            <input type="text" id="batch" name="batch" autocapitalize="characters" autocomplete="off" required>
                        </div>
                        <div class="inputBox">
                            <label for="semester">Enter Semester</label>
                            <input type="text" id="semester" name="semester" autocomplete="off" required>
                        </div>
                        <div class="inputBox">
                            <label for="fine_amount">Enter Fine Amount</label>
                            <input type="text" id="fine_amount" name="fine_amount" autocomplete="off" required>
                        </div>
                    </div>
                    <div class="fine-input-fields">
                        <div class="fine-details">
                            <div class="left-inputs">
                                <div class="checkbox-input">
                                    <input type="checkbox" id="ut-1" name="ut_1">
                                    <label for="ut-1">Unit Test 1 :</label>
                                    <input type="text" id="ut_1_input" name="ut_1_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                                <div class="checkbox-input">
                                    <input type="checkbox" id="ut-2" name="ut_2">
                                    <label for="ut-2">Unit Test 2 :</label>
                                    <input type="text" id="ut_2_input" name="ut_2_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                                <div class="checkbox-input">
                                    <input type="checkbox" id="vst" name="vst">
                                    <label for="vst">VST :</label>
                                    <input type="text" id="vst_input" name="vst_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                                <div class="checkbox-input">
                                    <input type="checkbox" id="project" name="project">
                                    <label for="project">Project :</label>
                                    <input type="text" id="project_input" name="project_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                            </div>
                            <div class="right-inputs">
                                <div class="checkbox-input">
                                    <input type="checkbox" id="int-1" name="int_1">
                                    <label for="int-1">Internal 1 :</label>
                                    <input type="text" id="int_1_input" name="int_1_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                                <div class="checkbox-input">
                                    <input type="checkbox" id="int-2" name="int_2">
                                    <label for="int-2">Internal 2 :</label>
                                    <input type="text" id="int_2_input" name="int_2_input" class="fine-input-details" placeholder="Paper Names" autocapitalize="characters" autocomplete="off">
                                </div>
                                <div class="checkbox-input">
                                    <input type="checkbox" id="attend" name="attend">
                                    <label for="attend">Attendance :</label>
                                    <input type="text" id="attend_input" name="attend_input" class="fine-input-details" placeholder="Attnd. Percentage" autocomplete="off">
                                </div>
                                <button type="submit" id="fine-btn">Add Fine</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="total-students filter-section">
                <div class="search-bar">
                    <input type="text" id="search" placeholder="Search by Roll No.  or  Batch">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="status-filter">
                    <select id="status-filter">
                        <option value="" selected>Status</option>
                        <option value="1">Clear</option>
                        <option value="0">Unpaid</option>
                    </select>
                </div>
            </div>
            <div class="data-section">
                <table>
                    <thead>
                        <tr>
                            <th>Fine No.</th>
                            <th>Roll No.</th>
                            <th>Batch</th>
                            <th>Semester</th>
                            <th>Fine Details</th>
                            <th>Fine Amount</th>
                            <th>Status</th>
                            <th class="last-column">Action</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                    </tbody>
                </table>
            </div>
        </div>
        <div id="statusModal" class="modal">
            <div class="modal-content">
                <h2>Update Fine Status</h2>
                <form id="status-form">
                    <input type="hidden" id="fine_no_modal" name="fine_no_modal">
                    <div class="inputBox">
                        <label for="payment_type">Payment Mode</label>
                        <select id="payment_type" name="payment_type" required>
                            <option value="Cash">Cash</option>
                            <option value="Online">Online</option>
                        </select>
                    </div>
                    <div class="inputBox">
                        <label for="transaction_id">Transaction ID</label>
                        <input type="text" id="transaction_id" name="transaction_id" autocomplete="off" autocapitalize="characters">
                    </div>
                    <button type="submit" id="status-submit-btn">Submit</button>
                    <button type="button" id="close-modal-btn">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById('search');
            const statusFilter = document.getElementById('status-filter');
            const form = document.getElementById('fine-form');
            const tableBody = document.getElementById('table-body');
            const modal = document.getElementById('statusModal');
            const closeModalBtn = document.getElementById('close-modal-btn');
            const statusForm = document.getElementById('status-form');
            const fineNoModal = document.getElementById('fine_no_modal');

            searchInput.addEventListener('input', function() {
                filterTableRows(this.value.trim().toLowerCase(), statusFilter.value);
            });

            statusFilter.addEventListener('change', function() {
                filterTableRows(searchInput.value.trim().toLowerCase(), this.value);
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                addFine();
            });

            closeModalBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('fine-table-btn') && e.target.dataset.fineNo) {
                    fineNoModal.value = e.target.getAttribute('data-fine-no');
                    modal.style.display = 'block';
                }
            });

            statusForm.addEventListener('submit', function(e) {
                e.preventDefault();
                updateFineStatusWithDetails();
            });

            function addFine() {
                const formData = new FormData(form);
                formData.append('action', 'add_fine');

                fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            alert('Fine added successfully.');
                            loadFines();
                        } else {
                            alert('Error adding fine: ' + data);
                        }
                    })
                    .catch(error => {
                        alert('An error occurred: ' + error);
                    });
            }

            function loadFines() {
                fetch('', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'action=get_fines'
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Sort data in descending order based on fine_no
                        data.sort((a, b) => b.fine_no - a.fine_no);

                        tableBody.innerHTML = '';
                        data.forEach(fine => {
                            const fineDetails = fine.fine_details;
                            let fineDetailsHtml = '';
                            for (const [key, value] of Object.entries(fineDetails)) {
                                fineDetailsHtml += `${key} : ${value}<br>`;
                            }
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${fine.fine_no}</td>
                                <td>${fine.roll_no}</td>
                                <td>${fine.batch}</td>
                                <td>${fine.semester}</td>
                                <td>${fineDetailsHtml}</td>
                                <td>${fine.fine_amount}</td>
                                <td>
                                    <button class="fine-table-btn" data-fine-no="${fine.fine_no}" data-status="${fine.fine_status}" style="background-color: ${fine.fine_status == 1 ? 'green' : 'default'};">
                                        ${fine.fine_status == 1 ? 'Clear' : 'Unpaid'}
                                    </button>
                                </td>
                                <td>
                                    <form method="POST" action="payment/fine-invoice.php" style="display: inline;">
                                        <input type="hidden" name="fine_no" value="${fine.fine_no}">
                                        <input type="hidden" name="roll_no" value="${fine.roll_no}">
                                        <input type="hidden" name="fine_update_timestamp" value="${fine.fine_update_timestamp}">
                                        <input type="hidden" name="fine_batch" value="${fine.batch}">
                                        <input type="hidden" name="fine_semester" value="${fine.semester}">
                                        <input type="hidden" name="fine_amount" value="${fine.fine_amount}">
                                        <input type="hidden" name="fine_details" value="${fineDetailsHtml}">
                                        <input type="hidden" name="fine_payment_mode" value="${fine.fine_payment_mode}">
                                        <input type="hidden" name="fine_transaction_ID" value="${fine.fine_transaction_id}">
                                        <button type="submit" class="billing-btn fine-table-btn last-column" id="billing" ${fine.fine_status == 0 ? 'disabled' : ''}>Billing</button>
                                    </form>
                                </td>
                            `;
                            tableBody.appendChild(row);
                        });

                        document.querySelectorAll('.fine-table-btn[data-fine-no]').forEach(button => {
                            // Disable button if status is Paid
                            if (parseInt(button.getAttribute('data-status')) === 1) {
                                button.disabled = true;
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching fines:', error);
                    });
            }

            function updateFineStatusWithDetails() {
                const formData = new FormData(statusForm);
                formData.append('action', 'update_status_with_details');

                fetch('', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data === 'success') {
                            alert('Fine status updated successfully.');
                            modal.style.display = 'none';
                            loadFines();
                        } else {
                            alert('Error updating fine status: ' + data);
                        }
                    })
                    .catch(error => {
                        alert('An error occurred: ' + error);
                    });
            }

            function filterTableRows(rollNumber, status) {
                const tableRows = document.querySelectorAll('#table-body tr');
                tableRows.forEach(row => {
                    const rollNoCell = row.querySelector('td:nth-child(2)');
                    const rollNo = rollNoCell.textContent.trim().toLowerCase();
                    const statusCell = row.querySelector('.fine-table-btn');
                    const fineStatus = statusCell.getAttribute('data-status');

                    const rollMatch = rollNo.includes(rollNumber);
                    const statusMatch = status === "" || fineStatus === status;

                    if (rollMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }

            loadFines();
        });
    </script>
</body>

</html>