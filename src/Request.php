<?php

namespace Keniley\RequestValidation;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Validation;
use Keniley\RequestValidation\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Request implements RequestInterface
{
    /**
     *  Search string for json
     *
     *  @var array
     */
    const JSON = ['/json', '+json'];

    /**
     *  Original request
     *
     *  @var RequestStack
     */
    protected $original;

    /**
     *  Instance of validator
     *
     *  @var Validation
     */
    protected $validator;

    /**
     *  Instance of ConstraintViolationList
     *
     *  @var ConstraintViolationList
     */
    protected $violations;


    /**
     *  The constructor
     *
     *  @param RequestStack $request
     */
    public function __construct(RequestStack $request)
    {
        $this->original = $request->getCurrentRequest();
        $this->validator = Validation::createValidator();

        $this->validate();
    }

    /**
     *  The validation rules
     *  Overwrite this method in child class
     *
     *  @return Assert\Collection
     */
    public function rules(): Assert\Collection
    {
        return new Assert\Collection([]);
    }

    /**
     *  Get all input data from frequest
     *
     *  @return array
     */
    final public function all(): array
    {
        $params = [] + $this->original->query->all() + $this->original->request->all() + $this->original->files->all();

        $content = $this->original->getContent();

        if ($this->isJson() && $content) {
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                if (is_array($data) === false) {
                    $data = [$data];
                }

                $params = $params + $data;
            }
        }

        return $params;
    }

    /**
     *  Get a subset containing the provided keys with values from the input data
     *
     *  Note: Adapted from laravel/laravel
     *
     *  @see https://github.com/laravel/framework/blob/5.8/LICENSE.md
     *
     *  @param mixed $keys
     *  @return array
     */
    final public function only($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = [];

        $input = $this->all();

        foreach ($keys as $key) {
            $results[$key] = $input[$key] ?? null;
        }

        return $results;
    }

    /**
     *  Get all of the input except for a specified array of items
     *
     *  Note: Adapted from laravel/laravel
     *
     *  @see https://github.com/laravel/framework/blob/5.8/LICENSE.md
     *
     *  @param mixed $keys
     *  @return array
     */
    final public function except($keys): array
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        $results = $this->all();

        foreach ($keys as $key) {
            unset($results[$key]);
        }

        return $results;
    }

    /**
     *  Determine if the request contains a given input item key
     *
     *  Note: Adapted from laravel/laravel
     *
     *  @see https://github.com/laravel/framework/blob/5.8/LICENSE.md
     *
     *  @param mixed $key
     *  @return bool
     */
    final public function has($key): bool
    {
        $keys = is_array($key) ? $key : func_get_args();

        $input = $this->all();

        foreach ($keys as $value) {
            if (array_key_exists($value, $input) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Determine if the current request is asking for JSON in return
     *
     *  Note: Adapted from laravel/laravel
     *
     *  @see https://github.com/laravel/framework/blob/5.8/LICENSE.md
     *
     *  @return bool
     */
    final public function wantsJson(): bool
    {
        $accept = $this->original->getAcceptableContentTypes();

        $result = false;

        if ($accept) {
            foreach ($accept as $type) {
                foreach (self::JSON as $search) {
                    if (mb_strpos($type, $search) !== false) {
                        $result = true;
                    }
                }
            }
        }

        return $result;
    }

    /**
     *  Determine if the request is sending JSON
     *
     *  Note: Adapted from laravel/laravel
     *
     *  @see https://github.com/laravel/framework/blob/5.8/LICENSE.md
     *
     *  @return bool
     */
    final public function isJson(): bool
    {
        $content = $this->original->getContentType();

        $result = false;

        if ($content) {
            foreach (self::JSON as $search) {
                if (mb_strpos($content, $search) !== false) {
                    $result = true;
                }
            }
        }

        return $result;
    }

    /**
     *  Determine if the request is valid
     *
     *  @return bool
     */
    final public function isValid(): bool
    {
        if (null !== $this->violations) {
            if ($this->violations->count()) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Get validation errors from validator
     *
     *  @return array
     */
    final public function errors(): array
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $errors = [];

        if ($this->violations instanceof ConstraintViolationList) {
            $iterator = $this->violations->getIterator();

            foreach ($iterator as $key => $violation) {
                $entryErrors = (array) $propertyAccessor->getValue($errors, $violation->getPropertyPath());
                $entryErrors[] = $violation->getMessage();

                $propertyAccessor->setValue($errors, $violation->getPropertyPath(), $entryErrors);
            }
        }

        return $errors;
    }

    /**
     *  Check input data by rules
     *
     *  @param Assert\Collection $rules
     *  @return RequestInterface
     */
    final public function validate(Assert\Collection $rules = null): RequestInterface
    {
        $rules = $rules ?? $this->rules();

        $this->violations = $this->validator->validate($this->all(), $rules);

        if (method_exists($this, 'after')) {
            $this->after();
        }

        return $this;
    }

    /**
     *  Read data from the original request
     *
     *  @param string $property
     *  @return mixed
     */
    final public function __get(string $property)
    {
        if (property_exists($this->original, $property)) {
            return $this->original->$property;
        }

        $message = sprintf("Get undefined property %s in class %s", $property, __CLASS__);
        trigger_error($message, E_USER_WARNING);

        return null;
    }

    /**
     *  Set data to the original request
     *
     *  @param string $property
     *  @return RequestInterface
     */
    final public function __set(string $property, $value): RequestInterface
    {
        if (property_exists($this->original, $property)) {
            $this->original->$property = $value;
            return $this;
        }

        $message = sprintf("Set data to undefined property %s in class %s", $property, __CLASS__);
        trigger_error($message, E_USER_WARNING);

        return $this;
    }

    /**
     *  Determine if the proterty exist in the original request
     *
     *  @param string $property
     *  @return bool
     */
    final public function __isset(string $property): bool
    {
        return isset($this->original->$property);
    }

    /**
     *  Unset the data from the original request
     *
     *  @param string $property
     */
    final public function __unset(string $property)
    {
        unset($this->original->$property);
    }

    /**
     *  Call the method in the original request
     *
     *  @param string $name
     *  @param array $arguments
     *  @return mixed
     */
    final public function __call(string $name, array $arguments)
    {
        if (method_exists($this->original, $name)) {
            return $this->original->$name(...$arguments);
        }

        $message = sprintf("Call undefined method %s in class %s", $name, __CLASS__);
        trigger_error($message, E_USER_WARNING);

        return null;
    }
}
