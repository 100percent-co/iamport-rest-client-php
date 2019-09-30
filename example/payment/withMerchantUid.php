<?php

require_once '../../vendor/autoload.php';

use Iamport\RestClient\Iamport;
use Iamport\RestClient\Request\Payment;

$iamport = new Iamport('imp_apikey', 'ekKoeW8RyKuT0zgaZsUtXXTLQ4AhPFW3ZGseDA6bkA5lamv9OqDMnxyeB9wqOsuO9W3Mx9YSJ4dTqJ3f');

// merchant_uid 로 주문정보 찾기(가맹점의 주문번호)
$request = Payment::withMerchantUid('검색할 주문 번호(merchant_uid)');
// 검색옵션 참조 :  https://api.iamport.kr/#!/payments/getPaymentByMerchantUid
$request->payment_status = 'paid';
$request->sorting        = '-started';
$result                  = $iamport->callApi($request);

if ($result->getSuccess()) {
    /**
     *	Response\Payment 를 가리킵니다. __get을 통해 API의 Item Model의 값들을 모두 property처럼 접근할 수 있습니다.
     *	참고 : https://api.iamport.kr/#!/payments/getPaymentByMerchantUid 의 Response Class Model.
     */
    $payment = $result->getData();

    /*
     *	IMP.request_pay({
     *		custom_data : {my_key : value}
     *	});
     *	와 같이 custom_data를 결제 건에 대해서 지정하였을 때 정보를 추출할 수 있습니다.
     *  (서버에는 json encoded형태로 저장하고 Response Model에서 json_decode 처리합니다.)
     */
    dump('Custom Data :', $payment->custom_data);

    // TODO : 가맹점 DB에서 결제되어야 하는 금액 조회 ( 아래 코드는 예시를 돕고자 작성된 샘플코드로 실제 가맹점의 환경에 맞게 직접 작성하셔야 됩니다.
    $pdo          = new PDO('dsn', 'db username', 'db password');
    $pdoStatement = $pdo->prepare('SELECT amount FROM payments WHERE merchant_uid = :merchant_uid');
    $pdoStatement->bindValue(':merchant_uid', $payment->merchant_uid);
    $pdoStatement->execute();
    $data                  = $pdoStatement->fetch(PDO::FETCH_ASSOC);
    $amount_should_be_paid = $data['amount'];

    // 내부적으로 결제완료 처리하시기 위해서는 (1) 결제완료 여부 (2) 금액이 일치하는지 확인을 해주셔야 합니다.
    if ('paid' === $payment->status && $payment->amount === $amount_should_be_paid) {
        //TODO : 결제성공 처리
    } else {
        //TODO : 결제금액 불일치 혹은 미완료된 결제 처리
    }
} else {
    $error = $result->getError();
    dump("아임포트 API 에러코드 : $error->code");
    dump("아임포트 API 에러메시지 : $error->message");
    // previous에는 에러 추적을 위해 아임포트 API 서버에서 응답하는 에러정보가 아닌 원본 Exception 객체가 담겨있습니다.
    dump('원본 Exception :', $error->previous);
}
