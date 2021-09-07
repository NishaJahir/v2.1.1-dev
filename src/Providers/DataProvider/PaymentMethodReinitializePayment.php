<?php

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helper\PaymentHelper;
use Plenty\Plugin\Log\Loggable;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\ConfigRepository;

class PaymentMethodReinitializePayment
{
  use Loggable;
  
  public function call(Twig $twig, $arg):string
  {
    /** @var PaymentHelper $paymentHelper */
    $paymentHelper = pluginApp(PaymentHelper::class);
    $paymentService = pluginApp(PaymentService::class);
    $config = pluginApp(ConfigRepository::class);
    $paymentKey = 'NOVALNET_SEPA';
    $this->getLogger(__METHOD__)->error('order details', $arg[0]);
    $name = trim($config->get('Novalnet.' . strtolower($paymentKey) . '_payment_name'));
    $paymentName = ($name ? $name : $paymentHelper->getTranslatedText(strtolower($paymentKey)));
    $endCustomerName = 'Norbert Maier';
    $show_birthday = false;
    
    
    return $twig->render('Novalnet::PaymentMethodReinitializePayment', [
      "order" => $arg[0], 
      "paymentMethodId" => 6002,
      'nnPaymentProcessUrl' => $paymentService->getProcessPaymentUrl(),
      'paymentMopKey'     =>  $paymentKey,
      'paymentName' => $paymentName,  
       'endcustomername'=> empty(trim($endUserName)) ? $endCustomerName : $endUserName,
       'nnGuaranteeStatus' => $show_birthday ? $guaranteeStatus : ''  
   ]);
  }
}
