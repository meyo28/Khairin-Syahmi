<?php
session_start();
require_once 'db_config.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'landlord') {
    redirectWithMessage('login.php', 'danger', 'Please login as landlord');
}

$flash_message = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIESTUDENT - Landlord Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar-link {
            transition: all 0.2s;
        }
        .sidebar-link:hover, .sidebar-link.active {
            background-color: rgba(13, 110, 253, 0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .booking-row {
            transition: background-color 0.2s;
        }
        .booking-row:hover {
            background-color: rgba(0, 0, 0, 0.02);
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
                               class="list-group-item list-group-item-action sidebar-link active">
                                <i class="fas fa-inbox me-2"></i>Booking Requests
                            </a>
                            <a href="properties.php" 
                               class="list-group-item list-group-item-action sidebar-link">
                                <i class="fas fa-home me-2"></i>My Properties
                            </a>
                            <a href="add_property.php" 
                               class="list-group-item list-group-item-action sidebar-link">
                                <i class="fas fa-plus-circle me-2"></i>Add Property
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Booking Requests</h4>
                            <span id="bookings-count" class="badge bg-white text-primary">Loading...</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Student</th>
                                        <th>Request Date</th>
                                        <th>Move-in Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="bookings-table">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Action Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="action-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" id="booking-id" name="id">
                    
                    <div class="modal-body">
                        <div id="booking-details">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="landlord-message" class="form-label">Message to Student</label>
                            <textarea class="form-control" id="landlord-message" name="message" rows="3"
                                      placeholder="Optional message to the student"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" id="reject-btn" class="btn btn-danger">Reject</button>
                        <button type="button" id="approve-btn" class="btn btn-success">Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // DOM elements
        const bookingsTable = document.getElementById('bookings-table');
        const actionModal = new bootstrap.Modal('#actionModal');
        const actionForm = document.getElementById('action-form');
        const bookingDetails = document.getElementById('booking-details');
        const rejectBtn = document.getElementById('reject-btn');
        const approveBtn = document.getElementById('approve-btn');
        
        // Current booking being managed
        let currentBooking = null;
        
        // Load bookings from API
        async function loadBookings() {
            try {
                const response = await fetch('api/bookings.php');
                const bookings = await response.json();
                
                document.getElementById('bookings-count').textContent = `${bookings.length} requests`;
                
                if (bookings.length === 0) {
                    bookingsTable.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <div class="alert alert-info mb-0">
                                    No booking requests found.
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                let html = '';
                bookings.forEach(booking => {
                    const statusClass = {
                        'pending': 'warning',
                        'approved': 'success',
                        'rejected': 'danger'
                    }[booking.status];
                    
                    html += `
                        <tr class="booking-row">
                            <td>${booking.property_title}</td>
                            <td>${booking.student_name}</td>
                            <td>${new Date(booking.created_at).toLocaleDateString()}</td>
                            <td>${new Date(booking.move_in_date).toLocaleDateString()}</td>
                            <td>
                                <span class="badge bg-${statusClass}">
                                    ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary manage-btn"
                                        data-booking-id="${booking.id}"
                                        data-booking-status="${booking.status}">
                                    <i class="fas fa-edit"></i> Manage
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                bookingsTable.innerHTML = html;
                
                // Add event listeners to manage buttons
                document.querySelectorAll('.manage-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        currentBooking = {
                            id: this.dataset.bookingId,
                            status: this.dataset.bookingStatus
                        };
                        
                        document.getElementById('booking-id').value = currentBooking.id;
                        loadBookingDetails();
                        
                        // Show appropriate buttons based on current status
                        if (currentBooking.status === 'pending') {
                            rejectBtn.style.display = 'inline-block';
                            approveBtn.style.display = 'inline-block';
                        } else {
                            rejectBtn.style.display = 'none';
                            approveBtn.style.display = 'none';
                        }
                        
                        actionModal.show();
                    });
                });
                
            } catch (error) {
                console.error('Error loading bookings:', error);
                bookingsTable.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="alert alert-danger mb-0">
                                Error loading booking requests. Please try again later.
                            </div>
                        </td>
                    </tr>
                `;
            }
        }
        
        // Load booking details for modal
        async function loadBookingDetails() {
            try {
                bookingDetails.innerHTML = `
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                
                const response = await fetch(`api/bookings.php?id=${currentBooking.id}`);
                const booking = await response.json();
                
                bookingDetails.innerHTML = `
                    <h5>${booking.property_title}</h5>
                    <p class="mb-1"><strong>Student:</strong> ${booking.student_name}</p>
                    <p class="mb-1"><strong>Requested:</strong> ${new Date(booking.created_at).toLocaleDateString()}</p>
                    <p class="mb-1"><strong>Move-in:</strong> ${new Date(booking.move_in_date).toLocaleDateString()}</p>
                    <p class="mb-3"><strong>Status:</strong> 
                        <span class="badge bg-${booking.status === 'pending' ? 'warning' : booking.status === 'approved' ? 'success' : 'danger'}">
                            ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                        </span>
                    </p>
                    
                    ${booking.message ? `
                        <div class="alert alert-light">
                            <strong>Student Message:</strong>
                            <p class="mb-0">${booking.message}</p>
                        </div>
                    ` : ''}
                `;
                
                if (booking.landlord_message) {
                    document.getElementById('landlord-message').value = booking.landlord_message;
                }
                
            } catch (error) {
                console.error('Error loading booking details:', error);
                bookingDetails.innerHTML = `
                    <div class="alert alert-danger">
                        Error loading booking details. Please try again.
                    </div>
                `;
            }
        }
        
        // Handle booking status update
        async function updateBookingStatus(status) {
            const submitBtn = status === 'approved' ? approveBtn : rejectBtn;
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Processing...
            `;
            
            try {
        const formData = new FormData(actionForm);
        formData.append('status', status);
        formData.append('_method', 'PUT'); // ✅ HERE!

        const response = await fetch('api/bookings.php', {
  method: 'POST',
  body: formData
});

const text = await response.text();
console.log('RAW RESPONSE:', text); // ✅ log raw server output

let result;
try {
    result = JSON.parse(text);
} catch (e) {
    throw new Error("Server did not return valid JSON. Raw: " + text);
}

                
                if (response.ok) {
                    actionModal.hide();
                    loadBookings(); // Refresh bookings list
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show';
                    alert.innerHTML = `
                        Booking has been ${status} successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.querySelector('nav').after(alert);
                    
                } else {
                    throw new Error(result.error || 'Failed to update booking');
                }
                
            } catch (error) {
                console.error('Update error:', error);
                
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    Error: ${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('nav').after(alert);
                
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        }
        
        // Button event listeners
        rejectBtn.addEventListener('click', () => updateBookingStatus('rejected'));
        approveBtn.addEventListener('click', () => updateBookingStatus('approved'));
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', loadBookings);
    </script>
</body>
</html>