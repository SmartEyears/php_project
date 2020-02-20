<div class="header">
    <h1> 관리자 추가 </h1>
</div>
<div>
    <form class="input" action="" method="POST">
        <div>
            <label>아이디</label>
        </div>
        <div class="input_box"> 
            <input class="form-control" type="text" name="admin_mem[mem_id]" placeholder="아이디" maxlength="20">
        </div>
        <div>
            <label>비밀번호</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="admin_mem[mem_pw]" placeholder="비밀번호" maxlength="16">
        </div>
        <div>
            <label>비밀번호 확인</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="password" name="admin_mem[mem_pw2]" placeholder="비밀번호" maxlength="16">
        </div>
        <div>
            <label>성명</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="admin_mem[mem_name]" placeholder="성명" maxlength="40">
        </div>
        <div>
            <label>연락처</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="admin_mem[mem_hp]" placeholder="연락처" maxlength="11">
        </div>
        <div>
            <label>이메일</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="admin_mem[mem_email]" placeholder="이메일" maxlength="100">
        </div>
        <div class="btn">
            <div>
                <input class="btn btn-outline-primary" value="저장" type ="submit"></input>
            </div>
            <div>    
                <a class="btn btn-outline-primary" href="admin.php?action=Home">취소</a>
            </div>
        </div>
    </form>
</div>