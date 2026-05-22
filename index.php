<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>TutorPK — Learning for All</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&family=Inter:wght@400;500&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --green-dark: #1a6b2f;
    --green-mid: #228B40;
    --green-light: #2ecc6e;
    --green-pale: #d4f5e2;
    --yellow: #f5c518;
    --yellow-dark: #d4a80a;
    --yellow-light: #fff4b8;
    --bg: #f8fdf9;
    --text: #0e2a1a;
    --text-muted: #4a7a5a;
    --white: #ffffff;
    --shadow: 0 8px 32px rgba(34,139,64,0.13);
  }

  html { scroll-behavior: smooth; }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    overflow-x: hidden;
  }

  /* ── NAVBAR ── */
  nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 5vw;
    height: 70px;
    /* background: rgba(255,255,255,0.95); */
    background-color: #FFFFFF;
    backdrop-filter: blur(12px);
    border-bottom: 2px solid var(--green-pale);
    box-shadow: 0 2px 16px rgba(34,139,64,0.08);
  }
  .nav-logo img { height: 72px; object-fit: contain; }
  .nav-links { display: flex; gap: 28px; list-style: none; }
  .nav-links a {
    text-decoration: none; font-family: 'Poppins', sans-serif;
    font-weight: 600; font-size: 0.9rem; color: var(--green-dark);
    transition: color 0.2s;
  }
  .nav-links a:hover { color: var(--yellow-dark); }
  .nav-cta {
    display: flex; gap: 10px;
  }
  .btn {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 9px 22px; border-radius: 50px; font-family: 'Poppins', sans-serif;
    font-weight: 600; font-size: 0.88rem; text-decoration: none;
    cursor: pointer; transition: all 0.2s; border: 2px solid transparent;
  }
  .btn-primary {
    background: linear-gradient(135deg, var(--green-mid), var(--green-dark));
    color: #fff;
    box-shadow: 0 4px 16px rgba(34,139,64,0.35);
  }
  .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(34,139,64,0.45); }
  .btn-yellow {
    background: var(--yellow); color: var(--green-dark);
    box-shadow: 0 4px 16px rgba(245,197,24,0.4);
  }
  .btn-yellow:hover { transform: translateY(-2px); background: var(--yellow-dark); color: #fff; }
  .btn-outline {
    background: transparent; color: var(--green-dark);
    border-color: var(--green-mid);
  }
  .btn-outline:hover { background: var(--green-pale); }

  /* ── HERO ── */
  .hero {
    min-height: 78vh;
    display: flex; align-items: center;
    padding: 90px 5vw 40px;
    background:
      radial-gradient(ellipse 70% 60% at 80% 30%, rgba(245,197,24,0.12) 0%, transparent 70%),
      radial-gradient(ellipse 60% 80% at 10% 70%, rgba(34,139,64,0.1) 0%, transparent 65%),
      linear-gradient(160deg, #f8fdf9 0%, #e8f9ee 50%, #fff9d6 100%);
    position: relative; overflow: hidden;
  }
  .hero::before {
    content: '';
    position: absolute; top: -80px; right: -120px;
    width: 600px; height: 600px; border-radius: 50%;
    background: radial-gradient(circle, rgba(34,139,64,0.07) 0%, transparent 70%);
    pointer-events: none;
  }
  .hero-inner {
    max-width: 1200px; margin: 0 auto; width: 100%;
    display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center;
  }
  .hero-badge {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--yellow-light); border: 1.5px solid var(--yellow);
    border-radius: 50px; padding: 5px 16px; font-size: 0.8rem;
    font-weight: 600; color: var(--green-dark); margin-bottom: 20px;
  }
  .hero-badge span { width: 8px; height: 8px; border-radius: 50%; background: var(--yellow-dark); display: block; }
  h1 {
    font-family: 'Poppins', sans-serif;
    font-size: clamp(2.2rem, 3.5vw, 3.4rem);
    font-weight: 800; line-height: 1.15; letter-spacing: -0.03em;
    color: var(--text); margin-bottom: 18px;
  }
  h1 .accent-green { color: var(--green-mid); }
  h1 .accent-yellow { color: var(--yellow-dark); }
  .hero-sub {
    font-size: 1.05rem; line-height: 1.7; color: var(--text-muted);
    max-width: 480px; margin-bottom: 22px;
  }
  .hero-btns { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 24px; }
  .hero-stats {
    display: flex; gap: 32px;
  }
  .stat { display: flex; flex-direction: column; }
  .stat-num {
    font-family: 'Poppins', sans-serif; font-weight: 800;
    font-size: 1.6rem; color: var(--green-dark);
  }
  .stat-label { font-size: 0.78rem; color: var(--text-muted); font-weight: 500; }

  /* Hero Right */
  .hero-right { position: relative; display: flex; justify-content: center; }
  .hero-visual {
    width: 100%; max-width: 460px;
    background: var(--white);
    border-radius: 24px;
    padding: 32px;
    border: 2px solid var(--green-pale);
    box-shadow: var(--shadow), 0 0 0 8px rgba(34,139,64,0.05);
    position: relative;
  }
  .hero-visual::before {
    content: '';
    position: absolute; inset: -12px; border-radius: 32px;
    background: linear-gradient(135deg, rgba(34,139,64,0.08), rgba(245,197,24,0.08));
    z-index: -1;
  }
  .visual-logo { text-align: center; margin-bottom: 24px; }
  .visual-logo img { height: 80px; }
  .visual-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  .vcard {
    background: linear-gradient(135deg, var(--green-pale), #eaffef);
    border-radius: 14px; padding: 16px 14px;
    border: 1.5px solid rgba(34,139,64,0.15);
  }
  .vcard-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--green-mid); display: flex; align-items: center;
    justify-content: center; margin-bottom: 10px; font-size: 18px;
  }
  .vcard-title { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.82rem; color: var(--green-dark); margin-bottom: 3px; }
  .vcard-sub { font-size: 0.73rem; color: var(--text-muted); }
  .floating-badge {
    position: absolute; top: -16px; right: -16px;
    background: var(--yellow); color: var(--green-dark);
    border-radius: 50px; padding: 6px 14px;
    font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.78rem;
    box-shadow: 0 4px 14px rgba(245,197,24,0.5);
    animation: float 3s ease-in-out infinite;
  }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-7px)} }

  /* ── SECTIONS ── */
  section { padding: 80px 5vw; }
  .section-inner { max-width: 1200px; margin: 0 auto; }
  .section-label {
    display: inline-block; font-size: 0.78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.12em;
    color: var(--green-mid); background: var(--green-pale);
    padding: 4px 14px; border-radius: 50px; margin-bottom: 12px;
  }
  h2 {
    font-family: 'Poppins', sans-serif; font-weight: 800;
    font-size: clamp(1.8rem, 2.8vw, 2.5rem); line-height: 1.2;
    letter-spacing: -0.03em; margin-bottom: 14px;
  }
  .section-sub { font-size: 1rem; color: var(--text-muted); max-width: 520px; line-height: 1.7; margin-bottom: 48px; }

  /* ── HOW IT WORKS ── */
  .how-bg { background: linear-gradient(160deg, #f0fbf4 0%, #fffbe8 100%); }
  .steps-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap: 24px; }
  .step-card {
    background: var(--white); border-radius: 20px;
    padding: 28px 24px; border: 2px solid var(--green-pale);
    position: relative; transition: transform 0.2s, box-shadow 0.2s;
  }
  .step-card:hover { transform: translateY(-4px); box-shadow: var(--shadow); }
  .step-num {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, var(--green-mid), var(--green-dark));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.1rem;
    margin-bottom: 16px;
  }
  .step-title { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1rem; color: var(--text); margin-bottom: 8px; }
  .step-desc { font-size: 0.85rem; color: var(--text-muted); line-height: 1.6; }
  .step-connector {
    position: absolute; top: 42px; right: -16px;
    width: 32px; height: 2px; background: var(--yellow); z-index: 1;
  }

  /* ── CARDS (Student/Tutor) ── */
  .register-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; }
  .reg-card {
    border-radius: 24px; padding: 36px 32px;
    border: 2px solid transparent; position: relative; overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .reg-card:hover { transform: translateY(-5px); }
  .reg-card.student {
    background: linear-gradient(135deg, #e8f9ee 0%, #f5fff8 100%);
    border-color: rgba(34,139,64,0.2);
    box-shadow: 0 12px 40px rgba(34,139,64,0.12);
  }
  .reg-card.tutor {
    background: linear-gradient(135deg, #fffbe6 0%, #fff8d0 100%);
    border-color: rgba(245,197,24,0.3);
    box-shadow: 0 12px 40px rgba(245,197,24,0.18);
  }
  .reg-icon {
    width: 56px; height: 56px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; margin-bottom: 20px;
  }
  .student .reg-icon { background: linear-gradient(135deg, var(--green-mid), var(--green-dark)); }
  .tutor .reg-icon { background: linear-gradient(135deg, var(--yellow), var(--yellow-dark)); }
  .reg-title { font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.35rem; color: var(--text); margin-bottom: 10px; }
  .reg-desc { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 20px; }
  .reg-features { list-style: none; margin-bottom: 28px; display: flex; flex-direction: column; gap: 8px; }
  .reg-features li {
    display: flex; align-items: center; gap: 10px;
    font-size: 0.85rem; color: var(--text);
  }
  .reg-features li::before {
    content: '✓'; width: 20px; height: 20px; border-radius: 50%;
    background: var(--green-light); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 800; flex-shrink: 0;
  }
  .tutor .reg-features li::before { background: var(--yellow-dark); }

  /* ── FEATURES ── */
  .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 20px; }
  .feat-card {
    background: var(--white); border-radius: 18px; padding: 24px;
    border: 1.5px solid var(--green-pale); transition: all 0.2s;
  }
  .feat-card:hover { border-color: var(--green-light); box-shadow: 0 6px 24px rgba(34,139,64,0.1); transform: translateY(-3px); }
  .feat-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; margin-bottom: 14px;
    background: linear-gradient(135deg, var(--green-pale), #c8f5d8);
  }
  .feat-title { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.95rem; color: var(--text); margin-bottom: 7px; }
  .feat-desc { font-size: 0.82rem; color: var(--text-muted); line-height: 1.6; }

  /* ── TESTIMONIALS ── */
  .testi-bg { background: linear-gradient(160deg, #1a6b2f 0%, #228B40 50%, #2a7a20 100%); }
  .testi-bg h2, .testi-bg .section-label { color: #fff; }
  .testi-bg .section-sub { color: rgba(255,255,255,0.75); }
  .testi-bg .section-label { background: rgba(255,255,255,0.15); }
  .testi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 20px; }
  .testi-card {
    background: rgba(255,255,255,0.1); border-radius: 18px;
    padding: 24px; border: 1.5px solid rgba(255,255,255,0.2);
    backdrop-filter: blur(8px);
  }
  .testi-stars { color: var(--yellow); font-size: 0.9rem; margin-bottom: 12px; }
  .testi-text { font-size: 0.88rem; color: rgba(255,255,255,0.9); line-height: 1.65; margin-bottom: 16px; font-style: italic; }
  .testi-author { display: flex; align-items: center; gap: 10px; }
  .testi-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: var(--yellow); display: flex; align-items: center;
    justify-content: center; font-family: 'Poppins', sans-serif;
    font-weight: 700; font-size: 0.85rem; color: var(--green-dark);
  }
  .testi-name { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.85rem; color: #fff; }
  .testi-role { font-size: 0.75rem; color: rgba(255,255,255,0.6); }

  /* ── CTA BANNER ── */
  .cta-section {
    background: linear-gradient(135deg, var(--yellow-light) 0%, var(--green-pale) 100%);
    border-top: 2px solid var(--yellow); border-bottom: 2px solid var(--green-pale);
    text-align: center; padding: 72px 5vw;
  }
  .cta-section h2 { margin-bottom: 12px; }
  .cta-section p { color: var(--text-muted); margin-bottom: 32px; font-size: 1rem; }
  .cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }

  /* ── FOOTER ── */
  footer {
    background: var(--green-dark);
    color: rgba(255,255,255,0.85);
    padding: 48px 5vw 28px;
  }
  .footer-inner {
    max-width: 1200px; margin: 0 auto;
    display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 40px;
    padding-bottom: 36px; border-bottom: 1px solid rgba(255,255,255,0.12);
  }
  .footer-logo img { height: 52px; filter: brightness(0) invert(1); margin-bottom: 14px; }
  .footer-about { font-size: 0.83rem; line-height: 1.7; color: rgba(255,255,255,0.65); max-width: 260px; }
  .footer-col h4 { font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 0.88rem; color: var(--yellow); margin-bottom: 14px; }
  .footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 8px; }
  .footer-col a { font-size: 0.82rem; color: rgba(255,255,255,0.65); text-decoration: none; transition: color 0.2s; }
  .footer-col a:hover { color: var(--yellow); }
  .footer-bottom {
    max-width: 1200px; margin: 20px auto 0;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 0.78rem; color: rgba(255,255,255,0.45);
  }
  .footer-bottom strong { color: var(--yellow); }

  /* ── ANIMATIONS ── */
  @keyframes fadeInUp { from { opacity: 0; transform: translateY(28px); } to { opacity: 1; transform: translateY(0); } }
  .hero-left > * { animation: fadeInUp 0.7s ease both; }
  .hero-left > *:nth-child(1) { animation-delay: 0.05s; }
  .hero-left > *:nth-child(2) { animation-delay: 0.15s; }
  .hero-left > *:nth-child(3) { animation-delay: 0.25s; }
  .hero-left > *:nth-child(4) { animation-delay: 0.35s; }
  .hero-left > *:nth-child(5) { animation-delay: 0.45s; }
  .hero-right { animation: fadeInUp 0.8s 0.3s ease both; }

  /* ── RESPONSIVE ── */
  @media (max-width: 900px) {
    .hero-inner { grid-template-columns: 1fr; gap: 40px; }
    .hero-right { display: none; }
    .register-grid { grid-template-columns: 1fr; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 600px) {
    nav { padding: 0 4vw; }
    .nav-links { display: none; }
    h1 { font-size: 2rem; }
    .hero-stats { gap: 20px; }
    .footer-inner { grid-template-columns: 1fr; }
    .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
  }

  /* ── FLOATING RIGHT SIDEBAR ── */
  .side-panel {
    position: absolute; right: -20px; top: 40px;
    z-index: 90; display: flex; flex-direction: column; gap: 14px;
    align-items: flex-end;
  }
  .side-pill {
    display: flex; align-items: center; gap: 10px;
    background: #fff; border-radius: 50px;
    padding: 9px 14px 9px 10px;
    border: 2px solid var(--green-pale);
    box-shadow: 0 6px 24px rgba(34,139,64,0.14);
    font-family: 'Poppins', sans-serif; font-size: 0.78rem; font-weight: 600;
    color: var(--green-dark); white-space: nowrap;
    cursor: default; transition: transform 0.2s, box-shadow 0.2s;
  }
  .side-pill:hover { transform: translateX(-4px); box-shadow: 0 10px 30px rgba(34,139,64,0.22); }
  .side-pill-icon {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;
  }
  .side-pill.green .side-pill-icon { background: linear-gradient(135deg, var(--green-mid), var(--green-dark)); }
  .side-pill.yellow .side-pill-icon { background: linear-gradient(135deg, var(--yellow), var(--yellow-dark)); }
  .side-pill.teal .side-pill-icon { background: linear-gradient(135deg, #14b8a6, #0d9488); }
  .side-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green-light); margin-left: 4px; animation: pulse-dot 2s ease-in-out infinite; }
  .side-pill.yellow .side-dot { background: var(--yellow-dark); animation-delay: 0.5s; }
  .side-pill.teal .side-dot { background: #14b8a6; animation-delay: 1s; }
  @keyframes pulse-dot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.4;transform:scale(0.6)} }

  /* individual slide-in animations */
  .side-pill:nth-child(1) { animation: slideInRight 0.6s 0.8s ease both, bobUp 4s 1.4s ease-in-out infinite; }
  .side-pill:nth-child(2) { animation: slideInRight 0.6s 1.0s ease both, bobUp 4s 1.8s ease-in-out infinite; }
  .side-pill:nth-child(3) { animation: slideInRight 0.6s 1.2s ease both, bobDown 4s 2.2s ease-in-out infinite; }
  .side-pill:nth-child(4) { animation: slideInRight 0.6s 1.4s ease both, bobUp 5s 2.0s ease-in-out infinite; }
  .side-pill:nth-child(5) { animation: slideInRight 0.6s 1.6s ease both, bobDown 5s 1.5s ease-in-out infinite; }
  @keyframes slideInRight { from { opacity:0; transform: translateX(60px); } to { opacity:1; transform: translateX(0); } }
  @keyframes bobUp { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
  @keyframes bobDown { 0%,100%{transform:translateY(0)} 50%{transform:translateY(6px)} }

  /* live counter tick */
  .live-count { font-size: 1rem; font-weight: 800; color: var(--green-dark); min-width: 36px; text-align: right; }

  /* notification pop */
  .notif-pop {
    position: fixed; right: 18px; bottom: 30px; z-index: 91;
    background: #fff; border-radius: 16px; padding: 12px 16px;
    border: 2px solid var(--yellow); box-shadow: 0 8px 28px rgba(245,197,24,0.25);
    display: flex; align-items: center; gap: 10px; max-width: 220px;
    animation: popIn 0.5s 2.5s cubic-bezier(.34,1.56,.64,1) both;
    font-size: 0.78rem; font-family: 'Poppins', sans-serif; color: var(--text);
  }
  .notif-pop strong { color: var(--green-dark); display: block; font-size: 0.8rem; }
  .notif-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--green-light); flex-shrink: 0; animation: pulse-dot 1.5s ease-in-out infinite; }
  @keyframes popIn { from{opacity:0;transform:translateY(20px) scale(0.85)} to{opacity:1;transform:translateY(0) scale(1)} }

  @media (max-width: 1100px) { .side-panel { display: none; } .notif-pop { display: none; } }
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="nav-logo">
    <img src="assets/index_nav.png" alt="TutorPK">
  </div>
  <ul class="nav-links">
    <li><a href="#how">How It Works</a></li>
    <li><a href="#register">Register</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#testimonials">Reviews</a></li>
  </ul>
  <div class="nav-cta">
    <a href="login.php" class="btn btn-outline">Log In</a>
    <a href="#register" class="btn btn-primary">Get Started</a>
  </div>
</nav>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TutorPk connects students with verified tutors across Pakistan. Book classes, manage schedules, and learn smarter — all in one place.">
    <title>TutorPk — Learning for All</title>
    <style>
        @import "tailwindcss" source(none);
        @source "../src";
        @import "tw-animate-css";

        @custom-variant dark (&:is(.dark *));

        /*
         * Design system definition.
         *
         * The @theme inline block maps CSS custom properties to Tailwind utility
         * classes (e.g. --color-primary -> bg-primary, text-primary).
         *
         * The :root and .dark blocks define the actual color values using oklch.
         * All colors MUST use oklch format.
         *
         * To add a new semantic color:
         * 1. Add the variable to :root (light value) and .dark (dark value)
         * 2. Register it in @theme inline as --color-<name>: var(--<name>)
         */

        @theme inline {
          --radius-sm: calc(var(--radius) - 4px);
          --radius-md: calc(var(--radius) - 2px);
          --radius-lg: var(--radius);
          --radius-xl: calc(var(--radius) + 4px);
          --radius-2xl: calc(var(--radius) + 8px);
          --radius-3xl: calc(var(--radius) + 12px);
          --radius-4xl: calc(var(--radius) + 16px);
          --color-background: var(--background);
          --color-foreground: var(--foreground);
          --color-card: var(--card);
          --color-card-foreground: var(--card-foreground);
          --color-popover: var(--popover);
          --color-popover-foreground: var(--popover-foreground);
          --color-primary: var(--primary);
          --color-primary-foreground: var(--primary-foreground);
          --color-secondary: var(--secondary);
          --color-secondary-foreground: var(--secondary-foreground);
          --color-muted: var(--muted);
          --color-muted-foreground: var(--muted-foreground);
          --color-accent: var(--accent);
          --color-accent-foreground: var(--accent-foreground);
          --color-destructive: var(--destructive);
          --color-destructive-foreground: var(--destructive-foreground);
          --color-border: var(--border);
          --color-input: var(--input);
          --color-ring: var(--ring);
          --color-ring-offset-background: var(--background);
          --color-chart-1: var(--chart-1);
          --color-chart-2: var(--chart-2);
          --color-chart-3: var(--chart-3);
          --color-chart-4: var(--chart-4);
          --color-chart-5: var(--chart-5);
          --color-sidebar: var(--sidebar);
          --color-sidebar-foreground: var(--sidebar-foreground);
          --color-sidebar-primary: var(--sidebar-primary);
          --color-sidebar-primary-foreground: var(--sidebar-primary-foreground);
          --color-sidebar-accent: var(--sidebar-accent);
          --color-sidebar-accent-foreground: var(--sidebar-accent-foreground);
          --color-sidebar-border: var(--sidebar-border);
          --color-sidebar-ring: var(--sidebar-ring);
        }

        :root {
          --radius: 0.75rem;
          /* TutorPk brand: green + yellow */
          --brand-green: oklch(0.55 0.15 150);
          --brand-green-deep: oklch(0.42 0.13 150);
          --brand-yellow: oklch(0.82 0.17 75);
          --brand-yellow-deep: oklch(0.72 0.18 65);
          --brand-cream: oklch(0.985 0.02 95);

          --background: oklch(0.985 0.02 95);
          --foreground: oklch(0.22 0.05 150);
          --card: oklch(1 0 0);
          --card-foreground: oklch(0.22 0.05 150);
          --popover: oklch(1 0 0);
          --popover-foreground: oklch(0.22 0.05 150);
          --primary: oklch(0.55 0.15 150);
          --primary-foreground: oklch(0.99 0.01 95);
          --secondary: oklch(0.82 0.17 75);
          --secondary-foreground: oklch(0.22 0.05 150);
          --muted: oklch(0.95 0.03 120);
          --muted-foreground: oklch(0.45 0.05 150);
          --accent: oklch(0.82 0.17 75);
          --accent-foreground: oklch(0.22 0.05 150);
          --destructive: oklch(0.577 0.245 27.325);
          --destructive-foreground: oklch(0.984 0.003 247.858);
          --border: oklch(0.9 0.04 130);
          --input: oklch(0.9 0.04 130);
          --ring: oklch(0.55 0.15 150);
          --chart-1: oklch(0.646 0.222 41.116);
          --chart-2: oklch(0.6 0.118 184.704);
          --chart-3: oklch(0.398 0.07 227.392);
          --chart-4: oklch(0.828 0.189 84.429);
          --chart-5: oklch(0.769 0.188 70.08);
          --sidebar: oklch(0.984 0.003 247.858);
          --sidebar-foreground: oklch(0.129 0.042 264.695);
          --sidebar-primary: oklch(0.208 0.042 265.755);
          --sidebar-primary-foreground: oklch(0.984 0.003 247.858);
          --sidebar-accent: oklch(0.968 0.007 247.896);
          --sidebar-accent-foreground: oklch(0.208 0.042 265.755);
          --sidebar-border: oklch(0.929 0.013 255.508);
          --sidebar-ring: oklch(0.704 0.04 256.788);
        }

        .dark {
          --background: oklch(0.129 0.042 264.695);
          --foreground: oklch(0.984 0.003 247.858);
          --card: oklch(0.208 0.042 265.755);
          --card-foreground: oklch(0.984 0.003 247.858);
          --popover: oklch(0.208 0.042 265.755);
          --popover-foreground: oklch(0.984 0.003 247.858);
          --primary: oklch(0.929 0.013 255.508);
          --primary-foreground: oklch(0.208 0.042 265.755);
          --secondary: oklch(0.279 0.041 260.031);
          --secondary-foreground: oklch(0.984 0.003 247.858);
          --muted: oklch(0.279 0.041 260.031);
          --muted-foreground: oklch(0.704 0.04 256.788);
          --accent: oklch(0.279 0.041 260.031);
          --accent-foreground: oklch(0.984 0.003 247.858);
          --destructive: oklch(0.704 0.191 22.216);
          --destructive-foreground: oklch(0.984 0.003 247.858);
          --border: oklch(1 0 0 / 10%);
          --input: oklch(1 0 0 / 15%);
          --ring: oklch(0.551 0.027 264.364);
          --chart-1: oklch(0.488 0.243 264.376);
          --chart-2: oklch(0.696 0.17 162.48);
          --chart-3: oklch(0.769 0.188 70.08);
          --chart-4: oklch(0.627 0.265 303.9);
          --chart-5: oklch(0.645 0.246 16.439);
          --sidebar: oklch(0.208 0.042 265.755);
          --sidebar-foreground: oklch(0.984 0.003 247.858);
          --sidebar-primary: oklch(0.488 0.243 264.376);
          --sidebar-primary-foreground: oklch(0.984 0.003 247.858);
          --sidebar-accent: oklch(0.279 0.041 260.031);
          --sidebar-accent-foreground: oklch(0.984 0.003 247.858);
          --sidebar-border: oklch(1 0 0 / 10%);
          --sidebar-ring: oklch(0.551 0.027 264.364);
        }

        @layer base {
          * {
            border-color: var(--color-border);
          }

          body {
            background-color: var(--color-background);
            color: var(--color-foreground);
          }
        }

        /* =================== TutorPk landing styles =================== */
        .tp-page {
          min-height: 90vh;
          background: var(--brand-cream);
          color: var(--foreground);
          font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        /* ---------- NAV ---------- */
        .tp-nav {
          position: sticky;
          top: 0;
          z-index: 30;
          background: #ffffff;
          border-bottom: 1px solid color-mix(in oklab, var(--brand-green) 12%, transparent);
        }
        .tp-nav-inner {
          max-width: 1240px;
          margin: 0 auto;
          padding: 14px 28px;
          display: flex;
          align-items: center;
          gap: 24px;
        }
        .tp-logo img {
          height: 44px;
          width: auto;
          display: block;
        }
        .tp-nav-links {
          display: flex;
          align-items: center;
          gap: 26px;
          margin-left: 24px;
        }
        .tp-nav-links a {
          font-size: 0.92rem;
          font-weight: 500;
          color: var(--foreground);
          text-decoration: none;
          display: inline-flex;
          align-items: center;
          gap: 4px;
          transition: color 0.15s ease;
        }
        .tp-nav-links a:hover {
          color: var(--brand-green);
        }
        .tp-caret {
          font-size: 0.7rem;
          color: var(--muted-foreground);
        }
        .tp-nav-cta {
          margin-left: auto;
          display: flex;
          align-items: center;
          gap: 14px;
        }
        .tp-login-btn-link {
          font-size: 0.85rem;
          font-weight: 700;
          letter-spacing: 0.06em;
          color: var(--foreground);
          text-decoration: none;
          padding: 8px 6px;
          transition: color 0.15s ease;
        }
        .tp-login-btn-link:hover {
          color: var(--brand-green);
        }
        .tp-signup-wrap {
          position: relative;
        }
        .tp-signup-btn {
          background: var(--brand-yellow);
          color: var(--brand-green-deep);
          font-size: 0.85rem;
          font-weight: 800;
          letter-spacing: 0.06em;
          padding: 11px 22px;
          border: 0;
          border-radius: 6px;
          text-decoration: none;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          gap: 6px;
          box-shadow: 0 8px 20px -8px color-mix(in oklab, var(--brand-yellow-deep) 80%, transparent);
          transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.15s;
        }
        .tp-signup-btn:hover {
          background: var(--brand-yellow-deep);
          transform: translateY(-1px);
        }
        .tp-signup-menu {
          position: absolute;
          top: calc(100% + 12px);
          right: 0;
          min-width: 200px;
          background: #fff;
          border-radius: 10px;
          box-shadow:
            0 18px 40px -10px color-mix(in oklab, var(--brand-green) 25%, transparent),
            0 0 0 1px color-mix(in oklab, var(--brand-green) 12%, transparent);
          padding: 8px;
          opacity: 0;
          visibility: hidden;
          transform: translateY(-6px);
          transition: opacity 0.15s ease, transform 0.15s ease, visibility 0.15s;
          z-index: 40;
        }
        .tp-signup-wrap:hover .tp-signup-menu,
        .tp-signup-wrap:focus-within .tp-signup-menu {
          opacity: 1;
          visibility: visible;
          transform: translateY(0);
        }
        .tp-signup-menu a {
          display: block;
          padding: 10px 12px;
          border-radius: 8px;
          color: var(--foreground);
          text-decoration: none;
          font-size: 0.9rem;
          font-weight: 600;
        }
        .tp-signup-menu a:hover {
          background: color-mix(in oklab, var(--brand-yellow) 25%, transparent);
          color: var(--brand-green-deep);
        }

        /* ---------- HERO ---------- */
        .tp-hero {
          position: relative;
          background:
            radial-gradient(
              circle at 75% 50%,
              color-mix(in oklab, var(--brand-yellow) 30%, transparent) 0%,
              transparent 55%
            ),
            linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-deep) 100%);
          color: #fff;
          overflow: hidden;
          min-height: 35vh;
          display: flex;
          align-items: center;
        }
        .tp-hero::before {
          content: "";
          position: absolute;
          inset: 0;
          background-image:
            radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.07), transparent 40%),
            radial-gradient(circle at 90% 10%, rgba(255, 255, 255, 0.08), transparent 35%);
          pointer-events: none;
        }
        .tp-hero-inner {
          position: relative;
          max-width: 1240px;
          margin: 0 auto;
          width: 100%;
          padding: 15px 28px 30px;
          display: grid;
          grid-template-columns: 1.1fr 1fr;
          gap: 48px;
          align-items: center;
        }
        .tp-hero-copy {
          position: relative;
          z-index: 2;
        }
        .tp-hero-title {
          font-size: clamp(2.2rem, 4.4vw, 4rem);
          line-height: 1.05;
          font-weight: 800;
          letter-spacing: -0.02em;
          margin: 0 0 10px;
          color: #fff;
          text-shadow: 0 2px 30px rgba(0, 0, 0, 0.15);
        }
        .tp-hero-sub {
          font-size: clamp(1rem, 1.4vw, 1.25rem);
          color: color-mix(in oklab, #fff 88%, var(--brand-yellow) 12%);
          margin: 0 0 12px;
          max-width: 520px;
          line-height: 1.5;
          font-weight: 400;
        }
        .tp-hero-actions {
          display: flex;
          flex-wrap: wrap;
          gap: 14px;
        }
        .tp-btn {
          display: inline-flex;
          align-items: center;
          gap: 10px;
          padding: 14px 26px;
          border-radius: 6px;
          font-size: 0.85rem;
          font-weight: 800;
          letter-spacing: 0.08em;
          text-decoration: none;
          cursor: pointer;
          border: 0;
          transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
        }
        .tp-btn-primary {
          background: var(--brand-yellow);
          color: var(--brand-green-deep);
          box-shadow: 0 14px 30px -10px rgba(0, 0, 0, 0.3);
        }
        .tp-btn-primary:hover {
          background: var(--brand-yellow-deep);
          transform: translateY(-2px);
          box-shadow: 0 18px 36px -10px rgba(0, 0, 0, 0.45);
        }
        .tp-btn-ghost {
          background: rgba(255, 255, 255, 0.08);
          color: #fff;
          border: 1px solid rgba(255, 255, 255, 0.45);
          backdrop-filter: blur(2px);
        }
        .tp-btn-ghost:hover {
          background: rgba(255, 255, 255, 0.16);
          transform: translateY(-2px);
        }
        .tp-arrow {
          display: inline-block;
          transition: transform 0.2s ease;
        }
        .tp-btn:hover .tp-arrow {
          transform: translateX(4px);
        }

        /* ---------- HERO VISUAL (particle letter) ---------- */
        .tp-hero-visual {
          position: relative;
          height: 460px;
          width: 100%;
        }
        .tp-glow {
          position: absolute;
          inset: 10% 5% 10% 5%;
          background: radial-gradient(
            circle,
            color-mix(in oklab, var(--brand-yellow) 35%, transparent) 0%,
            transparent 65%
          );
          filter: blur(20px);
        }
        /* Orbiting rings */
        .tp-orbit {
          position: absolute;
          top: 50%;
          left: 50%;
          border-radius: 50%;
          border: 1.5px dashed rgba(255, 255, 255, 0.18);
          transform: translate(-50%, -50%);
        }
        .tp-orbit-1 {
          width: 320px;
          height: 320px;
          animation: tp-spin 22s linear infinite;
        }
        .tp-orbit-2 {
          width: 240px;
          height: 240px;
          border-style: solid;
          border-color: rgba(255, 220, 100, 0.22);
          animation: tp-spin 16s linear infinite reverse;
        }
        .tp-orbit-3 {
          width: 400px;
          height: 400px;
          border-color: rgba(255, 255, 255, 0.1);
          animation: tp-spin 35s linear infinite;
        }
        .tp-orbit-1::before,
        .tp-orbit-2::before {
          content: "";
          position: absolute;
          top: -7px;
          left: 50%;
          width: 14px;
          height: 14px;
          border-radius: 50%;
          background: var(--brand-yellow);
          box-shadow: 0 0 18px var(--brand-yellow);
          transform: translateX(-50%);
        }
        .tp-orbit-2::before {
          background: #ffffff;
          box-shadow: 0 0 14px rgba(255, 255, 255, 0.8);
          width: 10px;
          height: 10px;
          top: -5px;
        }
        @keyframes tp-spin {
          from {
            transform: translate(-50%, -50%) rotate(0deg);
          }
          to {
            transform: translate(-50%, -50%) rotate(360deg);
          }
        }

        /* Central emblem */
        .tp-emblem {
          position: absolute;
          top: 50%;
          left: 50%;
          width: 260px;
          height: 260px;
          transform: translate(-50%, -50%);
          filter: drop-shadow(0 20px 40px rgba(0, 0, 0, 0.4))
            drop-shadow(0 0 30px color-mix(in oklab, var(--brand-yellow) 60%, transparent));
          animation: tp-bob 5s ease-in-out infinite;
        }
        .tp-emblem-svg {
          width: 100%;
          height: 100%;
        }
        @keyframes tp-bob {
          0%, 100% {
            transform: translate(-50%, -50%) translateY(0);
          }
          50% {
            transform: translate(-50%, -50%) translateY(-10px);
          }
        }
        .tp-particles {
          position: absolute;
          inset: 0;
        }
        .tp-particles span {
          position: absolute;
          border-radius: 50%;
          background: var(--brand-yellow);
          box-shadow: 0 0 8px color-mix(in oklab, var(--brand-yellow) 70%, transparent);
          opacity: 0.85;
          animation: tp-float 6s ease-in-out infinite;
        }
        @keyframes tp-float {
          0%, 100% {
            transform: translate(0, 0) scale(1);
            opacity: 0.85;
          }
          50% {
            transform: translate(6px, -10px) scale(1.15);
            opacity: 1;
          }
        }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 980px) {
          .tp-nav-links {
            display: none;
          }
          .tp-hero-inner {
            grid-template-columns: 1fr;
            padding: 60px 24px;
            text-align: center;
          }
          .tp-hero-actions {
            justify-content: center;
          }
          .tp-hero-visual {
            height: 320px;
          }
        }
        @media (max-width: 560px) {
          .tp-nav-inner {
            padding: 12px 16px;
            gap: 8px;
          }
          .tp-logo img {
            height: 36px;
          }
          .tp-login-btn-link {
            display: none;
          }
        }

        /* =================== AUTH PAGES (login + signup) =================== */
        /* (included for completeness, though not used on landing page) */
        .tp-auth {
          min-height: 100vh;
          background:
            radial-gradient(circle at 80% 20%, color-mix(in oklab, var(--brand-yellow) 40%, transparent), transparent 55%),
            linear-gradient(135deg, var(--brand-green) 0%, var(--brand-green-deep) 100%);
          padding: 48px 20px;
          display: flex;
          align-items: center;
          justify-content: center;
          font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }
        .tp-auth-card {
          width: 100%;
          max-width: 460px;
          background: #fff;
          border-radius: 18px;
          padding: 36px 32px;
          box-shadow:
            0 30px 60px -20px rgba(0, 0, 0, 0.35),
            0 0 0 1px color-mix(in oklab, var(--brand-green) 8%, transparent);
        }
        .tp-auth-wide {
          max-width: 720px;
        }
        .tp-auth-logo {
          display: inline-block;
          margin-bottom: 18px;
        }
        .tp-auth-logo img {
          height: 46px;
          width: auto;
        }
        .tp-pill {
          display: inline-block;
          font-size: 0.72rem;
          font-weight: 700;
          letter-spacing: 0.1em;
          text-transform: uppercase;
          color: var(--brand-green-deep);
          background: color-mix(in oklab, var(--brand-yellow) 35%, transparent);
          padding: 5px 12px;
          border-radius: 999px;
          margin-bottom: 12px;
        }
        .tp-auth-title {
          font-size: clamp(1.6rem, 2.4vw, 2rem);
          font-weight: 800;
          letter-spacing: -0.02em;
          color: var(--foreground);
          margin: 0 0 6px;
        }
        .tp-auth-sub {
          font-size: 0.95rem;
          color: var(--muted-foreground);
          margin: 0 0 24px;
          line-height: 1.5;
        }
        .tp-auth-form {
          display: flex;
          flex-direction: column;
          gap: 14px;
        }
        .tp-grid-2 {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 14px;
        }
        .tp-field {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }
        .tp-field > span {
          font-size: 0.82rem;
          font-weight: 600;
          color: var(--foreground);
        }
        .tp-field input,
        .tp-field select,
        .tp-field textarea {
          width: 100%;
          font: inherit;
          font-size: 0.95rem;
          padding: 11px 14px;
          border-radius: 8px;
          border: 1.5px solid var(--border);
          background: #fff;
          color: var(--foreground);
          transition: border-color 0.15s ease, box-shadow 0.15s ease;
          outline: none;
        }
        .tp-field input:focus,
        .tp-field select:focus,
        .tp-field textarea:focus {
          border-color: var(--brand-green);
          box-shadow: 0 0 0 3px color-mix(in oklab, var(--brand-green) 20%, transparent);
        }
        .tp-field textarea {
          resize: vertical;
          min-height: 80px;
        }
        .tp-field input[type="file"] {
          padding: 8px;
          background: color-mix(in oklab, var(--brand-yellow) 10%, #fff);
        }
        .tp-row-between {
          display: flex;
          align-items: center;
          justify-content: space-between;
          font-size: 0.85rem;
          color: var(--muted-foreground);
        }
        .tp-check {
          display: inline-flex;
          align-items: center;
          gap: 8px;
          font-size: 0.85rem;
          color: var(--muted-foreground);
          cursor: pointer;
        }
        .tp-check-block {
          display: flex;
          margin-top: 2px;
        }
        .tp-link {
          color: var(--brand-green);
          text-decoration: none;
          font-weight: 600;
        }
        .tp-link:hover {
          color: var(--brand-green-deep);
          text-decoration: underline;
        }
        .tp-btn-full {
          width: 100%;
          justify-content: center;
          margin-top: 8px;
        }
        .tp-btn-ghost-dark {
          flex: 1;
          background: #fff;
          color: var(--brand-green-deep);
          border: 1.5px solid var(--border);
          padding: 12px 16px;
          border-radius: 8px;
          font-size: 0.85rem;
          font-weight: 700;
          text-decoration: none;
          text-align: center;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          transition: background 0.15s, border-color 0.15s, transform 0.12s;
        }
        .tp-btn-ghost-dark:hover {
          background: color-mix(in oklab, var(--brand-yellow) 18%, #fff);
          border-color: var(--brand-yellow-deep);
          transform: translateY(-1px);
        }
        .tp-divider {
          position: relative;
          text-align: center;
          margin: 22px 0 18px;
          color: var(--muted-foreground);
          font-size: 0.8rem;
        }
        .tp-divider::before,
        .tp-divider::after {
          content: "";
          position: absolute;
          top: 50%;
          width: 38%;
          height: 1px;
          background: var(--border);
        }
        .tp-divider::before { left: 0; }
        .tp-divider::after { right: 0; }
        .tp-divider span {
          background: #fff;
          padding: 0 10px;
          position: relative;
          z-index: 1;
        }
        .tp-auth-cta-row {
          display: flex;
          gap: 10px;
        }
        .tp-auth-foot {
          text-align: center;
          font-size: 0.9rem;
          color: var(--muted-foreground);
          margin-top: 22px;
        }

        @media (max-width: 600px) {
          .tp-auth-card {
            padding: 28px 22px;
          }
          .tp-grid-2 {
            grid-template-columns: 1fr;
          }
          .tp-auth-cta-row {
            flex-direction: column;
          }
        }
    </style>
</head>
<body>
    <div class="tp-page">
        <!-- NAV -->


        <!-- HERO -->
        <section class="tp-hero">
            <div class="tp-hero-inner">
                <div class="tp-hero-copy">
                    <h1 class="tp-hero-title">
                        Pakistan's Smartest<br>
                        Online Tutoring Network
                    </h1>
                    <p class="tp-hero-sub">
                        with a Verified Tutor &amp; Personal Learning Approach
                    </p>

                    <div class="tp-hero-actions">
                        <a href="register.php" class="tp-btn tp-btn-primary">
                            FIND A TUTOR <span class="tp-arrow">→</span>
                        </a>
                        <a href="tutor/tutor_register.php" class="tp-btn tp-btn-ghost">
                            START TEACHING <span class="tp-arrow">→</span>
                        </a>
                    </div>
                </div>

                <div class="tp-hero-visual" aria-hidden="true">
                    <div class="tp-glow"></div>
                    <div class="tp-orbit tp-orbit-1"></div>
                    <div class="tp-orbit tp-orbit-2"></div>
                    <div class="tp-orbit tp-orbit-3"></div>

                    <!-- Central emblem SVG (exactly as in original) -->
                    <div class="tp-emblem">
                        <svg viewBox="0 0 220 220" class="tp-emblem-svg">
                            <defs>
                                <linearGradient id="tpBookGrad" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stopColor="#facc15" />
                                    <stop offset="100%" stopColor="#eab308" />
                                </linearGradient>
                                <linearGradient id="tpCapGrad" x1="0" y1="0" x2="0" y2="1">
                                    <stop offset="0%" stopColor="#ffffff" />
                                    <stop offset="100%" stopColor="#facc15" />
                                </linearGradient>
                            </defs>

                            <!-- Book pages -->
                            <path
                                d="M25 150 Q110 115 195 150 L195 178 Q110 143 25 178 Z"
                                fill="url(#tpBookGrad)"
                            />
                            <path d="M110 128 L110 172" stroke="rgba(0,0,0,0.18)" stroke-width="2" />

                            <!-- Graduation cap -->
                            <g transform="translate(110 70)">
                                <polygon points="-58,15 0,-12 58,15 0,42" fill="url(#tpCapGrad)" />
                                <rect x="-3" y="-12" width="6" height="6" fill="#fff" />
                                <line x1="52" y1="15" x2="52" y2="58" stroke="#fff" stroke-width="2.5" />
                                <circle cx="52" cy="62" r="5" fill="#fff" />
                            </g>

                            <!-- Crescent + star pin -->
                            <g transform="translate(110 110)">
                                <circle r="24" fill="#ffffff" />
                                <circle r="24" cx="8" cy="-3" fill="#15803d" />
                                <g transform="translate(-3 1) scale(0.6)" fill="#ffffff">
                                    <polygon points="0,-12 3,-4 11,-4 4.5,2 7,10 0,5 -7,10 -4.5,2 -11,-4 -3,-4" />
                                </g>
                            </g>
                        </svg>
                    </div>

                    <!-- Particles container (generated via JS to match original React logic) -->
                    <!-- Particles container -->
                    <div class="tp-particles" id="particles-container"></div>

                    <!-- FLOATING CARDS MOVED HERE -->
                    <aside class="side-panel">
                        <div class="side-pill green">
                            <div class="side-pill-icon">🎓</div>
                            <div>
                                <div style="font-size:0.68rem;color:var(--text-muted);font-weight:500;">Students Online</div>
                                <span class="live-count" id="student-count">1,247</span>
                            </div>
                            <div class="side-dot"></div>
                        </div>
                        <div class="side-pill yellow">
                            <div class="side-pill-icon">📚</div>
                            <div>
                                <div style="font-size:0.68rem;color:var(--text-muted);font-weight:500;">Active Tutors</div>
                                <span class="live-count" id="tutor-count">342</span>
                            </div>
                            <div class="side-dot"></div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>

    <!-- Minimal JavaScript to generate particles exactly like the original React component -->
    <script>
        /**
         * Exact replica of the original particleStyle function from index.tsx
         * This ensures identical positioning, sizing, and animation delays.
         */
        function particleStyle(i) {
            // deterministic pseudo-random scatter around the emblem
            const seed = (i * 9301 + 49297) % 233280;
            const r = seed / 233280;
            const r2 = ((i * 2654435761) % 1000) / 1000;
            const angle = r * Math.PI * 2;
            const radius = 30 + r2 * 220;
            const x = 50 + Math.cos(angle) * (radius / 5);
            const y = 50 + Math.sin(angle) * (radius / 5);
            const size = 3 + r2 * 6;
            return {
                left: `${x}%`,
                top: `${y}%`,
                width: `${size}px`,
                height: `${size}px`,
                animationDelay: `${(r * 4).toFixed(2)}s`,
            };
        }

        /**
         * Generate 40 particles on page load (exactly as in React)
         */
        function generateParticles() {
            const container = document.getElementById('particles-container');
            if (!container) return;

            // Clear any existing particles (in case of re-run)
            container.innerHTML = '';

            for (let i = 0; i < 40; i++) {
                const span = document.createElement('span');
                const style = particleStyle(i);
                Object.assign(span.style, style);
                container.appendChild(span);
            }
        }

        // Initialize everything when the page loads
        window.addEventListener('DOMContentLoaded', () => {
            generateParticles();
            
            // Optional: Re-generate particles on window resize for responsive consistency
            window.addEventListener('resize', () => {
                // Only regenerate if the visual size changed significantly
                if (window.innerWidth < 980) {
                    generateParticles();
                }
            });
        });
    </script>
</body>
</html>

<!-- HOW IT WORKS -->
<section class="how-bg" id="how">
  <div class="section-inner">
    <span class="section-label">How It Works</span>
    <h2>Start Learning in 3 Easy Steps</h2>
    <p class="section-sub">Getting connected with the right tutor takes just minutes. Simple, fast, and hassle-free.</p>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-num">1</div>
        <div class="step-title">Create Your Account</div>
        <div class="step-desc">Sign up as a student or tutor in under 2 minutes. No complicated forms — just the basics.</div>
        <div class="step-connector"></div>
      </div>
      <div class="step-card">
        <div class="step-num">2</div>
        <div class="step-title">Search & Match</div>
        <div class="step-desc">Browse verified tutors by subject, city, or price. Read reviews and view profiles.</div>
        <div class="step-connector"></div>
      </div>
      <div class="step-card">
        <div class="step-num">3</div>
        <div class="step-title">Book a Session</div>
        <div class="step-desc">Pick a time, confirm your booking, and join your first session online or in-person.</div>
        <div class="step-connector"></div>
      </div>
      <div class="step-card">
        <div class="step-num">4</div>
        <div class="step-title">Track & Grow</div>
        <div class="step-desc">Monitor your progress, rate your tutor, and keep growing with every session.</div>
      </div>
    </div>
  </div>
</section>

<!-- REGISTER -->
<section id="register">
  <div class="section-inner">
    <span class="section-label">Join TutorPK</span>
    <h2>Choose Your Role</h2>
    <p class="section-sub">Whether you want to learn or teach, TutorPK has everything you need to succeed.</p>
    <div class="register-grid">
      <div class="reg-card student">
        <div class="reg-icon">🎓</div>
        <div class="reg-title">Register as Student</div>
        <div class="reg-desc">Create your learning account, find the best tutors, book sessions, and track your progress all in one place.</div>
        <ul class="reg-features">
          <li>Search 5,000+ verified tutors</li>
          <li>Book online or in-person sessions</li>
          <li>Real-time chat with educators</li>
          <li>Progress tracking dashboard</li>
          <li>Secure and fast payment system</li>
          <li>Access to premium study materials</li>
        </ul>
        <a href="register.php" class="btn btn-primary" style="width:100%; font-size:0.95rem; padding:12px;">Student Sign Up →</a>
      </div>
      <div class="reg-card tutor">
        <div class="reg-icon">📖</div>
        <div class="reg-title">Register as Tutor</div>
        <div class="reg-desc">Join our verified tutor network, create your profile, set your schedule, and start earning by teaching.</div>
        <ul class="reg-features">
          <li>Create a professional tutor profile</li>
          <li>Set your own rates & schedule</li>
          <li>Manage bookings & earnings</li>
          <li>Chat directly with students</li>
          <li>Withdraw earnings with ease</li>
          <li>Advanced student success metrics</li>
        </ul>
        <a href="tutor/tutor_register.php" class="btn btn-yellow" style="width:100%; font-size:0.95rem; padding:12px;">Tutor Sign Up →</a>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features" style="background: linear-gradient(160deg, #f8fdf9, #fffbe8);">
  <div class="section-inner">
    <span class="section-label">Platform Features</span>
    <h2>Everything You Need to Learn</h2>
    <p class="section-sub">TutorPK is packed with tools that make finding, booking, and learning from tutors effortless.</p>
    <div class="features-grid">
      <div class="feat-card"><div class="feat-icon">🔍</div><div class="feat-title">Smart Tutor Search</div><div class="feat-desc">Filter by subject, city, price range, and rating to find your perfect match.</div></div>
      <div class="feat-card"><div class="feat-icon">📅</div><div class="feat-title">Easy Scheduling</div><div class="feat-desc">Book sessions at times that work for you with a visual calendar interface.</div></div>
      <div class="feat-card"><div class="feat-icon">💬</div><div class="feat-title">In-App Messaging</div><div class="feat-desc">Chat with your tutor before and after every class, all in one safe space.</div></div>
      <div class="feat-card"><div class="feat-icon">📊</div><div class="feat-title">Progress Reports</div><div class="feat-desc">See detailed reports on sessions attended, topics covered, and improvement.</div></div>
      <div class="feat-card"><div class="feat-icon">💳</div><div class="feat-title">Secure Payments</div><div class="feat-desc">Pay safely with bank transfer, JazzCash, or EasyPaisa with full receipts.</div></div>
      <div class="feat-card"><div class="feat-icon">⭐</div><div class="feat-title">Reviews & Ratings</div><div class="feat-desc">Read honest feedback from real students to pick the best tutor for you.</div></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="testi-bg" id="testimonials">
  <div class="section-inner">
    <span class="section-label">Student Reviews</span>
    <h2>What Our Students Say</h2>
    <p class="section-sub">Thousands of students across Pakistan are already achieving their goals with TutorPK.</p>
    <div class="testi-grid">
      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"I found the best maths tutor in Lahore within minutes. My grades went from C to A in just 2 months!"</p>
        <div class="testi-author"><div class="testi-avatar">AA</div><div><div class="testi-name">Ahmed Ali</div><div class="testi-role">FSc Student, Lahore</div></div></div>
      </div>
      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"As a tutor, TutorPK helped me reach students across Pakistan. The platform is incredibly easy to use."</p>
        <div class="testi-author"><div class="testi-avatar">SA</div><div><div class="testi-name">Sara Akram</div><div class="testi-role">Physics Tutor, Karachi</div></div></div>
      </div>
      <div class="testi-card">
        <div class="testi-stars">★★★★★</div>
        <p class="testi-text">"Online sessions from home! I connected with a chemistry expert from Islamabad while living in Peshawar."</p>
        <div class="testi-author"><div class="testi-avatar">ZK</div><div><div class="testi-name">Zara Khan</div><div class="testi-role">Matric Student, Peshawar</div></div></div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="cta-section">
  <div class="section-inner" style="text-align:center">
    <span class="section-label">Get Started Today</span>
    <h2>Start Your Learning Journey Now</h2>
    <p>Join thousands of students and tutors already on TutorPK. It's free to sign up!</p>
    <div class="cta-btns">
      <a href="#register" class="btn btn-primary" style="padding:13px 32px; font-size:1rem;">Find a Tutor</a>
      <a href="#register" class="btn btn-yellow" style="padding:13px 32px; font-size:1rem;">Become a Tutor</a>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <div>
      <div class="footer-logo">
        <span style="font-size: 2rem; font-weight: 800; color: #f59e0b; letter-spacing: -1px;">TutorPK</span>
      </div>
      <p class="footer-about">Pakistan's leading online tutoring platform. Connecting students with verified, expert tutors across the country.</p>
    </div>
    <div class="footer-col">
      <h4>Students</h4>
      <ul>
        <li><a href="#">Find a Tutor</a></li>
        <li><a href="#">How It Works</a></li>
        <li><a href="#">Subjects</a></li>
        <li><a href="#">My Dashboard</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Tutors</h4>
      <ul>
        <li><a href="#">Become a Tutor</a></li>
        <li><a href="#">Tutor Guidelines</a></li>
        <li><a href="#">Earnings</a></li>
        <li><a href="#">Resources</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Company</h4>
      <ul>
        <li><a href="#">About Us</a></li>
        <li><a href="#">Contact</a></li>
        <li><a href="#">Privacy Policy</a></li>
        <li><a href="#">Terms of Service</a></li>
      </ul>
    </div>
  </div>
  <div class="footer-bottom">
    <span>© 2026 <strong>TutorPK</strong>. All rights reserved by TutorPK. Learning for All.</span>

  </div>
</footer>



<!-- NOTIFICATION POP -->
<div class="notif-pop" id="notif-pop">
  <div class="notif-dot"></div>
  <div>
    <strong id="notif-name">Ahmed just joined!</strong>
    <span style="color:var(--text-muted);">Found a Maths tutor in Lahore</span>
  </div>
</div>

<script>
  // Animate live counters with slight random increments
  function animateCounter(el, base, max, interval) {
    setInterval(() => {
      const delta = Math.floor(Math.random() * 3) - 1;
      let val = parseInt(el.textContent.replace(/,/g,'')) + delta;
      val = Math.max(base, Math.min(max, val));
      el.textContent = val.toLocaleString();
    }, interval);
  }
  animateCounter(document.getElementById('student-count'), 1200, 1350, 3200);
  animateCounter(document.getElementById('tutor-count'), 320, 380, 4500);
  animateCounter(document.getElementById('session-count'), 70, 120, 5000);

  // Rotate notification messages
  const notifs = [
    {name:'Ahmed just joined!', msg:'Found a Maths tutor in Lahore'},
    {name:'Sara booked a session', msg:'Physics tutor in Karachi'},
    {name:'Zara is now learning', msg:'Chemistry from Islamabad'},
    {name:'Ali started a session', msg:'English tutor in Peshawar'},
    {name:'Hina signed up today', msg:'Biology help in Rawalpindi'},
  ];
  let ni = 0;
  const notifEl = document.getElementById('notif-pop');
  setInterval(() => {
    ni = (ni + 1) % notifs.length;
    notifEl.style.animation = 'none';
    notifEl.offsetHeight;
    notifEl.style.animation = 'popIn 0.5s cubic-bezier(.34,1.56,.64,1) both';
    document.getElementById('notif-name').textContent = notifs[ni].name;
    notifEl.querySelector('span').textContent = notifs[ni].msg;
  }, 5000);
</script>
</body>
</html>
