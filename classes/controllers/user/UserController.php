<?php
session_start();

class userController {
    private $pdo;
    private $userTable;
    private $mileageTable;
    private $aesCrypt;
    private $eventTable;
    private $couponTable;

    public function __construct (PDO $pdo,
                                userDatabaseTable $userTable, 
                                mileageDatabaseTable $mileageTable, 
                                AESCrypt $aesCrypt, 
                                eventDatabaseTable $eventTable,
                                couponDatabaseTable $couponTable){
        $this->pdo = $pdo;
        $this->userTable = $userTable;
        $this->mileageTable = $mileageTable;
        $this->aesCrypt = $aesCrypt;
        $this->eventTable = $eventTable;
        $this->couponTable = $couponTable;
    }

    public function checkSession(){
        if(empty($_SESSION['sess_id'])){
            header('location:index.php?action=home');
        }
    }

    public function home(){
        $title = 'HOME';

        return ['template' => 'userHome.html.php', 'title' => $title ];
    }

    //회입가입 입력 폼
    public function signUpView(){
        $title = '회원가입';
        
        return ['template'=>'userSignup.html.php', 'title' => $title ];
    }
    //가입
    public function signUp(){
        try{
            if(empty($_POST['member'])){
                throw new Exception('값이 없습니다. 다시 시도하세요');
            }
    
            $member = $_POST['member'];
            //비밀번호 확인
            if($member['mem_pw'] != $member['mem_pw2']){
                throw new Exception('입력한 비밀번호가 서로 다릅니다.');
            }
            //공백 검사
            if($member['mem_id'] == ""){
                throw new Exception('아이디를 입력해주세요');
            }
    
            if(empty($member['mem_pw'])){
                throw new Exception('비밀번호를 입력해주세요');
            }
    
            if(empty($member['mem_name'])){
                throw new Exception('이름을 입력해주세요');
            }
    
            if(empty($member['mem_hp'])){
                throw new Exception('핸드폰 번호를 입력해주세요');
            }
    
            $this->pdo->beginTransaction();
            $result = $this->userTable->validationId($member['mem_id']);
            if(!empty($result)){
                throw new Exception('중복 된 아이디 입니다.');
            }
            $this->userTable->insertUser($member);
            $this->pdo->commit();
            header('location:index.php?action=home'); 
        }catch(PDOException $e){
            $this->pdo->rollback();
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "index.php?action=signUpView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=signUpView"
                ],
                'title' => "오류"
            ];
        }
    }

     //로그인
    public function userLoginView(){  
        $title = '로그인';

        return ['template'=>'userLogin.html.php', 'title' => $title ];
    }

    public function userLogin(){
        try{
            if(empty($_POST['login'])){
                throw new Exception('값이 없습니다. 다시 시도하세요');
            }

            $id = $_POST['login']['mem_id'];
            $pw = $_POST['login']['mem_pw'];
            
            if(empty($id)){
                throw new Exception('아이디를 입력해 주세요');
            }

            if(empty($pw)){
                throw new Exception('비밀번호를 입력해 주세요');
            }
            
            $this->pdo->beginTransaction();
            $author = $this->userTable->findUser($id);
            
            if(empty($author)){
                throw new Exception('등록된 회원이 아닙니다.');
            }

            if(!empty($author) && password_verify($pw,$author[2])){
                //로그인 성공
                $_SESSION['sess_id'] = $author['m_id'];
                $_SESSION['sess_memId'] = $author['mem_id'];
                $_SESSION['sess_memName'] = $this->aesCrypt->decrypt($author['mem_name']);
                
                if(empty($_SESSION['sess_id'])){
                    throw new Exception('값이 비었습니다. 다시 시도 해주세요.');
                }
                //마일리지
                $check = $this->mileageTable->todayCheak($_SESSION['sess_id']); //당일 참여 여부
                //미참여 회원 출석 이벤트 포인트 증정
                if(empty($check)){
                    $money = $this->mileageTable->myMileage($_SESSION['sess_id']);
                    $end_date = date("Y-m-d H:i:s",strtotime("+1 months"));
                    $this->mileageTable->mileageInsert($_SESSION['sess_id'], 100, "출석이벤트", $end_date, 'N', "-", 0);
                }
                header('location: index.php?action=home');
            }else{
                throw new Exception('아이디 혹은 비밀번호가 틀렸습니다.');
            }
            $this->pdo->commit();
        }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터 베이스 오류!',
                    'location' => "index.php?action=userLoginView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=userLoginView"
                ],
                'title' => "오류"
            ];
        }
        
    }

    //회원 탈퇴 수정 보완
    public function deleteSelf(){
        $this->checkSession();
        try{
            if(empty($_POST['member']['_id'])){
                throw new Exception('값이 비었습니다.');
            }

            $this->pdo->beginTransaction();
            $user= $this->userTable->findUser($_POST['member']['id']);

            if(empty($user)){
                throw new Exception('값이 비었습니다.');
            }
            $this->userTable->delete($_POST['member']['_id']);
            session_destroy();
            header('location: index.php');
            $this->pdo->commit();

        }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "index.php?action=home"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=home"
                ],
                'title' => "오류"
            ];
        }
    }

    public function userLogout(){
        $this->checkSession();
        $title = '로그아웃';
    
        return ['template'=>'userLogout.html.php', 'title' => $title ];
    }

    public function getCouponView(){
        $title = "쿠폰 추가하기";
        $couponList = $this->couponTable->MyCouponCheck($_SESSION['sess_id']);
        return [
            'title' => $title,
            'template' => 'userGetCoupon.html.php',
            'variables' => [
                'cp_list' => $couponList
            ]
        ];
    }

    public function getCoupon(){
        try{
            if(empty($_POST['coupon_num'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->pdo->beginTransaction();
            $coupon = $this->couponTable->findCoupon($_POST['coupon_num']);
            if(empty($coupon)){
                throw new Exception('해당 쿠폰이 없습니다.');
            }
            if($coupon['status'] == 'D'){
                throw new Exception('해당 쿠폰은 발급 종료되었습니다.');
            }
            $cp_val = $this->couponTable->CouponValidation($_SESSION['sess_id'], $_POST['coupon_num']);
            if(!empty($cp_val)){
                throw new Exception('이미 받으셨습니다.');
            }
            if($coupon['max_num'] <= $coupon['give_num']){
                throw new Exception('발급 횟수 초과 되었습니다.');
            }
            $end_date = strtotime($coupon['end_date']);
            $start_date = strtotime(date('Y-m-d'));
            if($end_date <= $start_date){
                throw new Exception('발급 기간이 지났습니다.');
            }
            $this->couponTable->giveCoupon($_POST['coupon_num'], $coupon['cp_type'], $_SESSION['sess_id']);
            $this->couponTable->updateCouponCount($coupon['give_num']+1, $coupon['cp_num']);
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '발급이 완료 되었습니다.',
                    'location' => "index.php?action=getCouponView"
                ],
                'title' => "알림"
            ];
        }catch(PDOException $e){
            $this->pdo->rollback();
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "index.php?action=getCouponView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=getCouponView"
                ],
                'title' => "오류"
            ];
        }
    }

    public function eventView(){
        $this->checkSession();
        $my_ecp = $this->couponTable->findEventCoupon($_SESSION['sess_id']);
        var_dump($my_ecp);
        $eventList = $this->eventTable->userEventList($_SESSION['sess_id']);
        $cp_count = count($my_ecp);
        $title = "이벤트";
        return [
            'title' => $title,
            'template' => 'userEvent.html.php',
            'variables' => [
                'cp_count' => $cp_count ?? 0,
                'cp_list' => $my_ecp,
                'list' => $eventList
            ]    
        ];
    }

    public function event(){
        $this->checkSession();
        try{
            $this->pdo->beginTransaction();
            $cp = $_POST['cp'];
            $cp_id = $cp['id'];
            $cp_count = $cp['count'];
            $m_id = $_SESSION['sess_id'];

            if($cp_count < 1){
                throw new Exception("쿠폰이 없습니다.");
            }
            //쿠폰 사용으로 업데이트               
            $this->couponTable->usedEventCoupon($cp_id);
            //당첨알고리즘                
            function winningAlgo($winnerList){
                $pctg = mt_rand(1,100);
                if($pctg < 2){
                    $compare = $winnerList[1] ?? 0;
                    if($compare >= 1){return 6;}
                    return 1;
                }else if($pctg < 4){
                    $compare = $winnerList[2] ?? 0;
                    if($compare >= 3){return 6;}
                    return 2;
                }else if($pctg < 7){
                    $compare = $winnerList[3] ?? 0;
                    if($compare >= 10){return 6;}
                    return 3;
                }else if($pctg < 12){
                    $compare = $winnerList[4] ?? 0;
                    if($compare >= 100){return 6;}
                    return 4;
                }else if($pctg < 22){
                    $compare = $winnerList[5] ?? 0;
                    if($compare >= 1000){return 6;}
                    return 5;
                }else{
                    return 6;
                }
            }

            $winnerList = $this->eventTable->findWinner();                
            
            foreach($winnerList as $winner){
                $list[] = [$winner['winner'] => $winner['NUM']];
            }

            $winner = array();
            for($i=0; $i<sizeof($winnerList); $i++) {
                $winner[$winnerList[$i]['winner']] = $winnerList[$i]['NUM'];
            }

            $rank = winningAlgo($winner);
            $this->eventTable->insertEvent($m_id ,$cp_id, $rank);

            //$this->couponTable->
            //함수 개선하기
            if($rank == 1){
                //$this->couponTable->couponTable('P', null, 9, '1등 당첨 90퍼센트 할인', null, null, $_SESSION['sess_id'], null);
            }else if($rank == 2){
                //$this->couponTable->couponTable('P', null, 5, '2등 당첨 50퍼센트 할인', null, null, $_SESSION['sess_id'], null);
            }else if($rank == 3){
                //$this->couponTable->couponTable('M', null, 50000, '3등 당첨 50000원 할인', 50000, null, $_SESSION['sess_id'], null);
            }else if($rank == 4){
                //$this->couponTable->couponTable('M', null, 10000, '4등 당첨 10000원 할인', 10000, null, $_SESSION['sess_id'], null);
            }else if($rank == 5){
                //$this->couponTable->couponTable('M', null, 5000, '5등 당첨 5000원 할인', 5000, null, $_SESSION['sess_id'], null);
            }else if($rank == 6){
                //$rank = "꽝";
            }else{
                throw new Exception("비정상적인 접근입니다.");                    
            }
                            
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $rank." 입니다.",
                    'location' => "index.php?action=eventView"
                ],
                'title' => "결과"
            ];
        }catch(PDOException $e){
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "index.php?action=eventView"
                ],
                'title' => "오류"
            ];
        }catch(Exception $e){
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=eventView"
                ],
                'title' => "오류"
            ];
        }
    }
}