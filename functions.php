<?php
// admin/functions.php

function get_image_url($image_path) {
    if (empty($image_path)) {
        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="140" height="140" viewBox="0 0 24 24" fill="#f3f4f6" stroke="#9ca3af" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>');
    }
    
    // Si c'est déjà une URL complète
    if (filter_var($image_path, FILTER_VALIDATE_URL)) {
        return $image_path;
    }
    
    // Si c'est un chemin absolu
    if (strpos($image_path, '/') === 0) {
        // Récupérer l'URL de base
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $base_url = $protocol . $host . '/';
        
        // Retirer le slash initial et ajouter l'URL de base
        return $base_url . ltrim($image_path, '/');
    }
    
    // Par défaut, traiter comme chemin relatif
    return '../uploads/products/' . $image_path;
}

function image_exists($image_path) {
    if (empty($image_path)) return false;
    
    // Convertir le chemin d'URL en chemin physique
    if (strpos($image_path, 'http') === 0) {
        // C'est une URL - difficile à vérifier sans curl
        return true; // Supposer que ça existe
    }
    
    $physical_path = __DIR__ . '/../' . ltrim($image_path, '/');
    return file_exists($physical_path);
}

function handle_image_upload($file_input_name, $product_id, $conn) {
    if (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'][0] === UPLOAD_ERR_NO_FILE) {
        return false;
    }
    
    $upload_dir = __DIR__ . '/../uploads/products/';
    
    // Créer le dossier s'il n'existe pas
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_images = [];
    
    foreach ($_FILES[$file_input_name]['tmp_name'] as $key => $tmp_name) {
        if ($_FILES[$file_input_name]['error'][$key] === UPLOAD_ERR_OK) {
            // Validation de type MIME
            $mime_type = mime_content_type($tmp_name);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($mime_type, $allowed_types)) {
                continue; // Ignorer les fichiers non-images
            }
            
            // Générer un nom de fichier sécurisé
            $original_name = basename($_FILES[$file_input_name]['name'][$key]);
            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($original_name, PATHINFO_FILENAME));
            $file_name = time() . '_' . $safe_name . '_' . uniqid() . '.' . $extension;
            $file_path = $upload_dir . $file_name;
            
            // Déplacer le fichier
            if (move_uploaded_file($tmp_name, $file_path)) {
                // Définir les permissions
                chmod($file_path, 0644);
                
                // Enregistrer dans la base de données
                $image_url = '/uploads/products/' . $file_name;
                
                // Vérifier si c'est la première image (devient principale)
                $is_main = ($key === 0) ? 1 : 0;
                
                $stmt = $conn->prepare("
                    INSERT INTO product_images (product_id, image_url, is_main, upload_date) 
                    VALUES (?, ?, ?, NOW())
                ");
                $stmt->bind_param("isi", $product_id, $image_url, $is_main);
                
                if ($stmt->execute()) {
                    $uploaded_images[] = [
                        'id' => $stmt->insert_id,
                        'url' => $image_url,
                        'is_main' => $is_main
                    ];
                }
                $stmt->close();
            }
        }
    }
    
    return $uploaded_images;
}

function get_product_images($product_id, $conn) {
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, upload_date DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
    return $images;
}

function set_main_image($image_id, $product_id, $conn) {
    // D'abord, toutes les images de ce produit ne sont plus principales
    $stmt_reset = $conn->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
    $stmt_reset->bind_param("i", $product_id);
    $stmt_reset->execute();
    $stmt_reset->close();
    
    // Ensuite, définir l'image spécifiée comme principale
    $stmt_set = $conn->prepare("UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?");
    $stmt_set->bind_param("ii", $image_id, $product_id);
    $result = $stmt_set->execute();
    $stmt_set->close();
    
    return $result;
}
?>