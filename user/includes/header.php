<?php
// Get unread notification count
$unread_count = 0;
try {
    $notification_query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = :user_id AND is_read = 0";
    $notification_stmt = $db->prepare($notification_query);
    $notification_stmt->bindParam(":user_id", $user_id);
    $notification_stmt->execute();
    $notification_result = $notification_stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = $notification_result['unread_count'];
} catch (Exception $e) {
    // Silently fail
}

// Check for sidebar state cookie
$sidebar_collapsed = isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TourSync - User Dashboard</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/user.css">
    
    <!-- Preload critical resources -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" as="style">
    <link rel="preload" href="../assets/images/pattern.svg" as="image">
    
    <!-- Inline critical CSS -->
    <style>
        body {
            visibility: visible !important;
            opacity: 1 !important;
        }
        .loading-overlay {
            display: none !important;
        }
    </style>
</head>
<body class="<?php echo $sidebar_collapsed ? 'sidebar-collapsed' : ''; ?>">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-md navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <i class="fas fa-globe-americas me-2"></i>
                TourGrid
            </a>
            
            <!-- Sidebar Toggle Button -->
            <button id="sidebarToggleBtn" class="sidebar-toggle-btn d-none d-md-flex" type="button" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav ms-auto mb-2 mb-md-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bell"></i>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                            <li id="notificationPlaceholder"><span class="dropdown-item text-center">Loading notifications...</span></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="bookings.php"><i class="fas fa-calendar-check me-2"></i>My Bookings</a></li>
                            <li><a class="dropdown-item" href="wishlist.php"><i class="fas fa-heart me-2"></i>Wishlist</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Start of dashboard container -->
    <div class="dashboard-container">
