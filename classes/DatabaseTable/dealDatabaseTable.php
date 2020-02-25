<?php
class dealDatabaseTable{
    private $pdo;
    private $mileageTable;

    //생성자
    public function __construct (PDO $pdo, mileageDatabaseTable $mileageTable) {
        $this->pdo = $pdo;
        $this->mileageTable = $mileageTable;
    }

    //게시판에 등록
    public function write($product, $price, $m_id,$seller){
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

    //게시글 찾기 
    public function findWrite($id){
        $sql = "SELECT * FROM `deal_board` WHERE board_id = :board_id FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $id);
        $query->execute();
    }
    
    //게시판 삭제
    public function deleteWrite($id){
        $sql = "DELETE FROM `deal_board` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $id);
        $query->execute();
    }
    
    //판매 업데이트
    public function updateWrite($board_id, $buyer_id, $seller_id, $price, $product, $end_date, $status, $kind, $margin){
        $sql ="UPDATE `deal_board`
                SET 
                `status` = TRUE,
                `sell_date` = NOW()
                WHERE
                board_id = :board_id
                ";
        $query = $this->pdo->prepare($sql);
        
        $query->bindValue(':board_id', $board_id);
        $query->execute();

        //적립 테이블 INSERT
        $sql = 'INSERT INTO saving
        (m_id, save, balance, reason, reg_date, end_date, status)
        VALUES(:m_id, :save, :balance, :reason, NOW(), :end_date, :status)';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $seller_id);
        $query->bindValue(':save', $price - $margin);
        $query->bindValue(':balance', $price - $margin);
        $query->bindValue(':reason', $product." 판매");
        $query->bindValue(':end_date', $end_date);
        $query->bindValue(':status', $status);
        $query->execute();
        
        //마일리지 로그 테이블 INSERT
        $sql = 'INSERT INTO `mil_log`
                (m_id, status, plus_minus, reason, reg_date, end_date)
                VALUES(:m_id, "P", :plus_minus, :reason, NOW(), :end_date)';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $seller_id);
        $query->bindValue(':plus_minus', $price - $margin);
        $query->bindValue(':reason', $product." 판매");
        $query->bindValue(':end_date', $end_date);
        $query->execute();

        //수익테이블 INSERT
        if($margin != 0){
            $sql = "INSERT INTO `margin`
                    (kind, margin, reg_date)
                    VALUES (:kind, :margin, NOW())";
            $query = $this->pdo->prepare($sql);
            $query->bindValue(':kind', $kind);
            $query->bindValue(':margin', $margin);
            $query->execute();
        }

        //가장 오래된 마일리지 찾기
        $mil_id = $this->mileageTable->selectOldMil($buyer_id);
        $sql = "INSERT INTO `reduce`
                (m_id, mil_id, reduce, reason, reg_date)
                VALUES(:m_id, :mil_id, :reduce, :reason, NOW())";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $buyer_id);
        $query->bindValue(':mil_id', $mil_id['mil_id']);
        $query->bindValue(':reduce', $price);
        $query->bindValue(':reason', $product." 구매");
        $query->execute();

        //마일리지 로그 테이블에 인서트
        $sql = "INSERT INTO `mil_log`
            (m_id, status, plus_minus , reason, reg_date)
            VALUES(:m_id,'M',:plus_minus, :reason, NOW())";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $buyer_id);
        $query->bindValue(':plus_minus', $price);
        $query->bindValue(':reason', $product." 구매");
        $query->execute();

        //오래 된 마일리지부터 순차적으로 차감
        while($price > 0){
            $oldMileage = $this->mileageTable->selectOldMil($buyer_id);
            $sql = "UPDATE `saving`
                    SET `balance` = :balance,
                        `status` = :status
                    WHERE m_id = :m_id AND reg_date = :reg_date";
            if($oldMileage['balance'] > $price){
                $price = $oldMileage['balance'] - $price;

                $query = $this->pdo->prepare($sql);
                $query->bindValue(':balance', $price);
                $query->bindValue(':status', "N");
                $query->bindValue(':m_id', $buyer_id);
                $query->bindValue(':reg_date', $oldMileage['reg_date']);
                $query->execute();

                $price = 0;
            }else{
                $price = $price - $oldMileage['balance'];

                $query = $this->pdo->prepare($sql);
                $query->bindValue(':balance', 0);
                $query->bindValue(':status', "U");
                $query->bindValue(':m_id', $buyer_id);
                $query->bindValue(':reg_date', $oldMileage['reg_date']);
                $query->execute();
            }
        }

        //차감상세 입력
        $detail = $this->mileageTable->findDetail($mil_id['mil_id'], $buyer_id);
        $sql = "INSERT INTO `reduce_detail`
            (re_id, mil_id, reduce)
            VALUES
            (:re_id, :mil_id, :reduce)";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':re_id', $detail['re_id']);
        $query->bindValue(':mil_id', $detail['mil_id']);
        $query->bindValue(':reduce', $detail['reduce']);
        $query->execute();
    }
    
    //수수료 insert
    public function insertMargin($kind, $margin){
        $sql = "INSERT INTO `margin`
                (kind, margin, reg_date)
                VALUES (:kind, :margin, NOW())";
        $query = $this->pdo->prepare($sql);
        $this->pdo->beginTransaction();
        
        $query->bindValue(':kind', $kind);
        $query->bindValue(':margin', $margin);
        $query->execute();
    }
    
    //수수료 조회하기
    public function selectMargin(){
        $sql = "SELECT * FROM `margin` ORDER BY mar_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
    
    //수수료 합계
    public function totalMargin(){
        $sql = "SELECT SUM(margin) FROM `margin`";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetch();
    }
    
    //게시판 글 조회
    public function selectWrite(){
        $sql = "SELECT * FROM `deal_board` WHERE status = 's' ORDER BY board_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
}