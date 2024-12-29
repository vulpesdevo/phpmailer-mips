<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Booking Summary</title>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <link rel="stylesheet" href="BookingPage2.css">

        <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    </head>
    <?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo "<script>alert('You need to log in to view this page.');</script>";
    header('refresh:2; url=sample.php');
    exit;
}
$userId = $_SESSION['ownerID']; // Ensure this is set when the user logs in

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "capstone";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get values from POST parameters
$selectedPet = $_POST['pet_id'] ?? ''; // Ensure this is correctly set in your form
$selectedSalon = $_POST['salon_id'] ?? ''; // Ensure this is correctly set in your form
$selectedDate = $_POST['selected_date'] ?? '';
$selectedTime = $_POST['timeSlot'] ?? ''; // Retrieve the selected time
$selectedPayment = $_POST['payment_method'] ?? '';
$userservices = isset($_POST['serviceid']) ? $_POST['serviceid'] : [];

// Validation: Check if all required fields are filled
if (empty($selectedPet) || empty($selectedSalon) || empty($selectedDate) || empty($selectedTime) || empty($selectedPayment) || empty($userservices)) {
    echo "<script>alert('Please complete the booking by selecting all required information.'); window.location.href='BookingPage1.php';</script>";
    exit; // Stop further execution
}

// Initialize total amount
$totalAmount = 0;

// Check for duplicate bookings
$checkDuplicateSql = "SELECT * FROM book WHERE date = ? AND time = ? AND salonid = ?";
$checkStmt = $conn->prepare($checkDuplicateSql);
$checkStmt->bind_param("ssi", $selectedDate, $selectedTime, $selectedSalon);
$checkStmt->execute();
$duplicateResult = $checkStmt->get_result();

if ($duplicateResult->num_rows > 0) {
    echo "<script>alert('Duplicate entry: There is already a booking for this date and time.'); window.location.href='BookingPage1.php';</script>";
    $checkStmt->close();
    $conn->close();
    exit; // Stop further execution
}

$checkStmt->close();

// Fetch pet name
$stmt = $conn->prepare("SELECT petname FROM petinfo WHERE petid = ?");
$stmt->bind_param("i", $selectedPet);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$petname = $row['petname'] ?? 'Unknown Pet';
$stmt->close();

// Initialize salon name
$salonName = '';

// Define the $salons array
$salons = array(
    array('salonid' => 1, 'shopname' => 'Vetter Health Animal Clinic and Livestock Consultancy'),
    array('salonid' => 2, 'shopname' => 'Davids Pet Grooming Salon'),
    array('salonid' => 3, 'shopname' => 'Kanjis Pet Grooming Services'),
);

// Find the selected salon name
foreach ($salons as $salon) {
    if ($salon['salonid'] == $selectedSalon) {
        $salonName = $salon['shopname'];
        break;
    }
}

// Initialize service names
$serviceNames = [];

// Calculate total amount and prepare service IDs
$serviceIds = [];
if (!empty($userservices) && is_array ($userservices)) {
    $serviceIds = array_map('intval', $userservices); // Ensure service IDs are integers
    $serviceIdsString = implode(',', $serviceIds); // Create a comma-separated string of service IDs
} else {
    $serviceIdsString = NULL; // Set to NULL if no services are selected
}

// Fetch prices and names for the selected services
if ($serviceIdsString) {
    $sql = "SELECT servicename, price FROM services WHERE serviceid IN ($serviceIdsString)";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $serviceNames[] = htmlspecialchars($row['servicename']); // Collect service names
            $totalAmount += (float)$row['price']; // Add to total amount
        }
    }
}

// Convert service names array to a string for display
$serviceNamesString = implode(', ', $serviceNames);

// Insert data into the book table, including the status column
$status = 0; // Set status to 0 for new bookings
$stmt = $conn->prepare("INSERT INTO book (ownerID, petid, salonid, date, time, paymentmethod, serviceid, paymentprice, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisssssd", $userId, $selectedPet, $selectedSalon, $selectedDate, $selectedTime, $selectedPayment, $serviceIdsString, $totalAmount, $status);

$stmt->execute(); // Execute the insert statement
$stmt->close();
$conn->close();
?>

    <body>
        <form id="bookingForm" action="send.php" method="post">
            <input type="hidden" name="email" value="recipient@example.com">
            <input type="hidden" name="subject" value="Booking Confirmation">
            <input type="hidden" name="message" value="
            Service: <?php echo $serviceNamesString; ?>,
            Date: <?php echo htmlspecialchars($selectedDate); ?>,
            Time: <?php echo htmlspecialchars($selectedTime); ?>,
            Pet: <?php echo htmlspecialchars($petname); ?>,
            Pet Salon: <?php echo htmlspecialchars($salonName); ?>,
            Payment Method: <?php echo htmlspecialchars($selectedPayment); ?>,
            Total Fee: <?php echo number_format($totalAmount, 2); ?>
        ">
            <div class="nav">
                <div>
                    <a href="BookingPage1.php"><i class="fa-solid fa-arrow-left arrow_left"></i></a>
                    Booking Summary
                </div>
            </div>
            <!-- content -->
            <div class="box">
                <div class="contents">Service</div>
                <div class="services1">
                    <div class="contents1_service"><?php echo $serviceNamesString; ?></div>
                </div>

                <hr class="line1">
                <div class="container_date_time">
                    <div class="date_div">
                        <div class="contents">Date</div>
                        <div class="contents1_date"><?php echo htmlspecialchars($selectedDate); ?></div>
                    </div>
                    <div class="time_div">
                        <div class="contents">Time</div>
                        <div class="contents1_time"><?php echo htmlspecialchars($selectedTime); ?></div>
                    </div>
                </div>

                <hr class="line1">

                <div class="contents">Pet</div>
                <div class="contents1_pet"><?php echo htmlspecialchars($petname); ?></div>
                <hr class="line1">
                <div class="contents">Pet Salon</div>
                <div class="contents1_salon"><?php echo htmlspecialchars($salonName); ?></div>
                <hr class="line1">
                <div class="contents">Payment Method</div>
                <div class="contents1_payment"><?php echo htmlspecialchars($selectedPayment); ?></div>

                <hr class="line1">
                <div class="contents">Total Fee</div>
                <div class="contents1_fee"><?php echo number_format($totalAmount, 2); ?></div>

            </div>

            <a class="cd-popup-trigger book_button">Book</a>
            <div class="cd-popup" role="alert">
                <div class="cd-popup-container">
                    <i id="initial-icon" class="fa-solid fa-calendar-check"></i>
                    <p class="popup-text">Are you sure you want to book an appointment?</p>
                    <div id="confirmation-section" style="display: none;">
                        <i class="fa-solid fa-circle-check"></i>
                        <p class="confirmation-text">Booking Successful!</p>
                    </div>
                    <ul class="cd-buttons">
                        <li><a href="#0" class="yes-button" onclick="confirmBooking()">Book</a></li>
                        <li><a href="#0" class="no-button">Cancel</a></li>
                    </ul>
                    <a href="#0" class="cd-popup-close img-replace">Close</a>
                </div> <!-- cd-popup-container -->
            </div> <!-- cd-popup -->
        </form>

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
        <script>
        jQuery(document).ready(function($) {
            //open popup
            $('.cd-popup-trigger').on('click', function(event) {
                event.preventDefault();
                $('.cd-popup ').addClass('is-visible');
            });

            //close popup
            $('.cd-popup').on('click', function(event) {
                if ($(event.target).is('.cd-popup-close') || $(event.target).is('.cd-popup')) {
                    event.preventDefault();
                    $(this).removeClass('is-visible');
                }
            });

            //close popup when clicking the esc keyboard button
            $(document).keyup(function(event) {
                if (event.which == '27') {
                    $('.cd-popup').removeClass('is-visible');
                }
            });

            // show confirmation message when "Yes" button is clicked
            $('.yes-button').on('click', function(event) {
                event.preventDefault();
                $('.popup-text').hide();
                $('.confirmation-text').show();
                $('.cd-buttons').hide();
            });

            $('.no-button').on('click', function(event) {
                event.preventDefault();
                $('.cd-popup').removeClass('is-visible');
            });
        });

        function confirmBooking() {
            // Hide the initial booking text and icon
            document.querySelector('.popup-text').style.display = 'none';
            document.getElementById('initial-icon').style.display = 'none';
            // Show the confirmation section
            document.getElementById('confirmation-section').style.display = 'block';

            // Submit the form after a short delay to show the confirmation message
            setTimeout(function() {
                document.getElementById('bookingForm').submit();
            }, 2000); // Adjust the delay as needed
        }
        </script>

    </body>

</html>