<script>
    function send(form){
        if(confirm('정말로 회원 탈퇴하시겠습니까?')){
        form.submit();
        window.close();
        }else{
        window.close();
        }
    }
</script>

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
        <input type="hidden" name="member[_id]" value="<?=$_SESSION['sess_id']?>">
        <input type="hidden" name="member[id]" value="<?=$_SESSION['sess_memId']?>">
        <input class="btn btn-dark" type="button" value="회원 탈퇴" onClick="send(this.form)">  
    </form>
    <?php } ?>
</div>
