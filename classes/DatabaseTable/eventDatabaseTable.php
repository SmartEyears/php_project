<?php
class eventDataBaseTable{
    private $pdo;
    
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function giveCoupon($cp_type, $cp_target, $cp_price, $cp_name, $cp_max, $cp_min, $m_id, $end_date){
        $sql = "INSERT INTO `coupon`
                SET
                cp_type = :cp_type,
                cp_target = :cp_target,
                cp_price = :cp_price,
                cp_name = :cp_name,
                cp_max = :cp_max,
                cp_min = :cp_min,
                m_id = :m_id, 
                end_date = :end_date
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_type', $cp_type);
        $query->bindValue(':cp_target', $cp_target);
        $query->bindValue(':cp_price', $cp_price);
        $query->bindValue(':cp_name', $cp_name);
        $query->bindValue(':cp_max', $cp_max);
        $query->bindValue(':cp_min', $cp_min);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':end_date', $end_date);
        $query->execute();
    }

    public function MyCoupon($m_id){
        $sql = "SELECT * FROM `coupon` WHERE m_id = :m_id AND cp_type != 'E' AND used='N'";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll(); 
    }

    //쿠폰 사용처리
    public function usedCoupon($cp_id){
        $sql = "UPDATE `coupon` SET used = 'U' WHERE cp_id = :cp_id"; 
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->execute();
    }

    public function logCoupon($cp_id, $m_id, $deal_id, $money, $money_cut, $status){
        $sql = "INSERT `cp_log` SET cp_id=:cp_id, 
                                    m_id=:m_id, 
                                    board_id=:deal_id, 
                                    money=:money,  
                                    status=:status, 
                                    reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':deal_id', $deal_id);
        $query->bindValue(':money', $money);
        $query->bindValue(':status', $status);
        $query->execute();
    }

    public function insertEvent($m_id, $cp_id, $winner){
        $sql = "INSERT `event` SET m_id=:m_id, cp_id=:cp_id, winner=:winner, reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':winner', $winner);
        $query->execute();
    }

    //유저 쿠폰 조회
    public function userCoupon($m_id){
        $sql = "SELECT * FROM `coupon` WHERE m_id = :m_id AND cp_type ='E' AND used='N'";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }
    //이벤트 당첨자 조회
    public function findWinner(){
        $sql = "SELECT winner, COUNT(*) as NUM FROM `event` GROUP BY winner";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        $result = $query->fetchAll();
        return $result;
    }

    public function findMeWinner($m_id){
        $sql = "SELECT winner, COUNT(*) FROM `event` WHERE m_id = :m_id GROUP BY winner";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        $result = $query->fetchAll();
        return $result;
    }

    public function selectEvent(){
        $sql = "SELECT * FROM `event` ORDER BY event_id";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        return $query->fetchAll();
    }
    
    public function userEventList($m_id){
        $sql = "SELECT * FROM `event` WHERE m_id=:m_id ORDER BY event_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }
}