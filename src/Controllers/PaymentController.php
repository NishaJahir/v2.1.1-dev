<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet 
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

namespace Novalnet\Controllers;

use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Novalnet\Helper\PaymentHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\ConfigRepository; 
use Novalnet\Services\TransactionService;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Frontend\Contracts\Checkout;

/**
 * Class PaymentController
 *
 * @package Novalnet\Controllers
 */
class PaymentController extends Controller
{
    use Loggable;
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var SessionStorageService
     */
    private $sessionStorage;

    /**
     * @var basket
     */
    private $basketRepository;

    /**
     * @var AddressRepositoryContract
     */
    private $addressRepository;
    
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var Twig
     */
    private $twig;
    
    /**
     * @var ConfigRepository
     */
    private $config;
    
    /**
     * @var transaction
     */
    private $transaction; 

    /**
     * PaymentController constructor.
     *
     * @param Request $request
     * @param Response $response
     * @param ConfigRepository $config
     * @param PaymentHelper $paymentHelper
     * @param SessionStorageService $sessionStorage
     * @param BasketRepositoryContract $basketRepository
     * @param PaymentService $paymentService
     * @param TransactionService $tranactionService
     * @param Twig $twig
     */
    public function __construct(  Request $request,
                                  Response $response,
                                  ConfigRepository $config,
                                  PaymentHelper $paymentHelper,
                                  AddressRepositoryContract $addressRepository,
                                  FrontendSessionStorageFactoryContract $sessionStorage,
                                  BasketRepositoryContract $basketRepository,             
                                  PaymentService $paymentService,
                                  TransactionService $tranactionService,
                                  Twig $twig
                                )
    {

        $this->request         = $request;
        $this->response        = $response;
        $this->paymentHelper   = $paymentHelper;
        $this->sessionStorage  = $sessionStorage;
        $this->addressRepository = $addressRepository;
        $this->basketRepository  = $basketRepository;
        $this->paymentService  = $paymentService;
        $this->twig            = $twig;
        $this->config          = $config;
        $this->transaction     = $tranactionService;
    }

    /**
     * Novalnet redirects to this page if the payment was executed successfully
     *
     */
    public function paymentResponse() {
        $responseData = $this->request->all();
        $isPaymentSuccess = isset($responseData['status']) && in_array($responseData['status'], ['90','100']);
        $notificationMessage = $this->paymentHelper->getNovalnetStatusText($responseData);
        if ($isPaymentSuccess) {
            $this->paymentService->pushNotification($notificationMessage, 'success', 100);
        } else {
            $this->paymentService->pushNotification($notificationMessage, 'error', 100);    
        }
        
        $responseData['test_mode'] = $this->paymentHelper->decodeData($responseData['test_mode'], $responseData['uniqid']);
        $responseData['amount']    = $this->paymentHelper->decodeData($responseData['amount'], $responseData['uniqid']) / 100;
        $paymentRequestData = $this->sessionStorage->getPlugin()->getValue('nnPaymentDataUpdated');
        $this->sessionStorage->getPlugin()->setValue('nnPaymentData', array_merge($paymentRequestData, $responseData));
        $transactionDetails = $this->transaction->getTransactionData('orderNo', $responseData['order_no']);
        //if(empty($transactionDetails[0]->tid)) {         
            $this->paymentService->validateResponse();
        //}
        $this->sessionStorage->getPlugin()->setValue('nnOrderNo', $responseData['order_no']);
        return $this->response->redirectTo('confirmation');
    }

    /**
     * Process the Form payment
     *
     */
    public function processPayment()
    {
        $requestData = $this->request->all();
        $notificationMessage = $this->paymentHelper->getNovalnetStatusText($requestData);
        $basket = $this->basketRepository->load();  
        $this->getLogger(__METHOD__)->error('basket', $basket);
        $billingAddressId = $basket->customerInvoiceAddressId;
        $address = $this->addressRepository->findAddressById($billingAddressId);
        foreach ($address->options as $option) {
            if ($option->typeId == 9) {
            $dob = $option->value;
            }
       }
        
        $doRedirect = false;
        if($requestData['paymentKey'] == 'NOVALNET_CC' && !empty($requestData['nn_cc3d_redirect']) ) {
              $doRedirect = true;
        }
        $serverRequestData = $this->paymentService->getRequestParameters($this->basketRepository->load(), $requestData['paymentKey'], $doRedirect);
        
        if (empty($serverRequestData['data']['first_name']) && empty($serverRequestData['data']['last_name'])) {
        $notificationMessage = $this->paymentHelper->getTranslatedText('nn_first_last_name_error');
                $this->paymentService->pushNotification($notificationMessage, 'error', 100);
                return $this->response->redirectTo('checkout');
        }
        
        $guarantee_payments = [ 'NOVALNET_SEPA', 'NOVALNET_INVOICE', 'NOVALNET_INSTALMENT_INVOICE',  'NOVALNET_INSTALMENT_SEPA'];        
        if($requestData['paymentKey'] == 'NOVALNET_CC') {
            $serverRequestData['data']['pan_hash'] = $requestData['nn_pan_hash'];
            $serverRequestData['data']['unique_id'] = $requestData['nn_unique_id'];
        $this->sessionStorage->getPlugin()->setValue('nnDoRedirect', $requestData['nn_cc3d_redirect']);
            if(!empty($requestData['nn_cc3d_redirect']) )
            {
                $this->sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData['data']);
                $this->sessionStorage->getPlugin()->setValue('nnPaymentUrl',$serverRequestData['url']);
                $this->paymentService->pushNotification($notificationMessage, 'success', 100);
                return $this->response->redirectTo('place-order');
            }
        }
        // Handles Guarantee, Instalment and Normal Payment
        else if( in_array( $requestData['paymentKey'], $guarantee_payments ) ) 
        {   
            // Mandatory Params For Novalnet SEPA
            if ( in_array($requestData['paymentKey'],['NOVALNET_SEPA', 'NOVALNET_INSTALMENT_SEPA'] ) ) {
                    $serverRequestData['data']['bank_account_holder'] = $requestData['nn_sepa_cardholder'];
                    $serverRequestData['data']['iban'] = $requestData['nn_sepa_iban'];                  
            }            
            
            $guranteeStatus = $this->paymentService->getGuaranteeStatus($this->basketRepository->load(), $requestData['paymentKey']);                        
            
            if('guarantee' == $guranteeStatus || in_array($requestData['paymentKey'],['NOVALNET_INSTALMENT_INVOICE', 'NOVALNET_INSTALMENT_SEPA'] ))
            {    
                $birthday = sprintf('%4d-%02d-%02d',$requestData['nn_guarantee_year'],$requestData['nn_guarantee_month'],$requestData['nn_guarantee_date']);
                $birthday = !empty($dob)? $dob :  $birthday;
                
                if( time() < strtotime('+18 years', strtotime($birthday)) && empty($address->companyName))
                {
                    $notificationMessage = $this->paymentHelper->getTranslatedText('dobinvalid');
                    $this->paymentService->pushNotification($notificationMessage, 'error', 100);
                    return $this->response->redirectTo('checkout');
                }

                    // Guarantee Params Formation 
                    if( $requestData['paymentKey'] == 'NOVALNET_SEPA' ) {
                        $serverRequestData['data']['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
                        $serverRequestData['data']['key']          = '40';
                        $serverRequestData['data']['birth_date']   =  $birthday;
                    } elseif(strpos($requestData['paymentKey'], 'INSTALMENT')) {
                        $serverRequestData['data']['instalment_cycles'] = $requestData['nn_instalment_cycle'];
                        $serverRequestData['data']['instalment_period'] =  '1m';
                        $serverRequestData['data']['birth_date']   =  $birthday;
                        
                    } else {                        
                        $serverRequestData['data']['payment_type'] = 'GUARANTEED_INVOICE';
                        $serverRequestData['data']['key']          = '41';
                        $serverRequestData['data']['birth_date']   =  $birthday;
                    }
            }
        }
        if (!empty ($address->companyName) ) {
            unset($serverRequestData['data']['birth_date']);
        }
       
        
        $orderOb =  $this->paymentHelper->orderObject($requestData['orderId']);
        $serverRequestData['data']['amount'] = $this->paymentHelper->ConvertAmountToSmallerUnit($orderOb->amounts[0]->invoiceTotal);
        $this->getLogger(__METHOD__)->error('request params controller', $serverRequestData);
        $this->sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData);
        if(!empty($requestData['reInit'])) {
            $this->paymentService->paymentCalltoNovalnetServer();
            $this->paymentService->validateResponse();
            return $this->response->redirectTo('confirmation');
            
        } else {
            return $this->response->redirectTo('place-order');
        }
       
        
    }

    /**
     * Process the redirect payment
     *
     */
    public function redirectPayment()
    {
        $nnReinitPayment = $this->sessionStorage->getPlugin()->getValue('nnReinit');
        $this->getLogger(__METHOD__)->error('nnReinitPayment', $nnReinitPayment);
        if(!empty($nnReinitPayment)) {
             $paymentKey = $this->sessionStorage->getPlugin()->getValue('paymentKey');
             $this->getLogger(__METHOD__)->error('key controller', $paymentKey);
             $paymentRequestData = $this->paymentService->getRequestParameters($this->basketRepository->load(), $paymentKey);
             $paymentRequestData = $paymentRequestData['data'];
            $this->getLogger(__METHOD__)->error('key req', $paymentRequestData);
             $paymentUrl = $paymentRequestData['url'];
        } else {
            $paymentRequestData = $this->sessionStorage->getPlugin()->getValue('nnPaymentData');
            $paymentUrl = $this->sessionStorage->getPlugin()->getValue('nnPaymentUrl');
        }
        $orderNo = $this->sessionStorage->getPlugin()->getValue('nnOrderNo');
        $paymentRequestData['order_no'] = $orderNo;
        $this->sessionStorage->getPlugin()->setValue('nnPaymentData', null);
        $this->sessionStorage->getPlugin()->setValue('nnOrderNo', null);
         
        $this->getLogger(__METHOD__)->error('redirectPayment request',  $paymentRequestData);
        if(!empty($paymentRequestData['order_no'])) {
            $this->sessionStorage->getPlugin()->setValue('nnPaymentDataUpdated', $paymentRequestData);  
            return $this->twig->render('Novalnet::NovalnetPaymentRedirectForm', [
                                                               'formData'     => $paymentRequestData,
                                                                'nnPaymentUrl' => $paymentUrl
                                   ]);
        } else {            
            return $this->response->redirectTo('confirmation');
       }
    }
    
    public function payOrderNow()
    {
       
        
    }
    
}
