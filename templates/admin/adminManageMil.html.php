<div class="header">
    <h1> <?= $list[0]['mem_name']."님의"?>포인트 내역 </h1>
    <h2> 잔액 : <?=$list[0]['mileage'];?></h2>
</div>
<table class="table">
        <thead>
            <tr>
                <th>상태</th>
                <th>금액</th>
                <th>사유구분</th>
                <th>적립 & 소멸 일자</th>
                <th>만료일자</th>
            </tr>
        </thead>
<?php foreach($mil as $mileage): ?>
  <tr>
    <td>
    <?php 
    if($mileage['status']=="P"){
      echo "적립";
    }else if($mileage['status']=="M"){
      echo "차감";
    }
      ?>
    </td>
    <td><?=$mileage['plus_minus']?></td>
    <td><?=$mileage['reason']?></td>
    <td><?=$mileage['date_format(reg_date,\'%Y-%m-%d\')']?></td>
    <td><?=$mileage['date_format(end_date,\'%Y-%m-%d\')']?></td>
  </tr>
<?php endforeach;?>

<form action="admin.php?action=editMileage" method="post">
    <input type = "hidden" name = "mileage[m_id]" value="<?=$mil[0]['m_id']?>">
    <input type = "text" name = "mileage[status]" placeholder="상태" list="statusList">
    <input type = "text" name = "mileage[mil]" placeholder="금액" value="">
    <input type = "text" name = "mileage[reason]" placeholder="사유" list="reasonList">
    <input type = "text" name = "mileage[date]" placeholder="유효기간" list="dateList">
    <input class="btn btn-outline-primary" type = "submit" value="확인">
    <datalist id ="statusList">
      <option value="적립"></option>
      <option value="사용"></option>
    </datalist>
    <datalist id ="reasonList">
      <option value="기간 만료"></option>
      <option value="사용"></option>
      <option value="이벤트"></option>
    </datalist>
    <datalist id ="dateList">
      <option value="1month"></option>
      <option value="2month"></option>
      <option value="3month"></option>
      <option value="4month"></option>
      <option value="5month"></option>
      <option value="6month"></option>
      <option value="7month"></option>
      <option value="8month"></option>
      <option value="9month"></option>
      <option value="10month"></option>
      <option value="11month"></option>
      <option value="12month"></option>
      <option value="1year"></option>
      <option value="2year"></option>
      <option value="3year"></option>
      <option value="4year"></option>
    </datalist>
</form>
