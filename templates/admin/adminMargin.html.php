<div class="header">
    <h1>수익금 조회</h1>
    <h2>수익금 합계 :<?=$totalMargin[0]?></h2>
</div>
<table class="table">
        <thead>
            <tr>
                <th>번호</th>
                <th>수수료</th>
                <th>사유</th>
                <th>날짜</th>
            </tr>
        </thead>
<?php foreach($marginList as $margin): ?>
  <tr>
    <td><?=$margin['fee_id']?></td>
    <td><?=$margin['fee']?></td>
    <td><?=$margin['reason']?></td>
    <td><?=$margin['reg_date']?></td>
  </tr>
<?php endforeach;?>
</table>
