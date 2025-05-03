<?php
// Database connection function
function connectDatabase() {
    // Use document root to create an absolute path
    $root_path = $_SERVER['DOCUMENT_ROOT'] . '/Toursync/';
    require_once $root_path . 'config/database.php';
    $database = new Database();
    return $database->getConnection();
}

// Function to get all destinations
function getAllDestinations() {
    $conn = connectDatabase();
    $sql = "SELECT * FROM destinations WHERE is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $destinations = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $destinations[] = $row;
    }
    
    return $destinations;
}

// Function to get destinations by hierarchy - simplified version
function getDestinationsHierarchy() {
    $conn = connectDatabase();
    
    // Check if the parent_id and type columns exist
    try {
        $check_sql = "SHOW COLUMNS FROM destinations LIKE 'parent_id'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            // Columns don't exist, return a simple list
            return ['error' => 'Hierarchical structure not available'];
        }
    } catch (Exception $e) {
        // Error occurred, return a simple list
        return ['error' => 'Database error: ' . $e->getMessage()];
    }
    
    // Get all destinations
    $sql = "SELECT * FROM destinations WHERE is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $destinations = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $destinations[] = $row;
    }
    
    // Organize into hierarchy
    $hierarchy = [];
    foreach ($destinations as $destination) {
        if ($destination['parent_id'] === null) {
            // This is a top-level destination (region)
            $hierarchy[$destination['destination_id']] = [
                'info' => $destination,
                'states' => []
            ];
        }
    }
    
    // Add states to regions
    foreach ($destinations as $destination) {
        if ($destination['parent_id'] !== null && isset($hierarchy[$destination['parent_id']])) {
            // This is a state under a region
            $hierarchy[$destination['parent_id']]['states'][$destination['destination_id']] = [
                'info' => $destination,
                'cities' => []
            ];
        }
    }
    
    // Add cities to states
    foreach ($destinations as $destination) {
        foreach ($hierarchy as $region_id => $region) {
            foreach ($region['states'] as $state_id => $state) {
                if ($destination['parent_id'] == $state_id) {
                    // This is a city under a state
                    $hierarchy[$region_id]['states'][$state_id]['cities'][] = $destination;
                }
            }
        }
    }
    
    return $hierarchy;
}

// Function to get destinations by region (legacy support)
function getDestinationsByRegion() {
    $conn = connectDatabase();
    $sql = "SELECT * FROM destinations WHERE is_active = 1 ORDER BY name";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $destinations = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $destinations[] = $row;
    }
    
    // Group by region (simplified)
    $regions = [
        'South India' => [],
        'North India' => [],
        'East India' => [],
        'West India' => [],
        'Northeast India' => [],
        'Islands' => []
    ];
    
    foreach ($destinations as $destination) {
        // Simple assignment based on name patterns
        if (in_array($destination['name'], ['Kerala', 'Tamil Nadu', 'Karnataka', 'Andhra Pradesh', 'Telangana', 'Andaman and Nicobar Islands'])) {
            $regions['South India'][] = $destination;
        } elseif (in_array($destination['name'], ['Jammu and Kashmir', 'Himachal Pradesh', 'Uttarakhand', 'Rajasthan', 'Delhi', 'Punjab', 'Uttar Pradesh'])) {
            $regions['North India'][] = $destination;
        } elseif (in_array($destination['name'], ['West Bengal', 'Odisha', 'Bihar', 'Assam', 'Meghalaya'])) {
            $regions['East India'][] = $destination;
        } elseif (in_array($destination['name'], ['Maharashtra', 'Gujarat', 'Madhya Pradesh', 'Goa'])) {
            $regions['West India'][] = $destination;
        } elseif (in_array($destination['name'], ['Sikkim', 'Arunachal Pradesh', 'Nagaland', 'Mizoram', 'Tripura', 'Manipur'])) {
            $regions['Northeast India'][] = $destination;
        } elseif (in_array($destination['name'], ['Lakshadweep'])) {
            $regions['Islands'][] = $destination;
        } else {
            // Default to South India if not matched
            $regions['South India'][] = $destination;
        }
    }
    
    return $regions;
}

// Function to get all states
function getAllStates() {
    $conn = connectDatabase();
    $sql = "SELECT * FROM destinations WHERE type = 'state' AND is_active = 1 ORDER BY name";
    $result = $conn->query($sql);
    
    $states = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $states[] = $row;
        }
    }
    
    $conn->close();
    return $states;
}

// Function to get cities by state
function getCitiesByState($state_id) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM destinations WHERE parent_id = ? AND type = 'city' AND is_active = 1 ORDER BY name";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $state_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cities = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cities[] = $row;
        }
    }
    
    $stmt->close();
    $conn->close();
    return $cities;
}

// Function to get parent destination
function getParentDestination($destination_id) {
    $conn = connectDatabase();
    $sql = "SELECT p.* FROM destinations d 
            JOIN destinations p ON d.parent_id = p.destination_id 
            WHERE d.destination_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parent = null;
    if ($result->num_rows > 0) {
        $parent = $result->fetch_assoc();
    }
    
    $stmt->close();
    $conn->close();
    return $parent;
}

// Function to get full destination path (e.g., "South India > Kerala > Munnar")
function getDestinationPath($destination_id) {
    $conn = connectDatabase();
    $path = [];
    
    // Get the destination
    $sql = "SELECT * FROM destinations WHERE destination_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $destination_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $destination = $result->fetch_assoc();
        $path[] = $destination['name'];
        
        // Get parent if exists
        if ($destination['parent_id']) {
            $parent_id = $destination['parent_id'];
            
            // Get parent
            $sql = "SELECT * FROM destinations WHERE destination_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $parent_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $parent = $result->fetch_assoc();
                $path[] = $parent['name'];
                
                // Get grandparent if exists
                if ($parent['parent_id']) {
                    $grandparent_id = $parent['parent_id'];
                    
                    // Get grandparent
                    $sql = "SELECT * FROM destinations WHERE destination_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $grandparent_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $grandparent = $result->fetch_assoc();
                        $path[] = $grandparent['name'];
                    }
                }
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    
    // Reverse to get correct order (region > state > city)
    return array_reverse($path);
}

// Function to get popular destinations (top 5 by tour count)
function getPopularDestinations($limit = 5) {
    $conn = connectDatabase();
    $sql = "SELECT d.*, COUNT(t.tour_id) as tour_count 
            FROM destinations d
            JOIN tours t ON d.destination_id = t.destination_id
            WHERE d.is_active = 1 AND t.is_active = 1
            GROUP BY d.destination_id
            ORDER BY tour_count DESC, d.name
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $destinations = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $destinations[] = $row;
    }
    
    return $destinations;
}

// Function to get destination by ID
function getDestinationById($id) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM destinations WHERE destination_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $destination = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $destination = $row;
    }
    
    return $destination;
}

// Function to get all tours
function getAllTours() {
    $conn = connectDatabase();
    $sql = "SELECT t.*, d.name as destination_name 
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.is_active = 1
            ORDER BY t.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $tours = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tours[] = $row;
    }
    
    return $tours;
}

// Function to get featured tours
function getFeaturedTours($limit = 4) {
    $conn = connectDatabase();
    $sql = "SELECT t.*, d.name as destination_name 
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.is_featured = 1 AND t.is_active = 1
            ORDER BY t.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $tours = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tours[] = $row;
    }
    
    return $tours;
}

// Function to get tours by destination
function getToursByDestination($destinationId) {
    $conn = connectDatabase();
    $sql = "SELECT t.*, d.name as destination_name 
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.destination_id = ? AND t.is_active = 1
            ORDER BY t.price";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $destinationId, PDO::PARAM_INT);
    $stmt->execute();
    
    $tours = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tours[] = $row;
    }
    
    return $tours;
}

// Function to get tour by ID
function getTourById($id) {
    $conn = connectDatabase();
    $sql = "SELECT t.*, d.name as destination_name 
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.tour_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $tour = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tour = $row;
    }
    
    return $tour;
}

// Function to get tour schedules by tour ID
function getTourSchedules($tourId) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM tour_schedules 
            WHERE tour_id = ? AND status = 'upcoming' AND start_date > CURDATE()
            ORDER BY start_date";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $tourId, PDO::PARAM_INT);
    $stmt->execute();
    
    $schedules = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schedules[] = $row;
    }
    
    return $schedules;
}

// Function to get hotels by destination
function getHotelsByDestination($destinationId) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM hotels 
            WHERE destination_id = ? AND is_active = 1
            ORDER BY star_rating DESC, price_per_night";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $destinationId, PDO::PARAM_INT);
    $stmt->execute();
    
    $hotels = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hotels[] = $row;
    }
    
    return $hotels;
}

// Function to get hotel by ID
function getHotelById($id) {
    $conn = connectDatabase();
    $sql = "SELECT h.*, d.name as destination_name 
            FROM hotels h
            JOIN destinations d ON h.destination_id = d.destination_id
            WHERE h.hotel_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $hotel = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hotel = $row;
    }
    
    return $hotel;
}

// Function to get tour itinerary
function getTourItinerary($tourId) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM tour_itinerary 
            WHERE tour_id = ?
            ORDER BY day_number";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $tourId, PDO::PARAM_INT);
    $stmt->execute();
    
    $itinerary = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $itinerary[] = $row;
    }
    
    return $itinerary;
}

// Function to get hotels associated with a tour
function getTourHotels($tourId) {
    $conn = connectDatabase();
    $sql = "SELECT h.*, th.nights, th.room_type 
            FROM hotels h
            JOIN tour_hotels th ON h.hotel_id = th.hotel_id
            WHERE th.tour_id = ?
            ORDER BY h.star_rating DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $tourId, PDO::PARAM_INT);
    $stmt->execute();
    
    $hotels = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hotels[] = $row;
    }
    
    return $hotels;
}

// Function to search tours
function searchTours($destination = null, $minPrice = null, $maxPrice = null, $duration = null) {
    $conn = connectDatabase();
    
    $sql = "SELECT t.*, d.name as destination_name 
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.is_active = 1";
    
    $params = [];
    $types = [];
    
    if ($destination) {
        $sql .= " AND t.destination_id = ?";
        $params[] = $destination;
        $types[] = PDO::PARAM_INT;
    }
    
    if ($minPrice) {
        $sql .= " AND t.price >= ?";
        $params[] = $minPrice;
        $types[] = PDO::PARAM_STR;
    }
    
    if ($maxPrice) {
        $sql .= " AND t.price <= ?";
        $params[] = $maxPrice;
        $types[] = PDO::PARAM_STR;
    }
    
    if ($duration) {
        $sql .= " AND t.duration <= ?";
        $params[] = $duration;
        $types[] = PDO::PARAM_INT;
    }
    
    $sql .= " ORDER BY t.price";
    
    $stmt = $conn->prepare($sql);
    
    for ($i = 0; $i < count($params); $i++) {
        $stmt->bindParam($i + 1, $params[$i], $types[$i]);
    }
    
    $stmt->execute();
    
    $tours = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $tours[] = $row;
    }
    
    return $tours;
}

// Function to get user by ID
function getUserById($userId) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM users WHERE user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $user = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user = $row;
    }
    
    return $user;
}

// Function to get user bookings
function getUserBookings($userId) {
    $conn = connectDatabase();
    $sql = "SELECT b.*, t.title as tour_title, t.image as tour_image, 
            d.name as destination_name, ts.start_date, ts.end_date, ts.status as schedule_status
            FROM bookings b
            JOIN tours t ON b.tour_id = t.tour_id
            JOIN destinations d ON t.destination_id = d.destination_id
            JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
            WHERE b.user_id = ?
            ORDER BY b.booking_date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $bookings = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $bookings[] = $row;
    }
    
    return $bookings;
}

// Function to get user wishlist
function getUserWishlist($userId) {
    $conn = connectDatabase();
    $sql = "SELECT w.*, t.title, t.description, t.price, t.duration, t.image,
            d.name as destination_name
            FROM wishlist w
            JOIN tours t ON w.tour_id = t.tour_id
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $wishlist = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $wishlist[] = $row;
    }
    
    return $wishlist;
}

// Function to check if a tour is in user's wishlist
function isInWishlist($userId, $tourId) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM wishlist WHERE user_id = ? AND tour_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $tourId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->rowCount() > 0;
}

// Function to add tour to wishlist
function addToWishlist($userId, $tourId) {
    $conn = connectDatabase();
    
    // Check if already in wishlist
    if (isInWishlist($userId, $tourId)) {
        return true;
    }
    
    $sql = "INSERT INTO wishlist (user_id, tour_id) VALUES (?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $tourId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Function to remove tour from wishlist
function removeFromWishlist($userId, $tourId) {
    $conn = connectDatabase();
    $sql = "DELETE FROM wishlist WHERE user_id = ? AND tour_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $tourId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Function to get user notifications
function getUserNotifications($userId, $limit = 5) {
    $conn = connectDatabase();
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $notifications = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $notifications[] = $row;
    }
    
    return $notifications;
}

// Function to mark notification as read
function markNotificationAsRead($notificationId) {
    $conn = connectDatabase();
    $sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $notificationId, PDO::PARAM_INT);
    return $stmt->execute();
}

// Function to count unread notifications
function countUnreadNotifications($userId) {
    $conn = connectDatabase();
    $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->execute();
    
    $count = 0;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count = $row['count'];
    }
    
    return $count;
}

// Function to add a notification
function addNotification($userId, $title, $message, $type = 'system') {
    $conn = connectDatabase();
    $sql = "INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $userId, PDO::PARAM_INT);
    $stmt->bindParam(2, $title, PDO::PARAM_STR);
    $stmt->bindParam(3, $message, PDO::PARAM_STR);
    $stmt->bindParam(4, $type, PDO::PARAM_STR);
    return $stmt->execute();
}

// Function to get active discount codes
function getActiveDiscountCodes() {
    $conn = connectDatabase();
    $currentDate = date('Y-m-d');
    
    $sql = "SELECT * FROM discount_codes 
            WHERE is_active = 1 
            AND start_date <= ? 
            AND end_date >= ?
            AND (usage_limit IS NULL OR usage_count < usage_limit)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(2, $currentDate, PDO::PARAM_STR);
    $stmt->execute();
    
    $discountCodes = [];
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $discountCodes[] = $row;
    }
    
    return $discountCodes;
}

// Function to validate discount code
function validateDiscountCode($code, $purchaseAmount) {
    $conn = connectDatabase();
    $currentDate = date('Y-m-d');
    
    $sql = "SELECT * FROM discount_codes 
            WHERE code = ? 
            AND is_active = 1 
            AND start_date <= ? 
            AND end_date >= ?
            AND min_purchase <= ?
            AND (usage_limit IS NULL OR usage_count < usage_limit)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $code, PDO::PARAM_STR);
    $stmt->bindParam(2, $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(3, $currentDate, PDO::PARAM_STR);
    $stmt->bindParam(4, $purchaseAmount, PDO::PARAM_STR);
    $stmt->execute();
    
    $discountCode = null;
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $discountCode = $row;
    }
    
    return $discountCode;
}

// Function to apply discount
function applyDiscount($amount, $discountCode) {
    if (!$discountCode) {
        return $amount;
    }
    
    if ($discountCode['discount_type'] == 'percentage') {
        $discountAmount = $amount * ($discountCode['discount_value'] / 100);
    } else {
        $discountAmount = $discountCode['discount_value'];
    }
    
    // Ensure discount doesn't exceed the original amount
    $discountAmount = min($discountAmount, $amount);
    
    return $discountAmount;
}

// Function to increment discount code usage
function incrementDiscountCodeUsage($code) {
    $conn = connectDatabase();
    $sql = "UPDATE discount_codes SET usage_count = usage_count + 1 WHERE code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $code, PDO::PARAM_STR);
    return $stmt->execute();
}

// Function to format currency
function formatCurrency($amount) {
    return '₹' . number_format($amount, 2);
}

// Function to format date
function formatDate($date) {
    return date('d M Y', strtotime($date));
}

// Function to calculate booking amount
function calculateBookingAmount($tourPrice, $numPeople, $priceAdjustment = 0) {
    $baseAmount = ($tourPrice + $priceAdjustment) * $numPeople;
    $taxRate = 0.05; // 5% tax
    $taxAmount = $baseAmount * $taxRate;
    $totalAmount = $baseAmount + $taxAmount;
    
    return [
        'base_amount' => $baseAmount,
        'tax_amount' => $taxAmount,
        'total_amount' => $totalAmount
    ];
}

// Function to get image URL with proper path
function getImageUrl($imagePath, $type = 'destination') {
    if (empty($imagePath)) {
        return '/assets/images/placeholder.jpg';
    }
    
    // Check if the image path already has a directory
    if (strpos($imagePath, '/') !== false) {
        return $imagePath;
    }
    
    // Map type to directory
    $directories = [
        'destination' => '/assets/images/destinations/',
        'tour' => '/assets/images/tours/',
        'hotel' => '/assets/images/hotels/',
        'user' => '/assets/images/users/'
    ];
    
    $directory = isset($directories[$type]) ? $directories[$type] : '/assets/images/';
    
    // Check if file exists, otherwise return placeholder
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $directory . $imagePath;
    if (file_exists($filePath)) {
        return $directory . $imagePath;
    } else {
        return '/assets/images/placeholder.jpg';
    }
}

// Function to get image path with fallback
function getImagePath($image, $type = 'tours') {
    if (empty($image)) {
        return 'assets/images/placeholder.jpg';
    }
    
    // Check if the image path already has a directory structure
    if (strpos($image, '/') !== false) {
        return $image;
    }
    
    // Check if image exists in uploads directory
    $uploadsPath = "assets/images/uploads/{$type}/{$image}";
    if (file_exists($uploadsPath)) {
        return $uploadsPath;
    }
    
    // Check if image exists in regular directory
    $regularPath = "assets/images/{$type}/{$image}";
    if (file_exists($regularPath)) {
        return $regularPath;
    }
    
    // Return placeholder if image not found
    return 'assets/images/placeholder.jpg';
}

// Function to get Indian regions
function getIndianRegions() {
    return [
        'South India' => ['Kerala', 'Tamil Nadu', 'Karnataka', 'Andhra Pradesh', 'Telangana'],
        'North India' => ['Rajasthan', 'Himachal Pradesh', 'Uttarakhand', 'Uttar Pradesh', 'Delhi', 'Jammu & Kashmir', 'Punjab'],
        'East India' => ['West Bengal', 'Odisha', 'Bihar', 'Jharkhand'],
        'West India' => ['Goa', 'Maharashtra', 'Gujarat'],
        'Northeast India' => ['Assam', 'Sikkim', 'Meghalaya', 'Arunachal Pradesh', 'Nagaland', 'Manipur', 'Mizoram', 'Tripura'],
        'Islands' => ['Andaman & Nicobar Islands', 'Lakshadweep']
    ];
}

// Function to get popular Indian destinations
function getPopularIndianDestinations() {
    return ['Kerala', 'Rajasthan', 'Goa', 'Himachal Pradesh', 'Tamil Nadu'];
}

// Function to get region color for placeholder images
function getRegionColor($region) {
    $colors = [
        'South India' => '#4CAF50', // Green
        'North India' => '#2196F3', // Blue
        'East India' => '#FFC107', // Yellow
        'West India' => '#FF9800', // Orange
        'Northeast India' => '#9C27B0', // Purple
        'Islands' => '#00BCD4' // Cyan
    ];
    
    return isset($colors[$region]) ? $colors[$region] : '#607D8B'; // Default to gray
}

// Function to generate placeholder image path for a destination
function generatePlaceholderPath($destinationName, $type = 'destination') {
    $regions = getIndianRegions();
    $region = '';
    
    // Find which region the destination belongs to
    foreach ($regions as $regionName => $destinations) {
        if (in_array($destinationName, $destinations)) {
            $region = $regionName;
            break;
        }
    }
    
    $color = getRegionColor($region);
    $colorCode = str_replace('#', '', $color);
    
    return "/assets/images/placeholders/{$type}-{$colorCode}.jpg";
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Function to redirect with message
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $url");
    exit;
}

// Function to display message
function displayMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'];
        
        echo "<div class='alert alert-$type'>$message</div>";
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?>
