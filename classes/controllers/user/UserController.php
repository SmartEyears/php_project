<?php
session_start();

class userController {
    private $pdo;
    private $userTable;
    private $mileageTable;
    private $aesCrypy;
    private $dealTable;
    private $eventTable;

    public function __construct (PDO $pdo,
                                userDatabaseTable $userTable, 
                                mileageDatabaseTable $mileageTable, 
                                AESCrypt $aesCrypt, 
                                dealDatabaseTable $dealTable,
                                eventDatabaseTable $eventTable){
        $this->pdo = $pdo;
        $this->userTable = $userTable;
        $this->mileageTable = $mileageTable;
        $this->aesCrypt = $aesCrypt;
        $this->dealTable = $dealTable;
        $this->eventTable = $eventTable;
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
            $this->rollback();
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
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }catch(PDOException $e){
            $this->pdo->rollback();
            echo "오류가 발생하였습니다.";
            exit;
        }
    }

    //회원 탈퇴
    public function deleteSelf(){
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

    //포인트 내역
    public function pointList(){
        $title = '포인트 내역';
        $money = $this->mileageTable->myMileage($_SESSION['sess_id']);
        $result = $this->mileageTable->searchMileage($_SESSION['sess_id']);
        $list = [];
        foreach ($result as $point){
            $list[] = [
                'status' => $point['status'],
                'plus_minus' => $point['plus_minus'],
                'reason' => $point['reason'],
                'reg_date' => $point["date_format(reg_date,'%Y-%m-%d')"],
                'end_date' => $point["date_format(end_date,'%Y-%m-%d')"]
            ];
        }

        return ['template' => 'userPointList.html.php', 
                'title' => $title,
                'variables' => [
                    'money' => $money,
                    'list' => $list 
                ] 
        ];
    }
     //적립 일자 삭제 
    public function minusMileage(){
        $this->mileageTable->minusMileage();
        header('location: index.php?action=pointList');
    }

    //포인트 충전
    public function pointCharge(){
        try{
            if(isset($_POST['chargeMil'])){
                $charge = $_POST['chargeMil'];
                $charge_id = $charge['id'];
                $charge_mil = $charge['total'];
                $charge_kind = $charge['reason'];
                $charge_fee = $charge['balance'] - $charge['total'];

                if($charge['id']== NULL OR $charge['balance']== NULL OR $charge['reason']== NULL){
                    throw new Exception(' 값이 비었습니다. 빈칸을 모두 채우세요');
                }
                $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                $this->pdo->beginTransaction();
                $this->mileageTable->mileageInsert($charge_id, $charge_mil, $charge_kind, $end_date, "N", "충전 수수료", $charge_fee);
                header('location: index.php?action=pointList');
                $this->pdo->commit();
            }else{
                $title = "포인트 충전";
                return [
                    'template' => 'userPointCharge.html.php',
                    'title' => $title
                ];
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            exit;
        }
    }

    //중고 거래
    public function dealBoard(){
        try{
            //삭제버튼
            if(isset($_POST['delete_id'])){
                $this->pdo->beginTransaction();
                $this->dealTable->findWrite($_POST['delete_id']);
                $this->dealTable->deleteWrite($_POST['delete_id']);
                header('location:index.php?action=dealBoard');
                $this->pdo->commit();
            }
            //구매버튼
            if(isset($_POST['sell'])){
                //변수 정리
                $sell = $_POST['sell'];
                $board_id = $sell['_id'];
                $fee = $sell['fee'];
                $sellerId = $sell['id'];
                $seller_id =  $sell['m_id'];
                $buyer_id = $_SESSION['sess_id'];
                $buyer = $_SESSION['sess_memId'];
                $product = $sell['product'];
                $price = $sell['price'];
    
                $buyer_mil = $this->mileageTable->myMileage($buyer_id);
                
                if(empty($price) OR empty($buyer_mil)){
                    throw new Exception('값이 비었습니다.');
                }

                if($price > $buyer_mil){
                    throw new Exception('잔액이 부족합니다.');
                }else{
                    $this->pdo->beginTransaction();
                    //구매 프로세스
                    //상품 상태 업데이트
                    $this->dealTable->findWrite($board_id); //FOR UPDATE
                    $this->dealTable->updateWrite($board_id); //상품 판매완료 상태로 업데이트
                    //차감 프로세스
                    $this->mileageTable->reduceProcess($buyer_id, $price, $product);
                    //판매자에게 수수료 제외한 마일리지 부여 $id, $save, $reason, $end_date, $status, $kind, $margin
                    $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                    $this->mileageTable->mileageInsert($seller_id, $price-$fee, $product."판매", $end_date, "N", "", "");
                    //거래 내역 추가
                    $this->dealTable->insertDealLog($buyer_id, $buyer, $board_id, $sellerId, $seller_id, $product, $price);
                    //수수료 INSERT 
                    $deal_id = $this->dealTable->findDealId($board_id);
                    $this->dealTable->insertMargin($deal_id, "", $seller_id, $fee, $product."판매");
                    //쿠폰 지급
                    //구매자 1개 2번째 공백은 거래번호 
                    $event_date = "2020-02-29 00:00:00"; //이벤트 종료일
                    $this->eventTable->giveCoupon($buyer_id, $deal_id, "N", $event_date); 
                    //판매자 2개
                    $this->eventTable->giveCoupon($seller_id, $deal_id, "N", $event_date); 
                    $this->eventTable->giveCoupon($seller_id, $deal_id, "N", $event_date);
                    header('location:index.php?action=dealBoard');
                    $this->pdo->commit();
                }
            }
        }catch(PDOException $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }
        
        $title = "중고 거래";
        $result = $this->dealTable->selectWrite();
        $list = [];
        foreach ($result as $board){
            $list[] = [
                '_id' => $board['board_id'],
                'product' => $board['product'],
                'price' => $board['price'],
                'm_id' => $board['m_id'],
                'seller' => $board["seller"],
                'reg_date' => $board["reg_date"],
                'status' => $board['status'],
                'fee' => $board['fee']
            ];
        }
        return [
            'template' => 'userDealBoard.html.php',
            'title' => $title,
            'variables' => [
                'list' => $list 
            ] 
        ];
    }

    //게시판 글쓰기
    public function boardCreate(){
        try{
            if(isset($_POST['board'])){
                    $board = $_POST['board'];
                    $product = $board['product'];
                    $price = $board['price'];
                    $m_id = $board['m_id'];
                    $seller = $board['seller'];

                    if($product == NULL){
                        throw new Exception('상품명을 입력해주세요', 1);
                    }else if($price == NULL){
                        throw new Exception('가격을 입력해주세요', 2);
                    }
                    $this->pdo->beginTransaction();
                    $this->dealTable->write($product, $price, $m_id, $seller);
                    header('location:index.php?action=dealBoard');    
                    $this->pdo->commit();
            }else{
                $title = "글쓰기";
                return [
                    'template' => 'userBoardCreate.html.php',
                    'title' => $title
                ];
            }
        }catch(PDOException $e){
            echo "Message:".$e->getMessage();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            $e->getCode(); // 코드 값으로 별도 처리
            exit;
        }
    }

    public function billLog(){  
        $title = "결제 내역";
        $billList = $this->dealTable->selectBill($_SESSION['sess_id']);
        $list = [];
        foreach ($billList as $bill){
            $list[] = [
                'payment' => $bill['payment'],
                'cost' => $bill['cost'],
                'charge_fee' => $bill['charge_fee'],
                'reg_date' => $bill["reg_date"],
                'bill_id' => $bill["bill_id"]
            ];
        }
        return [
            'template' => 'userBillLog.html.php',
            'title' => $title,
            'variables' =>[ 
                'list'=>$list
             ]
        ];
    }
    
    public function dealLog(){  
        $title = "구매 내역";
        $buyList = $this->dealTable->findBuyList($_SESSION['sess_id']);
        // var_dump($buyList);
        // exit;
        $list = [];
        foreach ($buyList as $buy){
            $list[] = [
                'deal_id' => $buy['deal_id'],
                'product' => $buy['product'],
                'price' => $buy['price'],
                'seller' => $buy['seller'],
                'reg_date' => $buy["reg_date"]
            ];
        }
        return [
            'template' => 'userBuyList.html.php',
            'title' => $title,
            'variables' =>[ 
                'list'=>$list
             ]
        ];
    }

    public function sellLog(){  
        $title = "거래 내역";
        $sellList = $this->dealTable->findSellList($_SESSION['sess_id']);
        $list = [];
        foreach ($sellList as $sell){
            $list[] = [
                'deal_id' => $sell['deal_id'],
                'buyer' => $sell['buyer'],
                'product' => $sell['product'],
                'price' => $sell['price'],
                'seller' => $sell['seller'],
                'reg_date' => $sell["reg_date"]
            ];
        }
        return [
            'template' => 'userSellList.html.php',
            'title' => $title,
            'variables' =>[ 
                'list'=>$list
             ]
        ];
    }

    public function event(){
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
                
                $this->eventTable->goEvent($cp_id);
                //당첨알고리즘
                $first = $this->eventTable->findWinner(1);
                $second = $this->eventTable->findWinner(2);
                $third = $this->eventTable->findWinner(3);
                $fourth = $this->eventTable->findWinner(4);
                $Fifth = $this->eventTable->findWinner(5);

                $pctg = mt_rand(1,100);
                $pctg = 1;
                if($pctg < 2){
                    if($first >= 1){
                        echo "꽝 입니다.";
                        $this->eventTable->insertEvent($m_id, $cp_id, 6);
                    }
                    echo "1등입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 1);
                }else if($pctg < 4){
                    if($second >= 3){
                        echo "꽝 입니다.";
                        $this->eventTable->insertEvent($m_id, $cp_id, 6);
                    }
                    echo "2등입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 2);
                }else if($pctg < 7){
                    if($third >= 10){
                        echo "꽝 입니다.";
                        $this->eventTable->insertEvent($m_id, $cp_id, 6);
                    }
                    echo "3등입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 3);
                }else if($pctg < 12){
                    if($fourth >= 100){
                        echo "꽝 입니다.";
                        $this->eventTable->insertEvent($m_id, $cp_id, 6);
                    }
                    echo "4등입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 4);
                }else if($pctg < 22){
                    if($Fifth >= 1000){
                        echo "꽝 입니다.";
                        $this->eventTable->insertEvent($m_id, $cp_id, 6);
                    }
                    echo "5등입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 5);
                }else{
                    echo "꽝 입니다.";
                    $this->eventTable->insertEvent($m_id, $cp_id, 6);     
                }
                $this->pdo->commit();
                // header('location:index.php?action=event');
            }else{
                $cp_list = $this->eventTable->myCoupon($_SESSION['sess_id']);
                $cp_count = count($cp_list);
                
                $one = $this->eventTable->findMeWinner(1, $_SESSION['sess_id']);
                $two = $this->eventTable->findMeWinner(2, $_SESSION['sess_id']);
                $three = $this->eventTable->findMeWinner(3, $_SESSION['sess_id']);
                $four = $this->eventTable->findMeWinner(4, $_SESSION['sess_id']);
                $five = $this->eventTable->findMeWinner(5, $_SESSION['sess_id']);

                $rank = array($one, $two, $three, $four, $five);

                $title = "이벤트";
                return [
                    'title' => $title,
                    'template' => 'userEvent.html.php',
                    'variables' => [
                        'cp_count' => $cp_count,
                        'cp_list' => $cp_list,
                        'rank' => $rank
                    ]    
                ];
            }
        }catch(PDOException $e){
            $this->pdo->rollback();
            header('location:index.php?action=event');
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }
    }
}