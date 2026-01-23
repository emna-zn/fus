<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'client') {
    header('Location: login.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit();
}

$database = new Database();
$conn = $database->getConnection();
$client_id = $_SESSION['user_id'];
$order_id = intval($_GET['id']);
$query = "SELECT o.*, u.company_name, u.contact_person, u.email, u.phone, u.country
          FROM orders o 
          JOIN users u ON o.client_id = u.id 
          WHERE o.id = ? AND o.client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $order_id, $client_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: orders.php');
    exit();
}
$items_query = $conn->prepare("SELECT oi.*, p.name as product_name, p.reference as product_reference, p.moq
                               FROM order_items oi 
                               JOIN products p ON oi.product_id = p.id 
                               WHERE oi.order_id = ?");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items = $items_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation | FUS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0a1931;
            --accent-gold: #d4af37;
        }
        
        .confirmation-card {
            border: 2px solid var(--accent-gold);
            border-radius: 15px;
            padding: 30px;
            background: white;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .order-header {
            background: linear-gradient(135deg, var(--primary-dark), #1a3a5f);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .status-timeline {
            display: flex;
            justify-content: space-between;
            margin: 40px 0;
            position: relative;
        }
        
        .status-timeline:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background-color: #dee2e6;
            z-index: 1;
        }
        
        .status-step {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .status-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #dee2e6;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 18px;
        }
        
        .status-step.active .status-dot {
            background-color: var(--accent-gold);
        }
        
        .status-step.completed .status-dot {
            background-color: #28a745;
        }
        
        .print-only {
            display: none;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-only {
                display: block !important;
            }
            
            .confirmation-card {
                border: none;
                box-shadow: none;
            }
            
            body {
                font-size: 12pt;
            }
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="confirmation-card">
            <!-- En-tête d'impression -->
            <div class="print-only mb-4">
                <div class="row">
                    <div class="col-6">
                        <h3>FUS Denim</h3>
                        <p class="mb-0">Tunis, Tunisia</p>
                        <p class="mb-0">contact@fus-denim.com</p>
                    </div>
                    <div class="col-6 text-end">
                        <h3>Order Confirmation</h3>
                        <p class="mb-0">Date: <?php echo date('F d, Y'); ?></p>
                    </div>
                </div>
                <hr>
            </div>
            
            <!-- En-tête de commande -->
            <div class="order-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="h3 mb-2"><i class="fas fa-check-circle me-2"></i>Order Submitted Successfully!</h1>
                        <p class="mb-0">Thank you for your order. Your request has been received and is being processed.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="bg-white text-dark rounded p-3 d-inline-block">
                            <strong>Order #:</strong><br>
                            <span class="h4 mb-0"><?php echo htmlspecialchars($order['reference']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Timeline du statut -->
            <div class="status-timeline">
                <?php
                $status_steps = [
                    ['status' => 'received', 'icon' => 'fa-inbox', 'label' => 'Received', 'description' => 'Order received'],
                    ['status' => 'validating', 'icon' => 'fa-clipboard-check', 'label' => 'Validating', 'description' => 'Under review'],
                    ['status' => 'confirmed', 'icon' => 'fa-check-double', 'label' => 'Confirmed', 'description' => 'Order confirmed'],
                    ['status' => 'production', 'icon' => 'fa-industry', 'label' => 'Production', 'description' => 'In production'],
                    ['status' => 'shipped', 'icon' => 'fa-truck', 'label' => 'Shipped', 'description' => 'Order shipped']
                ];
                
                $current_status_index = array_search($order['status'], array_column($status_steps, 'status'));
                ?>
                
                <?php foreach ($status_steps as $index => $step): ?>
                    <div class="status-step <?php echo $index == $current_status_index ? 'active' : ($index < $current_status_index ? 'completed' : ''); ?>">
                        <div class="status-dot">
                            <?php if ($index < $current_status_index): ?>
                                <i class="fas fa-check"></i>
                            <?php elseif ($index == $current_status_index): ?>
                                <i class="fas <?php echo $step['icon']; ?>"></i>
                            <?php else: ?>
                                <i class="fas <?php echo $step['icon']; ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="status-label">
                            <small class="d-block"><?php echo $step['label']; ?></small>
                            <small class="text-muted"><?php echo $step['description']; ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Détails de commande -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="fas fa-building me-2"></i>Client Information</h5>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-1"><strong>Company:</strong> <?php echo htmlspecialchars($order['company_name']); ?></p>
                            <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_person']); ?></p>
                            <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                            <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            <p class="mb-0"><strong>Country:</strong> <?php echo htmlspecialchars($order['country']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h5><i class="fas fa-shipping-fast me-2"></i>Order Details</h5>
                    <div class="card">
                        <div class="card-body">
                            <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1"><strong>Status:</strong> <span class="badge bg-primary"><?php echo ucfirst($order['status']); ?></span></p>
                            <p class="mb-1"><strong>Total Items:</strong> <?php echo $order['total_items']; ?> units</p>
                            <?php if ($order['estimated_delivery']): ?>
                                <p class="mb-1"><strong>Requested Delivery:</strong> <?php echo date('F d, Y', strtotime($order['estimated_delivery'])); ?></p>
                            <?php endif; ?>
                            <?php if ($order['shipping_address']): ?>
                                <p class="mb-0"><strong>Shipping To:</strong><br><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Articles de commande -->
            <h5><i class="fas fa-list-alt me-2"></i>Order Items</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Reference</th>
                            <th>Color</th>
                            <th>Size</th>
                            <th>Wash Type</th>
                            <th class="text-end">Quantity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['product_reference']); ?></td>
                                <td><?php echo htmlspecialchars($item['color']); ?></td>
                                <td><?php echo htmlspecialchars($item['size']); ?></td>
                                <td><?php echo htmlspecialchars($item['wash_type']); ?></td>
                                <td class="text-end"><?php echo $item['quantity']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        <tr class="table-light">
                            <td colspan="5" class="text-end fw-bold">Total Items:</td>
                            <td class="text-end fw-bold"><?php echo $order['total_items']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Notes de commande -->
            <?php if ($order['notes']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Order Notes</h6>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Prochaines étapes -->
            <div class="alert alert-info">
                <h6><i class="fas fa-forward me-2"></i>Next Steps:</h6>
                <ul class="mb-0">
                    <li>Our team will review your order within 1-2 business days</li>
                    <li>You will receive a confirmation email with order details</li>
                    <li>Production will begin once order is confirmed</li>
                    <li>You can track order status in your client portal</li>
                </ul>
            </div>
            
            <!-- Boutons d'action -->
            <div class="d-flex justify-content-between mt-4 pt-3 border-top no-print">
                <div>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                    <a href="dashboard_client.php" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>Dashboard
                    </a>
                </div>
                <div>
                    <button onclick="window.print()" class="btn btn-outline-dark me-2">
                        <i class="fas fa-print me-2"></i>Print Confirmation
                    </button>
                    <a href="export_order.php?id=<?php echo $order_id; ?>" class="btn btn-outline-success">
                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                    </a>
                </div>
            </div>
            
            <!-- Informations d'impression -->
            <div class="print-only mt-4 pt-3 border-top">
                <p class="small text-muted mb-0">
                    This is an automated order confirmation. For questions, contact contact@fus-denim.com
                </p>
                <p class="small text-muted mb-0">
                    Order #: <?php echo htmlspecialchars($order['reference']); ?> | 
                    Date: <?php echo date('F d, Y H:i:s'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Impression automatique optionnelle
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === 'true') {
            window.print();
        }
        
        // Sauvegarder comme PDF
        function saveAsPDF() {
            alert('PDF export feature will be implemented soon.');
            // Ici, vous pourriez intégrer une bibliothèque comme jsPDF
        }
        
        // Partager la confirmation
        function shareConfirmation() {
            if (navigator.share) {
                navigator.share({
                    title: 'Order Confirmation - <?php echo htmlspecialchars($order['reference']); ?>',
                    text: 'My order has been submitted to FUS Denim',
                    url: window.location.href
                });
            } else {
                alert('Copy this link to share: ' + window.location.href);
            }
        }
    </script>
</body>
</html>