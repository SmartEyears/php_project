<div class="header">
    <h1> 회원 시스템 개발 </h1>
</div>

<div class="content">
    <p> 안녕하세요 
    <?php 
    if(isset($_SESSION['sess_memName'])){
    echo $_SESSION['sess_memName']."님";   
    ?>
    </p>
    <form action="index.php?action=deleteSelf" method="post">
        <input type="hidden" name="id" value="<?=$_SESSION['sess_id']?>">
        <input class="btn btn-dark" type="submit" value="회원 탈퇴">  
    </form>
    <?php } ?>
</div>
