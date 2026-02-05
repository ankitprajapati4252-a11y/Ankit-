<?php
session_start();
$conn = mysqli_connect("localhost", "root", "root", "hotel_ankit");

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Basic info
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = md5($_POST['password']);
    
    // Personal details
    $gender = $_POST['gender'];
    $date_of_birth = $_POST['date_of_birth'];
    $aadhar_card = $_POST['aadhar_card'];
    $father_name = $_POST['father_name'];
    
    // Address
    $address = $_POST['address'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $pincode = $_POST['pincode'];
    $emergency_contact = $_POST['emergency_contact'];
    
    // Check if Aadhar already exists
    $check_aadhar = mysqli_query($conn, "SELECT id FROM users WHERE aadhar_card = '$aadhar_card'");
    if(mysqli_num_rows($check_aadhar) > 0) {
        $error = "यह आधार कार्ड नंबर पहले से रजिस्टर्ड है!";
    } else {
        // Check if email already exists
        $check_email = mysqli_query($conn, "SELECT id FROM users WHERE email = '$email'");
        if(mysqli_num_rows($check_email) > 0) {
            $error = "यह ईमेल पहले से रजिस्टर्ड है!";
        } else {
            // Insert user with all details
            $sql = "INSERT INTO users (name, email, phone, password, gender, date_of_birth, aadhar_card, father_name, address, city, state, pincode, emergency_contact) 
                    VALUES ('$name', '$email', '$phone', '$password', '$gender', '$date_of_birth', '$aadhar_card', '$father_name', '$address', '$city', '$state', '$pincode', '$emergency_contact')";
            
            if(mysqli_query($conn, $sql)) {
                $_SESSION['user_id'] = mysqli_insert_id($conn);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "रजिस्ट्रेशन विफल! " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>रजिस्टर - अंकित होटल</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI', Arial, sans-serif; }
        body { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height:100vh; display:flex; justify-content:center; align-items:center; padding:20px; }
        
        .form-container { 
            background:white; 
            width:100%; 
            max-width:900px; 
            border-radius:20px; 
            overflow:hidden; 
            box-shadow:0 20px 60px rgba(0,0,0,0.3); 
        }
        
        .form-header { 
            background:linear-gradient(135deg, #1a2a3a, #2c3e50); 
            color:white; 
            padding:30px; 
            text-align:center; 
        }
        
        .form-header h1 { font-size:2.5rem; margin-bottom:10px; }
        .form-header p { opacity:0.8; }
        
        .form-body { padding:40px; }
        
        .form-grid { 
            display:grid; 
            grid-template-columns:repeat(2, 1fr); 
            gap:25px; 
        }
        
        .form-group { margin-bottom:20px; }
        .form-group label { display:block; margin-bottom:8px; font-weight:600; color:#333; font-size:14px; }
        .form-group input, .form-group select, .form-group textarea { 
            width:100%; 
            padding:14px; 
            border:2px solid #ddd; 
            border-radius:10px; 
            font-size:16px; 
            transition:all 0.3s; 
            background:#f8f9fa;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { 
            border-color:#764ba2; 
            background:white; 
            outline:none; 
            box-shadow:0 0 0 3px rgba(118, 75, 162, 0.1);
        }
        
        .form-group.full-width { grid-column:1 / -1; }
        
        .gender-options { 
            display:flex; 
            gap:20px; 
            margin-top:10px; 
        }
        
        .gender-option { 
            flex:1; 
            text-align:center; 
            padding:15px; 
            border:2px solid #ddd; 
            border-radius:10px; 
            cursor:pointer; 
            transition:all 0.3s; 
            background:#f8f9fa;
        }
        
        .gender-option:hover { border-color:#764ba2; }
        .gender-option.selected { 
            border-color:#764ba2; 
            background:#764ba2; 
            color:white; 
        }
        
        .submit-btn { 
            grid-column:1 / -1; 
            padding:18px; 
            background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color:white; 
            border:none; 
            border-radius:10px; 
            font-size:18px; 
            font-weight:bold; 
            cursor:pointer; 
            transition:all 0.3s; 
            margin-top:20px; 
        }
        
        .submit-btn:hover { 
            transform:translateY(-2px); 
            box-shadow:0 10px 20px rgba(102, 126, 234, 0.4); 
        }
        
        .login-link { 
            text-align:center; 
            margin-top:30px; 
            color:#666; 
        }
        
        .login-link a { 
            color:#764ba2; 
            text-decoration:none; 
            font-weight:600; 
        }
        
        .error-message { 
            background:#fee; 
            color:#c00; 
            padding:15px; 
            border-radius:10px; 
            margin-bottom:20px; 
            border-left:5px solid #c00; 
            grid-column:1 / -1; 
        }
        
        .form-section { 
            background:#f8f9fa; 
            padding:20px; 
            border-radius:10px; 
            margin-bottom:30px; 
            border-left:5px solid #764ba2; 
        }
        
        .form-section h3 { 
            color:#2c3e50; 
            margin-bottom:20px; 
            display:flex; 
            align-items:center; 
            gap:10px; 
        }
        
        .required::after { 
            content:" *"; 
            color:#e74c3c; 
        }
        
        @media (max-width: 768px) {
            .form-grid { grid-template-columns:1fr; }
            .form-container { margin:10px; }
            .form-body { padding:25px; }
            .gender-options { flex-direction:column; }
        }
    </style>
    <script>
        function selectGender(gender) {
            // Remove selected class from all
            document.querySelectorAll('.gender-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            event.target.classList.add('selected');
            
            // Update hidden input
            document.getElementById('gender').value = gender;
        }
        
        function validateAadhar(aadhar) {
            // Aadhar validation: 12 digits
            const regex = /^\d{12}$/;
            return regex.test(aadhar);
        }
        
        function validateForm() {
            const aadhar = document.getElementById('aadhar_card').value;
            const dob = document.getElementById('date_of_birth').value;
            const gender = document.getElementById('gender').value;
            
            // Validate Aadhar
            if(!validateAadhar(aadhar)) {
                alert('कृपया वैध 12-अंकीय आधार कार्ड नंबर डालें');
                return false;
            }
            
            // Validate Age (must be 18+)
            const today = new Date();
            const birthDate = new Date(dob);
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if(age < 18) {
                alert('कृपया 18 वर्ष से अधिक उम्र के व्यक्ति ही रजिस्टर कर सकते हैं');
                return false;
            }
            
            // Validate gender selection
            if(!gender) {
                alert('कृपया लिंग चुनें');
                return false;
            }
            
            return true;
        }
        
        // Auto-fill address based on pincode (example)
        function autoFillAddress() {
            const pincode = document.getElementById('pincode').value;
            if(pincode.length === 6) {
                // Example mapping (in real app, use API)
                const pincodeData = {
                    '452001': {city: 'इंदौर', state: 'मध्य प्रदेश'},
                    '452002': {city: 'इंदौर', state: 'मध्य प्रदेश'},
                    '452003': {city: 'इंदौर', state: 'मध्य प्रदेश'},
                    '110001': {city: 'नई दिल्ली', state: 'दिल्ली'},
                    '400001': {city: 'मुंबई', state: 'महाराष्ट्र'}
                };
                
                if(pincodeData[pincode]) {
                    document.getElementById('city').value = pincodeData[pincode].city;
                    document.getElementById('state').value = pincodeData[pincode].state;
                }
            }
        }
    </script>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h1>📝 नया अकाउंट बनाएं</h1>
            <p>अंकित होटल में बुकिंग के लिए रजिस्टर करें</p>
        </div>
        
        <div class="form-body">
            <?php if(isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateForm()">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3>👤 व्यक्तिगत जानकारी</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name" class="required">पूरा नाम</label>
                            <input type="text" id="name" name="name" required 
                                   placeholder="जैसे: रमेश कुमार" 
                                   pattern="[A-Za-z\s]{3,50}"
                                   title="केवल अक्षर और स्पेस, 3-50 वर्ण">
                        </div>
                        
                        <div class="form-group">
                            <label for="email" class="required">ईमेल</label>
                            <input type="email" id="email" name="email" required 
                                   placeholder="example@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone" class="required">मोबाइल नंबर</label>
                            <input type="tel" id="phone" name="phone" required 
                                   placeholder="9876543210" 
                                   pattern="[0-9]{10}"
                                   title="10 अंकों का मोबाइल नंबर"
                                   value="7697878985">
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="required">पासवर्ड</label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="कम से कम 6 वर्ण"
                                   minlength="6">
                        </div>
                    </div>
                </div>
                
                <!-- Identity Details Section -->
                <div class="form-section">
                    <h3>🆔 पहचान विवरण</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required">लिंग</label>
                            <input type="hidden" id="gender" name="gender" required>
                            <div class="gender-options">
                                <div class="gender-option" onclick="selectGender('male')">
                                    👨 पुरुष
                                </div>
                                <div class="gender-option" onclick="selectGender('female')">
                                    👩 महिला
                                </div>
                                <div class="gender-option" onclick="selectGender('other')">
                                    ⚧ अन्य
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_of_birth" class="required">जन्म तिथि</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" required 
                                   max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="aadhar_card" class="required">आधार कार्ड नंबर</label>
                            <input type="text" id="aadhar_card" name="aadhar_card" required 
                                   placeholder="12 अंकों का नंबर"
                                   pattern="\d{12}"
                                   title="12 अंकों का आधार कार्ड नंबर">
                        </div>
                        
                        <div class="form-group">
                            <label for="father_name" class="required">पिता का नाम</label>
                            <input type="text" id="father_name" name="father_name" required 
                                   placeholder="पिता का पूरा नाम">
                        </div>
                    </div>
                </div>
                
                <!-- Address Details Section -->
                <div class="form-section">
                    <h3>🏠 पता विवरण</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="address" class="required">पूरा पता</label>
                            <textarea id="address" name="address" rows="3" required 
                                      placeholder="मकान नंबर, स्ट्रीट, इलाका"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="city" class="required">शहर</label>
                            <input type="text" id="city" name="city" required 
                                   placeholder="शहर का नाम">
                        </div>
                        
                        <div class="form-group">
                            <label for="state" class="required">राज्य</label>
                            <input type="text" id="state" name="state" required 
                                   placeholder="राज्य का नाम">
                        </div>
                        
                        <div class="form-group">
                            <label for="pincode" class="required">पिन कोड</label>
                            <input type="text" id="pincode" name="pincode" required 
                                   placeholder="6 अंकों का पिन कोड"
                                   pattern="\d{6}"
                                   onblur="autoFillAddress()">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact" class="required">आपातकालीन संपर्क</label>
                            <input type="tel" id="emergency_contact" name="emergency_contact" required 
                                   placeholder="आपातकालीन मोबाइल नंबर"
                                   pattern="[0-9]{10}">
                        </div>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div style="background:#f0f8ff; padding:20px; border-radius:10px; margin:20px 0; border-left:5px solid #3498db;">
                    <h4 style="color:#2c3e50; margin-bottom:10px;">📜 नियम और शर्तें</h4>
                    <div style="color:#555; font-size:14px;">
                        <p>✅ रजिस्ट्रेशन के लिए 18 वर्ष से अधिक उम्र आवश्यक</p>
                        <p>✅ आधार कार्ड सत्यापन के लिए आवश्यक</p>
                        <p>✅ सभी जानकारी सही और अप-टू-डेट होनी चाहिए</p>
                        <p>✅ बुकिंग के समय मूल दस्तावेज दिखाने होंगे</p>
                        <p style="margin-top:10px; color:#666;">
                            <input type="checkbox" id="terms" required>
                            <label for="terms">मैं उपरोक्त नियमों से सहमत हूँ और सभी जानकारी सही है</label>
                        </p>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    ✅ अकाउंट बनाएं
                </button>
            </form>
            
            <div class="login-link">
                पहले से अकाउंट है? <a href="login.php">लॉगिन करें</a>
            </div>
        </div>
    </div>
    
    <script>
        // Set minimum date (18 years ago)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const minDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            document.getElementById('date_of_birth').max = minDate.toISOString().split('T')[0];
            
            // Auto-fill some demo data for testing
            if(window.location.href.includes('localhost')) {
                document.getElementById('name').value = 'अंकित शर्मा';
                document.getElementById('email').value = 'test@example.com';
                document.getElementById('aadhar_card').value = '123456789012';
                document.getElementById('father_name').value = 'रमेश शर्मा';
                document.getElementById('address').value = 'चिताड़, इंदौर';
                document.getElementById('city').value = 'इंदौर';
                document.getElementById('state').value = 'मध्य प्रदेश';
                document.getElementById('pincode').value = '452001';
                document.getElementById('emergency_contact').value = '9876543210';
                
                // Select male gender by default for testing
                setTimeout(() => {
                    document.querySelector('.gender-option').click();
                }, 100);
            }
        });
    </script>
</body>
</html>