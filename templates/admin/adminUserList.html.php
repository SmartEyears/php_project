<div class="header">
    <h1> 회원 정보 </h1>
</div>
<table class="table">
        <thead>
            <tr>
                <th>번호</th>
                <th>아이디</th>
                <th>이름</th>
                <th>핸드폰</th>
                <th>이메일</th>
                <th>수정</th>
                <th>삭제</th>
                <th>마일리지관리</th>
            </tr>
        </thead>
<?php foreach($list as $oneUser): ?>
  <tr>
    <td><?=$oneUser['id']?></td>
    <td><?=$oneUser['mem_id']?></td>
    <td><?=$oneUser['mem_name']?></td>
    <td><?=$oneUser['mem_hp']?></td>
    <td><?=$oneUser['mem_email']?></td>
    <td>
        <form action="admin.php?action=edit" method="post">
            <input type="hidden" name="mem_id" value="<?=$oneUser['mem_id']?>"/>
            <input class="btn btn-outline-primary" type="submit" value="수정"/>  
        </form>
    </td>
    <td>
        <form action="admin.php?action=delete" method="post">
            <input type="hidden" name="id" value="<?=$oneUser['id']?>"/>
            <input class="btn btn-outline-primary" type="submit" value="삭제"/>  
        </form>
    </td>
    <td>
        <form action="admin.php?action=manageMileage" method="post">
            <input type="hidden" name="member[id]" value="<?=$oneUser['id']?>">
            <input type="hidden" name="member[name]" value="<?=$oneUser['mem_name']?>">
            <input class="btn btn-outline-primary" type="submit" value="마일리지 관리">  
        </form>
    </td>
  </tr>
<?php endforeach; ?>