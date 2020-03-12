<?php
session_start();

class DealController{
    private $pdo;
    private $userTable;
    private $mileageTable;
    private $dealTable;
    private $couponTable;

    public function __construct (PDO $pdo,
                                userDatabaseTable $userTable, 
                                mileageDatabaseTable $mileageTable, 
                                dealDatabaseTable $dealTable,
                                couponDatabaseTable $couponTable){
        $this->pdo = $pdo;
        $this->userTable = $userTable;
        $this->mileageTable = $mileageTable;
        $this->dealTable = $dealTable;
        $this->couponTable = $couponTable;
    }

    public function dealBoardView(){
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
    }

    public function dealTry(){
        try{
            if(empty($_POST['deal'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->pdo->beginTransaction();
            $m_id = $_POST['deal']['m_id'];
            $cp_num = $_POST['deal']['cp_num'];
            $deal_id = $_POST['deal']['dealboard_id'];

            if($_SESSION['sess_id'] != $m_id){
                throw new Exception('잘못 된 접근 입니다.');
            }

            if(empty($deal_id)){
                throw new Exception('잘못 된 접근 입니다.');
            }

            $product = $this->dealTable->findWrite($deal_id);

            if(empty($product)){
                throw new Exception('값이 비었습니다.');
            }

            $board_id = $product['board_id'];
            $price = $product['price'];
            $productName = $product['product'];
            
            $coupon = $this->couponTable->CouponValidation($m_id, $cp_num); //for update
            
            if($coupon['status'] == 'U'){
                throw new Exception('이미 사용한 쿠폰 입니다.');
            }

            $couponInfo = $this->couponTable->fingCouponInfo($coupon['cp_num']);
            
            if($couponInfo['cp_type'] == "M"){
                //금액권
                $salePri = $couponInfo['cp_price'];
                $price = $price - $salePri;
            }else if($couponInfo['cp_type'] == "P"){
                //퍼센트
                $salePri = $price * 0.01 * $couponInfo['cp_percent'];
                $price = $price - $salePri;
            }
            //쿠폰 사용처리
            $this->couponTable->usedCoupon($m_id, $cp_num, $board_id, $salePri, 'U');
            //잔액
            $balance = $this->mileageTable->myMileage($m_id);

            if($balance < $price){
                throw new Exception('금액이 부족합니다.');
            }

            $this->dealTable->updateWrite($board_id, 'd', $m_id);
            $this->mileageTable->reduceMileage($m_id, $price, $productName);
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '구매 성공 판매자의 승인을 기다립니다.',
                    'location' => "deal.php?action=dealWait"
                ],
                'title' => "알림!"
            ];
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'title' => "오류!",
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealBoard"
                ]
            ];
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealWait"
                ],
                'title' => "알림!"
            ];
        }
    }

    public function dealOderPage(){
        if(empty($_POST['sell'])){
                throw new Exception('값이 비었습니다.');
        }
        $sell = $_POST['sell'];
        $coupon = $this->couponTable->findMyCoupon($_SESSION['sess_id']);
        //var_dump($coupon);
        if(empty($coupon)){
            throw new Exception('쿠폰 비었습니다.');
        }
        $list = [];
        foreach ($coupon as $cp){
            $list[] = [
                'coupon' => $this->couponTable->fingCouponInfo($cp['cp_num'])
            ];
        }

        $couponList = array();
        for($i=0; $i<sizeof($list); $i++) {
            $couponList[$i] = $list[$i]['coupon'];
        }

        return [
            'template' => 'userOder.html.php',
            'title' => "구매 페이지",
            'variables' => [
                'sell' => $sell,
                'coupon' => $couponList
            ]
        ];
    }

    //중고 거래
    public function deleteBoard(){
        try{
            if(empty($_POST['delete_id'])){
                throw new Exception('값이 비어 있습니다.');
            }
            $this->pdo->beginTransaction();
            $check = $this->dealTable->findWrite($_POST['delete_id']);
            if($check['m_id'] != $_SESSION['sess_id']){
                throw new Exception('비정상적인 시도 입니다.');
            }
            $this->dealTable->deleteWrite($_POST['delete_id']);
            header('location:deal.php?action=dealBoardView');
            $this->pdo->commit();
            
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealBoardView"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealBoardView"
                ],
                'title' => "오류!"
            ];
        }
    }

    //게시판 글쓰기
    public function boardCreate(){
        try{
            if(isset($_POST['board'])){
                $board = $_POST['board'];
                $product_type = $board['product_type'];
                $product = $board['product'];
                $price = $board['price'];
                $m_id = $board['m_id'];
                $seller = $board['seller'];

                if($m_id != $_SESSION['sess_id']){
                    throw new Exception('비정상적인 시도 입니다.');
                }
                if($product == NULL){
                    throw new Exception('상품명을 입력해주세요', 1);
                }
                if($price == NULL){
                    throw new Exception('가격을 입력해주세요', 2);
                }
                $this->pdo->beginTransaction();
                $this->dealTable->write($product_type, $product, $price, $m_id, $seller);
                header('location:deal.php?action=dealBoardView');    
                $this->pdo->commit();
            }else{
                $title = "글쓰기";
                return [
                    'template' => 'userBoardCreate.html.php',
                    'title' => $title
                ];
            }
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealBoardView"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage();
            //$e->getCode(); // 코드 값 반환
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealBoardView"
                ],
                'title' => "오류!"
            ];
        }
    }

    public function dealAgree(){
        try{
            if(empty($_POST['agree'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->pdo->beginTransaction();
            $board_id = $_POST['agree'];
            $product = $this->dealTable->findWrite($board_id);

            if(empty($product['m_id'])){
                throw new Exception('값이 비었습니다.');
            }
            if($product['m_id'] != $_SESSION['sess_id']){
                throw new Exception('잘못된 접근 입니다.');
            }
    
            $seller_id = $product['m_id'];
            $coupon = $this->couponTable->findUseCoupon($board_id);
    
            $product_name = $product['product'];
            $price = $product['price'];
            $buyer_id = $product['buyer'];
            $seller = $product['seller'];
            $user = $this->userTable->selectUser($buyer_id);
    
            //판매 완료로 변환
            $this->dealTable->updateWrite($board_id, 'c', $buyer_id);
            //거래 내역 추가
            $this->dealTable->insertDealLog($buyer_id, $user['mem_id'], $board_id, $seller_id, $seller, $product_name, $price, $coupon['saleprice']);
    
            if(!empty($coupon)){
                $price = $price - $coupon['saleprice'];
            }
            
            $fee = $price *0.05;
           
            $this->mileageTable->mileageInsert($seller_id, $price-$fee, $product_name.'판매', NULL,'N', $fee);
            
            //이벤트 쿠폰 발급 보류 giveCoupon($cp_num, $m_id){
            $this->couponTable->giveCoupon('WZFVEFT4OMCI', 'E', $seller_id);
            $this->couponTable->giveCoupon('WZFVEFT4OMCI', 'E', $seller_id);
            $this->couponTable->giveCoupon('WZFVEFT4OMCI', 'E', $buyer_id);
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '거래완료!',
                    'location' => "deal.php?action=dealingView"
                ],
                'title' => "알림!"
            ];
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage().$e->getFile().':'.$e->getLine();;
            $this->pdo->rollback();
            exit;
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealingView"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage();
            //$e->getCode(); // 코드 값 반환
            return [
                'title' => "오류!",
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealingView"
                ]
            ];
        }
    }

    public function dealRefuse(){
        try{
            if(empty($_POST['refuse'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->pdo->beginTransaction();
            $board_id = $_POST['refuse'];
            $product = $this->dealTable->findWrite($board_id);
            if($product['m_id'] != $_SESSION['sess_id']){
                throw new Exception('잘못된 접근 입니다.');
            }
            $buyer_id = $product['buyer'];
            $this->dealTable->updateWrite($board_id, 's', '');
            //쿠폰 지급 cp_log에서 거래번호로 조회
            $coupon = $this->couponTable->findUseCoupon($board_id); //로그에서 사용된 쿠폰이 있는지 확인
            $product_name = $product['product'];
            if(!empty($coupon)){
                //쿠폰 돌려주고 로그 남김 
                $this->couponTable->usedCoupon($coupon['m_id'], $coupon['cp_num'], $coupon['board_id'], $coupon['saleprice'], 'N');
            }
            //마일리지 입금 할인 금액 있을 경우 빼고 $id, $save, $reason, $end_date, $status, $fee
            $salePri = $coupon['saleprice'] ?? 0;
            $price = $product['price'] - $salePri;
            $this->mileageTable->mileageInsert($buyer_id, $price, $product_name." 거래 취소로 인한 환불", NULL, 'N', '0');
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '취소 완료!',
                    'location' => "deal.php?action=dealingView"
                ],
                'title' => "오류!"
            ];
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealingView"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage();
            //$e->getCode(); // 코드 값 반환
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealingView"
                ],
                'title' => "오류!"
            ];
        }
    }

    public function dealingView(){
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
    }

    public function selfRefuse(){
        try{
            if(empty($_POST['selfRefuse'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->pdo->beginTransaction();
            $board_id = $_POST['selfRefuse'];
            $product = $this->dealTable->findWrite($board_id);
            if($product['buyer'] != $_SESSION['sess_id']){
                throw new Exception('잘못된 접근 입니다.');
            }
            $buyer_id = $product['buyer'];
            $this->dealTable->updateWrite($board_id, 's', '');
            //쿠폰 지급 cp_log에서 거래번호로 조회
            $coupon = $this->couponTable->findUseCoupon($board_id); //로그에서 사용된 쿠폰이 있는지 확인
            if(!empty($coupon)){
                //쿠폰 돌려주고 
                //로그$m_id, $cp_num, $board_id, $saleprice, $status
                $this->couponTable->usedCoupon($coupon['m_id'], $coupon['cp_num'], $coupon['board_id'], $coupon['saleprice'], 'N');
            }
            //마일리지 입금 할인 금액 있을 경우 빼고 $id, $save, $reason, $end_date, $status, $fee
            $salePri = $coupon['saleprice'] ?? 0;
            $price = $product['price'] - $salePri;
            $product_name = $product['name'];
            $this->mileageTable->mileageInsert($buyer_id, $price,'거래 취소로 인한 환불', NULL, 'N', '0');
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '취소 완료!',
                    'location' => "deal.php?action=dealWait"
                ],
                'title' => "오류!"
            ];
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "deal.php?action=dealWait"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage();
            //$e->getCode(); // 코드 값 반환
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "deal.php?action=dealWait"
                ],
                'title' => "오류!"
            ];
        }
    }

    //구매자 구매취소
    public function dealWait(){
        $title="구매 대기";
        $list = $this->dealTable->selectWaitDeal($_SESSION['sess_id']);
        return [
            'template' => 'userDealWait.html.php',
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
}