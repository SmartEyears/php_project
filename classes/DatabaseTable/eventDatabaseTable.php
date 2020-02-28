<?php
class eventDataBaseTable{
    private $pdo;
    
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function giveCoupon($m_id, $deal_id, $status, $end_date){
        $sql = "INSERT INTO `coupon`
                SET
                m_id = :m_id, 
                deal_id = :deal_id,
                status = :status,
                reg_date = NOW(),
                end_date = :end_date
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':deal_id', $deal_id);
        $query->bindValue(':status', $status);
        $query->bindValue(':end_date', $end_date);
        $query->execute();
    }

    public function MyCoupon($m_id){
        $sql = "SELECT * FROM `coupon` WHERE m_id = :m_id AND status = 'N'";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }

    public function goEvent($cp_id){
        //FOR UPDATE
        $sql = "SELECT * FROM `coupon` WHERE cp_id = :cp_id FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->execute();
        //쿠폰 사용처리
        $sql = "UPDATE `coupon` SET status = 'U' WHERE cp_id = :cp_id"; 
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->execute();
    }

    public function insertEvent($m_id, $cp_id, $winner){
        $sql = "INSERT `event` SET m_id=:m_id, cp_id=:cp_id, winner=:winner, reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':winner', $winner);
        $query->execute();
        return;
    }

    //유저 쿠폰 조회
    public function userCoupon($m_id){
        $sql = "SELECT * FROM `coupon` WHERE m_id = :m_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }
    //이벤트 당첨자 조회
    public function findWinner($winner){
        $sql = "SELECT * FROM `event` WHERE winner = :winner";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':winner', $winner);
        $query->execute();
        $result = count($query->fetchAll());
        return $result;
    }

    public function findMeWinner($winner, $m_id){
        $sql = "SELECT * FROM `event` WHERE m_id = :m_id AND winner = :winner";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':winner', $winner);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        $result = count($query->fetchAll());
        return $result;
    }

    public function selectEvent(){
        $sql = "SELECT * FROM `event` ORDER BY event_id";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
}