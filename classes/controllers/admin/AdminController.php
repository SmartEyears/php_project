<?php
session_start();

class AdminController{
    private $pdo;
    private $adminTable;
    private $aesCrypt;
    private $mileageTable;
    private $dealTable;
    private $eventTable;
    private $couponTable;
    
    public function __construct (PDO $pdo,
                                adminDatabaseTable $adminTable, 
                                AESCrypt $aesCrypt, 
                                mileageDatabaseTable $mileageTable,
                                dealDatabaseTable $dealTable,
                                eventDatabaseTable $eventTable,
                                couponDatabaseTable $couponTable){
        $this->pdo = $pdo;
        $this->adminTable = $adminTable;
        $this->aesCrypt = $aesCrypt;
        $this->mileageTable = $mileageTable;
        $this->dealTable = $dealTable;
        $this->eventTable = $eventTable;
        $this->couponTable = $couponTable;
    }

    public function checkSession(){
        if(empty($_SESSION['sess_ad_id'])){
            header('location:admin.php?action=home');
        }
    }

    public function home(){
        $title = 'HOME';
        return ['template' => 'adminHome.html.php', 'title' => $title ];   
    }

    public function adminLogin(){
        try{
            if(empty($_POST['adminlogin'])){
                throw new Exception('비어있는 값입니다.');
            }
            $id = $_POST['adminlogin']['mem_id'];
            $pw = $_POST['adminlogin']['mem_pw'];
            
            $author = $this->adminTable->findAdmin($id);
    
            if(!empty($author) && password_verify($pw,$author[2])){
                //로그인 성공
                $_SESSION['sess_admin'] = "onlyAdmin";
                $_SESSION['sess_ad_id'] = $author[0]; 
                $_SESSION['sess_adminId'] = $this->aesCrypt->decrypt($author[1]); //복호
                $_SESSION['sess_adminName'] = $this->aesCrypt->decrypt($author[3]);
                return [
                    'template' => '../user/notice.html.php',
                    'variables' => [
                        'message' => '로그인 성공! 환영합니다.'.$_SESSION['sess_adminName'].'님',
                        'location' => "admin.php?action=home"
                    ],
                    'title' => "알림!"
                ];
            }else{
                throw new Exception('아이디 혹은 비밀번호가 틀렸습니다.');
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=adminLoginView"
                ],
                'title' => "오류"
            ];
        }
    }

    //관리자 로그인
    public function adminLoginView(){   
        $title = 'adminLogin';

        return ['template' => 'adminLogin.html.php', 'title' => $title ];
    }

    //관리자 로그아웃
    public function adminLogout(){
        session_destroy();
        return [
            'template' => '../user/notice.html.php',
            'variables' => [
                'message' => '로그아웃!',
                'location' => "admin.php?action=home"
            ],
            'title' => "알림!"
        ];
    }

    public function edit(){
        $this->checkSession();
        try{
            if(empty($_POST['member'])){
                throw new Exception("값이 비었습니다.");
            }
            if($_POST['member']['mem_pw'] != $_POST['member']['mem_pw2']){
                throw new Exception("입력한 비밀번호가 서로 다릅니다.");
            }
            $this->pdo->beginTransaction();
            $this->adminTable->edit($_POST['member']);
            $this->pdo->commit();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '수정완료!',
                    'location' => "admin.php?action=adminUserList"
                ],
                'title' => "알림!"
            ];
        }catch(PDOException $e){
            // echo $e->getMessage();
            $this->pdo->rollback();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "admin.php?action=adminUserList"
                ],
                'title' => "알림!"
            ];
        }catch(Exception $e){
            //echo 'Message:'.$e->getMessage();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=adminUserList"
                ],
                'title' => "알림!"
            ];
        }
    }

    //관리자 회원 수정
    public function editView(){
        $this->checkSession();
        try{
            $user = $this->adminTable->findUser($_POST['mem_id']);
            if(empty($user)){
                throw new Exception("오류가 발생하였습니다.");
            }
            $user = [
                'm_id' => $user['m_id'],
                'mem_id' => $user['mem_id'],
                'mem_pw' => $user['mem_pw'],
                'mem_name' => $this->aesCrypt->decrypt($user['mem_name']),
                'mem_hp' => $this->aesCrypt->decrypt($user['mem_hp']),
                'mem_email' => $this->aesCrypt->decrypt($user['mem_email'])
            ];
            $title = '회원 수정';
            return [
                'template'=>'adminUserEdit.html.php', 
                'title' => $title,
                'variables' => [
                    'user' => $user
                ]    
            ];  
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=home"
                ],
                'title' => "알림!"
            ];
        }
    }

    public function adminAdd(){
        $this->checkSession();
        try{
            if(empty($_POST['admin_mem'])){
                throw new Exception("값이 비었습니다.");
            }
            $admin_mem = $_POST['admin_mem'];
            if($admin_mem['mem_pw'] != $admin_mem['mem_pw2']){
                throw new Exception("입력한 비밀번호가 서로 다릅니다.");
            }
            if($admin_mem['mem_id'] == ""){
                throw new Exception('아이디를 입력해주세요');
            }
            if(empty($admin_mem['mem_pw'])){
                throw new Exception('비밀번호를 입력해주세요');
            }
            if(empty($admin_mem['mem_name'])){
                throw new Exception('이름을 입력해주세요');
            }
            if(empty($admin_mem['mem_hp'])){
                throw new Exception('핸드폰 번호를 입력해주세요');
            }
            unset($admin_mem['mem_pw2']);
            $this->pdo->beginTransaction();
            $this->adminTable->insertAdmin($admin_mem);
            $this->pdo->commit();
            header('location: admin.php?action=home');
        }catch(PDOException $e){
            $this->pdo->rollback();
            // echo $e->getMessage();
            echo "데이터베이스 오류!";
            exit;
        }catch(Exception $e){
            echo 'Message:'.$e->getMessage();
            exit;
        }
    }

    public function adminAddView(){
        $this->checkSession();
        $title = '관리자 추가';
        return ['template'=>'adminAdd.html.php', 'title' => $title ];
    }

     //회원정보조회
     public function adminUserList(){
        $this->checkSession();
        $result = $this->adminTable->selectUser(); 
        if(empty($result)){
            throw new Exception('핸드폰 번호를 입력해주세요');
        }
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

        return [
            'template'=>'adminUserList.html.php',
            'title' => $title, 
            'variables' => [
                'list' => $list,
            ]
        ];
    }

    //관리자정보조회
    public function adminList(){
        $this->checkSession();
        //관리자만 접속 가능
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
        return [
            'template'=>'adminList.html.php',
            'title' => $title, 
            'variables' => [
                'list' => $list,
            ]   
        ];
    }

    //회원 마일리지 조회
    public function manageMileage(){
        $this->checkSession();
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
        return [
            'template'=>'adminManageMil.html.php',
            'title' => $title,
            'variables' => [
                'list' => $list,
                'mil' => $mil
            ]  
        ];
    }
    //회원 쿠폰 조회
    public function manageCoupon(){
        $this->checkSession();
        $member = $_POST['member'];
        $id = $member['id'];
        //cp_id, deal_id, status
        $coupon = $this->couponTable->findUserCoupon($id);
        
        $title="쿠폰관리";
        return [
            'template' => 'adminManageCP.html.php',
            'title' => $title,
            'variables' => [
                'list' => $coupon,
                'id' => $id
            ]
        ];
    }

    //관리자 마일리지 부여
    public function editMileage(){
        $this->checkSession();
        try{
            $this->pdo->beginTransaction();
            $mileage = $_POST['mileage'];
            $id = $mileage['m_id'];
            $plusMinus = $mileage['mil'];
            $balance = $this->mileageTable->myMileage($id);
            if(empty($balance)){
                throw new Exception('잔액을 불러오는데 실패하였습니다.');
            }
            $reason = $mileage['reason'];
            $mil_id = $this->mileageTable->selectOldMil($id);
            if(empty($mil_id)){
                throw new Exception('마일리지값을 불러오는데 실패하였습니다.');
            }
            $mil_id = $mil_id['mil_id'];
            
            if(strlen($mileage['date']) == 5){
                $date = substr($mileage['date'], 0, 1);
                $date = $date." year";
                $now = date("Y-m-d H:i:s");
                $date = date("Y-m-d H:i:s", strtotime($now.$date));
            }else if(strlen($mileage['date']) == 7){
                $date = substr($mileage['date'], 0, 2);
                $date = $date." month";
                $now = date("Y-m-d H:i:s");
                $date = date("Y-m-d H:i:s", strtotime($now.$date));
            }else if(strlen($mileage['date']) == 6){
                $date = substr($mileage['date'], 0, 1);
                $date = $date." month";
                $now = date("Y-m-d H:i:s");
                $date = date("Y-m-d H:i:s", strtotime($now.$date));
            }else{
                $date = NULL;
            }      
            if($mileage['status'] == "적립"){
                $status = "N";
                $this->mileageTable->mileageInsert($id, $plusMinus, $reason, $date, "N", "" , ""); 
                $this->pdo->commit();
                header('location:admin.php?action=adminUserList');
            }else if($mileage['status'] == "사용"){
                $status = "U";
                if($balance < $plusMinus){
                    throw new Exception('마일리지가 모자랍니다.');
                }else{
                    $this->mileageTable->reduceProcess($id, $plusMinus, $reason);   
                }
                $this->pdo->commit();
                header('location:admin.php?action=adminUserList');           
            }else{
                throw new Exception('정상적인 값을 입력해주세요');
            }
        }catch(PDOException $e){
            echo "데이터베이스 오류!";
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Meassage:".$e->getMeassage();
            exit;
        }

    }

    //관리자 회원 삭제
    public function delete(){
        $this->checkSession();
        try{
            if($_POST['id'] == NULL){
                throw new Exception("값이 비어있습니다. 다시 시도 해주세요.");
            }
            $this->pdo->beginTransaction();
            $this->adminTable->findAdmin($_POST['id']); 
            $this->adminTable->delete($_POST['id']);
            $this->pdo->commit();
            header('location: admin.php?action=adminUserList');
        }catch(PDOException $e){
            $this->pdo->rollback();
            echo "데이터베이스 오류!";
            exit;
        }catch(Exception $e){
            echo "Meassage:".$e->getMeassage();
            exit;
        }
    }

    //관리자 수익금 조회
    public function adminMargin(){
        try{
            $this->checkSession();
            $totalMargin = $this->dealTable->totalMargin();
            if(empty($totalMargin)){
                throw new Exception('합계 값을 불러오는데 실패했습니다.');
            }
            $marginList = $this->dealTable->selectMargin();
            if(empty($marginList)){
                throw new Exception('리스트를 불러오는데 실패했습니다.');
            }
            $title = "수익금 조회";
            return [
                'title' => $title,
                'template'=>'adminMargin.html.php',
                'variables' => [
                    'totalMargin' => $totalMargin, 
                    'marginList' => $marginList
                ]
            ];
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=home"
                ],
                'title' => "오류"
            ];
        }
    }

    public function winnerList(){
        $this->checkSession();
        try{
            $list = $this->eventTable->selectEvent();
            if(empty($list)){
                throw new Exception('값을 불러오는데 실패했습니다.');
            }
            foreach($list as $winner){
                $user = $this->adminTable->findUser_id($winner['m_id']);
                $user = $user['mem_id'];
                $winnerlist[] = [
                    'event_id' => $winner['event_id'],
                    'memberId' => $user,
                    'winner' => $winner['winner'],
                    'reg_date' => $winner['reg_date']
                ];
            }
            
           $title = "응모현황";
           return [
               'template' => 'adminEventList.html.php',
               'title' => $title,
               'variables' => [
                   'list' => $winnerlist
               ]
           ];
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=home"
                ],
                'title' => "오류"
            ];
        }
    }

    public function CreateCouponView(){
        $this->checkSession();
        $title = "쿠폰 발급";
        
        return [
            'template' => 'adminCouponCreate.html.php',
            'title' => $title
        ];
    }

    public function CreateCoupon(){
        $this->checkSession();
        try{
            if(empty($_POST['coupon'])){
                throw new Exception('값이 비었습니다.'); 
            }
            
            $coupon = $_POST['coupon'];
            
            if(empty($coupon['name'])){
                throw new Exception('이름 값이 비었습니다.');
            }
            
            if($coupon['type'] == "금액권"){
                $coupon['type'] = 'M';
            }else if($coupon['type'] == "퍼센트"){
                $coupon['type'] = 'P';
            }else if($coupon['type'] == "이벤트"){
                $coupon['type'] = 'E';
            }else{
                throw new Exception('타입 값이 비었습니다.');
            }
            
            if(empty($coupon['price'])){
                $coupon['price'] = NULL;
            }
            
            if(empty($coupon['percent'])){
                $coupon['percent'] = NULL;
            }
            if($coupon['type'] != 'E'){
                if(empty($coupon['percent']) AND empty($coupon['price'])){
                    throw new Exception('할인 값을 입력해주세요');
                }
            }
            if(empty($coupon['start_date'])){
                throw new Exception('시작일을 입력해주세요');
            }
            
            if(empty($coupon['end_date'])){
                throw new Exception('종료일 입력해주세요');
            }
            //쿠폰번호 생성
            $len = 12;
            $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
            $result = "";
            srand((double)microtime()*1000000); //난수 초기화
            for($i=0; $i<$len; $i++){
                $couponStr = rand(0, strlen($char));
                $result .= substr($char, $couponStr-1, 1);
            }

            if(!empty($coupon['m_id'])){
                $coupon['max_num'] = 1;
            }
            $this->pdo->beginTransaction();

            $this->couponTable->CreateCoupon($result, $coupon['type'], $coupon['price'], $coupon['percent'], $coupon['name'], $coupon['max_num'],$coupon['start_date'], $coupon['end_date']);

            if(!empty($coupon['m_id'])){
                $this->couponTable->giveCoupon($result, $coupon['type'], $coupon['m_id']);
            }

            $this->pdo->commit();

            return [
                    'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '쿠폰 생성 완료',
                    'location' => "admin.php?action=couponList"
                ],
                'title' => "알림"
            ];
        }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }
    }

    public function couponActivation(){
        try{
            if(empty($_POST['cp_num'])){
                throw new Exception('값이 없습니다.');
            }
            $cp = $this->couponTable->findCoupon($_POST['cp_num']);
            if(empty($cp)){
                throw new Exception('잘못된 쿠폰 번호입니다.');
            }
            $this->couponTable->activationCoupon('A', $cp['cp_num']);
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '쿠폰 활성화 완료',
                    'location' => "admin.php?action=couponList"
                ],
                'title' => "알림"
            ];
        }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }
    }
   
    public function couponDeactivation(){
       try{
           if(empty($_POST['cp_num'])){
               throw new Exception('값이 없습니다.');
           }
           $cp = $this->couponTable->findCoupon($_POST['cp_num']);
           if(empty($cp)){
               throw new Exception('잘못된 쿠폰 번호입니다.');
           }
           $this->couponTable->activationCoupon('D', $cp['cp_num']);
           return [
               'template' => '../user/notice.html.php',
               'variables' => [
                   'message' => '쿠폰 비활성화 완료',
                   'location' => "admin.php?action=couponList"
               ],
               'title' => "알림"
           ];
       }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../user/notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "admin.php?action=CreateCouponView"
                ],
                'title' => "오류"
            ];
        }
    }
   //사용내역
   public function couponList(){
       try{
           $couponList = $this->couponTable->selectCoupon();
        //    if(empty($couponList)){
        //        throw new Exception('값이 없습니다.');
        //    }
           $title = "발급 쿠폰 내역";
           return [
              'template' => 'adminCouponList.html.php',
              'title' => $title,
              'variables' => [
                   'cpList' => $couponList
               ]
          ];
       }catch(Exception $e){
        return [
            'template' => '../user/notice.html.php',
            'variables' => [
                'message' => $e->getMessage(),
                'location' => "admin.php?action=home"
            ],
            'title' => "오류"
        ];
    }
   }
}