<?php
echo "<h1>अंकित होटल - डेटाबेस सेटअप</h1>";

// MAMP कनेक्शन
$conn = mysqli_connect("localhost", "root", "root");

if(!$conn) {
    die("❌ MySQL कनेक्शन फेल: " . mysqli_connect_error());
}

echo "✅ MySQL कनेक्शन सफल<br>";

// डेटाबेस बनाएं
$sql = "CREATE DATABASE IF NOT EXISTS hotel_ankit";
if(mysqli_query($conn, $sql)) {
    echo "✅ डेटाबेस बन गया<br>";
} else {
    echo "❌ डेटाबेस एरर: " . mysqli_error($conn) . "<br>";
}

// डेटाबेस सेलेक्ट करें
mysqli_select_db($conn, "hotel_ankit");

// पुरानी टेबल्स डिलीट करें
$tables = ['parking_slots', 'food_orders', 'bookings', 'food_items', 'rooms', 'users'];
foreach($tables as $table) {
    mysqli_query($conn, "DROP TABLE IF EXISTS $table");
}
echo "✅ पुरानी टेबल्स डिलीट<br>";

// टेबल्स बनाएं (अपडेटेड users टेबल)
$queries = [
    "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(15) NOT NULL,
        password VARCHAR(255) NOT NULL,
        gender ENUM('male', 'female', 'other') NOT NULL,
        date_of_birth DATE NOT NULL,
        aadhar_card VARCHAR(12) UNIQUE NOT NULL,
        father_name VARCHAR(100),
        address TEXT,
        city VARCHAR(50),
        state VARCHAR(50),
        pincode VARCHAR(10),
        emergency_contact VARCHAR(15),
        id_proof_image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_type VARCHAR(50),
        description TEXT,
        price_per_night DECIMAL(10,2),
        capacity INT,
        image_url VARCHAR(500)
    )",
    
    "CREATE TABLE food_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100),
        category VARCHAR(50),
        price DECIMAL(10,2),
        description TEXT,
        image_url VARCHAR(500)
    )",
    
    "CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        room_id INT,
        check_in DATE,
        check_out DATE,
        guests INT,
        total_amount DECIMAL(10,2),
        parking_required BOOLEAN DEFAULT FALSE,
        vehicle_type VARCHAR(20),
        vehicle_number VARCHAR(20),
        booking_status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE food_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT,
        food_item_id INT,
        quantity INT,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE parking_slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slot_number VARCHAR(10),
        vehicle_type VARCHAR(20),
        is_available BOOLEAN DEFAULT TRUE,
        booking_id INT,
        check_in_time DATETIME,
        check_out_time DATETIME,
        parking_fee DECIMAL(10,2) DEFAULT 0
    )"
];

foreach($queries as $query) {
    if(mysqli_query($conn, $query)) {
        echo "✅ टेबल बन गई<br>";
    } else {
        echo "❌ टेबल एरर: " . mysqli_error($conn) . "<br>";
    }
}

// पार्किंग स्लॉट्स इन्सर्ट करें
$parking_slots = [
    "INSERT INTO parking_slots (slot_number, vehicle_type, is_available) VALUES
    ('P-001', 'car', TRUE),
    ('P-002', 'car', TRUE),
    ('P-003', 'car', TRUE),
    ('P-004', 'car', TRUE),
    ('P-005', 'car', TRUE),
    ('B-001', 'bike', TRUE),
    ('B-002', 'bike', TRUE),
    ('B-003', 'bike', TRUE),
    ('B-004', 'bike', TRUE),
    ('B-005', 'bike', TRUE)"
];

foreach($parking_slots as $slot) {
    if(mysqli_query($conn, $slot)) {
        echo "✅ पार्किंग स्लॉट्स इन्सर्ट हुए<br>";
    }
}

// बाकी डेटा इन्सर्ट करें
$insert_data = [
    // कमरे
    "INSERT INTO rooms (room_type, description, price_per_night, capacity, image_url) VALUES
    ('डीलक्स रूम', 'AC, डबल बेड, टीवी, WiFi, फ्री ब्रेकफास्ट', 2500, 2, 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('सुपर डीलक्स', 'AC, किंग बेड, मिनी फ्रिज, सोफा, बालकनी', 3500, 3, 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('सुइट रूम', 'लग्जरी सुइट, जकूजी, सीटिंग एरिया, व्यू', 5000, 4, 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('फैमिली रूम', '2 डबल बेड, AC, टीवी, विस्तृत स्पेस', 4000, 4, 'https://images.unsplash.com/photo-1566195992011-5f6b21e539aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')",
    
    // खाना
    "INSERT INTO food_items (name, category, price, description, image_url) VALUES
    ('पनीर टिक्का', 'शाकाहार', 250, 'तंदूरी पनीर, मसालेदार', 'https://images.unsplash.com/photo-1606491956689-2ea866880c84?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('बटर चिकन', 'मांसाहार', 350, 'मक्खन चिकन, क्रीमी ग्रेवी', 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('दाल मखनी', 'शाकाहार', 180, 'क्रीमी दाल, बटर टॉपिंग', 'https://images.unsplash.com/photo-1585937421612-70ca003675ed?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'),
    ('नान', 'रोटी', 50, 'तंदूरी नान, मक्खन लगा', 'https://images.unsplash.com/photo-1563379091339-03246963d9d6?ixlib=rb-4.0.3&auto=format&fit=crop&w-800&q=80'),
    ('बिरयानी', 'मांसाहार', 300, 'हाइदराबादी बिरयानी, रायता साथ', 'https://images.unsplash.com/photo-1563379091339-03246963d9d6?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80')",
    
    // टेस्ट यूज़र (पूरी डिटेल के साथ)
    "INSERT INTO users (name, email, phone, password, gender, date_of_birth, aadhar_card, father_name, address, city, state, pincode, emergency_contact) VALUES
    ('अंकित शर्मा', 'ankit@hotel.com', '7697878985', '".md5('123456')."', 'male', '1995-05-15', '123456789012', 'रमेश शर्मा', 'चिताड़, इंदौर', 'इंदौर', 'मध्य प्रदेश', '452001', '9876543210')"
];

foreach($insert_data as $data) {
    if(mysqli_query($conn, $data)) {
        echo "✅ डेटा इन्सर्ट हुआ<br>";
    } else {
        echo "❌ डेटा एरर: " . mysqli_error($conn) . "<br>";
    }
}

echo "<hr>";
echo "<h2 style='color:green;'>🎉 सेटअप पूरा हो गया!</h2>";
echo "<h3><a href='index.php'>🏠 होमपेज पर जाएं</a></h3>";
echo "<h3><a href='login.php'>🔑 लॉगिन करें (टेस्ट यूज़र)</a></h3>";
echo "<p><strong>टेस्ट लॉगिन:</strong></p>";
echo "<p>ईमेल: ankit@hotel.com</p>";
echo "<p>पासवर्ड: 123456</p>";

mysqli_close($conn);
?>