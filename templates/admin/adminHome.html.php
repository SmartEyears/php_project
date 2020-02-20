<div class="header">
    <h1> 회원 시스템 개발 : 관리자</h1>
</div>
<p> 안녕하세요 
<?php 
    if(isset($_SESSION['sess_adminName'])){
        echo $_SESSION['sess_adminName']."님";   
    }
?>
</p>