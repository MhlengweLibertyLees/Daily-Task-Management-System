Daily Task System 📋
A modern, responsive task management system for team productivity tracking. Members submit daily work reports while administrators monitor team activity and manage users.


🌟 Features
For Team Members
✅ Submit daily task reports with details, blockers, and hours worked
✅ View complete task history
✅ Edit submissions before they're locked
✅ Export personal task data to CSV
✅ Real-time character counters on forms
✅ Mobile-responsive interface

For Administrators
👥 Monitor all team submissions via weekly grid (Monday–Friday)
🔒 Lock tasks to prevent further edits
📊 View detailed task information
👤 Manage users (create, edit, activate/deactivate)
📈 Export all team data to CSV
🔍 Complete audit trail of system actions
📅 Color-coded status indicators (Submitted, Edited, Locked)

🛠️ Technology Stack
Backend: PHP 8.0+, MySQL 5.7+, PDO
Frontend: Bootstrap 5.3.2, Vanilla JavaScript, Bootstrap Icons, Animate.css, Custom CSS3
Security: CSRF protection, bcrypt password hashing, SQL injection prevention, session timeout, RBAC


php database/seed.php
Email: admin@example.com
Password: AdminPass123!

## 📁 Project Structure
daily-task-system/
├── config/
│   ├── db.php              # Database configuration
│   └── config.php          # Application settings
├── lib/
│   ├── auth.php            # Authentication functions
│   ├── csrf.php            # CSRF protection
│   ├── tasks.php           # Task management functions
│   ├── utils.php           # User management functions
│   ├── validators.php      # Audit logging
│   └── permissions.php     # Access control
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css   # Custom styles
│   │   ├── js/
│   │   │   └── main.js     # JavaScript interactions
│   │   └── img/
│   │       └── logo.PNG    # Company logo
│   ├── tasks/
│   │   ├── create.php      # Task creation form
│   │   ├── edit.php        # Task editing
│   │   └── my.php          # User's task history
│   ├── admin/
│   │   ├── tasks.php       # All tasks management
│   │   ├── users.php       # User management
│   │   └── lock.php        # Task locking
│   ├── dashboard.php       # Main dashboard
│   ├── login.php           # Authentication
│   └── logout.php          # Session destruction
├── views/
│   ├── header.php          # Global header/navigation
│   └── footer.php          # Global footer
├── database/
│   ├── schema.sql          # Database schema
│   └── seed.php            # Sample data seeder
└── README.md               # This file


💻 Usage
Members
Login → Dashboard shows today’s status
Submit task → details, blockers, hours
Edit before admin locks
Export history to CSV

Administrators
Weekly grid with color-coded statuses
Manage tasks (filter, lock, export)
Manage users (CRUD, roles, activation)
Audit trail for all actions

🎨 Customization
Colors: Edit public/assets/css/style.css
Logo: Replace public/assets/img/logo.PNG
Session Timeout: Adjust in config/config.php

🔐 Security Features
CSRF tokens
PDO prepared statements
Escaped output (htmlspecialchars)
Bcrypt password hashing
Secure sessions with HTTP-only cookies
Role-based access control
Audit logging

📱 Responsive Design
Desktop: multi-column layouts
Tablet: adjusted sidebar layouts
Mobile: single column, touch-optimized

🧪 Testing
User registration/login
Task submission/editing
Admin grid display
Locking functionality
CSV export
CSRF/session timeout
Responsive design

🐛 Troubleshooting
DB connection failed: Check MySQL service & credentials
CSRF error: Clear cookies, check session config
Blank page: Enable error reporting in php.ini
Styles missing: Verify paths & clear cache

🤝 Contributing
Fork repo
Create feature branch
Commit changes
Push branch
Open PR
Coding standards: PSR-12, meaningful names, secure coding practices.

📝 License
MIT License – free to use and modify.


