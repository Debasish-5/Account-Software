<?php
session_start();

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: index.php");
    exit;
}

include 'navigation.php';
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
    <title>All Batch 2024</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="bills.css">
    <style>

    </style>
</head>

<body>
    <div class="content">
        <div class="header">
            <h2>All Batch 2024</h2>
            <button class="year" onclick="exportTableToExcel()">Exportㅤ<i class="fa-solid fa-file-export"></i></button>
        </div>
        <div class="main-content">
            <div class="total-payment-details">
                <div class="details-box">
                    <h4>Total Students : <span id="total-students-count"></span></h4>
                </div>
                <div class="details-box">
                    <h4>Total Amount : <span id="total-amount"></span></h4>
                </div>
                <div class="details-box">
                    <h4>Paid Amount : <span id="paid-amount"></span></h4>
                </div>
                <div class="details-box">
                    <h4>Remaining Amount : <span id="remaining-amount"></span></h4>
                </div>
                <div class="details-box">
                    <select id="batch-filter">
                        <!-- ############# TO BE CHANGED ############# -->
                        <option value="">Select Batch</option>
                        <option value="BBA24">BBA</option>
                        <option value="BCA24">BCA</option>
                        <option value="BSC24">B.Sc CS(H)</option>
                    </select>
                </div>
            </div>
            <div class="total-students filter-section">
                <div class="search-bar">
                    <input type="text" name="" id="search" placeholder="Search by Roll No.">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>

                <div id="1-year" class="year" onclick="selectYear(1)">
                    <h4>1st Year</h4>
                </div>
                <div id="2-year" class="year" onclick="selectYear(2)">
                    <h4>2nd Year</h4>
                </div>
                <div id="3-year" class="year" onclick="selectYear(3)">
                    <h4>3rd Year</h4>
                </div>


                <div class="status-filter">
                    <select id="status-filter">
                        <option value="" selected>Status</option>
                        <option value="Clear">Clear</option>
                        <option value="Outstanding">Outstanding</option>
                    </select>
                </div>
            </div>
            <div class="data-section">
                <table>
                    <thead>
                        <tr>
                            <th>Roll No.</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Remaining Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">

                    </tbody>
                </table>
            </div>
            <div id="information">

            </div>
        </div>
    </div>

    <script src="bills.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const selectedYear = localStorage.getItem('selectedYear2024');
            if (selectedYear) {
                selectYear(selectedYear); // Highlight the selected year
            } else {
                selectYear(1); // Default to the first year (1st Year)
            }

            fetchData(selectedYear);

            const searchInput = document.getElementById('search');
            searchInput.addEventListener('input', function() {
                filterTableRows(this.value.trim().toLowerCase());
            });

            const statusFilter = document.getElementById('status-filter');
            statusFilter.addEventListener('change', function() {
                filterTableByStatus(this.value);
            });

            const batchFilter = document.getElementById('batch-filter');
            batchFilter.addEventListener('change', function() {
                const selectedBatch = this.value;
                if (selectedBatch === "") {
                    fetchData(selectedYear);
                } else {
                    filterDataByBatch(selectedBatch, selectedYear);
                }
            });
        });

        function selectYear(year) {
            localStorage.setItem('selectedYear2024', year); //############ TO BE CHANGED #############
            fetchData(year);

            const yearElements = document.querySelectorAll('.year');
            yearElements.forEach(element => {
                if (element.id === `${year}-year`) {
                    element.classList.add('selected');
                } else {
                    element.classList.remove('selected');
                }
            });
        }

        function fetchData(year) {
            fetch('batch-payment-data.php')
                .then(response => response.json())
                .then(data => {
                    const filteredData = data.filter(row => {
                        const rollNo = row.ID;
                        return rollNo.includes("03BCA24") || rollNo.includes("03BSC24") || rollNo.includes("03BBA24");
                    }); //############ TO BE CHANGED #############
                    populateTable(filteredData, year);
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        function filterDataByBatch(batch, year) {
            fetch('batch-payment-data.php')
                .then(response => response.json())
                .then(data => {
                    const filteredData = data.filter(row => {
                        const rollNo = row.ID;
                        return rollNo.includes(batch); // Filter data based on batch
                    });
                    populateTable(filteredData, year);
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        function formatNumberIndian(num) {
            const x = num.toString();
            const lastThree = x.substring(x.length - 3);
            const otherNumbers = x.substring(0, x.length - 3);
            if (otherNumbers !== '') {
                return otherNumbers.replace(/\B(?=(\d{2})+(?!\d))/g, ",") + ',' + lastThree;
            }
            return lastThree;
        }


        function populateTable(data, year) {
            const tableBody = document.getElementById('table-body');
            tableBody.innerHTML = '';

            const fragment = document.createDocumentFragment();

            let totalStudents = 0;
            let totalAmountSum = 0;
            let totalPaidAmountSum = 0;
            let totalRemainingAmountSum = 0;

            data.forEach(row => {
                let totalAmount, oneTimePayment, firstInstallment, secondInstallment, thirdInstallment, fourthInstallment;

                if (year == 1) {
                    totalAmount = parseInt(row['1st_yr'], 10);
                    oneTimePayment = parseInt(row['1st_yr_one_time_payment'], 10);
                    firstInstallment = parseInt(row['1st_yr_1st_installment'], 10);
                    secondInstallment = parseInt(row['1st_yr_2nd_installment'], 10);
                    thirdInstallment = parseInt(row['1st_yr_3rd_installment'], 10);
                    fourthInstallment = parseInt(row['1st_yr_4th_installment'], 10);
                } else if (year == 2) {
                    totalAmount = parseInt(row['2nd_yr'], 10);
                    oneTimePayment = parseInt(row['2nd_yr_one_time_payment'], 10);
                    firstInstallment = parseInt(row['2nd_yr_1st_installment'], 10);
                    secondInstallment = parseInt(row['2nd_yr_2nd_installment'], 10);
                    thirdInstallment = parseInt(row['2nd_yr_3rd_installment'], 10);
                    fourthInstallment = parseInt(row['2nd_yr_4th_installment'], 10);
                } else if (year == 3) {
                    totalAmount = parseInt(row['3rd_yr'], 10);
                    oneTimePayment = parseInt(row['3rd_yr_one_time_payment'], 10);
                    firstInstallment = parseInt(row['3rd_yr_1st_installment'], 10);
                    secondInstallment = parseInt(row['3rd_yr_2nd_installment'], 10);
                    thirdInstallment = parseInt(row['3rd_yr_3rd_installment'], 10);
                    fourthInstallment = parseInt(row['3rd_yr_4th_installment'], 10);
                }

                const sum = firstInstallment + secondInstallment + thirdInstallment + fourthInstallment + oneTimePayment;
                let status;
                if (totalAmount === 0) {
                    status = "Outstanding";
                } else if ((firstInstallment + secondInstallment) >= (totalAmount / 2) && thirdInstallment === 0 && fourthInstallment === 0) {
                    status = "Clear";
                } else if (totalAmount === sum && totalAmount !== 0) {
                    status = "Clear";
                } else {
                    status = "Outstanding";
                }

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>${row.ID}</td>
            <td>${formatNumberIndian(totalAmount)}</td>
            <td>${formatNumberIndian(sum)}</td>
            <td>${formatNumberIndian(totalAmount - sum)}</td>
            <td>${status}</td>
            <td><button onclick="updateData('${row.ID}', '${year}')" class="update-btn">Update</button></td>
        `;
                fragment.appendChild(tr);

                // Update totals
                totalStudents++;
                totalAmountSum += totalAmount;
                totalPaidAmountSum += sum;
                totalRemainingAmountSum += (totalAmount - sum);
            });

            tableBody.appendChild(fragment);

            // Update the total values in the DOM
            document.getElementById('total-students-count').textContent = formatNumberIndian(totalStudents);
            document.getElementById('total-amount').textContent = formatNumberIndian(totalAmountSum);
            document.getElementById('paid-amount').textContent = formatNumberIndian(totalPaidAmountSum);
            document.getElementById('remaining-amount').textContent = formatNumberIndian(totalRemainingAmountSum);
        }

        function updateData(rollNo, year) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'payment-update.php';

            var rollNoInput = document.createElement('input');
            rollNoInput.type = 'hidden';
            rollNoInput.name = 'rollNo';
            rollNoInput.value = rollNo;
            form.appendChild(rollNoInput);

            var yearInput = document.createElement('input');
            yearInput.type = 'hidden';
            yearInput.name = 'year';
            yearInput.value = year;
            form.appendChild(yearInput);

            document.body.appendChild(form);
            form.submit();
        }

        function filterTableRows(rollNumber) {
            const tableRows = document.querySelectorAll('#table-body tr');
            tableRows.forEach(row => {
                const rollNoCell = row.querySelector('td:first-child');
                const rollNo = rollNoCell.textContent.trim().toLowerCase();
                if (rollNo.includes(rollNumber)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterTableByStatus(status) {
            const tableRows = document.querySelectorAll('#table-body tr');
            tableRows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(5)');
                const rowStatus = statusCell.textContent.trim();
                if (status === '' || rowStatus === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function exportTableToExcel() {
            const table = document.querySelector('table');
            const workbook = XLSX.utils.table_to_book(table, {
                sheet: "Sheet 1"
            });
            XLSX.writeFile(workbook, '2024-Batch-Data.xlsx'); //############ TO BE CHANGED #############
        }
    </script>
</body>

</html>