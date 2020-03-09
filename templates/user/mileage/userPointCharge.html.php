<script>
  function send(form){
    if(confirm('마일리지를 충전하시겠습니까?')){
      form.submit();
      window.close();
    }else{
      window.close();
    }
  }

  function calc(val){
    var fee = parseInt(val);

    if(val == ""){
      document.getElementById('result').value = "0";
    }else{
      document.getElementById('result').value = fee-fee * 0.02;
    }
  }

</script>

<div class="header">
    <h1> 포인트 충전 </h1>
</div>
<form class="chargeMil" action="" method="post">
    <input type = "hidden" name = "chargeMil[id]" value="<?=$_SESSION['sess_id']?>">
    <input type = "text" name = "chargeMil[reason]" placeholder="충전 방법" list="dealWay">
    <input type = "text" name = "chargeMil[balance]" placeholder="충전 금액" onkeyup = "calc(this.value)" >
    충전금액 : <input type = "text" id = "result" placeholder="충전 금액" disabled>
    <input class="btn btn-dark" type = "button" value="충전" onClick=send(this.form) >
    <datalist id ="dealWay">
      <option value="휴대폰 결제"></option>
      <option value="신용카드"></option>
      <option value="상품권"></option>
      <option value="가상계좌"></option>
    </datalist>
</form>
<label>※ 수수료는 충전 금액의 2%가 추가로 결제 되오며 유효기간은 충전일로 부터 5년입니다.</label>