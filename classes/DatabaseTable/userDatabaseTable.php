<?php
//사용자 정의 함수 모음
class userDatabaseTable {
    private $pdo;
    private $aesCrypt;
    //생성자
    public function __construct (PDO $pdo, AESCrypt $aesCrypt) {
        $this->pdo = $pdo;
        $this->aesCrypt = $aesCrypt;
    }

    public function insertUser($member){
        $id = $member['mem_id'];
        $pw = password_hash($member['mem_pw'], PASSWORD_DEFAULT);
        $name = $member['mem_name'];
        $hp = $member['mem_hp'];
        $email = $member['mem_email'];
        
        //암호화
        $name = $this->aesCrypt->encrypt($name);
        $hp = $this->aesCrypt->encrypt($hp);
        $email = $this->aesCrypt->encrypt($email);
        
        try{
            $sql = 'INSERT INTO mem
                    (mem_id,mem_pw,mem_name,mem_hp,mem_email,regdate)
                    VALUES(:mem_id, :mem_pw, :mem_name, :mem_hp, :mem_email, NOW())';
            $query = $this->pdo->prepare($sql);
            //트랜잭션 시작
            $this->pdo->beginTransaction();  
            
            $query->bindValue(':mem_id', $id);
            $query->bindValue(':mem_pw', $pw);
            $query->bindValue(':mem_name', $name);
            $query->bindValue(':mem_hp', $hp);
            $query->bindValue(':mem_email', $email);
            $query->execute();
            
            //트랜잭션 종료
            $this->pdo->commit();
        }catch(PDOException $e){
            //예외 발생 시 롤백
            $this->pdo->rollback();
            echo "Message:".$e->getMessage();
        }
        
    }
    
    //회원 탈퇴
    public function delete($id){
        $sql = "DELETE FROM mem WHERE m_id = :id";
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();
            $query->bindValue(':id', $id);
            $query->execute();
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
            echo "Message:".$e->getMessage();
            exit;
        }
    }

    //유저 찾기
    public function findUser($userId){
        $sql = 'SELECT * FROM mem WHERE mem_id = :id';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':id', $userId);
        $query->execute();
        return $query->fetch();
    }
    
    public function validationId($id){
        $sql = "SELECT * FROM `mem` WHERE `mem_id` = :id" ;
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':id', $id);
        $query->execute();
        $result = $query->fetchAll();
        try{
            if(!empty($result)){
                throw new Exception('중복 된 아이디 입니다.');
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
        }
    }
}