<?php
// Oracle Database Connection Parameters
$host = '192.168.100.29';
$port = '1521';
$service_name = 'orcl';
$username = 'NtgBi';
$password = 'NtgbI@2025';

// Establish Oracle Connection using OCI
$conn = oci_connect($username, $password, "(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(HOST=$host)(PORT=$port))(CONNECT_DATA=(SERVICE_NAME=$service_name)))");
if (!$conn) {
    $e = oci_error();
    die("Oracle Connection failed: " . $e['message']);
}

// Function to execute Oracle query and return results as array
function oracle_select($query)
{
    global $conn;
    $stid = oci_parse($conn, $query);
    if (!$stid) {
        $e = oci_error($conn);
        die("Query Parse Error: " . $e['message']);
    }
    oci_execute($stid);
    $results = array();
    while ($row = oci_fetch_assoc($stid)) {
        $results[] = $row;
    }
    oci_free_statement($stid);
    return $results;
}

// Function to get single field value (adapted)
function return_field_value($field, $table, $condition, $return_field)
{
    $query = "SELECT $field FROM $table WHERE $condition";
    $result = oracle_select($query);
    return isset($result[0][$return_field]) ? $result[0][$return_field] : null;
}

// Adapted from provided PHP files: Load floors, lines, etc.
// Assuming similar tables exist in Oracle (lib_prod_floor, lib_sewing_line, prod_resource_mst, etc.)
// You may need to adjust table/column names based on actual Oracle schema.

// API-like endpoint simulation: If action is set, handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    header('Content-Type: application/json');

    if ($action == 'get_sheets') {
        // Simulate sheets (e.g., companies or reports). Adjust query as needed.
        $sheets = oracle_select("SELECT DISTINCT COMPANY_NAME FROM LIB_COMPANY"); // Example
        echo json_encode(array_column($sheets, 'COMPANY_NAME'));
    } elseif ($action == 'get_floors') {
        $sheet = $_GET['sheet'] ?? 'Daily Production Report';
        // Get floors for selected "sheet" (adapt query)
        $floors = oracle_select("SELECT DISTINCT FLOOR FROM YOUR_PRODUCTION_TABLE"); // Replace with actual table
        echo json_encode(array_column($floors, 'FLOOR'));
    } elseif ($action == 'get_data') {
        $sheet = $_GET['sheet'] ?? 'Daily Production Report';
        $floor = $_GET['floor'] ?? null;

        // Main query to fetch data (adapted columns)
        $query = "SELECT DATE, BUYER_NAME, STYLE, ITEM, FLOOR, LINE, TOTAL_TARGET, TOTAL_ACHIVE, 
                         HOURLY_TARGET, HOURLY_ACHIVE, OPERATOR, HELPER, TOTAL_MACHINE, SMV, DHU, 
                         EFFICIENCY, LINE_WISE_TOTAL_PRODUCTION, LINE_WISE_EFFICIENCY 
                  FROM YOUR_PRODUCTION_TABLE"; // Replace with actual table name

        if ($floor) {
            $query .= " WHERE FLOOR = '$floor'";
        }

        $data = oracle_select($query);

        // Process data similar to final.html JS (group by floor/line)
        $processed = [];
        foreach ($data as $row) {
            $floorName = $row['FLOOR'];
            if (!isset($processed[$floorName])) {
                $processed[$floorName] = ['lines' => [], /* add totals */];
            }
            $processed[$floorName]['lines'][] = $row;
            // Calculate totals (similar to parseSheetData in JS)
        }

        echo json_encode($processed);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TIL Hourly Production Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        /* CSS from final.html */
        body {
            background: #000000;
        }

        .today-date {
            text-align: right;
        }

        @media screen {
            .card-body {
                height: auto;
            }

            h1,
            h2,
            h3,
            h4,
            h5 {
                font-size: 3vw;
            }

            #buyer,
            #line,
            #style,
            #item {
                font-size: 2.5vw;
            }

            #helpar,
            #operator {
                font-size: 3vw;
            }

            #currentTimeBlock,
            #currentDateBlock {
                font-size: 2.25vw;
            }

            #floor,
            #block {
                font-size: 2.25vw;
            }

            #canvan {
                height: 50vh;
            }
        }

        @media screen and (min-width: 769px) {
            .card-body {
                height: 100%;
            }

            h1,
            h2,
            h3,
            h4,
            h5 {
                font-size: 1.25vw;
            }

            h6 {
                font-size: 0.9vw;
            }

            #currentTimeBlock,
            #currentDateBlock {
                font-size: 1vw;
            }

            #buyer,
            #line,
            #style,
            #item {
                font-size: 1.25vw;
            }

            #helpar,
            #operator {
                font-size: 2vw;
            }

            #floor,
            #block {
                font-size: 1.25vw;
            }
        }

        .line-highlight {
            background-color: rgba(255, 255, 0, 0.2) !important;
            transition: background-color 0.5s ease;
        }

        .bottleneck-container {
            overflow: hidden;
            position: relative;
            height: 100%;
        }

        .bottleneck-marquee {
            position: absolute;
            white-space: nowrap;
            animation: marquee 15s linear infinite;
        }

        @keyframes marquee {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .bottleneck-marquee.paused {
            animation-play-state: paused;
        }

        #line-wise-data-body tr:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
            cursor: pointer;
        }
    </style>
</head>

<body class="text-light">
    <div class="container-fluid">
        <!-- HTML structure from final.html -->
        <div class="row mb-3 mt-2 text-center">
            <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                <div class="card p-1 text-light" style="background: #131730; border: 1px solid #ffffff; width: 100%;">
                    <div class="card-body p-1 text-light" style="height: 8vh">
                        <h6 id="sheet-display">Sheet: <select id="sheetSelector" class="form-select form-select-sm bg-dark text-light"></select></h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                <div class="card p-1 text-light" style="background: #131730; border: 1px solid #ffffff; width: 100%;">
                    <div class="card-body p-1 text-light" style="height: 8vh">
                        <h6 id="floor-display">Floor: <select id="floorSelector" class="form-select form-select-sm bg-dark text-light"></select></h6>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-xl-6 col-md-6 col-sm-12">
                <div class="card m-1 text-light" style="background: #131730; border: 1px solid #ffffff; width: 100%;">
                    <div class="card-body pt-1" style="height: 8vh">
                        <div class="row">
                            <div class="col-12">
                                <marquee>
                                    <strong class="card-text" id="buyer">Buyer: John Doe</strong>
                                    <strong class="card-text" id="line">Line: 1</strong>
                                    <strong class="card-text" id="style">Style: A</strong>
                                    <strong class="card-text" id="item">Item: XYZ</strong>
                                </marquee>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                <div class="card m-1 text-light" style="background: #131730; border: 1px solid #ffffff; width: 100%;">
                    <div class="card-body pt-1" style="height: 8vh">
                        <h5 class="card-text" id="currentDateBlock"><strong>Date: <span id="currentDate">Oct 17, 2024</span></strong></h5>
                        <h5 class="card-text text-left" id="currentTimeBlock">Time: <span id="currentTime">12:25 PM</span></h5>
                    </div>
                </div>
            </div>
        </div>
        <!-- Rest of the HTML from final.html -->
        <div id="floor-dashboard-container">
            <!-- Production Card -->
            <div class="row mb-3 mt-2 text-center">
                <div class="col-lg-4 col-xl-4 col-md-4 col-sm-12">
                    <div class="card mb-2 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 57vh; text-align: left">
                            <h1 class="card-text text-left">Production</h1>
                            <h2 class="card-text" style="text-align: center">Total Target : </h2>
                            <h1 class="card-title" id="total_target_per_day" style="font-size: 7vh; text-align: center">6000</h1>
                            <h2 class="card-text text-left" style="text-align: center">Hourly Target : </h2>
                            <h1 class="card-title text-left" id="total_target_per_hour" style="font-size: 7vh; text-align: center">600</h1>
                            <h2 class="card-text text-left" style="text-align: center">Achievement : </h2>
                            <h1 class="card-title" style="font-size: 7vh; text-align: center">
                                <label id="total_output_hour">450</label>
                                <span id="total_output_hour_indication_down" style="display: inline;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="Red" class="bi bi-caret-down-fill" viewBox="0 0 16 16">
                                        <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z" />
                                    </svg>
                                </span>
                                <span id="total_output_hour_indication_up" style="display: none;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="Green" class="bi bi-caret-up-fill" viewBox="0 0 16 16">
                                        <path d="m7.247 4.86-4.796 5.481c-.566.647-.106 1.659.753 1.659h9.592a1 1 0 0 0 .753-1.659l-4.796-5.48a1 1 0 0 0-1.506 0z" />
                                    </svg>
                                </span>
                            </h1>
                        </div>
                    </div>
                </div>
                <!-- Chart Card -->
                <div class="col-lg-6 col-xl-6 col-md-6 col-sm-12">
                    <div class="card mb-3 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 57vh">
                            <h2 class="card-text text-left" id="graph-title">Cumulative (Floor Total)</h2>
                            <div class="pt-1" id="canvan">
                                <canvas id="myChart" style="width: 100%; height: 100%;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Efficiency Cards -->
                <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                    <div class="row">
                        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                            <div class="card mb-3 text-light" style="background: #131730; border: 1px solid #ffffff;">
                                <div class="card-body" style="height: 27vh">
                                    <h2 class="card-text text-left">Efficiency</h2>
                                    <h5 id="total_efficiency" style="font-size: 4vh; text-align: center">75.00%</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
                        <div class="card mb-3 text-light" style="background: #131730; border: 1px solid #ffffff;">
                            <div class="card-body" style="height: 27vh">
                                <h2 class="card-text text-left">Stylewise Average Efficiency</h2>
                                <h3 class="card-title text-center" style="font-size: 5vh; text-align: center">
                                    <label id="total_performance" style="font-size: 5vh; text-align: center">75.00 </label>%
                                </h3>
                                <h6 id="performance-label"></h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bottom Row Cards -->
            <div class="row mb-1 mt-2 text-center">
                <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                    <div class="card mb-1 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 25vh">
                            <h3 class="card-text text-left" style="text-align: left">Manpower</h3>
                            <table class="table text-light text-center table-borderless">
                                <tbody>
                                    <tr class="text-center">
                                        <th scope="col">
                                            <h6>Operators</h6>
                                        </th>
                                        <td scope="col">
                                            <h6 id="total_operator">-</h6>
                                        </td>
                                    </tr>
                                    <tr class="text-center">
                                        <th scope="col">
                                            <h6>Helpers</h6>
                                        </th>
                                        <td scope="col">
                                            <h6 id="total_helpar">-</h6>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                    <div class="card mb-1 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 25vh">
                            <h2 class="card-text text-left">Total Machine</h2>
                            <h3 class="card-title text-center">
                                <label id="total_running_machine" style="font-size: 7vh; text-align: center">70</label>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                    <div class="card mb-1 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 25vh">
                            <h2 class="card-text text-left">SMV</h2>
                            <h3 class="card-title text-center">
                                <label id="average_smv" style="font-size: 7vh; text-align: center">5.56</label>
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-xl-4 col-md-4 col-sm-12">
                    <div class="card mb-1 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 25vh">
                            <h2 class="card-text text-center">Remarks</h2>
                            <div class="bottleneck-container" id="bottleneck-container">
                                <div class="bottleneck-marquee" id="bottleneck-marquee">No Remarks reported</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-xl-2 col-md-2 col-sm-12">
                    <div class="card mb-1 text-light" style="background: #131730; border: 1px solid #ffffff;">
                        <div class="card-body" style="height: 25vh">
                            <h2 class="card-text text-left">DHU</h2>
                            <h3 class="card-title text-center" style="font-size: 5vh; text-align: center">
                                <label id="average_dhu" style="font-size: 5vh; text-align: center">0.44%</label>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // JS from final.html, adapted to fetch from PHP instead of Google Sheets
        var ctx = document.getElementById('myChart').getContext('2d');
        var myChart = new Chart(ctx, {
            /* Chart config same as final.html */ });

        // Fetch sheets from PHP
        function fetchSheets() {
            $.getJSON('?action=get_sheets', function(sheets) {
                $('#sheetSelector').html('');
                sheets.forEach(sheet => {
                    $('#sheetSelector').append(`<option value="${sheet}">${sheet}</option>`);
                });
            });
        }

        // Fetch floors for selected sheet
        function fetchFloors(sheet) {
            $.getJSON(`?action=get_floors&sheet=${sheet}`, function(floors) {
                $('#floorSelector').html('');
                floors.forEach(floor => {
                    $('#floorSelector').append(`<option value="${floor}">${floor}</option>`);
                });
            });
        }

        // Fetch data for selected sheet/floor
        function fetchData(sheet, floor) {
            $.getJSON(`?action=get_data&sheet=${sheet}&floor=${floor}`, function(data) {
                // Process data and update dashboard (adapt parseSheetData logic from final.html)
                // Update cards, chart, etc.
            });
        }

        // Event listeners same as final.html, but call fetch functions
        $('#sheetSelector').change(function() {
            fetchFloors(this.value);
        });
        $('#floorSelector').change(function() {
            fetchData($('#sheetSelector').val(), this.value);
        });

        // Initialize
        fetchSheets();
        // Update time, etc. same as final.html
    </script>
</body>

</html>