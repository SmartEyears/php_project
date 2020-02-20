<div>
    <h1> 회원수정 </h1>
</div>
<div>
    <form class="input" action="" method="POST">
        <input type="hidden" name="member[_id]" value="<?=$user['m_id']?>">
        <div>
            <label>아이디</label>
        </div>
        <div class="input_box"> 
            <input class="form-control" type="text" name="member[mem_id]" placeholder="아이디" value="<?=$user['mem_id']?>"disabled>
        </div>
        <div>
            <label>비밀번호</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="member[mem_pw]" placeholder="비밀번호" value="">
        </div>
        <div>
            <label>비밀번호 확인</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="member[mem_pw2]" placeholder="비밀번호" value="">
        </div>
        <div>
            <label>성명</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_name]" placeholder="성명" value="<?=$user['mem_name']?>">
        </div>
        <div>
            <label>연락처</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_hp]" placeholder="연락처" value="<?=$user['mem_hp']?>">
        </div>
        <div>
            <label>이메일</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="member[mem_email]" placeholder="이메일" value="<?=$user['mem_email']?>">
        </div>
        <div class="btn">
            <div>
                <input class="btn btn-outline-primary" value="수정" type ="submit"></input>
            </div>
            <div>    
                <a class="btn btn-outline-primary" href="admin.php?action=adminUserList">취소</a>
            </div>
        </div>
    </form>
</div>