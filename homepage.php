<?php
session_start();
require_once 'db_config.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    redirectWithMessage('login.php', 'danger', 'Please login as student');
}

$flash_message = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOMIESTUDENT - Find Accommodation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBkQtKucrhYvIZZVyxV7dbiHnx7-Sf5Ggs&libraries=places"></script>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .booking-card {
            border-left: 4px solid;
            margin-bottom: 1rem;
        }
        .booking-pending {
            border-left-color: #ffc107;
        }
        .booking-approved {
            border-left-color: #28a745;
        }
        .booking-rejected {
            border-left-color: #dc3545;
        }
        .price-range-container {
            position: relative;
        }
        .range-slider {
            -webkit-appearance: none;
            appearance: none;
            height: 5px;
            background: #ddd;
            outline: none;
            border-radius: 5px;
        }
        .range-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            background: #007bff;
            cursor: pointer;
            border-radius: 50%;
        }
        .map-container {
            height: 150px;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
        .modal-lg {
            max-width: 800px;
        }
        #map {
            height: 500px;
            width: 100%;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="homepage.php">HOMIESTUDENT</a>
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

    <div class="container py-4">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Search Filters</h5>
                    </div>
                    <div class="card-body">
                        <form id="filter-form">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="d-flex justify-content-between mb-2">
                                    <span id="min-price-value">RM0</span>
                                    <span id="max-price-value">RM5000</span>
                                </div>
                                <div class="price-range-container">
                                    <input type="range" class="form-range range-slider" min="0" max="5000" step="100" 
                                           id="min-price" name="min_price" value="0">
                                    <input type="range" class="form-range range-slider" min="0" max="5000" step="100" 
                                           id="max-price" name="max_price" value="5000">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Bedrooms</label>
                                <select class="form-select" id="bedrooms" name="bedrooms">
                                    <option value="">Any</option>
                                    <option value="0">Studio</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3+</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Property Type</label>
                                <select class="form-select" id="property-type" name="property_type">
                                    <option value="">Any</option>
                                    <option value="apartment">Apartment</option>
                                    <option value="house">House</option>
                                    <option value="condo">Condo</option>
                                    <option value="townhouse">Townhouse</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-2"></i>Apply Filters
                            </button>
                            <button type="button" id="clear-filters" class="btn btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- My Bookings Section -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">My Bookings</h5>
                    </div>
                    <div class="card-body" id="bookings-container">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Properties List -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Available Properties</h2>
                    <div id="properties-count" class="badge bg-primary rounded-pill fs-6">Loading...</div>
                </div>
                
                <!-- View Tabs -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#listView">List View</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#mapView">Map View</a>
                    </li>
                </ul>
                
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="listView">
                        <div id="properties-container" class="row">
                            <div class="col-12 text-center py-5">
                                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-3 text-muted">Loading available properties...</p>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="mapView">
                        <div id="map"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Book Property</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="booking-form">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" id="modal-property-id" name="property_id">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <h6 id="modal-property-title"></h6>
                            <p id="modal-property-price" class="text-primary fw-bold"></p>
                            <p id="modal-property-address" class="text-muted small"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="move-in-date" class="form-label">Preferred Move-in Date</label>
                            <input type="date" class="form-control" id="move-in-date" name="move_in_date" required
                                   min="<?= date('Y-m-d') ?>">
                            <div class="form-text">Please select your preferred move-in date</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="booking-message" class="form-label">Message to Landlord</label>
                            <textarea class="form-control" id="booking-message" name="message" rows="4" required
                                      placeholder="Tell the landlord about yourself, your background, and why you're interested in this property. This helps them get to know you better!"></textarea>
                            <div class="form-text">A good message increases your chances of approval</div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Booking Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Full Map Modal -->
    <div class="modal fade" id="fullMapModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="fullMapModalLabel">Property Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="fullMapFrame" width="100%" height="500" frameborder="0" style="border:0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let currentProperty = null;
        let allProperties = [];
        let map;
        let markers = [];
        
        // DOM elements
        const filterForm = document.getElementById('filter-form');
        const propertiesContainer = document.getElementById('properties-container');
        const bookingsContainer = document.getElementById('bookings-container');
        const propertiesCount = document.getElementById('properties-count');
        const bookingForm = document.getElementById('booking-form');
        const bookingModal = new bootstrap.Modal('#bookingModal');
        const fullMapModal = new bootstrap.Modal('#fullMapModal');
        
        // Price range display and interaction
        const minPriceSlider = document.getElementById('min-price');
        const maxPriceSlider = document.getElementById('max-price');
        const minPriceValue = document.getElementById('min-price-value');
        const maxPriceValue = document.getElementById('max-price-value');
        
        function updatePriceDisplay() {
            const minVal = parseInt(minPriceSlider.value);
            const maxVal = parseInt(maxPriceSlider.value);
            
            if (minVal >= maxVal) {
                minPriceSlider.value = maxVal - 100;
            }
            
            minPriceValue.textContent = 'RM' + minPriceSlider.value;
            maxPriceValue.textContent = 'RM' + maxPriceSlider.value;
        }
        
        minPriceSlider.addEventListener('input', updatePriceDisplay);
        maxPriceSlider.addEventListener('input', updatePriceDisplay);
        
        // Clear filters
        document.getElementById('clear-filters').addEventListener('click', function() {
            document.getElementById('min-price').value = 0;
            document.getElementById('max-price').value = 5000;
            document.getElementById('bedrooms').value = '';
            document.getElementById('property-type').value = '';
            updatePriceDisplay();
            loadProperties();
        });
        
        // Initialize Google Map
        function initMap() {
            // Default to New York if no properties
            const defaultCenter = { lat: 40.7128, lng: -74.0060 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: defaultCenter,
                zoom: 12
            });
            
            // Add markers if properties are loaded
            if (allProperties.length > 0) {
                updateMapMarkers();
            }
        }
        
        // Update map markers based on filtered properties
        function updateMapMarkers() {
            // Clear existing markers
            markers.forEach(marker => marker.setMap(null));
            markers = [];
            
            // Add new markers
            allProperties.forEach(property => {
                if (property.latitude && property.longitude) {
                    const marker = new google.maps.Marker({
                        position: { 
                            lat: parseFloat(property.latitude), 
                            lng: parseFloat(property.longitude) 
                        },
                        map: map,
                        title: property.title
                    });
                    
                    // Add info window
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div>
                                <h6>${property.title}</h6>
                                <p>${property.address}, ${property.city}</p>
                                <p>RM${property.rent_amount}/month</p>
                                <button class="btn btn-sm btn-primary" 
                                    onclick="showPropertyDetails(${property.id})">
                                    View Details
                                </button>
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });
                    
                    markers.push(marker);
                }
            });
            
            // Adjust map bounds to show all markers
            if (markers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach(marker => bounds.extend(marker.getPosition()));
                map.fitBounds(bounds);
            }
        }
        
        // Show property details from map marker click
        window.showPropertyDetails = function(propertyId) {
            const property = allProperties.find(p => p.id == propertyId);
            if (property) {
                currentProperty = {
                    id: property.id,
                    title: property.title,
                    price: property.rent_amount,
                    address: `${property.address}, ${property.city}`
                };
                
                document.getElementById('modal-property-id').value = currentProperty.id;
                document.getElementById('modal-property-title').textContent = currentProperty.title;
                document.getElementById('modal-property-price').textContent = 
                    `RM${parseFloat(currentProperty.price).toFixed(2)}/month`;
                document.getElementById('modal-property-address').textContent = currentProperty.address;
                
                bookingModal.show();
            }
        };
        
        // Show full screen map
        function showFullMap(address, city) {
            const iframe = document.getElementById('fullMapFrame');
            iframe.src = `https://www.google.com/maps/embed/v1/place?key=AIzaSyBkQtKucrhYvIZZVyxV7dbiHnx7-Sf5Ggs&q=${encodeURIComponent(address + ', ' + city)}`;
            fullMapModal.show();
        }
        
        // Load properties from API
        async function loadProperties() {
            try {
                propertiesContainer.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-3 text-muted">Loading properties...</p>
                    </div>
                `;
                
                // Get filter values or use empty strings to get all properties
                const minPrice = minPriceSlider.value || '';
                const maxPrice = maxPriceSlider.value || '';
                const bedrooms = document.getElementById('bedrooms').value || '';
                const propertyType = document.getElementById('property-type').value || '';
                
                // Build URL with parameters only if they have values
                let url = 'api/properties.php';
                const params = [];
                
                if (minPrice) params.push(`min_price=${minPrice}`);
                if (maxPrice) params.push(`max_price=${maxPrice}`);
                if (bedrooms) params.push(`bedrooms=${bedrooms}`);
                if (propertyType) params.push(`type=${propertyType}`);
                
                if (params.length > 0) {
                    url += `?${params.join('&')}`;
                }
                
                const response = await fetch(url);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const properties = await response.json();
                allProperties = properties;
                
                propertiesContainer.innerHTML = '';
                propertiesCount.textContent = `${properties.length} properties found`;
                
                if (properties.length === 0) {
                    propertiesContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                <i class="fas fa-home fa-3x mb-3 text-muted"></i>
                                <h5>No Properties Found</h5>
                                <p class="mb-0">No properties match your current filters. Try adjusting your search criteria.</p>
                            </div>
                        </div>
                    `;
                    return;
                }
                
                properties.forEach(property => {
                    const card = document.createElement('div');
                    card.className = 'col-md-6 col-xl-4 mb-4';
                    
                    // Create property card HTML with the new image display
                    card.innerHTML = `
                        <div class="card property-card h-100">
                            <div class="property-image-container">
                                ${property.property_image ? `
                                    <img src="${property.property_image}" 
                                         class="card-img-top property-image" 
                                         alt="${property.title}"
                                         onerror="this.onerror=null;this.src='images/default-property.jpg';">
                                ` : `
                                    <div class="property-image-placeholder">
                                        <i class="fas fa-home"></i>
                                    </div>
                                `}
                            </div>
                            
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">${property.title}</h5>
                                
                                <!-- Mini Map -->
                                <div class="map-container mb-2">
                                    <iframe
                                        width="100%"
                                        height="100%"
                                        frameborder="0"
                                        style="border:0"
                                        src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBkQtKucrhYvIZZVyxV7dbiHnx7-Sf5Ggs&q=${encodeURIComponent(property.address + ', ' + property.city)}"
                                        allowfullscreen>
                                    </iframe>
                                </div>
                                
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i> 
                                    ${property.address}, ${property.city}
                                </p>
                                <p class="card-text">
                                    <span class="badge bg-secondary me-2">${property.property_type}</span>
                                    ${property.bedrooms} bed • ${property.bathrooms} bath
                                </p>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <div>
                                        <strong class="text-primary fs-5">RM${parseFloat(property.rent_amount).toFixed(2)}</strong>
                                        <small class="text-muted">/month</small>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2" 
                                                onclick="showFullMap('${property.address.replace("'", "\\'")}', '${property.city}')">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </button>
                                        <button class="btn btn-primary book-btn" 
                                                data-property-id="${property.id}"
                                                data-property-title="${property.title}"
                                                data-property-price="${property.rent_amount}"
                                                data-property-address="${property.address}, ${property.city}">
                                            <i class="fas fa-calendar-plus me-1"></i>Book
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    propertiesContainer.appendChild(card);
                });
                
                // Update map markers
                if (typeof google !== 'undefined') {
                    updateMapMarkers();
                }
                
                // Add event listeners to booking buttons
                document.querySelectorAll('.book-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        currentProperty = {
                            id: this.dataset.propertyId,
                            title: this.dataset.propertyTitle,
                            price: this.dataset.propertyPrice,
                            address: this.dataset.propertyAddress
                        };
                        
                        document.getElementById('modal-property-id').value = currentProperty.id;
                        document.getElementById('modal-property-title').textContent = currentProperty.title;
                        document.getElementById('modal-property-price').textContent = 
                            `RM${parseFloat(currentProperty.price).toFixed(2)}/month`;
                        document.getElementById('modal-property-address').textContent = currentProperty.address;
                            
                        bookingModal.show();
                    });
                });
                
            } catch (error) {
                console.error('Error loading properties:', error);
                propertiesContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <h5>Error Loading Properties</h5>
                            <p class="mb-3">We're having trouble loading the properties. Please try again later.</p>
                            <button class="btn btn-outline-danger" onclick="loadProperties()">
                                <i class="fas fa-redo me-2"></i>Try Again
                            </button>
                        </div>
                    </div>
                `;
                propertiesCount.textContent = 'Error';
            }
        }
        
        // Load bookings from API
        async function loadBookings() {
            try {
                const response = await fetch('api/bookings.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const bookings = await response.json();
                
                if (bookings.length === 0) {
                    bookingsContainer.innerHTML = `
                        <div class="text-center py-3">
                            <i class="fas fa-calendar-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No bookings yet</p>
                        </div>
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
                        <div class="booking-card booking-${booking.status} p-3 rounded mb-3 shadow-sm">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">${booking.property_title}</h6>
                                    <small class="text-muted d-block mb-1">
                                        Move-in: ${new Date(booking.move_in_date).toLocaleDateString()}
                                    </small>
                                    <small class="text-muted">
                                        ${new Date(booking.created_at).toLocaleDateString()}
                                    </small>
                                </div>
                                <span class="badge bg-${statusClass}">
                                    ${booking.status.charAt(0).toUpperCase() + booking.status.slice(1)}
                                </span>
                            </div>
                            ${booking.landlord_message ? `
                                <div class="mt-2 p-2 bg-light rounded">
                                    <small><strong>Landlord response:</strong> ${booking.landlord_message}</small>
                                </div>
                            ` : ''}
                        </div>
                    `;
                });
                
                bookingsContainer.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading bookings:', error);
                bookingsContainer.innerHTML = `
                    <div class="alert alert-warning small">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Unable to load bookings
                    </div>
                `;
            }
        }
        
        // Handle booking form submission
        bookingForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalHTML = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                Submitting...
            `;
            
            try {
                const formData = new FormData(this);
                const response = await fetch('api/bookings.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    bookingModal.hide();
                    loadBookings(); // Refresh bookings list
                    
                    // Show success message
                    showAlert('success', 'Booking request submitted successfully! The landlord will review your request.');
                    
                    // Reset form
                    this.reset();
                } else {
                    throw new Error(result.error || 'Failed to submit booking');
                }
                
            } catch (error) {
                console.error('Booking error:', error);
                showAlert('danger', `Error: ${error.message}`);
                
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
            }
        });
        
        // Helper function to show alerts
        function showAlert(type, message) {
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('nav').after(alert);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 5000);
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Load properties immediately when page loads
            loadProperties();
            loadBookings();
            
            // Filter form submission
            filterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                loadProperties();
            });
            
            // Auto-filter on input change
            document.getElementById('bedrooms').addEventListener('change', loadProperties);
            document.getElementById('property-type').addEventListener('change', loadProperties);
            
            // Initialize map when Google Maps API is loaded
            if (typeof google !== 'undefined') {
                initMap();
            } else {
                window.initMap = initMap;
            }
        });
    </script>
</body>
</html>