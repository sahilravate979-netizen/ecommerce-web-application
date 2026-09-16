<?php
session_start();

require_once "../config/database.php";

/* =========================================
   ADMIN ACCESS CHECK
========================================= */

if (!isset($_SESSION["user_id"]) || $_SESSION["user_role"] !== "admin") {
    header("Location: ../login.php");
    exit();
}


/* =========================================
   UPDATE ORDER STATUS
========================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_status"])) {

    $order_id = intval($_POST["order_id"]);
    $status = $_POST["status"];

    $allowed_statuses = [
        "Pending",
        "Confirmed",
        "Shipped",
        "Delivered",
        "Cancelled"
    ];

    if (in_array($status, $allowed_statuses, true)) {

        $stmt = $conn->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");

        $stmt->bind_param("si", $status, $order_id);

        $stmt->execute();

        $stmt->close();
    }

    header("Location: orders.php?updated=1");
    exit();
}


/* =========================================
   ORDER STATISTICS
========================================= */

$total_orders = 0;
$pending_orders = 0;
$confirmed_orders = 0;
$shipped_orders = 0;
$delivered_orders = 0;
$cancelled_orders = 0;
$total_sales = 0;


$stats_result = $conn->query("
    SELECT
        COUNT(*) AS total_orders,

        SUM(
            CASE
                WHEN status = 'Pending'
                THEN 1
                ELSE 0
            END
        ) AS pending_orders,

        SUM(
            CASE
                WHEN status = 'Confirmed'
                THEN 1
                ELSE 0
            END
        ) AS confirmed_orders,

        SUM(
            CASE
                WHEN status = 'Shipped'
                THEN 1
                ELSE 0
            END
        ) AS shipped_orders,

        SUM(
            CASE
                WHEN status = 'Delivered'
                THEN 1
                ELSE 0
            END
        ) AS delivered_orders,

        SUM(
            CASE
                WHEN status = 'Cancelled'
                THEN 1
                ELSE 0
            END
        ) AS cancelled_orders,

        COALESCE(
            SUM(
                CASE
                    WHEN status != 'Cancelled'
                    THEN total
                    ELSE 0
                END
            ),
            0
        ) AS total_sales

    FROM orders
");


if ($stats_result) {

    $stats = $stats_result->fetch_assoc();

    $total_orders = $stats["total_orders"] ?? 0;
    $pending_orders = $stats["pending_orders"] ?? 0;
    $confirmed_orders = $stats["confirmed_orders"] ?? 0;
    $shipped_orders = $stats["shipped_orders"] ?? 0;
    $delivered_orders = $stats["delivered_orders"] ?? 0;
    $cancelled_orders = $stats["cancelled_orders"] ?? 0;
    $total_sales = $stats["total_sales"] ?? 0;
}


/* =========================================
   GET ORDERS
========================================= */

$orders_result = $conn->query("
    SELECT
        orders.id,
        orders.user_id,
        orders.total,
        orders.status,
        orders.address,
        orders.created_at,
        users.name,
        users.email

    FROM orders

    LEFT JOIN users
        ON orders.user_id = users.id

    ORDER BY orders.id DESC
");

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Orders | ShopEase Admin</title>

    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >

</head>


<body class="modern-admin">


<!-- =========================================
     SIDEBAR
========================================= -->

<aside class="modern-sidebar">

    <div class="sidebar-brand">

        <div class="brand-icon">
            🛒
        </div>

        <div>

            <h2>ShopEase</h2>

            <span>Admin Panel</span>

        </div>

    </div>


    <nav class="modern-nav">

        <a href="dashboard.php">

            <span class="nav-icon">⌂</span>

            <span>Dashboard</span>

        </a>


        <a href="products.php">

            <span class="nav-icon">▣</span>

            <span>Products</span>

        </a>


        <a href="add_product.php">

            <span class="nav-icon">⊕</span>

            <span>Add Product</span>

        </a>


        <a href="users.php">

            <span class="nav-icon">♟</span>

            <span>Users</span>

        </a>


        <a href="orders.php" class="active">

            <span class="nav-icon">🛒</span>

            <span>Orders</span>

        </a>


        <a href="../index.php">

            <span class="nav-icon">⌂</span>

            <span>Visit Store</span>

        </a>


        <a href="../logout.php">

            <span class="nav-icon">⇥</span>

            <span>Logout</span>

        </a>

    </nav>


    <div class="sidebar-bottom">

        <div class="sidebar-bottom-icon">
            🛍️
        </div>

        <strong>
            Manage • Grow • Succeed
        </strong>

        <small>
            Your E-Commerce Store
        </small>

    </div>

</aside>



<!-- =========================================
     MAIN
========================================= -->

<main class="modern-main">


    <!-- TOP BAR -->

    <header class="modern-topbar">

        <div></div>


        <div class="topbar-user">

            <div class="notification">

                🔔

                <span>
                    <?php echo $pending_orders; ?>
                </span>

            </div>


            <div class="user-avatar">
                👤
            </div>


            <div class="user-details">

                <strong>
                    Administrator
                </strong>

                <small>
                    Admin Account
                </small>

            </div>


            <span class="dropdown-arrow">
                ▾
            </span>

        </div>

    </header>



    <!-- =========================================
         CONTENT
    ========================================= -->

    <div class="modern-content">


        <!-- PAGE TITLE -->

        <div class="page-title-row">

            <div class="page-title">

                <div class="title-icon">
                    🛒
                </div>


                <div>

                    <h1>
                        Manage Orders
                    </h1>

                    <p>
                        View orders and update their delivery status.
                    </p>

                </div>

            </div>

        </div>



        <!-- =====================================
             STATISTICS
        ====================================== -->

        <section class="modern-stats">


            <!-- TOTAL -->

            <div class="modern-stat-card blue-card">

                <div class="stat-round-icon">
                    🛒
                </div>


                <div class="modern-stat-content">

                    <span>
                        Total Orders
                    </span>

                    <strong>
                        <?php echo $total_orders; ?>
                    </strong>

                    <small>
                        ● All orders
                    </small>

                </div>


                <div class="stat-arrow">
                    ›
                </div>

            </div>



            <!-- PENDING -->

            <div class="modern-stat-card red-card">

                <div class="stat-round-icon">
                    ⏳
                </div>


                <div class="modern-stat-content">

                    <span>
                        Pending
                    </span>

                    <strong>
                        <?php echo $pending_orders; ?>
                    </strong>

                    <small>
                        ● Awaiting confirmation
                    </small>

                </div>


                <div class="stat-arrow">
                    ›
                </div>

            </div>



            <!-- SHIPPED -->

            <div class="modern-stat-card purple-card">

                <div class="stat-round-icon">
                    🚚
                </div>


                <div class="modern-stat-content">

                    <span>
                        Shipped
                    </span>

                    <strong>
                        <?php echo $shipped_orders; ?>
                    </strong>

                    <small>
                        ● On the way
                    </small>

                </div>


                <div class="stat-arrow">
                    ›
                </div>

            </div>



            <!-- SALES -->

            <div class="modern-stat-card green-card">

                <div class="stat-round-icon">
                    ₹
                </div>


                <div class="modern-stat-content">

                    <span>
                        Total Sales
                    </span>

                    <strong>
                        ₹<?php echo number_format($total_sales, 2); ?>
                    </strong>

                    <small>
                        ● Excluding cancelled
                    </small>

                </div>


                <div class="stat-arrow">
                    ›
                </div>

            </div>


        </section>



        <!-- =====================================
             ORDERS PANEL
        ====================================== -->

        <section class="products-panel">


            <!-- PANEL HEADER -->

            <div class="products-panel-header">


                <div class="all-products-title">

                    <div class="mini-box-icon">
                        🛒
                    </div>


                    <div>

                        <h2>
                            All Orders
                        </h2>

                        <p>
                            Manage customer orders and delivery status.
                        </p>

                    </div>

                </div>



                <!-- FILTER -->

                <div class="product-tools">

                    <div class="product-search">

                        <span>
                            ⌕
                        </span>

                        <input
                            type="text"
                            id="orderSearch"
                            placeholder="Search orders..."
                            onkeyup="searchOrders()"
                        >

                    </div>


                    <select
                        id="orderFilter"
                        onchange="filterOrders()"
                    >

                        <option value="all">
                            All Orders
                        </option>

                        <option value="Pending">
                            Pending
                        </option>

                        <option value="Confirmed">
                            Confirmed
                        </option>

                        <option value="Shipped">
                            Shipped
                        </option>

                        <option value="Delivered">
                            Delivered
                        </option>

                        <option value="Cancelled">
                            Cancelled
                        </option>

                    </select>

                </div>

            </div>



            <!-- SUCCESS MESSAGE -->

            <?php if (isset($_GET["updated"])): ?>

                <div class="modern-success">

                    ✓
                    Order status updated successfully!

                </div>

            <?php endif; ?>



            <!-- =====================================
                 ORDERS TABLE
            ====================================== -->

            <div class="modern-table-wrapper">

                <table
                    class="modern-product-table order-table"
                    id="orderTable"
                >

                    <thead>

                        <tr>

                            <th>
                                Order
                            </th>

                            <th>
                                Customer
                            </th>

                            <th>
                                Amount
                            </th>

                            <th>
                                Address
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Update
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($orders_result && $orders_result->num_rows > 0): ?>


                        <?php while ($order = $orders_result->fetch_assoc()): ?>


                            <tr
                                data-status="<?php echo htmlspecialchars($order["status"]); ?>"
                            >


                                <!-- ORDER ID -->

                                <td>

                                    <span class="order-id-badge">

                                        #ORD-<?php
                                        echo str_pad(
                                            $order["id"],
                                            4,
                                            "0",
                                            STR_PAD_LEFT
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- CUSTOMER -->

                                <td>

                                    <div class="user-table-cell">

                                        <div class="table-user-avatar">

                                            <?php

                                            $name =
                                                $order["name"]
                                                ?? "User";

                                            echo htmlspecialchars(
                                                strtoupper(
                                                    substr(
                                                        $name,
                                                        0,
                                                        1
                                                    )
                                                )
                                            );

                                            ?>

                                        </div>


                                        <div>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $name
                                                );

                                                ?>

                                            </strong>


                                            <small>

                                                <?php

                                                echo htmlspecialchars(
                                                    $order["email"]
                                                    ?? "No email"
                                                );

                                                ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>



                                <!-- AMOUNT -->

                                <td>

                                    <strong class="product-price">

                                        ₹<?php

                                        echo number_format(
                                            $order["total"],
                                            2
                                        );

                                        ?>

                                    </strong>

                                </td>



                                <!-- ADDRESS -->

                                <td>

                                    <div class="order-address">

                                        📍

                                        <span>

                                            <?php

                                            $address =
                                                $order["address"];

                                            echo htmlspecialchars(
                                                strlen($address) > 45
                                                    ? substr(
                                                        $address,
                                                        0,
                                                        45
                                                    ) . "..."
                                                    : $address
                                            );

                                            ?>

                                        </span>

                                    </div>

                                </td>



                                <!-- DATE -->

                                <td>

                                    <div class="created-date">

                                        <span>
                                            ▣
                                        </span>


                                        <div>

                                            <strong>

                                                <?php

                                                echo date(
                                                    "d M Y",
                                                    strtotime(
                                                        $order["created_at"]
                                                    )
                                                );

                                                ?>

                                            </strong>


                                            <small>

                                                <?php

                                                echo date(
                                                    "h:i A",
                                                    strtotime(
                                                        $order["created_at"]
                                                    )
                                                );

                                                ?>

                                            </small>

                                        </div>

                                    </div>

                                </td>



                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $status_class =
                                        strtolower(
                                            $order["status"]
                                        );

                                    ?>


                                    <span
                                        class="order-status <?php echo $status_class; ?>"
                                    >

                                        <?php

                                        if (
                                            $order["status"]
                                            === "Pending"
                                        ) {

                                            echo "⏳";

                                        } elseif (
                                            $order["status"]
                                            === "Confirmed"
                                        ) {

                                            echo "✓";

                                        } elseif (
                                            $order["status"]
                                            === "Shipped"
                                        ) {

                                            echo "🚚";

                                        } elseif (
                                            $order["status"]
                                            === "Delivered"
                                        ) {

                                            echo "✓";

                                        } else {

                                            echo "✕";

                                        }

                                        ?>


                                        <?php
                                        echo htmlspecialchars(
                                            $order["status"]
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- UPDATE -->

                                <td>

                                    <form
                                        method="POST"
                                        class="status-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="order_id"
                                            value="<?php
                                            echo $order["id"];
                                            ?>"
                                        >


                                        <select
                                            name="status"
                                            onchange="this.form.submit()"
                                        >

                                            <option
                                                value="Pending"
                                                <?php
                                                echo $order["status"] === "Pending"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Pending
                                            </option>


                                            <option
                                                value="Confirmed"
                                                <?php
                                                echo $order["status"] === "Confirmed"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Confirmed
                                            </option>


                                            <option
                                                value="Shipped"
                                                <?php
                                                echo $order["status"] === "Shipped"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Shipped
                                            </option>


                                            <option
                                                value="Delivered"
                                                <?php
                                                echo $order["status"] === "Delivered"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Delivered
                                            </option>


                                            <option
                                                value="Cancelled"
                                                <?php
                                                echo $order["status"] === "Cancelled"
                                                    ? "selected"
                                                    : "";
                                                ?>
                                            >
                                                Cancelled
                                            </option>

                                        </select>


                                        <input
                                            type="hidden"
                                            name="update_status"
                                            value="1"
                                        >

                                    </form>

                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="7"
                                class="empty-products"
                            >

                                <div>
                                    🛒
                                </div>

                                <h3>
                                    No Orders Found
                                </h3>

                                <p>
                                    Customer orders will appear here.
                                </p>

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>


    </div>

</main>



<!-- =========================================
     JAVASCRIPT
========================================= -->

<script>

function searchOrders() {

    const search =
        document
        .getElementById("orderSearch")
        .value
        .toLowerCase();

    const rows =
        document.querySelectorAll(
            "#orderTable tbody tr"
        );

    rows.forEach(function(row) {

        const text =
            row.textContent.toLowerCase();

        if (text.includes(search)) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}


function filterOrders() {

    const filter =
        document.getElementById("orderFilter").value;

    const rows =
        document.querySelectorAll(
            "#orderTable tbody tr"
        );

    rows.forEach(function(row) {

        const status =
            row.getAttribute("data-status");

        if (
            filter === "all"
            ||
            status === filter
        ) {

            row.style.display = "";

        } else {

            row.style.display = "none";

        }

    });

}

</script>



<!-- =========================================
     ORDERS PAGE CSS
========================================= -->

<style>

.order-table {
    min-width: 1250px;
}


/* ORDER ID */

.order-id-badge {
    display: inline-flex;

    align-items: center;

    justify-content: center;

    padding: 8px 10px;

    border-radius: 8px;

    background: #edf5ff;

    color: #2464a5;

    font-size: 12px;

    font-weight: 700;

    white-space: nowrap;
}


/* CUSTOMER */

.user-table-cell {
    display: flex;

    align-items: center;

    gap: 11px;
}

.table-user-avatar {
    width: 40px;

    height: 40px;

    min-width: 40px;

    border-radius: 50%;

    display: flex;

    align-items: center;

    justify-content: center;

    background: linear-gradient(
        135deg,
        #1677ff,
        #4b7cff
    );

    color: white;

    font-weight: 700;
}

.user-table-cell strong {
    display: block;

    color: #14375f;

    font-size: 13px;
}

.user-table-cell small {
    display: block;

    margin-top: 4px;

    color: #8aa0ba;

    font-size: 10px;
}


/* ADDRESS */

.order-address {
    display: flex;

    align-items: flex-start;

    gap: 6px;

    max-width: 200px;

    color: #6682a3;

    font-size: 12px;

    line-height: 1.5;
}


/* STATUS */

.order-status {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 8px 12px;

    border-radius: 30px;

    font-size: 11px;

    font-weight: 700;

    white-space: nowrap;
}


.order-status.pending {
    background: #fff1d9;

    color: #d88600;

    border: 1px solid #ffe2ad;
}


.order-status.confirmed {
    background: #e4f0ff;

    color: #2271d1;

    border: 1px solid #cee4ff;
}


.order-status.shipped {
    background: #eee5ff;

    color: #7040d3;

    border: 1px solid #dfd0ff;
}


.order-status.delivered {
    background: #dcf8eb;

    color: #07885c;

    border: 1px solid #c5efdd;
}


.order-status.cancelled {
    background: #ffe3e5;

    color: #df303b;

    border: 1px solid #ffd0d3;
}


/* STATUS FORM */

.status-form {
    margin: 0;
}

.status-form select {
    min-width: 125px;

    padding: 9px 11px;

    border: 1px solid #d5e2f0;

    border-radius: 8px;

    background: white;

    color: #365878;

    font-size: 11px;

    font-weight: 600;

    outline: none;

    cursor: pointer;

    transition: 0.2s;
}

.status-form select:focus {
    border-color: #3d83ef;

    box-shadow:
        0 0 0 3px
        rgba(61,131,239,0.10);
}

</style>


</body>

</html>