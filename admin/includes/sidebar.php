<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Home</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tour-package-entry.php' ? 'active' : ''; ?>" href="tour-package-entry.php">
                    <i class="fas fa-plus-circle"></i>
                    <span>Tour Package Entry</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tour-package-view.php' ? 'active' : ''; ?>" href="tour-package-view.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Tour Package View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'hotel-details-entry.php' ? 'active' : ''; ?>" href="hotel-details-entry.php">
                    <i class="fas fa-hotel"></i>
                    <span>Hotel Details Entry</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'hotel-details-view.php' ? 'active' : ''; ?>" href="hotel-details-view.php">
                    <i class="fas fa-building"></i>
                    <span>Hotel Details View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tour-schedule-entry.php' ? 'active' : ''; ?>" href="tour-schedule-entry.php">
                    <i class="fas fa-calendar-plus"></i>
                    <span>Tour Schedule Entry</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tour-schedule-view.php' ? 'active' : ''; ?>" href="tour-schedule-view.php">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Tour Schedule View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>" href="bookings.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Booking View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>" href="customers.php">
                    <i class="fas fa-users"></i>
                    <span>Customer View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="fas fa-credit-card"></i>
                    <span>Payment View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'feedback.php' ? 'active' : ''; ?>" href="feedback.php">
                    <i class="fas fa-comment-alt"></i>
                    <span>Feedback View</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Log Out</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
