<?php
class mileageDatabaseTable{
    private $pdo;

    //생성자
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    
    //충전 마일리지
    public function mileageInsert($id, $save, $reason, $end_date, $status, $kind, $fee){
        //적립 테이블 INSERT
        $sql = 'INSERT INTO saving
        (m_id, save, balance, reason, reg_date, end_date, status)
        VALUES(:m_id, :save, :balance, :reason, NOW(), :end_date, :status)';
        $query = $this->pdo->prepare($sql);
        
        $query->bindValue(':m_id', $id);
        $query->bindValue(':save', $save);
        $query->bindValue(':balance', $save);
        $query->bindValue(':reason', $reason);
        $query->bindValue(':end_date', $end_date);
        $query->bindValue(':status', $status);
        $query->execute();

        //마일리지 로그 테이블 INSERT
        $sql = 'INSERT INTO `mil_log`
        (m_id, status, plus_minus, reason, reg_date, end_date)
        VALUES(:m_id, "P", :plus_minus, :reason, NOW(), :end_date)';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $id);
        $query->bindValue(':plus_minus', $save);
        $query->bindValue(':reason', $reason);
        $query->bindValue(':end_date', $end_date);
        $query->execute();

        if($reason == "휴대폰 결제" OR $reason == "신용카드" OR $reason == "상품권" OR $reason == "가상계좌"){
            $mil_id = $this->findMilId($id);
            $sql = 'INSERT INTO `bill` 
                    SET m_id=:m_id,
                        mil_id=:mil_id, 
                        payment = :payment, 
                        cost=:cost, 
                        charge_fee=:charge_fee,
                        reg_date = NOW()';
            $query = $this->pdo->prepare($sql);
            $query->bindValue('m_id', $id);
            $query->bindValue('mil_id', $mil_id);
            $query->bindValue('payment', $reason);
            $query->bindValue('cost', $save+$fee);
            $query->bindValue('charge_fee', $fee);
            $query->execute();
        }

        //수익테이블 INSERT
        if($fee != 0){
            $mil_id = $this->findMilId($id);
            $sql = "SELECT bill_id FROM `bill` WHERE mil_id = :mil_id";
            $query = $this->pdo->prepare($sql);
            $query->bindValue(':mil_id', $mil_id);
            $query->execute();
            $bill_id = $query->fetch();
            // var_dump($bill_id);
            // exit;
            
            $sql = "INSERT INTO `margin`
                    SET `deal_id` = :deal_id,
                        `bill_id` = :bill_id,
                        `m_id` = :m_id,
                        `fee` = :fee,
                        `reason` = :reason,
                        `reg_date` = NOW()
                        ";
            $query = $this->pdo->prepare($sql);
            $query->bindValue(':deal_id', $deal_id ?? 0);
            $query->bindValue(':bill_id', $bill_id['bill_id'] ?? 0);
            $query->bindValue(':m_id', $id);
            $query->bindValue(':fee', $fee);
            $query->bindValue(':reason', $reason);
            $query->execute();
        }
    }

    public function findMilId($m_id){
        $sql = "SELECT mil_id FROM `saving` WHERE m_id = :m_id ORDER BY reg_date DESC LIMIT 1";
        $query = $this->pdo->prepare($sql);
        $query->bindValue('m_id', $m_id);
        $query->execute();
        $mil_id = $query->fetch();
        return $mil_id['mil_id'];
    }
    
    //오래된 마일리지 부터 소모
    public function reduceProcess($buyer_id, $price, $product){
        $sql = "INSERT INTO `mil_log`
                (m_id, status, plus_minus , reason, reg_date)
                VALUES(:m_id,'M',:plus_minus, :reason, NOW())";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $buyer_id);
        $query->bindValue(':plus_minus', $price);
        $query->bindValue(':reason', $product);
        $query->execute();

        while($price > 0){
            $oldMileage = $this->selectOldMil($buyer_id); //FOR UPDATE
            $sql = "UPDATE `saving`
                    SET `balance` = :balance,
                        `status` = :status
                    WHERE m_id = :m_id AND reg_date = :reg_date";
            if($oldMileage["balance"] > $price){
                $this->reduceDetail($oldMileage['mil_id'], $buyer_id, $price, $product);
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

                $this->reduceDetail($oldMileage['mil_id'], $buyer_id, $oldMileage['balance'], $product);
            }
        }
    }
    
    //차감 테이블 INSERT
    private function reduceDetail($mil_id, $id, $reduce, $reason){
        $sql = "INSERT INTO `reduce` 
                SET mil_id=:mil_id,
                    m_id=:m_id,
                    reduce=:reduce,
                    reason=:reason,
                    reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue('mil_id', $mil_id);
        $query->bindValue('m_id', $id);
        $query->bindValue('reduce', $reduce);
        $query->bindValue('reason', $reason);
        $query->execute();
    }
    
    //12시 정각
    //기간만료 된 마일리지 찾기 
    public function minusMileage(){
        $sql = "SELECT * FROM saving 
                WHERE status = 'N' 
                AND date_format(end_date, '%Y-%m-%d') <= date_format(NOW(),'%Y-%m-%d')";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        $result = $query -> fetchAll();
        
        foreach($result as $minus){
            $this->reduceMileage($minus['m_id'], $minus['mil_id'], $minus['balance'], "기간만료"); //소멸내역 INSERT
            $this->updateBalance(0, "E", $minus['m_id'], $minus['reg_date']); //보유 마일리지 차감 saving update 
            $this->reduceDetail($detail['re_id'], $detail['mil_id'], $detail['reduce']);
        }
    }
    
    //사용가능한 가장 오래 된 마일리지
    public function selectOldMil($m_id){
        $sql = "SELECT * FROM `saving`
                WHERE m_id = :m_id AND status = 'N' ORDER BY reg_date ASC LIMIT 1";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetch();
    }

    //출석 이벤트 중복 참여 검사
    public function todayCheak($id){
        $sql = "SELECT * FROM saving 
                WHERE m_id = :m_id AND date_format(reg_date, '%Y-%m-%d') = date_format(NOW(),'%Y-%m-%d')";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $id);
        $query->execute();
        $result = count($query->fetchAll());
        return $result;
    }
    
    //유저 포인트 내역 조회
    public function searchMileage($id){
        $sql = "SELECT log_id, m_id, status, plus_minus, reason, date_format(reg_date,'%Y-%m-%d'), date_format(end_date,'%Y-%m-%d') 
                FROM mil_log
                WHERE m_id = :m_id
                ORDER BY reg_date DESC FOR UPDATE";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $id);
        $query->execute();
        return $query->fetchAll();
    }
    //잔액
    public function myMileage($id){
        $sql = "SELECT SUM(balance) FROM saving
                WHERE m_id = :m_id AND (end_date IS NULL OR end_date >= date_format(NOW(),'%Y-%m-%d'))";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $id);
        $query->execute();
        $money = $query->fetch();
        return $money[0] ?? 0;
    }
}