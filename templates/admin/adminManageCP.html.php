<div class="header">
    <h1> 유저 쿠폰 내역 </h1>
</div>
<form action="admin.php?action=CreateCoupon" method="post">
    <input type = "hidden" name = "coupon[m_id]" value="<?=$id?>">
    <input type = "text" name = "coupon[type]" placeholder="타입" list="cp_type">
    <input type = "text" name = "coupon[name]" placeholder="쿠폰 이름">
    <input type = "text" name = "coupon[price]" placeholder="할인가격">
    <input type = "text" name = "coupon[percent]" placeholder="퍼센트">
    <label>시작일 :</label>
    <input type = "date"" name = "coupon[start_date]" >
    <label>종료일 :</label>
    <input type = "date" name = "coupon[end_date]" >
    <input class="btn btn-outline-primary" type = "submit" value="확인">
    <datalist id ="cp_type">
      <option value="이벤트"></option>
      <option value="금액권"></option>
      <option value="퍼센트"></option>
    </datalist>
</form>
<table class="table">
    <thead>
        <tr>
            <th>쿠폰 타입</th>
            <th>할인</th>
            <th>쿠폰 이름</th>
            <th>발급 날짜</th>
            <th>사용 여부</th>
            <th>사용 일자</th>
            <th>할인 금액</th>
        </tr>
    </thead>
    <?php foreach($list as $coupon): ?>
    <tr>
        <td><?=$coupon['cp_type']?></td>
        <td>
        <?php
            if(empty($coupon['cp_price'])){
                echo $coupon['cp_percent']."%";
            }else if(empty($coupon['cp_percent'])){
                echo $coupon['cp_price']."원";
            }else{
                echo "-";
            }
        ?>
        </td>
        <td><?=$coupon['cp_name']?></td>
        <td><?=$coupon['reg_date']?></td>
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
        <td><?=$coupon['use_date']?></td>
        <td><?=$coupon['saleprice']?></td>
        <td>
        
        </td>
    </tr>
    <?php endforeach; ?>
</table>


