<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

// Check if user has Aadhar card registered
$user_check = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT aadhar_card, date_of_birth FROM users WHERE id = $user_id"));

if(empty($user_check['aadhar_card'])) {
    header("Location: profile.php?error=कृपया पहले अपनी प्रोफाइल पूरी करें (आधार कार्ड डालें)");
    exit();
}

// Calculate user age
$dob = new DateTime($user_check['date_of_birth']);
$today = new DateTime();
$age = $today->diff($dob)->y;

if($age < 18) {
    header("Location: profile.php?error=केवल 18 वर्ष से अधिक उम्र के व्यक्ति बुकिंग कर सकते हैं");
    exit();
}

// Parking fees
$parking_fee_car = 200; // per day
$parking_fee_bike = 100; // per day

// Initialize variables
$success = "";
$error = "";
$parking_success = "";
$parking_error = "";

// Handle booking form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $room_id = $_POST['room_id'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $guests = $_POST['guests'];
    
    // Get parking data if selected
    $parking_required = isset($_POST['parking_required']) ? 1 : 0;
    $vehicle_type = $_POST['vehicle_type'] ?? '';
    $vehicle_number = $_POST['vehicle_number'] ?? '';
    
    // Validate dates
    if(strtotime($check_out) <= strtotime($check_in)) {
        $error = "चेक-आउट तिथि चेक-इन तिथि के बाद होनी चाहिए!";
    } else {
        // Get room price
        $room_query = mysqli_query($conn, "SELECT * FROM rooms WHERE id = $room_id");
        $room = mysqli_fetch_assoc($room_query);
        
        if(!$room) {
            $error = "कमरा नहीं मिला!";
        } else {
            // Check room capacity
            if($guests > $room['capacity']) {
                $error = "कृपया " . $room['capacity'] . " से कम अतिथि चुनें!";
            } else {
                // Calculate nights
                $date1 = new DateTime($check_in);
                $date2 = new DateTime($check_out);
                $nights = $date2->diff($date1)->days;
                
                // Calculate room total
                $room_total = $room['price_per_night'] * $nights;
                
                // Calculate parking total if required
                $parking_total = 0;
                if($parking_required && $vehicle_type) {
                    $parking_rate = ($vehicle_type == 'car') ? $parking_fee_car : $parking_fee_bike;
                    $parking_total = $parking_rate * $nights;
                }
                
                // Calculate grand total
                $grand_total = $room_total + $parking_total;
                
                // Start transaction
                mysqli_begin_transaction($conn);
                
                try {
                    // Insert booking
                    $booking_sql = "INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, parking_required, vehicle_type, vehicle_number) 
                                    VALUES ($user_id, $room_id, '$check_in', '$check_out', $guests, $grand_total, $parking_required, '$vehicle_type', '$vehicle_number')";
                    
                    if(mysqli_query($conn, $booking_sql)) {
                        $booking_id = mysqli_insert_id($conn);
                        
                        // Assign parking slot if required
                        if($parking_required && $vehicle_type) {
                            // Find available parking slot
                            $slot_query = "SELECT * FROM parking_slots WHERE vehicle_type = '$vehicle_type' AND is_available = TRUE LIMIT 1";
                            $slot_result = mysqli_query($conn, $slot_query);
                            
                            if(mysqli_num_rows($slot_result) > 0) {
                                $slot = mysqli_fetch_assoc($slot_result);
                                $slot_id = $slot['id'];
                                
                                // Update parking slot
                                $update_slot = "UPDATE parking_slots SET 
                                                is_available = FALSE, 
                                                booking_id = $booking_id,
                                                check_in_time = NOW(),
                                                parking_fee = $parking_total
                                                WHERE id = $slot_id";
                                
                                if(mysqli_query($conn, $update_slot)) {
                                    $parking_success = "पार्किंग स्लॉट {$slot['slot_number']} आपके लिए रिजर्व हो गया!";
                                } else {
                                    $parking_error = "पार्किंग स्लॉट अपडेट में समस्या!";
                                }
                            } else {
                                $parking_error = "इस प्रकार के वाहन के लिए पार्किंग स्लॉट उपलब्ध नहीं है!";
                            }
                        }
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                        $success = "✅ बुकिंग सफल! बुकिंग आईडी: <strong>#$booking_id</strong>";
                        if($parking_success) $success .= "<br>🚗 $parking_success";
                        if($parking_error) $success .= "<br>⚠️ $parking_error";
                        
                        // Redirect to receipt after 3 seconds
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'booking_receipt.php?id=$booking_id';
                            }, 3000);
                        </script>";
                        
                    } else {
                        throw new Exception("बुकिंग इन्सर्ट में समस्या!");
                    }
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    mysqli_rollback($conn);
                    $error = "❌ बुकिंग फेल! " . $e->getMessage();
                }
            }
        }
    }
}

// Get all rooms
$rooms_query = mysqli_query($conn, "SELECT * FROM rooms");
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>कमरा बुकिंग - अंकित होटल</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 25px 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .user-info {
            color: #666;
            font-size: 18px;
        }
        
        /* Messages */
        .message {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            font-size: 16px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 5px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 5px solid #dc3545;
        }
        
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 5px solid #ffc107;
        }
        
        /* Quick Booking Form */
        .quick-booking {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .quick-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        /* Room Grid */
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }
        
        /* Room Card */
        .room-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .room-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .room-image {
            height: 250px;
            width: 100%;
            object-fit: cover;
            border-bottom: 3px solid #764ba2;
        }
        
        .room-content {
            padding: 25px;
        }
        
        .room-title {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .room-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .room-features {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .feature {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #555;
            font-size: 14px;
        }
        
        .room-price {
            font-size: 28px;
            font-weight: bold;
            color: #e74c3c;
            margin: 20px 0;
        }
        
        .price-label {
            font-size: 14px;
            color: #666;
            display: block;
        }
        
        /* Booking Form */
        .booking-form {
            margin-top: 20px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-input:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 0 3px rgba(118, 75, 162, 0.1);
        }
        
        /* Parking Section */
        .parking-section {
            background: #e8f4f8;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 5px solid #3498db;
        }
        
        .parking-option {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .vehicle-inputs {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }
        
        .parking-fee-display {
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
            color: #155724;
            margin-top: 10px;
        }
        
        /* Booking Summary */
        .booking-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .total-amount {
            font-size: 24px;
            font-weight: bold;
            color: #e74c3c;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        /* Buttons */
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Navigation */
        .nav-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            padding: 12px 30px;
            background: white;
            color: #764ba2;
            border: 2px solid #764ba2;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .nav-btn:hover {
            background: #764ba2;
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .room-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                padding: 20px;
            }
            
            .room-content {
                padding: 20px;
            }
            
            .quick-form {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .nav-btn {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
        }
    </style>
    <script>
        function toggleParkingInputs(roomId, checkbox) {
            const vehicleInputs = document.getElementById('vehicleInputs' + roomId);
            if(checkbox.checked) {
                vehicleInputs.style.display = 'block';
                updateParkingFee(roomId);
            } else {
                vehicleInputs.style.display = 'none';
                document.getElementById('parking_fee_display' + roomId).textContent = '₹0';
                updateGrandTotal(roomId);
            }
        }
        
        function updateParkingFee(roomId) {
            const vehicleType = document.getElementById('vehicle_type' + roomId);
            const checkIn = document.getElementById('check_in' + roomId);
            const checkOut = document.getElementById('check_out' + roomId);
            
            if(vehicleType && checkIn.value && checkOut.value) {
                const carRate = 200;
                const bikeRate = 100;
                
                // Calculate nights
                const date1 = new Date(checkIn.value);
                const date2 = new Date(checkOut.value);
                const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
                
                if(nights > 0) {
                    if(vehicleType.value === 'car') {
                        document.getElementById('parking_fee_display' + roomId).textContent = '₹' + (carRate * nights);
                    } else if(vehicleType.value === 'bike') {
                        document.getElementById('parking_fee_display' + roomId).textContent = '₹' + (bikeRate * nights);
                    }
                    updateGrandTotal(roomId);
                }
            }
        }
        
        function calculateNights(roomId) {
            const checkIn = document.getElementById('check_in' + roomId);
            const checkOut = document.getElementById('check_out' + roomId);
            
            if(checkIn.value && checkOut.value) {
                const date1 = new Date(checkIn.value);
                const date2 = new Date(checkOut.value);
                
                if(date2 <= date1) {
                    alert('चेक-आउट तिथि चेक-इन तिथि के बाद होनी चाहिए!');
                    checkOut.value = '';
                    return;
                }
                
                updateParkingFee(roomId);
                updateGrandTotal(roomId);
            }
        }
        
        function updateGrandTotal(roomId) {
            const roomPrice = document.getElementById('room_price' + roomId).value;
            const checkIn = document.getElementById('check_in' + roomId);
            const checkOut = document.getElementById('check_out' + roomId);
            
            if(checkIn.value && checkOut.value) {
                const date1 = new Date(checkIn.value);
                const date2 = new Date(checkOut.value);
                const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
                
                if(nights > 0) {
                    const roomTotal = roomPrice * nights;
                    document.getElementById('room_total' + roomId).textContent = roomTotal;
                    
                    // Get parking fee
                    const parkingDisplay = document.getElementById('parking_fee_display' + roomId);
                    let parkingTotal = 0;
                    if(parkingDisplay) {
                        const parkingText = parkingDisplay.textContent;
                        const match = parkingText.match(/₹(\d+)/);
                        if(match) {
                            parkingTotal = parseInt(match[1]);
                        }
                    }
                    
                    document.getElementById('parking_summary' + roomId).textContent = '₹' + parkingTotal;
                    document.getElementById('grand_total' + roomId).textContent = roomTotal + parkingTotal;
                }
            }
        }
        
        // Set minimum dates
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const checkInInputs = document.querySelectorAll('input[type="date"]');
            
            checkInInputs.forEach(input => {
                if(input.id.includes('check_in')) {
                    input.min = today;
                    input.value = today;
                    
                    // Set default check-out (tomorrow)
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const tomorrowStr = tomorrow.toISOString().split('T')[0];
                    
                    const roomId = input.id.replace('check_in', '');
                    const checkOutInput = document.getElementById('check_out' + roomId);
                    if(checkOutInput) {
                        checkOutInput.min = tomorrowStr;
                        checkOutInput.value = tomorrowStr;
                    }
                    
                    // Initial calculation
                    setTimeout(() => {
                        updateGrandTotal(roomId);
                    }, 100);
                }
            });
        });
        
        function validateForm(roomId) {
            const checkIn = document.getElementById('check_in' + roomId).value;
            const checkOut = document.getElementById('check_out' + roomId).value;
            const guests = document.getElementById('guests' + roomId).value;
            const parkingCheckbox = document.getElementById('parking_required' + roomId);
            
            // Validate dates
            if(!checkIn || !checkOut) {
                alert('कृपया चेक-इन और चेक-आउट तिथि चुनें!');
                return false;
            }
            
            if(new Date(checkOut) <= new Date(checkIn)) {
                alert('चेक-आउट तिथि चेक-इन तिथि के बाद होनी चाहिए!');
                return false;
            }
            
            // Validate guests
            if(!guests || guests < 1) {
                alert('कृपया अतिथि संख्या चुनें!');
                return false;
            }
            
            // Validate parking if selected
            if(parkingCheckbox && parkingCheckbox.checked) {
                const vehicleType = document.getElementById('vehicle_type' + roomId).value;
                const vehicleNumber = document.getElementById('vehicle_number' + roomId).value;
                
                if(!vehicleType) {
                    alert('कृपया वाहन प्रकार चुनें!');
                    return false;
                }
                
                if(!vehicleNumber) {
                    alert('कृपया वाहन नंबर डालें!');
                    return false;
                }
            }
            
            return true;
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">अंकित होटल - कमरा बुकिंग</div>
            <div class="user-info">
                स्वागत है, <?php echo $_SESSION['user_name']; ?>! | 
                आयु: <?php echo $age; ?> वर्ष | 
                आधार: <?php echo substr($user_check['aadhar_card'], 0, 4) . '****' . substr($user_check['aadhar_card'], -4); ?>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if($success): ?>
            <div class="message success">
                <?php echo $success; ?>
                <div style="margin-top: 15px;">
                    <a href="booking_receipt.php?id=<?php echo $booking_id; ?>" 
                       target="_blank"
                       style="background:#3498db; color:white; padding:10px 20px; border-radius:5px; text-decoration:none; margin-right:10px;">
                        🧾 रसीद देखें
                    </a>
                    <a href="receipt_print.php?id=<?php echo $booking_id; ?>&auto=true" 
                       target="_blank"
                       style="background:#2ecc71; color:white; padding:10px 20px; border-radius:5px; text-decoration:none;">
                        🖨️ प्रिंट करें
                    </a>
                </div>
                <div style="margin-top: 10px; font-size: 14px;">
                    आपको 3 सेकंड में रसीद पेज पर रीडायरेक्ट किया जा रहा है...
                </div>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="message error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Quick Booking Form -->
        <div class="quick-booking">
            <h2 style="color: #2c3e50; margin-bottom: 25px; text-align: center; font-size: 28px;">
                ⚡ त्वरित बुकिंग
            </h2>
            
            <form action="#" method="GET" class="quick-form">
                <div class="form-group">
                    <label class="form-label">📅 चेक-इन</label>
                    <input type="date" id="quick_check_in" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">📅 चेक-आउट</label>
                    <input type="date" id="quick_check_out" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">👥 अतिथि</label>
                    <select id="quick_guests" class="form-input" required>
                        <option value="1">1 व्यक्ति</option>
                        <option value="2" selected>2 व्यक्ति</option>
                        <option value="3">3 व्यक्ति</option>
                        <option value="4">4 व्यक्ति</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="button" onclick="scrollToRooms()" 
                            style="padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                                   color: white; border: none; border-radius: 10px; font-size: 16px; 
                                   font-weight: bold; cursor: pointer; width: 100%;">
                        🔍 कमरे देखें
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Room Selection -->
        <div style="background: white; padding: 30px; border-radius: 15px; margin-bottom: 30px;">
            <h2 style="color: #2c3e50; margin-bottom: 25px; text-align: center; font-size: 28px;" id="roomsSection">
                🏨 उपलब्ध कमरे चुनें
            </h2>
            
            <div class="room-grid">
                <?php 
                $room_counter = 1;
                while($room = mysqli_fetch_assoc($rooms_query)): 
                ?>
                <div class="room-card">
                    <img src="<?php echo $room['image_url']; ?>" 
                         alt="<?php echo $room['room_type']; ?>" 
                         class="room-image">
                    
                    <div class="room-content">
                        <h3 class="room-title"><?php echo $room['room_type']; ?></h3>
                        <p class="room-description"><?php echo $room['description']; ?></p>
                        
                        <div class="room-features">
                            <span class="feature">🛏️ <?php echo $room['capacity']; ?> व्यक्ति</span>
                            <span class="feature">❄️ AC</span>
                            <span class="feature">📺 TV</span>
                            <span class="feature">📶 WiFi</span>
                        </div>
                        
                        <div class="room-price">
                            <span class="price-label">प्रति रात</span>
                            ₹<?php echo $room['price_per_night']; ?>
                        </div>
                        
                        <!-- Booking Form for each room -->
                        <div class="booking-form">
                            <form method="POST" onsubmit="return validateForm(<?php echo $room_counter; ?>)">
                                <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                <input type="hidden" id="room_price<?php echo $room_counter; ?>" value="<?php echo $room['price_per_night']; ?>">
                                
                                <div class="form-group">
                                    <label class="form-label">📅 चेक-इन</label>
                                    <input type="date" 
                                           id="check_in<?php echo $room_counter; ?>" 
                                           name="check_in" 
                                           class="form-input" 
                                           required 
                                           onchange="calculateNights(<?php echo $room_counter; ?>)">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">📅 चेक-आउट</label>
                                    <input type="date" 
                                           id="check_out<?php echo $room_counter; ?>" 
                                           name="check_out" 
                                           class="form-input" 
                                           required 
                                           onchange="calculateNights(<?php echo $room_counter; ?>)">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">👥 अतिथि</label>
                                    <select id="guests<?php echo $room_counter; ?>" 
                                            name="guests" 
                                            class="form-input" 
                                            required>
                                        <?php for($i = 1; $i <= $room['capacity']; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> व्यक्ति</option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <!-- Parking Section -->
                                <div class="parking-section">
                                    <h4 style="margin-bottom: 15px; color: #2c3e50;">🚗 पार्किंग सुविधा</h4>
                                    
                                    <div class="parking-option">
                                        <input type="checkbox" 
                                               id="parking_required<?php echo $room_counter; ?>" 
                                               name="parking_required" 
                                               value="1"
                                               onclick="toggleParkingInputs(<?php echo $room_counter; ?>, this)">
                                        <label for="parking_required<?php echo $room_counter; ?>">
                                            पार्किंग चाहिए (कार: ₹200/दिन, बाइक: ₹100/दिन)
                                        </label>
                                    </div>
                                    
                                    <div id="vehicleInputs<?php echo $room_counter; ?>" class="vehicle-inputs">
                                        <div class="form-group">
                                            <label class="form-label">वाहन प्रकार</label>
                                            <select id="vehicle_type<?php echo $room_counter; ?>" 
                                                    name="vehicle_type" 
                                                    class="form-input"
                                                    onchange="updateParkingFee(<?php echo $room_counter; ?>)">
                                                <option value="">-- चुनें --</option>
                                                <option value="car">कार 🚗 (₹200/दिन)</option>
                                                <option value="bike">बाइक 🏍️ (₹100/दिन)</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">वाहन नंबर</label>
                                            <input type="text" 
                                                   id="vehicle_number<?php echo $room_counter; ?>" 
                                                   name="vehicle_number" 
                                                   class="form-input" 
                                                   placeholder="जैसे: MP09AB1234">
                                        </div>
                                        
                                        <div id="parking_fee_display<?php echo $room_counter; ?>" class="parking-fee-display">
                                            पार्किंग फी: ₹0
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Booking Summary -->
                                <div class="booking-summary">
                                    <div class="summary-item">
                                        <span>कमरा किराया:</span>
                                        <span>₹<span id="room_total<?php echo $room_counter; ?>"><?php echo $room['price_per_night']; ?></span></span>
                                    </div>
                                    <div class="summary-item">
                                        <span>पार्किंग फी:</span>
                                        <span id="parking_summary<?php echo $room_counter; ?>">₹0</span>
                                    </div>
                                    <div class="total-amount">
                                        कुल राशि: ₹<span id="grand_total<?php echo $room_counter; ?>"><?php echo $room['price_per_night']; ?></span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn">
                                    ✅ इस कमरे को बुक करें
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php 
                $room_counter++;
                endwhile; 
                
                // Reset pointer if no rooms
                if(mysqli_num_rows($rooms_query) == 0): 
                ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 50px;">
                    <div style="font-size: 80px; color: #ddd; margin-bottom: 20px;">🏨</div>
                    <h3 style="color: #666;">कोई कमरा उपलब्ध नहीं है</h3>
                    <p style="color: #888;">कृपया बाद में पुनः प्रयास करें</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="index.php" class="nav-btn">🏠 होम</a>
            <a href="dashboard.php" class="nav-btn">📊 डैशबोर्ड</a>
            <a href="mybookings.php" class="nav-btn">📋 मेरी बुकिंग्स</a>
            <a href="profile.php" class="nav-btn">👤 प्रोफाइल</a>
        </div>
    </div>
    
    <script>
        // Quick booking form functionality
        function scrollToRooms() {
            const checkIn = document.getElementById('quick_check_in').value;
            const checkOut = document.getElementById('quick_check_out').value;
            const guests = document.getElementById('quick_guests').value;
            
            if(!checkIn || !checkOut) {
                alert('कृपया तिथियाँ चुनें!');
                return;
            }
            
            if(new Date(checkOut) <= new Date(checkIn)) {
                alert('चेक-आउट तिथि चेक-इन तिथि के बाद होनी चाहिए!');
                return;
            }
            
            // Set values in all room forms
            for(let i = 1; i < <?php echo $room_counter; ?>; i++) {
                const checkInInput = document.getElementById('check_in' + i);
                const checkOutInput = document.getElementById('check_out' + i);
                const guestsInput = document.getElementById('guests' + i);
                
                if(checkInInput) checkInInput.value = checkIn;
                if(checkOutInput) checkOutInput.value = checkOut;
                if(guestsInput) guestsInput.value = guests;
                
                // Update calculations
                updateGrandTotal(i);
            }
            
            // Scroll to rooms section
            document.getElementById('roomsSection').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Set quick booking form dates
        document.addEventListener('DOMContentLoaded', function() {
            // Set default dates for quick form
            const today = new Date();
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const todayStr = today.toISOString().split('T')[0];
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            
            document.getElementById('quick_check_in').value = todayStr;
            document.getElementById('quick_check_out').value = tomorrowStr;
            document.getElementById('quick_check_in').min = todayStr;
            document.getElementById('quick_check_out').min = tomorrowStr;
            
            // Update calculations for all rooms
            for(let i = 1; i < <?php echo $room_counter; ?>; i++) {
                updateGrandTotal(i);
            }
        });
    </script>
</body>
</html>