# Deployment Guide: JKKMCT Quiz Platform on Free Hosting

This guide walks you through deploying the quiz platform to **InfinityFree** (recommended free hosting) for real-world usage.

---

## Step 1: Create a Free Hosting Account

1. Go to [**InfinityFree.com**](https://www.infinityfree.com/)
2. Click **Sign Up** and create a free account
3. After verifying your email, log in to the dashboard

---

## Step 2: Create a Free Domain

1. In the InfinityFree dashboard, click **"Create Account"**
2. Choose **Free Subdomain** (e.g., `jkkmctquiz.infinityfreeapp.com`)
   - Or connect your own custom domain if you have one
3. Set a label (e.g., "JKKMCT Quiz")
4. Click **Create** — wait for it to activate (takes 1-5 minutes)

---

## Step 3: Create the MySQL Database

1. In your hosting panel, go to **MySQL Databases**
2. Click **Create Database**
3. Note down these details:
   - **Database Name** (e.g., `if0_12345678_jkkmct_quiz`)
   - **Username** (e.g., `if0_12345678`)
   - **Password** (auto-generated, copy it)
   - **Host** (e.g., `sql306.infinityfree.com`)

4. Open **phpMyAdmin** from the control panel
5. Select your database
6. Click the **Import** tab
7. Upload the `schema.sql` file from this project
8. Click **Go** — this creates all the required tables

---

## Step 4: Update Database Credentials

Before uploading, edit `config.php` with your hosting database details:

```php
$host = 'sql306.infinityfree.com';     // Your MySQL host from Step 3
$username = 'if0_12345678';             // Your MySQL username
$password = 'your_generated_password';  // Your MySQL password
$database = 'if0_12345678_jkkmct_quiz'; // Your database name
```

---

## Step 5: Upload Project Files

### Option A: Using File Manager (Easier)
1. In the control panel, open **Online File Manager**
2. Navigate to the `htdocs` folder
3. Upload ALL project files maintaining this structure:
   ```
   htdocs/
   ├── index.php
   ├── config.php
   ├── schema.sql
   ├── admin/
   │   ├── dashboard.php
   │   ├── login.php
   │   ├── logout.php
   │   ├── manage_competitions.php
   │   ├── manage_questions.php
   │   ├── view_candidates.php
   │   └── generate_report.php
   └── student/
       ├── register.php
       ├── login.php
       ├── logout.php
       ├── wait_room.php
       ├── take_quiz.php
       └── submit_quiz.php
   ```

### Option B: Using FTP (For Bulk Upload)
1. Download **FileZilla** (free FTP client)
2. Get FTP credentials from the hosting control panel:
   - Host: `ftpupload.net`
   - Username: your FTP username
   - Password: your FTP password
   - Port: `21`
3. Connect and upload all files to `htdocs/`

---

## Step 6: Access Your Live Website

Your website will be live at:
```
https://jkkmctquiz.infinityfreeapp.com/
```
- Admin Panel: `https://jkkmctquiz.infinityfreeapp.com/admin/login.php`
- Student Portal: `https://jkkmctquiz.infinityfreeapp.com/student/login.php`

---

## Step 7: Change Default Admin Password

> **IMPORTANT**: For real-world usage, change the default admin password immediately.

1. Open phpMyAdmin on your hosting panel
2. Select your database → `admins` table
3. Edit the row and update the password hash
4. You can generate a new hash using: https://bcrypt-generator.com/

---

## Important Notes for Production

### Email Sending
- Free hosting has limited `mail()` support
- For reliable email delivery, consider using a free SMTP service:
  - **Gmail SMTP** (500 emails/day)
  - **Brevo (formerly Sendinblue)** (300 emails/day free)
- For now, the debug credentials box on the registration page shows the generated ID/password for testing

### SSL Certificate
- InfinityFree provides **free SSL** (HTTPS) automatically
- Your site will be accessible via `https://`

### Limitations of Free Hosting
- No SSH access
- Limited PHP execution time (may affect large quizzes)
- Database size limited to the free plan quota
- May have daily hit limits (50,000 hits/day on InfinityFree)

---

## Alternative Free Hosting Providers

| Provider | Storage | PHP | MySQL | SSL | No Ads |
|----------|---------|-----|-------|-----|--------|
| **InfinityFree** | 5 GB | 8.3 | ✅ | ✅ | ✅ |
| **FreeHosting.com** | 10 GB | 8.0 | ✅ | ✅ | ✅ |
| **AwardSpace** | 1 GB | 8.x | ✅ | ✅ | ✅ |
| **GoogieHost** | 1 GB | 8.x | ✅ | ✅ | ✅ |
