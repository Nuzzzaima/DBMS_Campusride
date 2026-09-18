
<?php

session_start();

// Check if the student is logged in
$isLoggedIn = isset($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusRide - Safe & Affordable UIU Student Commute</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; scroll-behavior: smooth; }
        body { background: #f8fafc; color: #0f172a; line-height: 1.5; }
        
        /* Navbar */
        .navbar { background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 50; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 14px 24px; display: flex; align-items: center; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 12px; text-decoration: none; }
        .brand-icon { width: 40px; height: 40px; border-radius: 12px; background: #7c3aed; color: white; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 16px; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25); }
        .brand-text h2 { font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .brand-text span { font-size: 10px; font-weight: 700; color: #7c3aed; letter-spacing: 0.5px; text-transform: uppercase; }

        .nav-links { display: flex; align-items: center; gap: 24px; list-style: none; }
        .nav-link { color: #475569; text-decoration: none; font-size: 14px; font-weight: 700; transition: color 0.15s; }
        .nav-link:hover { color: #7c3aed; }
        .btn-nav-login { background: #f5f3ff; color: #7c3aed; padding: 8px 18px; border-radius: 10px; text-decoration: none; font-size: 13.5px; font-weight: 700; border: 1px solid #ddd6fe; transition: all 0.15s; }
        .btn-nav-login:hover { background: #7c3aed; color: white; }

        .container { max-width: 1140px; margin: 0 auto; padding: 60px 24px; }

        /* Hero */
        .hero { text-align: center; max-width: 800px; margin: 0 auto 70px; }
        .badge-hero { display: inline-flex; align-items: center; gap: 8px; background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 999px; border: 1px solid #a7f3d0; margin-bottom: 20px; }
        .hero h1 { font-size: 44px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-bottom: 16px; letter-spacing: -0.5px; }
        .hero p { font-size: 17px; color: #64748b; margin-bottom: 30px; font-weight: 500; }
        
        .section-title { text-align: center; margin-bottom: 40px; }
        .section-title h2 { font-size: 30px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .section-title p { font-size: 15px; color: #64748b; }

        /* 3 Box Advantages (About Section) */
        .benefits-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 80px; }
        .benefit-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: transform 0.15s, box-shadow 0.15s; }
        .benefit-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -4px rgba(0,0,0,0.06); }
        .benefit-icon { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 22px; }
        .icon-purple { background: #f3e8ff; color: #7c3aed; }
        .icon-emerald { background: #ecfdf5; color: #059669; }
        .icon-rose { background: #fff1f2; color: #e11d48; }
        .benefit-card h3 { font-size: 20px; font-weight: 800; margin-bottom: 10px; color: #0f172a; }
        .benefit-card p { font-size: 14px; color: #64748b; line-height: 1.6; }

        /* How It Works Steps */
        .steps-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 80px; }
        .step-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px 28px; position: relative; }
        .step-number { width: 38px; height: 38px; border-radius: 12px; background: #7c3aed; color: white; font-weight: 800; font-size: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 18px; box-shadow: 0 4px 10px rgba(124, 58, 237, 0.3); }
        .step-card h3 { font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #0f172a; }
        .step-card p { font-size: 13.5px; color: #64748b; line-height: 1.6; }

        /* Purple Box CTA */
        .purple-cta { background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 100%); border-radius: 28px; padding: 50px 40px; color: white; text-align: center; box-shadow: 0 20px 30px -10px rgba(124, 58, 237, 0.35); max-width: 900px; margin: 0 auto; }
        .purple-cta h2 { font-size: 32px; font-weight: 800; margin-bottom: 10px; }
        .purple-cta p { font-size: 16px; opacity: 0.9; margin-bottom: 30px; }
        .cta-buttons { display: flex; align-items: center; justify-content: center; gap: 16px; }
        .btn-start { background: white; color: #7c3aed; padding: 14px 28px; border-radius: 12px; font-weight: 800; font-size: 15px; text-decoration: none; transition: transform 0.15s, background 0.15s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .btn-start:hover { background: #f8fafc; transform: translateY(-2px); }
        .btn-signin { background: rgba(255, 255, 255, 0.18); color: white; padding: 14px 28px; border-radius: 12px; font-weight: 800; font-size: 15px; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.3); backdrop-filter: blur(4px); transition: background 0.15s; }
        .btn-signin:hover { background: rgba(255, 255, 255, 0.3); }

        footer { text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 13px; border-top: 1px solid #e2e8f0; margin-top: 60px; }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="brand">
                <div class="brand-icon">CR</div>
                <div class="brand-text">
                    <h2>CampusRide</h2>
                    <span>UIU Student Portal</span>
                </div>
            </a>
            <ul class="nav-links">
                <li><a href="#about" class="nav-link">About</a></li>
                <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                <?php if ($isLoggedIn): ?>
                    <li><a href="dashboard.php" class="btn-nav-login" style="background:#7c3aed; color:white;">Go to Dashboard →</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn-nav-login">Sign In</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- HERO -->
        <div class="hero">
            <div class="badge-hero">● UIU Permanent Campus Commute Platform</div>
            <h1>Smart, Safe & Affordable Rides for UIU Students</h1>
            <p>Connect with peers on your route, share bike and car commutes, reduce transportation costs, and reach campus safely and punctually.</p>
        </div>

        <!-- ABOUT SECTION ("Why CampusRide?") -->
        <section id="about" style="scroll-margin-top: 100px;">
            <div class="section-title">
                <h2>Why CampusRide?</h2>
                <p>Everything you need for safe, affordable campus commuting</p>
            </div>

            <!-- 3 Box Advantages -->
            <div class="benefits-grid">
                <!-- Advantage 1: Affordable -->
                <div class="benefit-card">
                    <div class="benefit-icon icon-emerald">৳</div>
                    <h3>Affordable</h3>
                    <p>Split ride costs with fellow students and save up to 60% on your daily commute compared to solo rickshaws or CNGs.</p>
                </div>

                <!-- Advantage 2: Convenient -->
                <div class="benefit-card">
                    <div class="benefit-icon icon-purple">⚡</div>
                    <h3>Convenient</h3>
                    <p>Find rides in seconds. Filter by route, time, gender, and ride type — your way.</p>
                </div>

                <!-- Advantage 3: Safe -->
                <div class="benefit-card">
                    <div class="benefit-icon icon-rose">🛡️</div>
                    <h3>Safe</h3>
                    <p>Verified student profiles, in-app SOS, and women-only ride options for peace of mind.</p>
                </div>
            </div>
        </section>

        <!-- HOW IT WORKS SECTION -->
        <section id="how-it-works" style="scroll-margin-top: 100px;">
            <div class="section-title">
                <h2>How It Works</h2>
                <p>Three simple steps to start sharing commutes across Dhaka</p>
            </div>

            <div class="steps-grid">
                <!-- Step 1 -->
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Create Account</h3>
                    <p>Register with your university email and verify your student identity.</p>
                </div>

                <!-- Step 2 -->
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Find or Offer a Ride</h3>
                    <p>Search for available rides on your route, or post your own for others to join.</p>
                </div>

                <!-- Step 3 -->
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Ride & Save</h3>
                    <p>Connect, travel together, and split the cost. Rate each other after the trip.</p>
                </div>
            </div>
        </section>

        <!-- PURPLE BOX CALLOUT -->
        <div class="purple-cta">
            <h2>Ready to Ride Smarter?</h2>
            <p>Join 1,200+ UIU students already sharing rides and saving money.</p>
            <div class="cta-buttons">
                <!-- Option 1: Register Student Account Page -->
                <a href="register.php" class="btn-start">Get Started for Free</a>
                <!-- Option 2: Login Page -->
                <a href="login.php" class="btn-signin">Sign In</a>
            </div>
        </div>
    </div>

    <footer>
        United International University • CampusRide System • United City, Madani Avenue, Badda, Dhaka 1212
    </footer>

</body>
</html>