<?php

namespace App\Http\Controllers;


use Hostville\Dorcas\Sdk;
use Illuminate\Http\Request;
use Dorcas\ModulesLibrary\Models\ModulesLibraryResources;
use Dorcas\ModulesLibrary\Models\ModulesLibraryVideos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Exceptions\RecordNotFoundException;
use Carbon\Carbon;

class HubController extends Controller
{
    const DORCAS_SUBSCRIPTIONS_PLANS = [
        1 => [
            'partner' => 0,
            'plan' => 'starter',
            'title' => 'Starter',
            'next' => 2
        ],
        2 => [
            'partner' => 0,
            'plan' => 'classic',
            'title' => 'Classic',
            'next' => 3
        ],
        3 => [
            'partner' => 0,
            'plan' => 'premium',
            'title' => 'Premium',
            'next' => 0
        ]
    ];

    // STARTER SUBSCRIPTIONS MUST ALWAYS HAVE ONLY 1 MODULE ENTRY BELOW!!!

    const DORCAS_SUBSCRIPTIONS_MODULES = [
        1 => [
            'partner' => 0,
            'subscription_id' => 1,
            'slug' => 'getting_started',
            'title' => 'Getting Started',
            'modules' => ["customers","operations"],
            'trial_days' => 365,
            'cost_month' => 0,
            'cost_year' => 0
        ],
        2 => [
            'partner' => 0,
            'subscription_id' => 2,
            'slug' => 'selling_online',
            'title' => 'Selling Online',
            'modules' => ["ecommerce","sales"],
            'trial_days' => 60,
            'cost_month' => 3000,
            'cost_year' => 30000
        ],
        3 => [
            'partner' => 0,
            'subscription_id' => 2,
            'slug' => 'payroll',
            'title' => 'Payroll',
            'modules' => ["people"],
            'trial_days' => 60,
            'cost_month' => 2000,
            'cost_year' => 20000
        ],
        4 => [
            'partner' => 0,
            'subscription_id' => 2,
            'slug' => 'finance',
            'title' => 'Finance',
            'modules' => ["finance"],
            'trial_days' => 60,
            'cost_month' => 3000,
            'cost_year' => 30000
        ],
        5 => [
            'partner' => 0,
            'subscription_id' => 2,
            'slug' => 'all',
            'title' => 'Complete',
            'modules' => ["customers","operations","ecommerce","sales","people","finance"],
            'trial_days' => 60,
            'cost_month' => 5000,
            'cost_year' => 50000
        ]
    ];

    /**
     * Create a new Dorcas Hub controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getDorcasEdition() {
        return env("DORCAS_EDITION","business");
    }


    /**
     * @param Sdk|null $sdk
     *
     * @return Collection|null
     */
    public function getLibraryResources($resource_type): ?Collection
    {
        $partner_id = !empty($partner->name) ? $partner->id : 0;
        //$company = $this->getCompany();
        //$company->id = 999999999;
        $company_id = 100006;
        # get the user
        $resources = Cache::remember('mli_resources.'.$company_id, 30, function () use ($resource_type) {
            $response = ModulesLibraryResources::where([
            	['partner_id', '=', '0'],
            	['resource_type', '=', $resource_type]
            ])->get()->toArray();
            //if (!$response->isSuccessful()) {
            //    return null;
            //}
            return collect($response)->map(function ($resource) {
                return (object) $resource;
            });
        });
        return $resources;
    }


    public function getLibraryVideos(Request $request, Sdk $sdk): ?Collection
    {
        $company = !empty($request->user()) && !empty($request->user()->company(true, true)) ? $request->user()->company(true, true) : null;
        $partner = null;
        if (!empty($request->user()->partner) && !empty($request->user()->partner['data'])) {
            $partner = (object) $request->user()->partner['data'];
        }
        $partner_id = !empty($partner->id) ? $partner->id : 0;
        $company_id = !empty($company->id) ? $company->id : rand(2000000,3000000);

        $resources = Cache::remember('mda_videos.'.$company_id, 1800, function () use ($partner_id) {
            /*$response = ModulesLibraryVideos::where([
                ['partner_id', '=', $partner_id],
                ['resource_type', '=', 'videos']
            ])->get()->toArray();*/
            $response = collect(config('modules-library.library.sample_resources', []));
            return $response->map(function ($resource) {
                return (object) $resource;
            });
        });
            /*$response = collect(config('modules-library.library.sample_resources', []));
            return $response->map(function ($resource) {
                return (object) $resource;
            });*/

        
        //$view->with('isoCurrencies', $currencies->values()->sortBy('currency'));


        return $resources;
    }


    public function getAuthResources(Request $request, Sdk $sdk): ?Collection
    {
        $company = !empty($request->user()) && !empty($request->user()->company(true, true)) ? $request->user()->company(true, true) : null;
        $partner = null;
        if (!empty($request->user()->partner) && !empty($request->user()->partner['data'])) {
            $partner = (object) $request->user()->partner['data'];
        }
        $partner_id = !empty($partner->id) ? $partner->id : 0;
        $company_id = !empty($company->id) ? $company->id : rand(2000000,3000000);

        $resources = Cache::remember('mau_media.'.$company_id, 30, function () use ($partner_id) {
            $response = collect(config('modules-auth.resources.media', []));
            return $response->map(function ($resource) {
                return (object) $resource;
            });
        });

        return $resources;
    }




    public function getWallet()
    {
        $wallet = [];
        /*$wallet = [
            'balance' => 250
        ];*/

        $config = $this->getCompany()->extra_data;

        if (empty($config['wallet'])) {
            $config['wallet']['NGN'] = [
                'balance' => 0
            ];
        }
        $sdk = app(Sdk::class);
        $query = $sdk->createCompanyService()->addBodyParam('extra_data', $config)->send('PUT');
        if (!$query->isSuccessful()) {
            // do something here
            throw new RecordNotFoundException($query->errors[0]['title'] ?? 'Something went wrong while setting up your wallet.');
        }

        # get the company configuration data
        if (!empty($config) && !empty($config['wallet'])) {
            # we actually have some wallet data
            $wallet = $config['wallet']['NGN'];
        }
        return response()->json($wallet);

    }

    public function incrementWallet(Request $request, Sdk $sdk, $amount)
    {
        try {
            $extraData = (array) $this->getCompany()->extra_data;
            $old_wallet_balance = $extraData['wallet']['NGN']["balance"];
            $extraData['wallet']['NGN'] = [
                'balance' => $request->balance + $amount
            ];
            # add a wallet entry
            $query = $sdk->createCompanyService()->addBodyParam('extra_data', $extraData)->send('PUT');
            # set updates to the account
            if (!$query->isSuccessful()) {
                // do something here
                throw new RecordNotFoundException($query->errors[0]['title'] ?? 'Something went wrong while adding to your wallet.');
            }
        } catch (\Exception $e) {
            throw new \Exception("Error Incrementing Wallet:  ". $e->getMessage());
        }
        return response()->json($query->data);

    }

    /**
     * @param Request $request
     * @param Sdk     $sdk
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function verifyTransaction(Request $request, Sdk $sdk)
    {
        $company = $request->user()->company(true, true);
        # get the company
        $channel = $request->input('channel', 'paystack');
        # get the provider channel
        $transaction = null;
        # the transaction resource
        try {
            switch($channel) {
                case 'paystack':
                    $paystack = new \Yabacon\Paystack(config('services.paystack.secret_key'));
                    $txn = $paystack->transaction->verify([
                        'reference'=> $request->reference
                    ]);
                    $transaction = [
                        'currency' => $txn->data->currency,
                        'amount' => $txn->data->amount / 100,
                        'extra_data' => [
                            'gateway_response' => $txn->data->gateway_response,
                            'channel' => $txn->data->channel,
                            'source_ip' => $txn->data->ip_address,
                            'custom_data' => $txn->data->metadata->custom_fields,
                            'card' => [
                                'auth_code' => $txn->data->authorization->authorization_code,
                                'last4' => $txn->data->authorization->last4,
                                'exp_month' => $txn->data->authorization->exp_month,
                                'exp_year' => $txn->data->authorization->exp_year,
                                'card_type' => $txn->data->authorization->card_type,
                            ]
                        ],
                        'is_successful' => $txn->data->status === 'success' ? 1 : 0
                    ];
                    break;
            }
        } catch (\Yabacon\Paystack\Exception\ApiException $e) {
            Log::error($e->getMessage());
            throw new \RuntimeException('Paystack Error: '.$e->getMessage());
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        }
        $transaction['reference'] = $request->reference;
        $transaction['processor'] = $channel;
        $transaction['plan_id'] = $company->plan['data']['id'] ?? '';
        # set the transaction data

        $transaction_purpose = $request->session()->get('dorcas_transaction_purpose', 'default');

        if ($payment_purpose === "subscription") {

            /*$this->validate($request, [
                'name' => 'required_if:action,update_business|string|max:100'
            ]);*/

            $accessExpiresAt = $request->session()->has('dorcas_subscription_expiry') ? Carbon::parse($request->session()->get('dorcas_subscription_expiry')) : Carbon::now();

            # validate the request
            try {
                $company = $request->user()->company(true, true);
                # get the company information
                    $query = $sdk->createCompanyService()
                                    ->addBodyParam('access_expires_at', $accessExpiresAt)
                                    ->send('PUT');
                    # send the request
                    if (!$query->isSuccessful()) {
                        throw new \RuntimeException('Failed while updating expiry. Please try again.');
                    }
            } catch (\Exception $e) {
                throw new \RuntimeException('Error extending Plan: '.$e->getMessage());
            }

            $transaction['access_expires_at'] = $accessExpiresAt;

            $billsQuery = $sdk->createCompanyResource($company->id)
            ->addBodyParam('transaction', $transaction)
            ->send('post', ['extend-plan']);

        } elseif($payment_purpose === "default") {

            $billsQuery = $sdk->createCompanyResource($company->id)
            ->addBodyParam('transaction', $transaction)
            ->send('post');
        }

        


        if (!$billsQuery->isSuccessful()) {
            $message = $billsQuery->getErrors()[0]['title'] ?? 'Failed while trying to save the billing record.';
            if ($transaction['is_successful'] === 1) {
                $message .= 'Kindly report the issue to customer support, your transaction reference is: '.$transaction['reference'];
            }
            throw new \RuntimeException($message);
        }
        # next up - we need to update the company information

        //send email to payment admin
        
        return response()->json($billsQuery->getData());
    }

    public static function getPartnerSubscriptionMeta(Request $request, Sdk $sdk)
    {
        $company = !empty($request->user()) && !empty($request->user()->company(true, true)) ? $request->user()->company(true, true) : null;

        $plan = $request->session()->get('planConfiguration');
        $plan_name = $plan["name"] ?? "starter";

        $partner_id = 0; // we need to collect real partner ID

        $partnerSubscriptionKey = collect(self::DORCAS_SUBSCRIPTIONS_PLANS)->filter(function ($plans_value, $plans_key) use ($partner_id, $plan_name) {
            return $plans_value["partner"] === $partner_id && $plans_value["plan"] === $plan_name;
        })->keys()->first();
        $partnerSubscriptionValue = collect(self::DORCAS_SUBSCRIPTIONS_PLANS)->filter(function ($plans_value, $plans_key) use ($partner_id, $plan_name) {
            return $plans_value["partner"] === $partner_id && $plans_value["plan"] === $plan_name;
        })->values()->first();
        
        $partnerSubscriptions = collect(self::DORCAS_SUBSCRIPTIONS_PLANS)->filter(function ($plans_value, $plans_key) use ($partner_id, $plan_name) {
            return $plans_value["partner"] === $partner_id;
        })->all();

        $partnerSubscriptionModules = collect(self::DORCAS_SUBSCRIPTIONS_MODULES)->filter(function ($modules_value, $modules_key) use ($partner_id) {
            return $modules_value["partner"] == $partner_id;
        })->all();
        /*->map(function ($modules_value, $modules_key) use ($partner_id) {
            $plans = self::DORCAS_SUBSCRIPTIONS_PLANS;
            $key = $modules_value["subscription_id"];
            return $modules_value[] = $plans[$key];
        })*/

        $partnerSubscriptionModulesCurrent = collect(self::DORCAS_SUBSCRIPTIONS_MODULES)->filter(function ($modules_value, $modules_key) use ($partnerSubscriptionKey) {
            return $modules_value["subscription_id"] == $partnerSubscriptionKey;
        })->all();

        $partnerSubscriptionUpgradeKey = $partnerSubscriptionValue["next"];

        $partnerSubscriptionModulesUpgrade = collect(self::DORCAS_SUBSCRIPTIONS_MODULES)->filter(function ($modules_value, $modules_key) use ($partnerSubscriptionUpgradeKey) {
            return $modules_value["subscription_id"] == $partnerSubscriptionUpgradeKey;
        })->all();

        return array(
            "partnerSubscriptionKey" => $partnerSubscriptionKey,
            "partnerSubscriptionValue" => $partnerSubscriptionValue,
            "partnerSubscriptions" => $partnerSubscriptions,
            "partnerSubscriptionModules" => $partnerSubscriptionModules,
            "partnerSubscriptionModulesCurrent" => $partnerSubscriptionModulesCurrent,
            "partnerSubscriptionModulesUpgrade" => $partnerSubscriptionModulesUpgrade
        );
    }


}
