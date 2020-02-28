<div class="header">
    <h1> 쿠폰 내역 </h1>
    <h2> 보유 쿠폰 : <?=count($list)?></h2>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>상태</th>
            <th>발급일자</th>
        </tr>
    </thead>
    <?php foreach($list as $coupon): ?>
    <tr>
        <td><?=$coupon['cp_id']?></td>
        <td>
        <?php 
        if($coupon['status'] == "U"){
            echo "사용";
        }else if($coupon['status'] == "N"){
            echo "미사용";
        }else if($coupon['status'] == "E"){
            echo "소멸";
        }
        ?>
        </td>
        <td><?=$coupon['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>

