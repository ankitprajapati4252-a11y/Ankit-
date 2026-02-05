<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

// यूज़र की एक्टिव बुकिंग्स
$active_bookings = mysqli_query($conn, 
    "SELECT * FROM bookings 
     WHERE user_id = $user_id 
     AND check_out >= CURDATE() 
     ORDER BY check_in DESC");

// सभी खाने के आइटम्स
$food_items = mysqli_query($conn, "SELECT * FROM food_items ORDER BY category, name");

// यूज़र के पिछले ऑर्डर्स
$user_orders = mysqli_query($conn, 
    "SELECT fo.*, fi.name, fi.price, fi.image_url, b.id as booking_id
     FROM food_orders fo
     JOIN food_items fi ON fo.food_item_id = fi.id
     JOIN bookings b ON fo.booking_id = b.id
     WHERE b.user_id = $user_id
     ORDER BY fo.order_date DESC
     LIMIT 10");

// खाना ऑर्डर हैंडल करो
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['order_food'])) {
        $booking_id = $_POST['booking_id'];
        $food_item_id = $_POST['food_item_id'];
        $quantity = $_POST['quantity'];
        $special_instructions = $_POST['special_instructions'] ?? '';
        
        // खाने की कीमत निकालो
        $food_item = mysqli_fetch_assoc(mysqli_query($conn, 
            "SELECT price FROM food_items WHERE id = $food_item_id"));
        $item_total = $food_item['price'] * $quantity;
        
        $sql = "INSERT INTO food_orders (booking_id, food_item_id, quantity, special_instructions) 
                VALUES ($booking_id, $food_item_id, $quantity, '$special_instructions')";
        
        if(mysqli_query($conn, $sql)) {
            $order_id = mysqli_insert_id($conn);
            $success = "✅ ऑर्डर #$order_id सफलतापूर्वक दर्ज हो गया!";
        } else {
            $error = "❌ ऑर्डर फेल हो गया!";
        }
    }
    
    // कार्ट से ऑर्डर
    if(isset($_POST['order_from_cart'])) {
        $cart_items = json_decode($_POST['cart_items'], true);
        $booking_id = $_POST['cart_booking_id'];
        
        foreach($cart_items as $item) {
            $food_item_id = $item['id'];
            $quantity = $item['quantity'];
            
            $sql = "INSERT INTO food_orders (booking_id, food_item_id, quantity) 
                    VALUES ($booking_id, $food_item_id, $quantity)";
            mysqli_query($conn, $sql);
        }
        
        $success = "✅ " . count($cart_items) . " आइटम्स का ऑर्डर सफल!";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>खाना ऑर्डर - अंकित होटल</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Arial, sans-serif; }
        body { background:#f8f9fa; color:#333; }
        
        /* Navbar */
        .navbar { background:linear-gradient(135deg, #1a2a3a, #2c3e50); padding:18px 40px; display:flex; justify-content:space-between; align-items:center; color:white; box-shadow:0 4px 12px rgba(0,0,0,0.1); position:sticky; top:0; z-index:1000; }
        .logo { font-size:28px; font-weight:bold; color:#f39c12; }
        .nav-links { display:flex; gap:25px; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; padding:8px 15px; border-radius:5px; transition:all 0.3s; }
        .nav-links a:hover { background:rgba(255,255,255,0.1); color:#f39c12; }
        
        /* Container */
        .container { max-width:1400px; margin:0 auto; padding:30px 20px; }
        
        /* Page Title */
        .page-title { text-align:center; font-size:2.8rem; color:#2c3e50; margin-bottom:10px; }
        .page-subtitle { text-align:center; color:#666; margin-bottom:40px; font-size:1.2rem; }
        
        /* Sections */
        .section { background:white; border-radius:15px; padding:30px; margin-bottom:40px; box-shadow:0 5px 20px rgba(0,0,0,0.05); }
        .section-title { font-size:1.8rem; color:#2c3e50; margin-bottom:25px; padding-bottom:15px; border-bottom:3px solid #e74c3c; display:flex; align-items:center; gap:10px; }
        
        /* Food Categories */
        .category-tabs { display:flex; gap:10px; margin-bottom:30px; flex-wrap:wrap; }
        .category-tab { padding:12px 25px; background:#ecf0f1; border-radius:30px; cursor:pointer; transition:all 0.3s; font-weight:500; }
        .category-tab:hover, .category-tab.active { background:#e74c3c; color:white; }
        
        /* Food Grid */
        .food-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:30px; margin-top:20px; }
        .food-card { border-radius:15px; overflow:hidden; box-shadow:0 8px 25px rgba(0,0,0,0.08); transition:all 0.3s; background:white; }
        .food-card:hover { transform:translateY(-10px); box-shadow:0 15px 35px rgba(0,0,0,0.15); }
        .food-img { height:200px; width:100%; object-fit:cover; }
        .food-info { padding:25px; }
        .food-name { font-size:1.4rem; color:#2c3e50; margin-bottom:10px; }
        .food-desc { color:#666; margin-bottom:15px; line-height:1.6; }
        .food-category { display:inline-block; padding:5px 15px; background:#ecf0f1; border-radius:20px; font-size:0.9rem; color:#7f8c8d; margin-bottom:10px; }
        .food-price { color:#e74c3c; font-size:1.6rem; font-weight:bold; margin:15px 0; }
        .price-symbol { font-size:1.2rem; }
        
        /* Order Form */
        .order-form { background:#f8f9fa; padding:25px; border-radius:10px; margin-top:20px; }
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:600; color:#2c3e50; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:14px; border:2px solid #ddd; border-radius:8px; font-size:1rem; transition:border 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:#3498db; outline:none; }
        
        /* Buttons */
        .btn { display:inline-block; padding:14px 28px; background:#e74c3c; color:white; text-decoration:none; border-radius:10px; font-weight:600; font-size:1rem; border:none; cursor:pointer; transition:all 0.3s; }
        .btn:hover { background:#c0392b; transform:translateY(-2px); }
        .btn-primary { background:#3498db; }
        .btn-primary:hover { background:#2980b9; }
        .btn-success { background:#2ecc71; }
        .btn-success:hover { background:#27ae60; }
        
        /* Cart */
        .cart-sidebar { position:fixed; top:100px; right:20px; width:350px; background:white; border-radius:15px; box-shadow:0 10px 40px rgba(0,0,0,0.15); padding:25px; z-index:100; display:none; }
        .cart-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; }
        .cart-items { max-height:400px; overflow-y:auto; }
        .cart-item { display:flex; align-items:center; padding:15px 0; border-bottom:1px solid #eee; }
        .cart-item-img { width:60px; height:60px; object-fit:cover; border-radius:8px; margin-right:15px; }
        .cart-item-info { flex:1; }
        .cart-item-name { font-weight:600; color:#2c3e50; }
        .cart-item-price { color:#e74c3c; font-weight:bold; }
        .cart-item-quantity { display:flex; align-items:center; gap:10px; margin-top:5px; }
        .quantity-btn { width:30px; height:30px; border-radius:50%; background:#ecf0f1; border:none; cursor:pointer; }
        .cart-total { font-size:1.4rem; font-weight:bold; color:#2c3e50; margin-top:20px; padding-top:20px; border-top:2px solid #eee; }
        
        /* Orders History */
        .orders-table { width:100%; border-collapse:collapse; margin-top:20px; }
        .orders-table th, .orders-table td { padding:15px; text-align:left; border-bottom:1px solid #eee; }
        .orders-table th { background:#2c3e50; color:white; }
        .orders-table tr:hover { background:#f9f9f9; }
        
        /* Messages */
        .success-message { background:#d4edda; color:#155724; padding:15px; border-radius:8px; margin-bottom:20px; border-left:5px solid #28a745; }
        .error-message { background:#f8d7da; color:#721c24; padding:15px; border-radius:8px; margin-bottom:20px; border-left:5px solid #dc3545; }
        
        /* Food Categories Icons */
        .category-icon { font-size:1.2rem; margin-right:5px; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .food-grid { grid-template-columns:1fr; }
            .cart-sidebar { position:static; width:100%; margin-top:20px; }
            .nav-links { flex-direction:column; gap:10px; }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">अंकित होटल</div>
        <div class="nav-links">
            <a href="index.php">🏠 होम</a>
            <a href="dashboard.php">📊 डैशबोर्ड</a>
            <a href="mybookings.php">📋 मेरी बुकिंग्स</a>
            <a href="booking.php">🏨 नई बुकिंग</a>
            <a href="parking.php">🚗 पार्किंग</a>
            <a href="food.php" style="background:rgba(231, 76, 60, 0.2);">🍽️ खाना ऑर्डर</a>
            <a href="logout.php">🚪 लॉगआउट</a>
            <a href="#" onclick="toggleCart()" style="position:relative;">
                🛒 कार्ट <span id="cart-count" style="background:#e74c3c; color:white; border-radius:50%; padding:2px 8px; font-size:12px; position:absolute; top:-8px; right:-8px;">0</span>
            </a>
        </div>
    </nav>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3>🛒 आपका कार्ट</h3>
            <button onclick="toggleCart()" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">×</button>
        </div>
        <div class="cart-items" id="cartItems">
            <!-- Cart items will be added here dynamically -->
        </div>
        <div class="cart-total">
            कुल: ₹<span id="cartTotal">0</span>
        </div>
        <div class="form-group">
            <label>बुकिंग चुनें:</label>
            <select id="cartBookingSelect">
                <option value="">-- बुकिंग चुनें --</option>
                <?php while($booking = mysqli_fetch_assoc($active_bookings)): 
                    mysqli_data_seek($active_bookings, 0); // Reset pointer
                ?>
                <option value="<?php echo $booking['id']; ?>">
                    बुकिंग #<?php echo $booking['id']; ?> (<?php echo date('d-m-Y', strtotime($booking['check_in'])); ?>)
                </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button class="btn btn-success" style="width:100%; margin-top:15px;" onclick="placeCartOrder()">✅ कार्ट से ऑर्डर करें</button>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Page Header -->
        <h1 class="page-title">🍽️ अंकित होटल रेस्टोरेंट</h1>
        <p class="page-subtitle">24x7 रूम सर्विस उपलब्ध | स्वादिष्ट भोजन ताजगी के साथ</p>
        
        <!-- Messages -->
        <?php if(isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Quick Order Section -->
        <div class="section">
            <h2 class="section-title">⚡ त्वरित ऑर्डर</h2>
            <form method="POST" class="order-form">
                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px;">
                    <div class="form-group">
                        <label>📋 बुकिंग चुनें</label>
                        <select name="booking_id" required>
                            <option value="">-- अपनी बुकिंग चुनें --</option>
                            <?php while($booking = mysqli_fetch_assoc($active_bookings)): ?>
                            <option value="<?php echo $booking['id']; ?>">
                                बुकिंग #<?php echo $booking['id']; ?> 
                                (<?php echo date('d-m-Y', strtotime($booking['check_in'])); ?> से <?php echo date('d-m-Y', strtotime($booking['check_out'])); ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>🍽️ खाना चुनें</label>
                        <select name="food_item_id" required>
                            <option value="">-- खाना चुनें --</option>
                            <?php while($food = mysqli_fetch_assoc($food_items)): 
                                mysqli_data_seek($food_items, 0); // Reset pointer
                            ?>
                            <option value="<?php echo $food['id']; ?>" data-price="<?php echo $food['price']; ?>">
                                <?php echo $food['name']; ?> - ₹<?php echo $food['price']; ?> 
                                (<?php echo $food['category']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>🔢 मात्रा</label>
                        <input type="number" name="quantity" min="1" max="10" value="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>📝 विशेष निर्देश (अगर कोई हो)</label>
                    <textarea name="special_instructions" rows="2" placeholder="जैसे: कम मसाला, एक्स्ट्रा चटनी, आदि"></textarea>
                </div>
                
                <button type="submit" name="order_food" class="btn">✅ ऑर्डर करें</button>
            </form>
        </div>
        
        <!-- Food Menu Section -->
        <div class="section">
            <h2 class="section-title">📋 मेनू कार्ड</h2>
            
            <!-- Category Tabs -->
            <div class="category-tabs">
                <div class="category-tab active" onclick="filterCategory('all')">🍽️ सभी</div>
                <div class="category-tab" onclick="filterCategory('शाकाहार')">🥦 शाकाहार</div>
                <div class="category-tab" onclick="filterCategory('मांसाहार')">🍗 मांसाहार</div>
                <div class="category-tab" onclick="filterCategory('रोटी')">🫓 रोटी</div>
                <div class="category-tab" onclick="filterCategory('स्टार्टर')">🍢 स्टार्टर</div>
                <div class="category-tab" onclick="filterCategory('स्नैक्स')">🥨 स्नैक्स</div>
                <div class="category-tab" onclick="filterCategory('डेजर्ट')">🍨 डेजर्ट</div>
                <div class="category-tab" onclick="filterCategory('ड्रिंक्स')">🥤 ड्रिंक्स</div>
            </div>
            
            <!-- Food Grid -->
            <div class="food-grid" id="foodGrid">
                <?php while($food = mysqli_fetch_assoc($food_items)): ?>
                <div class="food-card" data-category="<?php echo $food['category']; ?>">
                    <img src="<?php echo $food['image_url']; ?>" alt="<?php echo $food['name']; ?>" class="food-img">
                    <div class="food-info">
                        <span class="food-category">
                            <?php 
                            $icons = [
                                'शाकाहार' => '🥦',
                                'मांसाहार' => '🍗',
                                'रोटी' => '🫓',
                                'स्टार्टर' => '🍢',
                                'स्नैक्स' => '🥨',
                                'डेजर्ट' => '🍨',
                                'ड्रिंक्स' => '🥤'
                            ];
                            echo ($icons[$food['category']] ?? '🍽️') . ' ' . $food['category'];
                            ?>
                        </span>
                        <h3 class="food-name"><?php echo $food['name']; ?></h3>
                        <p class="food-desc"><?php echo $food['description']; ?></p>
                        <div class="food-price">
                            <span class="price-symbol">₹</span><?php echo $food['price']; ?>
                        </div>
                        <div style="display:flex; gap:10px;">
                            <button class="btn" onclick="addToCart(<?php echo $food['id']; ?>, '<?php echo $food['name']; ?>', <?php echo $food['price']; ?>, '<?php echo $food['image_url']; ?>')">
                                🛒 कार्ट में डालें
                            </button>
                            <button class="btn btn-primary" onclick="quickOrder(<?php echo $food['id']; ?>)">
                                ⚡ ऑर्डर करें
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Order History -->
        <div class="section">
            <h2 class="section-title">📜 आपके पिछले ऑर्डर्स</h2>
            
            <?php if(mysqli_num_rows($user_orders) > 0): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>ऑर्डर आईडी</th>
                            <th>खाना</th>
                            <th>मात्रा</th>
                            <th>कीमत</th>
                            <th>बुकिंग</th>
                            <th>तारीख</th>
                            <th>स्टेटस</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = mysqli_fetch_assoc($user_orders)): 
                            $total_price = $order['price'] * $order['quantity'];
                        ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <img src="<?php echo $order['image_url']; ?>" alt="<?php echo $order['name']; ?>" style="width:50px; height:50px; object-fit:cover; border-radius:5px;">
                                    <?php echo $order['name']; ?>
                                </div>
                            </td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>₹<?php echo $total_price; ?></td>
                            <td>बुकिंग #<?php echo $order['booking_id']; ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($order['order_date'])); ?></td>
                            <td>
                                <span style="background:#2ecc71; color:white; padding:5px 10px; border-radius:5px; font-size:0.9rem;">
                                    डिलीवर हो गया
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#666;">
                    <div style="font-size:4rem; margin-bottom:20px;">🍽️</div>
                    <h3>कोई ऑर्डर नहीं मिला</h3>
                    <p>अभी तक आपने कोई खाना ऑर्डर नहीं किया है</p>
                    <button class="btn" style="margin-top:20px;" onclick="document.querySelector('.category-tabs .active').click()">
                        मेनू देखें
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Restaurant Info -->
        <div class="section">
            <h2 class="section-title">🏨 रेस्टोरेंट जानकारी</h2>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:30px;">
                <div>
                    <h3 style="color:#2c3e50; margin-bottom:15px;">⏰ टाइमिंग</h3>
                    <ul style="color:#555; line-height:2;">
                        <li>ब्रेकफास्ट: 7:00 AM - 11:00 AM</li>
                        <li>लंच: 12:00 PM - 3:30 PM</li>
                        <li>डिनर: 7:00 PM - 11:30 PM</li>
                        <li>24x7 रूम सर्विस उपलब्ध</li>
                    </ul>
                </div>
                <div>
                    <h3 style="color:#2c3e50; margin-bottom:15px;">📞 संपर्क</h3>
                    <ul style="color:#555; line-height:2;">
                        <li>रेस्टोरेंट मैनेजर: 7697878985</li>
                        <li>रूम सर्विस: एक्सटेंशन 101</li>
                        <li>ईमेल: restaurant@ankithotel.com</li>
                        <li>सर्विस चार्ज: 5% एक्स्ट्रा</li>
                    </ul>
                </div>
                <div>
                    <h3 style="color:#2c3e50; margin-bottom:15px;">💡 नोट</h3>
                    <ul style="color:#555; line-height:2;">
                        <li>ऑर्डर 30-45 मिनट में डिलीवर</li>
                        <li>कैंसिलेशन 15 मिनट के अंदर</li>
                        <li>बिल रूम में ही दिया जाएगा</li>
                        <li>UPI/कैश पेमेंट उपलब्ध</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Cart array
        let cart = [];
        
        // Toggle cart sidebar
        function toggleCart() {
            const cartSidebar = document.getElementById('cartSidebar');
            cartSidebar.style.display = cartSidebar.style.display === 'block' ? 'none' : 'block';
            updateCartDisplay();
        }
        
        // Add item to cart
        function addToCart(id, name, price, imageUrl) {
            // Check if item already in cart
            const existingItem = cart.find(item => item.id === id);
            
            if(existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1,
                    imageUrl: imageUrl
                });
            }
            
            updateCartDisplay();
            showNotification(`✅ ${name} कार्ट में ऐड हो गया!`);
        }
        
        // Quick order function
        function quickOrder(foodId) {
            document.querySelector('select[name="food_item_id"]').value = foodId;
            document.querySelector('select[name="food_item_id"]').scrollIntoView({ behavior: 'smooth' });
            showNotification("⚡ ऑर्डर फॉर्म में ऐड हो गया!");
        }
        
        // Update cart display
        function updateCartDisplay() {
            // Update cart count
            const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cart-count').textContent = totalItems;
            
            // Update cart items
            const cartItemsDiv = document.getElementById('cartItems');
            const cartTotalSpan = document.getElementById('cartTotal');
            
            if(cart.length === 0) {
                cartItemsDiv.innerHTML = '<p style="text-align:center; color:#666; padding:20px;">कार्ट खाली है</p>';
                cartTotalSpan.textContent = '0';
                return;
            }
            
            let itemsHTML = '';
            let total = 0;
            
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                total += itemTotal;
                
                itemsHTML += `
                    <div class="cart-item">
                        <img src="${item.imageUrl}" alt="${item.name}" class="cart-item-img">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">₹${item.price} × ${item.quantity} = ₹${itemTotal}</div>
                            <div class="cart-item-quantity">
                                <button class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                                <button onclick="removeFromCart(${index})" style="margin-left:auto; background:none; border:none; color:#e74c3c; cursor:pointer;">🗑️</button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            cartItemsDiv.innerHTML = itemsHTML;
            cartTotalSpan.textContent = total;
        }
        
        // Update item quantity
        function updateQuantity(index, change) {
            if(cart[index].quantity + change >= 1) {
                cart[index].quantity += change;
                updateCartDisplay();
            }
        }
        
        // Remove item from cart
        function removeFromCart(index) {
            const itemName = cart[index].name;
            cart.splice(index, 1);
            updateCartDisplay();
            showNotification(`🗑️ ${itemName} कार्ट से रिमूव हो गया!`);
        }
        
        // Place order from cart
        function placeCartOrder() {
            const bookingSelect = document.getElementById('cartBookingSelect');
            const bookingId = bookingSelect.value;
            
            if(!bookingId) {
                alert('कृपया बुकिंग चुनें!');
                return;
            }
            
            if(cart.length === 0) {
                alert('कार्ट खाली है!');
                return;
            }
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            
            const bookingInput = document.createElement('input');
            bookingInput.type = 'hidden';
            bookingInput.name = 'cart_booking_id';
            bookingInput.value = bookingId;
            form.appendChild(bookingInput);
            
            const cartInput = document.createElement('input');
            cartInput.type = 'hidden';
            cartInput.name = 'cart_items';
            cartInput.value = JSON.stringify(cart);
            form.appendChild(cartInput);
            
            const submitInput = document.createElement('input');
            submitInput.type = 'hidden';
            submitInput.name = 'order_from_cart';
            submitInput.value = '1';
            form.appendChild(submitInput);
            
            document.body.appendChild(form);
            form.submit();
        }
        
        // Filter food by category
        function filterCategory(category) {
            const foodCards = document.querySelectorAll('.food-card');
            const tabs = document.querySelectorAll('.category-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            foodCards.forEach(card => {
                if(category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Show notification
        function showNotification(message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #2ecc71;
                color: white;
                padding: 15px 25px;
                border-radius: 8px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: slideIn 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Add CSS animation if not exists
            if(!document.getElementById('notification-style')) {
                const style = document.createElement('style');
                style.id = 'notification-style';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            // Remove after 3 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Initialize cart on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCartDisplay();
        });
    </script>
</body>
</html>