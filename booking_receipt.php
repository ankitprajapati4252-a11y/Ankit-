<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

if(isset($_GET['id'])) {
    $booking_id = $_GET['id'];
    
    // Get booking details with user info
    $booking_query = mysqli_query($conn, 
        "SELECT b.*, r.room_type, r.price_per_night, r.description as room_desc,
                u.name, u.email, u.phone, u.gender, u.date_of_birth, 
                u.aadhar_card, u.father_name, u.address, u.city, u.state, u.pincode,
                u.emergency_contact,
                ps.slot_number as parking_slot, ps.parking_fee
         FROM bookings b 
         JOIN rooms r ON b.room_id = r.id
         JOIN users u ON b.user_id = u.id
         LEFT JOIN parking_slots ps ON ps.booking_id = b.id
         WHERE b.id = $booking_id AND b.user_id = $user_id");
    
    $booking = mysqli_fetch_assoc($booking_query);
    
    if(!$booking) {
        die("
            <div style='text-align:center; padding:50px;'>
                <h2 style='color:red;'>❌ बुकिंग नहीं मिली</h2>
                <p>यह बुकिंग आपकी नहीं है या मौजूद नहीं है</p>
                <a href='mybookings.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>
                    मेरी बुकिंग्स पर वापस जाएं
                </a>
            </div>
        ");
    }
    
    // Calculate nights
    $check_in = new DateTime($booking['check_in']);
    $check_out = new DateTime($booking['check_out']);
    $nights = $check_out->diff($check_in)->days;
    
    // Calculate age
    $dob = new DateTime($booking['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
    
    // Calculate totals
    $room_total = $booking['price_per_night'] * $nights;
    $parking_fee = $booking['parking_fee'] ?? 0;
    $service_charge = $room_total * 0.05; // 5% service charge
    $gst = ($room_total + $parking_fee + $service_charge) * 0.18; // 18% GST
    
    $grand_total = $room_total + $parking_fee + $service_charge + $gst;
    
    // Format dates
    $formatted_check_in = date('d-m-Y', strtotime($booking['check_in']));
    $formatted_check_out = date('d-m-Y', strtotime($booking['check_out']));
    $booking_date = date('d-m-Y H:i:s', strtotime($booking['created_at']));
    $dob_formatted = date('d-m-Y', strtotime($booking['date_of_birth']));
    
} else {
    die("
        <div style='text-align:center; padding:50px;'>
            <h2 style='color:red;'>❌ बुकिंग आईडी नहीं मिली</h2>
            <p>कृपया वैध बुकिंग आईडी प्रदान करें</p>
            <a href='mybookings.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>
                मेरी बुकिंग्स पर वापस जाएं
            </a>
        </div>
    ");
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>बुकिंग रसीद #<?php echo $booking_id; ?> - अंकित होटल</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', 'Segoe UI', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                margin: 0 !important;
            }
        }
        
        /* Receipt Container */
        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        /* Header */
        .receipt-header {
            background: linear-gradient(135deg, #1a2a3a 0%, #2c3e50 100%);
            color: white;
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .hotel-name {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #f39c12;
        }
        
        .hotel-address {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        
        .hotel-contact {
            font-size: 18px;
            color: #3498db;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .receipt-label {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 18px;
        }
        
        /* Body */
        .receipt-body {
            padding: 40px;
        }
        
        /* Section */
        .section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #e74c3c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Grid Layouts */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        
        /* Info Items */
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .info-value {
            color: #333;
            font-size: 16px;
        }
        
        /* ID Verification Box */
        .id-box {
            background: #e8f4f8;
            border: 2px dashed #3498db;
            border-radius: 10px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .id-title {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        /* Bill Details */
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .bill-table th, .bill-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .bill-table th {
            background: #2c3e50;
            color: white;
        }
        
        .bill-table tr:hover {
            background: #f9f9f9;
        }
        
        .amount {
            text-align: right;
            font-weight: bold;
        }
        
        .total-row {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
            background: #f8f9fa;
        }
        
        /* Footer */
        .receipt-footer {
            background: #f8f9fa;
            padding: 30px 40px;
            text-align: center;
            border-top: 2px solid #ddd;
        }
        
        .footer-note {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .qr-code {
            width: 120px;
            height: 120px;
            margin: 20px auto;
            background: #eee;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #666;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 200px;
        }
        
        .btn-print {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-print:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f639e 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-download {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
        }
        
        .btn-download:hover {
            background: linear-gradient(135deg, #27ae60 0%, #219653 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.4);
        }
        
        .btn-back {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }
        
        .btn-back:hover {
            background: linear-gradient(135deg, #7f8c8d 0%, #6c7b7d 100%);
            transform: translateY(-2px);
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .status-confirmed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-checked-in {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        /* Highlights */
        .highlight {
            background: #fff3cd;
            padding: 15px;
            border-radius: 8px;
            border-left: 5px solid #f39c12;
            margin: 20px 0;
            color: #856404;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .grid-2, .grid-3 {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .action-btn {
                width: 100%;
                max-width: 300px;
            }
            
            .receipt-header, .receipt-body {
                padding: 25px;
            }
        }
        
        @media (max-width: 480px) {
            .hotel-name {
                font-size: 28px;
            }
            
            .receipt-label {
                position: static;
                display: inline-block;
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="receipt-header">
            <div class="receipt-label">बुकिंग रसीद</div>
            <div class="hotel-name">अंकित होटल इंदौर</div>
            <div class="hotel-address">चिताड़, इंदौर, मध्य प्रदेश - 452001</div>
            <div class="hotel-contact">📞 7697878985 | ✉️ info@ankithotelindore.com</div>
            <div style="margin-top: 15px; font-size: 14px; opacity: 0.8;">
                GST No: 07AABCA1234M1Z5 | PAN: AABCA1234M
            </div>
        </div>
        
        <!-- Body -->
        <div class="receipt-body">
            <!-- Booking Info -->
            <div class="section">
                <h2 class="section-title">📋 बुकिंग विवरण</h2>
                <div class="grid-3">
                    <div class="info-item">
                        <div class="info-label">बुकिंग आईडी</div>
                        <div class="info-value">
                            #<?php echo $booking_id; ?>
                            <span class="status-badge status-confirmed">
                                कन्फर्म
                            </span>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">बुकिंग तिथि</div>
                        <div class="info-value"><?php echo $booking_date; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">रसीद नंबर</div>
                        <div class="info-value">RN<?php echo date('Ymd') . $booking_id; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Guest Information -->
            <div class="section">
                <h2 class="section-title">👤 गेस्ट जानकारी</h2>
                <div class="grid-2">
                    <div>
                        <div class="info-item">
                            <div class="info-label">गेस्ट नाम</div>
                            <div class="info-value"><?php echo $booking['name']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">लिंग</div>
                            <div class="info-value">
                                <?php 
                                $gender_display = [
                                    'male' => 'पुरुष 👨',
                                    'female' => 'महिला 👩',
                                    'other' => 'अन्य ⚧'
                                ];
                                echo $gender_display[$booking['gender']] ?? 'पुरुष';
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">ईमेल</div>
                            <div class="info-value"><?php echo $booking['email']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">मोबाइल नंबर</div>
                            <div class="info-value"><?php echo $booking['phone']; ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-item">
                            <div class="info-label">आपातकालीन संपर्क</div>
                            <div class="info-value"><?php echo $booking['emergency_contact']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">पता</div>
                            <div class="info-value">
                                <?php echo $booking['address']; ?><br>
                                <?php echo $booking['city']; ?>, <?php echo $booking['state']; ?> - <?php echo $booking['pincode']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- ID Verification -->
            <div class="id-box">
                <h3 class="id-title">🆔 पहचान सत्यापन</h3>
                <div class="grid-3">
                    <div class="info-item">
                        <div class="info-label">आधार कार्ड नंबर</div>
                        <div class="info-value"><?php echo $booking['aadhar_card']; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">जन्म तिथि</div>
                        <div class="info-value"><?php echo $dob_formatted; ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">आयु</div>
                        <div class="info-value"><?php echo $age; ?> वर्ष</div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">पिता का नाम</div>
                        <div class="info-value"><?php echo $booking['father_name']; ?></div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 5px; color: #155724; font-weight: bold;">
                    ✅ पहचान सत्यापित | बुकिंग के समय मूल दस्तावेज दिखाना आवश्यक
                </div>
            </div>
            
            <!-- Room Details -->
            <div class="section">
                <h2 class="section-title">🏨 कमरा विवरण</h2>
                <div class="grid-2">
                    <div>
                        <div class="info-item">
                            <div class="info-label">कमरा प्रकार</div>
                            <div class="info-value"><?php echo $booking['room_type']; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">कमरा विवरण</div>
                            <div class="info-value"><?php echo $booking['room_desc']; ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="info-item">
                            <div class="info-label">चेक-इन</div>
                            <div class="info-value"><?php echo $formatted_check_in; ?> (दोपहर 2:00 बजे से)</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">चेक-आउट</div>
                            <div class="info-value"><?php echo $formatted_check_out; ?> (दोपहर 12:00 बजे तक)</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">रातें</div>
                            <div class="info-value"><?php echo $nights; ?> रात</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">अतिथि</div>
                            <div class="info-value"><?php echo $booking['guests']; ?> व्यक्ति</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Parking Details (if any) -->
            <?php if($booking['parking_required']): ?>
            <div class="section">
                <h2 class="section-title">🚗 पार्किंग विवरण</h2>
                <div class="grid-3">
                    <div class="info-item">
                        <div class="info-label">पार्किंग</div>
                        <div class="info-value">बुक किया गया ✅</div>
                    </div>
                    
                    <?php if($booking['parking_slot']): ?>
                    <div class="info-item">
                        <div class="info-label">पार्किंग स्लॉट</div>
                        <div class="info-value"><?php echo $booking['parking_slot']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($booking['vehicle_type']): ?>
                    <div class="info-item">
                        <div class="info-label">वाहन प्रकार</div>
                        <div class="info-value">
                            <?php echo ($booking['vehicle_type'] == 'car') ? 'कार 🚗' : 'बाइक 🏍️'; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($booking['vehicle_number']): ?>
                    <div class="info-item">
                        <div class="info-label">वाहन नंबर</div>
                        <div class="info-value"><?php echo $booking['vehicle_number']; ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Bill Details -->
            <div class="section">
                <h2 class="section-title">💰 बिल विवरण</h2>
                <table class="bill-table">
                    <thead>
                        <tr>
                            <th>विवरण</th>
                            <th>मात्रा</th>
                            <th>दर</th>
                            <th>राशि</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>कमरा किराया (<?php echo $nights; ?> रात)</td>
                            <td><?php echo $nights; ?> रात</td>
                            <td>₹<?php echo number_format($booking['price_per_night'], 2); ?></td>
                            <td class="amount">₹<?php echo number_format($room_total, 2); ?></td>
                        </tr>
                        
                        <?php if($parking_fee > 0): ?>
                        <tr>
                            <td>पार्किंग फी (<?php echo $nights; ?> दिन)</td>
                            <td><?php echo $nights; ?> दिन</td>
                            <td>₹<?php echo number_format($parking_fee / $nights, 2); ?></td>
                            <td class="amount">₹<?php echo number_format($parking_fee, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr>
                            <td>सर्विस चार्ज</td>
                            <td>5%</td>
                            <td>-</td>
                            <td class="amount">₹<?php echo number_format($service_charge, 2); ?></td>
                        </tr>
                        
                        <tr>
                            <td>GST</td>
                            <td>18%</td>
                            <td>-</td>
                            <td class="amount">₹<?php echo number_format($gst, 2); ?></td>
                        </tr>
                        
                        <tr class="total-row">
                            <td colspan="3"><strong>कुल राशि</strong></td>
                            <td class="amount">₹<?php echo number_format($grand_total, 2); ?></td>
                        </tr>
                        
                        <?php if($booking['total_amount']): ?>
                        <tr>
                            <td colspan="3">भुगतान की गई राशि</td>
                            <td class="amount" style="color: #27ae60;">₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                        </tr>
                        
                        <?php 
                        $balance = $grand_total - $booking['total_amount'];
                        if($balance > 0): ?>
                        <tr>
                            <td colspan="3">शेष राशि (चेक-इन पर देय)</td>
                            <td class="amount" style="color: #e74c3c;">₹<?php echo number_format($balance, 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Important Notes -->
            <div class="highlight">
                <strong>📌 महत्वपूर्ण नोट:</strong>
                <ul style="margin-top: 10px; padding-left: 20px;">
                    <li>चेक-इन के समय मूल आधार कार्ड और इस रसीद की प्रिंट कॉपी दिखाना आवश्यक</li>
                    <li>चेक-आउट समय: दोपहर 12:00 बजे (विलंब शुल्क लागू)</li>
                    <li>अतिरिक्त व्यक्ति के लिए ₹500/रात अतिरिक्त</li>
                    <li>कैंसिलेशन: 24 घंटे पहले तक 100% रिफंड</li>
                </ul>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="receipt-footer">
            <div class="qr-code">
                QR
            </div>
            <div class="footer-note">
                <p>धन्यवाद! अंकित होटल में आपका स्वागत है</p>
                <p>यह एक कंप्यूटर जनित रसीद है, हस्ताक्षर की आवश्यकता नहीं</p>
                <p>आपकी बुकिंग आईडी: <strong>#<?php echo $booking_id; ?></strong> | जनरेटेड ऑन: <?php echo date('d-m-Y H:i:s'); ?></p>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons no-print">
        <button onclick="window.print()" class="action-btn btn-print">
            🖨️ प्रिंट करें
        </button>
        
        <button onclick="downloadReceipt()" class="action-btn btn-download">
            📥 PDF डाउनलोड
        </button>
        
        <a href="mybookings.php" class="action-btn btn-back">
            ← मेरी बुकिंग्स
        </a>
    </div>
    
    <script>
        function downloadReceipt() {
            // In a real application, you would generate PDF here
            // For now, we'll show an alert and print
            alert('PDF डाउनलोड शुरू हो रहा है...');
            window.print();
        }
        
        // Auto-print if needed
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('print') === 'true') {
            window.print();
        }
        
        // Add watermark for security
        document.addEventListener('DOMContentLoaded', function() {
            const style = document.createElement('style');
            style.innerHTML = `
                @media print {
                    body::before {
                        content: "अंकित होटल इंदौर - बुकिंग #<?php echo $booking_id; ?>";
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%) rotate(-45deg);
                        font-size: 60px;
                        color: rgba(0,0,0,0.1);
                        z-index: 9999;
                        pointer-events: none;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>