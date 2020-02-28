<div class="header">
    <h1> 응모 내역 </h1>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>회원 아이디</th>
            <th>결과</th>
            <th>응모일자</th>
        </tr>
    </thead>
    <?php foreach($list as $winner): ?>
    <tr>
        <td><?=$winner['event_id']?></td>
        <td><?=$winner['memberId']?></td>
        <td>
        <?php
        if($winner['winner'] == "1"){
            echo "1등";
        }else if($winner['winner'] == "2"){
            echo "2등";
        }else if($winner['winner'] == "3"){
            echo "3등";
        }else if($winner['winner'] == "4"){
            echo "4등";
        }else if($winner['winner'] == "5"){
            echo "5등";
        }else if($winner['winner'] == "6"){
            echo "꽝";
        }
        ?>
        </td>
        <td><?=$winner['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>