<?php
session_start();

require_once "config/database.php";

/* =========================================
   GET PRODUCTS
========================================= */

$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

if ($search !== "") {

    $search_value = "%" . $search . "%";

    $stmt = $conn->prepare("
        SELECT id, name, description, price, image, stock
        FROM products
        WHERE name LIKE ?
           OR description LIKE ?
        ORDER BY id DESC
    ");

    $stmt->bind_param(
        "ss",
        $search_value,
        $search_value
    );

    $stmt->execute();

    $products = $stmt->get_result();

} else {

    $products = $conn->query("
        SELECT id, name, description, price, image, stock
        FROM products
        ORDER BY id DESC
    ");
}


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

    <title>ShopEase | Online Shopping</title>

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

            <strong>
                ShopEase
            </strong>

            <span>
                Online Store
            </span>

        </div>

    </div>


    <!-- SEARCH -->

    <form
        method="GET"
        action="index.php"
        class="store-search"
    >

        <span>
            🔍
        </span>

        <input
            type="text"
            name="search"
            placeholder="Search for products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">
            Search
        </button>

    </form>


    <!-- NAV ACTIONS -->

    <div class="store-nav-actions">


        <?php if (isset($_SESSION["user_id"])): ?>


            <a
                href="orders.php"
                class="store-nav-link"
            >
                📦
                Orders
            </a>


            <a
                href="cart.php"
                class="store-cart-link"
            >

                🛒

                Cart

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


            <?php if ($_SESSION["user_role"] === "admin"): ?>

                <a
                    href="admin/dashboard.php"
                    class="admin-store-link"
                >
                    Admin
                </a>

            <?php endif; ?>


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

                🛒
                Cart

                <span class="cart-count">
                    0
                </span>

            </a>


        <?php endif; ?>


    </div>

</nav>



<!-- =========================================
     HERO SECTION
========================================= -->

<section class="store-hero">

    <div class="hero-content">

        <span class="hero-small-title">
            ✨ Welcome to ShopEase
        </span>


        <h1>
            Everything You Need,
            <span>All in One Place.</span>
        </h1>


        <p>
            Discover quality products at amazing prices.
            Shop easily, securely and conveniently.
        </p>


        <div class="hero-buttons">

            <a
                href="#products"
                class="hero-primary-btn"
            >
                🛍️ Start Shopping
            </a>


            <?php if (!isset($_SESSION["user_id"])): ?>

                <a
                    href="register.php"
                    class="hero-secondary-btn"
                >
                    Create Account →
                </a>

            <?php else: ?>

                <a
                    href="orders.php"
                    class="hero-secondary-btn"
                >
                    My Orders →
                </a>

            <?php endif; ?>

        </div>


        <!-- FEATURES -->

        <div class="hero-features">

            <div>
                <span>✓</span>
                Secure Shopping
            </div>

            <div>
                <span>✓</span>
                Quality Products
            </div>

            <div>
                <span>✓</span>
                Easy Checkout
            </div>

        </div>

    </div>


    <div class="hero-visual">

        <div class="hero-circle">

            <div class="hero-shopping-bag">
                🛍️
            </div>

        </div>


        <div class="floating-product floating-one">
            🎧
        </div>

        <div class="floating-product floating-two">
            ⌚
        </div>

        <div class="floating-product floating-three">
            👟
        </div>

    </div>

</section>



<!-- =========================================
     PRODUCTS
========================================= -->

<section
    class="store-products-section"
    id="products"
>


    <!-- SECTION TITLE -->

    <div class="store-section-heading">

        <div>

            <span>
                OUR COLLECTION
            </span>

            <h2>
                Explore Our Products
            </h2>

            <p>
                Find something perfect for you.
            </p>

        </div>


        <div class="product-count-label">

            <?php

            if ($products) {
                echo $products->num_rows;
            } else {
                echo 0;
            }

            ?>

            Products

        </div>

    </div>



    <!-- SEARCH RESULT -->

    <?php if ($search !== ""): ?>

        <div class="search-result-message">

            🔎 Showing results for:

            <strong>
                "<?php
                echo htmlspecialchars($search);
                ?>"
            </strong>

            <a href="index.php">
                Clear Search
            </a>

        </div>

    <?php endif; ?>



    <!-- =====================================
         PRODUCT GRID
    ====================================== -->

    <div class="store-product-grid">


    <?php if ($products && $products->num_rows > 0): ?>


        <?php while ($product = $products->fetch_assoc()): ?>


            <div class="store-product-card">


                <!-- IMAGE -->

                <div class="store-product-image">

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
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div
                            class="store-image-placeholder"
                            style="display:none;"
                        >
                            📦
                        </div>

                    <?php else: ?>

                        <div class="store-image-placeholder">
                            📦
                        </div>

                    <?php endif; ?>


                    <!-- STOCK LABEL -->

                    <?php if ($product["stock"] > 0): ?>

                        <span class="available-label">
                            ● In Stock
                        </span>

                    <?php else: ?>

                        <span class="soldout-label">
                            Out of Stock
                        </span>

                    <?php endif; ?>

                </div>



                <!-- PRODUCT INFORMATION -->

                <div class="store-product-info">


                    <span class="product-category">
                        SHOP PRODUCT
                    </span>


                    <h3>

                        <?php
                        echo htmlspecialchars(
                            $product["name"]
                        );
                        ?>

                    </h3>


                    <p>

                        <?php

                        $description =
                            $product["description"]
                            ?? "";

                        if (strlen($description) > 85) {

                            echo htmlspecialchars(
                                substr(
                                    $description,
                                    0,
                                    85
                                ) . "..."
                            );

                        } else {

                            echo htmlspecialchars(
                                $description
                            );

                        }

                        ?>

                    </p>



                    <!-- PRICE -->

                    <div class="store-product-bottom">


                        <div class="store-price">

                            ₹<?php
                            echo number_format(
                                $product["price"],
                                2
                            );
                            ?>

                        </div>


                        <a
                            href="product.php?id=<?php echo $product["id"]; ?>"
                            class="view-product-btn"
                        >
                            View
                            →
                        </a>


                    </div>



                    <!-- ADD TO CART -->

                    <?php if ($product["stock"] > 0): ?>

                        <?php if (isset($_SESSION["user_id"])): ?>

                            <a
                                href="cart.php?action=add&id=<?php echo $product["id"]; ?>"
                                class="add-cart-btn"
                            >
                                🛒 Add to Cart
                            </a>

                        <?php else: ?>

                            <a
                                href="login.php"
                                class="add-cart-btn"
                            >
                                🔐 Login to Buy
                            </a>

                        <?php endif; ?>

                    <?php else: ?>

                        <button
                            class="add-cart-btn disabled"
                            disabled
                        >
                            Out of Stock
                        </button>

                    <?php endif; ?>


                </div>

            </div>


        <?php endwhile; ?>


    <?php else: ?>


        <!-- NO PRODUCTS -->

        <div class="no-products">

            <div>
                📦
            </div>

            <h3>
                No Products Found
            </h3>

            <p>
                We couldn't find any products matching your search.
            </p>

            <a href="index.php">
                View All Products
            </a>

        </div>


    <?php endif; ?>


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

                <strong>
                    ShopEase
                </strong>

                <span>
                    Online Store
                </span>

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



</body>

</html>