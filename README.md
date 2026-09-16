# 🛒 E-Commerce Web Application

**GitHub Repository:**  
https://github.com/sahilravate979-netizen/ecommerce-web-application

---

## 📌 Project Description

The **E-Commerce Web Application** is a complete online shopping website developed using **PHP, MySQL, HTML5, CSS3, and JavaScript**.

The application allows customers to register and login, browse products, search for products, view product details, add products to a shopping cart, checkout, place orders, and view their order history.

The application also provides a separate **Admin Panel** where administrators can manage products, users, and customer orders.

This project was developed for educational and academic purposes to demonstrate the implementation of an e-commerce system using PHP and MySQL.

---

# 🛠️ Technologies Used

| Technology | Purpose |
|------------|---------|
| HTML5 | Website structure |
| CSS3 | Website styling and design |
| JavaScript | Client-side interaction |
| PHP | Backend development |
| MySQL | Database management |
| phpMyAdmin | Database management interface |
| XAMPP | Local server environment |

---

# ✨ Features

## 👤 Customer Features

- User Registration
- User Login
- User Logout
- Browse Products
- Search Products
- View Product Details
- Add Products to Cart
- Update Cart Quantity
- Remove Products from Cart
- Checkout
- Place Orders
- View Order History

---

## 🔐 Admin Features

- Admin Login
- Admin Dashboard
- Dashboard Statistics
- Add Products
- Edit Products
- Delete Products
- View All Products
- View Registered Users
- View Customer Orders
- Update Order Status

---

# 📁 Project Structure

```text
ecommerce/
│
├── admin/
│   ├── dashboard.php
│   ├── orders.php
│   ├── products.php
│   ├── add_product.php
│   ├── edit_product.php
│   └── users.php
│
├── assets/
│   ├── css/
│   │   └── style.css
│   │
│   ├── js/
│   │   └── script.js
│   │
│   └── images/
│       └── product images
│
├── config/
│   └── database.php
│
├── database/
│   └── ecommerce_db.sql
│
├── includes/
│
├── index.php
├── login.php
├── logout.php
├── register.php
├── products.php
├── product.php
├── cart.php
├── checkout.php
├── orders.php
├── test_db.php
│
└── README.md

---

# 💻 Requirements

Before running this project, install:

- XAMPP
- PHP
- MySQL
- Web Browser

XAMPP provides the Apache web server and MySQL database required to run this project locally.

---

# ▶️ How to Run the Project

## Step 1: Install and Start XAMPP

Install XAMPP and open the XAMPP Control Panel.

Start:

- Apache
- MySQL

Make sure both services are running.

---

## Step 2: Place the Project in XAMPP

Place the project folder inside:

```text
C:\xampp\htdocs\

🗄️ Database Setup

The project uses MySQL.

Database name:

ecommerce_db

The project includes the database backup file:

database/ecommerce_db.sql
Step 3: Open phpMyAdmin

Open:

http://localhost/phpmyadmin/

Step 4: Create the Database

In phpMyAdmin:

Click New
Create a database named:
ecommerce_db
Step 5: Import the Database
Select ecommerce_db
Click Import
Click Choose File
Select:
database/ecommerce_db.sql
Click Import / Go

The database contains the following tables:

users
products
cart_items
orders
order_items
⚙️ Database Configuration

Open:

config/database.php

For a default XAMPP installation:

$host = "localhost";
$username = "root";
$password = "";
$database = "ecommerce_db";

If the MySQL configuration is different, update these values accordingly.

🌐 Project URLs

After starting Apache and MySQL:

Main Website

http://localhost/ecommerce/

Register

http://localhost/ecommerce/register.php

Login

http://localhost/ecommerce/login.php

Products

http://localhost/ecommerce/products.php

Product Details

http://localhost/ecommerce/product.php

Cart

http://localhost/ecommerce/cart.php

Checkout

http://localhost/ecommerce/checkout.php

Order History

http://localhost/ecommerce/orders.php

🔐 Admin Panel
Admin Dashboard

http://localhost/ecommerce/admin/dashboard.php

Admin Products

http://localhost/ecommerce/admin/products.php

Add Product

http://localhost/ecommerce/admin/add_product.php

Admin Users

http://localhost/ecommerce/admin/users.php

Admin Orders

http://localhost/ecommerce/admin/orders.php

🗄️ View Database

The database can be viewed using phpMyAdmin:

http://localhost/phpmyadmin/

Select:

ecommerce_db

The following tables can be viewed:

users
products
cart_items
orders
order_items
