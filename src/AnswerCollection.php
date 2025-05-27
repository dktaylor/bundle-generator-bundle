<?php

namespace Dktaylor\BundleGeneratorBundle;

use ArrayObject;

class AnswerCollection extends ArrayObject
{
    public function with(array $items): static
    {
        return new static($items);
    }

    public function get(string $name): mixed
    {
        if ($this->offsetExists($name)) {
            return $this->offsetGet($name);
        }

        return null;
    }

}
