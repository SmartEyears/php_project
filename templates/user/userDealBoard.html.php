<div class="header">
    <h1> 중고 거래 </h1>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>판매 물품</th>
            <th>가격</th>
            <th>판매자</th>
            <th>날짜</th>
            <th>버튼</th>
            <th>판매여부</th>
        </tr>
    </thead>
    <?php foreach($list as $board): ?>
    <tr>
        <td><?=$board['_id']?></td>
        <td><?=$board['product']?></td>
        <td><?=$board['price']?></td>
        <td><?=$board['seller']?></td>
        <td><?=$board['reg_date']?></td>
        <td>
            <?php
            if($board['status'] == FALSE){
                if($_SESSION['sess_memId'] == $board['seller']){
                ?>
                    <form action="" method="POST">
                        <input type="hidden" name="delete_id" value="<?=$board['_id']?>">
                        <input type="submit" class=" btn btn-dark" value="삭제">
                    </form>
                <?php
                }else{
                ?>
                    <form action="" method="POST">
                        <input type="hidden" name="sell[_id]" value="<?=$board['_id']?>">
                        <input type="hidden" name="sell[fee]" value="<?=$board['fee']?>">
                        <input type="hidden" name="sell[price]" value="<?=$board['price']?>">
                        <input type="hidden" name="sell[product]" value="<?=$board['product']?>">
                        <input type="hidden" name="sell[m_id]" value="<?=$board['m_id']?>">
                        <input type="submit" class=" btn btn-dark" value="구매">
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
        <td>
        <?php 
            if($board['status']==FALSE){
                echo "판매중";
            }
            else{
                echo "판매완료";
            }
        
        ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<div>
    <a class="btn btn-dark" href="index.php?action=boardCreate">글생성</a>
</div>