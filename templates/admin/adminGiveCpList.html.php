<div class="header">
    <h1> 쿠폰내역 </h1>
    <a href=admin.php?action=useCpList>쿠폰 사용내역</a>
    <a href=admin.php?action=giveCpList>쿠폰 발급내역</a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>쿠폰 번호</th>
            <th>회원 번호</th>
            <th>쿠폰 이름</th>
            <th>날짜</th>
        </tr>
    </thead>
    <?php foreach($cpLog as $log): ?>
    <tr>
        <td><?=$log['cl_id']?></td>
        <td><?=$log['cp_id']?></td>
        <td><?=$log['m_id']?></td>
        <td><?=$log['money']?></td>
        <td><?=$log['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>