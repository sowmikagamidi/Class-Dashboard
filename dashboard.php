<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "School Dashboard";
include('config.php');

if (!isset($tutorix_user_type) || ($tutorix_user_type != 'A' && $tutorix_user_type != 'S')) {
    echo "Unauthorized access. User type: " . ($tutorix_user_type ?? 'not set');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Dashboard | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f4f3f6 0%, #f2f1f3 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .dashboard-header {
            background: white;
            border-radius: 16px;
            padding: 15px 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .dashboard-header h1 {
            font-size: 22px;
            color: #1e293b;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .dashboard-header h1 i {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 8px;
            border-radius: 12px;
            color: white;
            font-size: 12px;
        }
        
        .filters-card {
            background: white;
            border-radius: 16px;
            padding: 15px 22px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 5px;
        }
        
        .filter-group select {
            padding: 8px 10px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .btn-reset {
            background: #f1f5f9;
            color: #475569;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 500;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
        }
        
        /* Top Stats Row - 5 cards in one line */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 12px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea15, #764ba215);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .stat-icon i {
            font-size: 20px;
            color: #667eea;
        }
        
        .stat-info {
            flex: 1;
        }
        
        .stat-title {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
            white-space: nowrap;
        }
        
        .stat-number {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
        }
        
        .card-header {
            padding: 12px 18px;
            border-bottom: 1px solid #f1f5f9;
            background: white;
        }
        
        .card-header h3 {
            font-size: 15px;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header h3 i {
            font-size: 16px;
        }
        
        .card-body {
            padding: 14px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .scrollable-container {
            max-height: 280px;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
        }
        
        .enrollment-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .enrollment-table-container {
            flex: 1;
            overflow-y: auto;
            overflow-x: auto;
            max-height: 280px;
        }
        
        .enrollment-table-container::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        
        .enrollment-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .enrollment-table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        .enrollment-footer {
            background: white;
            padding: 10px 0 0 0;
            margin-top: 10px;
            border-top: 1px solid #e2e8f0;
            position: sticky;
            bottom: 0;
            background: white;
            z-index: 20;
        }
        
        .enrollment-footer .table-footer {
            margin-top: 0;
            padding-top: 8px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        
        .data-table th {
            background: #f8fafc;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
            font-size: 12px;
        }
        
        .data-table td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #475569;
            font-size: 12px;
        }
        
        .data-table tr:hover td {
            background: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 500;
        }
        
        .badge-cbse { background: #e3f2fd; color: #1565c0; }
        .badge-icse { background: #e8f5e9; color: #2e7d32; }
        .badge-wbbse { background: #fff3e0; color: #e65100; }
        .badge-cambridge { background: #f3e5f5; color: #7b1fa2; }
        
        .badge-active { background: #dcfce7; color: #166534; }
        .badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-expiring { background: #fed7aa; color: #9a3412; }
        .badge-teacher { background: #e0e7ff; color: #3730a3; }
        .badge-lct { background: #fce7f3; color: #9d174d; }
        
        .doubts-stats {
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        
        .doubts-stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .doubts-stat-item:last-child {
            border-bottom: none;
        }
        
        .doubts-stat-label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
        }
        
        .doubts-stat-label i {
            margin-right: 8px;
            width: 18px;
            color: #f59e0b;
            font-size: 13px;
        }
        
        .doubts-stat-number {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .mini-stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 12px;
        }
        
        .mini-stat {
            background: #f8fafc;
            padding: 8px;
            border-radius: 12px;
            text-align: center;
        }
        
        .mini-stat .number {
            font-size: 18px;
            font-weight: 700;
        }
        
        .mini-stat .label {
            font-size: 10px;
            color: #64748b;
            margin-top: 3px;
        }
        
        .progress-section {
            margin-bottom: 12px;
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .progress-label {
            font-size: 12px;
            font-weight: 500;
            color: #475569;
        }
        
        .progress-percent {
            font-size: 12px;
            font-weight: 600;
            color: #667eea;
        }
        
        .progress-bar-container {
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            height: 5px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        
        .fill-teal { background: linear-gradient(90deg, #0d9488, #14b8a6); }
        .fill-yellow { background: linear-gradient(90deg, #ca8a04, #eab308); }
        .fill-purple { background: linear-gradient(90deg, #7c3aed, #8b5cf6); }
        
        .subject-item {
            padding: 8px 10px;
            margin-bottom: 8px;
            background: #f8fafc;
            border-radius: 10px;
        }
        
        .subject-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .subject-name {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        
        .subject-value {
            font-size: 12px;
            font-weight: 600;
        }
        
        .small-progress {
            height: 4px;
            background: #e2e8f0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .small-progress-fill {
            height: 100%;
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        .text-blue { color: #3b82f6; }
        .text-green { color: #10b981; }
        .text-purple { color: #8b5cf6; }
        .text-orange { color: #f97316; }
        .text-teal { color: #14b8a6; }
        
        .empty-state {
            text-align: center;
            padding: 25px;
            color: #94a3b8;
            font-size: 12px;
        }
        
        .filter-info {
            background: #eff6ff;
            padding: 6px 10px;
            border-radius: 8px;
            margin-bottom: 10px;
            font-size: 11px;
            color: #1e40af;
        }
        
        /* Tabs for Student Status only */
        .status-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        
        .status-tab {
            padding: 5px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 11px;
            font-weight: 500;
            background: #f1f5f9;
            color: #475569;
            transition: all 0.2s;
        }
        
        .status-tab.active {
            background: #667eea;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .table-footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .total-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        
        .wrap_loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 1200px) {
            .stats-row {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 1100px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            body { padding: 10px; }
            .stats-row { grid-template-columns: 1fr; }
            .filters-grid { grid-template-columns: 1fr; }
            .button-group { width: 100%; }
            .btn-apply, .btn-reset { flex: 1; justify-content: center; }
        }
    </style>
</head>
<body>

<div class="wrap_loader">
    <div class="loader"></div>
</div>

<div class="container">
    <div class="dashboard-header">
        <h1><i class="fas fa-chalkboard-user"></i> School Dashboard</h1>
    </div>
    
    <div class="filters-card">
        <div class="filters-grid">
            <div class="filter-group">
                <label><i class="fas fa-calendar"></i> Academic Year</label>
                <select id="academicYear">
                    <option value="2024">2024-2025</option>
                    <option value="2025">2025-2026</option>
                    <option value="2026" selected>2026-2027</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Board</label>
                <select id="board">
                    <option value="">All Boards</option>
                    <option value="C">CBSE</option>
                    <option value="I">ICSE</option>
                    <option value="W">WBBSE</option>
                    <option value="K">Cambridge</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-graduation-cap"></i> Class</label>
                <select id="class">
                    <option value="">All Classes</option>
                    <option value="6">Class 6</option>
                    <option value="7">Class 7</option>
                    <option value="8">Class 8</option>
                    <option value="9">Class 9</option>
                    <option value="10">Class 10</option>
                    <option value="11">Class 11</option>
                    <option value="12">Class 12</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-layer-group"></i> Section</label>
                <select id="batch">
                    <option value="">All Sections</option>
                </select>
            </div>
            <div class="button-group">
                <button id="applyBtn" class="btn-apply"><i class="fas fa-search"></i> Search</button>
                <button id="resetBtn" class="btn-reset"><i class="fas fa-undo-alt"></i> Reset</button>
            </div>
        </div>
    </div>
    
    <!-- Top Stats Row - 5 Stats in one line -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <div class="stat-title">Total Students</div>
                <div class="stat-number" id="totalStudents">0</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chalkboard-user"></i></div>
            <div class="stat-info">
                <div class="stat-title">Teachers & Mentors</div>
                <div class="stat-number" id="totalMentors">0</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-info">
                <div class="stat-title">Avg Attendance</div>
                <div class="stat-number" id="avgAttendanceStat">0<span style="font-size:16px">%</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <div class="stat-title">Overall Progress</div>
                <div class="stat-number" id="avgProgressStat">0<span style="font-size:14px">hrs</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-trophy"></i></div>
            <div class="stat-info">
                <div class="stat-title">Overall Performance</div>
                <div class="stat-number" id="overallPerformanceStat">0<span style="font-size:16px">%</span></div>
            </div>
        </div>
    </div>
    
    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Enrollment Overview -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-table-list" style="color: #667eea;"></i> Enrollment Overview</h3>
            </div>
            <div class="card-body">
                <div class="enrollment-wrapper">
                    <div class="enrollment-table-container" id="enrollmentTableContainer">
                        <div class="empty-state">Loading enrollment data...</div>
                    </div>
                    <div class="enrollment-footer" id="enrollmentFooter" style="display: none;">
                        <div class="table-footer">
                            <span class="total-badge" id="totalGroupsBadge">Total Groups: 0</span>
                            <span class="total-badge" id="totalStudentsBadge">Total Students: 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Status Overview -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-user-graduate" style="color: #10b981;"></i> Student Status Overview</h3>
            </div>
            <div class="card-body">
                <div class="mini-stats-row">
                    <div class="mini-stat">
                        <div class="number" style="color: #10b981;" id="activeStudents">0</div>
                        <div class="label">Active Students</div>
                    </div>
                    <div class="mini-stat">
                        <div class="number" style="color: #ef4444;" id="inactiveStudents">0</div>
                        <div class="label">Inactive Students</div>
                    </div>
                    <div class="mini-stat">
                        <div class="number" style="color: #f97316;" id="expiringStudents">0</div>
                        <div class="label">Expiring Soon</div>
                    </div>
                </div>
                <div id="studentFilterInfo" class="filter-info" style="display: none;"></div>
                <div class="status-tabs">
                    <div class="status-tab active" data-tab="active-students">Active</div>
                    <div class="status-tab" data-tab="inactive-students">Inactive</div>
                    <div class="status-tab" data-tab="expiring-students">Expiring Soon</div>
                </div>
                <div id="active-students" class="tab-content active">
                    <div class="scrollable-container" id="activeStudentsContainer">
                        <div class="empty-state">Loading active students...</div>
                    </div>
                </div>
                <div id="inactive-students" class="tab-content">
                    <div class="scrollable-container" id="inactiveStudentsContainer">
                        <div class="empty-state">Loading inactive students...</div>
                    </div>
                </div>
                <div id="expiring-students" class="tab-content">
                    <div class="scrollable-container" id="expiringStudentsContainer">
                        <div class="empty-state">Loading expiring students...</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teacher & Mentor Overview - SIMPLIFIED (No tabs, just one table) -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chalkboard-user" style="color: #8b5cf6;"></i> Teacher & Mentor Overview</h3>
            </div>
            <div class="card-body">
                
                <div id="teacherFilterInfo" class="filter-info" style="display: none;"></div>
                
                <!-- Single table for all teachers -->
                <div class="scrollable-container" id="allTeachersContainer">
                    <div class="empty-state">Loading teachers...</div>
                </div>
            </div>
        </div>
        
        <!-- Average Attendance -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-check" style="color: #8b5cf6;"></i> Average Attendance</h3>
            </div>
            <div class="card-body">
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label"><i class="fas fa-percent"></i> Overall Attendance</span>
                        <span class="progress-percent" id="overallAttendance">0%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-fill fill-purple" id="attendanceBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="scrollable-container" id="subjectAttendance">
                    <div class="empty-state">Loading data...</div>
                </div>
            </div>
        </div>
        
        <!-- Average Progress -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-line" style="color: #14b8a6;"></i> Average Progress</h3>
            </div>
            <div class="card-body">
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label"><i class="fas fa-clock"></i> Overall Progress</span>
                        <span class="progress-percent" id="overallProgressText">0 hrs</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-fill fill-teal" id="progressBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="scrollable-container" id="subjectProgress">
                    <div class="empty-state">Loading data...</div>
                </div>
            </div>
        </div>
        
        <!-- Average Performance -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-chart-bar" style="color: #f59e0b;"></i> Average Performance</h3>
            </div>
            <div class="card-body">
                <div class="progress-section">
                    <div class="progress-header">
                        <span class="progress-label"><i class="fas fa-percent"></i> Overall Performance</span>
                        <span class="progress-percent" id="overallPerformanceText">0%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-fill fill-yellow" id="performanceBar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="scrollable-container" id="subjectPerformance">
                    <div class="empty-state">Loading data...</div>
                </div>
            </div>
        </div>
        
        <!-- Student Doubts Analytics -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-question-circle" style="color: #f59e0b;"></i> Student Doubts Analytics</h3>
            </div>
            <div class="card-body">
                <div class="doubts-stats">
                    <div class="doubts-stat-item">
                        <span class="doubts-stat-label"><i class="fas fa-database"></i> Total Doubts</span>
                        <span class="doubts-stat-number" id="totalDoubts">0</span>
                    </div>
                    <div class="doubts-stat-item">
                        <span class="doubts-stat-label"><i class="fas fa-user"></i> Per Student Average</span>
                        <span class="doubts-stat-number" id="doubtsPerStudent">0</span>
                    </div>
                    <div class="doubts-stat-item">
                        <span class="doubts-stat-label"><i class="fas fa-chart-line"></i> Average Doubts (Mean)</span>
                        <span class="doubts-stat-number" id="doubtsMean">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var BASEURL = '<?php echo BASEURL; ?>';
var currentFilters = {
    academic_year: '2026',
    board_id: '',
    class_id: '',
    batch_id: ''
};

$(document).ready(function() {
    loadDashboardData();
    
    $('#applyBtn').click(function() {
        currentFilters.academic_year = $('#academicYear').val();
        currentFilters.board_id = $('#board').val();
        currentFilters.class_id = $('#class').val();
        currentFilters.batch_id = $('#batch').val();
        loadDashboardData();
    });
    
    $('#resetBtn').click(function() {
        $('#academicYear').val('2026');
        $('#board').val('');
        $('#class').val('');
        $('#batch').html('<option value="">All Sections</option>');
        currentFilters = { academic_year: '2026', board_id: '', class_id: '', batch_id: '' };
        loadDashboardData();
    });
    
    $('#class').change(function() {
        var classId = $(this).val();
        if(classId) loadBatches(classId);
        else $('#batch').html('<option value="">All Sections</option>');
    });
    
    // Student status tabs only (teacher tabs removed)
    $('.status-tab').click(function() {
        var parentCard = $(this).closest('.card');
        parentCard.find('.status-tab').removeClass('active');
        $(this).addClass('active');
        parentCard.find('.tab-content').removeClass('active');
        parentCard.find('#' + $(this).data('tab')).addClass('active');
    });
});

function loadBatches(classId) {
    $.ajax({
        url: BASEURL + '/ajax/txAjaxAdminDashboard.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getBatchesByClass',
            class_id: classId,
            academic_year: $('#academicYear').val(),
            board_id: $('#board').val()
        },
        success: function(response) {
            var select = $('#batch');
            select.html('<option value="">All Sections</option>');
            if(response.flag === 1 && response.data) {
                $.each(response.data, function(i, batch) {
                    select.append('<option value="' + batch.batch_id + '">Section ' + batch.section + '</option>');
                });
            }
        }
    });
}

function loadDashboardData() {
    $('.wrap_loader').show();
    $.ajax({
        url: BASEURL + '/ajax/txAjaxAdminDashboard.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'getDashboardData',
            academic_year: currentFilters.academic_year,
            board_id: currentFilters.board_id,
            class_id: currentFilters.class_id,
            batch_id: currentFilters.batch_id
        },
        success: function(response) {
            $('.wrap_loader').hide();
            if(response.flag === 1) updateDashboard(response.data);
            else showEmptyState();
        },
        error: function() { $('.wrap_loader').hide(); showEmptyState(); }
    });
}

function updateDashboard(data) {
    if(data.enrollment) {
        $('#totalStudents').text(data.enrollment.total_students || 0);
        $('#totalMentors').text(data.enrollment.total_mentors || 0);
    }
    
    // Update top stats row
    if(data.attendance) {
        $('#avgAttendanceStat').text((data.attendance.overall_attendance_percentage || 0) + '%');
    }
    
    if(data.progress) {
        $('#avgProgressStat').text(data.progress.total_hours || '0 hrs');
    }
    
    if(data.performance) {
        $('#overallPerformanceStat').text((data.performance.overall_percentage || 0) + '%');
    }
    
    if(data.doubts) {
        $('#totalDoubts').text(data.doubts.total_doubts || 0);
        $('#doubtsPerStudent').text(data.doubts.per_student || '0');
        $('#doubtsMean').text(data.doubts.mean_doubts || '0');
    }
    
    if(data.enrollment_table) updateEnrollmentTable(data.enrollment_table);
    if(data.teacher_mentor) updateTeacherMentorView(data.teacher_mentor);
    if(data.student_status) updateStudentStatusView(data.student_status);
    
    if(data.progress) {
        $('#overallProgressText').text(data.progress.total_hours || '0 hrs');
        $('#progressBar').css('width', (data.progress.percentage || 0) + '%');
        updateSubjectsList('#subjectProgress', data.progress.subjects, 'hours');
    }
    
    if(data.performance) {
        $('#overallPerformanceText').text((data.performance.overall_percentage || 0) + '%');
        $('#performanceBar').css('width', (data.performance.overall_percentage || 0) + '%');
        updateSubjectsList('#subjectPerformance', data.performance.subjects, 'percentage');
    }
    
    if(data.attendance) {
        $('#overallAttendance').text((data.attendance.overall_attendance_percentage || 0) + '%');
        $('#attendanceBar').css('width', (data.attendance.overall_attendance_percentage || 0) + '%');
        updateSubjectsList('#subjectAttendance', data.attendance.subjects, 'percentage');
    }
}

function updateEnrollmentTable(data) {
    var container = $('#enrollmentTableContainer');
    if (!data.data || data.data.length === 0) {
        container.html('<div class="empty-state">No enrollment data available</div>');
        $('#enrollmentFooter').hide();
        return;
    }
    
    $('#enrollmentFooter').show();
    $('#totalGroupsBadge').text('Total Groups: ' + (data.total_groups || 0));
    $('#totalStudentsBadge').text('Total Students: ' + (data.total_students || 0));
    
    var boardClass = {'C':'badge-cbse','I':'badge-icse','W':'badge-wbbse','K':'badge-cambridge'};
    var html = '<table class="data-table"><thead><tr><th>Board</th><th>Class</th><th>Section</th><th>Count</th></tr></thead><tbody>';
    $.each(data.data, function(i, row) {
        html += '<tr><td style="vertical-align: top;"><span class="badge ' + boardClass[row.board_id] + '">' + row.board_name + '</span></td>' +
                '<td style="vertical-align: top;">' + row.class_name + '</td>' +
                '<td style="vertical-align: top;">' + (row.section !== 'N/A' ? 'Section ' + row.section : 'All') + '</td>' +
                '<td style="vertical-align: top;" class="student-count">' + row.student_count + '</td>' +
                '</tr>';
    });
    html += '</tbody></table>';
    container.html(html);
}

// UPDATED: Simplified Teacher Mentor View - Single table, no tabs
function updateTeacherMentorView(data) {
    // Show total teachers count from teachers_list
    var teachersList = data.teachers_list || [];
    $('#totalTeachersCount').text(teachersList.length);
    
    var hasFilters = (currentFilters.class_id || (currentFilters.board_id != '') || currentFilters.batch_id);
    if (hasFilters) {
        var filterText = '';
        if (currentFilters.class_id) filterText += 'Class ' + currentFilters.class_id;
        if (currentFilters.board_id && currentFilters.board_id != '') filterText += (filterText ? ' | ' : '') + $('#board option:selected').text();
        if (currentFilters.batch_id) filterText += (filterText ? ' | ' : '') + 'Section ' + $('#batch option:selected').text();
        $('#teacherFilterInfo').html('<i class="fas fa-filter"></i> Filtered: ' + filterText).show();
    } else {
        $('#teacherFilterInfo').hide();
    }
    
    // Single table for all teachers
    var html = '<table class="data-table"><thead><tr>' +
                '<th>Name</th>' +
                '<th>Role</th>' +
                '<th>Section</th>' +
                '<th>Class</th>' +
                '<th>Board</th>' +
                '<th>Subject</th>' +
                '</thead><tbody>';
    
    if (teachersList.length === 0) {
        html += '<tr><td colspan="6" class="empty-state">No teachers found</td></tr>';
    } else {
        $.each(teachersList, function(i, teacher) {
            var roleBadge = '';
            if (teacher.role == 'Teacher') {
                roleBadge = '<span class="badge badge-teacher">Teacher</span>';
            } else if (teacher.role == 'Live Class Teacher') {
                roleBadge = '<span class="badge badge-lct">Live Class</span>';
            } else {
                roleBadge = '<span class="badge badge-teacher">' + (teacher.role || 'Staff') + '</span>';
            }
            
            var boardName = teacher.board_name || 'N/A';
            var boardClass = '';
            if (boardName == 'CBSE') boardClass = 'badge-cbse';
            else if (boardName == 'ICSE') boardClass = 'badge-icse';
            else if (boardName == 'WBBSE') boardClass = 'badge-wbbse';
            else if (boardName == 'Cambridge') boardClass = 'badge-cambridge';
            else boardClass = 'badge-other';
            
            html += '<tr>' +
                    '<td style="vertical-align: top;"><strong>' + escapeHtml(teacher.full_name) + '</strong></td>' +
                    '<td style="vertical-align: top;">' + roleBadge + '</td>' +
                    '<td style="vertical-align: top;">' + (teacher.section && teacher.section != 'N/A' ? 'Section ' + teacher.section : (teacher.batch_id ? 'Batch ' + teacher.batch_id : '—')) + '</td>' +
                    '<td style="vertical-align: top;">' + (teacher.class_display && teacher.class_display != 'Class ' ? teacher.class_display : (teacher.class_id ? 'Class ' + teacher.class_id : '—')) + '</td>' +
                    '<td style="vertical-align: top;"><span class="badge ' + boardClass + '">' + boardName + '</span></td>' +
                    '<td style="vertical-align: top;">' + (teacher.subject_name || '—') + '</td>' +
                    '</tr>';
        });
    }
    
    html += '</tbody></table>';
    $('#allTeachersContainer').html(html);
}

function updateStudentStatusView(data) {
    $('#activeStudents').text(data.active_count || 0);
    $('#inactiveStudents').text(data.inactive_count || 0);
    $('#expiringStudents').text(data.expiring_soon_count || 0);
    
    var hasFilters = (currentFilters.class_id || (currentFilters.board_id != '') || currentFilters.batch_id);
    if (hasFilters) {
        var filterText = '';
        if (currentFilters.class_id) filterText += 'Class ' + currentFilters.class_id;
        if (currentFilters.board_id && currentFilters.board_id != '') filterText += (filterText ? ' | ' : '') + $('#board option:selected').text();
        if (currentFilters.batch_id) filterText += (filterText ? ' | ' : '') + 'Section ' + $('#batch option:selected').text();
        $('#studentFilterInfo').html('<i class="fas fa-filter"></i> Filtered: ' + filterText).show();
    } else {
        $('#studentFilterInfo').hide();
    }
    
    var activeHtml = '<table class="data-table"><thead><tr><th>Name</th><th>Class</th><th>Board</th></tr></thead><tbody>';
    $.each(data.active_students || [], function(i, s) {
        var boardName = s.board_id == 'C' ? 'CBSE' : (s.board_id == 'I' ? 'ICSE' : (s.board_id == 'W' ? 'WBBSE' : (s.board_id == 'K' ? 'Cambridge' : (s.board_id || 'N/A'))));
        var boardClass = s.board_id == 'C' ? 'badge-cbse' : (s.board_id == 'I' ? 'badge-icse' : (s.board_id == 'W' ? 'badge-wbbse' : (s.board_id == 'K' ? 'badge-cambridge' : 'badge-other')));
        activeHtml += '<tr><td style="vertical-align: top;"><strong>' + escapeHtml(s.full_name) + '</strong></td>' +
                      '<td style="vertical-align: top;">' + (s.class_id ? 'Class ' + s.class_id : 'N/A') + '</td>' +
                      '<td style="vertical-align: top;"><span class="badge ' + boardClass + '">' + boardName + '</span></td>' +
                      '</tr>';
    });
    activeHtml += '</tbody></table>';
    $('#activeStudentsContainer').html(activeHtml || '<div class="empty-state">No active students</div>');
    
    var inactiveHtml = '<table class="data-table"><thead><tr><th>Name</th><th>Class</th><th>Board</th></tr></thead><tbody>';
    $.each(data.inactive_students || [], function(i, s) {
        var boardName = s.board_id == 'C' ? 'CBSE' : (s.board_id == 'I' ? 'ICSE' : (s.board_id == 'W' ? 'WBBSE' : (s.board_id == 'K' ? 'Cambridge' : (s.board_id || 'N/A'))));
        var boardClass = s.board_id == 'C' ? 'badge-cbse' : (s.board_id == 'I' ? 'badge-icse' : (s.board_id == 'W' ? 'badge-wbbse' : (s.board_id == 'K' ? 'badge-cambridge' : 'badge-other')));
        inactiveHtml += '<tr><td style="vertical-align: top;"><strong>' + escapeHtml(s.full_name) + '</strong></td>' +
                        '<td style="vertical-align: top;">' + (s.class_id ? 'Class ' + s.class_id : 'N/A') + '</td>' +
                        '<td style="vertical-align: top;"><span class="badge ' + boardClass + '">' + boardName + '</span></td>' +
                        '</tr>';
    });
    inactiveHtml += '</tbody></table>';
    $('#inactiveStudentsContainer').html(inactiveHtml || '<div class="empty-state">No inactive students</div>');
    
    var expiringHtml = '<table class="data-table"><thead><tr><th>Name</th><th>Class</th><th>Days Left</th></tr></thead><tbody>';
    $.each(data.expiring_soon_students || [], function(i, s) {
        expiringHtml += '<tr><td style="vertical-align: top;"><strong>' + escapeHtml(s.full_name) + '</strong></td>' +
                        '<td style="vertical-align: top;">' + (s.class_id ? 'Class ' + s.class_id : 'N/A') + '</td>' +
                        '<td style="vertical-align: top;"><span class="badge badge-expiring">' + (s.days_left || 'N/A') + ' days</span></td>' +
                        '</tr>';
    });
    expiringHtml += '</tbody></table>';
    $('#expiringStudentsContainer').html(expiringHtml || '<div class="empty-state">No expiring students</div>');
}

function updateSubjectsList(containerId, subjects, valueKey) {
    var container = $(containerId);
    if (!subjects || subjects.length === 0) {
        container.html('<div class="empty-state">No data available</div>');
        return;
    }
    
    var colors = ['#3b82f6', '#10b981', '#8b5cf6', '#f97316', '#14b8a6'];
    var html = '';
    $.each(subjects.slice(0, 6), function(i, subj) {
        var displayValue = valueKey === 'hours' ? subj.hours : subj.percentage + '%';
        html += '<div class="subject-item">' +
                    '<div class="subject-header">' +
                        '<span class="subject-name">' + subj.subject_name + '</span>' +
                        '<span class="subject-value" style="color: ' + colors[i % colors.length] + '">' + displayValue + '</span>' +
                    '</div>' +
                    '<div class="small-progress">' +
                        '<div class="small-progress-fill" style="width: ' + (subj.percentage || 0) + '%; background: ' + colors[i % colors.length] + ';"></div>' +
                    '</div>' +
                '</div>';
    });
    container.html(html);
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

function showEmptyState() {
    $('#enrollmentTableContainer').html('<div class="empty-state">No data available</div>');
    $('#enrollmentFooter').hide();
    $('#allTeachersContainer').html('<div class="empty-state">No data available</div>');
    $('#activeStudentsContainer').html('<div class="empty-state">No data available</div>');
    $('#inactiveStudentsContainer').html('<div class="empty-state">No data available</div>');
    $('#expiringStudentsContainer').html('<div class="empty-state">No data available</div>');
    $('#subjectProgress').html('<div class="empty-state">No data available</div>');
    $('#subjectPerformance').html('<div class="empty-state">No data available</div>');
    $('#subjectAttendance').html('<div class="empty-state">No data available</div>');
}
</script>

</body>
</html>