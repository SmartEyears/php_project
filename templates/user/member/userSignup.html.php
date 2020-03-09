<div class="header">
    <h1> 회원가입 </h1>
</div>
<div>
    <form class="input signupInput" action="index.php?action=signUp" method="POST">
        <div class="input_box"> 
            <div>
                <input class="form-control" type="text" name="member[mem_id]" placeholder="아이디" maxlength="20">
            </div>
            <div>
                <label>※ 아이디 제한 : 20자</label>    
            </div>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="member[mem_pw]" placeholder="비밀번호" maxlength="16">
            <div>
                <label>※ 비밀번호 제한 : 16자</label>    
            </div>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="member[mem_pw2]" placeholder="비밀번호 확인" maxlength="16">
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_name]" placeholder="성명" maxlength="40">
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_hp]" placeholder="연락처" maxlength="11">
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_email]" placeholder="이메일" maxlength="100">
            <div>
                <label>※ 이메일은 선택사항입니다. </label>    
            </div>
        </div>
        <div class="btn signupBtn">
            <div>
                <input class="btn btn-dark" value="가입" type ="submit"></input>
            </div>
            <div>    
                <a class="btn btn-dark" href="index.php">취소</a>
            </div>
        </div>
    </form>
</div>