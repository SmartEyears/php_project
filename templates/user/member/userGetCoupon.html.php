<div class="header">
    <h1> 쿠폰 추가하기 </h1>
</div>
<form action="index.php?action=getCoupon" method="post">
    <input type = "text" name = "coupon_num" placeholder="쿠폰 번호">
    <input class="btn btn-dark" type = "submit" value="충전" >
    <datalist id ="dealWay">
      <option value="휴대폰 결제"></option>
      <option value="신용카드"></option>
      <option value="상품권"></option>
      <option value="가상계좌"></option>
    </datalist>
</form>
<label>※ 쿠폰은 계정 당 하나만 발급 가능 합니다.</label>