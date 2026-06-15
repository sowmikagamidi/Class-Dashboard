# Class Dashboard – Student Feedback Analytics System

A full-featured web dashboard to collect, analyze, and visualize student feedback about teachers. It provides real‑time insights into teaching performance, engagement, and overall satisfaction.

![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
---

## 📌 Key Features

- **Teacher performance cards** with visual progress bars for Engagement, Clarity, Doubts Solved, and Experience.
- **Table view** with sortable columns, grade badges, and quick action buttons.
- **Raw feedback table** that combines student details (name + class/board/subject) with pagination (15 records per page) and live search.
- **Advanced filters** by teacher, class, subject, and date range.
- **Side panel** that slides open to show all comments for a selected teacher (respects active filters).
- **CSV export** of both teacher performance and detailed feedback.
- **Fully responsive** – works on desktops, tablets, and mobile devices.

---

## 🛠️ Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | PHP 7.4+                            |
| Database    | MySQL / MariaDB                     |
| Frontend    | HTML5, CSS3, JavaScript (ES6)       |
| Icons       | Font Awesome 6                      |
| Server      | Apache (XAMPP / WAMP / LAMP)        |

---
## 🚀 Installation Guide

### 1. Clone the repository
```bash
git clone https://github.com/sowmikagamidi/Class-Dashboard.git
cd Class-Dashboard
```

### 2. Set up the database
- Create a database (e.g. `dashboard`).
- Import the SQL schema (if you have a `.sql` dump) or manually create the tables above.
- Update the database credentials in `config.php` or directly in the main PHP files:

```php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'dashboard';
```

### 3. Run the application
- **XAMPP** : copy the folder to `C:\xampp\htdocs\class-dashboard` and visit `http://localhost/class-dashboard`
- **Built‑in PHP server** : `php -S localhost:8000`

### 4. Insert sample data (optional)
Populate the system with demo teachers and feedback using the provided sample SQL statements.

---

## 📖 How to Use

| Feature               | How to access                                                                 |
|-----------------------|--------------------------------------------------------------------------------|
| Teacher Cards         | Default view – shows progress bars, overall score, and a "View" button.       |
| Table View            | Click *Table View* toggle above the cards.                                    |
| Filter data           | Use the filter bar (Teacher, Class, Subject, Date range) and click *Apply*.   |
| See student comments  | Click **View** on any teacher – a right panel opens with all raw feedback.    |
| Export to CSV         | Click the green **Export CSV** button in the top‑right corner.                |
| Search comments       | Go to the *Raw Feedback* tab and type in the search box.                      |

---

## 📊 Grading System

| Overall Score | Grade | Label               | Color |
|---------------|-------|---------------------|-------|
| ≥ 85%         | A     | Excellent           | Green |
| 70 – 84%      | B     | Good                | Blue  |
| 50 – 69%      | C     | Average             | Orange|
| < 50%         | D     | Needs Improvement   | Red   |

---

## 📂 File Overview

| File                    | Purpose                                                       |
|-------------------------|---------------------------------------------------------------|
| `dashboard.php`         | Main dashboard – displays teacher cards, table, raw feedback.|
| `ajax/get_teacher_feedback.php` | AJAX endpoint that returns all feedback for a given teacher. |
| `config.php`            | Database connection and shared helper functions.             |
| `README.md`             | This documentation file.                                      |

---



