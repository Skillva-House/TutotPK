# 🎓 TutorPK - Online Tutoring Platform

TutorPK is a comprehensive, full-stack web application built with PHP that connects students with verified tutors. It provides a seamless educational ecosystem featuring class scheduling, real-time chat, an AI-powered learning assistant, and an integrated payment system.

## 🚀 Key Features

### 🧑‍🎓 For Students
* **Find Tutors:** Search for tutors based on subjects and view their profiles.
* **Book Classes:** Seamlessly book learning sessions with preferred tutors.
* **AI Assistant:** Get instant help using the built-in AI Chatbot (Powered by Groq/Gemini).
* **Real-time Chat:** Communicate directly with tutors.
* **Gamification:** Earn rewards and track learning progress through gamified elements.
* **Payments & Subscriptions:** Manage class payments and AI chatbot subscription upgrades.

### 👨‍🏫 For Tutors
* **Profile Management:** Set up a professional profile highlighting skills and subjects.
* **Schedule Classes:** Create and manage availability and upcoming schedules.
* **Student Management:** Keep track of enrolled students and class sessions.
* **Attendance System:** Mark students present or absent easily.
* **Real-time Chat:** Reply to student inquiries and guide them efficiently.

### 🛡️ For Admins
* **Dashboard Overview:** Monitor the overall activity of the platform.
* **Tutor Verification:** Review and approve tutor registrations to ensure quality.
* **Financial Management:** Track and verify payments.
* **Resolving Reports:** Manage reports submitted by users (dispute resolution).
* **Chatbot Upgrades:** Manage user AI subscription upgrades.

## �️ Technology Stack
* **Backend:** PHP (Vanilla)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript, AJAX
* **Database:** MySQL
* **APIs & Integrations:** 
  * PHPMailer (for email functionality)
  * Groq API / Gemini API (for AI Assistant)
* **Server Environment:** XAMPP / Apache

## 💻 Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Skillva-House/TutotPK.git
   ```

2. **Move to local server:**
   Place the project folder inside your `htdocs` (XAMPP) or `www` (WAMP) directory.

3. **Database Configuration:**
   * Create a new MySQL database named `tutorpk` (or your preferred name) via phpMyAdmin.
   * Import the provided `.sql` file (if applicable) to structure the tables.
   * Update the credentials in `connect.php` to match your local database settings (username, password, dbname).

4. **API & Email Configuration:**
   * **AI Chatbots:** Open `ajax/assistant_ask.php` and replace `'YOUR_GROQ_API_KEY'` and `'YOUR_GEMINI_API_KEY'` with your actual API keys.
   * **Mailing System:** Open `includes/mailer.php` and replace `'YOUR_EMAIL@gmail.com'` and `'YOUR_16_DIGIT_APP_PASSWORD'` with your real Gmail address and generated App Password.
   * *Note: Never commit your real API keys or Email passwords to GitHub!*

5. **Run the Application:**
   Open your browser and navigate to: `http://localhost/tutorpk/`

## 🔒 Security Notes
* This repository uses `.htaccess` to protect certain directories.
* Uploaded files (CVs, photos, payment screenshots) are stored in specific folders under `assets/uploads/`.
* For production environments, ensure environment variables or hidden configuration files are used for database credentials and API keys.

---
*Developed by Skillva House*