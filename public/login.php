<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = input_str($_POST, 'username');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $errors[] = 'Username dan password wajib diisi.';
    } else {
        $stmt = db()->prepare('SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true); // cegah session fixation
            $_SESSION['user'] = [
                'id'        => (int) $user['id'],
                'username'  => $user['username'],
                'full_name' => $user['full_name'],
                'role'      => $user['role'],
            ];
            redirect('/dashboard.php');
        }
        // Pesan generik: jangan bocorkan apakah username atau password yang salah
        $errors[] = 'Username atau password salah.';
    }
}

$pageTitle = 'Login';
require __DIR__ . '/../views/header.php';
?>
<div class="card login">
    <h2>Login Inventory</h2>
    <?php if ($errors): ?>
        <div class="errors"><ul><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post">
        <?= csrf_field() ?>
        <label for="username">Username</label>
        <input id="username" name="username" value="<?= e($username) ?>" autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password">
        <p><button class="btn" type="submit">Masuk</button></p>
    </form>
    <p class="muted">Demo: admin / admin123 &middot; staff / staff123</p>
</div>
<?php require __DIR__ . '/../views/footer.php'; ?>
