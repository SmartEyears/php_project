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
                    <a class="nav-item nav-link" href="mileage.php?action=pointList">포인트 내역</a>
                    <a class="nav-item nav-link" href="mileage.php?action=pointChargeView">포인트 충전</a>
                    <a class="nav-item nav-link" href="mileage.php?action=billLog">결제 내역</a>
                    <a class="nav-item nav-link" href="deal.php?action=dealBoardView">중고 거래</a>
                    <a class="nav-item nav-link" href="deal.php?action=dealLog">거래 내역</a>
                    <a class="nav-item nav-link" href="index.php?action=eventView">이벤트</a>
                    <a class="nav-item nav-link" href="index.php?action=getCouponView">쿠폰 추가</a>
                <?php }else{ ?>
                    <a class="nav-item nav-link" href="index.php?action=signUpView">회원 가입</a>
                    <a class="nav-item nav-link" href="index.php?action=userLoginView">로그인</a>
                <?php } ?>
            </div>
        </div>
    </nav>
    <main>
        <?=$output?>
    </main>
</body>
</html>