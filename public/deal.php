<?php
//template 버퍼에 담는 부분
function loadTemplate($templateFileName, $variables = []){
    extract($variables);

    ob_start();

    include __DIR__.'/../templates/user/'.$templateFileName;

    return ob_get_clean();
}

try{
    include __DIR__.'/../includes/DatabaseConn.php';
    include __DIR__.'/../classes/AESCrypt.php';
    include __DIR__.'/../classes/DatabaseTable/dealDatabaseTable.php';
    include __DIR__.'/../classes/DatabaseTable/eventDatabaseTable.php';
    include __DIR__.'/../classes/DatabaseTable/userDatabaseTable.php';
    include __DIR__.'/../classes/DatabaseTable/mileageDatabaseTable.php';
    include __DIR__.'/../classes/DatabaseTable/couponDatabaseTable.php';
    include __DIR__.'/../classes/controllers/user/DealController.php';

    $key = "dksxoghqkqh!";
    $iv = "xoghxoghxoghqkqh";

    $aesCrypt = new AESCrypt($key,$iv);
    $mileageTable = new mileageDatabaseTable($pdo);
    $eventTable = new eventDatabaseTable($pdo);
    $userTable = new userDatabaseTable($pdo, $aesCrypt);
    $dealTable = new dealDatabaseTable($pdo);
    $couponTable = new couponDatabaseTable($pdo);
    $DealController = new DealController($pdo, $userTable, $mileageTable, $dealTable, $eventTable, $couponTable);

    $action = $_GET['action'] ?? 'home';

    $page = $DealController->$action();

    $title = $page['title'];

    if(isset($page['variables'])){
        $output = loadTemplate($page['template'], $page['variables']);
    }else{
        $output = loadTemplate($page['template']);
    }
}
catch(PDOException $e){
    $title = '오류발가 발생했습니다.';

    $output = '데이터베이스 오류:'.$e->getMessage().', 위치:'.$e->getFile().':'.$e->getLine();
}

include __DIR__.'/../templates/user/userLayout.html.php';