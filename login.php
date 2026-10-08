<?php
    session_start();
    if (isset($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css" integrity="sha512-NhSC1YmyruXifcj/KFRWoC561YpHpc5Jtzgvbuzx5VozKpWvQ+4nXhPdFgmx8xqexRcpAglTj9sIBWINXa8x5w==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.8/css/bootstrap-grid.min.css" integrity="sha512-dOjUSaLkr6G2pwQ7ry9juX+iXw5602zg1kg8yH+guR3uSEidGyCnOEQnGlr7xwu/8WE+pVm1ZNqaIs5ETTIJQg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-fluid login-container">
        <div class="row login-container-row">
            <div class="login-info-wrap col-md-4 col-12">
                <h1 class="login-text-app-name">PTIT IoT</h1>
                <span class="login-text-app-desc">Giám sát - điều khiển phòng IoT theo thời gian thực</span>
                <span class="login-text-app-desc">Theo dõi nhiệt độ, độ ẩm, ánh sáng và điều khiển LED thông qua MQTT</span>
                <img src="img/block.png" alt="" srcset="">
            </div>
            <div class="login-main-wrap col-md-8 col-12">
                <div class="login-form-block">
                    <h2 class="login-text-center">Đăng nhập hệ thống</h2>
                    <span class="sub-text login-text-guide">Sử dụng tài khoản được cấu hình để tiếp tục</span>
                    <form class="main-login-form">
                        <div class="login-form-input-wrap">
                            <label for="username" class="login-form-label">Tên đăng nhập</label>
                            <input required type="text" class="login-form-input" id="username" placeholder="Nhập tên đăng nhập">
                        </div>
                        <div class="login-form-input-wrap">
                            <label for="password" class="login-form-label">Mật khẩu</label>
                            <input required type="password" class="login-form-input" id="password" placeholder="Nhập mật khẩu">
                        </div>
                        <button type="submit" class="btn login-btn">Đăng nhập</button>
                    </form>
                    <span class="login-text-status" id="login-error-message">Tên đăng nhập hoặc mật khẩu không chính xác</span>
                </div>
            </div>
        </div>
    </div>
</body>

<script>
    document.querySelector('.main-login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorElement = document.getElementById('login-error-message');
        
        fetch('api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, password })
        })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            if (response.ok) {
                window.location.href = 'index.php?page=dashboard';
            } else {
                errorElement.textContent = data.message || 'Không thể đăng nhập, vui lòng thử lại';
                errorElement.style.display = 'block';
            }
        })
        .catch(error => {
            errorElement.textContent = 'Không thể đăng nhập, vui lòng thử lại';
            errorElement.style.display = 'block';
        });
    });
</script>

</html>

<style>
    .login-form-label {
        display: block;
        width: 100%;
    }

    .login-form-input-wrap {
        margin-bottom: 12px;
    }

    .login-form-input {
        width: 100%;
        border: 1px solid var(--sub-text-color);
        border-radius: 8px;
        padding: 12px 12px;
        margin-top: 2px;
        outline: none;
    }

    .login-btn {
        margin-top: 16px;
        margin-bottom: 12px;
        width: 100%;
        padding: 12px 8px;
        border-radius: 4px;
    }

    .login-text-info {
        font-size: small;
        text-align: center;
        display: block;
        position: absolute;
        left: 0;
        right: 0;
        bottom: 16px;
    }

    .login-text-status {
        position: absolute;
        bottom: 24px;
        left: 0;
        right: 0;
        text-align: center;
        color: var(--primary-color);
        font-size: 14px;
        display: none;
    }

    .login-container-row {
        min-height: 100vh;
    }

    .login-text-app-desc {
        display: block;
        padding: 0 48px;
    }

    .login-info-wrap {
        background-color: var(--primary-color);
        color: var(--white-text-color);
        text-align: center;
        display: flex;
        flex-direction: column;
        justify-content: space-evenly;
    }

    .login-main-wrap {
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: var(--border-color);
    }

    .login-form-block {
        box-shadow: rgba(0, 0, 0, 0.05) 0px 6px 24px 0px, rgba(0, 0, 0, 0.08) 0px 0px 0px 1px;
        background-color: white;
        border-radius: 8px;
        padding: 52px 52px;
        position: relative;
    }

    .login-text-center {
        margin-bottom: 4px;
        margin-top: 0;

    }

    .main-login-form {
        margin-top: 16px;
    }
</style>

<link rel="stylesheet" href="responsive.css">
