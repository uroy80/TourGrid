<div id="sidebar" class="sidebar <?php echo $sidebar_collapsed ? 'collapsed' : ''; ?>">
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                <i class="fas fa-calendar-check"></i>
                <span>My Bookings</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'wishlist.php' ? 'active' : ''; ?>" href="wishlist.php">
                <i class="fas fa-heart"></i>
                <span>Wishlist</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reviews.php' ? 'active' : ''; ?>" href="reviews.php">
                <i class="fas fa-star"></i>
                <span>My Reviews</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-2"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'change-password.php' ? 'active' : ''; ?>" href="change-password.php">
                <i class="fas fa-key"></i>
                <span>Change Password</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-heading">Quick Links</div>
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link" href="../tours.php">
                <i class="fas fa-search"></i>
                <span>Browse Tours</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../index.php">
                <i class="fas fa-home"></i>
                <span>Back to Website</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sign Out</span>
            </a>
        </li>
    </ul>
</div>
