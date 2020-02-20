<?php
session_start();

class userController {
    private $userTable;
    private $mileageTable;
    private $aesCrypy;
    private $dealTable;

    public function __construct (userDatabaseTable $userTable, 
                                mileageDatabaseTable $mileageTable, 
                                AESCrypt $aesCrypt, 
                                dealDatabaseTable $dealTable){
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
                $this->userTable->emptySpace($member);
                
                //아이디 중복 검사
                $this->userTable->validationId($member['mem_id']);
    
                //가입
                $this->userTable->insertUser($member);
                $user = $this->userTable->finduser($member['mem_id']);
                $this->mileageTable->mileageInsert($user['m_id'], 5000, "가입 환영 마일리지", date("Y-m-d H:i:s",strtotime("+1 months")),"N");
                header('location: index.php?action=home'); 
    
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
                        $this->mileageTable->mileageInsert($_SESSION['sess_id'], 100, "출석이벤트", $end_date, 'N');
                    }
                    header('location: index.php?action=home');          
                }else{
                    throw new Exception('아이디 혹은 비밀번호가 틀렸습니다.');
                }
            }else{
                $title = '로그인';
        
                return ['template'=>'userLogin.html.php', 'title' => $title ];
            }
        }catch(Exception $e){
            echo "Message:".$e->getMessage();
            exit;
        }
    }

    //회원 탈퇴
    public function deleteSelf(){
        try{
            if(empty($_POST['id'])){
                throw new Exception('값이 비었습니다.');
            }
            $this->userTable->delete($_POST['id']);
            session_destroy();
            header('location: index.php');
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
        if(isset($_POST['chargeMil'])){
            $charge = $_POST['chargeMil'];
            $charge_id = $charge['id'];
            $charge_mil = $charge['balance'];
            $charge_kind = $charge['reason'];
            $charge_fee = $charge_mil * 0.02;

            try{
                if($charge['id']== NULL OR $charge['balance']== NULL OR $charge['reason']== NULL){
                    throw new Exception(' 값이 비었습니다. 빈칸을 모두 채우세요');
                }
                $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                $this->mileageTable->mileageInsert($charge_id, $charge_mil, $charge_kind, $end_date, "N");
                //수익금 입력
                $this->dealTable->insertMargin("충전 수수료", $charge_fee);
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
                $this->dealTable->deleteWrite($_POST['delete_id']);
                header('location:index.php?action=dealBoard');
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
               
                if($price > $buyer_mil){
                    throw new Exception('잔액이 부족합니다.');
                }else{
                    //판매처리
                    $this->dealTable->updateWrite($board_id);
                    //판매자 마일리지 입급
                    $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
                    $this->mileageTable->mileageInsert($seller_id, $price - $fee, $product." 판매", $end_date,"N");
                    
                    //구매자 마일리지 차감
                    $mil_id = $this->mileageTable->selectOldMil($buyer_id); //가장 오래된 마일리지를 가져온다
                    $this->mileageTable->reduceMileage($buyer_id, $mil_id['mil_id'], $price, $product." 구매"); //차감테이블 인서트
                    //마일리지 차감 계산
                    while($price > 0){
                        $oldMileage = $this->mileageTable->selectOldMil($buyer_id);
                        if($oldMileage['balance'] > $price){
                            $price = $oldMileage['balance'] - $price;
                            $this->mileageTable->updateBalance($price, "N", $buyer_id, $oldMileage['reg_date']);
                            $price = 0;
                        }else{
                            $price = $price - $oldMileage['balance'];
                            $this->mileageTable->updateBalance(0, "U", $buyer_id, $oldMileage['reg_date']);
                        }
                    }
                    
                    //수수료 내역 인서트
                    $this->dealTable->insertMargin("거래 수수료", $fee);
        
                    //상세테이블 인서트
                    $detail = $this->mileageTable->findDetail($mil_id['mil_id'], $buyer_id);
                    $this->mileageTable->reduceDetail($detail['re_id'], $detail['mil_id'], $detail['reduce']);
                    header('location:index.php?action=dealBoard');
                }
            }
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
        if(isset($_POST['board'])){
            $board = $_POST['board'];
            $product = $board['product'];
            $price = $board['price'];
            $m_id = $board['m_id'];
            $seller = $board['seller'];

            $this->dealTable->write($product, $price, $m_id, $seller);
            header('location:index.php?action=dealBoard');
        }else{
            $title = "글쓰기";
            return [
                'template' => 'userBoardCreate.html.php',
                'title' => $title
            ];
        }
    }

}