<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AmisEnrolledStudentCollection extends ResourceCollection
{
    public static $wrap = 'enrolled_students';

    public function toArray(Request $request): array
    {
        // Students are already shaped like the AMIS payload.
        return $this->collection->all();
    }
}
