<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

// पार्किंग स्लॉट्स की जानकारी
$parking_slots = mysqli_query($conn, "SELECT * FROM parking_slots ORDER BY slot_number");
$available_slots = mysqli_query($conn, "SELECT COUNT(*) as count FROM parking_slots WHERE is_available=TRUE");
$available_count = mysqli_fetch_assoc($available_slots)['count'];

// यूज़र की पार्किंग बुकिंग्स
$user_parking = mysqli_query($conn, 
    "SELECT ps.*, b.check_in, b.check_out, b.vehicle_number, r.room_type
     FROM parking_slots ps
     JOIN bookings b ON ps.booking_id = b.id
     JOIN rooms r ON b.room_id = r.id
     WHERE b.user_id = $user_id AND ps.is_available = FALSE
     ORDER BY ps.check_in_time DESC");
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>पार्किंग - अंकित होटल</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Arial, sans-serif; }
        body { background:#f8f9fa; }
        .navbar { background:linear-gradient(135deg, #1a2a3a, #2c3e50); padding:18px 40px; display:flex; justify-content:space-between; color:white; }
        .logo { font-size:28px; font-weight:bold; color:#f39c12; }
        .nav-links { display:flex; gap:25px; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; }
        .container { padding:40px; max-width:1400px; margin:0 auto; }
        
        /* पार्किंग डैशबोर्ड */
        .parking-dashboard { display:grid; grid-template-columns:repeat(3, 1fr); gap:30px; margin-bottom:40px; }
        .dashboard-card { background:white; padding:30px; border-radius:15px; text-align:center; box-shadow:0 5px 15px rgba(0,0,0,0.08); }
        .dashboard-card h3 { font-size:1.8rem; margin-bottom:15px; color:#2c3e50; }
        .dashboard-card .number { font-size:3rem; font-weight:bold; margin:20px 0; }
        .available { color:#27ae60; }
        .occupied { color:#e74c3c; }
        .total { color:#3498db; }
        
        /* पार्किंग लॉट */
        .parking-lot { background:#2c3e50; padding:40px; border-radius:15px; margin-bottom:40px; }
        .parking-lot h2 { color:white; text-align:center; margin-bottom:30px; font-size:2rem; }
        .slots-container { display:grid; grid-template-columns:repeat(5, 1fr); gap:20px; }
        .slot { background:white; padding:20px; border-radius:10px; text-align:center; position:relative; }
        .slot.available { background:#d5f4e6; border:3px solid #27ae60; }
        .slot.occupied { background:#fadbd8; border:3px solid #e74c3c; }
        .slot-number { font-size:1.5rem; font-weight:bold; color:#2c3e50; }
        .slot-status { padding:5px 10px; border-radius:20px; font-size:0.9rem; font-weight:bold; margin:10px 0; }
        .available-status { background:#27ae60; color:white; }
        .occupied-status { background:#e74c3c; color:white; }
        .vehicle-type { font-size:2rem; margin:10px 0; }
        
        /* यूज़र की पार्किंग */
        .user-parking { background:white; padding:30px; border-radius:15px; box-shadow:0 5px 15px rgba(0,0,0,0.08); }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { padding:15px; text-align:left; border-bottom:1px solid #ddd; }
        th { background:#2c3e50; color:white; }
        tr:hover { background:#f5f5f5; }
        
        /* पार्किंग गाइड */
        .parking-guide { background:#fff3cd; padding:20px; border-radius:10px; margin:20px 0; border-left:5px solid #f39c12; }
        .guide-list { display:grid; grid-template-columns:repeat(2, 1fr); gap:15px; margin-top:15px; }
        .guide-item { display:flex; align-items:center; gap:10px; }
        .guide-icon { font-size:1.5rem; }
        
        .page-title { text-align:center; font-size:2.5rem; color:#2c3e50; margin-bottom:30px; }
        .page-title::after { content:''; display:block; width:100px; height:4px; background:#e74c3c; margin:10px auto; border-radius:2px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">अंकित होटल</div>
        <div class="nav-links">
            <a href="index.php">होम</a>
            <a href="dashboard.php">डैशबोर्ड</a>
            <a href="mybookings.php">मेरी बुकिंग्स</a>
            <a href="parking.php">पार्किंग</a>
            <a href="logout.php">लॉगआउट</a>
        </div>
    </nav>

    <div class="container">
        <h1 class="page-title">🚗 होटल पार्किंग</h1>
        
        <!-- पार्किंग डैशबोर्ड -->
        <div class="parking-dashboard">
            <div class="dashboard-card">
                <h3>कुल स्लॉट</h3>
                <div class="number total">10</div>
                <p>कुल पार्किंग स्पेस</p>
            </div>
            <div class="dashboard-card">
                <h3>उपलब्ध</h3>
                <div class="number available"><?php echo $available_count; ?></div>
                <p>खाली स्लॉट</p>
            </div>
            <div class="dashboard-card">
                <h3>भरे हुए</h3>
                <div class="number occupied"><?php echo 10 - $available_count; ?></div>
                <p>ऑक्यूपाइड स्लॉट</p>
            </div>
        </div>
        
        <!-- पार्किंग गाइड -->
        <div class="parking-guide">
            <h3 style="color:#856404; margin-bottom:15px;">📋 पार्किंग गाइड</h3>
            <div class="guide-list">
                <div class="guide-item">
                    <div class="guide-icon">💰</div>
                    <div>
                        <strong>किराया:</strong><br>
                        कार: ₹200/दिन, बाइक: ₹100/दिन
                    </div>
                </div>
                <div class="guide-item">
                    <div class="guide-icon">⏰</div>
                    <div>
                        <strong>समय:</strong><br>
                        24x7 पार्किंग उपलब्ध
                    </div>
                </div>
                <div class="guide-item">
                    <div class="guide-icon">🔄</div>
                    <div>
                        <strong>कैंसिलेशन:</strong><br>
                        6 घंटे पहले तक फ्री
                    </div>
                </div>
                <div class="guide-item">
                    <div class="guide-icon">📞</div>
                    <div>
                        <strong>हेल्प:</strong><br>
                        पार्किंग असिस्टेंट: 7697878985
                    </div>
                </div>
            </div>
        </div>
        
        <!-- पार्किंग लॉट मैप -->
        <div class="parking-lot">
            <h2>📍 पार्किंग लॉट मैप</h2>
            <div class="slots-container">
                <?php while($slot = mysqli_fetch_assoc($parking_slots)): ?>
                <div class="slot <?php echo $slot['is_available'] ? 'available' : 'occupied'; ?>">
                    <div class="slot-number"><?php echo $slot['slot_number']; ?></div>
                    <div class="vehicle-type">
                        <?php echo $slot['vehicle_type'] == 'car' ? '🚗' : '🏍️'; ?>
                    </div>
                    <div class="slot-status <?php echo $slot['is_available'] ? 'available-status' : 'occupied-status'; ?>">
                        <?php echo $slot['is_available'] ? 'उपलब्ध' : 'भरा हुआ'; ?>
                    </div>
                    <?php if(!$slot['is_available']): ?>
                        <div style="font-size:0.8rem; color:#666; margin-top:5px;">
                            बुकिंग #<?php echo $slot['booking_id']; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- मेरी पार्किंग -->
        <div class="user-parking">
            <h2 style="color:#2c3e50; margin-bottom:20px;">📋 मेरी पार्किंग</h2>
            
            <?php if(mysqli_num_rows($user_parking) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>स्लॉट नंबर</th>
                            <th>वाहन प्रकार</th>
                            <th>वाहन नंबर</th>
                            <th>कमरा</th>
                            <th>चेक-इन</th>
                            <th>चेक-आउट</th>
                            <th>पार्किंग फी</th>
                            <th>स्टेटस</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($parking = mysqli_fetch_assoc($user_parking)): ?>
                        <tr>
                            <td><?php echo $parking['slot_number']; ?></td>
                            <td>
                                <?php echo $parking['vehicle_type'] == 'car' ? '🚗 कार' : '🏍️ बाइक'; ?>
                            </td>
                            <td><?php echo $parking['vehicle_number']; ?></td>
                            <td><?php echo $parking['room_type']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($parking['check_in'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($parking['check_out'])); ?></td>
                            <td>₹<?php echo $parking['parking_fee']; ?></td>
                            <td>
                                <span style="background:#27ae60; color:white; padding:5px 10px; border-radius:5px;">
                                    एक्टिव
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#666;">
                    <div style="font-size:4rem; margin-bottom:20px;">🚗</div>
                    <h3>कोई पार्किंग बुक नहीं की गई</h3>
                    <p>अपनी अगली बुकिंग में पार्किंग ऐड करें</p>
                    <a href="booking.php" style="display:inline-block; margin-top:20px; padding:12px 24px; background:#3498db; color:white; text-decoration:none; border-radius:8px;">
                        बुकिंग करें
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- पार्किंग नियम -->
        <div style="background:#e8f4f8; padding:25px; border-radius:15px; margin-top:40px;">
            <h3 style="color:#2c3e50; margin-bottom:15px;">📜 पार्किंग नियम</h3>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                <div>
                    <h4>✅ अनुमति</h4>
                    <ul style="color:#555; line-height:1.8;">
                        <li>केवल बुक किए गए गेस्ट्स के लिए</li>
                        <li>वाहन रजिस्ट्रेशन दिखाना जरूरी</li>
                        <li>स्लॉट नंबर में ही पार्क करें</li>
                        <li>की दी हुई जगह पर रखें</li>
                    </ul>
                </div>
                <div>
                    <h4>❌ मनाही</h4>
                    <ul style="color:#555; line-height:1.8;">
                        <li>ड्राइविंग लाइसेंस न होने पर</li>
                        <li>गलत स्लॉट में पार्किंग</li>
                        <li>नो पार्किंग जोन में पार्किंग</li>
                        <li>रात 10 बजे के बाद हॉर्न न बजाएं</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>