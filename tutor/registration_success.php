<?php
session_start();
$page_title = 'Registration Success';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?> – TutorPK</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../style.css">
    <style>
        .success-container {
            max-width: 500px;
            margin: 80px auto;
            text-align: center;
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            border-top: 6px solid #22c55e;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: inline-block;
            background: #dcfce7;
            width: 90px;
            height: 90px;
            line-height: 90px;
            border-radius: 50%;
            color: #16a34a;
        }
        .success-container h1 {
            color: #111827;
            font-size: 1.8rem;
            margin-bottom: 16px;
            font-weight: 800;
        }
        .success-container p {
            color: #4b5563;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .highlight {
            font-weight: 700;
            color: #1f2937;
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .home-btn {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: background 0.2s, transform 0.2s;
        }
        .home-btn:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="auth-body">

<div class="success-container">
    <div class="success-icon">✓</div>
    <h1>Application Received!</h1>
    <p>
        Thanks for applying to teach at <strong>TutorPK</strong>! We have received your application.
    </p>
    <p>
        Our admin team will review your profile and CV. This process usually takes <span class="highlight">at most 24 hours</span>. 
        Once approved, you will be able to log in and start teaching students all over Pakistan! 🚀
    </p>
    
    <div style="margin-top: 32px;">
        <a href="../index.php" class="home-btn">Return to Home</a>
    </div>
</div>

</body>
</html>
