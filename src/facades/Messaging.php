<?php
/**
 * Project: Luxus Point | Messaging.php
 * User: Simon - Bryah.de
 * Date: 14.09.14
 * Time: 21:41
 */

namespace Bryah\Hermes\Facades;


use Illuminate\Support\Facades\Facade;

class Messaging extends Facade{

    protected static function getFacadeAccessor() { return 'messaging'; }
}
