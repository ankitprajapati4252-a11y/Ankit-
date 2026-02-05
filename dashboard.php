<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

// Get user bookings
$bookings_query = mysqli_query($conn, 
    "SELECT bookings.*, rooms.room_type, rooms.price_per_night 
     FROM bookings 
     JOIN rooms ON bookings.room_id = rooms.id 
     WHERE bookings.user_id = $user_id 
     ORDER BY bookings.check_in DESC");
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>डैशबोर्ड - अंकित होटल</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial; }
        body { background:#f5f5f5; }
        .navbar { background:#2c3e50; padding:15px 30px; display:flex; justify-content:space-between; color:white; }
        .logo { font-size:24px; font-weight:bold; color:#f39c12; }
        .nav-links { display:flex; gap:20px; }
        .nav-links a { color:white; text-decoration:none; }
        .container { padding:40px; }
        .dashboard-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:30px; margin-top:30px; }
        .card { background:white; padding:30px; border-radius:10px; text-align:center; }
        .btn { display:inline-block; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px; margin-top:15px; }
        .bookings-table { width:100%; background:white; border-radius:10px; margin-top:30px; padding:20px; }
        table { width:100%; border-collapse:collapse; margin-top:20px; }
        th, td { padding:12px; text-align:left; border-bottom:1px solid #ddd; }
        th { background:#2c3e50; color:white; }
        tr:hover { background:#f5f5f5; }
        .no-bookings { text-align:center; padding:40px; background:white; border-radius:10px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">अंकित होटल</div>
        <div class="nav-links">
            <a href="index.php">होम</a>
            <a href="dashboard.php">डैशबोर्ड</a>
            <a href="booking.php">बुकिंग</a>
            <a href="food.php">खाना ऑर्डर</a>
            <a href="logout.php">लॉगआउट</a>
        </div>
    </nav>

    <div class="container">
        <h1>स्वागत है, <?php echo $_SESSION['user_name']; ?>!</h1>
        <p>आपका डैशबोर्ड</p>
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>नई बुकिंग</h3>
                <p>कमरा बुक करें</p>
                <a href="booking.php" class="btn">बुक करें</a>
            </div>
            <div class="card">
                <h3>खाना ऑर्डर</h3>
                <p>रेस्टोरेंट मेनू</p>
                <a href="food.php" class="btn">ऑर्डर करें</a>
            </div>
            <div class="card">
                <h3>मेरी जानकारी</h3>
                <p>नाम: <?php echo $_SESSION['user_name']; ?></p>
                <p>फोन: 7697878985</p>
            </div>
        </div>
        
        <!-- Bookings Section -->
        <div class="bookings-table">
            <h2>📋 मेरी बुकिंग्स</h2>
            
            <?php if(mysqli_num_rows($bookings_query) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>बुकिंग ID</th>
                            <th>कमरा</th>
                            <th>चेक-इन</th>
                            <th>चेक-आउट</th>
                            <th>अतिथि</th>
                            <th>कुल राशि</th>
                            <th>खाना ऑर्डर</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($booking = mysqli_fetch_assoc($bookings_query)): ?>
                        <tr>
                            <td>#<?php echo $booking['id']; ?></td>
                            <td><?php echo $booking['room_type']; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['check_in'])); ?></td>
                            <td><?php echo date('d-m-Y', strtotime($booking['check_out'])); ?></td>
                            <td><?php echo $booking['guests']; ?> व्यक्ति</td>
                            <td>₹<?php echo $booking['total_amount']; ?></td>
                            <td>
                                <a href="food.php?booking_id=<?php echo $booking['id']; ?>" 
                                   style="background:#e74c3c; color:white; padding:5px 10px; border-radius:3px; text-decoration:none;">
                                    ऑर्डर करें
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>📭 कोई बुकिंग नहीं मिली</h3>
                    <p>आपकी कोई बुकिंग नहीं है। पहली बुकिंग करें!</p>
                    <a href="booking.php" class="btn" style="margin-top:20px;">पहली बुकिंग करें</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Food Orders Section -->
        <div class="bookings-table" style="margin-top:40px;">
            <h2>🍽️ मेरे खाने के ऑर्डर</h2>
            
            <?php
            // Get food orders
            $food_orders_query = mysqli_query($conn, 
                "SELECT food_orders.*, food_items.name, food_items.price, bookings.id as booking_id
                 FROM food_orders
                 JOIN food_items ON food_orders.food_item_id = food_items.id
                 JOIN bookings ON food_orders.booking_id = bookings.id
                 WHERE bookings.user_id = $user_id
                 ORDER BY food_orders.order_date DESC");
            ?>
            
            <?php if(mysqli_num_rows($food_orders_query) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ऑर्डर ID</th>
                            <th>खाना</th>
                            <th>मात्रा</th>
                            <th>कीमत</th>
                            <th>बुकिंग ID</th>
                            <th>तारीख</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($food_orders_query)): 
                            $total_price = $order['price'] * $order['quantity'];
                        ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo $order['name']; ?></td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>₹<?php echo $total_price; ?></td>
                            <td>#<?php echo $order['booking_id']; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($order['order_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-bookings">
                    <h3>🍽️ कोई ऑर्डर नहीं मिला</h3>
                    <p>आपने अभी तक कोई खाना ऑर्डर नहीं किया है।</p>
                    <a href="food.php" class="btn" style="margin-top:20px;">खाना ऑर्डर करें</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>