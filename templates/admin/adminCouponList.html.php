<div class="header">
    <h1> 쿠폰내역 </h1>
</div>
<table class="table">
    <thead>
        <tr>
            <th>코드</th>
            <th>쿠폰 이름</th>
            <th>할인</th>
            <th>사용기간</th>
            <th>발급일</th>
            <th>발급 제한</th>
            <th>현재 발급 갯수</th>
            <th>활성 상태</th>
        </tr>
    </thead>
    <?php foreach($cpList as $cp): ?>
    <tr>
        <td><?=$cp['cp_num']?></td>
        <td><?=$cp['cp_name']?></td>
        <td>
        <?php
            if(empty($cp['cp_price'])){
                echo $cp['cp_percent']."%";
            }else if(empty($cp['cp_percent'])){
                echo $cp['cp_price']."원";
            }else{
                echo "-";
            }
        ?>
        </td>
        <td><?=$cp['start_date']."~".$cp['end_date'];?></td>
        <td><?=$cp['reg_date']?></td>
        <td><?=$cp['max_num']?></td>
        <td><?=$cp['give_num']?></td>
        <td>
        <?php
            if($cp['status']=='D'){
                echo "비활성화";
            }else if($cp['status']=='A'){
                echo "활성화";
            }else{
                echo "-";
            }
        ?>
        </td>
        <td>
            <form action='admin.php?action=couponActivation' method='POST'>
                <input type='hidden' name='cp_num' value='<?=$cp['cp_num']?>'>
                <input class='btn btn-dark' type='submit' value='활성화'>
            </form>
        </td>
        <td>
            <form action='admin.php?action=couponDeactivation' method='POST'>
                <input type='hidden' name='cp_num' value='<?=$cp['cp_num']?>'>
                <input class='btn btn-dark' type='submit' value='비활성화'>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>