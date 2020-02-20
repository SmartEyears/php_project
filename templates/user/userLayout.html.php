<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="css/bootstrap.css">
    <link rel="stylesheet" href="css/style.css">
    <title><?=$title?></title>
</head>
<body>
    <nav class="navbar navbar-expand-md navbar-light bg-light" >
        <a href="index.php" class="navbar-brand" >MainHomePage</a>
        <div class="collapse navbar-collapse" id="navbarCollapse">
            <div class="navbar-nav">
                <a class="nav-item nav-link" href="index.php?action=home">HOME</a>
                <?php if(isset($_SESSION['sess_id'])){ ?>
                    <a class="nav-item nav-link" href="index.php?action=userLogout">로그아웃</a>
                    <a class="nav-item nav-link" href="index.php?action=pointList">포인트 내역</a>
                    <a class="nav-item nav-link" href="index.php?action=pointCharge">포인트 충전</a>
                    <a class="nav-item nav-link" href="index.php?action=dealBoard">중고 거래</a>
                <?php }else{ ?>
                    <a class="nav-item nav-link" href="index.php?action=signUp">회원 가입</a>
                    <a class="nav-item nav-link" href="index.php?action=userLogin">로그인</a>
                <?php } ?>
            </div>
        </div>
    </nav>
    <main>
        <?=$output?>
    </main>
</body>
</html>