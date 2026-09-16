<?php

session_start();

require_once "../config/database.php";


/* ---------------------------------------------
   ADMIN CHECK
--------------------------------------------- */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}


/* ---------------------------------------------
   DELETE PRODUCT
--------------------------------------------- */

if (
    isset($_GET["action"]) &&
    $_GET["action"] === "delete" &&
    isset($_GET["id"])
) {

    $product_id = intval($_GET["id"]);

    if ($product_id > 0) {

        /* Delete cart entries first */

        $stmt = $conn->prepare(
            "DELETE FROM cart_items
             WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();


        /* Delete order items */

        $stmt = $conn->prepare(
            "DELETE FROM order_items
             WHERE product_id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();


        /* Delete product */

        $stmt = $conn->prepare(
            "DELETE FROM products
             WHERE id = ?"
        );

        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: products.php?deleted=1");
    exit();
}


/* ---------------------------------------------
   GET ALL PRODUCTS
--------------------------------------------- */

$result = $conn->query(
    "SELECT
        id,
        name,
        description,
        price,
        image,
        stock,
        created_at
     FROM products
     ORDER BY created_at DESC"
);

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = $row;
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

    <title>Manage Products - Admin</title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        .admin-products-page {

            min-height: 100vh;

            background:
                linear-gradient(
                    135deg,
                    #f5f7ff,
                    #eef2ff,
                    #f8fafc
                );

            padding: 40px;
        }


        .products-header {

            max-width: 1250px;

            margin: 0 auto 30px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;
        }


        .products-header h1 {

            margin: 0;

            font-size: 32px;

            color: #111827;
        }


        .products-header p {

            margin: 7px 0 0;

            color: #6b7280;
        }


        .add-product-btn {

            display: inline-block;

            padding: 13px 20px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            text-decoration: none;

            border-radius: 10px;

            font-weight: 700;

            transition: 0.3s;
        }


        .add-product-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.3);
        }


        .success-message {

            max-width: 1250px;

            margin: 0 auto 20px;

            background: #dcfce7;

            color: #166534;

            border: 1px solid #86efac;

            padding: 14px 18px;

            border-radius: 10px;

            font-weight: 600;
        }


        .products-table-box {

            max-width: 1250px;

            margin: auto;

            background: white;

            border-radius: 18px;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.08);

            overflow-x: auto;
        }


        .products-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;
        }


        .products-table th {

            background: #f8fafc;

            color: #374151;

            text-align: left;

            padding: 16px;

            font-size: 14px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .products-table td {

            padding: 16px;

            border-bottom:
                1px solid #e5e7eb;

            color: #374151;

            vertical-align: middle;
        }


        .products-table tr:hover {

            background: #f8fafc;
        }


        .product-image {

            width: 70px;

            height: 70px;

            object-fit: cover;

            border-radius: 12px;

            background: #f3f4f6;
        }


        .product-name {

            font-weight: 700;

            color: #111827;

            max-width: 200px;
        }


        .product-description {

            max-width: 250px;

            color: #6b7280;

            font-size: 14px;

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .price {

            color: #2563eb;

            font-weight: 800;

            white-space: nowrap;
        }


        .stock {

            font-weight: 700;
        }


        .stock-good {

            color: #16a34a;
        }


        .stock-low {

            color: #d97706;
        }


        .stock-out {

            color: #dc2626;
        }


        .action-buttons {

            display: flex;

            gap: 8px;
        }


        .edit-btn,
        .delete-btn {

            display: inline-block;

            padding: 8px 12px;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        .edit-btn {

            background: #dbeafe;

            color: #1d4ed8;
        }


        .edit-btn:hover {

            background: #bfdbfe;
        }


        .delete-btn {

            background: #fee2e2;

            color: #b91c1c;
        }


        .delete-btn:hover {

            background: #fecaca;
        }


        .empty-products {

            text-align: center;

            padding: 70px 20px;

            color: #6b7280;
        }


        .empty-products-icon {

            font-size: 60px;

            margin-bottom: 15px;
        }


        @media (max-width: 700px) {

            .admin-products-page {

                padding: 25px 15px;
            }


            .products-header {

                flex-direction: column;

                align-items: flex-start;
            }

        }

    </style>

</head>


<body>


<!-- ADMIN NAVBAR -->

<nav class="admin-topbar">

    <div>

        <strong>
            🛒 MyStore Admin
        </strong>

    </div>


    <div>

        <a href="dashboard.php">
            Dashboard
        </a>

        &nbsp;&nbsp;

        <a href="products.php">
            Products
        </a>

        &nbsp;&nbsp;

        <a href="orders.php">
            Orders
        </a>

        &nbsp;&nbsp;

        <a href="../index.php">
            View Store
        </a>

        &nbsp;&nbsp;

        <a href="../logout.php">
            Logout
        </a>

    </div>

</nav>


<section class="admin-products-page">


    <!-- HEADER -->

    <div class="products-header">

        <div>

            <h1>
                📦 Manage Products
            </h1>

            <p>
                Add, edit and manage products in your store.
            </p>

        </div>


        <a
            href="add_product.php"
            class="add-product-btn"
        >
            ➕ Add New Product
        </a>

    </div>


    <!-- SUCCESS -->

    <?php if (
        isset($_GET["deleted"]) &&
        $_GET["deleted"] == "1"
    ): ?>

        <div class="success-message">

            ✅ Product deleted successfully.

        </div>

    <?php endif; ?>


    <!-- PRODUCTS -->

    <?php if (empty($products)): ?>

        <div class="products-table-box">

            <div class="empty-products">

                <div class="empty-products-icon">
                    📦
                </div>

                <h2>
                    No Products Found
                </h2>

                <p>
                    Start by adding your first product.
                </p>

                <br>

                <a
                    href="add_product.php"
                    class="add-product-btn"
                >
                    ➕ Add Product
                </a>

            </div>

        </div>


    <?php else: ?>


        <div class="products-table-box">

            <table class="products-table">

                <thead>

                    <tr>

                        <th>
                            Image
                        </th>

                        <th>
                            Product
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Actions
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php foreach (
                        $products as $product
                    ): ?>


                        <?php

                        if ($product["stock"] <= 0) {

                            $stock_class =
                                "stock-out";

                        } elseif (
                            $product["stock"] <= 5
                        ) {

                            $stock_class =
                                "stock-low";

                        } else {

                            $stock_class =
                                "stock-good";
                        }


                        $image = !empty(
                            $product["image"]
                        )
                            ? "../assets/images/" .
                              basename(
                                  $product["image"]
                              )
                            : "../assets/images/default-product.png";

                        ?>


                        <tr>


                            <!-- IMAGE -->

                            <td>

                                <img
                                    src="<?php
                                    echo htmlspecialchars(
                                        $image
                                    );
                                    ?>"
                                    class="product-image"
                                    alt="<?php
                                    echo htmlspecialchars(
                                        $product["name"]
                                    );
                                    ?>"
                                >

                            </td>


                            <!-- NAME -->

                            <td>

                                <div class="product-name">

                                    <?php
                                    echo htmlspecialchars(
                                        $product["name"]
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- DESCRIPTION -->

                            <td>

                                <div
                                    class="product-description"
                                    title="<?php
                                    echo htmlspecialchars(
                                        $product["description"] ?? ""
                                    );
                                    ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $product["description"] ?? ""
                                    );
                                    ?>

                                </div>

                            </td>


                            <!-- PRICE -->

                            <td>

                                <span class="price">

                                    ₹<?php
                                    echo number_format(
                                        $product["price"],
                                        2
                                    );
                                    ?>

                                </span>

                            </td>


                            <!-- STOCK -->

                            <td>

                                <span
                                    class="
                                        stock
                                        <?php
                                        echo $stock_class;
                                        ?>
                                    "
                                >

                                    <?php
                                    echo $product["stock"];
                                    ?>

                                </span>

                            </td>


                            <!-- ACTIONS -->

                            <td>

                                <div class="action-buttons">


                                    <a
                                        href="edit_product.php?id=<?php
                                        echo $product["id"];
                                        ?>"
                                        class="edit-btn"
                                    >
                                        ✏️ Edit
                                    </a>


                                    <a
                                        href="products.php?action=delete&id=<?php
                                        echo $product["id"];
                                        ?>"
                                        class="delete-btn"
                                        onclick="
                                            return confirm(
                                                'Are you sure you want to delete this product?'
                                            );
                                        "
                                    >
                                        🗑 Delete
                                    </a>


                                </div>

                            </td>


                        </tr>


                    <?php endforeach; ?>


                </tbody>

            </table>

        </div>


    <?php endif; ?>


</section>


</body>

</html>