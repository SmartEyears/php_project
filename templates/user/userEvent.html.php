<script>
    function send(form){
        if(confirm('이벤트에 참여 쿠폰이 소모 됩니다. 계속 하시겠습니까?')){
        form.submit();
        window.close();
        }else{
        window.close();
        }
    }
</script>

<div class="header">
    <h1> 이벤트 페이지 </h1>
    <h2> 보유 쿠폰: <?=$cp_count?></h2>
</div>

<div class="content">
    <form action="" method="POST">
        <input type="hidden" name="cp[id]" value="<?=$cp_list[0]['cp_id']?>"/>
        <input type="hidden" name="cp[count]" value="<?=$cp_count?>"/>
        <input type="button" class="btn btn-dark"onclick=send(this.form) value="응모하기"/>
    </form>
</div>
<h3>당첨현황</h3>
<table class="table">
    <thead>
        <tr>
            <th>1등</th>
            <th>2등</th>
            <th>3등</th>
            <th>4등</th>
            <th>5등</th>
        </tr>
    </thead>

    <tr>
        <td><?=$rank[0]?></td>
        <td><?=$rank[1]?></td>
        <td><?=$rank[2]?></td>
        <td><?=$rank[3]?></td>
        <td><?=$rank[4]?></td>
    </tr>
</table>
