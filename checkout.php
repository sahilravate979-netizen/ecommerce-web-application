<?php

session_start();

require_once "config/database.php";


/* -------------------------------------------------
   LOGIN CHECK
------------------------------------------------- */

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* -------------------------------------------------
   GET USER INFORMATION
------------------------------------------------- */

$stmt = $conn->prepare(
    "SELECT name, email
     FROM users
     WHERE id = ?"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$stmt->close();


/* -------------------------------------------------
   GET CART ITEMS
------------------------------------------------- */

$stmt = $conn->prepare(
    "SELECT
        c.id AS cart_id,
        c.quantity,
        p.id AS product_id,
        p.name,
        p.price,
        p.image,
        p.stock
     FROM cart_items c
     JOIN products p ON c.product_id = p.id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC"
);

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$cart_items = [];

while ($row = $result->fetch_assoc()) {
    $cart_items[] = $row;
}

$stmt->close();


/* -------------------------------------------------
   REDIRECT IF CART IS EMPTY
------------------------------------------------- */

if (empty($cart_items)) {
    header("Location: cart.php");
    exit();
}


/* -------------------------------------------------
   CALCULATE TOTAL
------------------------------------------------- */

$total = 0;

foreach ($cart_items as $item) {
    $total += $item["price"] * $item["quantity"];
}


/* -------------------------------------------------
   PLACE ORDER
------------------------------------------------- */

$error = "";

if (isset($_POST["place_order"])) {

    $address = trim($_POST["address"] ?? "");

    if (empty($address)) {

        $error = "Please enter your delivery address.";

    } elseif (strlen($address) < 10) {

        $error = "Please enter a complete delivery address.";

    } else {

        try {

            /* Start database transaction */
            $conn->begin_transaction();


            /* -----------------------------------------
               CHECK STOCK AGAIN
            ----------------------------------------- */

            $stock_stmt = $conn->prepare(
                "SELECT
                    c.product_id,
                    c.quantity,
                    p.name,
                    p.price,
                    p.stock
                 FROM cart_items c
                 JOIN products p ON c.product_id = p.id
                 WHERE c.user_id = ?
                 FOR UPDATE"
            );

            $stock_stmt->bind_param("i", $user_id);
            $stock_stmt->execute();

            $stock_result = $stock_stmt->get_result();

            $order_items = [];
            $final_total = 0;

            while ($item = $stock_result->fetch_assoc()) {

                if ($item["stock"] < $item["quantity"]) {

                    throw new Exception(
                        "Not enough stock available for " .
                        $item["name"] .
                        ". Available stock: " .
                        $item["stock"]
                    );
                }

                $final_total +=
                    $item["price"] * $item["quantity"];

                $order_items[] = $item;
            }

            $stock_stmt->close();


            /* -----------------------------------------
               CREATE ORDER
            ----------------------------------------- */

            $order_stmt = $conn->prepare(
                "INSERT INTO orders
                    (user_id, total, status, address)
                 VALUES
                    (?, ?, 'Pending', ?)"
            );

            $order_stmt->bind_param(
                "ids",
                $user_id,
                $final_total,
                $address
            );

            if (!$order_stmt->execute()) {
                throw new Exception(
                    "Unable to create order."
                );
            }

            $order_id = $conn->insert_id;

            $order_stmt->close();


            /* -----------------------------------------
               ADD PRODUCTS TO ORDER_ITEMS
            ----------------------------------------- */

            $item_stmt = $conn->prepare(
                "INSERT INTO order_items
                    (order_id, product_id, quantity, price)
                 VALUES
                    (?, ?, ?, ?)"
            );


            /* Update product stock */
            $stock_update_stmt = $conn->prepare(
                "UPDATE products
                 SET stock = stock - ?
                 WHERE id = ?"
            );


            foreach ($order_items as $item) {

                $product_id = $item["product_id"];
                $quantity = $item["quantity"];
                $price = $item["price"];


                /* Insert order item */

                $item_stmt->bind_param(
                    "iiid",
                    $order_id,
                    $product_id,
                    $quantity,
                    $price
                );

                if (!$item_stmt->execute()) {
                    throw new Exception(
                        "Unable to save order items."
                    );
                }


                /* Reduce product stock */

                $stock_update_stmt->bind_param(
                    "ii",
                    $quantity,
                    $product_id
                );

                if (!$stock_update_stmt->execute()) {
                    throw new Exception(
                        "Unable to update product stock."
                    );
                }
            }

            $item_stmt->close();
            $stock_update_stmt->close();


            /* -----------------------------------------
               CLEAR CART
            ----------------------------------------- */

            $clear_cart_stmt = $conn->prepare(
                "DELETE FROM cart_items
                 WHERE user_id = ?"
            );

            $clear_cart_stmt->bind_param(
                "i",
                $user_id
            );

            if (!$clear_cart_stmt->execute()) {
                throw new Exception(
                    "Unable to clear cart."
                );
            }

            $clear_cart_stmt->close();


            /* -----------------------------------------
               COMMIT EVERYTHING
            ----------------------------------------- */

            $conn->commit();


            /* -----------------------------------------
               SUCCESS
            ----------------------------------------- */

            header(
                "Location: orders.php?success=1&order_id=" .
                $order_id
            );

            exit();


        } catch (Exception $e) {

            /* Undo database changes if anything fails */
            $conn->rollback();

            $error = $e->getMessage();
        }
    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Checkout - E-Commerce</title>


    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >


    <style>

        /* -----------------------------------------
           CHECKOUT PAGE
        ----------------------------------------- */

        .checkout-page {

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


        .checkout-header {

            text-align: center;

            margin-bottom: 40px;
        }


        .checkout-header h1 {

            font-size: 38px;

            color: #111827;

            margin-bottom: 10px;
        }


        .checkout-header p {

            color: #6b7280;

            font-size: 16px;
        }


        .checkout-container {

            max-width: 1200px;

            margin: auto;

            display: grid;

            grid-template-columns:
                1fr 380px;

            gap: 30px;
        }


        .checkout-box {

            background: white;

            border-radius: 18px;

            padding: 30px;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.08);
        }


        .checkout-box h2 {

            margin-bottom: 25px;

            color: #111827;
        }


        /* -----------------------------------------
           CUSTOMER INFORMATION
        ----------------------------------------- */

        .customer-info {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;

            margin-bottom: 30px;
        }


        .info-card {

            background: #f8fafc;

            padding: 18px;

            border-radius: 12px;

            border: 1px solid #e5e7eb;
        }


        .info-card span {

            display: block;

            font-size: 13px;

            color: #6b7280;

            margin-bottom: 6px;
        }


        .info-card strong {

            color: #111827;

            word-break: break-word;
        }


        /* -----------------------------------------
           ADDRESS
        ----------------------------------------- */

        .form-group {

            margin-bottom: 20px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            font-weight: 700;

            color: #374151;
        }


        .address-input {

            width: 100%;

            min-height: 140px;

            padding: 15px;

            border: 1px solid #d1d5db;

            border-radius: 12px;

            resize: vertical;

            font-family: inherit;

            font-size: 15px;

            outline: none;
        }


        .address-input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.1);
        }


        /* -----------------------------------------
           ERROR
        ----------------------------------------- */

        .error-message {

            background: #fee2e2;

            color: #b91c1c;

            padding: 14px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        /* -----------------------------------------
           ORDER ITEMS
        ----------------------------------------- */

        .order-item {

            display: flex;

            align-items: center;

            gap: 15px;

            padding: 15px 0;

            border-bottom:
                1px solid #e5e7eb;
        }


        .order-item:last-child {

            border-bottom: none;
        }


        .order-image {

            width: 70px;

            height: 70px;

            object-fit: cover;

            border-radius: 10px;

            background: #f3f4f6;
        }


        .order-item-info {

            flex: 1;
        }


        .order-item-info h4 {

            margin: 0 0 5px;

            color: #111827;
        }


        .order-item-info p {

            margin: 0;

            color: #6b7280;

            font-size: 14px;
        }


        .order-item-price {

            font-weight: 700;

            color: #2563eb;

            white-space: nowrap;
        }


        /* -----------------------------------------
           SUMMARY
        ----------------------------------------- */

        .summary-row {

            display: flex;

            justify-content: space-between;

            padding: 12px 0;

            color: #4b5563;
        }


        .summary-total {

            display: flex;

            justify-content: space-between;

            border-top:
                1px solid #e5e7eb;

            padding-top: 20px;

            margin-top: 10px;

            font-size: 22px;

            font-weight: 800;

            color: #111827;
        }


        .place-order-btn {

            width: 100%;

            border: none;

            margin-top: 25px;

            padding: 16px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-size: 17px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.3s;
        }


        .place-order-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 10px 25px
                rgba(37, 99, 235, 0.3);
        }


        .back-cart {

            display: block;

            text-align: center;

            margin-top: 15px;

            padding: 13px;

            border: 1px solid #d1d5db;

            border-radius: 10px;

            text-decoration: none;

            color: #374151;

            font-weight: 600;
        }


        .back-cart:hover {

            background: #f3f4f6;
        }


        /* -----------------------------------------
           RESPONSIVE
        ----------------------------------------- */

        @media (max-width: 850px) {

            .checkout-container {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .checkout-page {

                padding: 30px 15px;
            }


            .customer-info {

                grid-template-columns: 1fr;
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

        <a href="orders.php">
            My Orders
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</nav>



<!-- CHECKOUT -->

<section class="checkout-page">


    <div class="checkout-header">

        <h1>
            🧾 Checkout
        </h1>

        <p>
            Enter your delivery details and place your order.
        </p>

    </div>



    <?php if (!empty($error)): ?>

        <div
            class="checkout-box error-message"
            style="max-width:1200px;margin:0 auto 25px;"
        >

            ⚠️
            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>



    <form
        method="POST"
        onsubmit="
            return confirm(
                'Are you sure you want to place this order?'
            );
        "
    >

        <div class="checkout-container">


            <!-- LEFT SIDE -->

            <div class="checkout-box">

                <h2>
                    👤 Customer Information
                </h2>


                <div class="customer-info">


                    <div class="info-card">

                        <span>
                            Name
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $user["name"] ?? ""
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="info-card">

                        <span>
                            Email
                        </span>

                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $user["email"] ?? ""
                            );
                            ?>
                        </strong>

                    </div>


                </div>



                <h2>
                    📍 Delivery Address
                </h2>


                <div class="form-group">

                    <label for="address">
                        Complete Address
                    </label>


                    <textarea
                        name="address"
                        id="address"
                        class="address-input"
                        placeholder="Enter your complete delivery address..."
                        required
                    ></textarea>

                </div>


                <div
                    style="
                        background:#eff6ff;
                        color:#1d4ed8;
                        padding:15px;
                        border-radius:10px;
                        margin-top:10px;
                    "
                >

                    📦 Your order will initially have
                    <strong>Pending</strong> status.

                </div>

            </div>



            <!-- RIGHT SIDE -->

            <div class="checkout-box">

                <h2>
                    🛍️ Order Summary
                </h2>


                <?php foreach ($cart_items as $item): ?>

                    <?php

                    $image = !empty($item["image"])
                        ? "assets/images/" .
                          basename($item["image"])
                        : "assets/images/default-product.png";

                    $item_total =
                        $item["price"] *
                        $item["quantity"];

                    ?>


                    <div class="order-item">


                        <img
                            src="<?php
                            echo htmlspecialchars($image);
                            ?>"
                            class="order-image"
                            alt="<?php
                            echo htmlspecialchars(
                                $item["name"]
                            );
                            ?>"
                        >


                        <div class="order-item-info">

                            <h4>

                                <?php
                                echo htmlspecialchars(
                                    $item["name"]
                                );
                                ?>

                            </h4>


                            <p>

                                Quantity:
                                <?php
                                echo $item["quantity"];
                                ?>

                            </p>

                        </div>


                        <div class="order-item-price">

                            ₹<?php
                            echo number_format(
                                $item_total,
                                2
                            );
                            ?>

                        </div>


                    </div>


                <?php endforeach; ?>



                <div
                    class="summary-row"
                    style="margin-top:20px;"
                >

                    <span>
                        Items
                    </span>

                    <strong>

                        <?php

                        $item_count = 0;

                        foreach ($cart_items as $item) {
                            $item_count +=
                                $item["quantity"];
                        }

                        echo $item_count;

                        ?>

                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Delivery
                    </span>

                    <strong>
                        FREE
                    </strong>

                </div>


                <div class="summary-total">

                    <span>
                        Total
                    </span>

                    <span>

                        ₹<?php
                        echo number_format(
                            $total,
                            2
                        );
                        ?>

                    </span>

                </div>


                <button
                    type="submit"
                    name="place_order"
                    class="place-order-btn"
                >

                    🛒 Place Order

                </button>


                <a
                    href="cart.php"
                    class="back-cart"
                >

                    ← Back to Cart

                </a>

            </div>


        </div>

    </form>


</section>


</body>

</html>