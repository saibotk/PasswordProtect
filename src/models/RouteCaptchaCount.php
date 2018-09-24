<?php
namespace Michaelmetz\Passwordprotect\Models;

use Illuminate\Database\Eloquent\Model;

class RouteCaptchaCount extends Model
{

    /**
     * @see Model::$timestamps
     */
    public $timestamps = true;

    /**
     * @see Model::$incrementing
     */
    public $incrementing = false;

    /**
     * @see Model::$primaryKey
     */
    protected $primaryKey = 'route';

    /**
     * @see Model::$table
     */
    protected $table = 'route_captcha_counts';

    /**
     * @see Model::$fillable
     */
    protected $fillable = [
        'route',
        'count'
    ];

    /**
     * Returns a sha256 hashed key for route names.
     * @param  string $key the route name.
     * @return string      the hashed route name as stored in the database.
     */
    public static function getHashedKey($key)
    {
        return hash('sha256', $key);
    }

    /**
     * Returns a model with the given route name matching the hash stored in the database.
     * @param  string 					$name 	the route name.
     * @return RouteCaptchaCount|null       	the model instance.
     */
    public static function getByRouteName($name)
    {
        $hashedname = RouteCaptchaCount::getHashedKey($name);
        return RouteCaptchaCount::where('route', $hashedname)->first();
    }

    /**
     * Returns either the model found with the route names hash in the database or returns a new instance of the model.
     * @param  string 				$name 	the route name.
     * @return RouteCaptchaCount       		the model instance.
     */
    public static function firstOrNewByRouteName($name)
    {
        $hashedname = RouteCaptchaCount::getHashedKey($name);
        return RouteCaptchaCount::firstOrNew(['route' => $hashedname]);
    }

	/**
	 * Returns true if the count value is exceeding the threshold.
	 * @return boolean
	 */
	public function isExceedingCountThreshold() {
		return $this->count >= config('passwordprotect.onfailure_captcha_counter_threshold');
	}
}
