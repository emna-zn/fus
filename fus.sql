-- ============================================
-- FICHIER SQL COMPLET - FUS DENIM PORTAL
-- Création de la base de données + données de test
-- ============================================

-- Créer la base de données si elle n'existe pas
CREATE DATABASE IF NOT EXISTS fus_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fus_portal;

-- ============================================
-- CRÉATION DES TABLES
-- ============================================

-- Table : Utilisateurs (clients B2B + admin)
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company_name VARCHAR(255),
    country VARCHAR(100),
    contact_person VARCHAR(255),
    phone VARCHAR(50),
    role ENUM('admin', 'client') DEFAULT 'client',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Table : Collections
CREATE TABLE IF NOT EXISTS collections (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255),
    season VARCHAR(100),
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_public (is_public),
    INDEX idx_season (season)
);

-- Table : Produits (articles denim)
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    collection_id INT,
    reference VARCHAR(100) UNIQUE,
    name VARCHAR(255),
    description TEXT,
    fabric_composition TEXT,
    weight_oz VARCHAR(50),
    available_colors TEXT,
    available_sizes TEXT,
    wash_types TEXT,
    certifications TEXT,
    moq INT,
    production_time_days INT,
    pdf_spec_url VARCHAR(500),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (collection_id) REFERENCES collections(id) ON DELETE SET NULL,
    INDEX idx_reference (reference),
    INDEX idx_collection (collection_id),
    INDEX idx_active_product (is_active),
    INDEX idx_moq (moq)
);

-- Table : Images produits
CREATE TABLE IF NOT EXISTS product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    image_url VARCHAR(500),
    is_main BOOLEAN DEFAULT FALSE,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product (product_id),
    INDEX idx_main (is_main)
);

-- Table : Commandes
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id INT,
    reference VARCHAR(100) UNIQUE,
    status ENUM('received', 'validating', 'confirmed', 'production', 'shipped') DEFAULT 'received',
    total_items INT,
    total_value DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    shipping_address TEXT,
    estimated_delivery DATE,
    actual_delivery DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status),
    INDEX idx_reference_order (reference),
    INDEX idx_created (created_at)
);

-- Table : Détails commande
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    color VARCHAR(100),
    size VARCHAR(20),
    wash_type VARCHAR(100),
    quantity INT,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    subtotal DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_product_item (product_id)
);

-- Table : Demandes d'accès (pour nouveaux clients)
CREATE TABLE IF NOT EXISTS access_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    country VARCHAR(100),
    website VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    admin_notes TEXT,
    INDEX idx_status_req (status),
    INDEX idx_requested (requested_at)
);

-- Table : Messages de contact
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_msg (email),
    INDEX idx_read (is_read),
    INDEX idx_submitted (submitted_at)
);

-- ============================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================

-- 1. INSERER LES UTILISATEURS (admin + clients B2B)
INSERT INTO users (email, password, company_name, country, contact_person, phone, role, is_active) VALUES
('admin@fus-denim.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'FUS Denim Admin', 'Tunisia', 'Admin User', '+216 71 123 456', 'admin', TRUE),
('contact@parisfashion.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Paris Fashion House', 'France', 'Jean Dupont', '+33 1 23 45 67 89', 'client', TRUE),
('orders@berlindenim.de', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Berlin Denim Co.', 'Germany', 'Hans Müller', '+49 30 123 456 78', 'client', TRUE),
('purchasing@krakowapparel.pl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Krakow Apparel', 'Poland', 'Anna Kowalski', '+48 12 345 67 89', 'client', TRUE),
('info@londonstyle.co.uk', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'London Style Ltd', 'United Kingdom', 'Emily Brown', '+44 20 7123 4567', 'client', TRUE),
('b2b@madridtextiles.es', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Madrid Textiles', 'Spain', 'Carlos Garcia', '+34 91 123 45 67', 'client', TRUE),
('newclient@italydesign.it', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Italy Design Studio', 'Italy', 'Marco Rossi', '+39 02 1234 5678', 'client', FALSE),
('request@amsterdamwear.nl', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Amsterdam Wear', 'Netherlands', 'Lisa Van Dijk', '+31 20 123 4567', 'client', FALSE);

-- 2. INSERER LES COLLECTIONS
INSERT INTO collections (name, season, description, is_public) VALUES
('Heritage Collection', 'SS2024', 'Timeless denim pieces with artisanal finishes and classic silhouettes. Inspired by vintage American workwear with modern comfort features.', TRUE),
('Modern Edge', 'AW2024', 'Contemporary cuts with innovative treatments and modern styling. Features laser finishing, ozone washing, and sustainable dye techniques.', TRUE),
('Sustainable Future', 'SS2024', 'Eco-conscious denim using organic materials and water-saving techniques. Each piece saves approximately 30 liters of water compared to conventional denim.', TRUE),
('Premium Raw', 'AW2024', 'High-quality raw denim for premium brands. Made from Japanese selvedge and Italian mills. Private collection for select partners.', FALSE),
('Urban Street', 'SS2024', 'Streetwear-inspired denim with bold washes and urban aesthetics. Designed for the modern urban consumer with oversized fits and utility details.', TRUE),
('Luxury Comfort', 'AW2024', 'Premium stretch denim combining luxury feel with maximum comfort. Features cashmere blends and innovative stretch technologies.', FALSE),
('Workwear Essentials', 'SS2024', 'Durable workwear denim with reinforced stitching and functional details. Designed for both fashion and function.', TRUE),
('Minimalist Line', 'AW2024', 'Clean, minimalist designs with focus on fabric quality and perfect fit. Less is more approach to denim design.', TRUE);

-- 3. INSERER LES PRODUITS (ARTICLES DENIM)
INSERT INTO products (collection_id, reference, name, description, fabric_composition, weight_oz, available_colors, available_sizes, wash_types, certifications, moq, production_time_days, pdf_spec_url, is_active) VALUES
(1, 'FUS-HC-001', 'Classic Straight Jeans', 'Timeless straight-leg jeans with vintage finish and five-pocket design. Perfect for everyday wear.', '98% Cotton, 2% Elastane', '12.5', 'Indigo,Black,Light Blue,Medium Wash', '28,30,32,34,36,38,40', 'Rinse,Stone Wash,Acid Wash,Vintage', 'OEKO-TEX® Standard 100, ISO 9001, REACH', 100, 45, '/specs/fus-hc-001.pdf', TRUE),
(1, 'FUS-HC-002', 'Vintage Denim Jacket', 'Classic trucker jacket with distressed details, metal buttons, and corduroy collar. True vintage reproduction.', '100% Organic Cotton', '10', 'Indigo,Black,Faded Blue', 'S,M,L,XL,XXL', 'Vintage,Distressed,Light Wash,Heavy Wash', 'GOTS, OEKO-TEX®, Organic 100', 50, 60, '/specs/fus-hc-002.pdf', TRUE),
(1, 'FUS-HC-003', 'Denim Overalls', 'Classic denim overalls with adjustable straps and multiple pockets. Workwear inspired.', '100% Cotton', '14', 'Indigo,Black', 'S,M,L,XL', 'Rinse,Stone Wash', 'ISO 9001, ISO 14001', 75, 55, '/specs/fus-hc-003.pdf', TRUE),

(2, 'FUS-ME-101', 'Slim Fit Stretch Jeans', 'Modern slim fit with comfort stretch technology. Tapered leg and mid-rise waist.', '92% Cotton, 6% Polyester, 2% Elastane', '11', 'Dark Indigo,Charcoal,Black,Navy', '28,29,30,31,32,33,34,36', 'Laser Finish,Enzyme Wash,Ozone,Stone Wash', 'REACH, ISO 9001, Bluesign®', 150, 40, '/specs/fus-me-101.pdf', TRUE),
(2, 'FUS-ME-102', 'Cargo Denim Pants', 'Utility-style pants with multiple pockets, reinforced knees, and adjustable hem.', '98% Cotton, 2% Elastane', '14', 'Army Green,Black,Indigo,Khaki', '30,32,34,36,38,40', 'Garment Wash,Rinse,Stone Wash', 'OEKO-TEX®, ISO 14001, REACH', 75, 55, '/specs/fus-me-102.pdf', TRUE),
(2, 'FUS-ME-103', 'Destroyed Skinny Jeans', 'Heavily distressed skinny jeans with ripped details and raw hem.', '95% Cotton, 5% Elastane', '10.5', 'Light Blue,Medium Wash,Black', '25,26,27,28,29,30', 'Destroyed,Heavy Distress,Acid Wash', 'OEKO-TEX®, ISO 9001', 120, 50, '/specs/fus-me-103.pdf', TRUE),

(3, 'FUS-SF-201', 'Organic Skinny Jeans', 'Eco-friendly skinny jeans from 100% organic cotton. Low-impact dyes used throughout.', '100% Organic Cotton', '10.5', 'Natural,Light Blue,Indigo,Ecru', '25,26,27,28,29,30,32', 'Natural Wash,Enzyme Bleach,Soft Wash', 'GOTS, Organic Content Standard, OEKO-TEX®', 200, 50, '/specs/fus-sf-201.pdf', TRUE),
(3, 'FUS-SF-202', 'Recycled Denim Jacket', 'Jacket made from post-consumer recycled denim. Each jacket uses approximately 2 recycled jeans.', '70% Recycled Cotton, 30% Organic Cotton', '12', 'Medium Blue,Patchwork,Light Denim', 'XS,S,M,L,XL', 'Recycled Look,Light Distress,Natural', 'GRS, OEKO-TEX®, Recycled Standard', 100, 65, '/specs/fus-sf-202.pdf', TRUE),
(3, 'FUS-SF-203', 'Hemp Blend Jeans', 'Sustainable jeans made from hemp-cotton blend. Naturally anti-bacterial and durable.', '55% Hemp, 45% Organic Cotton', '11.5', 'Natural,Hemp Green,Ecru', '30,32,34,36,38', 'Natural,Unwashed,Garment Dyed', 'GOTS, Organic, Hemp Certified', 150, 70, '/specs/fus-sf-203.pdf', TRUE),

(4, 'FUS-PR-301', 'Selvedge Raw Jeans', 'Premium Japanese selvedge denim, unwashed. Will develop unique fades with wear.', '100% Cotton (Japanese Selvedge)', '14.5', 'Raw Indigo', '28,29,30,31,32,33,34,36', 'Raw (Unwashed)', 'ISO 9001, Handcrafted, Selvedge', 50, 70, '/specs/fus-pr-301.pdf', TRUE),
(4, 'FUS-PR-302', 'Italian Denim Chinos', 'Luxury chinos from Italian denim mill. Superior drape and hand feel.', '98% Cotton, 2% Lycra', '11', 'Khaki,Navy,Olive,Charcoal', '30,31,32,33,34,36,38,40', 'Garment Dyed,Soft Finish,Mercerized', 'Made in Italy, OEKO-TEX®, Luxury', 75, 60, '/specs/fus-pr-302.pdf', TRUE),

(5, 'FUS-US-401', 'Oversized Denim Shirt', 'Streetwear oversized shirt with bold wash and dropped shoulders.', '100% Cotton', '8.5', 'Light Wash,Acid Wash,Black,Indigo', 'S,M,L,XL,XXL,XXXL', 'Acid Wash,Heavy Stone,Bleached', 'ISO 9001, REACH', 100, 45, '/specs/fus-us-401.pdf', TRUE),
(5, 'FUS-US-402', 'Jogger Denim Pants', 'Denim joggers with elastic waist, drawstring, and tapered elastic cuffs.', '92% Cotton, 8% Elastane', '10', 'Dark Indigo,Black,Grey,Navy', 'S,M,L,XL,XXL', 'Rinse,Garment Wash,Soft Touch', 'OEKO-TEX®, ISO 9001', 120, 40, '/specs/fus-us-402.pdf', TRUE),

(6, 'FUS-LC-501', 'Luxury Stretch Jeans', 'Ultra-comfortable premium stretch denim with memory technology.', '75% Cotton, 22% Polyester, 3% Elastane', '9.5', 'Mid Blue,Black,Dark Grey,Navy', '24,25,26,27,28,29,30,32', 'Soft Touch, Enzyme, Garment Wash', 'ISO 9001, Luxury Standard, OEKO-TEX®', 80, 50, '/specs/fus-lc-501.pdf', TRUE),
(6, 'FUS-LC-502', 'Cashmere Blend Denim', 'Denim blended with cashmere for ultimate softness and luxury feel.', '85% Cotton, 15% Cashmere', '10', 'Dark Indigo,Charcoal,Black', '28,29,30,31,32,34,36', 'Premium Soft Wash, Enzyme', 'Luxury Certification, Cashmere Mark', 40, 75, '/specs/fus-lc-502.pdf', TRUE),

(7, 'FUS-WE-601', 'Double-Knee Work Pants', 'Durable work pants with reinforced double-knee and tool pockets.', '100% Cotton (Canvas)', '16', 'Indigo,Brown,Black', '30,32,34,36,38,40,42', 'Rinse, Garment Wash', 'ISO 9001, Durability Certified', 100, 60, '/specs/fus-we-601.pdf', TRUE),

(8, 'FUS-ML-701', 'Minimalist Straight Jeans', 'Clean design straight leg jeans with hidden rivets and minimal branding.', '99% Cotton, 1% Elastane', '12', 'Dark Indigo,Black,Raw', '28,30,32,34,36', 'Rinse, Unwashed, Soft Wash', 'ISO 9001, Minimalist Design', 90, 45, '/specs/fus-ml-701.pdf', TRUE);

-- 4. INSERER LES IMAGES PRODUITS
INSERT INTO product_images (product_id, image_url, is_main) VALUES
(1, 'https://images.unsplash.com/photo-1544441893-675973e31985?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
(1, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),
(1, 'https://images.unsplash.com/photo-1520256862855-398228c41684?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),
(1, 'https://images.unsplash.com/photo-1602293589930-45aad59ba3ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),

(2, 'https://images.unsplash.com/photo-1551028719-00167b16eac5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
(2, 'https://images.unsplash.com/photo-1552374196-c4e7ffc6e126?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),

(3, 'https://images.unsplash.com/photo-1594633312681-425c7b97ccd1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(4, 'https://images.unsplash.com/photo-1520006403909-838d6b92c22e?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),
(4, 'https://images.unsplash.com/photo-1582418702059-97ebafb35d09?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', FALSE),

(5, 'https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(6, 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(7, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(8, 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(9, 'https://images.unsplash.com/photo-1543087903-1ac2ec7aa8c5?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(10, 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(11, 'https://images.unsplash.com/photo-1586790170083-2f9ceadc732d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(12, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(13, 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(14, 'https://images.unsplash.com/photo-1565084888279-aca607ecce0c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(15, 'https://images.unsplash.com/photo-1542272604-787c3835535d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(16, 'https://images.unsplash.com/photo-1591047139829-d91aecb6caea?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(17, 'https://images.unsplash.com/photo-1541099649105-f69ad21f3246?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE),

(18, 'https://images.unsplash.com/photo-1576871337632-b9aef4c17ab9?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80', TRUE);

-- 5. INSERER LES COMMANDES (HISTORIQUE)
INSERT INTO orders (client_id, reference, status, total_items, total_value, notes, shipping_address, estimated_delivery, created_at) VALUES
(2, 'FUS-ORD-2024-001', 'shipped', 250, 12500.00, 'Urgent delivery requested for Paris Fashion Week. Please ensure perfect stitching.', '123 Rue de la Mode, 75001 Paris, France', '2024-02-28', '2024-01-15 10:30:00'),
(2, 'FUS-ORD-2024-002', 'production', 150, 7500.00, 'Special attention to stitching quality. Need approval for first 50 pieces.', '123 Rue de la Mode, 75001 Paris, France', '2024-04-15', '2024-02-20 14:45:00'),
(3, 'FUS-ORD-2024-003', 'confirmed', 300, 18000.00, 'Standard delivery, no rush. Please use eco-friendly packaging.', 'Berliner Strasse 45, 10115 Berlin, Germany', '2024-05-10', '2024-03-10 09:15:00'),
(4, 'FUS-ORD-2024-004', 'validating', 100, 6000.00, 'Need samples approval before mass production. Send 3 pieces for each item.', 'ul. Fashionowa 12, 30-001 Krakow, Poland', '2024-05-20', '2024-03-25 16:20:00'),
(5, 'FUS-ORD-2024-005', 'received', 180, 9000.00, 'First order from new client. Please include catalog and samples of new collections.', '15 Fashion Street, London W1F 8PS, UK', '2024-06-01', '2024-04-05 11:00:00'),
(6, 'FUS-ORD-2024-006', 'shipped', 220, 13200.00, 'Split shipment: 120 now, 100 next month. Invoice separately.', 'Calle Denim 67, 28013 Madrid, Spain', '2024-03-30', '2024-02-28 13:30:00'),
(3, 'FUS-ORD-2024-007', 'shipped', 180, 10800.00, 'Repeat order from Berlin Denim Co. Same specifications as order #003.', 'Berliner Strasse 45, 10115 Berlin, Germany', '2024-04-10', '2024-03-05 11:20:00'),
(2, 'FUS-ORD-2024-008', 'production', 200, 10000.00, 'New collection items. Need quick turnaround for summer season.', '123 Rue de la Mode, 75001 Paris, France', '2024-05-05', '2024-04-01 09:45:00');

-- 6. INSERER LES DÉTAILS DES COMMANDES
INSERT INTO order_items (order_id, product_id, color, size, wash_type, quantity, unit_price, subtotal) VALUES
-- Détails pour commande 1
(1, 1, 'Indigo', '32', 'Stone Wash', 100, 25.00, 2500.00),
(1, 1, 'Black', '34', 'Rinse', 75, 25.00, 1875.00),
(1, 2, 'Indigo', 'M', 'Vintage', 50, 30.00, 1500.00),
(1, 2, 'Indigo', 'L', 'Vintage', 25, 30.00, 750.00),

-- Détails pour commande 2
(2, 3, 'Dark Indigo', '30', 'Laser Finish', 80, 28.00, 2240.00),
(2, 3, 'Charcoal', '32', 'Enzyme Wash', 70, 28.00, 1960.00),

-- Détails pour commande 3
(3, 5, 'Natural', '28', 'Natural Wash', 120, 32.00, 3840.00),
(3, 5, 'Light Blue', '30', 'Enzyme Bleach', 90, 32.00, 2880.00),
(3, 6, 'Medium Blue', 'L', 'Light Distress', 90, 35.00, 3150.00),

-- Détails pour commande 4
(4, 9, 'Light Wash', 'XL', 'Acid Wash', 50, 40.00, 2000.00),
(4, 10, 'Dark Indigo', 'L', 'Rinse', 50, 42.00, 2100.00),

-- Détails pour commande 5
(5, 4, 'Army Green', '36', 'Garment Wash', 90, 26.00, 2340.00),
(5, 4, 'Black', '38', 'Rinse', 90, 26.00, 2340.00),

-- Détails pour commande 6
(6, 11, 'Mid Blue', '28', 'Soft Touch', 70, 45.00, 3150.00),
(6, 11, 'Black', '30', 'Enzyme', 75, 45.00, 3375.00),
(6, 12, 'Dark Indigo', '32', 'Premium Soft Wash', 75, 48.00, 3600.00),

-- Détails pour commande 7
(7, 5, 'Natural', '30', 'Natural Wash', 100, 32.00, 3200.00),
(7, 6, 'Medium Blue', 'M', 'Light Distress', 80, 35.00, 2800.00),

-- Détails pour commande 8
(8, 13, 'Dark Indigo', '32', 'Rinse', 120, 28.00, 3360.00),
(8, 14, 'Black', '34', 'Soft Wash', 80, 30.00, 2400.00);

-- 7. INSERER LES DEMANDES D'ACCÈS
INSERT INTO access_requests (company_name, contact_person, email, phone, country, website, message, status, requested_at) VALUES
('Milan Luxury Brands', 'Giovanni Conti', 'giovanni@milanluxury.it', '+39 02 9876 5432', 'Italy', 'www.milanluxury.it', 'We are interested in your premium collections for our luxury stores in Milan and Rome. Please grant us access to view your catalog.', 'pending', '2024-04-10 14:30:00'),
('Scandinavian Minimalist', 'Erik Johansen', 'erik@scanminimalist.se', '+46 8 123 4567', 'Sweden', 'www.scanminimalist.se', 'Looking for sustainable denim options for our eco-conscious brand. Particularly interested in your Sustainable Future collection.', 'approved', '2024-03-25 11:15:00'),
('Vienna Fashion Group', 'Klaus Bauer', 'klaus@viennafashion.at', '+43 1 234 5678', 'Austria', 'www.viennafashion.at', 'We supply to major retailers in Austria and Germany. Request access to evaluate your products for our next season.', 'rejected', '2024-04-05 16:45:00'),
('Lisbon Streetwear', 'Miguel Silva', 'miguel@lisbonstreetwear.pt', '+351 21 345 6789', 'Portugal', 'www.lisbonstreetwear.pt', 'Urban streetwear brand expanding across Europe. Need denim supplier for our collections.', 'pending', '2024-04-12 10:20:00');

-- 8. INSERER LES MESSAGES DE CONTACT
INSERT INTO contact_messages (name, email, subject, message, is_read, submitted_at) VALUES
('Alexandre Martin', 'alex@frenchretailer.fr', 'Partnership Inquiry', 'Hello, I represent a chain of retail stores in France. We are interested in carrying your denim line. Can we schedule a call?', TRUE, '2024-03-15 09:30:00'),
('Sarah Johnson', 'sarah@ecobrand.com', 'Sustainable Denim', 'I am researching sustainable denim manufacturers for my new eco-brand. Can you provide more information about your environmental practices?', FALSE, '2024-04-08 14:20:00'),
('Thomas Weber', 'thomas@denimblog.de', 'Factory Visit Request', 'I run a popular denim blog in Germany. Would it be possible to arrange a visit to your factory for a feature article?', FALSE, '2024-04-10 11:45:00'),
('Maria Rodriguez', 'maria@spanishdesign.es', 'Custom Denim Development', 'We are a design studio in Barcelona looking to develop custom denim fabrics. Do you offer fabric development services?', TRUE, '2024-04-05 16:10:00');

-- ============================================
-- MISE À JOUR DES TOTAUX DES COMMANDES
-- ============================================
UPDATE orders o
SET total_value = (
    SELECT SUM(subtotal) 
    FROM order_items oi 
    WHERE oi.order_id = o.id
)
WHERE id IN (1,2,3,4,5,6,7,8);

-- ============================================
-- CRÉATION DE VUES UTILES
-- ============================================

-- Vue : Produits actifs avec informations collection
CREATE OR REPLACE VIEW vw_active_products AS
SELECT 
    p.id,
    p.reference,
    p.name,
    c.name as collection_name,
    c.season,
    p.fabric_composition,
    p.weight_oz,
    p.moq,
    p.production_time_days,
    p.is_active
FROM products p
LEFT JOIN collections c ON p.collection_id = c.id
WHERE p.is_active = TRUE
ORDER BY p.reference;

-- Vue : Commandes avec informations client
CREATE OR REPLACE VIEW vw_orders_with_client AS
SELECT 
    o.id,
    o.reference,
    o.status,
    o.total_items,
    o.total_value,
    o.created_at,
    u.company_name,
    u.contact_person,
    u.country,
    u.email
FROM orders o
LEFT JOIN users u ON o.client_id = u.id
ORDER BY o.created_at DESC;

-- Vue : Statistiques produits par collection
CREATE OR REPLACE VIEW vw_collection_stats AS
SELECT 
    c.id,
    c.name,
    c.season,
    COUNT(p.id) as product_count,
    SUM(p.moq) as total_moq,
    AVG(p.production_time_days) as avg_production_days
FROM collections c
LEFT JOIN products p ON c.id = p.collection_id
WHERE p.is_active = TRUE
GROUP BY c.id, c.name, c.season;

-- ============================================
-- CRÉATION DE PROCÉDURES STOCKÉES
-- ============================================

-- Procédure : Mettre à jour le statut d'une commande
DELIMITER //
CREATE PROCEDURE UpdateOrderStatus(
    IN p_order_id INT,
    IN p_new_status VARCHAR(20)
)
BEGIN
    UPDATE orders 
    SET status = p_new_status, 
        updated_at = CURRENT_TIMESTAMP