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

    //포인트 내역
    public function pointList(){
        $this->checkSession();
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
        $this->checkSession();
        try{
            if(isset($_POST['chargeMil'])){
                $charge = $_POST['chargeMil'];
                
                if($charge['id'] != $_SESSION['sess_id']){
                    throw new Exception('다시 로그인하세요');
                }
                if($charge['id']== NULL OR $charge['balance']== NULL OR $charge['reason']== NULL){
                    throw new Exception('값이 비었습니다.');
                }
                
                $charge_id = $charge['id'];
                $charge_mil = $charge['balance'] - ($charge['balance']*0.02);
                $charge_kind = $charge['reason'];
                $charge_fee = $charge['balance']*0.02;
                $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                $this->pdo->beginTransaction();
                $this->mileageTable->mileageInsert($charge_id, $charge_mil, $charge_kind, $end_date, "N", "충전 수수료", $charge_fee);
                $this->pdo->commit();
                header('location: index.php?action=pointList');
            }else{
                $title = "포인트 충전";
                return [
                    'template' => 'userPointCharge.html.php',
                    'title' => $title
                ];
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "pointCharge"
                ],
                'title' => "오류!"
            ];
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => "다시 시도 해주세요",
                    'location' => "pointCharge"
                ],
                'title' => "오류!"
            ];
            exit;
        }
    }

    //중고 거래
    public function dealBoard(){
        $this->checkSession();
        try{
            //구매 접수
            if(isset($_POST['deal'])){
                $this->pdo->beginTransaction();
                $m_id = $_POST['deal']['m_id'];
                $cp_id = $_POST['deal']['cp_id'];
                $deal_id = $_POST['deal']['dealboard_id'];

                if($_SESSION['sess_id'] != $m_id){
                    throw new Exception('잘못된 접근 입니다.');
                }

                if(empty($deal_id)){
                    throw new Exception('잘못된 접근 입니다.');
                }

                $product = $this->dealTable->findWrite($deal_id);
                $board_id = $product['board_id'];
                $price = $product['price'];
                $productName = $product['product'];
                
                if(!empty($cp_id)){
                    $coupon = $this->dealTable->selectCoupon($cp_id); //for update
                    if($coupon['cp_type'] == "M"){
                        //금액권
                        $salePri = $coupon['cp_price'];
                        $price = $price - $salePri;
                    }else if($coupon['cp_type'] == "P"){
                        //퍼센트
                        $salePri = $price * 0.1 * $coupon['cp_price'];
                        $price = $price - $salePri;
                    }
                    //쿠폰 사용처리
                    $this->eventTable->usedCoupon($cp_id);
                    //쿠폰 로그 $cp_id, $m_id, $deal_id, $money, $status
                    $this->eventTable->logCoupon($cp_id, $m_id, $deal_id, $salePri, "U", $productName.' 상품에 쿠폰 적용');
                }
                //잔액
                $balance = $this->mileageTable->myMileage($m_id);
                if($balance < $price){
                    throw new Exception('금액이 부족합니다.');
                }
                $this->dealTable->updateWrite($board_id, 'd', $m_id);
                $this->mileageTable->reduceProcess($m_id, $price, $productName);
                $this->pdo->commit();
            }

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
                $coupon = $this->eventTable->myCoupon($_SESSION['sess_id']);
                //var_dump($sell);
                return [
                    'template' => 'userOder.html.php',
                    'title' => "구매 페이지",
                    'variables' => [
                        'sell' => $sell,
                        'coupon' => $coupon
                    ]
                ];
            }
            
            $title = "중고 거래";
            $result = $this->dealTable->selectWrite('s');
            $list = [];
            foreach ($result as $board){
                $list[] = [
                '_id' => $board['board_id'],
                'product_type' => $board['product_type'],
                'product' => $board['product'],
                'price' => $board['price'],
                'm_id' => $board['m_id'],
                'seller' => $board["seller"],
                'reg_date' => $board["reg_date"],
                'status' => $board['status']
                ];
            }
            return [
                'template' => 'userDealBoard.html.php',
                'title' => $title,
                'variables' => [
                    'list' => $list 
                    ] 
                ];
        }catch(PDOException $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "dealBoard"
                ],
                'title' => "오류!"
            ];
            exit;
        }
    }

    //게시판 글쓰기
    public function boardCreate(){
        $this->checkSession();
        try{
            if(isset($_POST['board'])){
                $board = $_POST['board'];
                $product_type = $board['product_type'];
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
                $this->dealTable->write($product_type, $product, $price, $m_id, $seller);
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
            $e->getCode(); // 코드 값 반환
            exit;
        }
    }

    public function billLog(){  
        $this->checkSession();
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
        $this->checkSession();  
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
        $this->checkSession(); 
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
   
    //구매자 구매취소
    public function dealWait(){
        $this->checkSession();
        try{
            if(isset($_POST['selfRefuse'])){
                $this->pdo->beginTransaction();
                $board_id = $_POST['selfRefuse'];
                $product = $this->dealTable->findWrite($board_id);
                $buyer_id = $product['buyer'];
                $this->dealTable->updateWrite($board_id, 's', '');
                //쿠폰 지급 cp_log에서 거래번호로 조회
                $coupon = $this->dealTable->findUseCoupon($board_id); //로그에서 사용된 쿠폰이 있는지 확인
                if(!empty($coupon)){
                    //쿠폰 돌려주고 
                    $this->dealTable->updateUsedCP($coupon['cp_id']);
                    //로그$cp_id, $m_id, $board_id, $money, $status
                    $this->eventTable->logCoupon($coupon['cp_id'], $_SESSION['sess_id'], $board_id, 0, 'G', $product['name'].'구매자 취소');
                }
                //마일리지 입금 할인 금액 있을 경우 빼고 $id, $save, $reason, $end_date, $status, $fee
                $salePri = $coupon['money'] ?? 0;
                $price = $product['price'] - $salePri;
                $product_name = $product['name'];
                $this->mileageTable->mileageInsert($buyer_id, $price,'거래 취소로 인한 환불', NULL, 'N', '0');
                $this->pdo->commit();
                header('location:index.php?action=dealWait');
            }
            $title="구매 대기";
            $list = $this->dealTable->selectWaitDeal($_SESSION['sess_id']);
            return [
                'template' => 'userDealWait.html.php',
                'title' => $title,
                'variables' =>[ 
                    'list'=>$list
                 ]
            ];
        }catch(PDOException $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "dealBoard"
                ],
                'title' => "오류!"
            ];
        }
    }

    public function dealing(){
        $this->checkSession();
        try{
            if(isset($_POST['agree'])){
                //승락
                //마일리지 및 수수료 계산후 분뱌
                $this->pdo->beginTransaction();
                $board_id = $_POST['agree'];
                $product = $this->dealTable->findWrite($board_id);

                $seller_id = $product['m_id'];
                $coupon = $this->dealTable->findUseCoupon($board_id);

                $product_name = $product['product'];
                $price = $product['price'];
                $buyer_id = $product['buyer'];
                $seller = $product['seller'];
                $user = $this->userTable->selectUser($buyer_id);

                //판매 완료로 변환
                $this->dealTable->updateWrite($board_id, 'c', $buyer_id);
                //거래 내역 추가
                $this->dealTable->insertDealLog($buyer_id, $user['mem_id'], $board_id, $seller_id, $seller, $product_name, $price, $coupon['money']);

                if(!empty($coupon)){
                    $price = $price - $coupon['money'];
                }
                
                $fee = $price *0.05;
                $this->mileageTable->mileageInsert($seller_id, $price-$fee, $product_name.'판매', NULL,'N', $fee);

                //이벤트 쿠폰 발급
                //$cp_type, $cp_target, $cp_price, $cp_name, $cp_max, $cp_min, $m_id, $end_date
                $this->eventTable->giveCoupon('E', NULL, NULL, '이벤트 참여 쿠폰', NULL, NULL, $seller_id, NULL);
                $this->eventTable->giveCoupon('E', NULL, NULL, '이벤트 참여 쿠폰', NULL, NULL, $seller_id, NULL);
                $this->eventTable->giveCoupon('E', NULL, NULL, '이벤트 참여 쿠폰', NULL, NULL, $buyer_id, NULL);

                $this->pdo->commit();
            }

            //거절
            if(isset($_POST['refuse'])){
                $this->pdo->beginTransaction();
                $board_id = $_POST['refuse'];
                $product = $this->dealTable->findWrite($board_id);
                $buyer_id = $product['buyer'];
                $this->dealTable->updateWrite($board_id, 's', '');
                //쿠폰 지급 cp_log에서 거래번호로 조회
                $coupon = $this->dealTable->findUseCoupon($board_id); //로그에서 사용된 쿠폰이 있는지 확인
                $product_name = $product['product'];
                if(!empty($coupon)){
                    //쿠폰 돌려주고 로그 남김
                    $this->dealTable->updateUsedCP($coupon['cp_id']);
                    $this->eventTable->logCoupon($coupon['cp_id'], $_SESSION['sess_id'], $board_id, 0, 'G', $product_name.'주문 취소로 인한 재발급');
                }
                //마일리지 입금 할인 금액 있을 경우 빼고 $id, $save, $reason, $end_date, $status, $fee
                $salePri = $coupon['money'] ?? 0;
                $price = $product['price'] - $salePri;
                $this->mileageTable->mileageInsert($buyer_id, $price, $product_name."거래 취소로 인한 환불", NULL, 'N', '0');
                $this->pdo->commit();
                header('location:index.php?action=dealing');
            }

            $title = "진행 중인 거래";
            $dealList = $this->dealTable->selectDealing($_SESSION['sess_id'],'d');
            // var_dump($dealList);
            return [
                'template' => 'userDealPage.html.php',
                'title' => $title,
                'variables' => [
                    'list' =>$dealList
                ]
            ];
        }catch(PDOException $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            exit;
        }catch(Exception $e){
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "dealBoard"
                ],
                'title' => "오류!"
            ];
            exit;
        }

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
                    $this->eventTable->giveCoupon('P', null, 9, '1등 당첨 90퍼센트 할인', null, null, $_SESSION['sess_id'], null);
                }elseif($rank == 2){
                    $this->eventTable->giveCoupon('P', null, 5, '2등 당첨 50퍼센트 할인', null, null, $_SESSION['sess_id'], null);
                }elseif($rank == 3){
                    $this->eventTable->giveCoupon('M', null, 50000, '3등 당첨 50000원 할인', 50000, null, $_SESSION['sess_id'], null);
                }elseif($rank == 4){
                    $this->eventTable->giveCoupon('M', null, 10000, '4등 당첨 10000원 할인', 10000, null, $_SESSION['sess_id'], null);
                }elseif($rank == 5){
                    $this->eventTable->giveCoupon('M', null, 5000, '5등 당첨 5000원 할인', 5000, null, $_SESSION['sess_id'], null);
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
                        'location' => "event"
                    ],
                    'title' => "결과"
                ];
            }else{
                $my_ecp = $this->eventTable->userCoupon($_SESSION['sess_id']);
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
                    'location' => "event"
                ],
                'title' => "결과"
            ];
            exit;
        }
    }
}