<?php

namespace Michaelmetz\Passwordprotect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use UnexpectedValueException;

/**
 * Controller for the PasswordProtect Package
 *
 * @author     Michael Metz
 * @link       https://github.com/Michael-Metz
 */
class PasswordProtectController extends Controller
{
    private $desiredRouteKeyName;
    private $routeRouteKeyName;

    /**
     * PasswordProtectController constructor. sets session key names from config/passwordprotect.php
     */
    public function __construct(){
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
                return view('passwordprotect::passwordprotect', compact('protectedRoute'));
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
    function postForm(Request $request)
    {

        $request->session()->keep([$this->desiredRouteKeyName,$this->routeRouteKeyName]);

        // Validate
        $validate = ['password' => 'required'];

        // I suggest also using recaptha to prevent brute force attacks, this is to add it to validation from the view

        if(config('passwordprotect.use_greggilbert_recaptcha'))
        {
            if(config('recaptcha.version') == 2)
                $validate['g-recaptcha-response'] = 'required|recaptcha';
            else if(config('recaptcha.version') == 1)
                $validate['recaptcha_response_field'] = 'required|recaptcha';
            else
                throw new UnexpectedValueException("recaptcha is config version:" .config('recaptcha.version') . "fix is needed to make this work with recaptcha again");

        }

        $this->validate($request, $validate);

        // Pull route variables from session;
        $desiredRoute = $request->session()->get($this->desiredRouteKeyName);
        $rootRoute = $request->session()->get($this->routeRouteKeyName);

        // Pull valid password from .env
        $rootRouteKey = $this->generateRootRouteKey($rootRoute);
        $validPassword = $this->getValidPasswordFromEnv($rootRouteKey);
        $enteredPassword = $request->input('password');

        // Check if password is correct
        if ($enteredPassword != $validPassword) {
            $request->session()->forget('errors');
            return redirect()->back();
        }

        // Password is correct; get rid of leftover session stuff
        $request->session()->forget($this->desiredRouteKeyName);
        $request->session()->forget($this->routeRouteKeyName);

        // Put session key and value that will allow the user to pass through the PasswordProtectPage middleware
        $hashedSessionPassword = bcrypt($validPassword);

        $request->session()->put($rootRouteKey,$hashedSessionPassword);
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
    private function getValidPasswordFromEnv($rootRouteKey){
        $validPassword = env($rootRouteKey);

        if ($validPassword == null)
            throw new UnexpectedValueException("Password Protect: \"" . $rootRouteKey ."\" was not set in the .env file");

        return $validPassword;
    }

	/**
	 * Generates the root route key for the root route
	 *
	 * @param $rootRoute
	 * @return String - the root route path as a key
	 */
    public function generateRootRouteKey($rootRoute){
        return 'PP_' . strtoupper($rootRoute);
    }

}
