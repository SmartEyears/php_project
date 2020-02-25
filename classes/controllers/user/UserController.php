<?php
session_start();

class userController {
    private $pdo;
    private $userTable;
    private $mileageTable;
    private $aesCrypy;
    private $dealTable;

    public function __construct (PDO $pdo,
                                userDatabaseTable $userTable, 
                                mileageDatabaseTable $mileageTable, 
                                AESCrypt $aesCrypt, 
                                dealDatabaseTable $dealTable){
        $this->pdo = $pdo;
        $this->userTable = $userTable;
        $this->mileageTable = $mileageTable;
        $this->aesCrypt = $aesCrypt;
        $this->dealTable = $dealTable;
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
                try{
                    $this->pdo->beginTransaction();
                    $this->userTable->validationId($member['mem_id']);
                    $this->userTable->insertUser($member);
                    header('location: index.php?action=home'); 
                    $this->pdo->commit();
                }catch(PDOExeception $e){
                    $this->rollback();
                    echo "데이터 베이스 오류!";
                    exit;
                }
            }
            else{
                $title = '회원가입';
                
                return ['template'=>'userSignup.html.php', 'title' => $title ];
            }
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
            }catch(Exception $e){
                echo "Message:".$e->getMessage();
                exit;
            }catch(PDOException $e){
                $this->pdo->rollback();
                echo "오류가 발생하였습니다.";
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
        if(isset($_POST['chargeMil'])){
            $charge = $_POST['chargeMil'];
            $charge_id = $charge['id'];
            $charge_mil = $charge['total'];
            $charge_kind = $charge['reason'];
            $charge_fee = $charge['balance'] - $charge['total'];

            try{
                if($charge['id']== NULL OR $charge['balance']== NULL OR $charge['reason']== NULL){
                    throw new Exception(' 값이 비었습니다. 빈칸을 모두 채우세요');
                }
                $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                $this->mileageTable->mileageInsert($charge_id, $charge_mil, $charge_kind, $end_date, "N", "충전 수수료", $charge_fee);
                header('location: index.php?action=pointList');
            }catch(Exception $e){
                echo "Message:".$e->getMessage();
                exit;
            }
        }else{
            $title = "포인트 충전";
            return [
                'template' => 'userPointCharge.html.php',
                'title' => $title
            ];
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
                $seller_id =  $sell['m_id'];
                $buyer_id = $_SESSION['sess_id'];
                $product = $sell['product'];
                $price = $sell['price'];
    
                $buyer_mil = $this->mileageTable->myMileage($buyer_id);
                
                if(empty($price) OR empty($buyer_mil)){
                    throw new Exception('값이 비었습니다.');
                }

                if($price > $buyer_mil){
                    throw new Exception('잔액이 부족합니다.');
                }else{
                    //구매 프로세스
                    //마일리지 로그 테이블에 물건값 차감
                    $this->mileageTable->reduceMileage($buyer_id, $price, $product."구입");
                    //오래 된 마일리지 불러와서 순차로 차감
                    $this->mileageTable->reduceProcess($buyer_id, $price, $product);
                    //판매자에게 수수료 제외한 마일리지 부여 $id, $save, $reason, $end_date, $status, $kind, $margin
                    $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                    $this->mileageTable->mileageInsert($seller_id, $price-$fee, $product."판매", $end_date, "N", "", "");
                    //거래 내역 추가
                    //$this->dealTable->
                    
                    header('location:index.php?action=dealBoard');
                }
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }catch(PDOException $e){
            echo "Message:".$e->getMessage();
            $this->pdo->rollback();
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
        if(isset($_POST['board'])){
            try{
                $board = $_POST['board'];
                $product = $board['product'];
                $price = $board['price'];
                $m_id = $board['m_id'];
                $seller = $board['seller'];

                if($product == NULL){
                    throw new Exception('상품명을 입력해주세요');
                }else if($price == NULL){
                    throw new Exception('가격을 입력해주세요');
                }
                $this->pdo->beginTransaction();
                $this->dealTable->write($product, $price, $m_id, $seller);
                header('location:index.php?action=dealBoard');    
                $this->pdo->commit();
            }catch(Exception $e){
                echo "Message:".$e->getMessage();
                exit;
            }catch(PDOException $e){
                echo "Message:".$e->getMessage();
                $this->pdo->rollback();
                exit;
            }

        }else{
            $title = "글쓰기";
            return [
                'template' => 'userBoardCreate.html.php',
                'title' => $title
            ];
        }
    }

    public function billLog(){  
        $title = "결제 내역";
        $billList = $this->userTable->selectBill($_SESSION['sess_id']);
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
        $title = "거래 내역";
        return [
            'template' => 'userDealLog.html.php',
            'title' => $title
        ];
    }

}