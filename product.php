<?php
session_start();

require_once "config/database.php";

/* =========================================
   GET PRODUCT ID
========================================= */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: index.php");
    exit();
}

$product_id = intval($_GET["id"]);


/* =========================================
   GET PRODUCT
========================================= */

$stmt = $conn->prepare("
    SELECT id, name, description, price, image, stock
    FROM products
    WHERE id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit();
}

$product = $result->fetch_assoc();

$stmt->close();


/* =========================================
   CART COUNT
========================================= */

$cart_count = 0;

if (isset($_SESSION["user_id"])) {

    $user_id = $_SESSION["user_id"];

    $cart_stmt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS cart_count
        FROM cart_items
        WHERE user_id = ?
    ");

    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();

    $cart_result = $cart_stmt->get_result();

    if ($cart_row = $cart_result->fetch_assoc()) {
        $cart_count = $cart_row["cart_count"];
    }

    $cart_stmt->close();
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

    <title>
        <?php echo htmlspecialchars($product["name"]); ?> | ShopEase
    </title>

    <link
        rel="stylesheet"
        href="assets/css/style.css"
    >

</head>


<body class="store-page">


<!-- =========================================
     NAVBAR
========================================= -->

<nav class="store-navbar">

    <div class="store-logo">

        <div class="store-logo-icon">
            🛒
        </div>

        <div>

            <strong>ShopEase</strong>

            <span>Online Store</span>

        </div>

    </div>


    <form
        method="GET"
        action="index.php"
        class="store-search"
    >

        <span>🔍</span>

        <input
            type="text"
            name="search"
            placeholder="Search for products..."
        >

        <button type="submit">
            Search
        </button>

    </form>


    <div class="store-nav-actions">

        <a
            href="index.php"
            class="store-nav-link"
        >
            🏠 Home
        </a>


        <?php if (isset($_SESSION["user_id"])): ?>

            <a
                href="orders.php"
                class="store-nav-link"
            >
                📦 Orders
            </a>


            <a
                href="cart.php"
                class="store-cart-link"
            >

                🛒 Cart

                <span class="cart-count">
                    <?php echo $cart_count; ?>
                </span>

            </a>


            <div class="store-user">

                <div class="store-user-avatar">

                    <?php

                    echo htmlspecialchars(
                        strtoupper(
                            substr(
                                $_SESSION["user_name"],
                                0,
                                1
                            )
                        )
                    );

                    ?>

                </div>

                <span>
                    <?php
                    echo htmlspecialchars(
                        $_SESSION["user_name"]
                    );
                    ?>
                </span>

            </div>


            <a
                href="logout.php"
                class="logout-store-link"
            >
                Logout
            </a>

        <?php else: ?>

            <a
                href="login.php"
                class="login-store-link"
            >
                Login
            </a>


            <a
                href="register.php"
                class="register-store-link"
            >
                Register
            </a>


            <a
                href="cart.php"
                class="store-cart-link"
            >
                🛒 Cart
                <span class="cart-count">0</span>
            </a>

        <?php endif; ?>

    </div>

</nav>



<!-- =========================================
     BREADCRUMB
========================================= -->

<div class="product-breadcrumb">

    <a href="index.php">
        Home
    </a>

    <span>›</span>

    <span>
        Products
    </span>

    <span>›</span>

    <strong>
        <?php
        echo htmlspecialchars($product["name"]);
        ?>
    </strong>

</div>



<!-- =========================================
     PRODUCT DETAILS
========================================= -->

<section class="product-details-section">


    <!-- PRODUCT IMAGE -->

    <div class="product-details-image">

        <?php if (!empty($product["image"])): ?>

            <img
                src="assets/images/<?php
                echo htmlspecialchars(
                    basename(
                        $product["image"]
                    )
                );
                ?>"
                alt="<?php
                echo htmlspecialchars(
                    $product["name"]
                );
                ?>"
                onerror="this.style.display='none'; document.getElementById('productPlaceholder').style.display='flex';"
            >

            <div
                id="productPlaceholder"
                class="product-large-placeholder"
                style="display:none;"
            >
                📦
            </div>

        <?php else: ?>

            <div class="product-large-placeholder">
                📦
            </div>

        <?php endif; ?>


        <?php if ($product["stock"] > 0): ?>

            <span class="product-stock-badge">
                ✓ In Stock
            </span>

        <?php else: ?>

            <span class="product-stock-badge out">
                Out of Stock
            </span>

        <?php endif; ?>

    </div>



    <!-- PRODUCT INFORMATION -->

    <div class="product-details-info">

        <span class="details-category">
            SHOP PRODUCT
        </span>


        <h1>

            <?php
            echo htmlspecialchars(
                $product["name"]
            );
            ?>

        </h1>


        <div class="details-rating">

            <span>★★★★★</span>

            <small>
                Customer Favourite
            </small>

        </div>


        <div class="details-price">

            ₹<?php
            echo number_format(
                $product["price"],
                2
            );
            ?>

        </div>


        <div class="details-divider"></div>


        <h3>
            Product Description
        </h3>


        <p class="details-description">

            <?php

            echo nl2br(
                htmlspecialchars(
                    $product["description"]
                    ?? "No description available."
                )
            );

            ?>

        </p>


        <!-- STOCK -->

        <div class="details-stock">

            <span>
                📦
            </span>

            <?php if ($product["stock"] > 0): ?>

                <strong>
                    <?php
                    echo $product["stock"];
                    ?>
                </strong>

                items available

            <?php else: ?>

                <strong>
                    0
                </strong>

                items available

            <?php endif; ?>

        </div>



        <!-- ADD TO CART -->

        <?php if ($product["stock"] > 0): ?>


            <?php if (isset($_SESSION["user_id"])): ?>


                <form
                    action="cart.php"
                    method="GET"
                    class="product-buy-form"
                >

                    <input
                        type="hidden"
                        name="action"
                        value="add"
                    >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php
                        echo $product["id"];
                        ?>"
                    >


                    <div class="quantity-box">

                        <label>
                            Quantity
                        </label>

                        <div class="quantity-control">

                            <button
                                type="button"
                                onclick="changeQuantity(-1)"
                            >
                                −
                            </button>


                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                value="1"
                                min="1"
                                max="<?php
                                echo $product["stock"];
                                ?>"
                            >


                            <button
                                type="button"
                                onclick="changeQuantity(1)"
                            >
                                +
                            </button>

                        </div>

                    </div>


                    <button
                        type="submit"
                        class="details-add-cart"
                    >
                        🛒 Add to Cart
                    </button>


                </form>


            <?php else: ?>


                <div class="login-to-purchase">

                    <p>
                        Please login to purchase this product.
                    </p>

                    <a href="login.php">
                        🔐 Login to Continue
                    </a>

                </div>


            <?php endif; ?>


        <?php else: ?>


            <button
                class="details-add-cart disabled"
                disabled
            >
                Out of Stock
            </button>


        <?php endif; ?>


        <!-- BENEFITS -->

        <div class="product-benefits">

            <div>

                <span>🚚</span>

                <div>

                    <strong>
                        Fast Delivery
                    </strong>

                    <small>
                        Quick and reliable delivery
                    </small>

                </div>

            </div>


            <div>

                <span>🔒</span>

                <div>

                    <strong>
                        Secure Payment
                    </strong>

                    <small>
                        Your payment information is safe
                    </small>

                </div>

            </div>


            <div>

                <span>↩️</span>

                <div>

                    <strong>
                        Easy Shopping
                    </strong>

                    <small>
                        Simple and convenient checkout
                    </small>

                </div>

            </div>

        </div>


    </div>

</section>



<!-- =========================================
     FOOTER
========================================= -->

<footer class="store-footer">

    <div class="footer-brand">

        <div class="store-logo">

            <div class="store-logo-icon">
                🛒
            </div>

            <div>

                <strong>ShopEase</strong>

                <span>Online Store</span>

            </div>

        </div>

        <p>
            Your trusted destination for quality products.
        </p>

    </div>


    <div class="footer-links">

        <a href="index.php">
            Home
        </a>

        <a href="products.php">
            Products
        </a>

        <a href="cart.php">
            Cart
        </a>

        <a href="orders.php">
            Orders
        </a>

    </div>


    <div class="footer-copy">

        © <?php echo date("Y"); ?> ShopEase.
        All rights reserved.

    </div>

</footer>



<script>

function changeQuantity(change) {

    const quantityInput =
        document.getElementById("quantity");

    let quantity =
        parseInt(quantityInput.value) || 1;

    const max =
        parseInt(quantityInput.max);

    quantity += change;

    if (quantity < 1) {
        quantity = 1;
    }

    if (quantity > max) {
        quantity = max;
    }

    quantityInput.value = quantity;
}

</script>



<style>

/* =====================================================
   PRODUCT DETAILS PAGE
===================================================== */

.product-breadcrumb {
    padding: 20px 7%;

    display: flex;

    align-items: center;

    gap: 10px;

    background: white;

    border-bottom: 1px solid #e5edf6;

    color: #8aa0b8;

    font-size: 12px;
}


.product-breadcrumb a {
    color: #1677ff;

    text-decoration: none;

    font-weight: 600;
}


.product-breadcrumb strong {
    color: #426381;

    font-weight: 600;

    overflow: hidden;

    text-overflow: ellipsis;

    white-space: nowrap;
}


/* MAIN */

.product-details-section {
    min-height: 620px;

    padding: 65px 8% 80px;

    background:
        radial-gradient(
            circle at 15% 25%,
            rgba(40,126,255,0.08),
            transparent 30%
        ),
        #f7faff;

    display: grid;

    grid-template-columns:
        minmax(350px, 1fr)
        minmax(400px, 1fr);

    gap: 70px;

    align-items: center;
}


/* IMAGE */

.product-details-image {
    min-height: 500px;

    background:
        linear-gradient(
            135deg,
            #edf5fd,
            #ffffff
        );

    border: 1px solid #dce8f5;

    border-radius: 25px;

    display: flex;

    align-items: center;

    justify-content: center;

    position: relative;

    overflow: hidden;

    box-shadow:
        0 20px 55px
        rgba(40,80,130,0.10);
}


.product-details-image img {
    width: 100%;

    height: 500px;

    object-fit: contain;

    padding: 35px;

    box-sizing: border-box;

    transition: transform 0.4s ease;
}


.product-details-image:hover img {
    transform: scale(1.04);
}


.product-large-placeholder {
    width: 100%;

    height: 100%;

    min-height: 500px;

    align-items: center;

    justify-content: center;

    font-size: 110px;

    background:
        linear-gradient(
            135deg,
            #eaf3fd,
            #f9fbff
        );
}


.product-stock-badge {
    position: absolute;

    top: 20px;

    right: 20px;

    padding: 10px 15px;

    border-radius: 30px;

    background: #dcf8eb;

    color: #07885c;

    border: 1px solid #c6efdd;

    font-size: 11px;

    font-weight: 800;
}


.product-stock-badge.out {
    background: #ffe3e5;

    color: #df303b;

    border-color: #ffd0d3;
}


/* INFORMATION */

.product-details-info {
    max-width: 600px;
}


.details-category {
    color: #1677ff;

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 2px;
}


.product-details-info h1 {
    margin: 10px 0 12px;

    color: #102d50;

    font-size: clamp(32px, 4vw, 50px);

    line-height: 1.1;

    letter-spacing: -1px;
}


.details-rating {
    display: flex;

    align-items: center;

    gap: 12px;

    margin-bottom: 20px;
}


.details-rating span {
    color: #f4a51c;

    letter-spacing: 3px;

    font-size: 16px;
}


.details-rating small {
    color: #8298b0;

    font-size: 11px;
}


.details-price {
    color: #1677ff;

    font-size: 32px;

    font-weight: 800;
}


.details-divider {
    height: 1px;

    background: #dfe8f2;

    margin: 25px 0;
}


.product-details-info h3 {
    color: #27496c;

    font-size: 15px;

    margin-bottom: 10px;
}


.details-description {
    color: #6f88a4;

    font-size: 14px;

    line-height: 1.8;

    margin: 0 0 20px;
}


/* STOCK INFO */

.details-stock {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 9px 13px;

    border-radius: 8px;

    background: #edf7f2;

    color: #557b69;

    font-size: 11px;

    margin-bottom: 22px;
}


.details-stock strong {
    color: #07885c;
}


/* BUY */

.product-buy-form {
    display: flex;

    align-items: end;

    gap: 13px;

    flex-wrap: wrap;
}


.quantity-box label {
    display: block;

    color: #577591;

    font-size: 10px;

    font-weight: 700;

    margin-bottom: 6px;
}


.quantity-control {
    height: 46px;

    display: flex;

    align-items: center;

    border: 1px solid #d5e2ee;

    background: white;

    border-radius: 9px;

    overflow: hidden;
}


.quantity-control button {
    width: 40px;

    height: 100%;

    border: none;

    background: #f2f6fb;

    color: #2b5a84;

    font-size: 20px;

    cursor: pointer;
}


.quantity-control button:hover {
    background: #e5eef8;
}


.quantity-control input {
    width: 50px;

    height: 100%;

    border: none;

    outline: none;

    text-align: center;

    color: #23486d;

    font-weight: 700;
}


.details-add-cart {
    height: 46px;

    padding: 0 25px;

    border: none;

    border-radius: 9px;

    background:
        linear-gradient(
            135deg,
            #1677ff,
            #255eea
        );

    color: white;

    font-size: 13px;

    font-weight: 700;

    cursor: pointer;

    box-shadow:
        0 9px 20px
        rgba(37,99,235,0.22);

    transition: 0.25s;
}


.details-add-cart:hover {
    transform: translateY(-2px);

    box-shadow:
        0 13px 25px
        rgba(37,99,235,0.28);
}


.details-add-cart.disabled {
    background: #b7c2cf;

    cursor: not-allowed;

    box-shadow: none;
}


/* LOGIN */

.login-to-purchase {
    padding: 17px;

    background: white;

    border: 1px solid #dce8f4;

    border-radius: 11px;

    margin-bottom: 20px;
}


.login-to-purchase p {
    margin: 0 0 10px;

    color: #6f88a4;

    font-size: 12px;
}


.login-to-purchase a {
    display: inline-block;

    padding: 10px 15px;

    border-radius: 8px;

    background: #1677ff;

    color: white;

    text-decoration: none;

    font-size: 12px;

    font-weight: 700;
}


/* BENEFITS */

.product-benefits {
    margin-top: 30px;

    padding-top: 25px;

    border-top: 1px solid #dfe8f2;

    display: grid;

    grid-template-columns:
        repeat(3, 1fr);

    gap: 15px;
}


.product-benefits > div {
    display: flex;

    align-items: flex-start;

    gap: 9px;
}


.product-benefits > div > span {
    width: 34px;

    height: 34px;

    min-width: 34px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eaf3ff;

    border-radius: 9px;

    font-size: 16px;
}


.product-benefits strong {
    display: block;

    color: #315474;

    font-size: 10px;
}


.product-benefits small {
    display: block;

    margin-top: 4px;

    color: #8aa0b6;

    font-size: 9px;

    line-height: 1.4;
}


/* RESPONSIVE */

@media (max-width: 900px) {

    .product-details-section {
        grid-template-columns: 1fr;

        gap: 40px;

        padding: 40px 6%;
    }

    .product-details-image {
        min-height: 400px;
    }

    .product-details-image img {
        height: 400px;
    }

    .product-large-placeholder {
        min-height: 400px;
    }

}


@media (max-width: 600px) {

    .product-breadcrumb {
        padding: 15px 20px;
    }

    .product-details-section {
        padding: 30px 20px 50px;
    }

    .product-details-image {
        min-height: 320px;
    }

    .product-details-image img {
        height: 320px;

        padding: 20px;
    }

    .product-large-placeholder {
        min-height: 320px;
    }

    .product-details-info h1 {
        font-size: 32px;
    }

    .details-price {
        font-size: 27px;
    }

    .product-benefits {
        grid-template-columns: 1fr;
    }

}

</style>


</body>

</html>