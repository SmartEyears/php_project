<?php
session_start();

class MileageController{
    private $pdo;
    private $mileageTable;

    public function __construct (PDO $pdo,
                                mileageDatabaseTable $mileageTable){
        $this->pdo = $pdo;
        $this->mileageTable = $mileageTable;
    }

    public function checkSession(){
        if(empty($_SESSION['sess_id'])){
            header('location:index.php?action=home');
        }
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
                header('location: mileage.php?action=pointList');
            }else{
                $title = "포인트 충전";
                return [
                    'template' => 'userPointCharge.html.php',
                    'title' => $title
                ];
            }
        }catch(PDOException $e){
            echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            return [
                'template' => 'notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "mileage.php?action=pointCharge"
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
                    'location' => "mileage.php?action=pointCharge"
                ],
                'title' => "오류!"
            ];
            exit;
        }
    }

    public function billLog(){  
        $this->checkSession();
        $title = "결제 내역";
        $billList = $this->mileageTable->selectBill($_SESSION['sess_id']);
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

}