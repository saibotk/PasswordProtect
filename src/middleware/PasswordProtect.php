<?php

namespace Michaelmetz\Passwordprotect\Middleware;

use Closure;
use Illuminate\Contracts\Hashing\Hasher;
use UnexpectedValueException;
use InvalidArgumentException;

/**
 * Middleware for the PasswordProtect Package
 *
 * @author     Michael Metz
 * @link       https://github.com/Michael-Metz
 */
class PasswordProtect
{
    protected $hash;
    private $desiredRouteKeyName;
    private $routeRouteKeyName;

    /**
     * PasswordProtect constructor Sets session key names from config/passwordprotect.php
     *
     * @param Hasher $hash - used for checking hash password stored in session
     */
    public function __construct(Hasher $hash)
    {
        $this->hash = $hash;
        $this->desiredRouteKeyName = config('passwordprotect.desired_route_key_name');
        $this->routeRouteKeyName = config('passwordprotect.root_route_key_name');
    }

    /**
     *
     * @param  \Illuminate\Http\Request $request - used to get the route path its coming from
     * @param  \Closure                 $next - could be any route this middleware is attached to
     * @param                           $depth - how deep into the breadcrumb route you want to check
     *
     * @return next - if the session has valid route key with valid password
     * @return redirect - to password protect form page so user can attempt to supply a valid password
     *
	 * @throws InvalidArgumentException - if programmer supplies a bad $depth
	 * @throws UnexpectedValueException - if no route password is set
     */
    public function handle($request, Closure $next, $depth)
    {

        $rootRoute = "";

        $route = $request->path();
        $breadcrumbs = explode('/', $route);
        $breadcrumbsSize = count($breadcrumbs);

        // Validate that depth is correct or return a error.
        if ($breadcrumbsSize <= 0 || $breadcrumbsSize < $depth)
            throw new InvalidArgumentException("There is an error in defining the depth in your password protected routes:" . $route . "\ndepth:" . $depth);

        // Generate how shallow the protected route will be
        for ($i = 0; $i < $depth; $i++)
        {
            $rootRoute .= $breadcrumbs[$i];
            if($i != $depth -1)
                $rootRoute .= "/";
        }

        // Check if the protected route is noted in the session key='PP_[route]' value='env()' means user had entered correct password
        $rootRouteKey = $this->generateRootRouteKey($rootRoute);
        $routePassword = $this->getValidPasswordFromEnv($rootRouteKey);

        // If env returns null then the .env is not configured for this route
        if($routePassword == null)
                throw new UnexpectedValueException("Password Protect: \"" . $rootRouteKey ."\" was not set in the .env file");

        // Check if session holds the correct password for the given route
        if ($request->session()->has($rootRouteKey))
        {
            $hashedSessionPassword = $request->session()->get($rootRouteKey);

            if($this->hash->check($routePassword, $hashedSessionPassword))
            {
                //success user has already supplied the correct password!
                return $next($request);
            }
        }


        // Flash routes in session and let PasswordProtect Controller handle the rest.
        $request->session()->flash($this->desiredRouteKeyName, $route);
        $request->session()->flash($this->routeRouteKeyName, $rootRoute);

        return redirect("passwordprotect");
    }

    /**
     * Fetches the valid route password form the .env file
     * if the .env file is not configured for the root Route then an HttpException is thrown
     *
     * @param $rootRoute
     * @return String - the valid password from .env
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException 			- if .env does not contain the given rootRoute
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
