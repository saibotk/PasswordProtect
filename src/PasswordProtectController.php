<?php

namespace Michaelmetz\Passwordprotect;

use Michaelmetz\Passwordprotect\Models\RouteCaptchaCount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UnexpectedValueException;

/**
 * Controller for the PasswordProtect Package
 *
 * @author     Michael Metz, saibotk
 * @link       https://github.com/Michael-Metz
 */
class PasswordProtectController extends Controller
{
    private $desiredRouteKeyName;
    private $routeRouteKeyName;

    /**
     * PasswordProtectController constructor. Sets session key names from config/passwordprotect.php
     */
    public function __construct()
    {
        $this->desiredRouteKeyName = config('passwordprotect.desired_route_key_name');
        $this->routeRouteKeyName = config('passwordprotect.root_route_key_name');
    }

    /**
     * All this does is check if $desiredRouteKeyName is in the session
     *
     * it was flashed to the session from the middleware/PasswordProtect.php
     * therefore the user will only get the password page if they came from the middleware.
     *
     * @param Request $request
     *
     * @return View - password protect view
     * @return redirect - if the this route was accessed without passing through middleware/PasswordProtect.php
     */
	 function getForm(Request $request)
     {
         // Only send the password form if request passed through PasswordProtect the middleware
		 if ($request->session()->exists($this->desiredRouteKeyName))
         {
             if($request->session()->exists($this->routeRouteKeyName))
             {
 				$request->session()->reflash();
                 $protectedRoute = $request->session()->get($this->desiredRouteKeyName);

 				$captchaneeded = false;
 				if(config('passwordprotect.use_onfailure_captcha_counter')) {
 					$routecaptchacount = RouteCaptchaCount::getByRouteName($protectedRoute);
					if(!is_null($routecaptchacount)) {
						$captchaneeded = $routecaptchacount->isExceedingCountThreshold();
					}
 				}

                 return view('passwordprotect::passwordprotect', compact('protectedRoute', 'captchaneeded'));
             }
         }

         return redirect('/');
     }

    /**
     * Checks if password matches the password that protects the desired route
     *
     * Then stores the rootRouteKey along with the hashed valid password into the session
     *
     * @param Request $request - with password input from Views\Vendor\Passwordprotect\passwordprotect.blade.php
     *
     * @return redirect - to desired protected route IF correct password is supplied.
     * @return redirect - back to form IF incorrect password
     *
     * @throws UnexpectedValueException - if the recaptha.version is wrong
     */
    public function postForm(Request $request)
    {
        $request->session()->keep([$this->desiredRouteKeyName,$this->routeRouteKeyName]);

        // Pull route variables from session;
        $desiredRoute = $request->session()->get($this->desiredRouteKeyName);
        $rootRoute = $request->session()->get($this->routeRouteKeyName);

        if (config('passwordprotect.use_onfailure_captcha_counter')) {
            $routecaptchacount = RouteCaptchaCount::getByRouteName($rootRoute);
        }

        // Validate
        $validate = ['password' => 'required'];

        // I suggest also using recaptcha to prevent brute force attacks, this is to add it to validation from the view.
        $reachedthreshold = !is_null($routecaptchacount) && $routecaptchacount->isExceedingCountThreshold();
        $checkcaptchaneeded = !config('passwordprotect.use_onfailure_captcha_counter') || $reachedthreshold;
        if ($checkcaptchaneeded) {
            if (config('passwordprotect.use_greggilbert_recaptcha')) {
                if (config('recaptcha.version') == 2) {
                    $validate['g-recaptcha-response'] = 'required|recaptcha';
                } elseif (config('recaptcha.version') == 1) {
                    $validate['recaptcha_response_field'] = 'required|recaptcha';
                } else {
                    throw new UnexpectedValueException("recaptcha is config version:" .config('recaptcha.version') . "fix is needed to make this work with recaptcha again");
                }
            } elseif (config('passwordprotect.use_securimage_captcha')) {
                $validate['captcha_code'] = 'required|string';
            }
        }

        $validator = \Validator::make($request->all(), $validate);

        if ($validator->fails()) {
            if (config('passwordprotect.use_onfailure_captcha_counter')) {
                $this->incrementRouteCaptchaCount($rootRoute);
            }
            return \Redirect::back()
                        ->withErrors($validator)
                        ->withInput();
        }

        if ($checkcaptchaneeded && config('passwordprotect.use_securimage_captcha')) {
            $image = new \Securimage();
            if ($image->check($request->captcha_code) !== true) {
                if (config('passwordprotect.use_onfailure_captcha_counter')) {
                    $this->incrementRouteCaptchaCount($rootRoute);
                }
                $request->session()->forget('errors');
                $errors= ['The captcha code you entered does not match'];
                return \Redirect::back()
                    ->withErrors($errors);
            }
        }

        // Pull valid password from .env
        $rootRouteKey = $this->generateRootRouteKey($rootRoute);
        $validPassword = $this->getValidPasswordFromEnv($rootRouteKey);
        $enteredPassword = $request->input('password');

        // Check if password is correct
        if ($enteredPassword != $validPassword) {
            if (config('passwordprotect.use_onfailure_captcha_counter')) {
                $this->incrementRouteCaptchaCount($rootRoute);
            }
            $request->session()->forget('errors');
            return redirect()->back()->withErrors('Wrong password');
        }

        // Password is correct; get rid of leftover session stuff
        $request->session()->forget($this->desiredRouteKeyName);
        $request->session()->forget($this->routeRouteKeyName);

        // Put session key and value that will allow the user to pass through the PasswordProtectPage middleware
        $hashedSessionPassword = bcrypt($validPassword);

        // Clear routes failure count
        if (config('passwordprotect.use_onfailure_captcha_counter')) {
            $routecaptchacount = RouteCaptchaCount::getByRouteName($rootRoute);
            if (!is_null($routecaptchacount)) {
                $routecaptchacount->delete();
            }
        }

        $request->session()->put($rootRouteKey, $hashedSessionPassword);
        return redirect($desiredRoute);
    }

    /**
     * Fetches the valid route password form the .env file
     * if the .env file is not configured for the root Route then an HttpException is thrown
     *
     * @param $rootRoute
     * @return String - the valid password from .env
     * @throws UnexpectedValueException	- if .env does not contain the given rootRoute
     */
    private function getValidPasswordFromEnv($rootRouteKey)
    {
        $validPassword = env($rootRouteKey);

        if ($validPassword == null) {
            throw new UnexpectedValueException("Password Protect: \"" . $rootRouteKey ."\" was not set in the .env file");
        }

        return $validPassword;
    }

    /**
     * Generates the root route key for the root route
     *
     * @param $rootRoute
     * @return String - the root route path as a key
     */
    public function generateRootRouteKey($rootRoute)
    {
        return 'PP_' . strtoupper($rootRoute);
    }

    /**
     * Increments a RouteCaptchaCount instances count variable with the given name, or creates a new instance and saves it to the database.
     * @param  [type] $route the route name.
     */
    private function incrementRouteCaptchaCount($route)
    {
        $routecaptchacount = RouteCaptchaCount::firstOrNewByRouteName($route);
        $routecaptchacount->count = $routecaptchacount->count + 1;
        $routecaptchacount->save();
    }
}
