<div class="header">
    <h1>로그인</h1>
</div>
<form class="login" action="index.php?action=userLogin" method="POST">
    <div class="input loginInput">
        <input class="form-control" type="text" name="login[mem_id]"  placeholder="아이디">
        <input class="form-control" type="password" name="login[mem_pw]"  placeholder="비밀번호">
    </div>
    <div class="btn loginBtn">
        <div>
            <input class="btn btn-dark" value="로그인" type ="submit"></input>
        </div>
    </div>
</form>