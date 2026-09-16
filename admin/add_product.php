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
   VARIABLES
===================================================== */

$name = "";
$description = "";
$price = "";
$stock = "";
$image_url = "";

$error = "";
$success = "";


/* =====================================================
   ADD PRODUCT
===================================================== */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

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

        $image = $image_url;


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


                $file_type = mime_content_type(
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

                } elseif (
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


                    $new_filename =
                        "product_" .
                        time() .
                        "_" .
                        uniqid() .
                        "." .
                        $extension;


                    $upload_directory =
                        "../assets/images/";


                    /* Create image folder if needed */

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

                        $image =
                            $new_filename;

                    } else {

                        $error =
                            "Unable to upload image.";
                    }
                }

            } else {

                $error =
                    "There was an error uploading the image.";
            }
        }


        /* -----------------------------------------
           INSERT PRODUCT
        ----------------------------------------- */

        if ($error === "") {

            $stmt = $conn->prepare(
                "INSERT INTO products
                (
                    name,
                    description,
                    price,
                    image,
                    stock
                )
                VALUES (?, ?, ?, ?, ?)"
            );


            if (!$stmt) {

                $error =
                    "Database error: " .
                    $conn->error;

            } else {

                /*
                 * name        = s
                 * description = s
                 * price       = d
                 * image       = s
                 * stock       = i
                 */

                $stmt->bind_param(
                    "ssdsi",
                    $name,
                    $description,
                    $price,
                    $image,
                    $stock
                );


                if ($stmt->execute()) {

                    $success =
                        "Product added successfully.";

                    /* Clear form */

                    $name = "";
                    $description = "";
                    $price = "";
                    $stock = "";
                    $image_url = "";

                } else {

                    $error =
                        "Unable to add product: " .
                        $stmt->error;
                }


                $stmt->close();
            }
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
        Add Product - Admin
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =================================================
           PAGE
        ================================================= */

        .add-product-page {

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


        .add-container {

            max-width: 900px;

            margin: auto;
        }


        /* =================================================
           HEADER
        ================================================= */

        .add-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }


        .add-header h1 {

            margin: 0;

            font-size: 32px;

            color: #111827;
        }


        .add-header p {

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
           CARD
        ================================================= */

        .add-card {

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

            border: 1px solid #fecaca;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        .success-message {

            background: #dcfce7;

            color: #166534;

            border: 1px solid #86efac;

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

            border: 1px solid #d1d5db;

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
           IMAGE SECTION
        ================================================= */

        .image-section {

            background: #f8fafc;

            padding: 22px;

            border-radius: 14px;

            border: 1px solid #e5e7eb;

            margin-top: 5px;

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

            font-weight: 700;
        }


        /* =================================================
           PREVIEW
        ================================================= */

        .preview-box {

            display: none;

            margin-top: 15px;

            padding: 15px;

            background: white;

            border-radius: 12px;

            border: 1px solid #e5e7eb;
        }


        .preview-box img {

            width: 160px;

            height: 160px;

            object-fit: cover;

            border-radius: 12px;

            display: block;
        }


        /* =================================================
           BUTTONS
        ================================================= */

        .form-actions {

            display: flex;

            gap: 12px;

            padding-top: 25px;

            border-top: 1px solid #e5e7eb;
        }


        .add-btn {

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


        .add-btn:hover {

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

            .add-product-page {

                padding: 25px 15px;
            }


            .add-header {

                flex-direction: column;

                align-items: flex-start;
            }


            .add-card {

                padding: 22px;
            }


            .form-row {

                grid-template-columns: 1fr;
            }


            .form-actions {

                flex-direction: column;
            }


            .add-btn,
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
     ADD PRODUCT PAGE
===================================================== -->

<section class="add-product-page">


    <div class="add-container">


        <!-- HEADER -->

        <div class="add-header">

            <div>

                <h1>
                    ➕ Add New Product
                </h1>

                <p>
                    Add a new product to your online store.
                </p>

            </div>


            <a
                href="products.php"
                class="back-btn"
            >
                ← Back to Products
            </a>

        </div>


        <!-- CARD -->

        <div class="add-card">


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

                    <br><br>

                    <a
                        href="products.php"
                        style="
                            color:#166534;
                            font-weight:700;
                        "
                    >
                        View Products →
                    </a>

                </div>

            <?php endif; ?>


            <!-- FORM -->

            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <!-- NAME -->

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
                        placeholder="Example: Smart Watch"
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
                        placeholder="Enter a detailed product description..."
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
                            echo htmlspecialchars($price);
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
                            echo htmlspecialchars($stock);
                            ?>"
                            min="0"
                            step="1"
                            placeholder="10"
                            required
                        >

                    </div>


                </div>


                <!-- IMAGE -->

                <div class="image-section">

                    <h3>
                        🖼️ Product Image
                    </h3>


                    <!-- IMAGE URL -->

                    <div class="form-group">

                        <label for="image_url">

                            Image URL

                        </label>


                        <input
                            type="url"
                            id="image_url"
                            name="image_url"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars($image_url);
                            ?>"
                            placeholder="https://example.com/product.jpg"
                        >


                        <div class="image-help">

                            Paste an online image URL.

                        </div>

                    </div>


                    <div class="or-divider">
                        OR
                    </div>


                    <!-- IMAGE FILE -->

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

                            Supported: JPG, JPEG, PNG, GIF and WEBP.
                            Maximum 5 MB.

                        </div>

                    </div>


                    <!-- PREVIEW -->

                    <div
                        id="previewBox"
                        class="preview-box"
                    >

                        <strong>
                            Image Preview
                        </strong>

                        <br><br>

                        <img
                            id="imagePreview"
                            src=""
                            alt="Image Preview"
                        >

                    </div>

                </div>


                <!-- BUTTONS -->

                <div class="form-actions">


                    <button
                        type="submit"
                        class="add-btn"
                    >

                        ➕ Add Product

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


<!-- =====================================================
     IMAGE PREVIEW JAVASCRIPT
===================================================== -->

<script>

const imageFile =
    document.getElementById("image_file");

const imageUrl =
    document.getElementById("image_url");

const previewBox =
    document.getElementById("previewBox");

const imagePreview =
    document.getElementById("imagePreview");


/* ---------------------------------------------
   FILE PREVIEW
--------------------------------------------- */

imageFile.addEventListener(
    "change",
    function () {

        const file = this.files[0];

        if (!file) {

            previewBox.style.display = "none";

            return;
        }


        const reader =
            new FileReader();


        reader.onload =
            function (event) {

                imagePreview.src =
                    event.target.result;

                previewBox.style.display =
                    "block";
            };


        reader.readAsDataURL(file);

    }
);


/* ---------------------------------------------
   URL PREVIEW
--------------------------------------------- */

imageUrl.addEventListener(
    "input",
    function () {

        const url =
            this.value.trim();


        if (
            url !== "" &&
            imageFile.files.length === 0
        ) {

            imagePreview.src = url;

            previewBox.style.display =
                "block";

        } else if (url === "") {

            previewBox.style.display =
                "none";
        }

    }
);


/* ---------------------------------------------
   IMAGE ERROR
--------------------------------------------- */

imagePreview.addEventListener(
    "error",
    function () {

        previewBox.style.display =
            "none";
    }
);

</script>


</body>

</html>