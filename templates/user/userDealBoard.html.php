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
    <h1> 중고 거래 </h1>
    <a href=index.php?action=dealBoard>판매글</a>
    <a href=index.php?action=dealing>판매승인</a>
    <a href=index.php?action=dealWait>구매대기</a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>분류</th>
            <th>판매 물품</th>
            <th>가격</th>
            <th>판매자</th>
            <th>날짜</th>
            <th>버튼</th>
        </tr>
    </thead>
    <?php foreach($list as $board): ?>
    <tr>
        <td><?=$board['_id']?></td>
        <td><?=$board['product_type']?></td>
        <td><?=$board['product']?></td>
        <td><?=$board['price']?></td>
        <td><?=$board['seller']?></td>
        <td><?=$board['reg_date']?></td>
        <td>
            <?php
            if($board['status'] == 's'){
                if($_SESSION['sess_memId'] == $board['seller']){
                ?>
                    <form action="" method="POST">
                        <input type="hidden" name="delete_id" value="<?=$board['_id']?>">
                        <input type="button" class=" btn btn-dark" value="삭제" onClick=send(this.form)>
                    </form>
                <?php
                }else{
                ?>
                    <form action="" method="POST">
                        <input type="hidden" name="sell[_id]" value="<?=$board['_id']?>">
                        <input type="hidden" name="sell[sellerId]" value="<?=$board['seller']?>">
                        <input type="hidden" name="sell[price]" value="<?=$board['price']?>">
                        <input type="hidden" name="sell[product]" value="<?=$board['product']?>">
                        <input type="hidden" name="sell[product_type]" value="<?=$board['product_type']?>">
                        <input type="hidden" name="sell[m_id]" value="<?=$board['m_id']?>">
                        <input type="button" class=" btn btn-dark" value="구매" onClick=send(this.form)>
                    </form>
                <?php
                }
            }else{
                ?>
                <label>-</label>
            <?php
            }
            ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<div>
    <a class="btn btn-dark" href="index.php?action=boardCreate">글생성</a>
</div>