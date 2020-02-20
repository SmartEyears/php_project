<div class="header">
    <h1>수익금 조회</h1>
    <h2>수익금 합계 :<?=$totalMargin[0]?></h2>
</div>
<table class="table">
        <thead>
            <tr>
                <th>번호</th>
                <th>유형</th>
                <th>수수료</th>
                <th>날짜</th>
            </tr>
        </thead>
<?php foreach($marginList as $margin): ?>
  <tr>
    <td><?=$margin['mar_id']?></td>
    <td><?=$margin['kind']?></td>
    <td><?=$margin['margin']?></td>
    <td><?=$margin['reg_date']?></td>
  </tr>
<?php endforeach;?>
</table>
