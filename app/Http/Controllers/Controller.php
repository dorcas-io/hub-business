<?php

namespace App\Http\Controllers;


use App\Dorcas\Support\Tabler\TablerNotification;
use App\Dorcas\Support\Tabler\TablerNotificationCollection;
use Dorcas\ModulesFinanceTax\Models\TaxAuthorities;
use Hostville\Dorcas\Sdk;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

  /**
   * Container that holds views data for the controller
   *
   * @var array
   */
  protected $data = [];

  /**
   * Controller constructor.
   *
   * We set some defaults for the views data
   */
  public function __construct()
  {
    $this->data = [
      'page' => [
        'title' => config('app.name')
      ]
    ];

    config(['navigation-menu.modules-settings.sub-menu.settings-subscription.visibility' => 'hide']);
    //we need to also do a check on this core controller on if the person rights permission grant access to the route :-)

    //$this->data['overviewModeHeader'] = 'Learning Mode &raquo; <a class="btn btn-primary btn-sm" href="'. route('welcome-overview') . '">Return to Overview Page</a>';
  }

  /**
   * @return null|object
   */
  public function getCompany()
  {
    if (!Auth::check()) {
      return null;
    }
    return Auth::user()->company(true, true);
  }

  /**
   * @return null|object
   */
  public function getCompanyViaDomain()
  {
    $request = app()->make('request');
    $domain = $request->session()->get('domain');
    if (empty($domain)) {
      return null;
    }
    return (object) $domain->owner['data'];
  }

  /**
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getAdverts(Sdk $sdk = null): ?Collection
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = $this->getCompany();
    # get the user
    $adverts = Cache::remember('adverts.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createAdvertResource()->addQueryArgument('limit', 100)->send('GET');
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($advert) {
        return (object) $advert;
      });
    });
    return $adverts;
  }

  /**
   * Returns the bank account for the authenticated user.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getBankAccounts(Sdk $sdk = null): ?Collection
  {
    $sdk = $sdk ?: app(Sdk::class);
    $user = auth()->user();
    # get the user
    $bankAccounts = Cache::remember('user.bank-accounts.'.$user->id, 30, function () use ($sdk) {
      $response = $sdk->createProfileService()->send('GET', ['bank-accounts']);
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($bankAccount) {
        return (object) $bankAccount;
      });
    });
    return $bankAccounts;
  }

  /**
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getBlogCategories(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $categories = Cache::remember('business.blog-categories.'.$company->id, 30, function () use ($sdk) {
      $query = $sdk->createBlogResource()->addQueryArgument('limit', 10000)->send('GET', ['categories']);
      # get the response
      if (!$query->isSuccessful() || empty($query->getData())) {
        return null;
      }
      return collect($query->getData())->map(function ($category) {
        return (object) $category;
      });
    });
    return $categories;
  }

  /**
   * Returns the custom fields created by the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getContactFields(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $contactFields = Cache::remember('crm.custom-fields.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createContactFieldResource()->addQueryArgument('limit', 100)->send('get');
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($customField) {
        return (object) $customField;
      });
    });
    //echo $contactFields; 
    return $contactFields;
  }

  /**
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getGroups(Sdk $sdk = null): ?Collection
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = $this->getCompany();
    # get the user
    $groupd = Cache::remember('crm.groups.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createGroupResource()->addQueryArgument('limit', 200)->send('GET');
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($group) {
        return (object) $group;
      });
    });
    return $groupd;
  }

  /**
   * Returns the customers owned by the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getCustomers(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $customers = Cache::remember('crm.customers.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createCustomerResource()->addQueryArgument('limit', 10000)->send('get');
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($customer) {
        return (object) $customer;
      });
    });
    return $customers;
  }

  /**
   * Returns the departments in the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getDepartments(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $departments = Cache::remember('business.departments.'.$company->id, 30, function () use ($sdk) {
      $query =  $sdk->createDepartmentResource()->addQueryArgument('limit', 10000)->send('get');
      # send the request
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($department) {
        return (object) $department;
      });
    });
    return $departments;
  }

  /**
   * Returns the domains belonging to the currently authenticated business.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection
   */
  public function getDomains(Sdk $sdk = null): ?Collection
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $domains = Cache::remember('ecommerce.domains.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createDomainResource()->addQueryArgument('limit', 1000)->send('get');
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($domain) {
        return (object) $domain;
      });
    });
    return $domains;
  }

  /**
   * Returns the employees in the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getEmployees(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $employees = Cache::remember('business.employees.'.$company->id, 30, function () use ($sdk) {
      $query =  $sdk->createEmployeeResource()->addQueryArgument('include', 'teams:limit(30|0),department')
        ->addQueryArgument('limit', 10000)
        ->send('get');
      # send the request
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($employee) {
        return (object) $employee;
      });
    });
    return $employees;
  }

  /**
   * @param Sdk|null $sdk
   *
   * @return mixed|Collection|null
   */
  public function getFinanceAccounts(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $accounts = Cache::remember('finance.accounts.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createFinanceResource()->addQueryArgument('limit', 10000)
        ->addQueryArgument('include', 'sub_accounts')
        ->send('get', ['accounts']);
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($account) {
        return (object) $account;
      });
    });

    return $accounts;
  }

  /**
   * @param Sdk|null $sdk
   *
   * @return mixed|Collection|null
   */
  public function getFinanceTaxAuthorities(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
//        $authorities = Cache::remember('finance.tax.authority.'.$company->id, 30, function () use ($sdk) {
    $response = $sdk->createTaxResource()->addQueryArgument('limit', 10000)
      ->send('get', ['authority']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($authorities) {
      return (object) $authorities;
    });
//        });
    return $response;
  }

  public function getPeoplePayrollAuthorities(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

//        $authorities = Cache::remember('payroll.authority.'.$company->id, 30, function () use ($sdk) {
    $response = $sdk->createPayrollResource()->addQueryArgument('limit', 10000)
      ->send('get', ['authority']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($authorities) {
      return (object) $authorities;
    });
//        });
    return $response;
  }

  /*
   * This function gets Approvals
   */

  public function getPeopleApprovals(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $response = $sdk->createApprovalsResource()->addQueryArgument('limit', 10000)
      ->send('get', ['approval']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($approvals) {
      return (object) $approvals;
    });
    return $response;
  }

  public function getTranstrakAccount(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $response = $sdk->createFinanceResource()
      ->send('get', ['transtrak','mail']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($transtrak) {
      return (object) $transtrak;
    });
    return $response;
  }

  public function getUsers(Sdk $sdk= null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    $resource = $sdk->createUserResource()->addQueryArgument('limit', 10000);
    $response =  $resource->relationships('company')->send('get');
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($users) {
      return (object) $users;
    });
    return $response;
  }

  public function getPeopleLeaveTypes(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $response = $sdk->createLeavesResource()->addQueryArgument('limit', 10000)
      ->send('get', ['types']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($leave) {
      return (object) $leave;
    });
    return $response;
  }

  public function getPeopleLeaveGroups(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $response = $sdk->createLeavesResource()->addQueryArgument('limit', 10000)
      ->send('get', ['groups']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($leave) {
      return (object) $leave;
    });
    return $response;
  }

  public function getEmployeeLeaveRequest(Sdk $sdk = null){
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $response = $sdk->createLeavesResource()
      ->addQueryArgument('limit', 10000)
      ->addQueryArgument('user_id', \auth()->user()->id)
      ->send('get', ['requests']);
    if (!$response->isSuccessful()) {
      return null;
    }
    return collect($response->getData())->map(function ($leave) {
      return (object) $leave;
    });
    return $response;
  }
  /**
   * @param Sdk|null $sdk
   *
   * @return mixed|Collection|null
   */
  public function getFinanceReportConfigurations(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $configurations = Cache::remember('finance.report_configurations.'.$company->id, 60, function () use ($sdk) {
      $response = $sdk->createFinanceResource()->addQueryArgument('limit', 10000)
        ->send('get', ['reports', 'configure']);
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($configuration) {
        return (object) $configuration;
      });
    });
    return $configurations;
  }

  /**
   * Returns all the integrations configured by the currently authenticated company
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getIntegrations(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $integrations = Cache::remember('integrations.'.$company->id, 1800, function () use ($sdk) {
      $query =  $sdk->createIntegrationResource()->addQueryArgument('limit', 100)
        ->send('get');
      # send the request
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($integration) {
        return (object) $integration;
      });
    });
    return $integrations;
  }

  /**
   * Returns the locations in the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getLocations(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $locations = Cache::remember('business.locations.'.$company->id, 30, function () use ($sdk, $company) {
      $query = $sdk->createCompanyResource($company->id)->addQueryArgument('include', 'locations')->send('GET');
      # get the response
      if (!$query->isSuccessful() || empty($query->getData()['locations']['data'])) {
        return null;
      }
      return collect($query->getData()['locations']['data'])->map(function ($location) {
        return (object) $location;
      });
    });
    return $locations;
  }

  /**
   * Returns the pricing plans configured on the server.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getPricingPlans(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    # get the company
    $plans = Cache::remember('pricing_plans', 1800, function () use ($sdk) {
      $query = $sdk->createPlanResource()->send('get');
      # get the response
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($plan) {
        return (object) $plan;
      });
    });
    return $plans;
  }

  /**
   * Returns the products in the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getProducts(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $products = Cache::remember('business.products.'.$company->id, 30, function () use ($sdk, $company) {
      $query = $sdk->createProductResource()->addQueryArgument('limit', 10000)->send('GET');
      # get the response
      if (!$query->isSuccessful() || empty($query->getData())) {
        return null;
      }
      return collect($query->getData())->map(function ($product) {
        return (object) $product;
      });
    });
    return $products;
  }

  /**
   * Returns the product categories on the account.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getProductCategories(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company

    $categories = Cache::remember('business.product-categories.'.$company->id, 30, function () use ($sdk) {
      $query = $sdk->createProductCategoryResource()->addQueryArgument('limit', 10000)->send('GET');
      # get the response
      if (!$query->isSuccessful() || empty($query->getData())) {
        return null;
      }
      return collect($query->getData())->map(function ($category) {
        return (object) $category;
      });
    });
    return $categories;
  }

  /**
   * Returns the professional profile for the currently authenticated user.
   *
   * @param Sdk|null    $sdk
   *
   * @return mixed
   */
  public function getProfessionalProfile(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $id = \auth()->user()->id;
    //Cache::forget('professional.profile.'.$id);
    $profile = Cache::remember('professional.profile.'.$id, 30, function () use ($sdk, $id) {
      $query = $sdk->createDirectoryResource($id)->send('get');
      # get the response
      if (!$query->isSuccessful() || empty($query->getData())) {
        return null;
      }
      return $query->getData(true);
    });
    return $profile;
  }

  /**
   * Returns the service categories.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getProfessionalServiceCategories(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $profile = Cache::remember('professional.service_categories', 30, function () use ($sdk) {
      $query = $sdk->createDirectoryResource()->send('get', ['categories']);
      # get the response
      if (!$query->isSuccessful() || empty($query->getData())) {
        return null;
      }
      return collect($query->getData())->map(function ($category) {
        return (object) $category;
      });
    });
    return $profile;
  }

  /**
   * Returns the subdomains belonging to the currently authenticated business.
   *
   * @param Sdk|null $sdk
   *
   * @return mixed
   */
  public function getSubDomains(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    Cache::forget('ecommerce.subdomains.'.$company->id);
    $subdomains = Cache::remember('ecommerce.subdomains.'.$company->id, 30, function () use ($sdk) {
      $response = $sdk->createDomainResource()->addQueryArgument('limit', 1000)->send('get', ['issuances']);
      if (!$response->isSuccessful()) {
        return null;
      }
      return collect($response->getData())->map(function ($subdomain) {
        return (object) $subdomain;
      });
    });
    return $subdomains;
  }

  /**
   * Returns the teams in the currently authenticated company.
   *
   * @param Sdk|null $sdk
   *
   * @return Collection|null
   */
  public function getTeams(Sdk $sdk = null)
  {
    $sdk = $sdk ?: app(Sdk::class);
    $company = auth()->user()->company(true, true);
    # get the company
    $teams = Cache::remember('business.teams.'.$company->id, 30, function () use ($sdk) {
      $query =  $sdk->createTeamResource()->addQueryArgument('limit', 10000)->send('get');
      # send the request
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($team) {
        return (object) $team;
      });
    });
    return $teams;
  }

  /**
   * @param Sdk         $sdk
   * @param string|null $countryId
   *
   * @return mixed
   */
  public static function getDorcasStates(Sdk $sdk, string $countryId = null)
  {
    $states = Cache::remember('dorcas_all_states_cache', 30, function () use ($sdk, $countryId) {
      $resource = $sdk->createStateResource()->addQueryArgument('include', 'country')
        ->addQueryArgument('limit', 1000);
      if (!empty($countryId)) {
        $resource->addQueryArgument('country_id', $countryId);
      }
      $query = $resource->send('GET');
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($state) {
        return (object) $state;
      });
    });
    return $states;
  }

  /**
   * @param Sdk $sdk
   *
   * @return mixed
   */
  public static function getCountries(Sdk $sdk)
  {
    $countries = Cache::remember('dorcas_all_countries_cache', 30, function () use ($sdk) {
      $query = $sdk->createCountryResource()->addQueryArgument('include', 'states')
        ->addQueryArgument('limit', 1000)
        ->send('GET');
      if (!$query->isSuccessful()) {
        return null;
      }
      return collect($query->getData())->map(function ($country) {
        return (object) $country;
      });
    });
    return $countries;
  }

  /**
   * Sets the UiResponse instance in the views data.
   *
   * @param Request $request
   *
   * @return $this
   */
  protected function setViewUiResponse(Request $request = null)
  {
    if ($request->session()->has('UiResponse')) {
      $this->data['uiResponse'] = $request->session()->pull('UiResponse');
    } elseif ($request->session()->has('UiToast')) {
      $this->data['uiToast'] = $request->session()->pull('UiToast');
    }
    return $this;
  }

  /**
   * Sets the notification items for the views.
   *
   * @param Request            $request
   * @param TablerNotification ...$notifications
   */
  protected function setViewUiNotifications(Request $request, TablerNotification ...$notifications)
  {
    $collection = new TablerNotificationCollection();
    foreach ($notifications as $notification) {
      $collection->add($notification);
    }
    $request->session()->put('UiNotifications', $collection->toArray());
  }

  protected function getTaxAuth(){
    return (object) new TaxAuthorities;
  }
}
