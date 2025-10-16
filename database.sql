CREATE DATABASE IF NOT EXISTS petshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petshop;

DROP TABLE IF EXISTS tbl_product_category;
DROP TABLE IF EXISTS tbl_order_detail;
DROP TABLE IF EXISTS tbl_product;
DROP TABLE IF EXISTS tbl_category;
DROP TABLE IF EXISTS tbl_delivery;
DROP TABLE IF EXISTS tbl_payment;
DROP TABLE IF EXISTS tbl_order;
DROP TABLE IF EXISTS tbl_adoption_request;
DROP TABLE IF EXISTS tbl_pet_info;
DROP TABLE IF EXISTS tbl_pet_owner;
DROP TABLE IF EXISTS tbl_user_info;
DROP TABLE IF EXISTS tbl_user_account;

CREATE TABLE tbl_user_account (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('owner', 'customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE tbl_user_info (
    user_info_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    address TEXT NULL,
    contact_number VARCHAR(30) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_info_user_account FOREIGN KEY (user_id) REFERENCES tbl_user_account (user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_pet_owner (
    pet_owner_id INT AUTO_INCREMENT PRIMARY KEY,
    user_info_id INT NOT NULL,
    ownership_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    CONSTRAINT fk_pet_owner_user_info FOREIGN KEY (user_info_id) REFERENCES tbl_user_info (user_info_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_pet_info (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_owner_id INT NULL,
    pet_name VARCHAR(120) NOT NULL,
    species VARCHAR(80) NOT NULL,
    breed VARCHAR(120) NULL,
    age INT NULL,
    description TEXT NULL,
    status ENUM('available', 'adopted') NOT NULL DEFAULT 'available',
    CONSTRAINT fk_pet_info_owner FOREIGN KEY (pet_owner_id) REFERENCES tbl_pet_owner (pet_owner_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_adoption_request (
    adoption_id INT AUTO_INCREMENT PRIMARY KEY,
    user_info_id INT NOT NULL,
    pet_id INT NOT NULL,
    request_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_adoption_user FOREIGN KEY (user_info_id) REFERENCES tbl_user_info (user_info_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_adoption_pet FOREIGN KEY (pet_id) REFERENCES tbl_pet_info (pet_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_order (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_info_id INT NOT NULL,
    order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_order_user_info FOREIGN KEY (user_info_id) REFERENCES tbl_user_info (user_info_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_payment (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    payment_method ENUM('cash', 'card', 'paypal') NOT NULL DEFAULT 'cash',
    payment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_payment_order FOREIGN KEY (order_id) REFERENCES tbl_order (order_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_delivery (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_date DATETIME NULL,
    status ENUM('pending', 'shipped', 'delivered') NOT NULL DEFAULT 'pending',
    CONSTRAINT fk_delivery_order FOREIGN KEY (order_id) REFERENCES tbl_order (order_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE tbl_product (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_product_category FOREIGN KEY (category_id) REFERENCES tbl_category (category_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_order_detail (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_detail_order FOREIGN KEY (order_id) REFERENCES tbl_order (order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_order_detail_product FOREIGN KEY (product_id) REFERENCES tbl_product (product_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE tbl_product_category (
    product_category_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    category_id INT NOT NULL,
    UNIQUE KEY uq_product_category (product_id, category_id),
    CONSTRAINT fk_product_category_product FOREIGN KEY (product_id) REFERENCES tbl_product (product_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_product_category_category FOREIGN KEY (category_id) REFERENCES tbl_category (category_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
