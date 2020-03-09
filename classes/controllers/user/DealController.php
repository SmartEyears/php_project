<?php
session_start();

class DealController{
    private $pdo;
    private $userTable;
    private $mileageTable;
    private $aesCrypt;
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
}