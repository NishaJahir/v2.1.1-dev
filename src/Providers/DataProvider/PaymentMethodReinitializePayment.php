<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;

class PaymentMethodReinitializePayment
{
  
  public function call(Twig $twig, $arg):string
  {
    $order = $arg[0];
    /** @var PaymentHelper $paymentHelper */
    $paymentHelper = pluginApp(PaymentHelper::class);
    $paymentService = pluginApp(PaymentService::class);
    $sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);
    $config = pluginApp(ConfigRepository::class);
    $basketRepository = pluginApp(BasketRepositoryContract::class);
    $paymentKey = 'NOVALNET_SEPA';
    $paymentHelper->logger('order details', $arg[0]);
    $name = trim($config->get('Novalnet.' . strtolower($paymentKey) . '_payment_name'));
    $paymentName = ($name ? $name : $paymentHelper->getTranslatedText(strtolower($paymentKey)));
    $endUserName = '';
    $endCustomerName = 'Norbert Maier';
    $show_birthday = false;
    
    foreach($order['properties'] as $property) {
        if($property['typeId'] == 3)
        {
            $mopId = $property['value'];
        }
        if($property['typeId'] == 4)
        {
            $paidStatus = $property['value'];
        }
    }
    
    $paymentKey = $paymentHelper->getPaymentKeyByMop($mopId);
    $orderAmount = $paymentHelper->ConvertAmountToSmallerUnit($order['amounts'][0]['invoiceTotal']);
    $serverRequestData = $paymentService->getRequestParameters($basketRepository->load(), $paymentKey, false, $orderAmount);
       
    $paymentHelper->logger('order amount', $orderAmount);
    $paymentHelper->logger('req12345', $serverRequestData);
    
      
       $sessionStorage->getPlugin()->setValue('nnReinit',1);
       $sessionStorage->getPlugin()->setValue('nnOrderNo',$order['id']);
       $sessionStorage->getPlugin()->setValue('mop',$mopId);
       $sessionStorage->getPlugin()->setValue('paymentKey',$paymentKey);
      
    if ($paymentKey == 'NOVALNET_SOFORT') {
       $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData['data']);
    } else {
        $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData);
    }
    
    return $twig->render('Novalnet::PaymentMethodReinitializePayment', [
      "order" => $arg[0], 
      "paymentMethodId" => 6003,
      'nnPaymentProcessUrl' => $paymentService->getProcessPaymentUrl(),
      'paymentMopKey'     =>  $paymentKey,
      'paymentName' => $paymentName,
      'redirectUrl' => $paymentService->getRedirectPaymentUrl(),
       'endcustomername'=> empty(trim($endUserName)) ? $endCustomerName : $endUserName,
       'nnGuaranteeStatus' => $show_birthday ? $guaranteeStatus : '',
      'reInit' => 1
   ]);
  }
}
