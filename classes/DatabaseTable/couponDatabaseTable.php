<?php
class couponDatabaseTable{
    private $pdo;

    //생성자
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

    public function logCoupon($cp_id, $m_id, $deal_id, $money, $status, $reason){
        $sql = "INSERT `cp_log` SET cp_id=:cp_id, 
                                    m_id=:m_id, 
                                    board_id=:deal_id, 
                                    money=:money,  
                                    status=:status, 
                                    cl_reason = :reason,
                                    reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':deal_id', $deal_id);
        $query->bindValue(':money', $money);
        $query->bindValue(':status', $status);
        $query->bindValue(':reason', $reason);
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
    public function updateUsedCP($cp_id){
        $sql = "UPDATE `coupon` SET used = 'N' WHERE cp_id = :cp_id";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':cp_id' ,$cp_id);
        $query->execute();
    }
}