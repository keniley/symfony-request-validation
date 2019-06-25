<?php

namespace Keniley\RequestValidation;

use Symfony\Component\Validator\Constraints\Collection;

interface RequestInterface
{
    /**
     *  The validation rules
     *  Overwrite this method in child class
     *
     *  @return Collection
     */
    public function rules(): Collection;

    /**
     *  Get all input data from frequest
     *
     *  @return array
     */
    public function all(): array;

    /**
     *  Get a subset containing the provided keys with values from the input data
     *
     *  @param mixed $keys
     *  @return array
     */
    public function only($keys): array;

    /**
     *  Get all of the input except for a specified array of items
     *
     *  @param mixed $keys
     *  @return array
     */
    public function except($keys): array;

    /**
     *  Determine if the request contains a given input item key
     *
     *  @param mixed $key
     *  @return bool
     */
    public function has($key): bool;

    /**
     *  Determine if the current request is asking for JSON in return
     *
     *  @return bool
     */
    public function wantsJson(): bool;

    /**
     *  Determine if the request is sending JSON
     *
     *  @return bool
     */
    public function isJson(): bool;

    /**
     *  Check input data by rules
     *
     *  @param Collection $rules
     *  @return RequestInterface
     */
    public function validate(Collection $rules = null): RequestInterface;
}
