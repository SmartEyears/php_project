<?php
class dealDatabaseTable{
    private $pdo;

    //생성자
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }

    //게시판에 등록
    public function write($product, $price, $m_id,$seller){
        $sql = "INSERT INTO `deal_board` 
                (product, price, m_id, seller, reg_date, fee)
                VALUES
                (:product, :price, :m_id, :seller, NOW(), :fee)";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();

            $query->bindValue(':product', $product);
            $query->bindValue(':price', $price);
            $query->bindValue(':m_id', $m_id);
            $query->bindValue(':seller', $seller);
            $query->bindValue(':fee', $price * 0.05);
            $query->execute();

            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }

    
    //게시판 삭제
    public function deleteWrite($id){
        $sql = "DELETE FROM `deal_board` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            
            $query->bindValue(':board_id', $id);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }
    
    //판매 업데이트
    public function updateWrite($id){
        $sql ="UPDATE `deal_board`
                SET 
                `status` = TRUE,
                `sell_date` = NOW()
                WHERE
                board_id = :board_id
                ";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            
            $query->bindValue(':board_id', $id);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }   
    }
    
    //수수료 insert
    public function insertMargin($kind, $margin){
        $sql = "INSERT INTO `margin`
                (kind, margin, reg_date)
                VALUES (:kind, :margin, NOW())";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            
            $query->bindValue(':kind', $kind);
            $query->bindValue(':margin', $margin);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
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
        $sql = "SELECT * FROM `deal_board` ORDER BY board_id DESC";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
}