<?php
class dealDatabaseTable{
    private $pdo;
    private $mileageTable;

    //생성자
    public function __construct (PDO $pdo, mileageDatabaseTable $mileageTable) {
        $this->pdo = $pdo;
        $this->mileageTable = $mileageTable;
    }

    //상품 등록
    public function write($product, $price, $m_id, $seller){
        $sql = "INSERT INTO `deal_board` 
                (product, price, m_id, seller, reg_date, fee)
                VALUES
                (:product, :price, :m_id, :seller, NOW(), :fee)";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':product', $product);
        $query->bindValue(':price', $price);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':seller', $seller);
        $query->bindValue(':fee', $price * 0.05);
        $query->execute();
    }
    
    //게시판 삭제
    public function deleteWrite($id){
        $sql = "DELETE FROM `deal_board` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $id);
        $query->execute();
    }

    //구매 내역 인서트
    public function insertDealLog($m_id, $buyer, $board_id, $seller, $seller_id, $product, $price){
        $sql = "INSERT INTO `deal_log`
                SET 
                `m_id` = :m_id,
                `buyer` = :buyer,
                `board_id` =  :board_id,
                `seller` = :seller,
                `seller_id` = :seller_id,
                `product` = :product,
                `price` = :price,
                `reg_date` = NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':buyer', $buyer);
        $query->bindValue(':board_id', $board_id);
        $query->bindValue(':seller', $seller);
        $query->bindValue(':seller_id', $seller_id);
        $query->bindValue(':product', $product);
        $query->bindValue(':price', $price);
        $query->execute();
    }
    
    //판매 업데이트
    public function updateWrite($board_id){
        $sql ="UPDATE `deal_board`
                SET 
                `status` = 'c',
                `sell_date` = NOW()
                WHERE
                board_id = :board_id
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue('board_id', $board_id);
        $query->execute();
    }
    
    //수수료 insert
    public function insertMargin($deal_id, $bill_id, $m_id, $fee, $reason){
        $sql = "INSERT INTO `margin`
                SET
                `deal_id`=:deal_id,
                `bill_id`=:bill_id,
                `m_id` = :m_id,
                `fee` = :fee,
                `reason` = :reason,
                `reg_date` = NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':deal_id', $deal_id ?? 0);
        $query->bindValue(':bill_id', $bill_id ?? 0);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':fee', $fee);
        $query->bindValue(':reason', $reason);
        $query->execute();
    }
    //deal_id 가져오기
    public function findDealId($board_id){
        $sql = "SELECT deal_id FROM `deal_log` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $board_id);
        $query->execute();
        $deal_id = $query->fetch();
        return $deal_id['deal_id']; 
    }
    
    //수수료 조회하기
    public function selectMargin(){
        $sql = "SELECT * FROM `margin` ORDER BY fee_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
    
    //수수료 합계
    public function totalMargin(){
        $sql = "SELECT SUM(fee) FROM `margin`";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetch();
    }
    
    //상품 조회
    public function selectWrite(){
        $sql = "SELECT * FROM `deal_board` WHERE status = 's' ORDER BY board_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }

    //게시글 찾기 
    public function findWrite($id){
        $sql = "SELECT * FROM `deal_board` WHERE board_id = :board_id FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $id);
        $query->execute();
    }

    //구매내역 조회
    public function findBuyList($m_id){
        $sql = "SELECT * FROM `deal_log` WHERE m_id = :m_id ORDER BY deal_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }

    //판매내역 조회
    public function findSellList($m_id){
        $sql = "SELECT * FROM `deal_log` WHERE seller_id = :seller_id ORDER BY deal_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':seller_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }

    public function selectBill($id){
        $sql = "SELECT * FROM `bill` WHERE m_id = :m_id ORDER BY reg_date DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $id);
        $query->execute();
        return $query->fetchAll();
    }
}