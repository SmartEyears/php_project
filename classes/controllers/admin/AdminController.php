<?php
session_start();

class AdminController{
    private $adminTable;
    private $aesCrypt;
    private $mileageTable;
    private $dealTable;
    
    public function __construct (adminDatabaseTable $adminTable, 
                                AESCrypt $aesCrypt, 
                                mileageDatabaseTable $mileageTable,
                                dealDatabaseTable $dealTable){
        $this->adminTable = $adminTable;
        $this->aesCrypt = $aesCrypt;
        $this->mileageTable = $mileageTable;
        $this->dealTable = $dealTable;
    }

    //홈
    public function home(){
        $title = 'HOME';
        return ['template' => 'adminHome.html.php', 'title' => $title ];   
    }

    //관리자 로그인
    public function adminLogin(){
        try{
            if(isset($_POST['adminlogin'])){
                $id = $_POST['adminlogin']['mem_id'];
                $pw = $_POST['adminlogin']['mem_pw'];
               
                $author = $this->adminTable->findAdmin($id);
    
                if(!empty($author) && password_verify($pw,$author[2])){
                    //로그인 성공
                    $_SESSION['sess_admin'] = "onlyAdmin";
                    $_SESSION['sess_id'] = $author[0]; 
                    $_SESSION['sess_adminId'] = $this->aesCrypt->decrypt($author[1]); //복호
                    $_SESSION['sess_adminName'] = $this->aesCrypt->decrypt($author[3]);
                    header('location: admin.php?action=home');
                }else{
                    echo "아이디 혹은 비밀번호가 틀렸습니다.";
                    exit;
                }
            }else{
                $title = 'adminLogin';
                return ['template' => 'adminLogin.html.php', 'title' => $title ];
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
        }
    }

    //관리자 로그아웃
    public function adminLogout(){
        $title = 'adminLogout';

        return ['template' => 'adminLogout.html.php', 'title' => $title ];
    }

    //관리자 회원 수정
    public function edit(){
        try{
            if(isset($_POST['member'])){
                if($_POST['member']['mem_pw'] == NULL){
                    
                }
                if($_POST['member']['mem_pw'] != $_POST['member']['mem_pw2']){
                    throw new Exception("입력한 비밀번호가 서로 다릅니다.");
                }
                $this->adminTable->edit($_POST['member']);
                header('location: admin.php?action=adminUserList');
            }else{
                $user = $this->adminTable->findUser($_POST['mem_id']);
                $user = [
                    'm_id' => $user['m_id'],
                    'mem_id' => $user['mem_id'],
                    'mem_pw' => $user['mem_pw'],
                    'mem_name' => $this->aesCrypt->decrypt($user['mem_name']),
                    'mem_hp' => $this->aesCrypt->decrypt($user['mem_hp']),
                    'mem_email' => $this->aesCrypt->decrypt($user['mem_email'])
                ];
                $title = '회원 수정';
                return ['template'=>'adminUserEdit.html.php', 
                        'title' => $title,
                        'variables' => [
                            'user' => $user
                    ] 
                ];
            }
        }catch(Exception $e){
            echo 'Message:'.$e->getMessage();
            exit;
        }
    }

    //관리자 추가
    public function adminAdd(){
        try{
            if($_SESSION['sess_admin'] == "onlyAdmin"){
                if(isset($_POST['admin_mem'])){
                    $admin_mem = $_POST['admin_mem'];
                    
                    //비밀번호 확인
                    if($admin_mem['mem_pw'] != $admin_mem['mem_pw2']){
                        throw new Exception("입력한 비밀번호가 서로 다릅니다.");
                    }
                    
                    //공백 검사
                    $this->adminTable->emptySpace($admin_mem);
        
                    unset($admin_mem['mem_pw2']);
        
                    $this->adminTable->insertAdmin($admin_mem);
                    header('location: admin.php?action=home'); 
                }
                else{
                    $title = '관리자 추가';
                    
                    return ['template'=>'adminAdd.html.php', 'title' => $title ];
                }
            }else{
                header('location: admin.php?action=home');
            }
        }catch(Exception $e){
            echo 'Message:'.$e->getMessage();
            exit;
        }
    }

     //회원정보조회
     public function adminUserList(){
        //관리자만 접속 가능
        if($_SESSION['sess_admin'] == "onlyAdmin"){
            $result = $this->adminTable->selectUser();    
            $list = [];
            foreach ($result as $oneUser){
                $list[] = [
                    'id' => $oneUser['m_id'],
                    'mem_name' => $this->aesCrypt->decrypt($oneUser['mem_name']),
                    'mem_id' => $oneUser['mem_id'],
                    'mem_hp' => $this->aesCrypt->decrypt($oneUser['mem_hp']),
                    'mem_email' => $this->aesCrypt->decrypt($oneUser['mem_email'])
                ];
            }
    
            $title = '회원목록';
    
            return ['template'=>'adminUserList.html.php',
                    'title' => $title, 
                    'variables' => [
                            'list' => $list,
                        ]   
            ];
        }else{
            header('location: index.php?action=home');
        }
    }

    //관리자정보조회
    public function adminList(){
        //관리자만 접속 가능
        if($_SESSION['sess_admin'] == "onlyAdmin"){
            $result = $this->adminTable->selectAdmin($this->aesCrypt->encrypt($_SESSION['sess_adminId']));
            $list = [];
            foreach ($result as $oneAdmin){

                $list[] = [
                    'id' => $oneAdmin['a_id'],
                    'mem_name' => $this->aesCrypt->decrypt($oneAdmin['ad_name']),
                    'mem_id' => $oneAdmin['ad_id'],
                    'mem_hp' => $this->aesCrypt->decrypt($oneAdmin['ad_hp']),
                    'mem_email' => $this->aesCrypt->decrypt($oneAdmin['ad_email'])
                ];
            }
    
            $title = '관리자목록';
    
            return ['template'=>'adminList.html.php',
                    'title' => $title, 
                    'variables' => [
                            'list' => $list,
                        ]   
            ];
        }else{
            header('location: index.php?action=home');
        }
    }

    public function manageMileage(){
        $member = $_POST['member'];
        $id = $member['id'];
        $name = $member['name'];
        $nowMileage = $this->mileageTable->myMileage($id);
        $mil = $this->mileageTable->searchMileage($id);
        $title = '회원 마일리지 관리';
        $list[] = [
            'mem_name' => $name,
            'mileage' => $nowMileage
        ];
        return ['template'=>'adminManageMil.html.php',
                    'title' => $title,
                    'variables' => [
                        'list' => $list,
                        'mil' => $mil
                    ]  
            ];
    }

    //관리자 마일리지 부여
    public function editMileage(){
        $mileage = $_POST['mileage'];
        $id = $mileage['m_id'];
        $plusMinus = $mileage['mil'];
        $balance = $this->mileageTable->myMileage($id);
        $reason = $mileage['reason'];
        $mil_id = $this->mileageTable->selectOldMil($id);
        $mil_id = $mil_id['mil_id'];
        
        if(strlen($mileage['date']) == 5){
            //년단위
            $date = substr($mileage['date'], 0, 1);
            $date = $date." year";
            $now = date("Y-m-d H:i:s");
            $date = date("Y-m-d H:i:s", strtotime($now.$date));
        }else if(strlen($mileage['date']) == 7){
            //1~9월
            $date = substr($mileage['date'], 0, 2);
            $date = $date." month";
            $now = date("Y-m-d H:i:s");
            $date = date("Y-m-d H:i:s", strtotime($now.$date));
        }else if(strlen($mileage['date']) == 6){
            //10~12월
            $date = substr($mileage['date'], 0, 1);
            $date = $date." month";
            $now = date("Y-m-d H:i:s");
            $date = date("Y-m-d H:i:s", strtotime($now.$date));
        }else{
            $date = NULL;
        }
        try{
            if($mileage['status'] == "적립"){
                $status = "N";
                $this->mileageTable->mileageInsert($id, $plusMinus, $reason, $date, "N");    
                header('location:admin.php?action=adminUserList');
            }else if($mileage['status'] == "사용"){
                $status = "U";
                if($balance < $plusMinus){
                    throw new Exception('마일리지가 모자랍니다.');
                }else{
                    $this->mileageTable->reduceMileage($id, $mil_id, $plusMinus, $reason);
                    //오래된 마일리지 부터 차감
                    while($plusMinus > 0){
                        $oldMileage = $this->mileageTable->selectOldMil($id);
                        if($oldMileage['balance'] > $plusMinus){
                            $plusMinus = $oldMileage['balance'] - $plusMinus;
                            $this->mileageTable->updateBalance($plusMinus, "N", $id, $oldMileage['reg_date']);
                            $plusMinus = 0;
                        }else{
                            $plusMinus = $plusMinus - $oldMileage['balance'];
                            $this->mileageTable->updateBalance(0, "U", $id, $oldMileage['reg_date']);
                        }
                    }
                    //상세테이블 인서트
                    $detail = $this->mileageTable->findDetail($mil_id, $id);
                    $this->mileageTable->reduceDetail($detail['re_id'], $detail['mil_id'], $detail['reduce']);
                    header('location:admin.php?action=adminUserList');
                }            
            }else{
                throw new Exception('정상적인 값을 입력해주세요');
            } 
        }catch(Exception $e){
            echo "Meassage:".$e->getMeassage();
            exit;
        }

    }

    //관리자 회원 삭제
    public function delete(){
        try{
            if($_POST['id'] == NULL){
                throw new Exception("값이 비어있습니다. 다시 시도 해주세요.");
            }
            $this->adminTable->delete($_POST['id']);
            header('location: admin.php?action=adminUserList');
        }catch(Exception $e){
            echo "Meassage:".$e->getMeassage();
            exit;
        }
    }

    //관리자 수익금 조회
    public function adminMargin(){
        $totalMargin = $this->dealTable->totalMargin();
        $marginList = $this->dealTable->selectMargin();
        //var_dump($marginList);
        $title = "수익금 조회";
        return ['template'=>'adminMargin.html.php',
                'title' => $title,
                'variables' => [
                    'totalMargin' => $totalMargin,
                    'marginList' => $marginList
                ]
        ];
   }
}