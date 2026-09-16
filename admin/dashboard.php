<?php
session_start();

require_once "../config/database.php";

/* -----------------------------------
   ADMIN ACCESS CHECK
----------------------------------- */

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}

/* -----------------------------------
   ADMIN INFORMATION
----------------------------------- */

$admin_name = $_SESSION["user_name"] ?? "Admin";

/* -----------------------------------
   DASHBOARD STATISTICS
----------------------------------- */

// Total normal users
$user_result = $conn->query("
    SELECT COUNT(*) AS total_users
    FROM users
    WHERE role = 'user'
");

$total_users = 0;

if ($user_result) {
    $row = $user_result->fetch_assoc();
    $total_users = $row["total_users"];
}


// Total products
$product_result = $conn->query("
    SELECT COUNT(*) AS total_products
    FROM products
");

$total_products = 0;

if ($product_result) {
    $row = $product_result->fetch_assoc();
    $total_products = $row["total_products"];
}


// Total orders
$order_result = $conn->query("
    SELECT COUNT(*) AS total_orders
    FROM orders
");

$total_orders = 0;

if ($order_result) {
    $row = $order_result->fetch_assoc();
    $total_orders = $row["total_orders"];
}


// Total sales
// Your orders table uses "total", NOT "total_amount"
$sales_result = $conn->query("
    SELECT COALESCE(SUM(total), 0) AS total_sales
    FROM orders
    WHERE status != 'Cancelled'
");

$total_sales = 0;

if ($sales_result) {
    $row = $sales_result->fetch_assoc();
    $total_sales = $row["total_sales"];
}


/* -----------------------------------
   RECENT ORDERS
----------------------------------- */

$recent_orders = $conn->query("
    SELECT 
        orders.id,
        orders.total,
        orders.status,
        orders.created_at,
        users.name
    FROM orders
    LEFT JOIN users ON orders.user_id = users.id
    ORDER BY orders.created_at DESC
    LIMIT 5
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - E-Commerce</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>


<body class="admin-body">


<!-- =====================================
     ADMIN SIDEBAR
===================================== -->

<aside class="admin-sidebar">

    <div class="admin-logo">

        <h2>🛒 E-Commerce</h2>

        <p>Admin Panel</p>

    </div>


    <nav class="admin-nav">

        <a href="dashboard.php" class="active">
            📊 Dashboard
        </a>

        <a href="products.php">
            📦 Products
        </a>

        <a href="add_product.php">
            ➕ Add Product
        </a>

        <a href="users.php">
            👥 Users
        </a>

        <a href="orders.php">
            🛍️ Orders
        </a>

        <a href="../index.php">
            🏠 Visit Store
        </a>

        <a href="../logout.php">
            🚪 Logout
        </a>

    </nav>

</aside>



<!-- =====================================
     MAIN CONTENT
===================================== -->

<main class="admin-main">


    <!-- HEADER -->

    <header class="admin-header">

        <div>

            <h1>Admin Dashboard</h1>

            <p>
                Welcome back,
                <strong><?php echo htmlspecialchars($admin_name); ?></strong> 👋
            </p>

        </div>


        <div class="admin-header-right">

            <span class="admin-badge">
                👑 Administrator
            </span>

        </div>

    </header>



    <!-- =================================
         STATISTICS
    ================================== -->

    <section class="admin-stats">


        <!-- USERS -->

        <div class="stat-card">

            <div class="stat-icon">
                👥
            </div>

            <div class="stat-info">

                <h3>
                    <?php echo $total_users; ?>
                </h3>

                <p>Total Users</p>

            </div>

        </div>



        <!-- PRODUCTS -->

        <div class="stat-card">

            <div class="stat-icon">
                📦
            </div>

            <div class="stat-info">

                <h3>
                    <?php echo $total_products; ?>
                </h3>

                <p>Total Products</p>

            </div>

        </div>



        <!-- ORDERS -->

        <div class="stat-card">

            <div class="stat-icon">
                🛍️
            </div>

            <div class="stat-info">

                <h3>
                    <?php echo $total_orders; ?>
                </h3>

                <p>Total Orders</p>

            </div>

        </div>



        <!-- SALES -->

        <div class="stat-card">

            <div class="stat-icon">
                💰
            </div>

            <div class="stat-info">

                <h3>
                    ₹<?php echo number_format($total_sales, 2); ?>
                </h3>

                <p>Total Sales</p>

            </div>

        </div>


    </section>



    <!-- =================================
         QUICK ACTIONS
    ================================== -->

    <section class="admin-section">

        <div class="section-heading">

            <div>

                <h2>Quick Actions</h2>

                <p>Manage your e-commerce store</p>

            </div>

        </div>


        <div class="quick-actions">


            <a href="add_product.php" class="quick-action-card">

                <div class="quick-icon">
                    ➕
                </div>

                <div>

                    <h3>Add Product</h3>

                    <p>
                        Add a new product to your store
                    </p>

                </div>

            </a>



            <a href="products.php" class="quick-action-card">

                <div class="quick-icon">
                    📦
                </div>

                <div>

                    <h3>Manage Products</h3>

                    <p>
                        View, edit or delete products
                    </p>

                </div>

            </a>



            <a href="users.php" class="quick-action-card">

                <div class="quick-icon">
                    👥
                </div>

                <div>

                    <h3>Manage Users</h3>

                    <p>
                        View registered customers
                    </p>

                </div>

            </a>



            <a href="orders.php" class="quick-action-card">

                <div class="quick-icon">
                    🛍️
                </div>

                <div>

                    <h3>Manage Orders</h3>

                    <p>
                        View and update customer orders
                    </p>

                </div>

            </a>


        </div>

    </section>



    <!-- =================================
         RECENT ORDERS
    ================================== -->

    <section class="admin-section">

        <div class="section-heading">

            <div>

                <h2>Recent Orders</h2>

                <p>Latest orders placed by customers</p>

            </div>


            <a href="orders.php" class="btn btn-primary">
                View All Orders
            </a>

        </div>



        <div class="admin-table-container">

            <table class="admin-table">

                <thead>

                    <tr>

                        <th>Order ID</th>

                        <th>Customer</th>

                        <th>Total</th>

                        <th>Status</th>

                        <th>Date</th>

                    </tr>

                </thead>


                <tbody>

                <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>

                    <?php while ($order = $recent_orders->fetch_assoc()): ?>

                        <tr>

                            <td>
                                #<?php echo $order["id"]; ?>
                            </td>


                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $order["name"] ?? "Unknown Customer"
                                );
                                ?>
                            </td>


                            <td>
                                ₹<?php
                                echo number_format(
                                    $order["total"],
                                    2
                                );
                                ?>
                            </td>


                            <td>

                                <?php

                                $status = $order["status"];

                                $status_class = strtolower($status);

                                ?>

                                <span class="status-badge status-<?php echo $status_class; ?>">

                                    <?php echo htmlspecialchars($status); ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    "d M Y",
                                    strtotime($order["created_at"])
                                );

                                ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>


                <?php else: ?>

                    <tr>

                        <td colspan="5" class="no-data">

                            📭 No orders found.

                        </td>

                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- =================================
         ADMIN INFORMATION
    ================================== -->

    <section class="admin-info-panel">

        <div class="info-icon">
            💡
        </div>


        <div>

            <h3>Admin Panel</h3>

            <p>
                From this dashboard you can manage products,
                customers and orders for your e-commerce website.
            </p>

        </div>

    </section>


</main>


</body>

</html>