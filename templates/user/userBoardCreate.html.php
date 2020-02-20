<form action="" method="POST">
    <label>※ 거래 시 수수료가 발생합니다. 수수료는 거래 금액의 5% 입니다.</label>
    <label>수수료는 판매자가 부담하며 5%를 제외하고 마일리지로 지급됩니다.</label>
    <input type="text" name="board[product]" class="form-control" placeholder="판매 물품">
    <input type="text" name="board[price]" class="form-control" placeholder="가격">
    <input type="hidden" name="board[seller]" class="form-control" value="<?=$_SESSION['sess_memId']?>">
    <input type="hidden" name="board[m_id]" class="form-control" value="<?=$_SESSION['sess_id']?>">
    <input class="btn btn-dark" value="등록" type ="submit"></input>
    <a class="btn btn-dark" href="index.php">취소</a>
</form>