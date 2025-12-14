<?php
// Progress.php
session_start();
include 'connection.php';

if (!isset($_SESSION['email'])) {
    header("Location: Loginpage.php");
    exit();
}

$user_email = $_SESSION['email'];
$current_user_id = null;

$sql = "SELECT id FROM users WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $current_user_id = $row['id'];
} else {
    session_destroy();
    header("Location: Loginpage.php");
    exit();
}
$stmt->close();

function getWorkoutAnalytics($conn, $user_id) {
    $analytics = [];
    
    // TODAY'S STATS
    $sql_today = "SELECT COUNT(id) AS today_workouts, SUM(total_duration_seconds) AS today_duration 
                  FROM workout_sessions 
                  WHERE user_id = ? AND session_date = CURDATE()";
    $stmt_today = $conn->prepare($sql_today);
    $stmt_today->bind_param("i", $user_id);
    $stmt_today->execute();
    $result_today = $stmt_today->get_result()->fetch_assoc();
    $stmt_today->close();
    
    $analytics['today_workouts'] = $result_today['today_workouts'] ?? 0;
    $analytics['today_duration_min'] = round(($result_today['today_duration'] ?? 0) / 60);

    // WEEKLY STATS (Last 7 days)
    $sql_weekly = "SELECT COUNT(id) AS weekly_workouts, SUM(total_duration_seconds) AS weekly_duration 
                   FROM workout_sessions 
                   WHERE user_id = ? AND session_date >= DATE(NOW() - INTERVAL 7 DAY)";
    $stmt_weekly = $conn->prepare($sql_weekly);
    $stmt_weekly->bind_param("i", $user_id);
    $stmt_weekly->execute();
    $result_weekly = $stmt_weekly->get_result()->fetch_assoc();
    $stmt_weekly->close();
    
    $analytics['weekly_workouts'] = $result_weekly['weekly_workouts'] ?? 0;
    $analytics['weekly_duration_min'] = round(($result_weekly['weekly_duration'] ?? 0) / 60);

    // MONTHLY STATS (Current month)
    $sql_monthly = "SELECT COUNT(id) AS monthly_workouts, SUM(total_duration_seconds) AS monthly_duration 
                    FROM workout_sessions 
                    WHERE user_id = ? AND MONTH(session_date) = MONTH(CURDATE()) AND YEAR(session_date) = YEAR(CURDATE())";
    $stmt_monthly = $conn->prepare($sql_monthly);
    $stmt_monthly->bind_param("i", $user_id);
    $stmt_monthly->execute();
    $result_monthly = $stmt_monthly->get_result()->fetch_assoc();
    $stmt_monthly->close();
    
    $analytics['monthly_workouts'] = $result_monthly['monthly_workouts'] ?? 0;
    $analytics['monthly_duration_min'] = round(($result_monthly['monthly_duration'] ?? 0) / 60);

    // ALL-TIME STATS
    $sql_total = "SELECT COUNT(id) AS total_workouts, SUM(total_duration_seconds) AS total_duration FROM workout_sessions WHERE user_id = ?";
    $stmt_total = $conn->prepare($sql_total);
    $stmt_total->bind_param("i", $user_id);
    $stmt_total->execute();
    $result_total = $stmt_total->get_result()->fetch_assoc();
    $stmt_total->close();
    
    $analytics['total_workouts'] = $result_total['total_workouts'] ?? 0;
    $analytics['total_duration_min'] = round(($result_total['total_duration'] ?? 0) / 60);

    // DAILY WORKOUTS (Last 7 days for chart)
    $sql_daily = "SELECT session_date, COUNT(id) AS count, SUM(total_duration_seconds) AS duration 
                  FROM workout_sessions 
                  WHERE user_id = ? AND session_date >= DATE(NOW() - INTERVAL 7 DAY) 
                  GROUP BY session_date 
                  ORDER BY session_date ASC";
    $stmt_daily = $conn->prepare($sql_daily);
    $stmt_daily->bind_param("i", $user_id);
    $stmt_daily->execute();
    $result_daily = $stmt_daily->get_result();
    $daily_data = [];
    while ($row = $result_daily->fetch_assoc()) {
        $daily_data[$row['session_date']] = [
            'workouts' => $row['count'],
            'duration_min' => round($row['duration'] / 60)
        ];
    }
    
    // Fill in missing days with zeros
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        if (!isset($daily_data[$date])) {
            $daily_data[$date] = [
                'workouts' => 0,
                'duration_min' => 0
            ];
        }
    }
    
    // Sort by date
    ksort($daily_data);
    $analytics['daily'] = $daily_data;
    $stmt_daily->close();

    // MONTHLY TREND (Last 6 months for chart)
    $sql_monthly_trend = "SELECT DATE_FORMAT(session_date, '%Y-%m') AS month, 
                                 COUNT(id) AS count, 
                                 SUM(total_duration_seconds) AS duration 
                          FROM workout_sessions 
                          WHERE user_id = ? AND session_date >= DATE(NOW() - INTERVAL 6 MONTH) 
                          GROUP BY month 
                          ORDER BY month ASC";
    $stmt_monthly_trend = $conn->prepare($sql_monthly_trend);
    $stmt_monthly_trend->bind_param("i", $user_id);
    $stmt_monthly_trend->execute();
    $result_monthly_trend = $stmt_monthly_trend->get_result();
    $monthly_data = [];
    while ($row = $result_monthly_trend->fetch_assoc()) {
        $monthly_data[$row['month']] = [
            'workouts' => $row['count'],
            'duration_min' => round($row['duration'] / 60)
        ];
    }
    
    // Fill in missing months with zeros
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        if (!isset($monthly_data[$month])) {
            $monthly_data[$month] = [
                'workouts' => 0,
                'duration_min' => 0
            ];
        }
    }
    
    // Sort by month
    ksort($monthly_data);
    $analytics['monthly_trend'] = $monthly_data;
    $stmt_monthly_trend->close();

    // WORKOUT FREQUENCY ANALYSIS
    $sql_frequency = "SELECT 
                        DAYNAME(session_date) as day_name,
                        COUNT(id) as workout_count
                      FROM workout_sessions 
                      WHERE user_id = ?
                      GROUP BY DAYNAME(session_date)
                      ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $stmt_frequency = $conn->prepare($sql_frequency);
    $stmt_frequency->bind_param("i", $user_id);
    $stmt_frequency->execute();
    $result_frequency = $stmt_frequency->get_result();
    $frequency_data = [];
    $total_days = 0;
    
    while ($row = $result_frequency->fetch_assoc()) {
        $frequency_data[$row['day_name']] = $row['workout_count'];
        $total_days += $row['workout_count'];
    }
    
    // Fill in missing days with zeros
    $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    foreach ($days_of_week as $day) {
        if (!isset($frequency_data[$day])) {
            $frequency_data[$day] = 0;
        }
    }
    
    $analytics['frequency'] = $frequency_data;
    $analytics['total_workout_days'] = $total_days;
    $stmt_frequency->close();

    // AVERAGE WORKOUT DURATION
    $sql_avg = "SELECT AVG(total_duration_seconds) as avg_duration 
                FROM workout_sessions 
                WHERE user_id = ?";
    $stmt_avg = $conn->prepare($sql_avg);
    $stmt_avg->bind_param("i", $user_id);
    $stmt_avg->execute();
    $result_avg = $stmt_avg->get_result()->fetch_assoc();
    $stmt_avg->close();
    
    $analytics['avg_duration_min'] = round(($result_avg['avg_duration'] ?? 0) / 60);

    return $analytics;
}

$workout_analytics = getWorkoutAnalytics($conn, $current_user_id);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Dashboard</title>
    <link rel="stylesheet" href="Memberstyle.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    
    <!-- Enhanced Styles -->
    <style>
        /* Additional animation for card entrance */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-container {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
        
        /* Progress Card Styling */
        .progress-card {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            border-left: 4px solid #FFD700;
            transition: all 0.3s ease;
        }
        
        .progress-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .progress-card h3 {
            color: #FFD700 !important;
        }
        
        .progress-card p {
            color: #ffffff !important;
        }
        
        /* Chart container styling */
        .chart-container {
            background-color: #ffffff;
            border: 2px solid #e5e5e5;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container:hover {
            border-color: #FFD700;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }
        
        /* Insight cards styling */
        .insight-card {
            background-color: #ffffff;
            border: 2px solid #e5e5e5;
            border-radius: 1rem;
            transition: all 0.3s ease;
        }
        
        .insight-card:hover {
            border-color: #FFD700;
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .insight-card h3 {
            color: #000000;
            border-bottom: 3px solid #FFD700;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        
        /* Trend indicator styling */
        .trend-up {
            color: #10B981;
            background-color: rgba(16, 185, 129, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
        }
        
        .trend-down {
            color: #EF4444;
            background-color: rgba(239, 68, 68, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
        }
        
        .trend-neutral {
            color: #FFD700;
            background-color: rgba(107, 114, 128, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 600;
        }
        
        /* Progress bar styling */
        .progress-bar-container {
            width: 100%;
            height: 12px;
            background-color: #e5e5e5;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 0.5rem;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #FFD700, #FFED4E);
            border-radius: 6px;
            transition: width 1s ease-in-out;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .main-content {
                margin-top: 80px;
                padding: 1rem;
            }
            
            .progress-cards-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            
            .chart-grid {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .chart-container {
                padding: 1rem;
            }
            
            .insight-cards-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
        }
        
        /* Custom scrollbar */
        .chart-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .chart-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .chart-container::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 10px;
        }
        
        .chart-container::-webkit-scrollbar-thumb:hover {
            background: #e6c200;
        }
        
        /* Animation for charts */
        @keyframes chartFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        canvas {
            animation: chartFadeIn 0.8s ease forwards;
        }
        
        /* Loading animation for charts */
        .chart-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
            color: #666;
        }
        
        .chart-loading::after {
            content: '';
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #FFD700;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Metric card styling */
        .metric-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e5e5e5;
        }
        
        .metric-card:hover {
            border-color: #FFD700;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        /* Day of week highlighting */
        .active-day {
            background-color: #FFD700 !important;
            color: #000000 !important;
            font-weight: 600;
        }
        
        .day-cell {
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .day-cell:hover {
            background-color: rgba(255, 215, 0, 0.1);
        }

        .insight-card:not(.bg-gradient-to-r) h3 {
            color: #000000;
            border-bottom: 3px solid #FFD700;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }

/* Special styling for gradient summary cards - ADD THIS */
        .insight-card.bg-gradient-to-r {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            border: 2px solid #FFD700;
            color: white;
        }

        .insight-card:not(.bg-gradient-to-r) h3 {
            color: #000000;
            border-bottom: 3px solid #FFD700;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }

        /* Special styling for gradient summary cards - ADD THIS */
        .insight-card.bg-gradient-to-r {
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            border: 2px solid #FFD700;
            color: white;
        }

        .insight-card.bg-gradient-to-r h3 {
            color: white !important;
        }
    </style>
</head>
<body class="min-h-screen">

<!-- Mobile Top Navbar -->
<nav class="mobile-navbar" id="mobileNavbar">
    <div class="navbar-container">
        <div class="navbar-brand">
            <i class='bx bx-line-chart text-yellow-500 text-2xl'></i>
            <h2>Progress Dashboard</h2>
        </div>
        <button class="navbar-toggle" id="navbarToggle">
            <i class='bx bx-menu'></i>
        </button>
    </div>
    <div class="navbar-menu" id="navbarMenu">
        <ul>
            <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
            <li><a href="WorkoutJournal.php"><i class='bx bx-notepad'></i> Workout Journal</a></li>
            <li><a href="#" class="active"><i class='bx bx-line-chart'></i> Progress</a></li>
            <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
            <li class="more-menu-mobile">
                <a href="#" class="more-toggle-mobile">
                    <i class='bx bx-dots-horizontal-rounded'></i> More 
                    <i class='bx bx-chevron-down toggle-icon'></i>
                </a>
                <ul class="submenu" id="mobileSubmenu">
                    <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                    <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
                </ul>
            </li>
            <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
        </ul>
    </div>
</nav>

<!-- Desktop Sidebar -->
<div class="sidebar">
    <h2>Member Panel</h2>
    <ul>
        <li><a href="Membership.php"><i class='bx bx-user'></i> User Details</a></li>
        <li><a href="WorkoutJournal.php"><i class='bx bx-notepad'></i> Workout Journal</a></li>
        <li><a href="#" class="bg-gray-700"><i class='bx bx-line-chart'></i> Progress</a></li>
        <li><a href="TrainerBooking.php"><i class='bx bxs-user-pin'></i> Trainers</a></li>
        <li class="more-menu">
            <a href="#" class="more-toggle">
                 More 
                <i class='bx bx-chevron-down toggle-icon'></i>
            </a>
            <ul class="submenu" id="desktopSubmenu">
                <li><a href="CalorieScanner.php"><i class='bx bx-scan'></i> Calorie Scanner</a></li>
                <li><a href="gym_scanner_module.php"><i class='bx bx-qr-scan'></i> Scan Equipment</a></li>
            </ul>
        </li>
        <li><a href="Loginpage.php"><i class='bx bx-log-out'></i> Logout</a></li>
    </ul>
</div>

<!-- Main Content Container -->
<div class="main-content">
    <div class="cards-container">
        <!-- Header -->
        <div class="header-container">
            <div class="header-title">
                <h1 class="main-heading">Fitness Progress</h1>
                <div class="message-container">
                    <p class="text-center text-gray-600 mb-8">Track your fitness journey with clear and easy analytics</p>
                </div>
            </div>
        </div>
        
        <!-- Card container for progress dashboard -->
        <div class="card-container w-full">
            <!-- Overview Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Today -->
                <div class="progress-card p-6 rounded-lg shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class='bx bx-calendar-check text-yellow-500 text-2xl mr-3'></i>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">Today</h3>
                                <p class="text-sm text-gray-300">Daily Activity</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium px-3 py-1 rounded-full bg-yellow-500 bg-opacity-20 text-yellow-300">Now</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Workouts Completed</p>
                            <p class="stat-value text-3xl font-bold"><?php echo $workout_analytics['today_workouts']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Total Duration</p>
                            <p class="stat-value text-2xl font-bold"><?php echo $workout_analytics['today_duration_min']; ?> min</p>
                        </div>
                    </div>
                </div>
                
                <!-- This Week -->
                <div class="progress-card p-6 rounded-lg shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class='bx bx-calendar-week text-yellow-500 text-2xl mr-3'></i>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">This Week</h3>
                                <p class="text-sm text-gray-300">Last 7 Days</p>
                            </div>
                        </div>
                        <?php 
                        $weekly_trend = $workout_analytics['weekly_workouts'] >= 3 ? 'trend-up' : 
                                       ($workout_analytics['weekly_workouts'] > 0 ? 'trend-neutral' : 'trend-down');
                        ?>
                        <span class="text-sm font-medium px-3 py-1 rounded-full <?php echo $weekly_trend; ?>">
                            <?php echo $workout_analytics['weekly_workouts'] >= 3 ? 'Good' : ($workout_analytics['weekly_workouts'] > 0 ? 'Average' : 'Start'); ?>
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Workouts Completed</p>
                            <p class="stat-value text-3xl font-bold"><?php echo $workout_analytics['weekly_workouts']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Total Duration</p>
                            <p class="stat-value text-2xl font-bold"><?php echo $workout_analytics['weekly_duration_min']; ?> min</p>
                        </div>
                    </div>
                </div>
                
                <!-- This Month -->
                <div class="progress-card p-6 rounded-lg shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class='bx bx-calendar text-yellow-500 text-2xl mr-3'></i>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">This Month</h3>
                                <p class="text-sm text-gray-300">Current Month</p>
                            </div>
                        </div>
                        <?php 
                        $monthly_trend = $workout_analytics['monthly_workouts'] >= 12 ? 'trend-up' : 
                                        ($workout_analytics['monthly_workouts'] >= 8 ? 'trend-neutral' : 'trend-down');
                        ?>
                        <span class="text-sm font-medium px-3 py-1 rounded-full <?php echo $monthly_trend; ?>">
                            <?php echo $workout_analytics['monthly_workouts'] >= 12 ? 'Excellent' : ($workout_analytics['monthly_workouts'] >= 8 ? 'Good' : 'Progress'); ?>
                        </span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Workouts Completed</p>
                            <p class="stat-value text-3xl font-bold"><?php echo $workout_analytics['monthly_workouts']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Total Duration</p>
                            <p class="stat-value text-2xl font-bold"><?php echo $workout_analytics['monthly_duration_min']; ?> min</p>
                        </div>
                    </div>
                </div>
                
                <!-- All Time -->
                <div class="progress-card p-6 rounded-lg shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <i class='bx bx-trophy text-yellow-500 text-2xl mr-3'></i>
                            <div>
                                <h3 class="text-lg font-semibold mb-1">All Time</h3>
                                <p class="text-sm text-gray-300">Total Progress</p>
                            </div>
                        </div>
                        <span class="text-sm font-medium px-3 py-1 rounded-full bg-yellow-500 bg-opacity-20 text-yellow-300">Total</span>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Total Workouts</p>
                            <p class="stat-value text-3xl font-bold"><?php echo $workout_analytics['total_workouts']; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-300 mb-1">Average Duration</p>
                            <p class="stat-value text-2xl font-bold"><?php echo $workout_analytics['avg_duration_min']; ?> min</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="chart-grid grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Weekly Activity Chart -->
                <section>
                    <h2 class="section-heading text-xl font-semibold mb-4 text-black flex items-center">
                        <i class='bx bx-calendar-week mr-2 text-yellow-600'></i> Weekly Activity Breakdown
                    </h2>
                    <div class="chart-container">
                        <div id="weeklyChartLoading" class="chart-loading"></div>
                        <canvas id="weeklyActivityChart" class="h-80"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-7 gap-2">
                        <?php 
                        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        $frequency = $workout_analytics['frequency'];
                        $max_freq = max($frequency);
                        
                        foreach ($days as $day) {
                            $full_day = $day == 'Mon' ? 'Monday' : 
                                       ($day == 'Tue' ? 'Tuesday' : 
                                       ($day == 'Wed' ? 'Wednesday' : 
                                       ($day == 'Thu' ? 'Thursday' : 
                                       ($day == 'Fri' ? 'Friday' : 
                                       ($day == 'Sat' ? 'Saturday' : 'Sunday')))));
                            $count = $frequency[$full_day] ?? 0;
                            $percentage = $max_freq > 0 ? ($count / $max_freq * 100) : 0;
                        ?>
                        <div class="day-cell text-center <?php echo $count > 0 ? 'bg-yellow-50' : ''; ?>">
                            <div class="text-sm font-medium text-gray-600 mb-1"><?php echo $day; ?></div>
                            <div class="text-lg font-bold <?php echo $count > 0 ? 'text-yellow-600' : 'text-gray-400'; ?>">
                                <?php echo $count; ?>
                            </div>
                            <div class="progress-bar-container mt-2">
                                <div class="progress-bar-fill" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </section>

                <!-- Monthly Progress Chart -->
                <section>
                    <h2 class="section-heading text-xl font-semibold mb-4 text-black flex items-center">
                        <i class='bx bx-trending-up mr-2 text-yellow-600'></i> Monthly Progress Trend
                    </h2>
                    <div class="chart-container">
                        <div id="monthlyChartLoading" class="chart-loading"></div>
                        <canvas id="monthlyProgressChart" class="h-80"></canvas>
                    </div>
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="metric-card">
                            <div class="flex items-center mb-2">
                                <i class='bx bx-bar-chart-alt text-yellow-600 text-xl mr-2'></i>
                                <h4 class="font-semibold text-gray-700">Monthly Average</h4>
                            </div>
                            <?php
                            $monthly_data = $workout_analytics['monthly_trend'];
                            $workout_counts = array_column($monthly_data, 'workouts');
                            $average = count($workout_counts) > 0 ? array_sum($workout_counts) / count($workout_counts) : 0;
                            ?>
                            <p class="text-3xl font-bold text-black"><?php echo round($average, 1); ?></p>
                            <p class="text-sm text-gray-500">workouts per month</p>
                        </div>
                        <div class="metric-card">
                            <div class="flex items-center mb-2">
                                <i class='bx bx-time text-yellow-600 text-xl mr-2'></i>
                                <h4 class="font-semibold text-gray-700">Consistency Score</h4>
                            </div>
                            <?php
                            $non_zero_months = array_filter($workout_counts, function($count) { return $count > 0; });
                            $consistency = count($monthly_data) > 0 ? (count($non_zero_months) / count($monthly_data) * 100) : 0;
                            ?>
                            <p class="text-3xl font-bold text-black"><?php echo round($consistency); ?>%</p>
                            <p class="text-sm text-gray-500">active months</p>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Daily Activity Heatmap -->
            <section class="mb-10">
                <h2 class="section-heading text-xl font-semibold mb-6 text-black flex items-center">
                    <i class='bx bx-calendar mr-2 text-yellow-600'></i> Last 7 Days Activity
                </h2>
                <div class="chart-container">
                    <div id="dailyChartLoading" class="chart-loading"></div>
                    <canvas id="dailyActivityChart" class="h-64"></canvas>
                </div>
            </section>
            
            <!-- Key Metrics Section -->
            <section class="mb-10">
                <h2 class="section-heading text-xl font-semibold mb-6 text-black flex items-center">
                    <i class='bx bx-stats mr-2 text-yellow-600'></i> Key Performance Metrics
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="metric-card">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                                <i class='bx bx-timer text-yellow-600 text-2xl'></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-700">Workout Frequency</h4>
                                <p class="text-sm text-gray-500">Workouts per week</p>
                            </div>
                        </div>
                        <?php 
                        $weekly_freq = $workout_analytics['weekly_workouts'];
                        $target = 3; // Recommended 3 workouts per week
                        $achievement = ($weekly_freq / $target) * 100;
                        if ($achievement > 100) $achievement = 100;
                        ?>
                        <div class="mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-2xl font-bold text-black"><?php echo $weekly_freq; ?>/<?php echo $target; ?></span>
                                <span class="text-sm font-medium <?php echo $weekly_freq >= $target ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo round($achievement); ?>%
                                </span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $achievement; ?>%"></div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php 
                                if ($weekly_freq >= $target) {
                                    echo "Great! You're meeting the recommended frequency.";
                                } elseif ($weekly_freq > 0) {
                                    echo "Good progress! Aim for $target workouts per week.";
                                } else {
                                    echo "Start with at least 1 workout this week.";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                                <i class='bx bx-time-five text-yellow-600 text-2xl'></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-700">Average Duration</h4>
                                <p class="text-sm text-gray-500">Minutes per workout</p>
                            </div>
                        </div>
                        <?php 
                        $avg_duration = $workout_analytics['avg_duration_min'];
                        $duration_target = 45; // Recommended 45 minutes per workout
                        $duration_achievement = ($avg_duration / $duration_target) * 100;
                        if ($duration_achievement > 100) $duration_achievement = 100;
                        ?>
                        <div class="mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-2xl font-bold text-black"><?php echo $avg_duration; ?> min</span>
                                <span class="text-sm font-medium <?php echo $avg_duration >= $duration_target ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo round($duration_achievement); ?>%
                                </span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $duration_achievement; ?>%"></div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php 
                                if ($avg_duration >= $duration_target) {
                                    echo "Excellent! You're getting quality workouts.";
                                } elseif ($avg_duration > 0) {
                                    echo "Good! Aim for $duration_target minutes per session.";
                                } else {
                                    echo "Track your workout duration to see progress.";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="metric-card">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center mr-4">
                                <i class='bx bx-calendar-star text-yellow-600 text-2xl'></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-700">Consistency</h4>
                                <p class="text-sm text-gray-500">Workout days vs total days</p>
                            </div>
                        </div>
                        <?php 
                        $total_days = $workout_analytics['total_workout_days'];
                        $total_possible = $workout_analytics['total_workouts'] > 0 ? $total_days * 3 : 1; // Estimate
                        $consistency_score = $total_possible > 0 ? ($total_days / $total_possible * 100) : 0;
                        if ($consistency_score > 100) $consistency_score = 100;
                        ?>
                        <div class="mt-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-2xl font-bold text-black"><?php echo $total_days; ?> days</span>
                                <span class="text-sm font-medium <?php echo $consistency_score >= 70 ? 'text-green-600' : 'text-yellow-600'; ?>">
                                    <?php echo round($consistency_score); ?>%
                                </span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: <?php echo $consistency_score; ?>%"></div>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                <?php 
                                if ($consistency_score >= 70) {
                                    echo "Excellent consistency! Keep it up.";
                                } elseif ($consistency_score > 0) {
                                    echo "Good start! Try to workout more consistently.";
                                } else {
                                    echo "Start building your workout habit.";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Insights Section -->
            <section class="mt-8">
                <h2 class="section-heading text-xl font-semibold mb-6 text-black flex items-center">
                    <i class='bx bx-bulb mr-2 text-yellow-600'></i> Personalized Insights
                </h2>
                <div id="insights-container" class="insight-cards-grid space-y-6">
                    <!-- Insights will be populated by JavaScript -->
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    // Mobile navigation JavaScript (same as WorkoutJournal.php)
    document.addEventListener('DOMContentLoaded', function() {
        // Hamburger menu toggle
        const navbarToggle = document.getElementById('navbarToggle');
        const navbarMenu = document.getElementById('navbarMenu');
        
        if (navbarToggle && navbarMenu) {
            navbarToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                navbarMenu.classList.toggle('active');
                this.classList.toggle('active');
                
                const icon = this.querySelector('i');
                if (navbarMenu.classList.contains('active')) {
                    icon.classList.remove('bx-menu');
                    icon.classList.add('bx-x');
                } else {
                    icon.classList.remove('bx-x');
                    icon.classList.add('bx-menu');
                }
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                const isMobile = window.innerWidth <= 1024;
                const isClickInsideNavbar = navbarMenu.contains(event.target) || 
                                           navbarToggle.contains(event.target);
                
                if (isMobile && !isClickInsideNavbar && navbarMenu.classList.contains('active')) {
                    navbarMenu.classList.remove('active');
                    navbarToggle.classList.remove('active');
                    const icon = navbarToggle.querySelector('i');
                    icon.classList.remove('bx-x');
                    icon.classList.add('bx-menu');
                }
            });
        }
        
        // Desktop sidebar submenu
        const moreToggle = document.querySelector('.more-toggle');
        const desktopSubmenu = document.getElementById('desktopSubmenu');
        const desktopToggleIcon = document.querySelector('.more-menu .toggle-icon');

        if (moreToggle && desktopSubmenu && desktopToggleIcon) {
            moreToggle.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                
                if (desktopSubmenu.style.maxHeight === '0px' || desktopSubmenu.style.maxHeight === '') {
                    desktopSubmenu.style.maxHeight = desktopSubmenu.scrollHeight + 'px'; 
                    desktopToggleIcon.style.transform = 'rotate(180deg)';
                } else {
                    desktopSubmenu.style.maxHeight = '0px';
                    desktopToggleIcon.style.transform = 'rotate(0deg)';
                }
            });
        }
        
        // Mobile submenu toggle
        const moreToggleMobile = document.querySelector('.more-toggle-mobile');
        const mobileSubmenu = document.getElementById('mobileSubmenu');
        const mobileToggleIcon = document.querySelector('.more-menu-mobile .toggle-icon');
        
        if (moreToggleMobile && mobileSubmenu && mobileToggleIcon) {
            moreToggleMobile.addEventListener('click', function(e) {
                e.preventDefault(); 
                e.stopPropagation();
                
                const isOpen = mobileSubmenu.style.maxHeight && mobileSubmenu.style.maxHeight !== '0px';
                
                if (isOpen) {
                    mobileSubmenu.style.maxHeight = '0px';
                    mobileToggleIcon.style.transform = 'rotate(0deg)';
                } else {
                    mobileSubmenu.style.maxHeight = mobileSubmenu.scrollHeight + 'px'; 
                    mobileToggleIcon.style.transform = 'rotate(180deg)';
                }
            });
        }
        
        // Initialize charts and insights
        initializeProgressDashboard();
    });

    // PHP data passed to JavaScript
    const analyticsData = <?php echo json_encode($workout_analytics); ?>;

    function initializeProgressDashboard() {
        // Create all charts
        createWeeklyActivityChart();
        createMonthlyProgressChart();
        createDailyActivityChart();
        
        // Generate insights
        generateInsights();
    }

    function createWeeklyActivityChart() {
        const dailyData = analyticsData.daily;
        const labels = Object.keys(dailyData);
        const workoutCounts = labels.map(date => dailyData[date].workouts);
        const durationMins = labels.map(date => dailyData[date].duration_min);
        
        // Format dates for display
        const formattedLabels = labels.map(date => {
            const d = new Date(date);
            return d.toLocaleDateString('en-US', { weekday: 'short' });
        });
        
        const ctx = document.getElementById('weeklyActivityChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: formattedLabels,
                datasets: [
                    {
                        label: 'Workouts',
                        data: workoutCounts,
                        backgroundColor: '#FFD700',
                        borderColor: '#e6c200',
                        borderWidth: 2,
                        borderRadius: 6,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Duration (min)',
                        data: durationMins,
                        type: 'line',
                        fill: false,
                        borderColor: '#000000',
                        backgroundColor: '#000000',
                        borderWidth: 3,
                        tension: 0.4,
                        pointBackgroundColor: '#000000',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#000000',
                            usePointStyle: true,
                            padding: 20,
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFD700',
                        bodyColor: '#ffffff',
                        borderColor: '#FFD700',
                        borderWidth: 1,
                        callbacks: {
                            title: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                const date = new Date(labels[index]);
                                return date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric' });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#666666',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#666666',
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Number of Workouts',
                            color: '#000000',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false
                        },
                        ticks: {
                            color: '#666666'
                        },
                        title: {
                            display: true,
                            text: 'Duration (minutes)',
                            color: '#000000',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
        
        // Hide loading indicator
        document.getElementById('weeklyChartLoading').style.display = 'none';
        return chart;
    }

    function createMonthlyProgressChart() {
        const monthlyData = analyticsData.monthly_trend;
        const labels = Object.keys(monthlyData);
        const workoutCounts = labels.map(month => monthlyData[month].workouts);
        
        // Format month labels
        const formattedLabels = labels.map(label => {
            const [year, month] = label.split('-');
            const date = new Date(year, month - 1);
            return date.toLocaleDateString('en-US', { month: 'short' });
        });
        
        const ctx = document.getElementById('monthlyProgressChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: formattedLabels,
                datasets: [{
                    label: 'Workouts per Month',
                    data: workoutCounts,
                    backgroundColor: labels.map((label, index) => {
                        // Use different shades for visual variety
                        return index === labels.length - 1 ? '#FFD700' : // Current month - bright yellow
                               index === labels.length - 2 ? '#FFED4E' : // Last month - lighter yellow
                               'rgba(255, 215, 0, 0.6)'; // Older months - semi-transparent
                    }),
                    borderColor: '#000000',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFD700',
                        bodyColor: '#ffffff',
                        borderColor: '#FFD700',
                        borderWidth: 1,
                        callbacks: {
                            title: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                const [year, month] = labels[index].split('-');
                                const date = new Date(year, month - 1);
                                return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#666666',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#666666',
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Number of Workouts',
                            color: '#000000',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
        
        // Hide loading indicator
        document.getElementById('monthlyChartLoading').style.display = 'none';
        return chart;
    }

    function createDailyActivityChart() {
        const dailyData = analyticsData.daily;
        const labels = Object.keys(dailyData);
        const workoutCounts = labels.map(date => dailyData[date].workouts);
        
        // Create gradient for heatmap effect
        const ctx = document.getElementById('dailyActivityChart').getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, '#FFED4E');
        gradient.addColorStop(1, '#FFD700');
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.map(date => {
                    const d = new Date(date);
                    return d.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'Daily Workouts',
                    data: workoutCounts,
                    backgroundColor: gradient,
                    borderColor: '#000000',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#000000',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFD700',
                        bodyColor: '#ffffff',
                        borderColor: '#FFD700',
                        borderWidth: 1,
                        callbacks: {
                            title: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                const date = new Date(labels[index]);
                                return date.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#666666',
                            font: {
                                size: 11,
                                weight: 'bold'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            color: '#666666',
                            stepSize: 1,
                            precision: 0
                        },
                        title: {
                            display: true,
                            text: 'Workouts',
                            color: '#000000',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    }
                }
            }
        });
        
        // Hide loading indicator
        document.getElementById('dailyChartLoading').style.display = 'none';
        return chart;
    }

    function generateInsights() {
        const container = document.getElementById('insights-container');
        container.innerHTML = '';
        
        const totalWorkouts = analyticsData.total_workouts;
        const weeklyWorkouts = analyticsData.weekly_workouts;
        const monthlyWorkouts = analyticsData.monthly_workouts;
        const avgDuration = analyticsData.avg_duration_min;
        const frequency = analyticsData.frequency;
        
        if (totalWorkouts === 0) {
            container.innerHTML = `
                <div class="insight-card p-6">
                    <div class="flex items-center mb-4">
                        <i class='bx bx-bulb text-yellow-600 text-2xl mr-3'></i>
                        <h3 class="text-lg font-semibold">Welcome to Your Fitness Journey!</h3>
                    </div>
                    <p class="text-gray-700 mb-4">Start tracking your workouts to unlock personalized insights and progress analytics.</p>
                    <div class="space-y-3">
                        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i> 
                        <span>Complete your first workout to begin tracking progress</span></p>
                        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i> 
                        <span>Set achievable goals in your Workout Journal</span></p>
                        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-600 mr-2 mt-1'></i> 
                        <span>Track consistency to build lasting habits</span></p>
                    </div>
                    <button onclick="window.location.href='WorkoutJournal.php'" 
                            class="mt-4 bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium flex items-center">
                        <i class='bx bx-plus mr-2'></i> Start Your First Workout
                    </button>
                </div>
            `;
            return;
        }
        
        // Calculate insights
        const insights = [];
        
        // Weekly frequency insight
        if (weeklyWorkouts >= 4) {
            insights.push({
                type: 'success',
                icon: 'bx-trending-up',
                title: 'Excellent Weekly Frequency',
                message: `You're averaging ${weeklyWorkouts} workouts per week. This is above the recommended 3 workouts per week for optimal results.`,
                action: 'Keep up the great work!'
            });
        } else if (weeklyWorkouts >= 2) {
            insights.push({
                type: 'info',
                icon: 'bx-check-circle',
                title: 'Good Weekly Routine',
                message: `You're averaging ${weeklyWorkouts} workouts per week. Consider adding one more session to reach the recommended 3 workouts per week.`,
                action: 'Aim for consistency'
            });
        } else {
            insights.push({
                type: 'warning',
                icon: 'bx-calendar-plus',
                title: 'Increase Weekly Activity',
                message: `You're averaging ${weeklyWorkouts} workout${weeklyWorkouts !== 1 ? 's' : ''} per week. Try to schedule at least 2-3 workouts weekly for better progress.`,
                action: 'Plan your workouts'
            });
        }
        
        // Duration insight
        if (avgDuration >= 45) {
            insights.push({
                type: 'success',
                icon: 'bx-time-five',
                title: 'Optimal Workout Duration',
                message: `Your average workout duration of ${avgDuration} minutes is excellent for building strength and endurance.`,
                action: 'Maintain this intensity'
            });
        } else if (avgDuration >= 30) {
            insights.push({
                type: 'info',
                icon: 'bx-timer',
                title: 'Good Workout Duration',
                message: `Your average workout of ${avgDuration} minutes is effective. Consider extending to 45 minutes for optimal results.`,
                action: 'Increase duration gradually'
            });
        } else {
            insights.push({
                type: 'warning',
                icon: 'bx-time',
                title: 'Short Workout Sessions',
                message: `Your average workout duration of ${avgDuration} minutes is below the recommended 45 minutes.`,
                action: 'Focus on quality over quantity'
            });
        }
        
        // Monthly progress insight
        if (monthlyWorkouts >= 12) {
            insights.push({
                type: 'success',
                icon: 'bx-trophy',
                title: 'Outstanding Monthly Progress',
                message: `You've completed ${monthlyWorkouts} workouts this month! This shows exceptional commitment to your fitness goals.`,
                action: 'Set new challenges'
            });
        } else if (monthlyWorkouts >= 8) {
            insights.push({
                type: 'info',
                icon: 'bx-bar-chart-alt',
                title: 'Steady Monthly Progress',
                message: `${monthlyWorkouts} workouts this month is a solid achievement. You're building consistent habits.`,
                action: 'Stay consistent'
            });
        } else {
            insights.push({
                type: 'warning',
                icon: 'bx-calendar',
                title: 'Monthly Activity Level',
                message: `You've completed ${monthlyWorkouts} workout${monthlyWorkouts !== 1 ? 's' : ''} this month.`,
                action: 'Aim for 8+ workouts monthly'
            });
        }
        
        // Consistency insight (best workout day)
        const maxDay = Object.keys(frequency).reduce((a, b) => frequency[a] > frequency[b] ? a : b);
        const maxCount = frequency[maxDay];
        
        if (maxCount >= 10) {
            insights.push({
                type: 'success',
                icon: 'bx-star',
                title: 'Strong Workout Habit',
                message: `${maxDay}s are your most active day with ${maxCount} workouts. This shows excellent consistency.`,
                action: 'Leverage this strength'
            });
        } else if (maxCount >= 5) {
            insights.push({
                type: 'info',
                icon: 'bx-calendar-star',
                title: 'Preferred Workout Day',
                message: `You workout most often on ${maxDay}s (${maxCount} times). Consider making this your primary workout day.`,
                action: 'Build around your schedule'
            });
        }
        
        // Total workouts milestone
        if (totalWorkouts >= 50) {
            insights.push({
                type: 'success',
                icon: 'bx-award',
                title: 'Fitness Milestone Achieved!',
                message: `You've completed ${totalWorkouts} total workouts. This is a significant achievement in your fitness journey.`,
                action: 'Celebrate your progress'
            });
        } else if (totalWorkouts >= 25) {
            insights.push({
                type: 'info',
                icon: 'bx-flag',
                title: 'Progress Milestone',
                message: `${totalWorkouts} workouts completed! You're building a strong foundation.`,
                action: 'Aim for 50 workouts'
            });
        }
        
        // Render insights
        const insightsHtml = insights.map(insight => `
            <div class="insight-card p-6 ${insight.type === 'success' ? 'border-green-200' : 
                                                 insight.type === 'warning' ? 'border-yellow-200' : 
                                                 'border-yellow-400'}">
                <div class="flex items-start mb-4">
                    <div class="w-10 h-10 rounded-full ${insight.type === 'success' ? 'bg-green-100' : 
                                                           insight.type === 'warning' ? 'bg-yellow-100' : 
                                                           'bg-yellow-200'} flex items-center justify-center mr-4 flex-shrink-0">
                        <i class='${insight.icon} ${insight.type === 'success' ? 'text-green-600' : 
                                                   insight.type === 'warning' ? 'text-yellow-600' : 
                                                   'text-yellow-600'} text-xl'></i>
                    </div>
                    <div class="flex-grow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-1">${insight.title}</h3>
                        <p class="text-gray-600 mb-2">${insight.message}</p>
                        <p class="text-sm font-medium ${insight.type === 'success' ? 'text-green-600' : 
                                                         insight.type === 'warning' ? 'text-yellow-600' : 
                                                         'text-yellow-600'}">
                            <i class='bx bx-check mr-1'></i> ${insight.action}
                        </p>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = insightsHtml;
        
        // Add summary card if we have insights
        if (insights.length > 0) {
            const summaryCard = `
<div class="insight-card p-6 bg-gradient-to-r from-black to-gray-800 text-white">
    <div class="flex items-center mb-4">
        <i class='bx bx-target-lock text-yellow-500 text-2xl mr-3'></i>
        <h3 class="text-lg font-semibold text-white">Your Fitness Summary</h3>
    </div>
    <div class="space-y-3">
        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-500 mr-2 mt-1'></i> 
        <span class="text-white">${totalWorkouts} total workouts completed</span></p>
        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-500 mr-2 mt-1'></i> 
        <span class="text-white">Average ${weeklyWorkouts} workouts per week</span></p>
        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-500 mr-2 mt-1'></i> 
        <span class="text-white">${avgDuration} minute average workout duration</span></p>
        <p class="flex items-start"><i class='bx bx-check-circle text-yellow-500 mr-2 mt-1'></i> 
        <span class="text-white">${monthlyWorkouts} workouts this month</span></p>
    </div>
    <div class="mt-4 pt-4 border-t border-gray-700">
        <p class="text-yellow-300 font-medium">
            <i class='bx bx-up-arrow-alt mr-1'></i> 
            ${weeklyWorkouts >= 3 && avgDuration >= 45 ? 'Excellent progress! Keep maintaining your routine.' :
              weeklyWorkouts >= 2 ? 'Good foundation! Focus on increasing frequency and duration.' :
              'Start strong! Aim for consistency in your workouts.'}
        </p>
    </div>
</div>
`;

container.innerHTML += summaryCard;
        }
    }
</script>
</body>
</html>