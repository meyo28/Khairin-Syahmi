<?php
session_start();
require_once 'db_config.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'landlord') {
    redirectWithMessage('login.php', 'danger', 'Please login as landlord');
}

// Get landlord's properties
$landlord_id = $_SESSION['user_id'];
$properties = $conn->query("
    SELECT * FROM properties 
    WHERE landlord_id = $landlord_id
    ORDER BY available_date DESC
")->fetch_all(MYSQLI_ASSOC);

$flash_message = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIESTUDENT - My Properties</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .property-card {
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .property-image-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .property-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .property-image:hover {
            transform: scale(1.05);
        }
        .property-image-placeholder {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
        }
        .map-container {
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="landlord.php">HOMIESTUDENT</a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <a href="logout.php" class="btn btn-outline-light">Logout</a>
            </div>
        </div>
    </nav>

    <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?> alert-dismissible fade show mb-0">
            <?= $flash_message['text'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <a href="landlord.php" 
                               class="list-group-item list-group-item-action">
                                <i class="fas fa-inbox me-2"></i>Booking Requests
                            </a>
                            <a href="properties.php" 
                               class="list-group-item list-group-item-action active">
                                <i class="fas fa-home me-2"></i>My Properties
                            </a>
                            <a href="add_property.php" 
                               class="list-group-item list-group-item-action">
                                <i class="fas fa-plus-circle me-2"></i>Add Property
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>My Properties</h2>
                    <a href="add_property.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Property
                    </a>
                </div>
                
                <?php if (count($properties) > 0): ?>
                    <div class="row">
                        <?php foreach ($properties as $property): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card property-card h-100">
                                    <div class="property-image-container">
                                        <?php if (!empty($property['property_image'])): ?>
                                            <img src="<?= htmlspecialchars($property['property_image']) ?>" 
                                                 class="card-img-top property-image" 
                                                 alt="<?= htmlspecialchars($property['title']) ?>"
                                                 onerror="this.onerror=null;this.src='images/default-property.jpg';">
                                        <?php else: ?>
                                            <div class="property-image-placeholder">
                                                <i class="fas fa-home"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                                        
                                        <!-- Mini Map -->
                                        <div class="map-container mb-2">
                                            <iframe
                                                width="100%"
                                                height="100%"
                                                frameborder="0"
                                                style="border:0"
                                                src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBkQtKucrhYvIZZVyxV7dbiHnx7-Sf5Ggs&q=<?= urlencode($property['address'] . ', ' . $property['city']) ?>"
                                                allowfullscreen>
                                            </iframe>
                                        </div>
                                        
                                        <p class="card-text text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i> 
                                            <?= htmlspecialchars($property['address']) ?>, 
                                            <?= htmlspecialchars($property['city']) ?>
                                        </p>
                                        <p class="card-text">
                                            <span class="badge bg-secondary me-2"><?= htmlspecialchars($property['property_type']) ?></span>
                                            <?= $property['bedrooms'] ?> bed • <?= $property['bathrooms'] ?> bath
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mt-auto">
                                            <div>
                                                <strong class="text-primary fs-5">RM<?= number_format($property['rent_amount'], 2) ?></strong>
                                                <small class="text-muted">/month</small>
                                            </div>
                                            <div>
                                                <small class="text-muted">
                                                    Available from <?= date('M j, Y', strtotime($property['available_date'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex justify-content-between">
                                            <a href="edit_property.php?id=<?= $property['id'] ?>" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger delete-property"
                                                    data-id="<?= $property['id'] ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't added any properties yet. 
                        <a href="add_property.php" class="alert-link">Add your first property</a> to get started.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this property? This action cannot be undone.</p>
                    <p class="text-danger"><strong>Warning:</strong> Any existing bookings for this property will also be cancelled.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirm-delete" class="btn btn-danger">Delete Property</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete property confirmation
        const deleteModal = new bootstrap.Modal('#deleteModal');
        const confirmDeleteBtn = document.getElementById('confirm-delete');
        
        document.querySelectorAll('.delete-property').forEach(btn => {
            btn.addEventListener('click', function() {
                const propertyId = this.dataset.id;
                confirmDeleteBtn.href = `delete_property.php?id=${propertyId}`;
                deleteModal.show();
            });
        });
    </script>
</body>
</html>