# Validation in request for symfony

This package extends the standard symfony request class with several features, especially validation directly in the request.

## Installation

Install the package via composer:
```shell
$ composer require keniley/symfony-request-validation
```

## Add to services

```yaml
#config/services.yaml

services:
    Keniley\RequestValidation\Request:
        arguments: ['@Symfony\Component\HttpFoundation\RequestStack']
```

## Basic using

Change in your controller request class

```php
namespace App\Controller;

use Keniley\RequestValidation\Request;

class SomeController extends Controller
{
    public function someMethod(Request $request)
    {
        // code
    }
}
```

## New methods

### Get all input data from request.
Get data from query, request, file and JSON body.

```php
public function someMethod(Request $request)
{
    $data = $request->all(); // output array
}
```

### Get a subset containing the provided keys with values from the input data.
If key does not exist return null as value.

```php
public function someMethod(Request $request)
{
    $data = $request->only(['param1', 'param2']); // output array
    // or
    $data = $request->only('param1', 'param2'); // output array
}
```

###  Get all of the input except for a specified array of items.

```php
public function someMethod(Request $request)
{
    $data = $request->except(['param1', 'param2']); // output array
    // or
    $data = $request->except('param1', 'param2'); // output array
}
```

###  Determine if the request contains a given input item key. 
If you enter multiple keys, they must contain all of them.

```php
public function someMethod(Request $request)
{
    $has = $request->has(['param1', 'param2']); // output bool
    // or
    $has = $request->has('param1', 'param2'); // output bool
}
```

###  Determine if the current request is asking for JSON in return.
Determine by Accept header in request

```php
public function someMethod(Request $request)
{
    $json = $request->wantsJson(); // output bool
}
```

###  Determine if the request is sending JSON.
Determine by Content-Type header in request

```php
public function isJson(Request $request)
{
    $json = $request->wantsJson(); // output bool
}
```

## Original request

Of course you have all the standard methods and features of the original request.

```php
public function isJson(Request $request)
{
    $contentType = $request->getContentType();
}
```

## Validation in controller

You must first create a set of validation rules and then call the validate() method

```php
namespace App\Controller;

use Keniley\RequestValidation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class SomeController extends Controller
{
    public function someMethod(Request $request)
    {
        $rules = new Assert\Collection([
            'param1' =>  new Assert\NotBlank(),
        ]);

        if(! $request->validate($assert)->isValid()) {
            $errors = $request->errors(); // output array
        }

        $data = $request->all();

        // some logic
    }
}
```

## Validation in separate class

You can create a new class that inherits from the Keniley\RequestValidation\Request

### Create new request class:

```php
namespace App\Request;

use Keniley\RequestValidation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class UserUpdateRequest extends Request
{
    public function rules(): Assert\Collection
    {
        $rules = new Assert\Collection([
            'param1' =>  new Assert\NotBlank(),
        ]);

        return $rules;
    }
}
```

### Using in controller:

```php
namespace App\Controller;

use App\Request\UserUpdateRequest;

class SomeController extends Controller
{
    public function someMethod(UserUpdateRequest $request)
    {
        if(! $request->isValid()) {
            $errors = $request->errors(); // output array
        }
        
        $data = $request->all();
        // some logic
    }
}
```

## Usefull methods for validation

###  Determine if the request is valid

```php
public function someMethod(Request $request)
{
    $valid = $request->isValid(); // output bool
}
```

### Get validation errors from validator

```php
public function someMethod(Request $request)
{
    $errors = $request->errors(); // output array
}
```