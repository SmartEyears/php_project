<?php
class couponDatabaseTable{
    private $pdo;

    //생성자
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function createCoupon($cp_num, $cp_type, $cp_price, $cp_percent, $cp_name, $max_num, $start_date, $end_date){
        $sql = "INSERT INTO `coupon`
                SET
                cp_num = :cp_num,
                cp_type = :cp_type,
                cp_price = :cp_price,
                cp_percent = :cp_percent,
                cp_name = :cp_name,
                max_num = :max_num,
                reg_date = NOW(),
                start_date = :start_date,
                end_date = :end_date
                ";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_num', $cp_num);
        $query->bindValue(':cp_type', $cp_type);
        $query->bindValue(':cp_price', $cp_price);
        $query->bindValue(':cp_percent', $cp_percent);
        $query->bindValue(':cp_name', $cp_name);
        $query->bindValue(':max_num', $max_num);
        $query->bindValue(':start_date', $start_date);
        $query->bindValue(':end_date', $end_date);
        $query->execute();
    }
    
    public function selectCoupon(){
        $sql = "SELECT * FROM `coupon` ORDER BY cp_num";
        $query = $this->pdo->prepare($sql);;
        $query->execute();
        return $query->fetchAll();
    }
    //활성 비활성
    public function activationCoupon($status, $cp_num){
        $sql = "UPDATE `coupon` SET status=:status WHERE cp_num=:cp_num";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':status', $status);
        $query->bindValue(':cp_num', $cp_num);
        $query->execute();
    }
    //쿠폰 지급
    public function giveCoupon($cp_num, $m_id){
        $sql = "INSERT `cp_log` SET cp_num=:cp_num, 
                                    m_id=:m_id, 
                                    reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':cp_num', $cp_num);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
    }

    public function CouponValidation($m_id, $cp_num){
        $sql = "SELECT * FROM `cp_log` WHERE m_id = :m_id AND cp_num = :cp_num FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':cp_num', $cp_num);
        $query->execute();
        return $query->fetch();
    }

    public function findCoupon($cp_num){
        $sql = "SELECT * FROM `coupon` WHERE cp_num = :cp_num FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':cp_num' ,$cp_num);
        $query->execute();
        return $query->fetch();
    }

    //쿠폰 발급 횟수 카운트
    public function updateCouponCount($give_num, $cp_num){
        $sql = "UPDATE `coupon` SET give_num = :give_num WHERE cp_num = :cp_num";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':give_num' ,$give_num);
        $query->bindvalue(':cp_num' ,$cp_num);
        $query->execute();
    }

    public function findMyCoupon($m_id){
        $sql = "SELECT cp_num FROM `cp_log` WHERE m_id = :m_id AND status = 'N'";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':m_id', $m_id);
        $query->execute();
        return $query->fetchAll();
    }

    public function fingCouponInfo($cp_num){
        $sql = "SELECT * FROM `coupon` WHERE cp_num=:cp_num";
        $query = $this->pdo->prepare($sql);
        $query->bindvalue(':cp_num', $cp_num);
        $query->execute();
        return $query->fetch();
    }

    public function usedCoupon($m_id, $cp_num, $board_id, $saleprice){
        $sql = "UPDATE `cp_log` 
        SET status = 'U',
            board_id = :board_id,
            saleprice = :saleprice,
            use_date = NOW()
        WHERE cp_num=:cp_num AND m_id=:m_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':cp_num', $cp_num);
        $query->bindValue(':board_id', $board_id);
        $query->bindValue(':saleprice', $saleprice);
        $query->execute();
    }

    public function findUseCoupon(){
        $sql = "SELECT * FROM `cp_log` WHERE board_id = :board_id";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':board_id', $board_id);
        $query->execute();
        return $query->fetch();
    }
}