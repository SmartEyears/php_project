<div class="header">
    <h1> 구매 내역 </h1>
    <a href=deal.php?action=dealLog>구매내역</a>
    <a href=deal.php?action=sellLog>판매내역</a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>거래상품</th>
            <th>가격</th>
            <th>판매자</th>
            <th>날짜</th>
        </tr>
    </thead>
    <?php foreach($list as $buy): ?>
    <tr>
        <td><?=$buy['deal_id']?></td>
        <td><?=$buy['product']?></td>
        <td><?=$buy['price']?></td>
        <td><?=$buy['seller']?></td>
        <td><?=$buy['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>