-- DIC CMS database, MySQL 8 / MariaDB compatible
CREATE TABLE IF NOT EXISTS admins (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('super_admin','editor') NOT NULL DEFAULT 'editor',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS content (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 type ENUM('teacher','notice','slide','page','news','event','gallery','admission','academic') NOT NULL,
 title VARCHAR(255) NOT NULL,
 slug VARCHAR(190) DEFAULT NULL,
 body MEDIUMTEXT,
 image_url VARCHAR(1000),
 subject VARCHAR(190),
 designation VARCHAR(190),
 department VARCHAR(190),
 attachment_url VARCHAR(1000),
 sort_order INT NOT NULL DEFAULT 0,
 published TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY unique_type_slug (type,slug),
 INDEX public_items (type,published,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_settings (
 setting_key VARCHAR(100) PRIMARY KEY,
 value_json LONGTEXT NOT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
