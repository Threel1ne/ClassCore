<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการห้องเรียน - Responsive Navbar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            --border-radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        /* Main Navbar Styles */
        .main-navbar {
            background: var(--dark-gradient);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #fff !important;
            text-decoration: none;
            transition: var(--transition);
        }

        .navbar-brand:hover {
            transform: scale(1.05);
            color: #fff !important;
        }

        .navbar-brand .brand-icon {
            margin-right: 0.5rem;
            background: var(--primary-gradient);
            padding: 0.5rem;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .navbar-toggler {
            border: none;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 10px;
            padding: 0.5rem;
            transition: var(--transition);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.25);
        }

        .navbar-toggler:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='m4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* User Info in Navbar */
        .user-info {
            display: flex;
            align-items: center;
            color: #ecf0f1;
            font-size: 0.9rem;
            margin-right: 1rem;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 0.75rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.75rem;
            opacity: 0.8;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.15rem 0.5rem;
            border-radius: 12px;
            margin-top: 0.1rem;
        }

        /* Navigation Menu */
        .navbar-nav .nav-link {
            color: #ecf0f1 !important;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: var(--transition);
            position: relative;
            display: flex;
            align-items: center;
            font-weight: 500;
            margin: 0 0.25rem;
        }

        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white !important;
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link.active {
            background: var(--primary-gradient);
            color: white !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .navbar-nav .nav-link i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        /* Dropdown Menu */
        .dropdown-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            min-width: 200px;
        }

        .dropdown-item {
            padding: 0.75rem 1rem;
            transition: var(--transition);
            border-radius: 8px;
            margin: 0.1rem;
        }

        .dropdown-item:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(5px);
        }

        .dropdown-item i {
            margin-right: 0.5rem;
            width: 16px;
            text-align: center;
        }

        /* Mobile Responsive */
        @media (max-width: 991.98px) {
            .navbar-collapse {
                background: rgba(44, 62, 80, 0.95);
                backdrop-filter: blur(10px);
                border-radius: var(--border-radius);
                margin-top: 1rem;
                padding: 1rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }

            .navbar-nav {
                margin-top: 1rem;
            }

            .navbar-nav .nav-link {
                margin: 0.25rem 0;
                padding: 1rem;
            }

            .user-info {
                order: -1;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .dropdown-menu {
                position: static !important;
                transform: none !important;
                box-shadow: none;
                background: rgba(255, 255, 255, 0.1);
                margin-top: 0.5rem;
            }

            .dropdown-item {
                color: #ecf0f1;
            }

            .dropdown-item:hover {
                background: rgba(255, 255, 255, 0.2);
            }
        }

        /* Extra small devices */
        @media (max-width: 575.98px) {
            .navbar-brand {
                font-size: 1.2rem;
            }

            .navbar-brand .brand-text {
                display: none;
            }

            .user-info {
                flex-direction: column;
                text-align: center;
            }

            .user-avatar {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }
        }

        /* Content area */
        .content-area {
            padding: 2rem;
            min-height: calc(100vh - 80px);
        }

        .page-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .page-header h1 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }

        @media (max-width: 767.98px) {
            .content-area {
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-gradient);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        /* Animations */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar-collapse.show {
            animation: slideIn 0.3s ease-out;
        }

        /* Accessibility */
        .navbar-nav .nav-link:focus {
            outline: 2px solid rgba(255, 255, 255, 0.5);
            outline-offset: 2px;
        }

        .dropdown-item:focus {
            outline: 2px solid var(--primary-gradient);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand" href="#" id="navbarBrand">
                <span class="brand-icon">
                    <i class="fas fa-graduation-cap"></i>
                </span>
                <span class="brand-text">ห้องเรียนสีขาวดิจิตอล</span>
            </a>

            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- User Info (Mobile) -->
                <div class="user-info d-lg-none">
                    <div class="user-avatar" id="mobileUserAvatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name" id="mobileUserName">นางสาวดาวใส ใจดี</div>
                        <div class="user-role" id="mobileUserRole">ฝ่ายการเรียน</div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" data-page="dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>หน้าหลัก</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="payments">
                            <i class="fas fa-money-bill-wave"></i>
                            <span>จัดการเงิน</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="academic">
                            <i class="fas fa-graduation-cap"></i>
                            <span>เพิ่มการบ้าน</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="subject_exchange">
                            <i class="fas fa-exchange-alt"></i>
                            <span>สลับวิชา</span>
                            <span class="notification-badge">3</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" data-page="discipline">
                            <i class="fas fa-shield-alt"></i>
                            <span>สารวัตร</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cog"></i>
                            <span>จัดการระบบ</span>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="#" data-page="admin">
                                <i class="fas fa-users-cog"></i>
                                จัดการผู้ใช้
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-page="permissions">
                                <i class="fas fa-lock"></i>
                                ตั้งค่าสิทธิ์
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" data-page="settings">
                                <i class="fas fa-wrench"></i>
                                การตั้งค่า
                            </a></li>
                        </ul>
                    </li>
                </ul>

                <!-- User Info & Actions (Desktop) -->
                <div class="d-none d-lg-flex align-items-center">
                    <div class="user-info me-3">
                        <div class="user-avatar" id="desktopUserAvatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <div class="user-name" id="desktopUserName">นางสาวดาวใส ใจดี</div>
                            <div class="user-role" id="desktopUserRole">ฝ่ายการเรียน</div>
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#" data-page="profile">
                                <i class="fas fa-user"></i>
                                โปรไฟล์
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-page="settings">
                                <i class="fas fa-cog"></i>
                                การตั้งค่า
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="logoutBtn">
                                <i class="fas fa-sign-out-alt"></i>
                                ออกจากระบบ
                            </a></li>
                        </ul>
                    </div>
                </div>

                <!-- Mobile Logout -->
                <div class="d-lg-none mt-3">
                    <a class="nav-link" href="#" id="mobileLogoutBtn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>ออกจากระบบ</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area -->
    <div class="content-area">
        <div class="page-header">
            <h1 id="pageTitle">หน้าหลัก</h1>
            <p class="text-muted mb-0">ระบบจัดการห้องเรียนดิจิตอล</p>
        </div>

        <!-- Demo Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">ยินดีต้อนรับสู่ระบบจัดการห้องเรียน</h5>
                        <p class="card-text">
                            นี่คือตัวอย่างการแสดงผลของ Navbar ที่ตอบสนองการใช้งานบนอุปกรณ์ต่างๆ 
                            ลองปรับขนาดหน้าต่างเบราว์เซอร์เพื่อดูการทำงานแบบ responsive
                        </p>
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <h6>คุณสมบัติของ Navbar:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>รองรับการใช้งานบนมือถือ</li>
                                    <li><i class="fas fa-check text-success me-2"></i>เมนูแบบ dropdown</li>
                                    <li><i class="fas fa-check text-success me-2"></i>แสดงข้อมูลผู้ใช้</li>
                                    <li><i class="fas fa-check text-success me-2"></i>ไอคอนการแจ้งเตือน</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>การออกแบบ:</h6>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>ใช้ Bootstrap 5</li>
                                    <li><i class="fas fa-check text-success me-2"></i>ฟอนต์ไทย Noto Sans Thai</li>
                                    <li><i class="fas fa-check text-success me-2"></i>สีและเอฟเฟกต์สวยงาม</li>
                                    <li><i class="fas fa-check text-success me-2"></i>เข้าถึงได้ง่าย (Accessibility)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle navigation clicks
            const navLinks = document.querySelectorAll('.nav-link[data-page]');
            const pageTitle = document.getElementById('pageTitle');
            
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Update page title
                    const page = this.getAttribute('data-page');
                    const linkText = this.querySelector('span').textContent;
                    pageTitle.textContent = linkText;
                    
                    // Close mobile menu if open
                    const navbarCollapse = document.getElementById('navbarNav');
                    if (navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                    
                    // Simulate page loading
                    console.log('Navigating to:', page);
                });
            });
            
            // Handle logout
            const logoutButtons = document.querySelectorAll('#logoutBtn, #mobileLogoutBtn');
            logoutButtons.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('คุณต้องการออกจากระบบหรือไม่?')) {
                        alert('ออกจากระบบสำเร็จ');
                        // Redirect to login page
                        // window.location.href = 'login.php';
                    }
                });
            });
            
            // Simulate user data update
            function updateUserInfo() {
                const users = [
                    { name: 'นางสาวดาวใส ใจดี', role: 'ฝ่ายการเรียน', avatar: 'D' },
                    { name: 'นายสมชาย ดีมาก', role: 'หัวหน้าห้อง', avatar: 'ส' },
                    { name: 'นางสาวมาลี สวยงาม', role: 'เลขานุการ', avatar: 'ม' },
                    { name: 'นายประดิษฐ์ เก่งมาก', role: 'admin', avatar: 'ป' }
                ];
                
                const randomUser = users[Math.floor(Math.random() * users.length)];
                
                // Update desktop user info
                document.getElementById('desktopUserName').textContent = randomUser.name;
                document.getElementById('desktopUserRole').textContent = randomUser.role;
                document.getElementById('desktopUserAvatar').innerHTML = randomUser.avatar;
                
                // Update mobile user info
                document.getElementById('mobileUserName').textContent = randomUser.name;
                document.getElementById('mobileUserRole').textContent = randomUser.role;
                document.getElementById('mobileUserAvatar').innerHTML = randomUser.avatar;
            }
            
            // Update user info every 10 seconds for demo
            setInterval(updateUserInfo, 10000);
            
            // Handle responsive brand text
            function handleBrandText() {
                const brandText = document.querySelector('.brand-text');
                if (window.innerWidth <= 575) {
                    brandText.style.display = 'none';
                } else {
                    brandText.style.display = 'inline';
                }
            }
            
            // Check on load and resize
            handleBrandText();
            window.addEventListener('resize', handleBrandText);
            
            // Add smooth scrolling for mobile menu
            const navbarToggler = document.querySelector('.navbar-toggler');
            navbarToggler.addEventListener('click', function() {
                setTimeout(() => {
                    const navbar = document.querySelector('.main-navbar');
                    navbar.scrollIntoView({ behavior: 'smooth' });
                }, 100);
            });
            
            // Add keyboard navigation support
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const openDropdowns = document.querySelectorAll('.dropdown-menu.show');
                    openDropdowns.forEach(dropdown => {
                        const bsDropdown = bootstrap.Dropdown.getInstance(dropdown.previousElementSibling);
                        if (bsDropdown) {
                            bsDropdown.hide();
                        }
                    });
                    
                    const navbarCollapse = document.getElementById('navbarNav');
                    if (navbarCollapse.classList.contains('show')) {
                        const bsCollapse = new bootstrap.Collapse(navbarCollapse);
                        bsCollapse.hide();
                    }
                }
            });
        });
    </script>
</body>
</html>