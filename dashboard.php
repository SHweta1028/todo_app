<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle task addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_task'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];

    // Validate input
    if (empty($title) || empty($description) || empty($category) || empty($due_date) || empty($status)) {
        echo "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO tasks (title, description, category, due_date, status, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssi", $title, $description, $category, $due_date, $status, $user_id);

        if ($stmt->execute()) {
            echo "New task added successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle task status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['task_id']) && isset($_POST['status'])) {
    $task_id = $_POST['task_id'];
    $status = $_POST['status'];

    // Validate status
    $valid_statuses = ['Pending', 'In Progress', 'Completed'];
    if (!in_array($status, $valid_statuses)) {
        echo "Invalid status.";
    } else {
        $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sii", $status, $task_id, $user_id);

        if ($stmt->execute()) {
            echo "Task status updated successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Handle task deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_task_id'])) {
    $task_id = $_POST['delete_task_id'];

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $task_id, $user_id);

    if ($stmt->execute()) {
        echo "Task deleted successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch tasks with optional filtering
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_category = isset($_GET['category']) ? $_GET['category'] : '';

$query = "SELECT id, title, description, category, due_date, status FROM tasks WHERE user_id = ?";
$params = [$user_id];
$types = 'i';

if ($filter_status && in_array($filter_status, ['Pending', 'In Progress', 'Completed'])) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= 's';
}

if ($filter_category) {
    $query .= " AND category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="dashboard_style.css">
</head>
<body>
    <h1>Your Tasks</h1>
    <a href="logout.php">Logout</a>

    <!-- Task Filtering -->
    <form method="GET" action="">
        <div class="form-inline">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="Pending" <?php echo $filter_status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="In Progress" <?php echo $filter_status == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="Completed" <?php echo $filter_status == 'Completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
            <select name="category">
                <option value="">All Categories</option>
                <option value="Work" <?php echo $filter_category == 'Work' ? 'selected' : ''; ?>>Work</option>
                <option value="Personal" <?php echo $filter_category == 'Personal' ? 'selected' : ''; ?>>Personal</option>
                <!-- Add more categories as needed -->
            </select>
            <button type="submit">Filter</button>
        </div>
    </form>

    <!-- Add or Update Task -->
    <form method="POST" action="">
        <h2>Add or Update Task</h2>
        <input type="hidden" name="new_task" value="1">
        <div class="form-inline">
            <label>Title: <input type="text" name="title" required></label>
            <label>Description: <input type="text" name="description" required></label>
            <label>Category: 
                <select name="category">
                    <option value="Work">Work</option>
                    <option value="Personal">Personal</option>
                    <!-- Add more categories as needed -->
                </select>
            </label>
            <label>Due Date: <input type="date" name="due_date" required></label>
            <label>Status: 
                <select name="status">
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                </select>
            </label>
            <button type="submit">Add/Update Task</button>
        </div>
    </form>

    <!-- Task List -->
    <table border="1">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Category</th>
                <th>Due Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <select name="status">
                                <option value="Pending" <?php echo $row['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $row['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $row['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                            <button type="submit">Update Status</button>
                        </form>
                        <form method="POST" action="">
                            <input type="hidden" name="delete_task_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                            <button type="submit">Delete Task</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
