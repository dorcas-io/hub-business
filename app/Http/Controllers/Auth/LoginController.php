<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Hostville\Dorcas\Sdk;
use Carbon\Carbon;
use App\Dorcas\Hub\Utilities\UiResponse\UiResponse;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/dashboard';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
        $this->data['page']['title'] = 'Login';
        $this->data['page']['login_product_name'] = !empty($partner->name) ? $partner->name : config('app.name');
    }

    /**
     * @inheritdoc
     */
    public function showLoginForm(Request $request)
    {
        $this->data['header']['title'] = 'Login';
        $this->setViewUiResponse($request);

        $sdk = app(Sdk::class);

        /*if (app()->getProvider(Dorcas\ModulesAuth\ModulesAuthServiceProvider::class)) {

            $authMedia = \Dorcas\ModulesAuth\Http\Controllers\ModulesAuthController::getLoginMedia($request, $sdk, "login", "all", "image");

            $this->data['authMedia'] = $authMedia;

        }  else {
            dd("no provider");
        }*/

        $authMedia = \Dorcas\ModulesAuth\Http\Controllers\ModulesAuthController::getAuthMedia($request, $sdk, "login", "all", "image");
        $this->data['authMedia'] = $authMedia;

        return view('modules-auth::login', $this->data);
        //return view('auth.login-v2', $this->data);
    }

    public function login(Request $request)
    {
        //dd("Hi");
        $this->validateLogin($request);
    
        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
    
            return $this->sendLockoutResponse($request);
        }
        
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }
    
        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);
    
        return $this->sendFailedLoginResponse($request);
    }


    /**
     * @param Request $request
     * @param         $dorcasUser
     */
    protected function authenticated(Request $request, $dorcasUser)
    {
        $viewMode = 'business';
        $company = $dorcasUser->company();
        # get the company information
        $companyConfiguration = (array) $company['extra_data'] ?? [];
        # get the company configuration
        if (empty($companyConfiguration['ui_setup'])) {
            # this key will be set after proper UI setup for the account; working on the assumption that an account
            # will not have "is_processional"/"is_vendor" set at account creation - unless the person signed up using
            # those specific modes. Once the key is set though, it will automatically load in "business" mode.
            if ($dorcasUser->is_professional) {
                $viewMode = 'professional';
            } elseif ($dorcasUser->is_vendor) {
                $viewMode = 'vendor';
            }
        }
        $request->session()->put('viewMode', $viewMode);
        # set it to the session

        //dd($company);

        $currentExpiry = Carbon::parse($company["access_expires_at"]);
        $createdDate = Carbon::parse($company["created_at"]);
        $dDay = Carbon::parse("30th March 2020 10:08 PM");

        if ($createdDate->lessThan($dDay) && $currentExpiry->lessThan($dDay->addYear()) ) { //update the expiry if old customer whose expiry has not  been updated
            //dd($dDay);  && $currentExpiry->lessThan($dDay->addYear())
            
            try {
                $new_expiry = Carbon::now()->addYear()->subDay()->endOfDay();

                $sdk = app(Sdk::class);
                $query = $sdk->createCompanyService()->addBodyParam('access_expires_at', $new_expiry->format('Y-m-d H:i:s'))
                                                    ->addBodyParam('update_expiry', 'yes')
                                                    ->send('PUT');

                if (!$query->isSuccessful()) {
                    //throw new \RuntimeException('Failed while updating your business information. Please try again.');
                    $response = (tabler_ui_html_response([$query->getErrors()[0]['title'] ?? 'Error Updating  Subscription']))->setType(UiResponse::TYPE_ERROR);
                } else {
                    $response = (tabler_ui_html_response(['Congratulations! Your subscription expiry has been successfully extended till ' . $new_expiry->toDayDateTimeString() . '!']))->setType(UiResponse::TYPE_SUCCESS);
                }
            } catch (\Exception $e) {
                $response = (tabler_ui_html_response([$e->getMessage()]))->setType(UiResponse::TYPE_ERROR);
            }
        }

        try {
            # we use this opportunity to sync the user data with what we have in our db
            DB::transaction(function () use ($dorcasUser) {
                $data = $dorcasUser->company(true, true);
                # get the company data
                $company = Company::firstOrNew(['uuid' => $data->id]);
                # get the company
                $company->reg_number = $data->registration;
                $company->name = $data->name;
                $company->phone = $data->phone;
                $company->email = $data->email;
                $company->website = $data->website;
                # set the properties
                $company->save();

                $user = User::firstOrNew(['uuid' => $dorcasUser->id]);
                $user->company_id = $company->id;
                $user->firstname = $dorcasUser->firstname;
                $user->lastname = $dorcasUser->lastname;
                $user->gender = $dorcasUser->gender;
                $user->email = $dorcasUser->email;
                $user->password = $dorcasUser->password;
                $user->phone = $dorcasUser->phone;
                $user->photo_url = $dorcasUser->photo;
                $user->is_verified = $dorcasUser->is_verified;
                # set the properties
                $user->save();
                # update the data
            });
        } catch (\Throwable $e) {
            // be quiet about it
            Log::error($e->getMessage());
        }
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        return $this->loggedOut($request) ?: redirect('/login');
    }

}
