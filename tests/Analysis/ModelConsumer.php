<?php

declare(strict_types=1);

namespace Essa\APIToolKit\Tests\Analysis;

use Essa\APIToolKit\Filters\Filterable;
use Essa\APIToolKit\Traits\HasActivation;
use Essa\APIToolKit\Traits\HasCache;
use Essa\APIToolKit\Traits\HasCreatedBy;
use Essa\APIToolKit\Traits\HasGeneratedCode;
use Essa\APIToolKit\Traits\HasOrder;
use Essa\APIToolKit\Traits\Sluggable;
use Illuminate\Database\Eloquent\Model;

/** @property string $name
 * @property string $slug
 * @property string $code
 * @property bool $is_active
 * @property int|string|null $created_by */
class ModelConsumer extends Model
{
    use Filterable;
    use HasActivation;
    use HasCache;
    use HasCreatedBy;
    use HasGeneratedCode;
    use HasOrder;
    use Sluggable;
}
