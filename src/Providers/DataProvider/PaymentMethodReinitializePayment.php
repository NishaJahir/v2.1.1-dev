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
    $serverRequestData = $paymentService->getRequestParameters($basketRepository->load(), $paymentKey);
       
       $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData);
       $sessionStorage->getPlugin()->setValue('nnOrderNo',$order['id']);
       $sessionStorage->getPlugin()->setValue('mop',$mopId);
       $sessionStorage->getPlugin()->setValue('paymentKey',$paymentKey);
    
    if ($paymentKey == 'NOVALNET_SOFORT') {
       $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData['data']);
       $sessionStorage->getPlugin()->setValue('nnPaymentUrl', $serverRequestData['url']);
    }
    return $twig->render('Novalnet::PaymentMethodReinitializePayment', [
      "order" => $arg[0], 
      "paymentMethodId" => 6008,
      'nnPaymentProcessUrl' => $paymentService->getProcessPaymentUrl(),
      'paymentMopKey'     =>  $paymentKey,
      'paymentName' => $paymentName,
      'redirectUrl' => $paymentService->getRedirectPaymentUrl();
       'endcustomername'=> empty(trim($endUserName)) ? $endCustomerName : $endUserName,
       'nnGuaranteeStatus' => $show_birthday ? $guaranteeStatus : '',
      'reInit' => 1
   ]);
  }
}
