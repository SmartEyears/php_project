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

    //회원가입
    public function signup(){
        try{
            if(isset($_POST['member'])){
                $member = $_POST['member'];
                //비밀번호 확인
                if($member['mem_pw'] != $member['mem_pw2']){
                    throw new Exception('입력한 비밀번호가 서로 다릅니다.');
                }
                //공백 검사
                if($member['mem_id'] == ""){
                    throw new Exception('아이디를 입력해주세요');
                }else if(empty($member['mem_pw'])){
                    throw new Exception('비밀번호를 입력해주세요');
                }else if(empty($member['mem_name'])){
                    throw new Exception('이름을 입력해주세요');
                }else if(empty($member['mem_hp'])){
                    throw new Exception('핸드폰 번호를 입력해주세요');
                }
                    $this->pdo->beginTransaction();
                    $this->userTable->validationId($member['mem_id']);
                    $this->userTable->insertUser($member);
                    header('location: index.php?action=home'); 
                    $this->pdo->commit();
            }
            else{
                $title = '회원가입';
                
                return ['template'=>'userSignup.html.php', 'title' => $title ];
            }
        }catch(PDOExeception $e){
            $this->pdo->rollback();
            echo "데이터 베이스 오류!";
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }
    }

     //로그인
    public function userLogin(){
        try{
            if(isset($_POST['login'])){
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
            }else{
                $title = '로그인';
        
                return ['template'=>'userLogin.html.php', 'title' => $title ];
            }
        }catch(PDOException $e){
            $this->pdo->rollback();
            echo "오류가 발생하였습니다.";
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }
    }

    //회원 탈퇴
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
                echo "오류가 발생하였습니다.";
                exit;
            }catch(Exception $e){
                echo "Message:".$e->getMessage();
                exit;
            }

    }

    public function userLogout(){
        $title = '로그아웃';
    
        return ['template'=>'userLogout.html.php', 'title' => $title ];
    }

    public function event(){
        $this->checkSession();
        try{
            if(isset($_POST['cp'])){
                $this->pdo->beginTransaction();
                $cp = $_POST['cp'];
                $cp_id = $cp['id'];
                $cp_count = $cp['count'];
                $m_id = $_SESSION['sess_id'];

                if($cp_count < 1){
                    throw new Exception("쿠폰이 없습니다.");
                }
                //쿠폰 사용으로 업데이트               
                $this->eventTable->usedCoupon($cp_id);
                //당첨알고리즘                
                function winningAlgo($winnerList){
                    $pctg = mt_rand(1,100);
                    if($pctg < 2){
                        $compare = $winnerList[1] ?? 0;
                        if($compare >= 1){
                            return 6;
                        }
                        return 1;
                    }else if($pctg < 4){
                        $compare = $winnerList[2] ?? 0;
                        if($compare >= 3){
                            return 6;
                        }
                        return 2;
                    }else if($pctg < 7){
                        $compare = $winnerList[3] ?? 0;
                        if($compare >= 10){
                            return 6;
                        }
                        return 3;
                    }else if($pctg < 12){
                        $compare = $winnerList[4] ?? 0;
                        if($compare >= 100){
                            return 6;
                        }
                        return 4;
                    }else if($pctg < 22){
                        $compare = $winnerList[5] ?? 0;
                        if($compare >= 1000){
                            return 6;
                        }
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

                if($rank == 1){
                    $this->eventTable->couponTable('P', null, 9, '1등 당첨 90퍼센트 할인', null, null, $_SESSION['sess_id'], null);
                }elseif($rank == 2){
                    $this->eventTable->couponTable('P', null, 5, '2등 당첨 50퍼센트 할인', null, null, $_SESSION['sess_id'], null);
                }elseif($rank == 3){
                    $this->eventTable->couponTable('M', null, 50000, '3등 당첨 50000원 할인', 50000, null, $_SESSION['sess_id'], null);
                }elseif($rank == 4){
                    $this->eventTable->couponTable('M', null, 10000, '4등 당첨 10000원 할인', 10000, null, $_SESSION['sess_id'], null);
                }elseif($rank == 5){
                    $this->eventTable->couponTable('M', null, 5000, '5등 당첨 5000원 할인', 5000, null, $_SESSION['sess_id'], null);
                }elseif($rank == 6){
                    $rank = "꽝";
                }else{
                    throw new Exception("비정상적인 접근입니다.");                    
                }
                               
                $this->pdo->commit();
                return [
                    'template' => 'notice.html.php',
                    'variables' => [
                        'message' => $rank." 입니다.",
                        'location' => "index.php?action=event"
                    ],
                    'title' => "결과"
                ];
            }else{
                $my_ecp = $this->couponTable->userCoupon($_SESSION['sess_id']);
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
        }catch(PDOException $e){
            $this->pdo->rollback();
            header('location:index.php?action=event');
            exit;
        }catch(Exception $e){
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "index.php?action=event"
                ],
                'title' => "결과"
            ];
            exit;
        }
    }
}