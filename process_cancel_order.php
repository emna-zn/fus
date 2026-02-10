<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id'])) {
    $_SESSION['error'] = 'Requête invalide';
    header('Location: orders.php');
    exit();
}

$order_id = (int)$_POST['order_id'];
$client_id = $_SESSION['user_id'];

$database = new Database();
$conn = $database->getConnection();
$check_query = "SELECT id, status FROM orders WHERE id = ? AND client_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $order_id, $client_id);
$check_stmt->execute();
$order = $check_stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = 'Commande non trouvée ou accès non autorisé';
    header('Location: orders.php');
    exit();
}
if ($order['status'] !== 'received') {
    $_SESSION['error'] = 'Seules les commandes avec statut "Reçue" peuvent être supprimées';
    header('Location: orders.php');
    exit();
}
$conn->begin_transaction();

try {
    $delete_items = "DELETE FROM order_items WHERE order_id = ?";
    $stmt1 = $conn->prepare($delete_items);
    $stmt1->bind_param("i", $order_id);
    $stmt1->execute();
    
    $delete_order = "DELETE FROM orders WHERE id = ?";
    $stmt2 = $conn->prepare($delete_order);
    $stmt2->bind_param("i", $order_id);
    $stmt2->execute();
    
    $conn->commit();
    
    $_SESSION['success'] = 'Commande supprimée avec succès';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Erreur lors de la suppression : ' . $e->getMessage();
}

header('Location: orders.php');
exit();
?>