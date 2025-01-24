<?php
// Turn off all error reporting
error_reporting(0);

// Or, to hide specific types of errors (e.g., warnings and notices)
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Disable displaying errors to the user
ini_set('display_errors', 0);
?>
<?php
// admin.php
session_start();
$db = new SQLite3('app.db');

// Create tables if they don't exist
$db->exec("
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        name TEXT NOT NULL,
        email TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        app_name TEXT NOT NULL,
        description TEXT,
        logo TEXT,
        url TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS packs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS apps_in_pack (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pack_id INTEGER NOT NULL,
        app_id INTEGER NOT NULL,
        FOREIGN KEY (pack_id) REFERENCES packs(id),
        FOREIGN KEY (app_id) REFERENCES applications(id)
    );
    CREATE TABLE IF NOT EXISTS user_packs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        pack_id INTEGER NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (pack_id) REFERENCES packs(id)
    );
    CREATE TABLE IF NOT EXISTS admins (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        full_name TEXT NOT NULL,
        email TEXT NOT NULL
    );
");

// Create initial admin user if it doesn't exist
$initialAdmin = $db->querySingle("SELECT id FROM admins WHERE username = 'jocarsa'");
if (!$initialAdmin) {
    $password = password_hash('jocarsa', PASSWORD_DEFAULT);
    $db->exec("INSERT INTO admins (username, password, full_name, email) VALUES ('jocarsa', '$password', 'Jose Vicente Carratala', 'info@josevicentecarratala.com')");
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $admin = $db->querySingle("SELECT * FROM admins WHERE username = '$username'", true);
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: admin.php");
        exit();
    } else {
        $loginError = "Invalid username or password.";
    }
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container d-flex justify-content-center align-items-center vh-100">
            <div class="card p-4">
                <h2 class="text-center mb-4">Admin Login</h2>
                ' . (isset($loginError) ? "<div class='alert alert-danger'>$loginError</div>" : "") . '
                <form method="POST">
                    <div class="mb-3">
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                    <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </body>
    </html>';
    exit();
}

// Handle CRUD operations
$table = $_GET['table'] ?? 'users'; // Default table
$action = $_GET['action'] ?? 'list'; // Default action
$id = $_GET['id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        if ($table === 'users') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $name = $_POST['name'];
            $email = $_POST['email'];
            $db->exec("INSERT INTO users (username, password, name, email) VALUES ('$username', '$password', '$name', '$email')");
        } elseif ($table === 'applications') {
            $app_name = $_POST['app_name'];
            $description = $_POST['description'];
            $logo = $_POST['logo'];
            $url = $_POST['url'];
            $db->exec("INSERT INTO applications (app_name, description, logo, url) VALUES ('$app_name', '$description', '$logo', '$url')");
        } elseif ($table === 'packs') {
            $name = $_POST['name'];
            $db->exec("INSERT INTO packs (name) VALUES ('$name')");
        } elseif ($table === 'apps_in_pack') {
            $pack_id = $_POST['pack_id'];
            $app_id = $_POST['app_id'];
            $db->exec("INSERT INTO apps_in_pack (pack_id, app_id) VALUES ($pack_id, $app_id)");
        } elseif ($table === 'user_packs') {
            $user_id = $_POST['user_id'];
            $pack_id = $_POST['pack_id'];
            $db->exec("INSERT INTO user_packs (user_id, pack_id) VALUES ($user_id, $pack_id)");
        } elseif ($table === 'admins') {
            $username = $_POST['username'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $db->exec("INSERT INTO admins (username, password, full_name, email) VALUES ('$username', '$password', '$full_name', '$email')");
        }
    } elseif ($action === 'edit' && $id) {
        if ($table === 'users') {
            $username = $_POST['username'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $db->exec("UPDATE users SET username = '$username', name = '$name', email = '$email' WHERE id = $id");
        } elseif ($table === 'applications') {
            $app_name = $_POST['app_name'];
            $description = $_POST['description'];
            $logo = $_POST['logo'];
            $url = $_POST['url'];
            $db->exec("UPDATE applications SET app_name = '$app_name', description = '$description', logo = '$logo', url = '$url' WHERE id = $id");
        } elseif ($table === 'packs') {
            $name = $_POST['name'];
            $db->exec("UPDATE packs SET name = '$name' WHERE id = $id");
        } elseif ($table === 'admins') {
            $username = $_POST['username'];
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $db->exec("UPDATE admins SET username = '$username', full_name = '$full_name', email = '$email' WHERE id = $id");
        }
    }
    header("Location: admin.php?table=$table");
    exit();
} elseif ($action === 'delete' && $id) {
    $db->exec("DELETE FROM $table WHERE id = $id");
    header("Location: admin.php?table=$table");
    exit();
}

// Fetch data for the selected table
$data = [];
if ($action === 'list') {
    if ($table === 'apps_in_pack') {
        // Fetch apps_in_pack with related pack and app names
        $result = $db->query("
            SELECT aip.*, p.name AS pack_name, a.app_name AS app_name
            FROM apps_in_pack aip
            JOIN packs p ON aip.pack_id = p.id
            JOIN applications a ON aip.app_id = a.id
        ");
    } elseif ($table === 'user_packs') {
        // Fetch user_packs with related user and pack names
        $result = $db->query("
            SELECT up.*, u.username AS user_name, p.name AS pack_name
            FROM user_packs up
            JOIN users u ON up.user_id = u.id
            JOIN packs p ON up.pack_id = p.id
        ");
    } else {
        // Fetch data for other tables
        $result = $db->query("SELECT * FROM $table");
    }
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
} elseif ($action === 'edit' && $id) {
    $row = $db->querySingle("SELECT * FROM $table WHERE id = $id", true);
}

// Fetch foreign key options
$users = $db->query("SELECT id, username FROM users");
$applications = $db->query("SELECT id, app_name FROM applications");
$packs = $db->query("SELECT id, name FROM packs");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #f8f9fa;
            padding: 1rem;
        }
        .main-content {
            flex: 1;
            padding: 1rem;
        }
        .nav-link {
            color: #333;
        }
        .nav-link.active {
            font-weight: bold;
            color: #000;
        }
    </style>
</head>
<body class="d-flex">
    <!-- Left Navigation Pane -->
    <div class="sidebar">
        <h4 class="mb-4">Admin Panel</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'users' ? 'active' : ''; ?>" href="?table=users">Users</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'applications' ? 'active' : ''; ?>" href="?table=applications">Applications</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'packs' ? 'active' : ''; ?>" href="?table=packs">Packs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'apps_in_pack' ? 'active' : ''; ?>" href="?table=apps_in_pack">Apps in Pack</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'user_packs' ? 'active' : ''; ?>" href="?table=user_packs">User Packs</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $table === 'admins' ? 'active' : ''; ?>" href="?table=admins">Admins</a>
            </li>
        </ul>
    </div>

    <!-- Main Pane -->
    <div class="main-content">
        <h1 class="mb-4 text-capitalize"><?php echo $table; ?></h1>

        <!-- List Data -->
        <?php if ($action === 'list'): ?>
            <a href="?table=<?php echo $table; ?>&action=add" class="btn btn-primary mb-3">Add New</a>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <?php if (!empty($data)): ?>
                            <?php foreach (array_keys($data[0]) as $column): ?>
                                <?php if (!in_array($column, ['pack_id', 'app_id', 'user_id'])): ?>
                                    <th><?php echo ucfirst(str_replace('_', ' ', $column)); ?></th>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row): ?>
                        <tr>
                            <?php foreach ($row as $column => $value): ?>
                                <?php if (!in_array($column, ['pack_id', 'app_id', 'user_id'])): ?>
                                    <td><?php echo $value; ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td>
                                <a href="?table=<?php echo $table; ?>&action=edit&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="?table=<?php echo $table; ?>&action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <form method="POST">
                <?php if ($table === 'users'): ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo $row['username'] ?? ''; ?>" required>
                    </div>
                    <?php if ($action === 'add'): ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $row['name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $row['email'] ?? ''; ?>" required>
                    </div>
                <?php elseif ($table === 'applications'): ?>
                    <div class="mb-3">
                        <label for="app_name" class="form-label">App Name</label>
                        <input type="text" name="app_name" class="form-control" value="<?php echo $row['app_name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea name="description" class="form-control"><?php echo $row['description'] ?? ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="logo" class="form-label">Logo URL</label>
                        <input type="text" name="logo" class="form-control" value="<?php echo $row['logo'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="url" class="form-label">App URL</label>
                        <input type="text" name="url" class="form-control" value="<?php echo $row['url'] ?? ''; ?>" required>
                    </div>
                <?php elseif ($table === 'packs'): ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Pack Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo $row['name'] ?? ''; ?>" required>
                    </div>
                <?php elseif ($table === 'apps_in_pack'): ?>
                    <div class="mb-3">
                        <label for="pack_id" class="form-label">Pack</label>
                        <select name="pack_id" class="form-select" required>
                            <?php while ($pack = $packs->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $pack['id']; ?>"><?php echo $pack['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="app_id" class="form-label">Application</label>
                        <select name="app_id" class="form-select" required>
                            <?php while ($app = $applications->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $app['id']; ?>"><?php echo $app['app_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php elseif ($table === 'user_packs'): ?>
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User</label>
                        <select name="user_id" class="form-select" required>
                            <?php while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="pack_id" class="form-label">Pack</label>
                        <select name="pack_id" class="form-select" required>
                            <?php while ($pack = $packs->fetchArray(SQLITE3_ASSOC)): ?>
                                <option value="<?php echo $pack['id']; ?>"><?php echo $pack['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php elseif ($table === 'admins'): ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo $row['username'] ?? ''; ?>" required>
                    </div>
                    <?php if ($action === 'add'): ?>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo $row['full_name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $row['email'] ?? ''; ?>" required>
                    </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="?table=<?php echo $table; ?>" class="btn btn-secondary">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
