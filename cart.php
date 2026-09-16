<?php
session_start();
require_once "config/database.php";

/* -------------------------------------------------
   USER LOGIN CHECK
------------------------------------------------- */
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user_id"];


/* -------------------------------------------------
   ADD PRODUCT TO CART
------------------------------------------------- */
if (isset($_GET["action"]) && $_GET["action"] === "add") {

    $product_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;
    $quantity = isset($_GET["quantity"]) ? intval($_GET["quantity"]) : 1;

    if ($product_id <= 0) {
        header("Location: index.php");
        exit();
    }

    if ($quantity < 1) {
        $quantity = 1;
    }

    /* Check product */
    $stmt = $conn->prepare(
        "SELECT id, name, price, stock 
         FROM products 
         WHERE id = ?"
    );

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $product_result = $stmt->get_result();
    $product = $product_result->fetch_assoc();

    $stmt->close();

    if (!$product) {
        header("Location: index.php");
        exit();
    }

    /* Check stock */
    if ($product["stock"] <= 0) {
        header("Location: product.php?id=" . $product_id);
        exit();
    }

    if ($quantity > $product["stock"]) {
        $quantity = $product["stock"];
    }

    /* Check whether product already exists in cart */
    $stmt = $conn->prepare(
        "SELECT id, quantity 
         FROM cart_items 
         WHERE user_id = ? AND product_id = ?"
    );

    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();

    $cart_result = $stmt->get_result();
    $existing_cart = $cart_result->fetch_assoc();

    $stmt->close();

    if ($existing_cart) {

        $new_quantity = $existing_cart["quantity"] + $quantity;

        if ($new_quantity > $product["stock"]) {
            $new_quantity = $product["stock"];
        }

        $stmt = $conn->prepare(
            "UPDATE cart_items 
             SET quantity = ? 
             WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param(
            "iii",
            $new_quantity,
            $existing_cart["id"],
            $user_id
        );

        $stmt->execute();
        $stmt->close();

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO cart_items (user_id, product_id, quantity)
             VALUES (?, ?, ?)"
        );

        $stmt->bind_param(
            "iii",
            $user_id,
            $product_id,
            $quantity
        );

        $stmt->execute();
        $stmt->close();
    }

    header("Location: cart.php");
    exit();
}


/* -------------------------------------------------
   UPDATE CART QUANTITY
------------------------------------------------- */
if (isset($_POST["update_cart"])) {

    $cart_id = isset($_POST["cart_id"]) ? intval($_POST["cart_id"]) : 0;
    $quantity = isset($_POST["quantity"]) ? intval($_POST["quantity"]) : 1;

    if ($cart_id > 0) {

        if ($quantity < 1) {
            $quantity = 1;
        }

        /* Get product stock */
        $stmt = $conn->prepare(
            "SELECT p.stock
             FROM cart_items c
             JOIN products p ON c.product_id = p.id
             WHERE c.id = ? AND c.user_id = ?"
        );

        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();

        $stock_result = $stmt->get_result();
        $stock_data = $stock_result->fetch_assoc();

        $stmt->close();

        if ($stock_data) {

            if ($quantity > $stock_data["stock"]) {
                $quantity = $stock_data["stock"];
            }

            if ($quantity > 0) {

                $stmt = $conn->prepare(
                    "UPDATE cart_items
                     SET quantity = ?
                     WHERE id = ? AND user_id = ?"
                );

                $stmt->bind_param(
                    "iii",
                    $quantity,
                    $cart_id,
                    $user_id
                );

                $stmt->execute();
                $stmt->close();
            }
        }
    }

    header("Location: cart.php");
    exit();
}


/* -------------------------------------------------
   REMOVE ITEM FROM CART
------------------------------------------------- */
if (isset($_GET["action"]) && $_GET["action"] === "remove") {

    $cart_id = isset($_GET["id"]) ? intval($_GET["id"]) : 0;

    if ($cart_id > 0) {

        $stmt = $conn->prepare(
            "DELETE FROM cart_items
             WHERE id = ? AND user_id = ?"
        );

        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: cart.php");
    exit();
}


/* -------------------------------------------------
   GET CART ITEMS
------------------------------------------------- */
$stmt = $conn->prepare(
    "SELECT 
        c.id AS cart_id,
        c.quantity,
        p.id AS product_id,
        p.name,
        p.description,
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
   CALCULATE TOTAL
------------------------------------------------- */
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {

    $item_total = $item["price"] * $item["quantity"];

    $subtotal += $item_total;
    $total_items += $item["quantity"];
}

$total = $subtotal;


/* -------------------------------------------------
   CART COUNT FOR NAVBAR
------------------------------------------------- */
$cart_count = $total_items;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Shopping Cart - E-Commerce</title>

    <link rel="stylesheet"
          href="assets/css/style.css">

    <style>

        /* CART PAGE */

        .cart-page {
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

        .cart-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .cart-header h1 {
            font-size: 38px;
            margin-bottom: 10px;
            color: #111827;
        }

        .cart-header p {
            color: #6b7280;
            font-size: 16px;
        }

        .cart-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            max-width: 1200px;
            margin: auto;
        }

        .cart-box {
            background: white;
            border-radius: 18px;
            padding: 25px;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
        }

        .cart-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 100px;
            height: 100px;
            border-radius: 14px;
            object-fit: cover;
            background: #f3f4f6;
        }

        .cart-item-info h3 {
            margin: 0 0 8px;
            font-size: 19px;
            color: #111827;
        }

        .cart-item-info p {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .cart-price {
            font-size: 18px;
            font-weight: 700;
            color: #2563eb;
        }

        .quantity-form {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 12px;
        }

        .quantity-input {
            width: 65px;
            padding: 8px;
            text-align: center;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 15px;
        }

        .update-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 8px;
            background: #2563eb;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }

        .update-btn:hover {
            background: #1d4ed8;
        }

        .remove-btn {
            display: inline-block;
            margin-top: 8px;
            color: #ef4444;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .item-total {
            font-size: 19px;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
        }

        /* SUMMARY */

        .cart-summary {
            background: white;
            border-radius: 18px;
            padding: 28px;
            height: fit-content;
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 25px;
        }

        .cart-summary h2 {
            margin-bottom: 25px;
            color: #111827;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            color: #4b5563;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            margin-top: 10px;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
        }

        .checkout-btn {
            display: block;
            width: 100%;
            text-align: center;
            margin-top: 25px;
            padding: 15px;
            border-radius: 12px;
            background: linear-gradient(
                135deg,
                #2563eb,
                #7c3aed
            );
            color: white;
            text-decoration: none;
            font-size: 17px;
            font-weight: 700;
            transition: 0.3s;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.3);
        }

        .continue-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 13px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            color: #374151;
            text-decoration: none;
            font-weight: 600;
        }

        .continue-btn:hover {
            background: #f3f4f6;
        }

        /* EMPTY CART */

        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-cart-icon {
            font-size: 70px;
            margin-bottom: 20px;
        }

        .empty-cart h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #111827;
        }

        .empty-cart p {
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

        /* RESPONSIVE */

        @media (max-width: 850px) {

            .cart-container {
                grid-template-columns: 1fr;
            }

            .cart-summary {
                position: static;
            }

        }

        @media (max-width: 600px) {

            .cart-page {
                padding: 30px 15px;
            }

            .cart-item {
                grid-template-columns: 80px 1fr;
            }

            .cart-item-image {
                width: 80px;
                height: 80px;
            }

            .item-total {
                grid-column: 2;
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

        <a href="index.php">Home</a>

        <a href="products.php">Products</a>

        <a href="cart.php" class="active">
            🛒 Cart (<?php echo $cart_count; ?>)
        </a>

        <a href="orders.php">My Orders</a>

        <a href="logout.php">Logout</a>

    </div>

</nav>


<!-- CART -->

<section class="cart-page">

    <div class="cart-header">

        <h1>🛒 Your Shopping Cart</h1>

        <p>
            Review your products before placing your order.
        </p>

    </div>


    <?php if (empty($cart_items)): ?>

        <div class="cart-box empty-cart">

            <div class="empty-cart-icon">
                🛍️
            </div>

            <h2>Your cart is empty</h2>

            <p>
                Looks like you haven't added anything to your cart yet.
            </p>

            <a href="products.php" class="shop-btn">
                Start Shopping
            </a>

        </div>

    <?php else: ?>


        <div class="cart-container">


            <!-- CART ITEMS -->

            <div class="cart-box">

                <?php foreach ($cart_items as $item): ?>

                    <?php

                    $image = !empty($item["image"])
                        ? "assets/images/" . basename($item["image"])
                        : "assets/images/default-product.png";

                    $item_total =
                        $item["price"] * $item["quantity"];

                    ?>

                    <div class="cart-item">


                        <!-- IMAGE -->

                        <img
                            src="<?php echo htmlspecialchars($image); ?>"
                            alt="<?php echo htmlspecialchars($item["name"]); ?>"
                            class="cart-item-image"
                        >


                        <!-- INFO -->

                        <div class="cart-item-info">

                            <h3>
                                <?php
                                echo htmlspecialchars($item["name"]);
                                ?>
                            </h3>

                            <p>
                                ₹<?php
                                echo number_format(
                                    $item["price"],
                                    2
                                );
                                ?>
                                each
                            </p>


                            <div class="cart-price">

                                ₹<?php
                                echo number_format(
                                    $item_total,
                                    2
                                );
                                ?>

                            </div>


                            <!-- QUANTITY -->

                            <form
                                method="POST"
                                class="quantity-form"
                            >

                                <input
                                    type="hidden"
                                    name="cart_id"
                                    value="<?php
                                    echo $item["cart_id"];
                                    ?>"
                                >

                                <input
                                    type="number"
                                    name="quantity"
                                    class="quantity-input"
                                    min="1"
                                    max="<?php
                                    echo $item["stock"];
                                    ?>"
                                    value="<?php
                                    echo $item["quantity"];
                                    ?>"
                                >

                                <button
                                    type="submit"
                                    name="update_cart"
                                    class="update-btn"
                                >
                                    Update
                                </button>

                            </form>


                            <!-- REMOVE -->

                            <a
                                href="cart.php?action=remove&id=<?php
                                echo $item["cart_id"];
                                ?>"
                                class="remove-btn"
                                onclick="return confirm(
                                    'Remove this product from your cart?'
                                );"
                            >
                                🗑 Remove
                            </a>

                        </div>


                        <!-- ITEM TOTAL -->

                        <div class="item-total">

                            ₹<?php
                            echo number_format(
                                $item_total,
                                2
                            );
                            ?>

                        </div>


                    </div>

                <?php endforeach; ?>

            </div>


            <!-- SUMMARY -->

            <div class="cart-summary">

                <h2>Order Summary</h2>


                <div class="summary-row">

                    <span>
                        Items
                    </span>

                    <strong>
                        <?php echo $total_items; ?>
                    </strong>

                </div>


                <div class="summary-row">

                    <span>
                        Subtotal
                    </span>

                    <strong>
                        ₹<?php
                        echo number_format(
                            $subtotal,
                            2
                        );
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


                <a
                    href="checkout.php"
                    class="checkout-btn"
                >
                    Proceed to Checkout →
                </a>


                <a
                    href="products.php"
                    class="continue-btn"
                >
                    ← Continue Shopping
                </a>

            </div>


        </div>

    <?php endif; ?>

</section>


</body>
</html>