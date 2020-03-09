<?php
class eventDataBaseTable{
    private $pdo;
    
    public function __construct (PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insertEvent($m_id, $cp_id, $winner){
        $sql = "INSERT `event` SET m_id=:m_id, cp_id=:cp_id, winner=:winner, reg_date=NOW()";
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':m_id', $m_id);
        $query->bindValue(':cp_id', $cp_id);
        $query->bindValue(':winner', $winner);
        $query->execute();
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