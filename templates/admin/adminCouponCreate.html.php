
<script>
  function disable(){
    var type = document.getElementById('type').value;
    var input_price = document.getElementById('price');
    var input_percent = document.getElementById('percent');

    if(type == '금액권'){
        input_percent.disabled = true;
        input_price.disabled = false;
    }else if(type == '퍼센트'){
        input_price.disabled = true;
        input_percent.disabled = false;
    }else{
        input_price.disabled = true;
        input_percent.disabled = true;
    }
  }

</script>
<div class="header">
    <h1>쿠폰 생성</h1>
</div>
<div>
    <form class="input" action="admin.php?action=CreateCoupon" method="POST">
        <div>
            <label>쿠폰이름</label>
        </div>
        <div class="input_box"> 
            <input class="form-control" type="text" name="coupon[name]" maxlength="20">
        </div>
        <div>
            <label>쿠폰 타입</label>
        </div>
        <div class="input_box">
            <input class="form-control" id='type' type="text" name="coupon[type]" onkeyup='disable()' list="cpType">
        </div>
        <div>
            <label>할인 가격</label>
        </div>
        <div class="input_box">
            <input id="price" class="form-control" type="text" name="coupon[price]" disabled>
        </div>
        <div>
            <label>퍼센트</label>
        </div>
        <div class="input_box">
            <input id="percent" class="form-control" type="text" name="coupon[percent]" disabled>
        </div>
        <div>
            <label>최대 발급 수</label>
        </div>
        <div class="input_box">
            <input class="form-control" type="text" name="coupon[max_num]" maxlength="100">
        </div>
        <div>
            <label>사용 시작일</label>
        </div>
        <div class="input_box">
            <input class="form-control" name="coupon[start_date]" type='date'/><br>
        </div>
        <div>
            <label>사용 종료일</label>
        </div>
        <div class="input_box">
            <input class="form-control" name="coupon[end_date]" type='date'/><br>
        </div>
        <div class="btn">
            <div>
                <input class="btn btn-outline-primary" value="저장" type ="submit"></input>
            </div>
            <div>    
                <a class="btn btn-outline-primary" href="admin.php?action=Home">취소</a>
            </div>
        </div>
        <datalist id ="cpType">
            <option value="금액권"></option>
            <option value="퍼센트"></option>
            <option value="이벤트"></option>
        </datalist>
    </form>
</div>