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
   DELETE USER
===================================================== */

if (
    isset($_GET["action"]) &&
    $_GET["action"] === "delete" &&
    isset($_GET["id"])
) {

    $delete_id = intval($_GET["id"]);


    /*
     * Do not allow admin to delete
     * their own account.
     */

    if ($delete_id === intval($_SESSION["user_id"])) {

        header("Location: users.php?error=self");
        exit();
    }


    if ($delete_id > 0) {

        /*
         * Delete cart items belonging to user
         */

        $stmt = $conn->prepare(
            "DELETE FROM cart_items
             WHERE user_id = ?"
        );

        $stmt->bind_param(
            "i",
            $delete_id
        );

        $stmt->execute();

        $stmt->close();


        /*
         * Delete order items belonging
         * to the user's orders.
         */

        $stmt = $conn->prepare(
            "DELETE oi
             FROM order_items oi
             INNER JOIN orders o
             ON oi.order_id = o.id
             WHERE o.user_id = ?"
        );

        $stmt->bind_param(
            "i",
            $delete_id
        );

        $stmt->execute();

        $stmt->close();


        /*
         * Delete user's orders.
         */

        $stmt = $conn->prepare(
            "DELETE FROM orders
             WHERE user_id = ?"
        );

        $stmt->bind_param(
            "i",
            $delete_id
        );

        $stmt->execute();

        $stmt->close();


        /*
         * Finally delete user.
         */

        $stmt = $conn->prepare(
            "DELETE FROM users
             WHERE id = ?"
        );

        $stmt->bind_param(
            "i",
            $delete_id
        );

        $stmt->execute();

        $stmt->close();
    }


    header("Location: users.php?deleted=1");

    exit();
}


/* =====================================================
   SEARCH
===================================================== */

$search = trim(
    $_GET["search"] ?? ""
);


if ($search !== "") {

    $search_value =
        "%" . $search . "%";


    $stmt = $conn->prepare(
        "SELECT
            id,
            name,
            email,
            role,
            created_at
         FROM users
         WHERE
            name LIKE ?
            OR email LIKE ?
         ORDER BY created_at DESC"
    );


    $stmt->bind_param(
        "ss",
        $search_value,
        $search_value
    );


    $stmt->execute();

    $result =
        $stmt->get_result();

} else {

    $result = $conn->query(
        "SELECT
            id,
            name,
            email,
            role,
            created_at
         FROM users
         ORDER BY created_at DESC"
    );
}


$users = [];

while ($row = $result->fetch_assoc()) {

    $users[] = $row;
}


if (isset($stmt)) {

    $stmt->close();
}


/* =====================================================
   USER COUNTS
===================================================== */

$total_users = count($users);

$total_admins = 0;

$total_normal_users = 0;


foreach ($users as $user) {

    if ($user["role"] === "admin") {

        $total_admins++;

    } else {

        $total_normal_users++;
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
        Manage Users - Admin
    </title>


    <link
        rel="stylesheet"
        href="../assets/css/style.css"
    >


    <style>

        /* =================================================
           PAGE
        ================================================= */

        .users-page {

            min-height: 100vh;

            padding: 40px;

            background:
                linear-gradient(
                    135deg,
                    #f5f7ff,
                    #eef2ff,
                    #f8fafc
                );
        }


        .users-container {

            max-width: 1250px;

            margin: auto;
        }


        /* =================================================
           HEADER
        ================================================= */

        .users-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;

            margin-bottom: 30px;
        }


        .users-header h1 {

            margin: 0;

            font-size: 32px;

            color: #111827;
        }


        .users-header p {

            margin-top: 7px;

            color: #6b7280;
        }


        .back-btn {

            display: inline-block;

            padding: 11px 18px;

            background: white;

            border: 1px solid #d1d5db;

            border-radius: 10px;

            text-decoration: none;

            color: #374151;

            font-weight: 700;
        }


        .back-btn:hover {

            background: #f3f4f6;
        }


        /* =================================================
           ALERTS
        ================================================= */

        .success-message {

            background: #dcfce7;

            color: #166534;

            border: 1px solid #86efac;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        .error-message {

            background: #fee2e2;

            color: #b91c1c;

            border: 1px solid #fecaca;

            padding: 15px 18px;

            border-radius: 10px;

            margin-bottom: 20px;

            font-weight: 600;
        }


        /* =================================================
           STAT CARDS
        ================================================= */

        .user-stats {

            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 20px;

            margin-bottom: 30px;
        }


        .user-stat {

            background: white;

            border-radius: 16px;

            padding: 22px;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.06);

            border: 1px solid #eef2f7;
        }


        .stat-icon {

            font-size: 30px;

            margin-bottom: 10px;
        }


        .stat-label {

            color: #6b7280;

            font-size: 14px;

            margin-bottom: 5px;
        }


        .stat-number {

            font-size: 28px;

            font-weight: 800;

            color: #111827;
        }


        /* =================================================
           SEARCH
        ================================================= */

        .search-box {

            background: white;

            padding: 20px;

            border-radius: 16px;

            box-shadow:
                0 8px 30px
                rgba(0, 0, 0, 0.06);

            margin-bottom: 25px;
        }


        .search-form {

            display: flex;

            gap: 10px;
        }


        .search-input {

            flex: 1;

            padding: 13px 15px;

            border: 1px solid #d1d5db;

            border-radius: 10px;

            font-size: 15px;

            outline: none;
        }


        .search-input:focus {

            border-color: #2563eb;

            box-shadow:
                0 0 0 3px
                rgba(37, 99, 235, 0.1);
        }


        .search-btn {

            border: none;

            padding: 13px 22px;

            border-radius: 10px;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-weight: 700;

            cursor: pointer;
        }


        .clear-btn {

            display: flex;

            align-items: center;

            padding: 0 15px;

            background: #f3f4f6;

            color: #374151;

            text-decoration: none;

            border-radius: 10px;

            font-weight: 600;
        }


        /* =================================================
           TABLE
        ================================================= */

        .users-table-box {

            background: white;

            border-radius: 18px;

            overflow-x: auto;

            box-shadow:
                0 10px 35px
                rgba(0, 0, 0, 0.08);
        }


        .users-table {

            width: 100%;

            min-width: 800px;

            border-collapse: collapse;
        }


        .users-table th {

            text-align: left;

            padding: 16px;

            background: #f8fafc;

            color: #374151;

            font-size: 14px;

            border-bottom:
                1px solid #e5e7eb;
        }


        .users-table td {

            padding: 16px;

            border-bottom:
                1px solid #e5e7eb;

            color: #374151;
        }


        .users-table tr:hover {

            background: #f8fafc;
        }


        /* =================================================
           USER AVATAR
        ================================================= */

        .user-info {

            display: flex;

            align-items: center;

            gap: 12px;
        }


        .avatar {

            width: 42px;

            height: 42px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #7c3aed
                );

            color: white;

            font-weight: 800;

            font-size: 16px;
        }


        .user-name {

            font-weight: 700;

            color: #111827;
        }


        .user-email {

            color: #6b7280;

            font-size: 13px;

            margin-top: 3px;
        }


        /* =================================================
           ROLE BADGES
        ================================================= */

        .role-badge {

            display: inline-block;

            padding: 7px 13px;

            border-radius: 20px;

            font-size: 12px;

            font-weight: 800;
        }


        .role-admin {

            background: #ede9fe;

            color: #6d28d9;
        }


        .role-user {

            background: #dbeafe;

            color: #1d4ed8;
        }


        /* =================================================
           DELETE BUTTON
        ================================================= */

        .delete-user {

            display: inline-block;

            padding: 8px 12px;

            background: #fee2e2;

            color: #b91c1c;

            border-radius: 8px;

            text-decoration: none;

            font-size: 13px;

            font-weight: 700;
        }


        .delete-user:hover {

            background: #fecaca;
        }


        .current-user {

            color: #9ca3af;

            font-size: 13px;

            font-weight: 600;
        }


        /* =================================================
           EMPTY
        ================================================= */

        .empty-users {

            text-align: center;

            padding: 70px 20px;

            color: #6b7280;
        }


        .empty-icon {

            font-size: 60px;

            margin-bottom: 15px;
        }


        /* =================================================
           RESPONSIVE
        ================================================= */

        @media (max-width: 800px) {

            .users-page {

                padding: 25px 15px;
            }


            .users-header {

                flex-direction: column;

                align-items: flex-start;
            }


            .user-stats {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 600px) {

            .search-form {

                flex-direction: column;
            }


            .clear-btn {

                justify-content: center;

                padding: 13px;
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

        <a href="users.php">
            Users
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

<section class="users-page">


    <div class="users-container">


        <!-- HEADER -->

        <div class="users-header">

            <div>

                <h1>
                    👥 Manage Users
                </h1>

                <p>
                    View and manage registered customers.
                </p>

            </div>


            <a
                href="dashboard.php"
                class="back-btn"
            >
                ← Dashboard
            </a>

        </div>


        <!-- =================================================
             ALERTS
        ================================================= -->

        <?php if (
            isset($_GET["deleted"]) &&
            $_GET["deleted"] === "1"
        ): ?>

            <div class="success-message">

                ✅ User deleted successfully.

            </div>

        <?php endif; ?>


        <?php if (
            isset($_GET["error"]) &&
            $_GET["error"] === "self"
        ): ?>

            <div class="error-message">

                ⚠️ You cannot delete your own admin account.

            </div>

        <?php endif; ?>


        <!-- =================================================
             STATS
        ================================================= -->

        <div class="user-stats">


            <div class="user-stat">

                <div class="stat-icon">
                    👥
                </div>

                <div class="stat-label">
                    Total Users
                </div>

                <div class="stat-number">
                    <?php echo $total_users; ?>
                </div>

            </div>


            <div class="user-stat">

                <div class="stat-icon">
                    👤
                </div>

                <div class="stat-label">
                    Customers
                </div>

                <div class="stat-number">
                    <?php
                    echo $total_normal_users;
                    ?>
                </div>

            </div>


            <div class="user-stat">

                <div class="stat-icon">
                    🛡️
                </div>

                <div class="stat-label">
                    Administrators
                </div>

                <div class="stat-number">
                    <?php
                    echo $total_admins;
                    ?>
                </div>

            </div>


        </div>


        <!-- =================================================
             SEARCH
        ================================================= -->

        <div class="search-box">

            <form
                method="GET"
                class="search-form"
            >

                <input
                    type="text"
                    name="search"
                    class="search-input"
                    value="<?php
                    echo htmlspecialchars($search);
                    ?>"
                    placeholder="🔎 Search by name or email..."
                >


                <button
                    type="submit"
                    class="search-btn"
                >

                    Search

                </button>


                <?php if ($search !== ""): ?>

                    <a
                        href="users.php"
                        class="clear-btn"
                    >
                        Clear
                    </a>

                <?php endif; ?>

            </form>

        </div>


        <!-- =================================================
             USERS TABLE
        ================================================= -->

        <div class="users-table-box">


            <?php if (empty($users)): ?>


                <div class="empty-users">

                    <div class="empty-icon">
                        👥
                    </div>

                    <h2>
                        No Users Found
                    </h2>

                    <p>
                        No registered users match your search.
                    </p>

                </div>


            <?php else: ?>


                <table class="users-table">


                    <thead>

                        <tr>

                            <th>
                                User
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Role
                            </th>

                            <th>
                                Registered
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $users as $user
                        ): ?>


                            <?php

                            $first_letter =
                                strtoupper(
                                    substr(
                                        $user["name"],
                                        0,
                                        1
                                    )
                                );

                            ?>


                            <tr>


                                <!-- USER -->

                                <td>

                                    <div class="user-info">


                                        <div class="avatar">

                                            <?php
                                            echo htmlspecialchars(
                                                $first_letter
                                            );
                                            ?>

                                        </div>


                                        <div>

                                            <div class="user-name">

                                                <?php
                                                echo htmlspecialchars(
                                                    $user["name"]
                                                );
                                                ?>

                                            </div>


                                            <div class="user-email">

                                                User ID:
                                                #<?php
                                                echo $user["id"];
                                                ?>

                                            </div>

                                        </div>


                                    </div>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["email"]
                                    );
                                    ?>

                                </td>


                                <!-- ROLE -->

                                <td>


                                    <?php if (
                                        $user["role"] === "admin"
                                    ): ?>

                                        <span
                                            class="
                                                role-badge
                                                role-admin
                                            "
                                        >
                                            🛡️ Admin
                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="
                                                role-badge
                                                role-user
                                            "
                                        >
                                            👤 User
                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- DATE -->

                                <td>

                                    <?php
                                    echo date(
                                        "d M Y",
                                        strtotime(
                                            $user["created_at"]
                                        )
                                    );
                                    ?>

                                </td>


                                <!-- ACTION -->

                                <td>


                                    <?php if (
                                        intval(
                                            $user["id"]
                                        )
                                        ===
                                        intval(
                                            $_SESSION["user_id"]
                                        )
                                    ): ?>

                                        <span class="current-user">
                                            Current Account
                                        </span>

                                    <?php else: ?>

                                        <a
                                            href="
                                                users.php?action=delete&id=<?php
                                                echo $user["id"];
                                                ?>"
                                            class="delete-user"
                                            onclick="
                                                return confirm(
                                                    'Are you sure you want to delete this user?'
                                                );
                                            "
                                        >

                                            🗑 Delete

                                        </a>

                                    <?php endif; ?>


                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>


            <?php endif; ?>


        </div>


    </div>


</section>


</body>

</html>