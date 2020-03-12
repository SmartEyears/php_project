<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/style.css">
    <title><?=$title?></title>
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-light bg-light">
        <a href="admin.php" class="navbar-brand">AdminPage</a>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav">
                <?php if(isset($_SESSION['sess_adminName'])){ ?>
                <a class="nav-item nav-link" href="admin.php?action=adminLogout">로그아웃</a>
                <a class="nav-item nav-link" href="admin.php?action=adminUserList">회원 정보 조회</a>
                <a class="nav-item nav-link" href="admin.php?action=adminList">관리자 정보 조회</a>
                <a class="nav-item nav-link" href="admin.php?action=adminAddView">관리자 추가</a>
                <a class="nav-item nav-link" href="admin.php?action=adminMargin">수익금 조회</a>
                <a class="nav-item nav-link" href="admin.php?action=winnerList">응모 현황</a>
                <a class="nav-item nav-link" href="admin.php?action=couponList">쿠폰 내역</a>
                <?php }else{?>
                <a class="nav-item nav-link" href="admin.php?action=adminLoginView">로그인</a>
                <?php } ?>
            </div>
        </div>
    </nav>
    <main>
        <?=$output?>
    </main>
</body>
</html>