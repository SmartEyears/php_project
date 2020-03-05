<script>
  function calc(val, cp_id){
    var salePri = parseInt(val);
    var ogPri = parseInt(<?=$sell['price']?>);
    var cp_ic = cp_id;
    //alert(salePri);
    if(salePri > 100){
        document.getElementById('salePri').value = salePri;
        document.getElementById('finalPri').value = ogPri - salePri;
        document.getElementById('cp_id').value = cp_id;
    }else{
        salePri = salePri * 0.1;
        salePri = ogPri * salePri;
        document.getElementById('salePri').value = salePri;
        document.getElementById('finalPri').value = ogPri - salePri;
        document.getElementById('cp_id').value = cp_id;
    }
  }

</script>

<div class="header">
    <h1>주문 페이지</h1>
</div>
<div class="header">
    <div>
        <labael>카테고리 :</label>
        <labael><?= $sell['product_type'] ?></label>
    </div>
    <div>
        <labael>상품명 :</label>
        <labael><?= $sell['product'] ?></label>
    </div>
    <div>
        <labael>상품가격 :</label>
        <labael><?= $sell['price']." 원" ?></label>
    </div>
    <div>
        <labael>할인 금액 :</label>
        <input type='text' id ='salePri' value='0' disabled/>
    </div>
    <div>
        <labael>판매자 ID :</label>
        <labael><?= $sell['sellerId'] ?></label>
    </div>
    <div>
        <labael>사용가능 쿠폰</label>
        <table class="table">
            <thead>
                <tr>
                    <th>쿠폰 이름</th>
                    <th>적용</th>
                </tr>
            </thead>
            <?php foreach($coupon as $cp): ?>
            <tr>
                <td><?=$cp['cp_name']?></td>
                <td>
                <?php if($cp['cp_target'] == $sell['product_type']){ ?>
                    <label>적용불가</label>
                <?php }else if($cp['cp_target'] == ""){ ?>
                    <input type='button' class="btn btn-dark" onclick='calc(<?=$cp["cp_price"]?>,<?=$cp["cp_id"]?>)' value="적용"/>
                <?php } ?> 
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div>
        <labael>결제금액 :</label>
        <input type='text' id ='finalPri' value='<?=$sell['price']?>' disabled>
    </div>
    <form method='POST' action=''>
        <input type="hidden" name='deal[m_id]' value="<?=$_SESSION['sess_id']?>">
        <input type="hidden" id ='cp_id' name='deal[cp_id]' value="">
        <input type="hidden" name='deal[dealboard_id]' value="<?=$sell['_id']?>">
        <input type="submit" class="btn btn-dark" value="구매"/>
    </form>
    <a href="index.php?action=dealBoard" class="btn btn-dark">취소</a>
</div>

