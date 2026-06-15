<?php
// Suppress all warnings and errors for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

include('../config.php');

// Check if user is admin
if ($tutorix_user_type != 'A' && $tutorix_user_type != 'S') {
    echo json_encode([ "flag" => -1,  "message" => "Unauthorized access"]);
    exit;
}

$post_data = $_POST;
$action = $post_data['action'] ?? '';

if (empty($school_id)) {
    echo json_encode(["flag" => -1, "message" => "Something went wrong"]);
    exit;
}

try {
    switch ($action) {
        case 'getBatchesByClass':
            $class_id = $post_data['class_id'] ?? null;
            $academic_year = $post_data['academic_year'] ?? null;
            $board_id = $post_data['board_id'] ?? '';
            
            if (empty($class_id)) {
                echo json_encode(["flag" => -1, "message" => "Class ID required"]);
                exit;
            }
            
            $sql = "SELECT DISTINCT batch_id, section, academic_year 
                    FROM TX_CLASS_BATCHES
                    WHERE school_id = $school_id 
                    AND class_id = $class_id";
            
            if (!empty($academic_year)) {
                $sql .= " AND academic_year = '$academic_year'";
            }
            if (!empty($board_id)) {
                $sql .= " AND board_id = '$board_id'";
            }
            $sql .= " ORDER BY section ASC";
            
            $data = $userObj->getFullQuery($sql);
            
            if (empty($data)) {
                echo json_encode(["flag" => -1, "message" => "No sections found"]);
            } else {
                echo json_encode(["flag" => 1, "message" => "Sections loaded", "data" => $data]);
            }
            break;

        case 'getDashboardData':
            $academic_year = $post_data['academic_year'] ?? date('Y');
            $class_id = $post_data['class_id'] ?? null;
            $batch_id = $post_data['batch_id'] ?? null;
            $board_id = $post_data['board_id'] ?? '';
            
            // Get valid students based on filters
            $validStudents = getAllValidStudents($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj);

            $dashboardData = [
                'enrollment'  => getEnrollmentStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents),
                'enrollment_table' => getEnrollmentTableData($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj),
                'teacher_mentor' => getTeacherMentorStats($school_id, $class_id, $batch_id, $board_id, $userObj),
                'student_status' => getStudentStatusStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj),
                'doubts'      => getDoubtsStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents),
                'progress'    => getProgressStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents),
                'performance' => getPerformanceStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents),
                'attendance'  => getAttendanceStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents)
            ];
            
            echo json_encode([
                "flag" => 1,
                "message" => "Dashboard data loaded",
                "data" => $dashboardData
            ]);
            break;

        default:
            echo json_encode(["flag" => -1, "message" => "Invalid action"]);
            break;
    }

} catch (Exception $e) {
    echo json_encode([
        "flag" => -1,
        "message" => $e->getMessage()
    ]);
}

$pdo = null;
exit;

// ============================================
// HELPER FUNCTIONS
// ============================================

function getAllValidStudents($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj) {
    
    $sql = "SELECT DISTINCT u.user_id, u.full_name, u.email_id, u.mobile_number, 
                   COALESCE(se.class_id, 0) as class_id, 
                   COALESCE(se.board_id, 'C') as board_id,
                   COALESCE(cb.section, '') as section,
                   COALESCE(sbm.batch_id, 0) as batch_id,
                   se.expiry_date,
                   u.user_status
            FROM USERS u
            LEFT JOIN TX_STUDENT_ENROLLMENT se ON u.user_id = se.student_id AND se.school_id = $school_id
            LEFT JOIN TX_STUDENT_BATCH_MAP sbm ON u.user_id = sbm.student_id
            LEFT JOIN TX_CLASS_BATCHES cb ON sbm.batch_id = cb.batch_id
            WHERE u.user_type IN ('U', 'SU')
            AND u.school_id = $school_id";
    
    if (!empty($batch_id)) {
        $sql = "SELECT DISTINCT u.user_id, u.full_name, u.email_id, u.mobile_number, 
                       se.class_id, se.board_id, cb.section, sbm.batch_id, se.expiry_date, u.user_status
                FROM USERS u
                JOIN TX_STUDENT_BATCH_MAP sbm ON u.user_id = sbm.student_id
                LEFT JOIN TX_STUDENT_ENROLLMENT se ON u.user_id = se.student_id
                LEFT JOIN TX_CLASS_BATCHES cb ON sbm.batch_id = cb.batch_id
                WHERE u.user_type IN ('U', 'SU')
                AND u.school_id = $school_id
                AND sbm.batch_id = $batch_id";
    } elseif (!empty($class_id)) {
        $sql .= " AND se.class_id = $class_id";
    }
    
    // Only filter by board if a specific board is selected (not empty)
    if (!empty($board_id)) {
        if (strpos($sql, 'WHERE') !== false) {
            $sql .= " AND se.board_id = '$board_id'";
        } else {
            $sql .= " AND se.board_id = '$board_id'";
        }
    }
    
    $students = $userObj->getFullQuery($sql);
    
    if (empty($students)) {
        return [];
    }
    
    $validStudents = [];
    foreach ($students as $student) {
        $validStudents[$student['user_id']] = $student;
    }
    
    return $validStudents;
}

// ============================================
// UPDATED: Teacher Mentor Stats - Simplified (No expiry logic)
// ============================================
function getTeacherMentorStats($school_id, $class_id, $batch_id, $board_id, $userObj) {
    
    // Base query with joins to get teacher batch assignments
    $sql = "SELECT DISTINCT 
                u.user_id, 
                u.full_name, 
                u.email_id, 
                u.mobile_number, 
                u.user_type, 
                u.user_status,
                mbm.batch_id,
                mbm.board_id as mentor_board_id,
                mbm.subject_id,
                cb.class_id,
                cb.section,
                cb.board_id as batch_board_id,
                CASE 
                    WHEN u.user_type = 'T' THEN 'Teacher'
                    WHEN u.user_type = 'LCT' THEN 'Live Class Teacher'
                    ELSE 'Staff'
                END as role
            FROM USERS u
            JOIN TX_MENTOR_BATCH_MAP mbm ON u.user_id = mbm.mentor_id
            LEFT JOIN TX_CLASS_BATCHES cb ON mbm.batch_id = cb.batch_id
            WHERE u.user_type IN ('T', 'LCT')
            AND u.school_id = $school_id
            AND u.user_status = 'A'";
    
    // Apply filters
    if (!empty($class_id)) {
        $sql .= " AND cb.class_id = $class_id";
    }
    if (!empty($batch_id)) {
        $sql .= " AND mbm.batch_id = $batch_id";
    }
    if (!empty($board_id)) {
        $sql .= " AND (mbm.board_id = '$board_id' OR cb.board_id = '$board_id')";
    }
    
    $sql .= " ORDER BY u.full_name";
    
    $teachers = $userObj->getFullQuery($sql);
    
    // If no teachers found in mentor batch map with filters, try without filters
    if (empty($teachers)) {
        $fallbackSql = "SELECT DISTINCT 
                            u.user_id, 
                            u.full_name, 
                            u.email_id, 
                            u.mobile_number, 
                            u.user_type, 
                            u.user_status,
                            mbm.batch_id,
                            mbm.board_id as mentor_board_id,
                            mbm.subject_id,
                            cb.class_id,
                            cb.section,
                            cb.board_id as batch_board_id,
                            CASE 
                                WHEN u.user_type = 'T' THEN 'Teacher'
                                WHEN u.user_type = 'LCT' THEN 'Live Class Teacher'
                                ELSE 'Staff'
                            END as role
                        FROM USERS u
                        LEFT JOIN TX_MENTOR_BATCH_MAP mbm ON u.user_id = mbm.mentor_id
                        LEFT JOIN TX_CLASS_BATCHES cb ON mbm.batch_id = cb.batch_id
                        WHERE u.user_type IN ('T', 'LCT')
                        AND u.school_id = $school_id
                        AND u.user_status = 'A'
                        ORDER BY u.full_name";
        $teachers = $userObj->getFullQuery($fallbackSql);
    }
    
    $all_teachers = [];
    
    // Subject name mapping
    $subjectNames = [
        'MA' => 'Mathematics', 'SC' => 'Science', 'PH' => 'Physics',
        'CE' => 'Chemistry', 'BI' => 'Biology', 'CF' => 'Computer Fundas',
        'AI' => 'Gen AI World', 'PD' => 'Personality Development', 'SE' => 'Spoken English'
    ];
    
    foreach ($teachers as $teacher) {
        $board_value = $teacher['mentor_board_id'] ?? $teacher['batch_board_id'] ?? null;
        $board_name = '';
        if ($board_value == 'C') $board_name = 'CBSE';
        elseif ($board_value == 'I') $board_name = 'ICSE';
        elseif ($board_value == 'W') $board_name = 'WBBSE';
        elseif ($board_value == 'K') $board_name = 'Cambridge';
        else $board_name = $board_value;
        
        $teacher_data = [
            'user_id' => $teacher['user_id'],
            'full_name' => $teacher['full_name'],
            'email_id' => $teacher['email_id'],
            'mobile_number' => $teacher['mobile_number'],
            'user_type' => $teacher['user_type'],
            'role' => $teacher['role'],
            'batch_id' => $teacher['batch_id'] ?? null,
            'class_id' => $teacher['class_id'] ?? null,
            'class_display' => $teacher['class_id'] ? 'Class ' . $teacher['class_id'] : 'N/A',
            'section' => $teacher['section'] ?? 'N/A',
            'subject_id' => $teacher['subject_id'] ?? null,
            'subject_name' => $subjectNames[$teacher['subject_id']] ?? ($teacher['subject_id'] ?? 'N/A'),
            'board_id' => $board_value,
            'board_name' => $board_name,
            'status' => 'Active'
        ];
        
        $all_teachers[] = $teacher_data;
    }
    
    return [
        'total_teachers' => count($all_teachers),
        'teachers_list' => $all_teachers
    ];
}

function getStudentStatusStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj) {
    $today = date('Y-m-d');
    $expiring_soon_date = date('Y-m-d', strtotime('+15 days'));
    
    $sql = "SELECT DISTINCT u.user_id, u.full_name, u.email_id, u.mobile_number, 
                   u.user_status, se.class_id, se.board_id, se.expiry_date, se.subscription_type,
                   COALESCE(cb.section, 'N/A') as section,
                   COALESCE(sbm.batch_id, 0) as batch_id
            FROM USERS u
            LEFT JOIN TX_STUDENT_ENROLLMENT se ON u.user_id = se.student_id AND se.school_id = $school_id
            LEFT JOIN TX_STUDENT_BATCH_MAP sbm ON u.user_id = sbm.student_id
            LEFT JOIN TX_CLASS_BATCHES cb ON sbm.batch_id = cb.batch_id
            WHERE u.user_type IN ('U', 'SU')
            AND u.school_id = $school_id";
    
    if (!empty($batch_id)) {
        $sql .= " AND sbm.batch_id = $batch_id";
    }
    if (!empty($class_id)) {
        $sql .= " AND se.class_id = $class_id";
    }
    if (!empty($board_id)) {
        $sql .= " AND se.board_id = '$board_id'";
    }
    if (!empty($academic_year)) {
        $sql .= " AND (YEAR(se.joining_date) <= $academic_year OR se.joining_date IS NULL)";
        $sql .= " AND (YEAR(se.expiry_date) >= $academic_year OR se.expiry_date IS NULL)";
    }
    
    $sql .= " ORDER BY u.full_name";
    
    $students = $userObj->getFullQuery($sql);
    
    $active_count = 0;
    $inactive_count = 0;
    $expiring_soon_count = 0;
    $active_students = [];
    $inactive_students = [];
    $expiring_soon_students = [];
    
    foreach ($students as $student) {
        if ($student['user_status'] == 'A') {
            if (!empty($student['expiry_date']) && $student['expiry_date'] >= $today && $student['expiry_date'] <= $expiring_soon_date) {
                $expiring_soon_count++;
                $student['days_left'] = ceil((strtotime($student['expiry_date']) - strtotime($today)) / 86400);
                $expiring_soon_students[] = $student;
            } else {
                $active_count++;
                $active_students[] = $student;
            }
        } else {
            $inactive_count++;
            $inactive_students[] = $student;
        }
    }
    
    return [
        'total_students' => count($students),
        'active_count' => $active_count,
        'inactive_count' => $inactive_count,
        'expiring_soon_count' => $expiring_soon_count,
        'active_students' => $active_students,
        'inactive_students' => $inactive_students,
        'expiring_soon_students' => $expiring_soon_students
    ];
}

function getEnrollmentTableData($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj) {
    
    $whereConditions = ["se.school_id = $school_id"];
    $whereConditions[] = "se.subscription_type IN ('P', 'B')";
    
    if (!empty($academic_year)) {
        $whereConditions[] = "YEAR(se.joining_date) <= $academic_year";
        $whereConditions[] = "(YEAR(se.expiry_date) >= $academic_year OR se.expiry_date IS NULL)";
    }
    
    if (!empty($class_id)) {
        $whereConditions[] = "se.class_id = $class_id";
    }
    
    if (!empty($board_id)) {
        $whereConditions[] = "se.board_id = '$board_id'";
    }
    
    if (!empty($batch_id)) {
        $whereConditions[] = "sbm.batch_id = $batch_id";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT 
                se.board_id,
                CASE 
                    WHEN se.board_id = 'C' THEN 'CBSE'
                    WHEN se.board_id = 'I' THEN 'ICSE'
                    WHEN se.board_id = 'W' THEN 'WBBSE'
                    WHEN se.board_id = 'K' THEN 'Cambridge'
                    ELSE 'Other'
                END as board_name,
                se.class_id,
                CONCAT('Class ', se.class_id) as class_name,
                COALESCE(cb.section, 'N/A') as section,
                COALESCE(sbm.batch_id, 0) as batch_id,
                COUNT(DISTINCT se.student_id) as student_count
            FROM TX_STUDENT_ENROLLMENT se
            LEFT JOIN TX_STUDENT_BATCH_MAP sbm ON se.student_id = sbm.student_id
            LEFT JOIN TX_CLASS_BATCHES cb ON sbm.batch_id = cb.batch_id AND cb.class_id = se.class_id
            WHERE $whereClause
            GROUP BY se.board_id, se.class_id, sbm.batch_id, cb.section
            ORDER BY se.class_id ASC, se.board_id ASC, cb.section ASC";
    
    $result = $userObj->getFullQuery($sql);
    
    if (empty($result)) {
        return [];
    }
    
    $totalStudents = 0;
    foreach ($result as $row) {
        $totalStudents += $row['student_count'];
    }
    
    return [
        'data' => $result,
        'total_students' => $totalStudents,
        'total_groups' => count($result)
    ];
}

function getEnrollmentStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents) {
    $totalStudents = count($validStudents);
    
    // Build query for teachers count based on filters
    $mentorSql = "SELECT COUNT(DISTINCT u.user_id) as total_mentors 
                  FROM USERS u
                  JOIN TX_MENTOR_BATCH_MAP mbm ON u.user_id = mbm.mentor_id
                  LEFT JOIN TX_CLASS_BATCHES cb ON mbm.batch_id = cb.batch_id
                  WHERE u.user_type IN ('T', 'LCT')
                  AND u.school_id = $school_id
                  AND u.user_status = 'A'";
    
    // Apply class filter
    if (!empty($class_id)) {
        $mentorSql .= " AND cb.class_id = $class_id";
    }
    
    // Apply batch/section filter
    if (!empty($batch_id)) {
        $mentorSql .= " AND mbm.batch_id = $batch_id";
    }
    
    // Apply board filter
    if (!empty($board_id)) {
        $mentorSql .= " AND (mbm.board_id = '$board_id' OR cb.board_id = '$board_id')";
    }
    
    $mentorResult = $userObj->getFullQuery($mentorSql);
    $totalMentors = (!empty($mentorResult) && isset($mentorResult[0]['total_mentors'])) ? (int)$mentorResult[0]['total_mentors'] : 0;
    
    // If no mentors found with filters and no filters applied, try getting all active teachers
    if ($totalMentors == 0 && empty($class_id) && empty($batch_id) && empty($board_id)) {
        $fallbackSql = "SELECT COUNT(DISTINCT user_id) as total_mentors 
                        FROM USERS
                        WHERE user_type IN ('T', 'LCT')
                        AND school_id = $school_id
                        AND user_status = 'A'";
        $fallbackResult = $userObj->getFullQuery($fallbackSql);
        $totalMentors = (!empty($fallbackResult) && isset($fallbackResult[0]['total_mentors'])) ? (int)$fallbackResult[0]['total_mentors'] : 0;
    }
    
    return [
        'total_students' => $totalStudents,
        'total_mentors' => $totalMentors,
        'academic_year' => $academic_year
    ];
}

function getDoubtsStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents) {
    $totalStudents = count($validStudents);

    if ($totalStudents === 0) {
        return [
            'total_doubts' => 0,
            'per_student' => '0',
            'mean_doubts' => '0'
        ];
    }

    $studentIds = array_keys($validStudents);
    $inUsers = implode(',', array_map('intval', $studentIds));

    $doubtsSql = "SELECT COUNT(*) as total_doubts 
                  FROM TX_USER_DOUBTS 
                  WHERE user_id IN ($inUsers)
                  AND school_id = $school_id";
    
    if (!empty($class_id)) {
        $doubtsSql .= " AND class_id = " . intval($class_id);
    }

    $doubtsResult = $userObj->getFullQuery($doubtsSql);
    $totalDoubts = (!empty($doubtsResult) && isset($doubtsResult[0]['total_doubts'])) ? (int)$doubtsResult[0]['total_doubts'] : 0;

    $perStudent = ($totalStudents > 0) ? round($totalDoubts / $totalStudents, 1) : 0;

    return [
        'total_doubts' => $totalDoubts,
        'per_student' => number_format($perStudent, 1),
        'mean_doubts' => number_format($perStudent, 1)
    ];
}

function getProgressStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents) {
    $totalStudents = count($validStudents);
    
    if ($totalStudents == 0) {
        return [
            'total_hours' => '0 hrs',
            'percentage' => 0,
            'subjects' => []
        ];
    }
    
    $subjects = getRelevantSubjects($class_id);
    $studentIds = array_keys($validStudents);
    $totalWatchedSum = 0;
    $totalAvailableSum = 0;
    $subjectWatched = [];
    $subjectAvailable = [];
    
    foreach ($subjects as $subj) {
        $subjectWatched[$subj] = 0;
        $subjectAvailable[$subj] = 0;
    }
    
    foreach ($studentIds as $studentId) {
        $student = $validStudents[$studentId];
        $studentClassId = isset($student['class_id']) ? $student['class_id'] : 0;
        
        $sql = "SELECT 
                    SUM(duration) as total_watched,
                    SUM(total_duration) as total_available
                FROM TX_STUDENT_PROGRESS 
                WHERE student_id = $studentId 
                AND academic_year = '$academic_year'
                AND total_duration > 0";
        
        if ($studentClassId > 0) {
            $sql .= " AND class_id = $studentClassId";
        }
        
        $result = $userObj->getFullQuery($sql);
        $studentWatched = 0;
        $studentAvailable = 0;
        if (!empty($result) && isset($result[0]['total_watched'])) {
            $studentWatched = (int)$result[0]['total_watched'];
            $studentAvailable = (int)($result[0]['total_available'] ?? 0);
        }
        $totalWatchedSum += $studentWatched;
        $totalAvailableSum += $studentAvailable;
        
        foreach ($subjects as $subj) {
            $subjSql = "SELECT 
                            SUM(duration) as total_watched,
                            SUM(total_duration) as total_available
                        FROM TX_STUDENT_PROGRESS 
                        WHERE student_id = $studentId 
                        AND subject_id = '$subj'
                        AND academic_year = '$academic_year'
                        AND total_duration > 0";
            
            if ($studentClassId > 0) {
                $subjSql .= " AND class_id = $studentClassId";
            }
            
            $subjResult = $userObj->getFullQuery($subjSql);
            $subjWatched = 0;
            $subjAvailable = 0;
            if (!empty($subjResult) && isset($subjResult[0]['total_watched'])) {
                $subjWatched = (int)$subjResult[0]['total_watched'];
                $subjAvailable = (int)($subjResult[0]['total_available'] ?? 0);
            }
            
            $subjectWatched[$subj] += $subjWatched;
            $subjectAvailable[$subj] += $subjAvailable;
        }
    }
    
    $avgWatchedSeconds = $totalStudents > 0 ? round($totalWatchedSum / $totalStudents) : 0;
    $avgAvailableSeconds = $totalStudents > 0 ? ($totalAvailableSum / $totalStudents) : 0;
    
    $hours = floor($avgWatchedSeconds / 3600);
    $minutes = floor(($avgWatchedSeconds % 3600) / 60);
    
    if ($hours > 0 && $minutes > 0) {
        $totalHours = "{$hours} hr {$minutes} min";
    } elseif ($hours > 0) {
        $totalHours = "{$hours} hr";
    } elseif ($minutes > 0) {
        $totalHours = "{$minutes} min";
    } else {
        $totalHours = "0 hrs";
    }
    
    $percentage = 0;
    if ($avgAvailableSeconds > 0) {
        $percentage = min(round(($avgWatchedSeconds / $avgAvailableSeconds) * 100, 2), 100);
    }
    
    $formattedSubjects = [];
    $subjectNames = [
        'MA' => 'Mathematics', 'SC' => 'Science', 'PH' => 'Physics',
        'CE' => 'Chemistry', 'BI' => 'Biology', 'CF' => 'Computer Fundas',
        'AI' => 'Gen AI World', 'PD' => 'Personality Development', 'SE' => 'Spoken English'
    ];
    
    foreach ($subjectWatched as $subj => $totalSubjWatched) {
        if ($totalSubjWatched > 0) {
            $avgSubjWatched = round($totalSubjWatched / $totalStudents);
            $avgSubjAvailable = $subjectAvailable[$subj] / $totalStudents;
            
            $subjHours = floor($avgSubjWatched / 3600);
            $subjMinutes = floor(($avgSubjWatched % 3600) / 60);
            
            if ($subjHours > 0 && $subjMinutes > 0) {
                $subjHoursStr = "{$subjHours} hr {$subjMinutes} min";
            } elseif ($subjHours > 0) {
                $subjHoursStr = "{$subjHours} hr";
            } elseif ($subjMinutes > 0) {
                $subjHoursStr = "{$subjMinutes} min";
            } else {
                $subjHoursStr = "0 min";
            }
            
            $subjPercentage = 0;
            if ($avgSubjAvailable > 0) {
                $subjPercentage = min(round(($avgSubjWatched / $avgSubjAvailable) * 100, 2), 100);
            }
            
            $formattedSubjects[] = [
                'subject_id' => $subj,
                'subject_name' => $subjectNames[$subj] ?? $subj,
                'hours' => $subjHoursStr,
                'percentage' => $subjPercentage
            ];
        }
    }
    
    return [
        'total_hours' => $totalHours,
        'percentage' => $percentage,
        'subjects' => $formattedSubjects
    ];
}

function getPerformanceStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents) {
    $totalStudents = count($validStudents);
    
    if ($totalStudents === 0) {
        return [
            'overall_percentage' => 0,
            'subjects' => []
        ];
    }

    $subjects = getRelevantSubjects($class_id);
    $studentIds = array_keys($validStudents);
    $totalMarksObtained = 0;
    $totalMaxMarks = 0;
    $subjectMarks = [];
    
    foreach ($subjects as $subj) {
        $subjectMarks[$subj] = ['obtained' => 0, 'max' => 0];
    }
    
    foreach ($studentIds as $studentId) {
        $student = $validStudents[$studentId];
        $studentClassId = isset($student['class_id']) ? $student['class_id'] : 0;
        
        $sql = "SELECT 
                    SUM(mark_obtained) as marks_obtained,
                    SUM(total_marks) as max_marks
                FROM TX_STUDENT_PERFORMANCE 
                WHERE student_id = $studentId 
                AND status IN ('C')
                AND test_type IN ('DT', 'UT')
                AND academic_year = '$academic_year'";
        
        if ($studentClassId > 0) {
            $sql .= " AND class_id = $studentClassId";
        }
        
        $result = $userObj->getFullQuery($sql);
        if (!empty($result) && isset($result[0])) {
            $totalMarksObtained += (float)($result[0]['marks_obtained'] ?? 0);
            $totalMaxMarks += (float)($result[0]['max_marks'] ?? 0);
        }
        
        foreach ($subjects as $subj) {
            $subjSql = "SELECT 
                            SUM(mark_obtained) as marks_obtained,
                            SUM(total_marks) as max_marks
                        FROM TX_STUDENT_PERFORMANCE 
                        WHERE student_id = $studentId 
                        AND subject_id = '$subj'
                        AND status IN ('C')
                        AND test_type IN ('DT', 'UT')
                        AND academic_year = '$academic_year'";
            
            if ($studentClassId > 0) {
                $subjSql .= " AND class_id = $studentClassId";
            }
            
            $subjResult = $userObj->getFullQuery($subjSql);
            
            if (!empty($subjResult) && isset($subjResult[0])) {
                $subjectMarks[$subj]['obtained'] += (float)($subjResult[0]['marks_obtained'] ?? 0);
                $subjectMarks[$subj]['max'] += (float)($subjResult[0]['max_marks'] ?? 0);
            }
        }
    }
    
    $overallPercentage = ($totalMaxMarks > 0) ? round(($totalMarksObtained / $totalMaxMarks) * 100, 2) : 0;
    
    $formattedSubjects = [];
    $subjectNames = [
        'MA' => 'Mathematics', 'SC' => 'Science', 'PH' => 'Physics',
        'CE' => 'Chemistry', 'BI' => 'Biology', 'CF' => 'Computer Fundas',
        'AI' => 'Gen AI World', 'PD' => 'Personality Development', 'SE' => 'Spoken English'
    ];
    
    foreach ($subjectMarks as $subj => $marks) {
        if ($marks['max'] > 0) {
            $subjPercentage = round(($marks['obtained'] / $marks['max']) * 100, 2);
            $formattedSubjects[] = [
                'subject_id' => $subj,
                'subject_name' => $subjectNames[$subj] ?? $subj,
                'percentage' => min($subjPercentage, 100)
            ];
        }
    }
    
    return [
        'overall_percentage' => $overallPercentage,
        'subjects' => $formattedSubjects
    ];
}

function getAttendanceStats($school_id, $academic_year, $class_id, $batch_id, $board_id, $userObj, $validStudents) {
    $totalStudents = count($validStudents);
    
    if ($totalStudents === 0) {
        return [
            'overall_attendance_percentage' => 0,
            'total_present_days' => 0,
            'total_scheduled_days' => 0,
            'subjects' => []
        ];
    }

    $studentIds = array_keys($validStudents);
    $subjects = getRelevantSubjects($class_id);
    
    $startDate = $academic_year . "-04-01";
    $endDate = ($academic_year + 1) . "-03-31";
    
    $totalAttendanceCount = 0;
    $subjectAttendance = [];
    
    foreach ($subjects as $subj) {
        $subjectAttendance[$subj] = ['present' => 0, 'total' => 0];
    }
    
    foreach ($studentIds as $studentId) {
        $student = $validStudents[$studentId];
        $studentClassId = isset($student['class_id']) ? $student['class_id'] : 0;
        
        $attendanceSql = "SELECT COUNT(*) as attendance_count 
                          FROM TX_STUDENT_ATTENDANCE 
                          WHERE student_id = $studentId 
                          AND school_id = $school_id
                          AND academic_year = '$academic_year'
                          AND DATE(entry_dtm) BETWEEN '$startDate' AND '$endDate'";
        
        if (!empty($class_id)) {
            $attendanceSql .= " AND class_id = $class_id";
        }
        
        $attendanceResult = $userObj->getFullQuery($attendanceSql);
        $studentAttendance = (!empty($attendanceResult) && isset($attendanceResult[0]['attendance_count'])) ? (int)$attendanceResult[0]['attendance_count'] : 0;
        
        $totalAttendanceCount += $studentAttendance;
        
        foreach ($subjects as $subj) {
            $subjSql = "SELECT COUNT(*) as attendance_count 
                        FROM TX_STUDENT_ATTENDANCE 
                        WHERE student_id = $studentId 
                        AND subject_id = '$subj'
                        AND school_id = $school_id
                        AND academic_year = '$academic_year'
                        AND DATE(entry_dtm) BETWEEN '$startDate' AND '$endDate'";
            
            if ($studentClassId > 0) {
                $subjSql .= " AND class_id = $studentClassId";
            }
            
            $subjResult = $userObj->getFullQuery($subjSql);
            $subjAttendance = (!empty($subjResult) && isset($subjResult[0]['attendance_count'])) ? (int)$subjResult[0]['attendance_count'] : 0;
            
            $subjectAttendance[$subj]['present'] += $subjAttendance;
            
            $totalSql = "SELECT COUNT(DISTINCT DATE(entry_dtm)) as total_classes 
                         FROM TX_STUDENT_ATTENDANCE 
                         WHERE subject_id = '$subj'
                         AND school_id = $school_id
                         AND academic_year = '$academic_year'
                         AND DATE(entry_dtm) BETWEEN '$startDate' AND '$endDate'";
            
            if ($studentClassId > 0) {
                $totalSql .= " AND class_id = $studentClassId";
            }
            
            $totalResult = $userObj->getFullQuery($totalSql);
            $totalClasses = (!empty($totalResult) && isset($totalResult[0]['total_classes'])) ? (int)$totalResult[0]['total_classes'] : 0;
            
            if ($subjectAttendance[$subj]['total'] < $totalClasses) {
                $subjectAttendance[$subj]['total'] = $totalClasses;
            }
        }
    }
    
    $avgAttendancePerStudent = ($totalStudents > 0) ? round($totalAttendanceCount / $totalStudents, 1) : 0;
    $totalSchoolDays = 220;
    $overallAttendancePercentage = ($totalSchoolDays > 0) ? min(round(($totalAttendanceCount / ($totalStudents * $totalSchoolDays)) * 100, 2), 100) : 0;
    
    $formattedSubjects = [];
    $subjectNames = [
        'MA' => 'Mathematics', 'SC' => 'Science', 'PH' => 'Physics',
        'CE' => 'Chemistry', 'BI' => 'Biology', 'CF' => 'Computer Fundas',
        'AI' => 'Gen AI World', 'PD' => 'Personality Development', 'SE' => 'Spoken English'
    ];
    
    foreach ($subjectAttendance as $subj => $data) {
        if ($data['total'] > 0) {
            $attendancePercentage = round(($data['present'] / ($totalStudents * $data['total'])) * 100, 2);
            $formattedSubjects[] = [
                'subject_id' => $subj,
                'subject_name' => $subjectNames[$subj] ?? $subj,
                'present_count' => $data['present'],
                'total_classes' => ($data['total'] * $totalStudents),
                'percentage' => min($attendancePercentage, 100)
            ];
        }
    }
    
    return [
        'overall_attendance_percentage' => $overallAttendancePercentage,
        'total_present_days' => $totalAttendanceCount,
        'total_scheduled_days' => ($totalStudents * $totalSchoolDays),
        'avg_attendance_per_student' => $avgAttendancePerStudent,
        'subjects' => $formattedSubjects
    ];
}

function getRelevantSubjects($class_id) {
    if (empty($class_id)) {
        return ['MA', 'SC', 'PH', 'CE', 'BI', 'CF', 'AI'];
    }
    if (in_array($class_id, [11, 12])) {
        return ['MA', 'PH', 'CE', 'BI'];
    } elseif ($class_id == 14) {
        return ['PH', 'CE', 'BI'];
    } elseif ($class_id == 15) {
        return ['PD'];
    } else {
        return ['MA', 'SC', 'CF', 'AI'];
    }
}
?>