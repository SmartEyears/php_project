<?php
class mileageDatabaseTable{
    private $pdo;

    //생성자
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    
    //마일리지 인서트
    public function mileageInsert($id, $save, $reason, $end_date, $status){
        $sql = 'INSERT INTO saving
        (m_id, save, balance, reason, reg_date, end_date, status)
        VALUES(:m_id, :save, :balance, :reason, NOW(), :end_date, :status)';
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            
            $query->bindValue(':m_id', $id);
            $query->bindValue(':save', $save);
            $query->bindValue(':balance', $save);
            $query->bindValue(':reason', $reason);
            $query->bindValue(':end_date', $end_date);
            $query->bindValue(':status', $status);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
        
        $sql = 'INSERT INTO `mil_log`
        (m_id, status, plus_minus, reason, reg_date, end_date)
        VALUES(:m_id, "P", :plus_minus, :reason, NOW(), :end_date)';
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            $query->bindValue(':m_id', $id);
            $query->bindValue(':plus_minus', $save);
            $query->bindValue(':reason', $reason);
            $query->bindValue(':end_date', $end_date);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }
    
    
    //마일리지 차감테이블 INSERT
    public function reduceMileage($m_id, $mil_id, $reduce, $reason){
        $sql = "INSERT INTO `reduce`
                (m_id, mil_id, reduce, reason, reg_date)
                VALUES(:m_id, :mil_id, :reduce, :reason, NOW())";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            $query->bindValue(':m_id', $m_id);
            $query->bindValue(':mil_id', $mil_id);
            $query->bindValue(':reduce', $reduce);
            $query->bindValue(':reason', $reason);
            
            $query->execute();
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
        
        $sql = "INSERT INTO `mil_log`
                (m_id, status, plus_minus , reason, reg_date)
                VALUES(:m_id,'M',:plus_minus, :reason, NOW())";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            $query->bindValue(':m_id', $m_id);
            $query->bindValue(':plus_minus', $reduce);
            $query->bindValue(':reason', $reason);
            
            $query->execute();
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }
    
    //saving 테이블에 잔액을 소모
    public function updateBalance($balance, $status, $m_id, $reg_date){
        $sql = "UPDATE `saving`
                SET `balance` = :balance,
                    `status` = :status
                WHERE m_id = :m_id AND reg_date = :reg_date";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();

            $query->bindValue(':balance', $balance);
            $query->bindValue(':status', $status);
            $query->bindValue(':m_id', $m_id);
            $query->bindValue(':reg_date', $reg_date);
            $query->execute();
            
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }
    
    //차감 상세 테이블에 기록
    public function reduceDetail($re_id, $mil_id, $reduce){
        $sql = "INSERT INTO `reduce_detail`
                (re_id, mil_id, reduce)
                VALUES
                (:re_id, :mil_id, :reduce)";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            $query->bindValue(':re_id', $re_id);
            $query->bindValue(':mil_id', $mil_id);
            $query->bindValue(':reduce', $reduce);
            
            $query->execute();
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
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
            $detail = $this->findDetail($minus['mil_id'], $minus['m_id']);
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

    //차감 찾아오기
    public function findDetail($mil_id, $m_id){
        $sql = "SELECT * FROM reduce WHERE mil_id = :mil_id AND m_id = :m_id ORDER BY reg_date DESC LIMIT 1";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':mil_id', $mil_id);
        $query->bindValue(':m_id', $m_id);
        $query->execute();
        return $query->fetch();
    }
    
    //유저 포인트 내역 조회
    public function searchMileage($id){
        $sql = "SELECT log_id, m_id, status, plus_minus, reason, date_format(reg_date,'%Y-%m-%d'), date_format(end_date,'%Y-%m-%d') 
                FROM mil_log
                WHERE m_id = :m_id
                ORDER BY reg_date DESC";
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