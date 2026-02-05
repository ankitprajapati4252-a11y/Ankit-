<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");
$user_id = $_SESSION['user_id'];

// Get user details
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
$user = mysqli_fetch_assoc($user_query);

// Update profile
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $emergency_contact = $_POST['emergency_contact'];
    
    $sql = "UPDATE users SET 
            name = '$name',
            phone = '$phone',
            address = '$address',
            city = '$city',
            state = '$state',
            pincode = '$pincode',
            emergency_contact = '$emergency_contact',
            updated_at = NOW()
            WHERE id = $user_id";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['user_name'] = $name;
        $success = "✅ प्रोफाइल अपडेट हो गई!";
        
        // Refresh user data
        $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
        $user = mysqli_fetch_assoc($user_query);
    } else {
        $error = "❌ अपडेट फेल!";
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>मेरी प्रोफाइल - अंकित होटल</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Arial, sans-serif; }
        body { background:#f8f9fa; }
        
        .navbar { background:linear-gradient(135deg, #1a2a3a, #2c3e50); padding:18px 40px; display:flex; justify-content:space-between; color:white; }
        .logo { font-size:28px; font-weight:bold; color:#f39c12; }
        .nav-links { display:flex; gap:25px; }
        .nav-links a { color:white; text-decoration:none; font-weight:500; }
        
        .container { max-width:1000px; margin:0 auto; padding:40px 20px; }
        
        .profile-header { 
            background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color:white; 
            padding:40px; 
            border-radius:15px; 
            margin-bottom:30px; 
            text-align:center; 
        }
        
        .profile-avatar { 
            font-size:80px; 
            margin-bottom:20px; 
        }
        
        .profile-name { 
            font-size:2.5rem; 
            margin-bottom:10px; 
        }
        
        .profile-email { 
            opacity:0.9; 
            margin-bottom:5px; 
        }
        
        .profile-grid { 
            display:grid; 
            grid-template-columns:repeat(2, 1fr); 
            gap:30px; 
        }
        
        .info-card { 
            background:white; 
            padding:30px; 
            border-radius:15px; 
            box-shadow:0 5px 20px rgba(0,0,0,0.05); 
        }
        
        .info-card h3 { 
            color:#2c3e50; 
            margin-bottom:20px; 
            padding-bottom:10px; 
            border-bottom:2px solid #f0f0f0; 
            display:flex; 
            align-items:center; 
            gap:10px; 
        }
        
        .info-item { 
            margin-bottom:15px; 
            display:flex; 
        }
        
        .info-label { 
            min-width:180px; 
            color:#666; 
            font-weight:500; 
        }
        
        .info-value { 
            color:#333; 
            flex:1; 
        }
        
        .edit-form { 
            background:white; 
            padding:30px; 
            border-radius:15px; 
            box-shadow:0 5px 20px rgba(0,0,0,0.05); 
            margin-top:30px; 
        }
        
        .form-grid { 
            display:grid; 
            grid-template-columns:repeat(2, 1fr); 
            gap:20px; 
        }
        
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:600; color:#333; }
        .form-group input, .form-group textarea { 
            width:100%; 
            padding:12px; 
            border:2px solid #ddd; 
            border-radius:8px; 
            font-size:16px; 
        }
        
        .form-group.full-width { grid-column:1 / -1; }
        
        .btn { 
            padding:14px 30px; 
            background:#764ba2; 
            color:white; 
            border:none; 
            border-radius:10px; 
            font-size:16px; 
            cursor:pointer; 
            transition:all 0.3s; 
        }
        
        .btn:hover { background:#5d3a82; }
        
        .message { 
            padding:15px; 
            border-radius:10px; 
            margin-bottom:20px; 
        }
        
        .success { background:#d4edda; color:#155724; }
        .error { background:#f8d7da; color:#721c24; }
        
        @media (max-width: 768px) {
            .profile-grid { grid-template-columns:1fr; }
            .form-grid { grid-template-columns:1fr; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">अंकित होटल</div>
        <div class="nav-links">
            <a href="index.php">🏠 होम</a>
            <a href="dashboard.php">📊 डैशबोर्ड</a>
            <a href="profile.php" style="background:rgba(255,255,255,0.1);">👤 प्रोफाइल</a>
            <a href="mybookings.php">📋 मेरी बुकिंग्स</a>
            <a href="logout.php">🚪 लॉगआउट</a>
        </div>
    </nav>
    
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php 
                $gender_icon = ($user['gender'] == 'female') ? '👩' : (($user['gender'] == 'male') ? '👨' : '👤');
                echo $gender_icon;
                ?>
            </div>
            <h1 class="profile-name"><?php echo $user['name']; ?></h1>
            <p class="profile-email">📧 <?php echo $user['email']; ?></p>
            <p>📞 <?php echo $user['phone']; ?> | 🆔 आधार: <?php echo $user['aadhar_card']; ?></p>
        </div>
        
        <!-- Messages -->
        <?php if(isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Profile Information -->
        <div class="profile-grid">
            <!-- Personal Info -->
            <div class="info-card">
                <h3>👤 व्यक्तिगत जानकारी</h3>
                <div class="info-item">
                    <div class="info-label">पूरा नाम:</div>
                    <div class="info-value"><?php echo $user['name']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">लिंग:</div>
                    <div class="info-value">
                        <?php 
                        echo ($user['gender'] == 'female') ? 'महिला' : 
                             (($user['gender'] == 'male') ? 'पुरुष' : 'अन्य');
                        ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">जन्म तिथि:</div>
                    <div class="info-value"><?php echo date('d-m-Y', strtotime($user['date_of_birth'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">आयु:</div>
                    <div class="info-value">
                        <?php 
                        $birthDate = new DateTime($user['date_of_birth']);
                        $today = new DateTime();
                        $age = $today->diff($birthDate)->y;
                        echo $age . ' वर्ष';
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Identity Info -->
            <div class="info-card">
                <h3>🆔 पहचान विवरण</h3>
                <div class="info-item">
                    <div class="info-label">आधार कार्ड:</div>
                    <div class="info-value"><?php echo $user['aadhar_card']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">पिता का नाम:</div>
                    <div class="info-value"><?php echo $user['father_name']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">ईमेल:</div>
                    <div class="info-value"><?php echo $user['email']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">मोबाइल:</div>
                    <div class="info-value"><?php echo $user['phone']; ?></div>
                </div>
            </div>
            
            <!-- Address Info -->
            <div class="info-card">
                <h3>🏠 पता विवरण</h3>
                <div class="info-item">
                    <div class="info-label">पता:</div>
                    <div class="info-value"><?php echo $user['address']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">शहर:</div>
                    <div class="info-value"><?php echo $user['city']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">राज्य:</div>
                    <div class="info-value"><?php echo $user['state']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">पिन कोड:</div>
                    <div class="info-value"><?php echo $user['pincode']; ?></div>
                </div>
            </div>
            
            <!-- Emergency Info -->
            <div class="info-card">
                <h3>🚨 आपातकालीन संपर्क</h3>
                <div class="info-item">
                    <div class="info-label">आपातकालीन नंबर:</div>
                    <div class="info-value"><?php echo $user['emergency_contact']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">अकाउंट बनाया:</div>
                    <div class="info-value"><?php echo date('d-m-Y H:i', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">अंतिम अपडेट:</div>
                    <div class="info-value"><?php echo date('d-m-Y H:i', strtotime($user['updated_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Edit Profile Form -->
        <div class="edit-form">
            <h3 style="color:#2c3e50; margin-bottom:25px;">✏️ प्रोफाइल एडिट करें</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>पूरा नाम</label>
                        <input type="text" name="name" value="<?php echo $user['name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>मोबाइल नंबर</label>
                        <input type="tel" name="phone" value="<?php echo $user['phone']; ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>पता</label>
                        <textarea name="address" rows="3" required><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>शहर</label>
                        <input type="text" name="city" value="<?php echo $user['city']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>राज्य</label>
                        <input type="text" name="state" value="<?php echo $user['state']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>पिन कोड</label>
                        <input type="text" name="pincode" value="<?php echo $user['pincode']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>आपातकालीन संपर्क</label>
                        <input type="tel" name="emergency_contact" value="<?php echo $user['emergency_contact']; ?>" required>
                    </div>
                </div>
                
                <div style="margin-top:30px; text-align:center;">
                    <button type="submit" name="update_profile" class="btn">
                        ✅ अपडेट करें
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>