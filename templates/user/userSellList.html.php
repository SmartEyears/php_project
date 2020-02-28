<div class="header">
    <h1> 판매 내역 </h1>
    <a href=index.php?action=dealLog>구매내역</a>
    <a href=index.php?action=sellLog>판매내역</a>
</div>
<table class="table">
    <thead>
        <tr>
            <th>번호</th>
            <th>거래상품</th>
            <th>가격</th>
            <th>구매자</th>
            <th>날짜</th>
        </tr>
    </thead>
    <?php foreach($list as $sell): ?>
    <tr>
        <td><?=$sell['deal_id']?></td>
        <td><?=$sell['product']?></td>
        <td><?=$sell['price']?></td>
        <td><?=$sell['buyer']?></td>
        <td><?=$sell['reg_date']?></td>
    </tr>
    <?php endforeach; ?>
</table>
