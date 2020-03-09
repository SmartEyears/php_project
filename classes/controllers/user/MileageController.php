<?php
session_start();

class MileageController{
    private $pdo;
    private $mileageTable;

    public function __construct (PDO $pdo, mileageDatabaseTable $mileageTable){
        $this->pdo = $pdo;
        $this->mileageTable = $mileageTable;
    }

     //포인트 내역
     public function pointList(){
        $title = '포인트 내역';
        $money = $this->mileageTable->myMileage($_SESSION['sess_id']); //내 잔역
        $result = $this->mileageTable->searchMileage($_SESSION['sess_id']); //마일리지 내역
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

    //포인트 충전
    public function pointCharge(){
        try{
            $charge = $_POST['chargeMil'];
            
            if($charge['id'] != $_SESSION['sess_id']){
                throw new Exception('다시 로그인하세요', 1);
            }
            if($charge['id']== NULL OR $charge['balance']== NULL OR $charge['reason']== NULL){
                throw new Exception('값이 비었습니다.');
            }
            
            $charge_id = $charge['id'];
            $charge_fee = $charge['balance']*0.02;
            $charge_mil = $charge['balance'] - $charge_fee;
            $charge_kind = $charge['reason'];
            $end_date = date("Y-m-d H:i:s",strtotime("+5 year"));
            $this->pdo->beginTransaction();
            $this->mileageTable->mileageInsert($charge_id, $charge_mil, $charge_kind, $end_date, "N", "충전 수수료", $charge_fee);
            $this->pdo->commit();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '충전 성공!',
                    'location' => "mileage.php?action=pointChargeView"
                ],
                'title' => "성공!"
            ];
        }catch(PDOException $e){
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            $this->pdo->rollback();
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => '데이터베이스 오류!',
                    'location' => "mileage.php?action=pointChargeView"
                ],
                'title' => "오류!"
            ];
        }catch(Exception $e){
            //echo "Message:".$e->getMessage()."위치:".$e->getFile().":".$e->getLine();
            if($e->getCode() == 1){
                session_destroy();
            }
            return [
                'template' => '../notice.html.php',
                'variables' => [
                    'message' => $e->getMessage(),
                    'location' => "mileage.php?action=pointChargeView"
                ],
                'title' => "오류!"
            ];
        }
    }

    public function pointChargeView(){
        $title = "포인트 충전";
        return [
            'template' => 'userPointCharge.html.php',
            'title' => $title
        ];
    }

    public function billLog(){  
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