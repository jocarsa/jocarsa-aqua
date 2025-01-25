<?php
// index.php
session_start();
$db = new SQLite3('app.db');

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $result = $db->querySingle("SELECT * FROM users WHERE username = '$username'", true);
    if ($result && password_verify($password, $result['password'])) {
        $_SESSION['user_id'] = $result['id'];
        header("Location: index.php");
        exit();
    } else {
        $loginError = "Invalid username or password.";
    }
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container d-flex justify-content-center align-items-center vh-100">
            <div class="card p-4">
                <h2 class="text-center mb-4">Login</h2>
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

// Fetch user's packs and applications
$user_id = $_SESSION['user_id'];
$packs = $db->query("SELECT p.id, p.name FROM packs p JOIN user_packs up ON p.id = up.pack_id WHERE up.user_id = $user_id");
$applications = [];
while ($pack = $packs->fetchArray(SQLITE3_ASSOC)) {
    $apps = $db->query("SELECT a.id, a.app_name, a.description, a.logo, a.url FROM applications a JOIN apps_in_pack aip ON a.id = aip.app_id WHERE aip.pack_id = {$pack['id']}");
    while ($app = $apps->fetchArray(SQLITE3_ASSOC)) {
        $applications[] = $app;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
        }
        .app-card {
            cursor: pointer;
            transition: transform 0.2s;
            box-sizing:border-box;
            padding:20px;
        }
        .app-card:hover {
            transform: scale(1.05);
        }
        .nav-link {
            cursor: pointer;
        }
        .iframe-container {
            width: 100%;
            height: calc(100vh - 100px);
            border: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Left Navigation Pane -->
            <div class="col-md-1 bg-dark text-white vh-100 p-3">
                
                <a href="#" class="d-block text-white mb-3 nav-link" onclick="loadDashboard()">Dashboard</a>
                <?php foreach ($applications as $app): ?>
                    <a href="#" class="d-block text-white mb-3 nav-link" onclick="loadApp('<?php echo $app['url']; ?>')">
                    	<img src="https://jocarsa.com/static/logo/<?php echo $app['logo']; ?>" class="card-img-top" alt="<?php echo $app['app_name']; ?>">
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Main Pane -->
            <div class="col-md-11 p-4">
                <div id="dashboard" class="app-grid">
                    <?php foreach ($applications as $app): ?>
                        <div class="card app-card" onclick="loadApp('<?php echo $app['url']; ?>')">
                            <img src="https://jocarsa.com/static/logo/<?php echo $app['logo']; ?>" class="card-img-top" alt="<?php echo $app['app_name']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $app['app_name']; ?></h5>
                                <p class="card-text"><?php echo $app['description']; ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <iframe id="app-frame" class="iframe-container" style="display: none;"></iframe>
            </div>
        </div>
    </div>

    <script>
        function loadApp(url) {
            document.getElementById('dashboard').style.display = 'none';
            document.getElementById('app-frame').style.display = 'block';
            document.getElementById('app-frame').src = url;
        }

        function loadDashboard() {
            document.getElementById('dashboard').style.display = 'grid';
            document.getElementById('app-frame').style.display = 'none';
        }
    </script>
</body>
</html>
