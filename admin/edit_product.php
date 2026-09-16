<?php

session_start();

require_once "../config/database.php";


/* =====================================================
   ADMIN LOGIN CHECK
===================================================== */

if (
    !isset($_SESSION["user_id"]) ||
    !isset($_SESSION["user_role"]) ||
    $_SESSION["user_role"] !== "admin"
) {
    header("Location: ../login.php");
    exit();
}


/* =====================================================
   GET PRODUCT ID
===================================================== */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: products.php");
    exit();
}

$product_id = intval($_GET["id"]);

if ($product_id <= 0) {
    header("Location: products.php");
    exit();
}


/* =====================================================
   GET EXISTING PRODUCT
===================================================== */

$stmt = $conn->prepare(
    "SELECT
        id,
        name,
        description,
        price,
        image,
        stock
     FROM products
     WHERE id = ?"
);

$stmt->bind_param("i", $product_id);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();


/* =====================================================
   PRODUCT NOT FOUND
===================================================== */

if (!$product) {
    header("Location: products.php");
    exit();
}


/* =====================================================
   DEFAULT VALUES
===================================================== */

$name = $product["name"];
$description = $product["description"] ?? "";
$price = $product["price"];
$image = $product["image"] ?? "";
$stock = $product["stock"];

$error = "";
$success = "";


/* =====================================================
   UPDATE PRODUCT
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ---------------------------------------------
       GET FORM VALUES
    --------------------------------------------- */

    $name = trim($_POST["name"] ?? "");

    $description = trim(
        $_POST["description"] ?? ""
    );

    $price = trim(
        $_POST["price"] ?? ""
    );

    $stock = trim(
        $_POST["stock"] ?? ""
    );

    $image_url = trim(
        $_POST["image_url"] ?? ""
    );


    /* ---------------------------------------------
       VALIDATION
    --------------------------------------------- */

    if ($name === "") {

        $error = "Product name is required.";

    } elseif (strlen($name) < 2) {

        $error = "Product name must contain at least 2 characters.";

    } elseif ($price === "" || !is_numeric($price)) {

        $error = "Please enter a valid price.";

    } elseif ((float)$price < 0) {

        $error = "Price cannot be negative.";

    } elseif (
        $stock === "" ||
        !is_numeric($stock) ||
        intval($stock) < 0
    ) {

        $error = "Please enter a valid stock quantity.";

    } else {

        $price = (float)$price;

        $stock = intval($stock);


        /* -----------------------------------------
           IMAGE HANDLING
        ----------------------------------------- */

        $final_image = $image;


        /* If URL entered */

        if ($image_url !== "") {

            $final_image = $image_url;
        }


        /* -----------------------------------------
           IMAGE UPLOAD
        ----------------------------------------- */

        if (
            isset($_FILES["image_file"]) &&
            $_FILES["image_file"]["error"] !== UPLOAD_ERR_NO_FILE
        ) {

            if (
                $_FILES["image_file"]["error"]
                === UPLOAD_ERR_OK
            ) {

                $allowed_types = [
                    "image/jpeg",
                    "image/jpg",
                    "image/png",
                    "image/gif",
                    "image/webp"
                ];

                $file_type =
                    mime_content_type(
                        $_FILES["image_file"]["tmp_name"]
                    );


                if (
                    !in_array(
                        $file_type,
                        $allowed_types
                    )
                ) {

                    $error =
                        "Only JPG, PNG, GIF and WEBP images are allowed.";

                } else {

                    /* Maximum 5 MB */

                    if (
                        $_FILES["image_file"]["size"]
                        > 5 * 1024 * 1024
                    ) {

                        $error =
                            "Image size must be less than 5 MB.";

                    } else {

                        $extension =
                            strtolower(
                                pathinfo(
                                    $_FILES["image_file"]["name"],
                                    PATHINFO_EXTENSION
                                )
                            );


                        /* Create unique filename */

                        $new_filename =
                            "product_" .
                            time() .
                            "_" .
                            uniqid() .
                            "." .
                            $extension;


                        $upload_directory =
                            "../assets/images/";


                        /* Create folder if missing */

                        if (
                            !is_dir(
                                $upload_directory
                            )
                        ) {

                            mkdir(
                                $upload_directory,
                                0777,
                                true
                            );
                        }


                        $upload_path =
                            $upload_directory .
                            $new_filename;


                        if (
                            move_uploaded_file(
                                $_FILES["image_file"]["tmp_name"],
                                $upload_path
                            )
                        ) {

                            /*
                             * Store only the filename
                             * in the database.
                             */

                            $final_image =
                                $new_filename;

                        } else {

                            $error =
                                "Unable to upload image.";

                        }
                    }
                }

            } else {

                $error =
                    "There was an error uploading the image.";
            }
        }


        /* -----------------------------------------
           UPDATE DATABASE
        ----------------------------------------- */

        if ($error === "") {

            $stmt = $conn->prepare(
                "UPDATE products
                 SET
                    name = ?,
                    description = ?,
                    price = ?,
                    image = ?,
                    stock = ?
                 WHERE id = ?"
            );


            /*
             * IMPORTANT:
             *
             * name        = s
             * description = s
             * price       = d
             * image       = s
             * stock       = i
             * id          = i
             *
             * Therefore:
             *
             * ssdsii
             */

            $stmt->bind_param(
                "ssdsii",
                $name,
                $description,
                $price,
                $final_image,
                $stock,
                $product_id
            );


            if ($stmt->execute()) {

                $success =
                    "Product updated successfully.";

                /*
                 * Update displayed image value
                 */

                $image = $final_image;

            } else {

                $error =
                    "Unable to update product: " .
                    $stmt->error;
            }


            $stmt->close();
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

    <title>
        Edit Product - Admin
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =================================================
           PAGE
        ================================================= */

        .edit-product-page {

            min-height: 100vh;

            padding: 45px 20px;

            background:
                linear-gradient(
                    135deg,
                    #f5f7ff,
                    #eef2ff,
                    #f8fafc
                );
        }


        .edit-container {

            max-width: 900px;

            margin: auto;

        }


        /* =================================================
           HEADER
        ================================================= */

        .edit-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            margin-bottom: 25px;

            gap: 20px;
        }


        .edit-header h1 {

            margin: 0;

            font-size: 32px;

            color: #111827;
        }


        .edit-header p {

            margin-top: 7px;

            color: #6b7280;
        }


        .back-btn {

            display: inline-block;

            padding: 11px 17px;

            background: white;

            border: 1px solid #d1d5db;

            border-radius: 10px;

            color: #374151;

            text-decoration: none;

            font-weight: 600;

            transition: 0.3s;
        }


        .back-btn:hover {

            background: #f3f4f6;

            transform: translateY(-1px);
        }


        /* =================================================
           FORM CARD
        ================================================= */

        .edit-card {

            background: white;

            border-radius: 20px;

            padding: 35px;

            box-shadow:
                0 12px 40px
                rgba(0, 0, 0, 0.08);
        }


        /* =================================================
           ALERTS
        ================================================= */

        .error-message {

            background: #fee2e2;

            color: #b91c1c;

            border:
                1px solid #fecaca;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        .success-message {

            background: #dcfce7;

            color: #166534;

            border:
                1px solid #86efac;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        /* =================================================
           FORM
        ================================================= */

        .form-group {

            margin-bottom: 22px;
        }


        .form-group label {

            display: block;

            margin-bottom: 8px;

            color: #374151;

            font-weight: 700;
        }


        .form-group label span {

            color: #ef4444;
        }


        .form-control {

            width: 100%;

            padding: 13px 14px;

            border:
                1px solid #d1d5db;

            border-radius: 10px;

            font-size: 15px;

            font-family: inherit;

            outline: none;

            box-sizing: border-box;

            transition: 0.2s;
        }


        .form-control:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.1);
        }


        textarea.form-control {

            min-height: 130px;

            resize: vertical;
        }


        .form-row {

            display: grid;

            grid-template-columns: 1fr 1fr;

            gap: 20px;
        }


        /* =================================================
           CURRENT IMAGE
        ================================================= */

        .current-image-box {

            background: #f8fafc;

            border:
                1px solid #e5e7eb;

            border-radius: 14px;

            padding: 20px;

            margin-bottom: 20px;
        }


        .current-image-box h3 {

            margin-top: 0;

            margin-bottom: 15px;

            color: #374151;

            font-size: 16px;
        }


        .current-image {

            width: 180px;

            height: 180px;

            object-fit: cover;

            border-radius: 14px;

            background: #f3f4f6;

            border:
                1px solid #e5e7eb;

            display: block;

            margin-bottom: 12px;
        }


        .no-image {

            width: 180px;

            height: 180px;

            display: flex;

            align-items: center;

            justify-content: center;

            background: #f3f4f6;

            border-radius: 14px;

            color: #9ca3af;

            font-size: 50px;

            margin-bottom: 12px;
        }


        .image-name {

            color: #6b7280;

            font-size: 13px;

            word-break: break-all;
        }


        /* =================================================
           IMAGE OPTIONS
        ================================================= */

        .image-section {

            background: #f8fafc;

            padding: 20px;

            border-radius: 14px;

            border:
                1px solid #e5e7eb;

            margin-bottom: 25px;
        }


        .image-section h3 {

            margin-top: 0;

            color: #374151;

            font-size: 17px;
        }


        .image-help {

            color: #6b7280;

            font-size: 13px;

            margin-top: 7px;
        }


        .or-divider {

            text-align: center;

            color: #9ca3af;

            margin: 15px 0;

            font-weight: 600;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .form-actions {

            display: flex;

            gap: 12px;

            margin-top: 30px;

            padding-top: 25px;

            border-top:
                1px solid #e5e7eb;
        }


        .update-btn {

            border: none;

            padding: 14px 25px;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.3s;
        }


        .update-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 8px 20px
                rgba(37, 99, 235, 0.3);
        }


        .cancel-btn {

            display: inline-block;

            padding: 14px 25px;

            border-radius: 10px;

            background: #f3f4f6;

            color: #374151;

            text-decoration: none;

            font-weight: 700;
        }


        .cancel-btn:hover {

            background: #e5e7eb;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 700px) {

            .edit-product-page {

                padding: 25px 15px;
            }


            .edit-header {

                flex-direction: column;

                align-items: flex-start;
            }


            .edit-card {

                padding: 22px;
            }


            .form-row {

                grid-template-columns: 1fr;
            }


            .current-image {

                width: 140px;

                height: 140px;
            }


            .no-image {

                width: 140px;

                height: 140px;
            }


            .form-actions {

                flex-direction: column;
            }


            .update-btn,
            .cancel-btn {

                text-align: center;

                width: 100%;

                box-sizing: border-box;
            }

        }

    </style>

</head>


<body>


<!-- =====================================================
     ADMIN TOPBAR
===================================================== -->

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


<!-- =====================================================
     PAGE
===================================================== -->

<section class="edit-product-page">


    <div class="edit-container">


        <!-- HEADER -->

        <div class="edit-header">

            <div>

                <h1>
                    ✏️ Edit Product
                </h1>

                <p>
                    Update your product information below.
                </p>

            </div>


            <a
                href="products.php"
                class="back-btn"
            >
                ← Back to Products
            </a>

        </div>


        <!-- FORM CARD -->

        <div class="edit-card">


            <!-- ERROR -->

            <?php if ($error !== ""): ?>

                <div class="error-message">

                    ⚠️
                    <?php
                    echo htmlspecialchars($error);
                    ?>

                </div>

            <?php endif; ?>


            <!-- SUCCESS -->

            <?php if ($success !== ""): ?>

                <div class="success-message">

                    ✅
                    <?php
                    echo htmlspecialchars($success);
                    ?>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- PRODUCT NAME -->

                <div class="form-group">

                    <label for="name">

                        Product Name
                        <span>*</span>

                    </label>


                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars($name);
                        ?>"
                        placeholder="Enter product name"
                        required
                    >

                </div>


                <!-- DESCRIPTION -->

                <div class="form-group">

                    <label for="description">

                        Product Description

                    </label>


                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        placeholder="Enter product description"
                    ><?php
                    echo htmlspecialchars(
                        $description
                    );
                    ?></textarea>

                </div>


                <!-- PRICE + STOCK -->

                <div class="form-row">


                    <div class="form-group">

                        <label for="price">

                            Price (₹)
                            <span>*</span>

                        </label>


                        <input
                            type="number"
                            id="price"
                            name="price"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $price
                            );
                            ?>"
                            min="0"
                            step="0.01"
                            placeholder="2499.00"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="stock">

                            Stock Quantity
                            <span>*</span>

                        </label>


                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $stock
                            );
                            ?>"
                            min="0"
                            step="1"
                            placeholder="10"
                            required
                        >

                    </div>


                </div>


                <!-- CURRENT IMAGE -->

                <div class="current-image-box">

                    <h3>
                        🖼️ Current Product Image
                    </h3>


                    <?php if (!empty($image)): ?>

                        <?php

                        /*
                         * If image is a URL:
                         * use it directly.
                         *
                         * If image is a filename:
                         * use assets/images/.
                         */

                        if (
                            filter_var(
                                $image,
                                FILTER_VALIDATE_URL
                            )
                        ) {

                            $current_image =
                                $image;

                        } else {

                            $current_image =
                                "../assets/images/" .
                                basename($image);
                        }

                        ?>


                        <img
                            src="<?php
                            echo htmlspecialchars(
                                $current_image
                            );
                            ?>"
                            class="current-image"
                            alt="Current Product Image"
                            onerror="
                                this.style.display='none';
                                document.getElementById(
                                    'image-error'
                                ).style.display='flex';
                            "
                        >


                        <div
                            id="image-error"
                            class="no-image"
                            style="display:none;"
                        >
                            🖼️
                        </div>


                        <div class="image-name">

                            <?php
                            echo htmlspecialchars(
                                $image
                            );
                            ?>

                        </div>


                    <?php else: ?>


                        <div class="no-image">
                            🖼️
                        </div>


                        <div class="image-name">

                            No image assigned.

                        </div>


                    <?php endif; ?>


                </div>


                <!-- IMAGE OPTIONS -->

                <div class="image-section">

                    <h3>
                        🖼️ Change Product Image
                    </h3>


                    <!-- URL -->

                    <div class="form-group">

                        <label for="image_url">

                            Image URL

                        </label>


                        <input
                            type="url"
                            id="image_url"
                            name="image_url"
                            class="form-control"
                            placeholder="https://example.com/product.jpg"
                        >


                        <div class="image-help">

                            Enter an online image URL if you
                            don't want to upload a file.

                        </div>

                    </div>


                    <div class="or-divider">
                        OR
                    </div>


                    <!-- FILE -->

                    <div class="form-group">

                        <label for="image_file">

                            Upload Image

                        </label>


                        <input
                            type="file"
                            id="image_file"
                            name="image_file"
                            class="form-control"
                            accept=".jpg,.jpeg,.png,.gif,.webp"
                        >


                        <div class="image-help">

                            JPG, PNG, GIF or WEBP.
                            Maximum size: 5 MB.

                        </div>

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">


                    <button
                        type="submit"
                        class="update-btn"
                    >

                        💾 Update Product

                    </button>


                    <a
                        href="products.php"
                        class="cancel-btn"
                    >

                        Cancel

                    </a>


                </div>


            </form>


        </div>


    </div>


</section>


</body>

</html>