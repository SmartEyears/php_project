<script>
    function send(form){
        if(confirm('계속 진행 하시겠습니까?')){
        form.submit();
        window.close();
        }else{
        window.close();
        }
    }
</script>

<div class="header">
    <h1>나의 진행 중인 거래</h1>
    <a href=deal.php?action=dealBoardView>판매글</a>
    <a href=deal.php?action=dealingView>판매승인</a>
    <a href=deal.php?action=dealWait>구매대기</a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>분류</th>
            <th>판매 물품</th>
            <th>가격</th>
            <th>날짜</th>
            <th></th>
            <th></th>
        </tr>
    </thead>
    <?php foreach($list as $board): ?>
    <tr>
        <td><?=$board['board_id']?></td>
        <td><?=$board['product_type']?></td>
        <td><?=$board['product']?></td>
        <td><?=$board['price']?></td>
        <td><?=$board['reg_date']?></td>
        <td>
            <form action='deal.php?action=dealAgree' method='POST'>
                <input type='hidden' name='agree' value='<?=$board['board_id']?>'>
                <input type='submit' class='btn btn-dark' value="승락">
            </form>
        </td>
        <td>
            <form action='deal.php?action=dealRefuse' method='POST'>
                <input type='hidden' name='refuse' value='<?=$board['board_id']?>'>
                <input type='submit' class='btn btn-dark' value="거절">
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
