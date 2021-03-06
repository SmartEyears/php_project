<?php
//template 버퍼에 담는 부분
function loadTemplate($templateFileName, $variables = []){
    extract($variables);

    ob_start();

    include __DIR__.'/../templates/user/mileage/'.$templateFileName;

    return ob_get_clean();

}
function checkSession(){
    if(empty($_SESSION['sess_id'])){
        header('location:index.php?action=home');
    }
}

try{
    include __DIR__.'/../includes/DatabaseConn.php';
    include __DIR__.'/../classes/DatabaseTable/mileageDatabaseTable.php';
    include __DIR__.'/../classes/controllers/user/MileageController.php';

    
    $mileageTable = new mileageDatabaseTable($pdo);
    $MileageController = new MileageController($pdo, $mileageTable);

    $action = $_GET['action'] ?? 'home';

    $page = $MileageController->$action();

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
checkSession();
include __DIR__.'/../templates/user/userLayout.html.php';