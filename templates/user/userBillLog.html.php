<div class="header">
    <h1> 결제 내역 </h1>
</div>
<table class="table">
        <thead>
            <tr>
                <th>번호</th>
                <th>결제방법</th>
                <th>금액</th>
                <th>결제일</th>
            </tr>
        </thead>
<?php foreach($list as $bill): ?>
  <tr>
    <td><?=$bill['bill_id']?></td>
    <td><?=$bill['payment']?></td>
    <td><?=$bill['cost']?></td>
    <td><?=$bill['reg_date']?></td>
  </tr>
  <?php endforeach; ?>