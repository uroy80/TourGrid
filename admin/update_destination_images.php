<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
   header("Location: ../login.php");
   exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

$success_msg = $error_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_images'])) {
    // Get all destinations
    $query = "SELECT * FROM destinations";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $destinations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated_count = 0;
    
    // Define image mapping for Indian destinations
    $image_mapping = [
        'Kerala' => 'kerala.jpg',
        'Tamil Nadu' => 'tamil-nadu.jpg',
        'Karnataka' => 'karnataka.jpg',
        'Andhra Pradesh' => 'andhra-pradesh.jpg',
        'Telangana' => 'telangana.jpg',
        'Andaman and Nicobar Islands' => 'andaman.jpg',
        'Jammu and Kashmir' => 'kashmir.jpg',
        'Himachal Pradesh' => 'himachal.jpg',
        'Uttarakhand' => 'uttarakhand.jpg',
        'Rajasthan' => 'rajasthan.jpg',
        'Delhi' => 'delhi.jpg',
        'Punjab' => 'punjab.jpg',
        'Uttar Pradesh' => 'uttar-pradesh.jpg',
        'West Bengal' => 'west-bengal.jpg',
        'Odisha' => 'odisha.jpg',
        'Bihar' => 'bihar.jpg',
        'Assam' => 'assam.jpg',
        'Meghalaya' => 'meghalaya.jpg',
        'Maharashtra' => 'maharashtra.jpg',
        'Gujarat' => 'gujarat.jpg',
        'Madhya Pradesh' => 'madhya-pradesh.jpg',
        'Goa' => 'goa.jpg',
        'Sikkim' => 'sikkim.jpg',
        'Arunachal Pradesh' => 'arunachal-pradesh.jpg',
        'Nagaland' => 'nagaland.jpg',
        'Mizoram' => 'mizoram.jpg',
        'Tripura' => 'tripura.jpg',
        'Manipur' => 'manipur.jpg',
        'Lakshadweep' => 'lakshadweep.jpg',
        // Cities
        'Munnar' => 'munnar.jpg',
        'Wayanad' => 'wayanad.jpg',
        'Alleppey' => 'alleppey.jpg',
        'Cochin' => 'cochin.jpg',
        'Kovalam' => 'kovalam.jpg',
        'Ooty' => 'ooty.jpg',
        'Kodaikanal' => 'kodaikanal.jpg',
        'Chennai' => 'chennai.jpg',
        'Pondicherry' => 'pondicherry.jpg',
        'Coorg' => 'coorg.jpg',
        'Bangalore' => 'bangalore.jpg',
        'Mysore' => 'mysore.jpg',
        'Hampi' => 'hampi.jpg',
        'Jaipur' => 'jaipur.jpg',
        'Udaipur' => 'udaipur.jpg',
        'Shimla' => 'shimla.jpg',
        'Manali' => 'manali.jpg',
        'Darjeeling' => 'darjeeling.jpg',
        'Mumbai' => 'mumbai.jpg',
    ];
    
    // Update destination images
    foreach ($destinations as $destination) {
        $destination_name = $destination['name'];
        
        // Check if we have a mapping for this destination
        if (isset($image_mapping[$destination_name])) {
            $image_filename = $image_mapping[$destination_name];
            
            // Update the destination image in the database
            $update_query = "UPDATE destinations SET image = :image WHERE destination_id = :destination_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":image", $image_filename);
            $update_stmt->bindParam(":destination_id", $destination['destination_id']);
            
            if ($update_stmt->execute()) {
                $updated_count++;
            }
        }
    }
    
    if ($updated_count > 0) {
        $success_msg = "Successfully updated images for $updated_count destinations.";
    } else {
        $error_msg = "No destination images were updated.";
    }
}

// Include admin header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Update Destination Images</h1>
            </div>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Update Images for Indian Destinations</h6>
                </div>
                <div class="card-body">
                    <p>This tool will update the images for all Indian destinations in the database. It will assign appropriate images to states and popular cities.</p>
                    <p>Note: This will only update destinations that match the predefined list of Indian locations. Any custom destinations will not be affected.</p>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <button type="submit" name="update_images" class="btn btn-primary">Update Destination Images</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Indian Destinations Image Reference</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Region</th>
                                    <th>States/UTs</th>
                                    <th>Popular Cities</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>South India</td>
                                    <td>Kerala, Tamil Nadu, Karnataka, Andhra Pradesh, Telangana, Andaman and Nicobar Islands</td>
                                    <td>Munnar, Wayanad, Alleppey, Cochin, Kovalam, Ooty, Kodaikanal, Coorg, Bangalore, Mysore, Hampi</td>
                                </tr>
                                <tr>
                                    <td>North India</td>
                                    <td>Jammu and Kashmir, Himachal Pradesh, Uttarakhand, Rajasthan, Delhi, Punjab, Uttar Pradesh</td>
                                    <td>Srinagar, Shimla, Manali, Jaipur, Udaipur, Agra, Varanasi</td>
                                </tr>
                                <tr>
                                    <td>East & West India</td>
                                    <td>West Bengal, Odisha, Bihar, Assam, Meghalaya, Maharashtra, Gujarat, Madhya Pradesh, Goa</td>
                                    <td>Darjeeling,

Meghalaya, Maharashtra, Gujarat, Madhya Pradesh, Goa</td>
                                    <td>Darjeeling, Kolkata, Mumbai, Pune, Ahmedabad, Goa Beaches</td>
                                </tr>
                                <tr>
                                    <td>Northeast & Islands</td>
                                    <td>Sikkim, Arunachal Pradesh, Nagaland, Mizoram, Tripura, Manipur, Lakshadweep</td>
                                    <td>Gangtok, Tawang, Kohima, Agatti Island</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
