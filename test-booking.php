<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Booking Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1>Test Booking Form</h1>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Direct Booking Links</h3>
        </div>
        <div class="card-body">
            <p>Click on one of these links to test the booking page:</p>

            <div class="list-group mb-3">
                <a href="booking.php?tour_id=17&schedule_id=1" class="list-group-item list-group-item-action">
                    Tour ID: 17, Schedule ID: 1
                </a>
                <a href="booking.php?tour_id=18&schedule_id=2" class="list-group-item list-group-item-action">
                    Tour ID: 18, Schedule ID: 2
                </a>
                <a href="booking.php?tour_id=19&schedule_id=3" class="list-group-item list-group-item-action">
                    Tour ID: 19, Schedule ID: 3
                </a>
            </div>

            <p>Or try the debug page:</p>
            <a href="debug-booking.php" class="btn btn-primary">Run Booking Debug</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Custom Booking Parameters</h3>
        </div>
        <div class="card-body">
            <form action="booking.php" method="GET">
                <div class="mb-3">
                    <label for="tour_id" class="form-label">Tour ID</label>
                    <input type="number" class="form-control" id="tour_id" name="tour_id" required>
                </div>

                <div class="mb-3">
                    <label for="schedule_id" class="form-label">Schedule ID</label>
                    <input type="number" class="form-control" id="schedule_id" name="schedule_id" required>
                </div>

                <button type="submit" class="btn btn-success">Go to Booking Page</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
