<?php

session_start();

require_once "config/database.php";

// Get search keyword
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

// Get products
if ($search !== "") {

    $stmt = $conn->prepare("
        SELECT id, name, description, price, image, stock
        FROM products
        WHERE name LIKE ? OR description LIKE ?
        ORDER BY id DESC
    ");

    $keyword = "%" . $search . "%";
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();

    $products = $stmt->get_result();

} else {

    $products = $conn->query("
        SELECT id, name, description, price, image, stock
        FROM products
        ORDER BY id DESC
    ");

}

// Cart count
$cart_count = 0;

if (isset($_SESSION["user_id"])) {

    $user_id = $_SESSION["user_id"];

    $stmt_cart = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0) AS total
        FROM cart_items
        WHERE user_id = ?
    ");

    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();

    $cart_result = $stmt_cart->get_result()->fetch_assoc();

    $cart_count = (int)$cart_result["total"];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Products - E-Commerce Store</title>

    <link rel="stylesheet" href="assets/css/style.css">

    <style>

        body {
            background: #f5f7fb;
        }

        .products-page {
            padding: 40px 7%;
        }

        .products-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .products-header h1 {
            font-size: 38px;
            margin-bottom: 10px;
        }

        .products-header p {
            color: #777;
        }

        .search-box {
            max-width: 650px;
            margin: 0 auto 40px;
            display: flex;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            padding: 14px 18px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }

        .search-box button {
            padding: 14px 25px;
            border: none;
            border-radius: 10px;
            background: #111827;
            color: white;
            cursor: pointer;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            transition: 0.3s;
        }

        .product-card:hover {
            transform: translateY(-6px);
        }

        .product-image {
            width: 100%;
            height: 220px;
            object-fit: cover;
            background: #eee;
        }

        .no-image {
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #eee;
            color: #888;
        }

        .product-content {
            padding: 20px;
        }

        .product-content h3 {
            margin-bottom: 10px;
        }

        .description {
            color: #777;
            height: 45px;
            overflow: hidden;
            margin-bottom: 12px;
        }

        .price {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stock {
            font-size: 14px;
            color: #555;
            margin-bottom: 15px;
        }

        .buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            flex: 1;
            text-align: center;
            padding: 11px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
        }

        .view-btn {
            background: #e5e7eb;
            color: #111;
        }

        .cart-btn {
            background: #111827;
            color: white;
        }

        .out-stock {
            background: #ddd;
            color: #666;
            cursor: not-allowed;
        }

        .empty {
            text-align: center;
            padding: 60px;
            color: #777;
        }

        @media(max-width:600px) {

            .products-page {
                padding: 25px 5%;
            }

            .products-header h1 {
                font-size: 30px;
            }

            .search-box {
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<!-- NAVBAR -->

<nav class="navbar">

    <div class="logo">
        🛍️ E-Commerce Store
    </div>

    <div class="nav-links">

        <a href="index.php">Home</a>

        <a href="products.php">Products</a>

        <?php if (isset($_SESSION["user_id"])): ?>

            <a href="cart.php">
                🛒 Cart (<?php echo $cart_count; ?>)
            </a>

            <a href="orders.php">My Orders</a>

            <a href="logout.php">Logout</a>

        <?php else: ?>

            <a href="login.php">Login</a>

            <a href="register.php">Register</a>

        <?php endif; ?>

    </div>

</nav>


<!-- PRODUCTS -->

<section class="products-page">

    <div class="products-header">

        <h1>Our Products</h1>

        <p>Browse our latest products</p>

    </div>


    <!-- SEARCH -->

    <form class="search-box" method="GET" action="products.php">

        <input
            type="text"
            name="search"
            placeholder="Search products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit">
            🔍 Search
        </button>

    </form>


    <!-- PRODUCT GRID -->

    <?php if ($products && $products->num_rows > 0): ?>

        <div class="product-grid">

            <?php while ($product = $products->fetch_assoc()): ?>

                <div class="product-card">

                    <?php

                    $image = trim($product["image"] ?? "");

                    if ($image !== ""):

                        $image_name = basename($image);

                    ?>

                        <img
                            src="assets/images/<?php echo htmlspecialchars($image_name); ?>"
                            class="product-image"
                            alt="<?php echo htmlspecialchars($product["name"]); ?>"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >

                        <div class="no-image" style="display:none;">
                            No Image
                        </div>

                    <?php else: ?>

                        <div class="no-image">
                            No Image
                        </div>

                    <?php endif; ?>


                    <div class="product-content">

                        <h3>
                            <?php echo htmlspecialchars($product["name"]); ?>
                        </h3>

                        <div class="description">
                            <?php echo htmlspecialchars($product["description"]); ?>
                        </div>

                        <div class="price">
                            ₹<?php echo number_format((float)$product["price"], 2); ?>
                        </div>

                        <div class="stock">

                            <?php if ((int)$product["stock"] > 0): ?>

                                Stock:
                                <?php echo (int)$product["stock"]; ?>

                            <?php else: ?>

                                Out of stock

                            <?php endif; ?>

                        </div>


                        <div class="buttons">

                            <a
                                href="product.php?id=<?php echo (int)$product["id"]; ?>"
                                class="btn view-btn"
                            >
                                View
                            </a>


                            <?php if ((int)$product["stock"] > 0): ?>

                                <?php if (isset($_SESSION["user_id"])): ?>

                                    <a
                                        href="cart.php?action=add&id=<?php echo (int)$product["id"]; ?>"
                                        class="btn cart-btn"
                                    >
                                        Add to Cart
                                    </a>

                                <?php else: ?>

                                    <a
                                        href="login.php"
                                        class="btn cart-btn"
                                    >
                                        Login to Buy
                                    </a>

                                <?php endif; ?>

                            <?php else: ?>

                                <span class="btn out-stock">
                                    Out of Stock
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <div class="empty">

            <h2>No products found</h2>

            <p>
                <?php if ($search !== ""): ?>
                    Try searching for another product.
                <?php else: ?>
                    Products will appear here after the admin adds them.
                <?php endif; ?>
            </p>

        </div>

    <?php endif; ?>

</section>


</body>
</html>