<div class="header">
    <h1> 포인트 내역 </h1>
    <h2> 잔액 : <?=$money?></h2>
</div>
<table class="table">
        <thead>
            <tr>
                <th>상태</th>
                <th>금액</th>
                <th>사유구분</th>
                <th>적립 & 차감 일자</th>
                <th>만료일자</th>
            </tr>
        </thead>
<?php foreach($list as $point): ?>
  <tr>
    <td><?php 
    if($point['status']=="P"){
        echo "적립";
    }elseif($point['status']=="M"){
        echo "차감";
    }
    ?>
    </td>
    <td>
    <?php 
        if($point['status'] == "M"){
            echo "-".$point['plus_minus'];
        }else{
            echo $point['plus_minus'];
        }
    ?>
    </td>
    <td><?=$point['reason']?></td>
    <td><?=$point['reg_date']?></td>
    <td><?=$point['end_date']?></td>
  </tr>
<?php endforeach; ?>
<a href="index.php?action=minusMileage"> 00시 업데이트</a>
