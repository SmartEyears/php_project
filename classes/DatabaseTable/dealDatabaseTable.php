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
    public function write($product_type, $product, $price, $m_id, $seller){
        $sql = "INSERT INTO `deal_board` 
                SET
                product_type = :product_type,
                product = :product, 
                price = :price, 
                m_id = :m_id, 
                seller = :seller, 
                reg_date =NOW()
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':product_type', $product_type);
        $query->bindValue(':product', $product);
        $query->bindValue(':price', $price);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':seller', $seller);
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
    public function updateWrite($board_id, $status, $buyer){
        $sql ="UPDATE `deal_board`
                SET 
                `status` = :status,
                `sell_date` = NOW(),
                `buyer` = :buyer
                WHERE
                board_id = :board_id
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $board_id);
        $query->bindValue(':status', $status);
        $query->bindValue(':buyer', $buyer);
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
    public function selectWrite($status){
        $sql = "SELECT * FROM `deal_board` WHERE status = :status ORDER BY board_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindValue('status', $status);
        $query->execute();
        return $query->fetchAll();
    }

    //구매 대기 중인 상품 조회
    public function selectWaitDeal($m_id){
        $sql = "SELECT * FROM `deal_board` WHERE buyer=:buyer";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':buyer', $m_id);
        $query->execute();
        return $query->fetchAll();
    }

    //거래중 상품 조회
    public function selectDealing($m_id, $status){
        $sql = "SELECT * FROM `deal_board` WHERE m_id = :m_id AND status = :status ORDER BY board_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->bindValue('m_id', $m_id);
        $query->bindValue('status', $status);
        $query->execute();
        return $query->fetchAll();
    }

    //게시글 찾기 
    public function findWrite($id){
        $sql = "SELECT * FROM `deal_board` WHERE board_id = :board_id FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $id);
        $query->execute();
        return $query->fetch();
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

    public function selectCoupon($cp_id){
        $sql = "SELECT * FROM `coupon` WHERE cp_id = :cp_id FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':cp_id' ,$cp_id);
        $query->execute();
        return $query->fetch();
    }

    public function selectCouponLog($status){
        $sql = "SELECT * FROM `cp_log` WHERE status = :status";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':status' ,$status);
        $query->execute();
        return $query->fetchAll();
    }

    public function findUseCoupon($board_id){
        $sql = "SELECT * FROM `cp_log` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':board_id' ,$board_id);
        $query->execute();
        return $query->fetch();
    }
    //사용 쿠폰 복원, 로그 남기기
    public function updateUsedCP($cp_id, $m_id, $board_id, $money, $status){
        $sql = "UPDATE `coupon` SET used = 'N' WHERE cp_id = :cp_id";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':cp_id' ,$cp_id);
        $query->execute();

        $sql = "INSERT INTO `cp_log`
                SET
                cp_id = :cp_id,
                m_id = :m_id, 
                board_id = :board_id,
                reg_date = NOW(),
                money = :money,
                status = :status
                ";

        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':board_id', $board_id);
        $query->bindValue(':money', $money);
        $query->bindValue(':status', $status);
        $query->execute();
    }
}