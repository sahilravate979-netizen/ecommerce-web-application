<?php

session_start();

require_once "config/database.php";


/* ---------------------------------------------
   LOGIN CHECK
--------------------------------------------- */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* ---------------------------------------------
   GET USER ORDERS
--------------------------------------------- */

$stmt = $conn->prepare(
    "SELECT
        id,
        total,
        status,
        address,
        created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

$stmt->bind_param("i", $user_id);

$stmt->execute();

$result = $stmt->get_result();

$orders = [];

while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

$stmt->close();

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Orders - E-Commerce</title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

    <style>

        /* -----------------------------------------
           ORDERS PAGE
        ----------------------------------------- */

        .orders-page {

            min-height: 100vh;

            padding: 50px 7%;

            background:
                linear-gradient(
                    135deg,
                    #f5f7ff,
                    #eef2ff,
                    #f8fafc
                );
        }


        .orders-header {

            text-align: center;

            margin-bottom: 40px;
        }


        .orders-header h1 {

            font-size: 38px;

            color: #111827;

            margin-bottom: 10px;
        }


        .orders-header p {

            color: #6b7280;

            font-size: 16px;
        }


        /* -----------------------------------------
           SUCCESS MESSAGE
        ----------------------------------------- */

        .success-message {

            max-width: 1100px;

            margin: 0 auto 25px;

            padding: 18px 22px;

            background: #dcfce7;

            color: #166534;

            border: 1px solid #86efac;

            border-radius: 12px;

            font-weight: 600;
        }


        /* -----------------------------------------
           ORDER CARD
        ----------------------------------------- */

        .orders-container {

            max-width: 1100px;

            margin: auto;
        }


        .order-card {

            background: white;

            border-radius: 18px;

            padding: 25px;

            margin-bottom: 25px;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.08);

            transition: 0.3s;
        }


        .order-card:hover {

            transform: translateY(-3px);

            box-shadow:
                0 15px 40px
                rgba(0, 0, 0, 0.12);
        }


        .order-top {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            padding-bottom: 18px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .order-id {

            font-size: 20px;

            font-weight: 800;

            color: #111827;
        }


        .order-date {

            color: #6b7280;

            font-size: 14px;

            margin-top: 5px;
        }


        /* -----------------------------------------
           STATUS
        ----------------------------------------- */

        .status {

            display: inline-block;

            padding: 8px 14px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: 700;
        }


        .status-pending {

            background: #fef3c7;

            color: #92400e;
        }


        .status-confirmed {

            background: #dbeafe;

            color: #1d4ed8;
        }


        .status-shipped {

            background: #e0e7ff;

            color: #4338ca;
        }


        .status-delivered {

            background: #dcfce7;

            color: #166534;
        }


        .status-cancelled {

            background: #fee2e2;

            color: #b91c1c;
        }


        /* -----------------------------------------
           ORDER DETAILS
        ----------------------------------------- */

        .order-details {

            display: grid;

            grid-template-columns:
                1fr 1fr 1fr;

            gap: 20px;

            padding: 20px 0;
        }


        .detail-box {

            background: #f8fafc;

            padding: 15px;

            border-radius: 12px;

            border: 1px solid #e5e7eb;
        }


        .detail-box span {

            display: block;

            font-size: 13px;

            color: #6b7280;

            margin-bottom: 6px;
        }


        .detail-box strong {

            color: #111827;

            font-size: 16px;
        }


        /* -----------------------------------------
           ADDRESS
        ----------------------------------------- */

        .address-box {

            background: #f8fafc;

            padding: 15px;

            border-radius: 12px;

            margin-top: 5px;

            border: 1px solid #e5e7eb;
        }


        .address-box strong {

            display: block;

            margin-bottom: 7px;

            color: #374151;
        }


        .address-box p {

            margin: 0;

            color: #6b7280;

            line-height: 1.6;
        }


        /* -----------------------------------------
           EMPTY ORDERS
        ----------------------------------------- */

        .empty-orders {

            background: white;

            max-width: 700px;

            margin: auto;

            padding: 70px 30px;

            text-align: center;

            border-radius: 18px;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.08);
        }


        .empty-icon {

            font-size: 70px;

            margin-bottom: 20px;
        }


        .empty-orders h2 {

            color: #111827;

            margin-bottom: 10px;
        }


        .empty-orders p {

            color: #6b7280;

            margin-bottom: 25px;
        }


        .shop-btn {

            display: inline-block;

            padding: 13px 25px;

            background: #2563eb;

            color: white;

            text-decoration: none;

            border-radius: 10px;

            font-weight: 700;
        }


        .shop-btn:hover {

            background: #1d4ed8;
        }


        /* -----------------------------------------
           RESPONSIVE
        ----------------------------------------- */

        @media (max-width: 800px) {

            .order-details {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .orders-page {

                padding: 30px 15px;
            }


            .order-top {

                flex-direction: column;

                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<!-- NAVBAR -->

<nav class="store-navbar">

    <div class="store-logo">

        🛒 MyStore

    </div>


    <div class="store-nav-links">

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="cart.php">
            🛒 Cart
        </a>

        <a
            href="orders.php"
            class="active"
        >
            My Orders
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>


<!-- ORDERS PAGE -->

<section class="orders-page">


    <div class="orders-header">

        <h1>
            📦 My Orders
        </h1>

        <p>
            Track and view all your orders.
        </p>

    </div>


    <?php if (
        isset($_GET["success"])
        &&
        $_GET["success"] == "1"
    ): ?>

        <div class="success-message">

            ✅ Your order has been placed successfully!

            <?php if (isset($_GET["order_id"])): ?>

                Order ID:
                <strong>
                    #<?php
                    echo intval($_GET["order_id"]);
                    ?>
                </strong>

            <?php endif; ?>

        </div>

    <?php endif; ?>


    <?php if (empty($orders)): ?>


        <div class="empty-orders">

            <div class="empty-icon">
                📦
            </div>

            <h2>
                No Orders Yet
            </h2>

            <p>
                You haven't placed any orders yet.
            </p>

            <a
                href="products.php"
                class="shop-btn"
            >
                Start Shopping
            </a>

        </div>


    <?php else: ?>


        <div class="orders-container">


            <?php foreach ($orders as $order): ?>


                <?php

                $status_class =
                    strtolower(
                        $order["status"]
                    );

                ?>


                <div class="order-card">


                    <!-- ORDER HEADER -->

                    <div class="order-top">


                        <div>

                            <div class="order-id">

                                Order #<?php
                                echo $order["id"];
                                ?>

                            </div>


                            <div class="order-date">

                                Placed on
                                <?php
                                echo date(
                                    "d M Y, h:i A",
                                    strtotime(
                                        $order["created_at"]
                                    )
                                );
                                ?>

                            </div>

                        </div>


                        <span
                            class="
                                status
                                status-<?php
                                echo $status_class;
                                ?>
                            "
                        >

                            <?php
                            echo htmlspecialchars(
                                $order["status"]
                            );
                            ?>

                        </span>


                    </div>


                    <!-- ORDER DETAILS -->

                    <div class="order-details">


                        <div class="detail-box">

                            <span>
                                Order Amount
                            </span>

                            <strong>

                                ₹<?php
                                echo number_format(
                                    $order["total"],
                                    2
                                );
                                ?>

                            </strong>

                        </div>


                        <div class="detail-box">

                            <span>
                                Payment
                            </span>

                            <strong>
                                Cash on Delivery
                            </strong>

                        </div>


                        <div class="detail-box">

                            <span>
                                Order Status
                            </span>

                            <strong>
                                <?php
                                echo htmlspecialchars(
                                    $order["status"]
                                );
                                ?>
                            </strong>

                        </div>


                    </div>


                    <!-- ADDRESS -->

                    <div class="address-box">

                        <strong>
                            📍 Delivery Address
                        </strong>

                        <p>

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $order["address"]
                                )
                            );
                            ?>

                        </p>

                    </div>


                </div>


            <?php endforeach; ?>


        </div>


    <?php endif; ?>


</section>


</body>

</html>