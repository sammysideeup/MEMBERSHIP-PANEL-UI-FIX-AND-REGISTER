<?php
session_start();
// NOTE: Assuming connection.php and the database schema are correct.
include "connection.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    // 💡 UPDATED: Capture First Name and Last Name separately
    $firstname = trim($_POST["firstname"]);
    $lastname = trim($_POST["lastname"]);
    $fullname = $firstname . " " . $lastname; // Combine for database storage
    
    $email = trim($_POST["email"]);
    $password = $_POST['password'];

    // --- Server-side Password Validation Check ---
    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $password)) {
        $_SESSION['error_message'] = 'Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, and one number.';
        header("Location: Register.php");
        exit();
    }

    // Collect additional form data
    $age = intval($_POST['age']);
    $gender = $_POST['gender'];
    $focus = $_POST['focus'];
    $cp_no = trim($_POST['cp_no']);
    $goal = $_POST['goal'];
    $activity = $_POST['activity'];
    $training_days = isset($_POST['training_days']) ? implode(", ", $_POST['training_days']) : '';
    $weight_kg = floatval($_POST['weight']);
    $height_cm = floatval($_POST['height']);

    // --- Server-side Contact Number Validation Check ---
    if (!preg_match("/^\d{11}$/", $cp_no)) {
        $_SESSION['error_message'] = 'Contact number must be exactly 11 digits.';
        header("Location: Register.php");
        exit();
    }

    // Compute BMI (height in meters)
    $bmi = ($height_cm > 0) ? round($weight_kg / (($height_cm / 100) ** 2), 2) : 0;

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Check for existing email
    $checkStmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        $_SESSION['error_message'] = 'This email is already registered.';
        header("Location: Register.php");
        exit();
    }
    $checkStmt->close();

    // Insert new user
    // The database column name is still 'fullname', so we pass the combined name.
    $stmt = $conn->prepare("
        INSERT INTO users (
            fullname, email, password,
            age, gender, focus, goal,
            activity, training_days, 
            weight_kg, height_cm, bmi, cp_no
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssisssssddds",  // 13 types: s=string, i=int, d=double
        $fullname, // Pass the combined name
        $email,
        $hashedPassword,
        $age,
        $gender,
        $focus,
        $goal,
        $activity,
        $training_days,
        $weight_kg,
        $height_cm,
        $bmi,
        $cp_no
    );


    if ($stmt->execute()) {
        // ✅ Set session for logged-in client
        $client_id = $stmt->insert_id;
        $_SESSION['client_id'] = $client_id;
        $_SESSION['client_name'] = $fullname;

        $stmt->close();
        $conn->close();

        $_SESSION['success_message'] = 'Registration successful!';
        header("Location: MembershipPayment.php");
        exit();
    } else {
        $errorMsg = 'Error during registration: ' . $stmt->error;
        $stmt->close();
        $conn->close();
        $_SESSION['error_message'] = $errorMsg;
        header("Location: Register.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Member Registration</title>
  <link rel="stylesheet" href="Registerstyle.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>

<body>
<div class="wrapper">
  <form method="POST" id="registerForm">
    <a href="Nonmember.php" class="back-icon"><i class='bx bx-arrow-back'></i></a>
    <h1>Membership Form</h1>
    <p> Want to become a member? Please fill out the form to complete the registration.</p>

    <!-- Error/Warning Messages -->
    <div id="passwordWarning" class="alert-box error-alert hidden">
        Password must be at least 8 characters long and include one uppercase letter, one lowercase letter, and one number.
    </div>
    
    <div id="contactWarning" class="alert-box error-alert hidden">
        Contact number must be exactly 11 digits.
    </div>

    <div id="step1">
      <!-- 💡 UPDATED: Separated Full Name into First Name and Last Name -->
      <div class="input-box">
        <input type="text" name="firstname" id="firstname" placeholder="First Name" required value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : ''; ?>" />
        <i class='bx bxs-user'></i>
      </div>
      <div class="input-box">
        <input type="text" name="lastname" id="lastname" placeholder="Last Name" required value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : ''; ?>" />
        <i class='bx bxs-user'></i>
      </div>

      <div class="input-box" id="contactBox">
     <input type="tel" name="cp_no" id="cp_no" placeholder="Contact Number (11 digits)" required 
            value="<?php echo isset($cp_no) ? htmlspecialchars($cp_no) : ''; ?>"
            maxlength="11"
            pattern="\d{11}"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);" /> 
        <i class='bx bxs-phone'></i>
      </div>

      <div class="input-box">
        <input type="email" name="email" id="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />
        <i class='bx bxs-envelope'></i>
      </div>

      <!-- Password and Confirm Password fields -->
      <div class="input-box" id="passwordBox"> 
        <input type="password" name="password" id="password" placeholder="Password" required />
        <i class='bx bx-show' id="togglePassword" style="cursor: pointer;"></i>
      </div>

      <div class="input-box" id="repasswordBox">
        <input type="password" id="repassword" placeholder="Confirm Password" required />
        <i class='bx bx-show' id="tgPassword" style="cursor: pointer;"></i>
      </div>

      <div class="input-box">
        <input type="text" id="verification_code" placeholder="Enter verification code" required />
        <i class='bx bxs-check-shield'></i>
      </div>

      <button type="button" class="btn" onclick="sendVerification()">Send Verification Code</button>
      <button type="button" class="btn" onclick="showStep2()">Next</button>
    </div>

  <div id="step2" class="hidden">
  <div class="form-card">
    <div class="group-label">Gender:</div>
    <div class="options">
      <span><input type="radio" name="gender" value="Male" required> Male</span>
      <span><input type="radio" name="gender" value="Female" required> Female</span>
    </div>
  </div>

  <div class="form-card">
    <div class="group-label">Select Focus Area:</div>
    <div class="options">
      <span><input type="radio" name="focus" value="Arms" required> Arms</span>
      <span><input type="radio" name="focus" value="Chest" required> Chest</span>
      <span><input type="radio" name="focus" value="Legs" required> Legs</span>
      <span><input type="radio" name="focus" value="Full Body" required> Full Body</span>
    </div>
  </div>

  <div class="form-card">
    <div class="group-label">Main Goal:</div>
    <div class="options">
      <span><input type="radio" name="goal" value="Lose Weight" required> Lose Weight</span>
      <span><input type="radio" name="goal" value="Gain Muscle" required> Gain Muscle</span>
      <span><input type="radio" name="goal" value="Stay Fit" required> Stay Fit</span>
    </div>
  </div>

  <div class="form-card">
    <div class="group-label">Activity Level:</div>
    <div class="options">
      <span><input type="radio" name="activity" value="Low" required> Low</span>
      <span><input type="radio" name="activity" value="Moderate" required> Moderate</span>
      <span><input type="radio" name="activity" value="High" required> High</span>
    </div>
  </div>

  <div class="form-card">
    <div class="group-label">Training Days per Week:</div>
    <div class="options">
      <span><input type="checkbox" name="training_days[]" value="Monday"> Monday</span>
      <span><input type="checkbox" name="training_days[]" value="Tuesday"> Tuesday</span>
      <span><input type="checkbox" name="training_days[]" value="Wednesday"> Wednesday</span>
      <span><input type="checkbox" name="training_days[]" value="Thursday"> Thursday</span>
      <span><input type="checkbox" name="training_days[]" value="Friday"> Friday</span>
      <span><input type="checkbox" name="training_days[]" value="Saturday"> Saturday</span>
      <span><input type="checkbox" name="training_days[]" value="Sunday"> Sunday</span>
    </div>
  </div>

  <div class="form-card">
    <div class="group-label">BMI Info:</div>
    <div class="options">
      <span><input type="number" name="age" placeholder="Age" required></span>
      <span><input type="number" name="weight" placeholder="Weight (kg)" step="0.1" required></span>
      <span><input type="number" name="height" placeholder="Height (cm)" step="0.1" required></span>
    </div>
  </div>

  <button type="submit" class="btn" name="register">Select Membership Plan</button>
</div>

<div class="register-link">
  <p>Already a member? <a href="Loginpage.php">Login here</a></p>
</div>
</form>
</div>

<!-- ✅ Toast Container -->
<div id="toast-alert" class="toast-alert" tabindex="0" style="display:none;">
  <div id="toast-message"></div>
  <button id="toast-close">&times;</button>
</div>

<script>
function sendVerification() {
  const email = document.getElementById("email").value.trim();
  if (!email) {
    showToast("Please enter your email first.");
    return;
  }

  // Show loading state
  showToast("Sending verification code...", "info");
  
  fetch("send_verification.php", {
    method: "POST",
    headers: { 
      "Content-Type": "application/x-www-form-urlencoded",
      "Accept": "text/plain"
    },
    body: "email=" + encodeURIComponent(email)
  })
  .then(response => response.text())
  .then(result => {
    if (result.trim() === "sent") {
      showToast("Verification code sent.", "success");
    } else if (result.trim() === "failed") {
      showToast("Failed to send verification code. Try again.");
    } else if (result.trim() === "no_email") {
      showToast("Please enter a valid email address.");
    } else {
      showToast("Failed to send verification code. Try again.");
    }
  })
  .catch(() => showToast("Failed to send verification code. Try again."));
}


function showStep2() {
  // 💡 UPDATED: Check both first and last name
  const firstname = document.getElementById("firstname").value.trim();
  const lastname = document.getElementById("lastname").value.trim();
  const cp_no = document.getElementById("cp_no").value.trim(); 
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  const repassword = document.getElementById("repassword").value;
  const enteredCode = document.getElementById("verification_code").value.trim();
  
  // Warnings and boxes
  const passwordWarning = document.getElementById("passwordWarning");
  const contactWarning = document.getElementById("contactWarning"); 
  const passwordBox = document.getElementById("passwordBox");
  const repasswordBox = document.getElementById("repasswordBox");
  const contactBox = document.getElementById("contactBox"); 

  // 1. Hide all warnings and clear previous errors
  passwordWarning.classList.add("hidden"); 
  contactWarning.classList.add("hidden"); 
  passwordBox.classList.remove("input-error");
  repasswordBox.classList.remove("input-error");
  contactBox.classList.remove("input-error"); 

  // 2. Check for required fields (including new first/last name)
  if (!firstname || !lastname || !cp_no || !email || !password || !repassword || !enteredCode) {
    showToast("Please complete all fields.");
    return;
  }
  
  // 3. Contact Number Validation
  if (cp_no.length !== 11 || !/^\d{11}$/.test(cp_no)) {
    contactWarning.classList.remove("hidden");
    contactBox.classList.add("input-error");
    showToast("Contact number must be exactly 11 digits.");
    
    setTimeout(() => {
        contactWarning.classList.add("hidden"); 
        contactBox.classList.remove("input-error");
    }, 4500);
    return;
  }

  // 4. Check Password Match
  if (password !== repassword) {
    showToast("Passwords do not match.");
    passwordBox.classList.add("input-error"); 
    repasswordBox.classList.add("input-error");
    return;
  }

  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/;

  // 5. Check Password Strength
  if (!passwordPattern.test(password)) {
    passwordWarning.classList.remove("hidden"); 
    passwordBox.classList.add("input-error");
    repasswordBox.classList.add("input-error");

    setTimeout(() => {
        passwordWarning.classList.add("hidden"); 
        passwordBox.classList.remove("input-error");
        repasswordBox.classList.remove("input-error");
    }, 4500); 

    return;
  }
  
  // If all client-side checks pass, proceed to verification
  
  // Show loading state
  showToast("Verifying code...", "info");
  
  fetch("verify_code.php", {
    method: "POST",
    headers: { 
      "Content-Type": "application/x-www-form-urlencoded",
      "Accept": "text/plain"
    },
    body: "code=" + encodeURIComponent(enteredCode)
  })
  .then(res => res.text())
  .then(data => {
    console.log("Verification response:", data); // Debug log
    if (data.trim() === "verified") {
      document.getElementById("step1").classList.add("hidden");
      document.getElementById("step2").classList.remove("hidden");
      showToast("Verification successful!", "success");
    } else if (data.trim() === "expired") {
      showToast("Verification code has expired. Please request a new one.");
    } else if (data.trim() === "invalid") {
      showToast("Invalid verification code. Please try again.");
    } else if (data.trim() === "no_code") {
      showToast("No verification code found. Please send a new code.");
    } else if (data.trim() === "no_post") {
      showToast("Verification failed. Please try again.");
    } else {
      showToast("Verification failed. Please try again.");
    }
  })
  .catch((error) => {
    console.error("Verification error:", error);
    showToast("Verification failed. Try again.");
  });
}

// Password Toggles
document.getElementById("togglePassword").addEventListener("click", function() {
  const passwordInput = document.getElementById("password");
  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  this.classList.toggle("bx-show");
  this.classList.toggle("bx-hide");
});

document.getElementById("tgPassword").addEventListener("click", function() {
  const passwordInput = document.getElementById("repassword");
  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
  passwordInput.setAttribute("type", type);
  this.classList.toggle("bx-show");
  this.classList.toggle("bx-hide");
});
</script>

<!-- ✅ Toast Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toast = document.getElementById('toast-alert');
  const messageEl = document.getElementById('toast-message');
  const closeBtn = document.getElementById('toast-close');

  window.showToast = function(message, type = 'error') {
    if (!toast || !messageEl || !message) return;
    messageEl.textContent = message;
    toast.className = `toast-alert ${type}`;
    toast.style.display = 'flex';
    setTimeout(() => toast.classList.add('show'), 10);

    const autoHideTimer = setTimeout(() => hideToast(), 5000);
    function hideToast() {
      toast.classList.remove('show');
      setTimeout(() => {
        toast.style.display = 'none';
        clearTimeout(autoHideTimer);
      }, 300);
    }

    closeBtn.onclick = hideToast;
    toast.onclick = (e) => { if (e.target === toast) hideToast(); };
  };

  <?php if (isset($_SESSION['error_message'])): ?>
    showToast('<?php echo str_replace("'", "\'", $_SESSION['error_message']); ?>');
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['success_message'])): ?>
    showToast('<?php echo str_replace("'", "\'", $_SESSION['success_message']); ?>', 'success');
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>
});
</script>

</body>
</html>