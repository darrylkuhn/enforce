Enforce
=======

Enforce is an add on for **Laravel4** and provides an elegant way to define custom data access enforcers on your Eloquent ORM models.

## Quick start

In the `require` key of `composer.json` file add the following

    "darrylkuhn/enforce": "dev-master"

Run the Composer update comand

    $ composer update

In `config/app.php` in the $aliases array replace the existing 'Eloquent' key with 'Enforce\Model':

```php
'aliases' => array(

    'App'        => 'Illuminate\Support\Facades\App',
    'Artisan'    => 'Illuminate\Support\Facades\Artisan',
    ...
    'Eloquent'   => 'Enforce\Model',

),
```

### Configuration

Enforce uses the standard Laravel config. Create `app/config/enforce.php` with the following:

```php
<?php
return [
    'byDefault' => false,
];
```

Of course you can set this to true if you'd like but read throught he entire quickstart before doing so (there are good reasons to initialize your application with enforce off.

### Usage

Your models should continue to extend Eloquent. Behind the scenes they're really extending Enforce\Model which in turn extends \Illuminate\Database\Eloquent\Model.

Your models now inherit a few new static methods including enforceOnRead() and enforceFilter(). 
 - enforceOnRead() takes a single parameter, either a Model or Collection. By default enforceOnRead() does nothing, its simply a passthru returning the model or collection it was given, this is where you can add your custom enforcement rules. 
 - enforceFilter() takes a Model or Collection and filters out any model if the $key (member variable) does not match the $reference value.
For example imagine you wanted to make sure the currently authenticated user could only access their own user model. You could implement such a restriction with the following code:

```php
<?php

class User extends Eloquent
{
	public static function enforceOnRead( $models )
    {
        // If the user is not logged in then they can't read user data period
        if ( !Auth::check() ) 
        {
            return null;
        }
        // Filter out any results that don't belong to the user
        else 
        {
            $key = 'id';
            $refrenceValue = Auth::user()->id;
            return self::enforceFilter($models, $key, $refrenceValue);
        }
    }
}
```
This filters out any models who's id doesn't match the id of the currently authenticated user. Now calls to ```php User::find($id); ``` will return filtered results. To be a little more useful let's say you wanted to allow "admins" to access all models - you could implement the following:

```php
<?php

class User extends Eloquent
{
	public static function enforceOnRead( $models )
    {
        // If the user is not logged in then they can't read user data period
        if ( !Auth::check() ) 
        {
            return null;
        }
        // If our user isn't an admin then we need to be sure to 
        // filter out any results that aren't theirs
        elseif ( ! Auth::user()->isAdmin() )
        {
            $key = 'id';
            $refrenceValue = Auth::user()->id;
            return self::enforceFilter($models, $key, $refrenceValue);
        }
        // Otherwise they can see anything.
        else 
        {
            return $models;
        }
    }
}
```

enforceFilter() can accpet complex keys (e.g. setting $key = 'primaryCompany()->locations[0]->id' will evaluate $model->primaryCompany()->locations[0]->id correctly)

If necessary You may bypass enforcement by explicitly setting enforcement to false in the call ```php User::find($id, ['*'], false);```

### Startup
In some cases it's adventageous to leave enforcement off until your app has reached some state. In the example above if enforcement is on and we do not explicity set enforcement to false in our User::find() call authentication will fail. This is because the rule requires a valid authenticated user to access user models and the authentication system uses the user model to authenticate - chicken meet egg. There are several ways to solve for this; you can of course flag calls in the authentication subsystem but this requires hacking the Laravel core (so it's not recommended). Assuming you're using a filter to authenticate a user prior to routing my recommendation is to initialize the app with the config option ```php enforce.byDefault = false``` and then add a filter which flips it to true once the authentication is complete. For example in add the following filter to filters.php 

```php
Route::filter('app.applyEnforce', function()
{
    // Make sure our models enforce their access rules by default from here on out
    Config::set('enforce.byDefault', true);
});
```

Then include it in the appropriate routes call:

```php
Route::group(array('before' => array('auth.basic', 'app.applyEnforce') ), function()
{
    // User Management
    Route::resource('users/{id}/roles', 'UserRoleController', ['only' => ['index', 'store', 'delete', 'describe']]);
});
```