<!DOCTYPE html>
<html>

<head>
    <title>Production Display Board</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body style="background:#000;color:#fff">

    <div class="container-fluid mt-3">

        <!-- HEADER -->
        <div class="row text-center mb-3">
            <div class="col">
                <h2 id="buyer">Buyer</h2>
                <h4 id="style">Style</h4>
                <h5 id="item">Item</h5>
            </div>
            <div class="col">
                <h3 id="floor">Floor</h3>
                <h3 id="line">Line</h3>
            </div>
            <div class="col">
                <h4 id="date"></h4>
                <h4 id="time"></h4>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="row text-center">
            <div class="col-md-3">
                <div class="card bg-dark text-light">
                    <h5>Total Target</h5>
                    <h2 id="total_target"></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-light">
                    <h5>Total Achieve</h5>
                    <h2 id="total_achive"></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-light">
                    <h5>Efficiency</h5>
                    <h2 id="eff"></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-light">
                    <h5>DHU%</h5>
                    <h2 id="dhu"></h2>
                </div>
            </div>
        </div>

    </div>

    <script>
        let index = 0;
        let rows = [];

        function loadData() {
            $.getJSON('display_board_api.php', function(data) {
                rows = data;
                showRow();
            });
        }

        function showRow() {
            if (rows.length === 0) return;

            let r = rows[index];

            $('#buyer').text('Buyer: ' + r.buyer);
            $('#style').text('Style: ' + r.style);
            $('#item').text('Item: ' + r.item);
            $('#floor').text('Floor: ' + r.floor);
            $('#line').text('Line: ' + r.line);

            $('#total_target').text(r.total_target);
            $('#total_achive').text(r.total_achive);
            $('#eff').text(r.eff + '%');
            $('#dhu').text(r.dhu + '%');

            let now = new Date();
            $('#date').text(now.toLocaleDateString());
            $('#time').text(now.toLocaleTimeString());

            index = (index + 1) % rows.length;
        }

        // Auto refresh
        loadData();
        setInterval(loadData, 60000); // DB refresh
        setInterval(showRow, 15000); // Line rotation
    </script>

</body>

</html>