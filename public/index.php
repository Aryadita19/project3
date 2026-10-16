<?php
require __DIR__ . '/../bootstrap.php';
redirect(current_user() ? '/dashboard.php' : '/login.php');
