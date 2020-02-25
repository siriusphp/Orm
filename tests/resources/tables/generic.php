<?php

return [
    "CREATE TABLE `content` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `created_at` DATETIME NULL,
        `updated_at` DATETIME NULL,
        `deleted_at` DATETIME NULL,
        `content_type` VARCHAR(50) NULL DEFAULT NULL,
        `title` TEXT NULL,
        `summary` TEXT NULL,
        `description` TEXT NULL
    )",
    "CREATE TABLE `content_products` (
        `content_id` INTEGER PRIMARY KEY,
        `category_id` INTEGER NULL DEFAULT '0',
        `featured_image_id` INTEGER NULL DEFAULT '0',
        `sku` VARCHAR(50) NULL DEFAULT '0',
        `price` DECIMAL(10,4) NULL DEFAULT '0.0000'
    )",
    "CREATE TABLE `categories` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `parent_id` INTEGER NULL DEFAULT '0',
        `name` VARCHAR(200) NULL DEFAULT '0',
	    `details` TEXT NULL
    )",
    "CREATE TABLE `images` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `content_id` INTEGER  NOT NULL,
        `content_type` VARCHAR(50) NOT NULL,
        `name` VARCHAR(255) NOT NULL DEFAULT '0',
        `folder` VARCHAR(255) NULL DEFAULT '0'
    )",
//    "CREATE TABLE `image_relations` (
//        `image_id` INTEGER NULL DEFAULT NULL,
//        `content_id` INTEGER NULL DEFAULT NULL,
//        `content_type` VARCHAR(50) NOT NULL,
//        `priority` INTEGER NOT NULL DEFAULT '0'
//    )",
    "CREATE TABLE `products_tags` (
        `product_id` INTEGER NOT NULL,
        `tag_id` INTEGER NOT NULL,
        `position` SMALLINT(6) NOT NULL,
        PRIMARY KEY (`product_id`, `tag_id`)
    )",
    "CREATE TABLE `tags` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `name` VARCHAR(50) NOT NULL
    )"
];