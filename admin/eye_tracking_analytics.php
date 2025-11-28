<?php
// Eye Tracking Analytics Dashboard for Admins
session_start();

// Check if user is admin (you may need to adjust this based on your admin authentication)
// For now, we'll check if they're logged in as an admin

// Use centralized database connection
require_once __DIR__ . '/../database/db_connection.php';
$conn = getMysqliConnection();

// Get analytics data with tracking type
$analytics_query = "
    SELECT 
        u.first_name, u.last_name, u.email,
        m.title as module_title,
        ets.session_type,
        SUM(ets.total_time_seconds) as total_study_time,
        COUNT(ets.id) as total_sessions,
        AVG(ets.total_time_seconds) as avg_session_time,
        MAX(ets.total_time_seconds) as max_session_time,
        MIN(ets.created_at) as first_session,
        MAX(ets.last_updated) as last_session
    FROM eye_tracking_sessions ets
    JOIN users u ON ets.user_id = u.id
    JOIN modules m ON ets.module_id = m.id
    GROUP BY ets.user_id, ets.module_id, ets.session_type
    ORDER BY total_study_time DESC
";

$analytics_result = $conn->query($analytics_query);

// Get top performers
$top_performers_query = "
    SELECT 
        u.first_name, u.last_name,
        SUM(ets.total_time_seconds) as total_study_time,
        COUNT(DISTINCT ets.module_id) as modules_studied
    FROM eye_tracking_sessions ets
    JOIN users u ON ets.user_id = u.id
    GROUP BY ets.user_id
    ORDER BY total_study_time DESC
    LIMIT 10
";

$top_performers_result = $conn->query($top_performers_query);

// Get module engagement stats
$module_stats_query = "
    SELECT 
        m.title as module_title,
        COUNT(DISTINCT ets.user_id) as unique_students,
        SUM(ets.total_time_seconds) as total_engagement_time,
        AVG(ets.total_time_seconds) as avg_time_per_session,
        COUNT(ets.id) as total_sessions
    FROM eye_tracking_sessions ets
    JOIN modules m ON ets.module_id = m.id
    GROUP BY ets.module_id
    ORDER BY total_engagement_time DESC
";

$module_stats_result = $conn->query($module_stats_query);

function formatTime($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eye Tracking Analytics - EyeLearn Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-8">📊 Eye Tracking Analytics Dashboard</h1>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            // Calculate summary statistics
            $total_users_query = "SELECT COUNT(DISTINCT user_id) as count FROM eye_tracking_sessions";
            $total_time_query = "SELECT SUM(total_time_seconds) as total FROM eye_tracking_sessions";
            $total_sessions_query = "SELECT COUNT(*) as count FROM eye_tracking_sessions";
            $avg_session_query = "SELECT AVG(total_time_seconds) as avg FROM eye_tracking_sessions";
            
            $total_users = $conn->query($total_users_query)->fetch_assoc()['count'];
            $total_time = $conn->query($total_time_query)->fetch_assoc()['total'];
            $total_sessions = $conn->query($total_sessions_query)->fetch_assoc()['count'];
            $avg_session = $conn->query($avg_session_query)->fetch_assoc()['avg'];
            ?>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-blue-600"><?php echo $total_users; ?></div>
                <div class="text-gray-600">Active Students</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600"><?php echo formatTime($total_time); ?></div>
                <div class="text-gray-600">Total Study Time</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-purple-600"><?php echo $total_sessions; ?></div>
                <div class="text-gray-600">Total Sessions</div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-orange-600"><?php echo formatTime($avg_session); ?></div>
                <div class="text-gray-600">Avg Session Time</div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">🏆 Top Performing Students</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Study Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modules Studied</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $rank = 1;
                            while ($row = $top_performers_result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php 
                                    if ($rank == 1) echo "🥇";
                                    elseif ($rank == 2) echo "🥈";
                                    elseif ($rank == 3) echo "🥉";
                                    else echo $rank;
                                    ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatTime($row['total_study_time']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['modules_studied']; ?>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Module Engagement Stats -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">📚 Module Engagement Statistics</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Session</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($row = $module_stats_result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($row['module_title']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['unique_students']; ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatTime($row['total_engagement_time']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatTime($row['avg_time_per_session']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['total_sessions']; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Analytics -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">📈 Detailed Student Analytics</h2>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Module</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Time</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sessions</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg Session</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            $analytics_result->data_seek(0); // Reset result pointer
                            while ($row = $analytics_result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($row['module_title']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php 
                                    $tracking_type = $row['session_type'];
                                    if ($tracking_type === 'cv_tracking') {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">👁️ CV Tracking</span>';
                                    } else {
                                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">🖱️ Basic Tracking</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatTime($row['total_study_time']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $row['total_sessions']; ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo formatTime($row['avg_session_time']); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('M j, Y H:i', strtotime($row['last_session'])); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
