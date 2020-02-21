<?php
//사용자 정의 함수 모음
class adminDatabaseTable {
    private $pdo;
    private $aesCrypt;

    //생성자
    public function __construct (PDO $pdo, AESCrypt $aesCrypt) {
        $this->pdo = $pdo;
        $this->aesCrypt = $aesCrypt;
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
        }
    }

    //수정 for update 적용하기
    public function edit($member){
        $_id = $member['_id'];
        $pw = password_hash($member['mem_pw'], PASSWORD_DEFAULT);
        $name = $this->aesCrypt->encrypt($member['mem_name']);
        $hp = $this->aesCrypt->encrypt($member['mem_hp']);
        $email = $this->aesCrypt->encrypt($member['mem_email']);
        if($member['mem_pw'] == NULL){
            $sql = "UPDATE `mem`
                    SET
                    `mem_name` = :name,
                    `mem_hp` = :hp,
                    `mem_email` = :email
                    WHERE
                        `m_id` = :_id
                        ";
        }else{
            $sql = "UPDATE `mem`
                    SET
                    `mem_pw` = :pw,
                    `mem_name` = :name,
                    `mem_hp` = :hp,
                    `mem_email` = :email
                    WHERE
                        `m_id` = :_id
                        ";
        }
        try{
            $query = $this->pdo->prepare($sql);  //SQL인젝션 예방 PDOStatement 객체를 반환
            $this->pdo->beginTransaction();
            if($member['mem_pw'] != NULL){
                $query->bindValue(':pw', $pw);
            }
            $query->bindValue(':name', $name);
            $query->bindValue(':hp', $hp);
            $query->bindValue(':email', $email);
            $query->bindValue(':_id', $_id);
            $query->execute();
    
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
        }
    }

    //관리자 추가    
    public function insertAdmin($admin){
        $id = $admin['mem_id']; 
        $pw = password_hash($admin['mem_pw'], PASSWORD_DEFAULT);
        $name = $admin['mem_name'];
        $hp = $admin['mem_hp'];
        $email = $admin['mem_email'];
        
        //암호화
        $name = $this->aesCrypt->encrypt($name);
        $hp = $this->aesCrypt->encrypt($hp);
        $email = $this->aesCrypt->encrypt($email);
        
        $sql = 'INSERT INTO        
        admin_mem(ad_id,ad_pw,ad_name,ad_hp,ad_email,regdate)
        VALUES(:ad_id, :ad_pw, :ad_name, :ad_hp, :ad_email, NOW())';
        $query = $this->pdo->prepare($sql);
        try{
            $this->pdo->beginTransaction();

            $query->bindValue(':ad_id', $id);
            $query->bindValue(':ad_pw', $pw);
            $query->bindValue(':ad_name', $name);
            $query->bindValue(':ad_hp', $hp);
            $query->bindValue(':ad_email', $email);
            $query->execute();

            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
            echo "Message:".$e->getMessage();
        }
    }
    
    //회원 목록
    public function selectUser(){
        $sql = "SELECT * FROM mem ORDER BY m_id";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        $row = $query->fetchAll();
        return $row;
    }
    
    //회원 찾기
    public function findUser($userId){
        $sql = 'SELECT * FROM mem WHERE mem_id = :id FOR UPDATE';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':id', $userId);
        $query->execute();
        return $query->fetch();
    }

    //관리자 목록
    public function selectAdmin(){
        $sql = "SELECT * FROM admin_mem ORDER BY ad_id";
        $query = $this->pdo->prepare($sql);
        $query->execute();
        $row = $query->fetchAll();
        return $row;
    }

    //관리자 찾기
    public function findAdmin($userId){
        $sql = 'SELECT * FROM admin_mem WHERE ad_id = :id';
        $query = $this->pdo->prepare($sql);
        $query->bindValue(':id', $userId);
        $query->execute();
        return $query->fetch();
    }
}