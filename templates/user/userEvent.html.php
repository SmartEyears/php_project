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
<h3>응모현황</h3>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>결과</th>
            <th>응모일자</th>
        </tr>
    </thead>
    <?php foreach($list as $winner): ?>
    <tr>
        <td><?=$winner['event_id']?></td>
        <td>
        <?php
        if($winner['winner'] == "1"){
            echo "1등";
        }else if($winner['winner'] == "2"){
            echo "2등";
        }else if($winner['winner'] == "3"){
            echo "3등";
        }else if($winner['winner'] == "4"){
            echo "4등";
        }else if($winner['winner'] == "5"){
            echo "5등";
        }else if($winner['winner'] == "6"){
            echo "꽝";
        }
        ?>
        </td>
        <td><?=$winner['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>
